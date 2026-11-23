<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Metademand_Resource;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use GlpiPlugin\Resources\ContractType;
use Group;
use ITILValidationTemplate;
use SimpleXMLElement;
use User;

/**
 * Summary of a meta-demand file before its import (export.form.php?import_form=1): what the
 * import creates, the errors preventing it, what it cannot carry over to this GLPI; then the
 * import of the file waiting for it, once its summary was seen.
 */
class ExportImportSummaryTest extends DbTestCase
{
    private const SESSION_KEY = 'plugin_metademands_pending_import';

    /** @var string[] files written by the test */
    private array $files = [];

    public function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        unset($_SESSION[self::SESSION_KEY]);

        parent::tearDown();
    }

    /**
     * A meta-demand with a block, a text, a radio with its values, an option, a child ticket
     * whose content shows the text: the XML of its export.
     */
    private function exportMetademand(string $name): string
    {
        $entities_id = $this->getTestRootEntity(true);
        $metademand  = $this->createItem(Metademand::class, [
            'name'             => $name,
            'entities_id'      => $entities_id,
            'object_to_create' => 'Ticket',
            'type'             => \Ticket::DEMAND_TYPE,
        ]);
        $base = [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'rank'                              => 1,
            'entities_id'                       => $entities_id,
        ];
        $text  = $this->createItem(Field::class, $base + ['type' => 'text', 'item' => '', 'name' => 'Text', 'order' => 1], ['order', 'item']);
        $radio = $this->createItem(Field::class, $base + ['type' => 'radio', 'item' => '', 'name' => 'Radio', 'order' => 2], ['order', 'item']);
        foreach ([$text, $radio] as $field) {
            $this->createItem(FieldParameter::class, ['plugin_metademands_fields_id' => $field->getID()]);
        }
        $yes = $this->createItem(FieldCustomvalue::class, ['plugin_metademands_fields_id' => $radio->getID(), 'name' => 'Yes', 'rank' => 0]);
        $this->createItem(FieldCustomvalue::class, ['plugin_metademands_fields_id' => $radio->getID(), 'name' => 'No', 'rank' => 1]);
        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $radio->getID(),
            'check_value'                  => $yes->getID(),
            'hidden_link'                  => $text->getID(),
        ], ['check_value']);
        $child = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => Task::TICKET_TYPE,
            'name'                              => 'Child',
            'level'                             => 1,
            'entities_id'                       => $entities_id,
            'formatastable'                     => 0,
            'useBlock'                          => 0,
            'block_use'                         => '[]',
        ]);
        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $child->getID(),
            'content'                     => '<p>#' . $text->getID() . '#</p>',
            'entities_id'                 => 0,
        ]);

        $target = Export::exportAsXMLForMetademands($metademand->getID());
        $this->assertNotEmpty($target);
        $xml = (string) file_get_contents($target['path']);
        @unlink($target['path']);

        return $xml;
    }

    /**
     * XML document of a meta-demand made of the given data, as an export writes it.
     *
     * @param array<string, mixed> $data
     */
    private function toXml(array $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><metademand></metademand>');
        Export::toXml($xml, $data);

        return (string) $xml->asXML();
    }

    /**
     * The file waiting for its import, as prepareImport() keeps it after the upload.
     *
     * @return string the token of its summary
     */
    private function setPendingImport(string $xml): string
    {
        $directory = GLPI_PLUGIN_DOC_DIR . '/metademands';
        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }
        $file = uniqid('import_', true) . '.xml';
        file_put_contents($directory . '/' . $file, $xml);
        $this->files[] = $directory . '/' . $file;

        $token = bin2hex(random_bytes(16));
        $_SESSION[self::SESSION_KEY] = ['token' => $token, 'file' => $file, 'filename' => 'my_export.xml'];

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $xml): array
    {
        $file = GLPI_TMP_DIR . '/' . uniqid('md_import_summary_', true) . '.xml';
        file_put_contents($file, $xml);
        $this->files[] = $file;
        $datas = Export::readImportFile($file);
        $this->assertIsArray($datas);

        return $datas;
    }

    private function capture(callable $render): string
    {
        ob_start();
        $render();

        return (string) ob_get_clean();
    }

    /**
     * The contract types of the Resources plugin, from its sources next to this plugin: the test
     * database holds its tables, the plugin is not active in the test environment.
     */
    private function useResourcesPlugin(): void
    {
        global $DB;

        $sources = dirname(__DIR__, 2) . '/resources/src/';
        if (!is_file($sources . 'ContractType.php') || !$DB->tableExists('glpi_plugin_resources_contracttypes')) {
            $this->markTestSkipped('The Resources plugin is not available next to this plugin');
        }
        if (!class_exists(ContractType::class, false)) {
            // A loader of its own, as GLPI registers for an active plugin: the loader of GLPI
            // remembers the classes it could not find (testWithoutTheResourcesPlugin…)
            $loader = new \Composer\Autoload\ClassLoader();
            $loader->addPsr4('GlpiPlugin\\Resources\\', $sources);
            $loader->register(true);
            $this->assertTrue(class_exists(ContractType::class));
        }
    }

    private function createContractType(string $name): ContractType
    {
        return $this->createItem(ContractType::class, [
            'name'         => $name,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);
    }

    public function testAnExportOfThisGlpiHasNoError(): void
    {
        $this->login();
        $name = 'Summary ' . mt_rand();
        $datas = $this->read($this->exportMetademand($name));

        $summary = Export::analyzeImportData($datas, $this->getTestRootEntity(true));

        $this->assertSame([], $summary['errors']);
        $this->assertSame($name, $summary['name']);
        $this->assertSame(\Ticket::getTypeName(1), $summary['object_to_create']);
        $this->assertSame(\Ticket::getTicketTypeName(\Ticket::DEMAND_TYPE), $summary['type']);
        $this->assertSame(PLUGIN_METADEMANDS_VERSION, $summary['version']);
        $counts = array_column($summary['counts'], 'count', 'label');
        $this->assertSame(1, $counts[_n('Block', 'Blocks', 1, 'metademands')]);
        $this->assertSame(2, $counts[_n('Field', 'Fields', 2, 'metademands')]);
        $this->assertSame(2, $counts[FieldCustomvalue::getTypeName(2)]);
        $this->assertSame(1, $counts[FieldOption::getTypeName(1)]);
        $this->assertSame(1, $counts[sprintf('%1$s (%2$s)', __('Child creation', 'metademands'), Task::getTaskTypeName(Task::TICKET_TYPE))]);

        // Imported again on the GLPI it comes from, the meta-demand gets a second copy
        $this->assertSame(
            [sprintf(__('A meta-demand named "%s" already exists: the import creates another one', 'metademands'), $name)],
            $summary['warnings'],
        );
        $source = new Metademand();
        $this->assertTrue($source->getFromDBByCrit(['name' => $name]));
        $this->assertTrue($source->delete(['id' => $source->getID()], true));
        $this->assertSame([], Export::analyzeImportData($datas, $this->getTestRootEntity(true))['warnings']);
    }

    public function testWhatTheImportCannotCarryOverIsReported(): void
    {
        $this->login();
        $missing = 999999;
        $this->assertFalse((new User())->getFromDB($missing));
        $this->assertFalse((new Group())->getFromDB($missing));
        $this->assertFalse((new ITILValidationTemplate())->getFromDB($missing));

        $datas = $this->read($this->toXml([
            'version'          => '99.0.0',
            'name'             => 'Problems ' . mt_rand(),
            'object_to_create' => 'Ticket',
            'type'             => \Ticket::INCIDENT_TYPE,
            'metafields'       => [
                // "Display field after" a field missing from the file
                'field1' => ['id' => 1, 'type' => 'text', 'item' => '', 'name' => 'Text', 'rank' => 1, 'plugin_metademands_fields_id' => 77],
                'field2' => ['id' => 2, 'type' => 'superfield', 'item' => '', 'name' => 'Super', 'rank' => 1],
                'field3' => ['id' => 3, 'type' => 'dropdown', 'item' => 'GlpiPlugin\\Missing\\Thing', 'name' => 'Thing', 'rank' => 2],
            ],
            'metafieldoptions' => [
                'fieldoptions1' => ['id' => 1, 'plugin_metademands_fields_id' => 99, 'check_value' => 1],
                'fieldoptions2' => [
                    'id'                           => 2,
                    'plugin_metademands_fields_id' => 1,
                    'hidden_link'                  => 55,
                    'plugin_metademands_tasks_id'  => 66,
                    'users_id_validate'            => $missing,
                    'itilvalidationtemplates_id'   => $missing,
                ],
            ],
            'tasks' => [
                'task5' => [
                    'id'                          => 5,
                    'type'                        => Task::TICKET_TYPE,
                    'name'                        => 'Child #88#',
                    'plugin_metademands_tasks_id' => 0,
                    'tickettask'                  => ['content' => 'Text #1#', 'groups_id_assign' => $missing],
                ],
            ],
            'metatasks' => ['metatask1' => ['plugin_metademands_tasks_id' => 42]],
        ]));

        $summary = Export::analyzeImportData($datas, $this->getTestRootEntity(true));

        $this->assertSame([], $summary['errors']);
        foreach ([
            sprintf(__('The file comes from a more recent version of the plugin (%1$s) than this one (%2$s): the settings unknown here are not imported', 'metademands'), '99.0.0', PLUGIN_METADEMANDS_VERSION),
            sprintf(__('Fields of a type unknown here, a plugin may be missing: %s', 'metademands'), 'superfield'),
            sprintf(__('Fields on objects unknown here, a plugin may be missing: %s', 'metademands'), 'GlpiPlugin\\Missing\\Thing'),
            sprintf(__('%1$s attached to no field of the file, not imported: %2$d', 'metademands'), FieldOption::getTypeName(2), 1),
            // The "display after" field and the field the option hides
            sprintf(_n('%d link to a field missing from the file is dropped', '%d links to fields missing from the file are dropped', 2, 'metademands'), 2),
            // The child object of the option, and of the link to a meta-demand
            sprintf(_n('%d link to a child object missing from the file is dropped', '%d links to child objects missing from the file are dropped', 2, 'metademands'), 2),
            sprintf(__('Tags pointing at fields missing from the file: %s', 'metademands'), '#88#'),
            sprintf(__('%1$s not found on this GLPI, to set again after the import: %2$s', 'metademands'), User::getTypeName(1), '#' . $missing),
            sprintf(__('%1$s not found on this GLPI, to set again after the import: %2$s', 'metademands'), ITILValidationTemplate::getTypeName(1), '#' . $missing),
            sprintf(__('%1$s not found on this GLPI, to set again after the import: %2$s', 'metademands'), Group::getTypeName(1), '#' . $missing),
        ] as $warning) {
            $this->assertContains($warning, $summary['warnings']);
        }
        $this->assertCount(10, $summary['warnings']);
    }

    public function testAMetademandCreatingAnUnknownObjectCannotBeImported(): void
    {
        $this->login();

        $summary = Export::analyzeImportData(['name' => 'Computer', 'object_to_create' => 'Computer'], 0);
        $this->assertSame([sprintf(__('The meta-demand creates an unknown object: %s', 'metademands'), 'Computer')], $summary['errors']);

        $summary = Export::analyzeImportData(['name' => 'Nothing'], 0);
        $this->assertSame([__('The file does not give the object the meta-demand creates', 'metademands')], $summary['errors']);
    }

    public function testOnlyAMetademandExportIsRead(): void
    {
        $file = GLPI_TMP_DIR . '/' . uniqid('md_import_summary_', true) . '.xml';
        $this->files[] = $file;

        file_put_contents($file, '<?xml version="1.0"?><form><name>Other</name></form>');
        $error = null;
        $this->assertNull(Export::readImportFile($file, $error));
        $this->assertSame(__('The file is not a meta-demand export', 'metademands'), $error);

        file_put_contents($file, '<metademand><name>Broken</metademand>');
        $error = null;
        $this->assertNull(Export::readImportFile($file, $error));
        $this->assertSame(__('The file is not an XML file', 'metademands'), $error);
    }

    public function testTheSummaryIsShownBeforeTheImport(): void
    {
        $this->login();
        $name  = 'Pending ' . mt_rand();
        $xml   = $this->exportMetademand($name);
        $token = $this->setPendingImport($xml);

        $html = $this->capture(fn() => $this->assertTrue(Export::showImportSummary()));
        $this->assertStringContainsString('my_export.xml', $html);
        $this->assertStringContainsString($name, $html);
        $this->assertStringContainsString('name="import_token" value="' . $token . '"', $html);
        $this->assertStringContainsString('name="cancel_import"', $html);
        $this->assertStringContainsString('name="_glpi_csrf_token"', $html);
        $this->assertMatchesRegularExpression('/name="import_file" value="1" class="btn btn-primary"\s*>/', $html);
        $this->assertStringNotContainsString('data-testid="import-errors"', $html);

        // Without the token of its summary, nothing is imported and the file is kept
        $this->assertFalse(Export::importPendingFile('not the token'));
        $this->assertFileExists($this->files[0]);
        $this->hasSessionMessages(ERROR, [__('No file is waiting for its import, please select it again', 'metademands')]);

        $new_id = Export::importPendingFile($token);
        $this->assertIsInt($new_id);
        $this->assertGreaterThan(0, $new_id);
        $this->assertCount(2, (new Field())->find(['plugin_metademands_metademands_id' => $new_id]));
        // The uploaded copy is dropped once imported
        $this->assertFileDoesNotExist($this->files[0]);
        $this->assertArrayNotHasKey(self::SESSION_KEY, $_SESSION);
        $this->assertFalse(Export::showImportSummary());
    }

    public function testAFileWithErrorsIsNotImported(): void
    {
        $this->login();
        $name  = 'Unknown object ' . mt_rand();
        $token = $this->setPendingImport($this->toXml(['name' => $name, 'object_to_create' => 'Computer']));

        $html = $this->capture(fn() => Export::showImportSummary());
        $this->assertStringContainsString('data-testid="import-errors"', $html);
        $this->assertMatchesRegularExpression('/name="import_file" value="1" class="btn btn-primary"\s*disabled/', $html);

        // The button is disabled, the confirmation is refused all the same
        $this->assertFalse(Export::importPendingFile($token));
        $this->hasSessionMessages(ERROR, [__('The file cannot be imported, see the errors of its summary', 'metademands')]);
        $this->assertFalse((new Metademand())->getFromDBByCrit(['name' => $name]));
        $this->assertFileDoesNotExist($this->files[0]);
    }

    public function testAnotherFileCanBeChosen(): void
    {
        $this->login();
        $this->setPendingImport($this->toXml(['name' => 'Cancelled', 'object_to_create' => 'Ticket']));

        Export::cancelPendingImport();

        $this->assertFileDoesNotExist($this->files[0]);
        $this->assertArrayNotHasKey(self::SESSION_KEY, $_SESSION);
        $html = $this->capture(fn() => $this->assertFalse(Export::showImportSummary()));
        $this->assertSame('', $html);
    }

    public function testTheImportLeavesOutWhatTheSummaryAnnounces(): void
    {
        $this->login();
        $name = 'Left out ' . mt_rand();
        $datas = $this->toXml([
            'version'          => PLUGIN_METADEMANDS_VERSION,
            'name'             => $name,
            'object_to_create' => 'Ticket',
            'type'             => \Ticket::INCIDENT_TYPE,
            'metafields'       => [
                'field1' => ['id' => 1, 'type' => 'text', 'item' => '', 'name' => 'Text', 'rank' => 1, 'order' => 1],
            ],
            'metafieldoptions' => [
                'fieldoptions1' => ['id' => 1, 'plugin_metademands_fields_id' => 99, 'check_value' => 1, 'hidden_block' => 2],
            ],
            'tasks' => [
                'task5' => [
                    'id'                          => 5,
                    'type'                        => Task::TICKET_TYPE,
                    'name'                        => 'Child',
                    'level'                       => 2,
                    'plugin_metademands_tasks_id' => 42,
                ],
            ],
            'metatasks' => ['metatask1' => ['plugin_metademands_tasks_id' => 43]],
        ]);
        $token = $this->setPendingImport($datas);
        $detached_options = countElementsInTable(FieldOption::getTable(), ['plugin_metademands_fields_id' => 0]);

        $new_id = Export::importPendingFile($token);
        $this->assertIsInt($new_id);

        $fields = (new Field())->find(['plugin_metademands_metademands_id' => $new_id]);
        $this->assertCount(1, $fields);
        // The option of a field missing from the file is not created, detached
        $this->assertSame($detached_options, countElementsInTable(FieldOption::getTable(), ['plugin_metademands_fields_id' => 0]));
        $this->assertSame([], (new FieldOption())->find(['plugin_metademands_fields_id' => array_key_first($fields)]));
        // The parent missing from the file is dropped, not kept as an unrelated id
        $tasks = (new Task())->find(['plugin_metademands_metademands_id' => $new_id]);
        $this->assertCount(1, $tasks);
        $this->assertSame(0, (int) current($tasks)['plugin_metademands_tasks_id']);
        $this->assertSame([], (new \GlpiPlugin\Metademands\MetademandTask())->find(['plugin_metademands_metademands_id' => $new_id]));
    }

    /**
     * Before the tests loading the classes of the Resources plugin, which stay loaded
     */
    public function testWithoutTheResourcesPluginItsLinksAreReported(): void
    {
        if (getItemForItemtype(ContractType::class) !== false) {
            $this->markTestSkipped('The classes of the Resources plugin are loaded');
        }
        $this->login();

        $summary = Export::analyzeImportData([
            'name'             => 'Without resources',
            'object_to_create' => 'Ticket',
            'resource_links'   => [
                'resource_link1' => ['plugin_resources_contracttypes_id' => 3, 'contracttype_name' => 'Interim'],
            ],
        ], $this->getTestRootEntity(true));

        $this->assertContains(
            __('The Resources plugin is not active here: the links to its contract types are not imported', 'metademands'),
            $summary['warnings'],
        );
    }

    public function testTheLinksToResourcesContractTypesAreImportedByName(): void
    {
        $this->login();
        $this->useResourcesPlugin();
        $suffix   = mt_rand();
        $interim  = $this->createContractType('Interim ' . $suffix);
        $internal = $this->createContractType('Internal ' . $suffix);

        // A meta-demand several contract types lead to
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Hiring ' . $suffix,
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => \Ticket::DEMAND_TYPE,
        ]);
        $links = [];
        foreach ([$interim, $internal] as $contracttype) {
            $links[] = $this->createItem(Metademand_Resource::class, [
                'plugin_resources_contracttypes_id' => $contracttype->getID(),
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'entities_id'                       => $this->getTestRootEntity(true),
            ]);
        }
        $target = Export::exportAsXMLForMetademands($metademand->getID());
        $this->assertNotEmpty($target);
        $xml = (string) file_get_contents($target['path']);
        @unlink($target['path']);
        $this->assertStringContainsString('<contracttype_name>Interim ' . $suffix . '</contracttype_name>', $xml);
        $this->assertStringContainsString('<contracttype_name>Internal ' . $suffix . '</contracttype_name>', $xml);

        // The contract types still lead to the source meta-demand, which they keep leading to
        $summary = Export::analyzeImportData($this->read($xml), $this->getTestRootEntity(true));
        $counts  = array_column($summary['counts'], 'count', 'label');
        $this->assertSame(2, $counts[_n('Contract type of the Resources plugin', 'Contract types of the Resources plugin', 2, 'metademands')]);
        $this->assertContains(
            sprintf(
                __('Contract types of the Resources plugin already leading to another meta-demand here, which they keep leading to: %s', 'metademands'),
                '"Interim ' . $suffix . '", "Internal ' . $suffix . '"',
            ),
            $summary['warnings'],
        );
        foreach ($links as $link) {
            $this->assertTrue($link->delete(['id' => $link->getID()], true));
        }

        // On another GLPI, the contract type of the same name has another id
        $this->assertTrue($internal->delete(['id' => $internal->getID()], true));
        $other_internal = $this->createContractType('Internal ' . $suffix);
        $summary = Export::analyzeImportData($this->read($xml), $this->getTestRootEntity(true));
        $this->assertSame([], array_values(array_filter(
            $summary['warnings'],
            static fn(string $warning): bool => str_contains($warning, 'Resources'),
        )));

        $token  = $this->setPendingImport($xml);
        $new_id = Export::importPendingFile($token);
        $this->assertIsInt($new_id);
        $imported = (new Metademand_Resource())->find(['plugin_metademands_metademands_id' => $new_id], ['plugin_resources_contracttypes_id']);
        $this->assertEqualsCanonicalizing(
            [$interim->getID(), $other_internal->getID()],
            array_map('intval', array_column($imported, 'plugin_resources_contracttypes_id')),
        );
        // In the entity the meta-demand is imported into
        foreach ($imported as $link) {
            $this->assertSame((int) $_SESSION['glpiactive_entity'], (int) $link['entities_id']);
        }
    }

    public function testAMissingContractTypeCanBeCreatedAndTheFileCheckedAgain(): void
    {
        $this->login();
        $this->useResourcesPlugin();
        $name = 'Freelance ' . mt_rand();
        $this->setPendingImport($this->toXml([
            'version'          => PLUGIN_METADEMANDS_VERSION,
            'name'             => 'Contract ' . mt_rand(),
            'object_to_create' => 'Ticket',
            'metafields'       => ['field1' => ['id' => 1, 'type' => 'text', 'name' => 'Text', 'rank' => 1]],
            'resource_links'   => [
                'resource_link7' => ['id' => 7, 'plugin_resources_contracttypes_id' => 999999, 'contracttype_name' => $name],
            ],
        ]));

        $html = $this->capture(fn() => Export::showImportSummary());
        $this->assertStringContainsString(
            htmlescape(sprintf(__('Contract types of the Resources plugin not found here, their links are not imported: %s', 'metademands'), '"' . $name . '"')),
            $html,
        );
        $this->assertStringContainsString('ti-refresh', $html);
        $this->assertStringContainsString('?import_form=1', $html);

        // Created meanwhile, the contract type is found when the file is checked again
        $this->createContractType($name);
        $html = $this->capture(fn() => Export::showImportSummary());
        $this->assertStringContainsString('data-testid="import-no-error"', $html);
        $this->assertStringNotContainsString('ti-refresh', $html);
    }

    public function testTheLinkOfAPreviousVersionIsFoundById(): void
    {
        $this->login();
        $this->useResourcesPlugin();
        $contracttype = $this->createContractType('Seasonal ' . mt_rand());
        // The single link of the exports of the previous versions, without the name
        $xml = $this->toXml([
            'version'          => '3.6.19',
            'name'             => 'Previous ' . mt_rand(),
            'object_to_create' => 'Ticket',
            'metafields'       => ['field1' => ['id' => 1, 'type' => 'text', 'name' => 'Text', 'rank' => 1]],
            'resource'         => [
                'id'                                => 4,
                'entities_id'                       => 0,
                'plugin_resources_contracttypes_id' => $contracttype->getID(),
                'plugin_metademands_metademands_id' => 12,
            ],
        ]);

        $summary = Export::analyzeImportData($this->read($xml), $this->getTestRootEntity(true));
        $this->assertSame(
            [sprintf(
                __('Contract types of the Resources plugin found by their id, the file coming from a previous version of the plugin: %s', 'metademands'),
                '#' . $contracttype->getID() . ' (' . $contracttype->fields['name'] . ')',
            )],
            $summary['infos'],
        );

        $new_id = Export::importPendingFile($this->setPendingImport($xml));
        $this->assertIsInt($new_id);
        $imported = (new Metademand_Resource())->find(['plugin_metademands_metademands_id' => $new_id]);
        $this->assertSame([$contracttype->getID()], array_map('intval', array_column($imported, 'plugin_resources_contracttypes_id')));
    }
}
