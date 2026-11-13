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
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Field;
use GlpiPlugin\Metademands\TicketTask;
use GlpiPlugin\Metademands\Wizard;
use Ticket;
use Ticket_Ticket;

/**
 * An inactive field (Field.is_active) is left out of the wizard, of the ticket and of the
 * PDF, and its options have no effect.
 */
class FieldActiveTest extends DbTestCase
{
    /** @var array{metademand: int, shown: int, hidden: int, trigger: int} */
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

    /**
     * SHOWNFIELD active; HIDDENFIELD inactive and mandatory; TRIGGERFIELD inactive yes / no
     * whose "yes" creates TRIGGERTASK and makes HIDDENFIELD... and SHOWNFIELD mandatory.
     * ALWAYSTASK is always created.
     */
    private function createForm(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'                 => 'Champ inactif',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ]);
        $metademands_id = $metademand->getID();

        $field = fn(string $name, string $type, int $order, int $is_active) => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => $type,
            'name'        => $name,
            'rank'        => 1,
            'order'       => $order,
            'entities_id' => 0,
            'is_active'   => $is_active,
        ])->getID();

        $shown   = $field('SHOWNFIELD', 'text', 1, 1);
        $hidden  = $field('HIDDENFIELD', 'text', 2, 0);
        $trigger = $field('TRIGGERFIELD', 'yesno', 3, 0);

        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $hidden,
            'is_mandatory'                 => 1,
        ]);

        $task = fn(string $name) => $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'name'        => $name,
            'type'        => Task::TICKET_TYPE,
            'level'       => 1,
            'entities_id' => 0,
        ])->getID();
        $trigger_task = $task('TRIGGERTASK');
        $always_task  = $task('ALWAYSTASK');
        foreach ([$trigger_task, $always_task] as $tasks_id) {
            $this->createItem(TicketTask::class, [
                'plugin_metademands_tasks_id' => $tasks_id,
                'content'     => 'Contenu',
                'entities_id' => 0,
            ]);
        }

        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $trigger,
            'check_value'                  => 2,
            'plugin_metademands_tasks_id'  => $trigger_task,
            'fields_link'                  => $shown,
        ], ['check_value']);
        // An active field making the inactive one mandatory
        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $shown,
            'check_value'                  => 0,
            'fields_link'                  => $hidden,
            'hidden_link'                  => $hidden,
        ], ['check_value']);

        return [
            'metademand' => $metademands_id,
            'shown'      => $shown,
            'hidden'     => $hidden,
            'trigger'    => $trigger,
        ];
    }

    private function getForm(bool $with_inactive = false): array
    {
        $data = Metademand::constructMetademands(
            $this->form['metademand'],
            [],
            Metademand::STEP_SHOW,
            $with_inactive,
        );

        return $data[Metademand::STEP_SHOW][$this->form['metademand']]['form'];
    }

    public function testTheWizardLeavesTheInactiveFieldsOut(): void
    {
        $form = $this->getForm();
        $this->assertSame([$this->form['shown']], array_keys($form));

        // The option of the active field no longer makes the inactive one mandatory nor visible
        $options = $form[$this->form['shown']]['options'][0];
        $this->assertSame([], $options['fields_link']);
        $this->assertSame([], $options['hidden_link']);

        // The copy of the metademand keeps them
        $this->assertCount(3, $this->getForm(true));
    }

    public function testTheTicketLeavesTheInactiveFieldsAndTheirOptionsOut(): void
    {
        $result = Metademand::addObjects($this->form['metademand'], ['fields' => [
            $this->form['shown']   => 'shown value',
            // Posted anyway, or restored from a draft saved before
            $this->form['hidden']  => 'hidden value',
            $this->form['trigger'] => 2,
        ]]);
        $this->assertIsArray($result);
        $parent_id = (int) $result['id'];

        $parent = new Ticket();
        $this->assertTrue($parent->getFromDB($parent_id));
        $this->assertStringContainsString('shown value', $parent->fields['content']);
        $this->assertStringNotContainsString('HIDDENFIELD', $parent->fields['content']);
        $this->assertStringNotContainsString('hidden value', $parent->fields['content']);
        $this->assertStringNotContainsString('TRIGGERFIELD', $parent->fields['content']);

        $this->assertSame(
            [$this->form['shown']],
            array_values(array_column(
                getAllDataFromTable(Ticket_Field::getTable(), ['tickets_id' => $parent_id]),
                'plugin_metademands_fields_id',
            )),
        );

        // The task of the inactive field option is not created
        $names = [];
        foreach (getAllDataFromTable(Ticket_Ticket::getTable(), [
            'tickets_id_2' => $parent_id,
            'link'         => Ticket_Ticket::SON_OF,
        ]) as $link) {
            $child = new Ticket();
            $this->assertTrue($child->getFromDB($link['tickets_id_1']));
            $names[] = $child->fields['name'];
        }
        $this->assertCount(1, $names);
        $this->assertStringContainsString('ALWAYSTASK', $names[0]);
    }

    public function testAnInactiveMandatoryFieldIsNotChecked(): void
    {
        // As the wizard posts it, the field left empty
        $post = ['field' => [], 'metademands_id' => $this->form['metademand']];

        $check = Wizard::checkvalues(['id' => $this->form['hidden']], $this->form['hidden'], $post, 'field');
        $this->assertFalse($check['result']);
        $this->assertSame([], $check['content']);

        // Active again: mandatory as before
        $field = new Field();
        $this->assertTrue($field->update(['id' => $this->form['hidden'], 'is_active' => 1]));
        $check = Wizard::checkvalues(['id' => $this->form['hidden']], $this->form['hidden'], $post, 'field');
        $this->assertTrue($check['result']);
        unset($_SESSION['MESSAGE_AFTER_REDIRECT']);
    }

    public function testTheConditionsOnAnInactiveFieldAreLeftOut(): void
    {
        $on_active = $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $this->form['metademand'],
            'plugin_metademands_fields_id'      => $this->form['shown'],
            'type'                              => 'text',
            'check_value'                       => 'Value',
            'show_logic'                        => Condition::SHOW_LOGIC_AND,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'order'                             => 1,
        ], ['check_value']);
        $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $this->form['metademand'],
            'plugin_metademands_fields_id'      => $this->form['hidden'],
            'type'                              => 'text',
            'check_value'                       => 'Value',
            'show_logic'                        => Condition::SHOW_LOGIC_AND,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'order'                             => 2,
        ], ['check_value']);

        $this->assertSame([$on_active->getID()], array_keys(Condition::conditionsTab($this->form['metademand'])));
    }
}
