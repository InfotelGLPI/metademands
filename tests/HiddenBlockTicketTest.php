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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;
use Ticket;

/**
 * A block shown by a radio value (option "hidden_block") keeps its fields in the ticket (#373)
 */
class HiddenBlockTicketTest extends DbTestCase
{
    /** @var array{metademand: int, radio: int, show: int, other: int, text: int} */
    private array $form;

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
        $this->form = $this->createForm();
    }

    private function createForm(): array
    {
        $metademands_id = $this->createItem(Metademand::class, [
            'name'               => 'Bloc affiché par un radio',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
        ])->getID();

        $field = fn(string $name, string $type, int $rank, int $order) => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => $type,
            'item'        => $type === 'radio' ? 'radio' : '',
            'name'        => $name,
            'rank'        => $rank,
            'order'       => $order,
            'entities_id' => 0,
        ], ['item'])->getID();

        $radio = $field('CHOICEFIELD', 'radio', 1, 1);
        $value = fn(string $name, int $rank) => $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $radio,
            'name'                         => $name,
            'rank'                         => $rank,
            'is_default'                   => 0,
        ])->getID();
        $show  = $value('SHOWVALUE', 0);
        $other = $value('OTHERVALUE', 1);

        $field('BLOCKTITLE', 'title-block', 2, 1);
        $text = $field('BLOCKFIELD', 'text', 2, 2);

        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $radio,
            'check_value'                  => $show,
            'hidden_block'                 => 2,
        ]);

        return [
            'metademand' => $metademands_id,
            'radio'      => $radio,
            'show'       => $show,
            'other'      => $other,
            'text'       => $text,
        ];
    }

    private function submit(int $choice): string
    {
        $result = Metademand::addObjects($this->form['metademand'], ['fields' => [
            // As posted by the wizard: strings
            $this->form['radio'] => (string) $choice,
            $this->form['text']  => 'value typed in the block',
        ]]);
        $this->assertIsArray($result);

        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB((int) $result['id']));

        return (string) $ticket->fields['content'];
    }

    /**
     * Include an Ajax endpoint of the wizard with the given POST, as the browser calls it
     */
    private function post(string $endpoint, array $post): string
    {
        $_POST = $post;
        ob_start();
        try {
            include dirname(__DIR__) . '/ajax/' . $endpoint;
        } finally {
            $output = trim((string) ob_get_clean());
        }

        return $output;
    }

    /**
     * As the browser does (plugin_metademands_wizard_nextBtn(), public/scripts/metademands.js):
     * the form goes to ajax/addform.php, which keeps the values in session, then to
     * ajax/createmetademands.php, which checks them, then step=create_metademands creates
     * the objects from the session.
     */
    private function submitThroughWizard(int $choice): string
    {
        foreach (['plugin_metademands', 'plugin_metademands_createmeta', 'plugin_metademands_fillform'] as $right) {
            $_SESSION['glpiactiveprofile'][$right] = ALLSTANDARDRIGHT;
        }
        $metademands_id = $this->form['metademand'];
        unset($_SESSION['plugin_metademands']);
        $post = [
            'metademands_id'      => $metademands_id,
            'form_metademands_id' => $metademands_id,
            'step'                => 2,
            'create_metademands'  => 1,
            'save_form'           => 1,
            'form_name'           => 'Formulaire #373',
            '_users_id_requester' => \Session::getLoginUserID(),
            'tickets_id'          => 0,
            'resources_id'        => 0,
            'resources_step'      => '',
            'field'               => [
                $this->form['radio'] => (string) $choice,
                $this->form['text']  => 'value typed in the block',
            ],
        ];
        $this->post('addform.php', $post);
        $this->assertSame('0', $this->post('createmetademands.php', $post), 'the wizard refused the submission');
        $values = $_SESSION['plugin_metademands'][$metademands_id];

        $before = countElementsInTable(Ticket::getTable());
        try {
            \GlpiPlugin\Metademands\Wizard::createMetademands($metademands_id, $values);
        } catch (\Throwable $redirect) {
            // Html::redirect() / Html::back() once the objects are created
        }
        $this->assertSame($before + 1, countElementsInTable(Ticket::getTable()));

        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB((int) max(array_column(getAllDataFromTable(Ticket::getTable()), 'id'))));

        return (string) $ticket->fields['content'];
    }

    public function testShownBlockKeepsItsFieldsThroughTheWizard(): void
    {
        $content = $this->submitThroughWizard($this->form['show']);
        $this->assertStringContainsString('BLOCKFIELD', $content);
        $this->assertStringContainsString('value typed in the block', $content);
    }

    public function testShownBlockKeepsItsFields(): void
    {
        $content = $this->submit($this->form['show']);
        $this->assertStringContainsString('SHOWVALUE', $content);
        $this->assertStringContainsString('BLOCKFIELD', $content);
        $this->assertStringContainsString('value typed in the block', $content);
    }

    public function testHiddenBlockLeavesItsFieldsOut(): void
    {
        $content = $this->submit($this->form['other']);
        $this->assertStringContainsString('OTHERVALUE', $content);
        $this->assertStringNotContainsString('value typed in the block', $content);
    }

    /**
     * As the form of the issue: empty fields hidden, the block opened by a specific dropdown
     * (dropdown_meta "other") in block display, a block title placed after the field of its
     * block, a sub-title closing the block opened. A title, a block title or an information
     * reset the display of their block: the blocks were left out of the ticket.
     */
    public function testBlockOpenedByABlockDisplayDropdown(): void
    {
        $metademands_id = $this->createItem(Metademand::class, [
            'name'               => 'Bloc affiché par une liste',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
            'hide_no_field'      => 1,
        ])->getID();

        $field = fn(string $name, string $type, string $item, int $rank, int $order) => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => $type,
            'item'        => $item,
            'name'        => $name,
            'rank'        => $rank,
            'order'       => $order,
            'entities_id' => 0,
        ], ['item'])->getID();

        // The block title after the field of its block
        $choice = $field('CHOICEFIELD', 'dropdown_meta', 'other', 1, 1);
        $field('INTROBLOCK', 'title-block', '', 1, 2);
        $field('INTROINFO', 'informations', 'informations', 1, 3);
        $this->createItem(\GlpiPlugin\Metademands\FieldParameter::class, [
            'plugin_metademands_fields_id' => $choice,
            'is_mandatory'                 => 1,
            'display_type'                 => 2,
        ]);
        $value = fn(string $name, int $rank) => $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $choice,
            'name'                         => $name,
            'rank'                         => $rank,
            'is_default'                   => 0,
        ])->getID();
        $accident = $value('ACCIDENTVALUE', 0);
        $value('INCIDENTVALUE', 1);

        $field('ACCIDENTBLOCK', 'title-block', '', 2, 1);
        $text = $field('ACCIDENTFIELD', 'textarea', '', 2, 2);
        $this->createItem(\GlpiPlugin\Metademands\FieldParameter::class, [
            'plugin_metademands_fields_id' => $text,
            'use_richtext'                 => 0,
        ]);
        // A sub-title closing the block, its field left empty
        $field('ENDTITLE', 'title', '', 2, 3);
        $field('ENDFIELD', 'text', '', 2, 4);

        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $choice,
            'check_value'                  => $accident,
            'hidden_block'                 => 2,
        ]);

        $result = Metademand::addObjects($metademands_id, ['fields' => [
            $choice => (string) $accident,
            $text   => 'what happened',
        ]]);
        $this->assertIsArray($result);
        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB((int) $result['id']));
        $content = (string) $ticket->fields['content'];

        $this->assertStringContainsString('ACCIDENTVALUE', $content);
        $this->assertStringContainsString('what happened', $content);
    }
}
