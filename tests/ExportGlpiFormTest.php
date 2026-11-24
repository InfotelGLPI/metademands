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

use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Destination\CommonITILField\AssigneeField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryField;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Field;
use GlpiPlugin\Metademands\TicketTask;
use Group;
use ITILCategory;

/**
 * A GLPI form exported for the metademands (Export::exportAsXMLFromGLPI()): the values of its
 * dropdown, radio and checkbox questions, its destinations as child tickets created on their
 * conditions when the options express them; and the metademand exported as a GLPI form.
 */
class ExportGlpiFormTest extends DbTestCase
{
    use FormTesterTrait;

    private const SESSION_KEY = 'plugin_metademands_pending_import';

    /** @var string[] */
    private array $files = [];

    public function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        unset($_SESSION[self::SESSION_KEY]);

        parent::tearDown();
    }

    private function exportForm(Form $form): string
    {
        $target = Export::exportAsXMLFromGLPI($form->getID());
        $this->assertNotEmpty($target);
        $xml = (string) file_get_contents($target['path']);
        @unlink($target['path']);

        return $xml;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $xml): array
    {
        $file = GLPI_TMP_DIR . '/' . uniqid('md_glpi_form_', true) . '.xml';
        file_put_contents($file, $xml);
        $this->files[] = $file;
        $datas = Export::readImportFile($file);
        $this->assertIsArray($datas);

        return $datas;
    }

    private function import(string $xml): int
    {
        $directory = GLPI_PLUGIN_DOC_DIR . '/metademands';
        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }
        $file = uniqid('import_', true) . '.xml';
        file_put_contents($directory . '/' . $file, $xml);
        $this->files[] = $directory . '/' . $file;
        $token = bin2hex(random_bytes(16));
        $_SESSION[self::SESSION_KEY] = ['token' => $token, 'file' => $file, 'filename' => 'form.xml'];

        $new_id = Export::importPendingFile($token);
        $this->assertIsInt($new_id);

        return $new_id;
    }

    /**
     * @param array<int, string> $options
     */
    private function options(array $options, bool $multiple = false): string
    {
        return (string) json_encode(['options' => $options, 'is_multiple_dropdown' => $multiple]);
    }

    /**
     * @return array<string, Field>
     */
    private function getFields(int $metademands_id): array
    {
        $fields = [];
        foreach ((new Field())->find(['plugin_metademands_metademands_id' => $metademands_id]) as $id => $row) {
            $field = new Field();
            $field->getFromDB($id);
            $fields[$row['name']] = $field;
        }

        return $fields;
    }

    public function testTheValuesOfTheListsAreExported(): void
    {
        $this->login();
        $form = $this->createForm(
            (new FormBuilder('Lists ' . mt_rand()))
                ->setShouldInitDestinations(false)
                ->addQuestion('Office', QuestionTypeDropdown::class, '2', $this->options([1 => 'Blagnac', 2 => 'Elancourt', 3 => 'Vitrolles']))
                ->addQuestion('Sites', QuestionTypeDropdown::class, '', $this->options([1 => 'Antipolis', 2 => 'Grimaud'], true))
                ->addQuestion('Returning', QuestionTypeRadio::class, '', $this->options([1 => 'No', 2 => 'Yes']))
                ->addQuestion('Equipment', QuestionTypeCheckbox::class, '', $this->options([1 => 'PC', 2 => 'Phone', 3 => 'Badge'])),
        );

        $datas = $this->read($this->exportForm($form));
        $by_name = [];
        foreach ($datas['metafields'] as $field) {
            $by_name[$field['name']] = $field;
        }
        $this->assertSame(['dropdown_meta', 'other'], [$by_name['Office']['type'], $by_name['Office']['item']]);
        $this->assertSame(['dropdown_multiple', 'other'], [$by_name['Sites']['type'], $by_name['Sites']['item']]);
        $this->assertSame(['radio', 'radio'], [$by_name['Returning']['type'], $by_name['Returning']['item']]);
        $this->assertSame(['checkbox', 'checkbox'], [$by_name['Equipment']['type'], $by_name['Equipment']['item']]);

        $new_id = $this->import($this->exportForm($form));
        $fields = $this->getFields($new_id);
        $values = static function (Field $field): array {
            $rows = (new FieldCustomvalue())->find(['plugin_metademands_fields_id' => $field->getID()], 'rank');

            return array_values(array_map(static fn(array $row): string => $row['name'] . ((int) $row['is_default'] ? ' *' : ''), $rows));
        };
        // The default value of the question is the default one of the field
        $this->assertSame(['Blagnac', 'Elancourt *', 'Vitrolles'], $values($fields['Office']));
        $this->assertSame(['Antipolis', 'Grimaud'], $values($fields['Sites']));
        $this->assertSame(['No', 'Yes'], $values($fields['Returning']));
        $this->assertSame(['PC', 'Phone', 'Badge'], $values($fields['Equipment']));
    }

    public function testTheDestinationsBecomeChildTicketsOnTheirConditions(): void
    {
        $this->login();
        $group = $this->createItem(Group::class, ['name' => 'Support ' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        $category = $this->createItem(ITILCategory::class, ['name' => 'Hardware ' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        $condition = static fn(string $question, int $value, ValueOperator $operator, LogicOperator $logic = LogicOperator::AND): array => [
            'item_name'      => $question,
            'item_type'      => Type::QUESTION,
            'value'          => $value,
            'value_operator' => $operator,
            'logic_operator' => $logic,
        ];

        $builder = (new FormBuilder('Newcomer ' . mt_rand()))
            ->setShouldInitDestinations(false)
            ->addQuestion('First name', QuestionTypeShortText::class)
            ->addQuestion('Office', QuestionTypeDropdown::class, '', $this->options([1 => 'ASO', 2 => 'IDF']))
            ->addQuestion('Activity', QuestionTypeDropdown::class, '', $this->options([1 => 'Aircraft', 2 => 'Helicopter']))
            ->addDestination(FormDestinationTicket::class, 'Always', [
                ITILCategoryField::getKey() => ['strategy' => 'specific_value', 'specific_itilcategory_id' => $category->getID()],
                // As posted by the form of the destination: "<foreign key>-<id>"
                AssigneeField::getKey()     => ['strategies' => ['specific_values'], 'specific_itilactors_ids' => ['groups_id-' . $group->getID()]],
            ])
            ->addDestination(FormDestinationTicket::class, 'ASO')
            ->addDestination(FormDestinationTicket::class, 'Not helicopter')
            ->addDestination(FormDestinationTicket::class, 'Unless IDF and helicopter')
            ->addDestination(FormDestinationTicket::class, 'ASO and helicopter');
        $builder->setDestinationCondition('ASO', CreationStrategy::CREATED_IF, [
            $condition('Office', 1, ValueOperator::EQUALS),
        ]);
        $builder->setDestinationCondition('Not helicopter', CreationStrategy::CREATED_IF, [
            $condition('Activity', 2, ValueOperator::NOT_EQUALS),
        ]);
        // Not (IDF and helicopter): not IDF or not helicopter
        $builder->setDestinationCondition('Unless IDF and helicopter', CreationStrategy::CREATED_UNLESS, [
            $condition('Office', 2, ValueOperator::EQUALS),
            $condition('Activity', 2, ValueOperator::EQUALS, LogicOperator::AND),
        ]);
        // An AND the options, met one at a time, cannot express
        $builder->setDestinationCondition('ASO and helicopter', CreationStrategy::CREATED_IF, [
            $condition('Office', 1, ValueOperator::EQUALS),
            $condition('Activity', 2, ValueOperator::EQUALS, LogicOperator::AND),
        ]);
        $form = $this->createForm($builder);

        // The title of the first destination shows the answer to "First name"
        global $DB;
        $destinations_id = $this->getDestinationId($form, 'Always');
        $destination = FormDestination::getById($destinations_id);
        $config = json_decode((string) $destination->fields['config'], true);
        $config[TitleField::getKey()] = ['value' => 'Welcome <span data-form-tag="true" data-form-tag-value="'
            . $this->getQuestionId($form, 'First name')
            . '" data-form-tag-provider="Glpi\Form\Tag\AnswerTagProvider" class="border-teal">#Answer: First name</span>'];
        $DB->update(FormDestination::getTable(), ['config' => json_encode($config)], ['id' => $destinations_id]);

        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        $xml = $this->exportForm($form);
        $this->hasSessionMessages(WARNING, [
            sprintf(_n(
                '%d destination exported without its conditions, its child ticket always created: see the summary of the import',
                '%d destinations exported without their conditions, their child tickets always created: see the summary of the import',
                1,
                'metademands',
            ), 1),
        ]);
        $summary = Export::analyzeImportData($this->read($xml), $this->getTestRootEntity(true));
        $this->assertCount(1, array_filter(
            $summary['warnings'],
            static fn(string $warning): bool => str_contains($warning, '"ASO and helicopter"'),
        ));

        $new_id = $this->import($xml);
        $fields = $this->getFields($new_id);
        $tasks = [];
        foreach ((new Task())->find(['plugin_metademands_metademands_id' => $new_id]) as $id => $row) {
            $tasks[$row['name']] = $id;
        }
        $this->assertSame(
            ['ASO', 'ASO and helicopter', 'Not helicopter', 'Unless IDF and helicopter', 'Welcome #' . $fields['First name']->getID() . '#'],
            (static function (array $names): array {
                sort($names);
                return $names;
            })(array_keys($tasks)),
        );
        $always = $tasks['Welcome #' . $fields['First name']->getID() . '#'];
        $ticket_task = new TicketTask();
        $this->assertTrue($ticket_task->getFromDBByCrit(['plugin_metademands_tasks_id' => $always]));
        $this->assertSame($category->getID(), (int) $ticket_task->fields['itilcategories_id']);
        $this->assertSame($group->getID(), (int) $ticket_task->fields['groups_id_assign']);
        $this->assertSame([], (new FieldOption())->find(['plugin_metademands_tasks_id' => [$always, $tasks['ASO and helicopter']]]));

        // The child tickets created for the answers, as the wizard decides it
        $values_id = static function (Field $field, string $name): int {
            $value = new FieldCustomvalue();
            $value->getFromDBByCrit(['plugin_metademands_fields_id' => $field->getID(), 'name' => $name]);

            return $value->getID();
        };
        $created = function (string $office, string $activity) use ($fields, $tasks, $values_id): array {
            $ticket = $this->createItem(\Ticket::class, ['name' => 'Parent', 'content' => 'Parent', 'entities_id' => $this->getTestRootEntity(true)]);
            foreach (['Office' => $office, 'Activity' => $activity] as $name => $answer) {
                $this->createItem(Ticket_Field::class, [
                    'tickets_id'                   => $ticket->getID(),
                    'plugin_metademands_fields_id' => $fields[$name]->getID(),
                    'value'                        => (string) $values_id($fields[$name], $answer),
                    'value2'                       => '',
                ]);
            }
            $result = [];
            foreach ($tasks as $name => $tasks_id) {
                if (Ticket_Field::checkTicketCreation($tasks_id, $ticket->getID())) {
                    $result[] = $name;
                }
            }
            sort($result);

            return $result;
        };
        $welcome = 'Welcome #' . $fields['First name']->getID() . '#';
        $this->assertSame(
            ['ASO', 'ASO and helicopter', 'Not helicopter', 'Unless IDF and helicopter', $welcome],
            $created('ASO', 'Aircraft'),
        );
        // "ASO and helicopter" is always created: reported by the summary, to be set by hand
        $this->assertSame(['ASO and helicopter', $welcome], $created('IDF', 'Helicopter'));
        $this->assertSame(['ASO and helicopter', 'Not helicopter', 'Unless IDF and helicopter', $welcome], $created('IDF', 'Aircraft'));
    }

    public function testTheSectionsOfTheExportAsAGlpiFormBeginAtZero(): void
    {
        $this->login();
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Sections ' . mt_rand(),
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => \Ticket::DEMAND_TYPE,
        ]);
        foreach ([1, 2] as $block) {
            $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'entities_id'                       => $this->getTestRootEntity(true),
                'type'                              => 'text',
                'item'                              => '',
                'name'                              => 'Text ' . $block,
                'rank'                              => $block,
                'order'                             => 1,
            ], ['order', 'item']);
        }

        $target = Export::exportAsJSONForGLPIForm($metademand->getID());
        $this->assertNotEmpty($target);
        $json = json_decode((string) file_get_contents($target['path']), true);
        @unlink($target['path']);

        $this->assertSame([0, 1], array_column($json['forms'][0]['sections'], 'rank'));
    }

    public function testTheHeaderOfTheFormBecomesTheFormInformation(): void
    {
        $this->login();
        $form = $this->createForm(
            (new FormBuilder('Header ' . mt_rand()))
                ->setShouldInitDestinations(false)
                ->setHeader('<p>Welcome, <strong>read this first</strong></p>')
                ->addQuestion('First name', QuestionTypeShortText::class),
        );

        $datas = $this->read($this->exportForm($form));
        $information = array_values(array_filter(
            $datas['metafields'],
            static fn(array $field): bool => $field['type'] === 'forminformation',
        ));
        $this->assertCount(1, $information);
        // First in the file, in its block: the shift of the blocks from 0 leaves it there
        $this->assertSame('forminformation', reset($datas['metafields'])['type']);
        $this->assertSame(0, (int) $information[0]['rank']);
        $this->assertArrayNotHasKey('header', $datas);

        $new_id = $this->import($this->exportForm($form));
        $fields = (new Field())->find(['plugin_metademands_metademands_id' => $new_id], ['rank', 'order']);
        $this->assertSame(
            [['forminformation', 0], ['title-block', 1], ['text', 1]],
            array_values(array_map(static fn(array $row): array => [$row['type'], (int) $row['rank']], $fields)),
        );
        $row = reset($fields);
        $this->assertSame('<p>Welcome, <strong>read this first</strong></p>', $row['comment']);
        // The header has no title: the name only names the tab of the block
        $field = new Field();
        $field->getFromDB((int) $row['id']);
        $this->assertSame(1, (int) Field::getAllParamsFromField($field)['hide_title']);
    }

    public function testTheCommentsBecomeInformationsShownOnTheirConditions(): void
    {
        $this->login();
        $condition = static fn(string $question, int $value, LogicOperator $logic = LogicOperator::AND): array => [
            'item_name'      => $question,
            'item_type'      => Type::QUESTION,
            'value'          => $value,
            'value_operator' => ValueOperator::EQUALS,
            'logic_operator' => $logic,
        ];
        $builder = (new FormBuilder('Comments ' . mt_rand()))
            ->setShouldInitDestinations(false)
            ->addSection('Identity')
            ->addQuestion('Office', QuestionTypeDropdown::class, '', $this->options([1 => 'ASO', 2 => 'IDF']))
            ->addQuestion('Activity', QuestionTypeDropdown::class, '', $this->options([1 => 'Aircraft', 2 => 'Helicopter']))
            ->addComment('Welcome', '<p>Read this first</p>')
            ->addComment('ASO process', '<p>Local process of ASO</p>')
            ->addComment('ASO error', '<p>No helicopters in ASO</p>');
        $builder->setCommentVisibility('ASO process', VisibilityStrategy::VISIBLE_IF, [$condition('Office', 1)]);
        // An AND the options, met one at a time, cannot express
        $builder->setCommentVisibility('ASO error', VisibilityStrategy::VISIBLE_IF, [
            $condition('Office', 1),
            $condition('Activity', 2),
        ]);
        $form = $this->createForm($builder);

        $xml = $this->exportForm($form);
        $this->hasSessionMessages(WARNING, [
            '1 comment exported without its visibility conditions, its information always shown: see the summary of the import',
        ]);
        $datas = $this->read($xml);
        $by_name = [];
        foreach ($datas['metafields'] as $field) {
            $by_name[$field['name']] = $field;
        }
        foreach (['Welcome' => 'Read this first', 'ASO process' => 'Local process of ASO', 'ASO error' => 'No helicopters in ASO'] as $name => $text) {
            $this->assertSame(['informations', 'informations', 1], [$by_name[$name]['type'], $by_name[$name]['item'], (int) $by_name[$name]['rank']]);
            $this->assertStringContainsString($text, $by_name[$name]['comment']);
        }
        // Shown on the value of the question: an option showing the field
        $options = array_values($datas['metafieldoptions']);
        $this->assertCount(1, $options);
        $this->assertSame((int) $by_name['ASO process']['id'], (int) $options[0]['hidden_link']);
        $this->assertSame((int) $by_name['Office']['id'], (int) $options[0]['plugin_metademands_fields_id']);
        $this->assertArrayNotHasKey('untranslated_visibility', $by_name['ASO process']);
        $this->assertSame('Shown if Office = ASO and Activity = Helicopter', $by_name['ASO error']['untranslated_visibility']);
        $this->assertContains(
            'Information "ASO error" always shown, its GLPI conditions not being expressible by the options: Shown if Office = ASO and Activity = Helicopter',
            Export::analyzeImportData($datas, 0)['warnings'],
        );

        $new_id = $this->import($xml);
        $fields = $this->getFields($new_id);
        $aso = (new FieldCustomvalue())->find(['plugin_metademands_fields_id' => $fields['Office']->getID(), 'name' => 'ASO']);
        $option = new FieldOption();
        $this->assertTrue($option->getFromDBByCrit(['plugin_metademands_fields_id' => $fields['Office']->getID()]));
        $this->assertSame($fields['ASO process']->getID(), (int) $option->fields['hidden_link']);
        $this->assertSame((int) array_key_first($aso), (int) $option->fields['check_value']);
        $this->assertSame('<p>Read this first</p>', $fields['Welcome']->fields['comment']);
    }

    public function testAFormWithoutHeaderHasNoFormInformation(): void
    {
        $this->login();
        $form = $this->createForm(
            (new FormBuilder('No header ' . mt_rand()))
                ->setShouldInitDestinations(false)
                ->addQuestion('First name', QuestionTypeShortText::class),
        );

        $datas = $this->read($this->exportForm($form));
        $this->assertNotContains('forminformation', array_column($datas['metafields'], 'type'));
    }

    public function testTheFormInformationIsTheHeaderOfTheExportAsAGlpiForm(): void
    {
        $this->login();
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Header ' . mt_rand(),
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => \Ticket::DEMAND_TYPE,
        ]);
        $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => $this->getTestRootEntity(true),
            'type'                              => 'forminformation',
            'item'                              => 'forminformation',
            'name'                              => 'Before you start',
            'comment'                           => '<p>Read this first</p>',
            'label2'                            => '<p>Then fill the form</p>',
            'rank'                              => 2,
        ], ['rank', 'order']);
        $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => $this->getTestRootEntity(true),
            'type'                              => 'text',
            'item'                              => '',
            'name'                              => 'Text',
            'rank'                              => 1,
            'order'                             => 1,
        ], ['order', 'item']);

        $target = Export::exportAsJSONForGLPIForm($metademand->getID());
        $this->assertNotEmpty($target);
        $json = json_decode((string) file_get_contents($target['path']), true);
        @unlink($target['path']);

        $this->assertSame('<p>Read this first</p><p>Then fill the form</p>', $json['forms'][0]['header']);
        // Neither a section nor a question
        $this->assertSame([0], array_column($json['forms'][0]['sections'], 'rank'));
        $this->assertSame(['Text'], array_column($json['forms'][0]['questions'], 'name'));
    }
}
