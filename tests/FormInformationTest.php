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
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Configstep;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Forminformation;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;
use GlpiPlugin\Metademands\Wizard;
use Ticket;

/**
 * The information of the form (type "forminformation", Fields/Forminformation.php): one per
 * metademand, alone in the block 0, shown above the blocks and as the first tab, never part
 * of the request.
 */
class FormInformationTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setValue(null, null);

        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), ['id' => 1, 'create_pdf' => 0, 'show_form_changes' => 0]);
        }

        $this->login('glpi', 'glpi');
        foreach (['plugin_metademands', 'plugin_metademands_createmeta', 'plugin_metademands_fillform'] as $right) {
            $_SESSION['glpiactiveprofile'][$right] = ALLSTANDARDRIGHT;
        }
    }

    public function tearDown(): void
    {
        unset($_SESSION['glpi_plugin_metademands_fields'], $_SESSION['plugin_metademands']);

        parent::tearDown();
    }

    private function createMetademand(array $input = []): Metademand
    {
        return $this->createItem(Metademand::class, $input + [
            'name'               => 'Form information ' . mt_rand(),
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
        ]);
    }

    private function informationInput(Metademand $metademand, array $input = []): array
    {
        return $input + [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => 0,
            'type'                              => 'forminformation',
            'item'                              => 'forminformation',
            'name'                              => 'INFOTITLE',
            'comment'                           => '<p>INFOCONTENT</p>',
            'rank'                              => 3,
        ];
    }

    private function addText(Metademand $metademand, string $name, int $rank, int $order = 1): int
    {
        $id = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => 0,
            'type'                              => 'text',
            'item'                              => '',
            'name'                              => $name,
            'rank'                              => $rank,
            'order'                             => $order,
        ], ['item'])->getID();
        // As the field form adds them (front/field.form.php)
        $this->createItem(FieldParameter::class, ['plugin_metademands_fields_id' => $id, 'is_mandatory' => 0]);

        return $id;
    }

    public function testItGoesAloneInItsBlockOncePerMetademand(): void
    {
        $metademand = $this->createMetademand();
        $this->addText($metademand, 'TEXTFIELD', 1);

        $field = new Field();
        $id = $field->add($this->informationInput($metademand));
        $this->assertGreaterThan(0, $id);
        $this->assertSame([Forminformation::BLOCK, 1], [(int) $field->fields['rank'], (int) $field->fields['order']]);

        // A second one is refused, its input given back to the form
        $this->assertFalse((new Field())->add($this->informationInput($metademand, ['name' => 'Second'])));
        $this->hasSessionMessages(ERROR, ['This form already has its form information: INFOTITLE']);
        $this->assertSame('Second', $_SESSION['glpi_plugin_metademands_fields'][0]['name'] ?? null);

        // ... but not in another metademand
        $this->assertGreaterThan(0, (new Field())->add($this->informationInput($this->createMetademand())));

        // It stays in its block
        $this->assertTrue($field->update(['id' => $id, 'rank' => 2, 'name' => 'Renamed']));
        $this->assertTrue($field->getFromDB($id));
        $this->assertSame([Forminformation::BLOCK, 'Renamed'], [(int) $field->fields['rank'], $field->fields['name']]);
        $this->assertFalse($field->moveTo(1, 0));
        $this->assertTrue($field->getFromDB($id));
        $this->assertSame(Forminformation::BLOCK, (int) $field->fields['rank']);
    }

    public function testTheFieldsLeftInBlockZeroAreMovedFirst(): void
    {
        $metademand = $this->createMetademand();
        $text = $this->addText($metademand, 'LEGACY', 0);

        $this->assertFalse((new Field())->add($this->informationInput($metademand)));
        $this->hasSessionMessages(ERROR, ['Block 0 has 1 field: move it to another block before adding the form information']);

        $legacy = new Field();
        $legacy->getFromDB($text);
        $this->assertTrue($legacy->moveTo(1, 0));
        $this->assertGreaterThan(0, (new Field())->add($this->informationInput($metademand)));
        // No field moves into its block
        $this->assertFalse($legacy->moveTo(0, 0));
    }

    public function testTheFieldFormsOfferItOnce(): void
    {
        $metademand = $this->createMetademand();

        ob_start();
        (new Field())->showForm(-1, ['parent' => $metademand]);
        $html = (string) ob_get_clean();
        $this->assertMatchesRegularExpression('/<button type="button" class="md-type-card"\s+data-md-type-card="forminformation"/', $html);
        $this->assertStringNotContainsString('This form already has its form information', $html);

        $id = (new Field())->add($this->informationInput($metademand));
        ob_start();
        (new Field())->showForm(-1, ['parent' => $metademand]);
        $html = (string) ob_get_clean();
        $this->assertMatchesRegularExpression('/<button type="button" class="md-type-card" disabled\s+data-md-type-card="forminformation"/', $html);
        $this->assertStringContainsString('This form already has its form information', $html);

        // Its own form: neither block nor order, its type fixed
        $field = new Field();
        $field->getFromDB($id);
        ob_start();
        $field->showForm($id);
        $html = (string) ob_get_clean();
        $this->assertStringContainsString('data-md-field-block hidden', $html);
        $this->assertStringNotContainsString('data-md-field-order', $html);
        $this->assertStringContainsString('<input type="hidden" name="type" value="forminformation">', $html);

        // An information cannot become the information of the form
        $info = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => 0,
            'type'                              => 'informations',
            'item'                              => 'informations',
            'name'                              => 'Information',
            'rank'                              => 1,
            'order'                             => 1,
        ]);
        ob_start();
        $info->showForm($info->getID());
        $html = (string) ob_get_clean();
        $this->assertStringContainsString('value="informations"', $html);
        $this->assertStringNotContainsString('value="forminformation"', $html);

        // Its parameters: those of an information, never mandatory
        $this->assertContains('forminformation', FieldParameter::NOT_MANDATORY_TYPES);
    }

    public function testTheListShowsItsBlockFirstAndFixed(): void
    {
        $metademand = $this->createMetademand();
        $this->addText($metademand, 'TEXTFIELD', 1);
        (new Field())->add($this->informationInput($metademand));

        ob_start();
        Field::displayTabContentForItem($metademand);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('<h3 class="card-title m-0">Form information</h3>', $html);
        $this->assertLessThan(strpos($html, 'data-md-rank="1"'), strpos($html, 'data-md-rank="0" data-md-fields-fixed'));
        // Its block takes no field, the others do
        $this->assertSame(1, substr_count($html, 'data-md-field-add-rank='));
        $this->assertStringContainsString('data-md-field-add-rank="1"', $html);
    }

    public function testTheWizardShowsItFirstAndAsTheFirstTab(): void
    {
        $metademand = $this->createMetademand(['step_by_step_mode' => 1]);
        $this->addText($metademand, 'TEXTFIELD', 1);
        $information = (new Field())->add($this->informationInput($metademand, ['name' => 'Before you start']));
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $information,
            'display_type'                 => 1,
            'hide_title'                   => 0,
        ]);
        // The configuration of the steps, created with the metademand when there is none yet
        $config = new Configstep();
        $steps  = [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'step_by_step_interface'            => Configstep::BOTH_INTERFACE,
            'see_blocks_as_tab'                 => 1,
        ];
        if ($config->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademand->getID()])) {
            $this->updateItem(Configstep::class, $config->getID(), $steps);
        } else {
            $this->createItem(Configstep::class, $steps);
        }

        $data = Metademand::constructMetademands($metademand->getID());
        ob_start();
        Wizard::constructForm(
            $metademand->getID(),
            $data,
            Metademand::STEP_SHOW,
            $data[Metademand::STEP_SHOW][$metademand->getID()]['form'],
        );
        $html = (string) ob_get_clean();

        // The first tab, named by it, then the block 1
        $this->assertMatchesRegularExpression('/id="ablock0"\s+href="#block0"\s+data-toggle="tab">Before you start<\/a>/', $html);
        $this->assertLessThan(strpos($html, 'id="ablock1"'), strpos($html, 'id="ablock0"'));
        // Its block comes first, with nothing to fill: no "no value" confirmation
        $this->assertMatchesRegularExpression('/<div bloc-id="bloc0" class="card tab-sc-child-0" style="[^"]*" data-md-form-information>/', $html);
        $this->assertLessThan(strpos($html, 'bloc-id="bloc1"'), strpos($html, 'bloc-id="bloc0"'));
        $this->assertStringContainsString('INFOCONTENT', $html);

        // Not a step of its own in the visibilities of the blocks
        ob_start();
        (new Step())->showForm(-1, ['parent' => $metademand]);
        $html = (string) ob_get_clean();
        $this->assertStringContainsString('Block 1', $html);
        $this->assertStringNotContainsString('Block 0', $html);
    }

    public function testItIsNotPartOfTheRequest(): void
    {
        $metademand = $this->createMetademand();
        $text = $this->addText($metademand, 'TEXTFIELD', 1);
        $information = (new Field())->add($this->informationInput($metademand));

        $result = Metademand::addObjects($metademand->getID(), ['fields' => [
            // As the wizard posts an information (ajax/createmetademands.php)
            $information => 0,
            $text        => 'value typed',
        ]]);
        $this->assertIsArray($result);

        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB((int) $result['id']));
        $content = (string) $ticket->fields['content'];
        $this->assertStringContainsString('value typed', $content);
        $this->assertStringNotContainsString('INFOTITLE', $content);
        $this->assertStringNotContainsString('INFOCONTENT', $content);
    }
}
