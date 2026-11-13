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

use CommonITILValidation;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\FieldValidation;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandValidation;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Task;
use GlpiPlugin\Metademands\TicketTask;
use ITILFollowup;
use Ticket;
use Ticket_Ticket;
use TicketValidation;
use User;

/**
 * Child tickets held until the validation of a user chosen in the form
 * (FieldOption.validation_fields_id).
 *
 * The plugin is not active in the test environment, so Plugin::doHook() skips its
 * hooks: the ITEM_UPDATE / ITEM_PURGE hooks of TicketValidation are called here.
 */
class FieldValidationTest extends DbTestCase
{
    private const YES = 2;
    private const NO  = 1;

    /** @var array{metademand: int, equipment: int, manager: int, equip_task: int, sub_task: int} */
    private array $form;

    public function setUp(): void
    {
        parent::setUp();

        // Reset Config singleton so each test reloads config from DB
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
     * An "Equipment" yes / no field whose "yes" creates the EQUIPTASK child ticket (and its
     * SUBTASK sub-task) once approved by the user of the "Manager" field; OTHERTASK is
     * always created.
     */
    private function createForm(int $validation_subticket = 0): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'                 => 'Valideur dynamique',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => $validation_subticket,
        ]);
        $metademands_id = $metademand->getID();

        $equipment = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => 'yesno',
            'name'        => 'Equipment',
            'rank'        => 1,
            'order'       => 1,
            'entities_id' => 0,
        ]);
        $manager = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => 'dropdown_object',
            'item'        => User::class,
            'name'        => 'Manager',
            'rank'        => 1,
            'order'       => 2,
            'entities_id' => 0,
        ]);
        // As saved by the field form: the ticket content reads the user details to show
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $manager->getID(),
            'informations_to_display'      => '["full_name"]',
        ], ['informations_to_display']);

        $equip_task = $this->createTask($metademands_id, 'EQUIPTASK', 1);
        $sub_task   = $this->createTask($metademands_id, 'SUBTASK', 2, $equip_task);
        $this->createTask($metademands_id, 'OTHERTASK', 1);

        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $equipment->getID(),
            'check_value'                  => self::YES,
            'plugin_metademands_tasks_id'  => $equip_task,
            'validation_fields_id'         => $manager->getID(),
        ], ['check_value']);

        return [
            'metademand' => $metademands_id,
            'equipment'  => $equipment->getID(),
            'manager'    => $manager->getID(),
            'equip_task' => $equip_task,
            'sub_task'   => $sub_task,
        ];
    }

    private function createTask(int $metademands_id, string $name, int $level, int $parent = 0): int
    {
        $task = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'plugin_metademands_tasks_id'       => $parent,
            'name'        => $name,
            'type'        => Task::TICKET_TYPE,
            'level'       => $level,
            'entities_id' => 0,
        ]);
        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $task->getID(),
            'content'     => 'Contenu de ' . $name,
            'entities_id' => 0,
        ]);

        return $task->getID();
    }

    private function submit(int $equipment, int $manager): int
    {
        $result = Metademand::addObjects($this->form['metademand'], ['fields' => [
            $this->form['equipment'] => $equipment,
            $this->form['manager']   => $manager,
        ]]);
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['id']);

        return (int) $result['id'];
    }

    /**
     * @return string[] names of the child tickets of the parent ticket
     */
    private function getChildNames(int $parent_id): array
    {
        $names = [];
        foreach (getAllDataFromTable(Ticket_Ticket::getTable(), [
            'tickets_id_2' => $parent_id,
            'link'         => Ticket_Ticket::SON_OF,
        ]) as $link) {
            $child = new Ticket();
            $this->assertTrue($child->getFromDB($link['tickets_id_1']));
            foreach (['EQUIPTASK', 'SUBTASK', 'OTHERTASK'] as $name) {
                if (str_contains($child->fields['name'], $name)) {
                    $names[] = $name;
                }
            }
        }
        sort($names);

        return $names;
    }

    private function getValidation(int $parent_id): TicketValidation
    {
        $validation = new TicketValidation();
        $this->assertTrue($validation->getFromDBByCrit(['tickets_id' => $parent_id]));

        return $validation;
    }

    private function getHeld(int $parent_id): FieldValidation
    {
        $held = new FieldValidation();
        $this->assertTrue($held->getFromDBByCrit(['tickets_id' => $parent_id]));

        return $held;
    }

    /**
     * Answer as the validator, then run the hook the plugin registers.
     */
    private function answer(int $parent_id, int $status, string $comment = ''): void
    {
        $this->login('tech', 'tech');

        $validation = $this->getValidation($parent_id);
        $this->assertTrue($validation->update([
            'id'                 => $validation->getID(),
            'status'             => $status,
            'comment_validation' => $comment,
        ]));
        FieldValidation::afterTicketValidationUpdate($validation);
    }

    private function countSubTaskSlots(int $parent_id): int
    {
        return countElementsInTable(Ticket_Task::getTable(), [
            'parent_tickets_id'           => $parent_id,
            'plugin_metademands_tasks_id' => $this->form['sub_task'],
        ]);
    }

    public function testOnlyTheUserFieldsOfTheMetademandCanBeTheValidator(): void
    {
        $this->assertSame(
            [$this->form['manager']],
            array_keys(FieldValidation::getValidatorFieldChoices($this->form['metademand'])),
        );

        // Not a user field: dropped on save
        $option = $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $this->form['equipment'],
            'check_value'                  => self::NO,
            'validation_fields_id'         => $this->form['equipment'],
        ], ['check_value', 'validation_fields_id']);
        $this->assertSame(0, (int) $option->fields['validation_fields_id']);
    }

    public function testEquipmentChildTicketsWaitForTheValidator(): void
    {
        $tech_id   = getItemByTypeName(User::class, 'tech', true);
        $parent_id = $this->submit(self::YES, $tech_id);

        // The other child tickets are created at once, the equipment ones are held
        $this->assertSame(['OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(0, $this->countSubTaskSlots($parent_id), 'The sub-task of a held task is held with it');

        $validation = $this->getValidation($parent_id);
        $this->assertSame(User::class, $validation->fields['itemtype_target']);
        $this->assertSame($tech_id, (int) $validation->fields['items_id_target']);
        $this->assertStringContainsString('EQUIPTASK', $validation->fields['comment_submission']);

        $held = $this->getHeld($parent_id);
        $this->assertSame(FieldValidation::WAITING, (int) $held->fields['status']);
        $this->assertSame($validation->getID(), (int) $held->fields['ticketvalidations_id']);
        $this->assertTrue(FieldValidation::hasWaiting($parent_id));
    }

    public function testAcceptedValidationCreatesTheHeldChildTickets(): void
    {
        $parent_id = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));

        $this->answer($parent_id, CommonITILValidation::ACCEPTED);

        $this->assertSame(['EQUIPTASK', 'OTHERTASK'], $this->getChildNames($parent_id));
        // Created later, on the resolution of EQUIPTASK, as without validation
        $this->assertSame(1, $this->countSubTaskSlots($parent_id));
        $this->assertSame(FieldValidation::CREATED, (int) $this->getHeld($parent_id)->fields['status']);
        $this->assertFalse(FieldValidation::hasWaiting($parent_id));
    }

    public function testRefusedValidationDropsTheHeldChildTickets(): void
    {
        $parent_id = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));

        $this->answer($parent_id, CommonITILValidation::REFUSED, 'Budget épuisé');

        $this->assertSame(['OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(0, $this->countSubTaskSlots($parent_id));
        $this->assertSame(FieldValidation::REFUSED, (int) $this->getHeld($parent_id)->fields['status']);

        $followups = getAllDataFromTable(ITILFollowup::getTable(), [
            'itemtype' => Ticket::class,
            'items_id' => $parent_id,
        ]);
        $this->assertCount(1, $followups);
        $content = reset($followups)['content'];
        $this->assertStringContainsString('EQUIPTASK', $content);
        $this->assertStringContainsString('Budget épuisé', $content);
    }

    public function testDeletedValidationRequestDropsTheHeldChildTickets(): void
    {
        $parent_id  = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));
        $validation = $this->getValidation($parent_id);

        $this->assertTrue($validation->delete(['id' => $validation->getID()], true));
        FieldValidation::afterTicketValidationPurge($validation);

        $this->assertSame(['OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(FieldValidation::REFUSED, (int) $this->getHeld($parent_id)->fields['status']);
    }

    public function testWithoutEquipmentNothingIsHeld(): void
    {
        $parent_id = $this->submit(self::NO, getItemByTypeName(User::class, 'tech', true));

        $this->assertSame(['OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(0, countElementsInTable(TicketValidation::getTable(), ['tickets_id' => $parent_id]));
        $this->assertSame(0, countElementsInTable(FieldValidation::getTable(), ['tickets_id' => $parent_id]));
    }

    public function testHeldChildTicketsAreListedOnTheParentTicket(): void
    {
        $parent_id = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));

        // The first level only: the sub-task follows its parent child ticket
        $rows = FieldValidation::getHeldRows($parent_id);
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('EQUIPTASK', $rows[0]['task_name']);
        $this->assertTrue($rows[0]['is_waiting']);
        $this->assertSame(getUserName(getItemByTypeName(User::class, 'tech', true)), $rows[0]['validator']);

        $html = \Glpi\Application\View\TemplateRenderer::getInstance()->render(
            '@metademands/forms/ticket_followup.html.twig',
            ['held_rows' => $rows, 'has_tickets' => false, 'is_central' => true],
        );
        $this->assertStringContainsString('EQUIPTASK', $html);
        $this->assertStringContainsString('ti-hourglass-high', $html);

        $this->answer($parent_id, CommonITILValidation::REFUSED, 'Non');
        $rows = FieldValidation::getHeldRows($parent_id);
        $this->assertCount(1, $rows);
        $this->assertFalse($rows[0]['is_waiting']);

        // Accepted: real child tickets, listed with the others
        $parent_id = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));
        $this->answer($parent_id, CommonITILValidation::ACCEPTED);
        $this->assertSame([], FieldValidation::getHeldRows($parent_id));
    }

    /**
     * "Validation of the sub-tickets" enabled: the child tickets wait for the validation
     * of the metademand, then the equipment ones for the validator of the form.
     */
    public function testValidatedMetademandHoldsTheEquipmentChildTickets(): void
    {
        $this->form = $this->createForm(1);
        $parent_id  = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));
        $this->assertSame([], $this->getChildNames($parent_id));
        $this->assertSame(0, countElementsInTable(TicketValidation::getTable(), ['tickets_id' => $parent_id]));

        $validation = new MetademandValidation();
        $this->assertTrue($validation->getFromDBByCrit(['tickets_id' => $parent_id]));
        $validation->validateMeta([
            'tickets_id'       => $parent_id,
            'create_subticket' => MetademandValidation::CREATE_SUBTICKETS,
        ]);

        $this->assertSame(['OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(0, $this->countSubTaskSlots($parent_id));
        $this->assertTrue(FieldValidation::hasWaiting($parent_id));

        $this->answer($parent_id, CommonITILValidation::ACCEPTED);

        $this->assertSame(['EQUIPTASK', 'OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(1, $this->countSubTaskSlots($parent_id));
    }

    /**
     * The validator of the metademand may create tasks of the parent ticket instead: the
     * equipment ones wait the same way, and stay tasks once accepted.
     */
    public function testValidatedMetademandHoldsTheEquipmentTasks(): void
    {
        $this->form = $this->createForm(1);
        $parent_id  = $this->submit(self::YES, getItemByTypeName(User::class, 'tech', true));

        $validation = new MetademandValidation();
        $this->assertTrue($validation->getFromDBByCrit(['tickets_id' => $parent_id]));
        $validation->validateMeta([
            'tickets_id'       => $parent_id,
            'create_subticket' => MetademandValidation::CREATE_TASKS,
        ]);

        $task_contents = fn() => array_column(
            getAllDataFromTable(\TicketTask::getTable(), ['tickets_id' => $parent_id]),
            'content',
        );
        $this->assertCount(1, $task_contents());
        $this->assertStringContainsString('OTHERTASK', implode(' ', $task_contents()));
        $this->assertSame(1, (int) $this->getHeld($parent_id)->fields['as_tasks']);

        $this->answer($parent_id, CommonITILValidation::ACCEPTED);

        // EQUIPTASK and its sub-task, as tasks of the parent ticket, no child ticket
        $this->assertCount(3, $task_contents());
        $this->assertStringContainsString('EQUIPTASK', implode(' ', $task_contents()));
        $this->assertSame([], $this->getChildNames($parent_id));
    }

    public function testWithoutValidatorTheChildTicketsAreCreatedAtOnce(): void
    {
        $parent_id = $this->submit(self::YES, 0);

        $this->assertSame(['EQUIPTASK', 'OTHERTASK'], $this->getChildNames($parent_id));
        $this->assertSame(1, $this->countSubTaskSlots($parent_id));
        $this->assertSame(0, countElementsInTable(TicketValidation::getTable(), ['tickets_id' => $parent_id]));
        $this->hasSessionMessages(WARNING, [
            'The validation of some child tickets could not be requested: they have been created without validation.',
        ]);
    }
}
