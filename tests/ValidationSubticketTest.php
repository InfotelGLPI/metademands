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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandValidation;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use Ticket;
use Ticket_Ticket;

class ValidationSubticketTest extends DbTestCase
{
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
    }

    /**
     * A metademand creating child tickets once validated: the validator may still
     * choose to create tasks on the parent ticket instead.
     *
     * @return int the parent ticket waiting for the validation
     */
    private function submitMetademandToValidate(): int
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createItem(Metademand::class, [
            'name'                 => 'Validation sous-tickets',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => 1,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'        => 'text',
            'name'        => 'Champ',
            'rank'        => 1,
            'order'       => 1,
            'entities_id' => 0,
        ]);
        $task = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'name'        => 'CHILDTASK',
            'type'        => Task::TICKET_TYPE,
            'level'       => 1,
            'entities_id' => 0,
        ]);
        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $task->getID(),
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $result = Metademand::addObjects($metademand->getID(), ['fields' => [$field->getID() => 'valeur']]);
        $parent_id = $result['id'];
        $this->assertGreaterThan(0, $parent_id);

        // Nothing is created before the validation
        $this->assertSame(0, countElementsInTable(Ticket_Ticket::getTable(), ['tickets_id_2' => $parent_id]));
        $this->assertSame(0, countElementsInTable(\TicketTask::getTable(), ['tickets_id' => $parent_id]));

        return $parent_id;
    }

    private function validate(int $parent_id, int $create_subticket): void
    {
        $validation = new MetademandValidation();
        $this->assertTrue($validation->getFromDBByCrit(['tickets_id' => $parent_id]));
        $this->assertSame(MetademandValidation::TO_VALIDATE, (int) $validation->fields['validate']);

        $validation->validateMeta([
            'tickets_id'       => $parent_id,
            'create_subticket' => $create_subticket,
        ]);
    }

    public function testValidationCreatesTheChildTickets(): void
    {
        $parent_id = $this->submitMetademandToValidate();

        $this->validate($parent_id, MetademandValidation::CREATE_SUBTICKETS);

        $this->assertSame(
            1,
            countElementsInTable(Ticket_Ticket::getTable(), [
                'tickets_id_2' => $parent_id,
                'link'         => Ticket_Ticket::SON_OF,
            ]),
            'The validation must create the child ticket',
        );
    }

    public function testValidationCreatesTheTasksInsteadOfTheChildTickets(): void
    {
        $parent_id = $this->submitMetademandToValidate();

        $this->validate($parent_id, MetademandValidation::CREATE_TASKS);

        // GLPI tasks of the parent ticket, not task templates of the plugin
        $this->assertSame(1, countElementsInTable(\TicketTask::getTable(), ['tickets_id' => $parent_id]));
        $this->assertSame(0, countElementsInTable(Ticket_Ticket::getTable(), ['tickets_id_2' => $parent_id]));
    }
}
