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
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Metademand;
use GlpiPlugin\Metademands\TicketTask;
use ITILSolution;
use SolutionTemplate;
use SolutionType;
use Ticket;
use Ticket_Ticket;

/**
 * Automatic action MetademandsAutoSolveParents: a parent ticket whose child tickets are all
 * closed is solved, with the solution template of the configuration
 */
class AutoSolveParentTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), ['id' => 1, 'create_pdf' => 0, 'show_form_changes' => 0]);
        }
        $this->configure(0, 0);

        $this->login('glpi', 'glpi');
    }

    private function configure(int $enabled, int $solutiontemplates_id): void
    {
        global $DB;
        $DB->update(Config::getTable(), [
            'auto_solve_parent_ticket' => $enabled,
            'solutiontemplates_id'     => $solutiontemplates_id,
        ], [new \Glpi\DBAL\QueryExpression('true')]);

        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setValue(null, null);
    }

    /**
     * A metademand with two ticket tasks, submitted: its parent ticket and its two children
     *
     * @return array{parent: int, children: list<int>}
     */
    private function createRequest(): array
    {
        $metademands_id = $this->createItem(Metademand::class, [
            'name'               => 'Clôture automatique',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
        ])->getID();
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => 'text',
            'name'        => 'TEXTFIELD',
            'rank'        => 1,
            'order'       => 1,
            'entities_id' => 0,
        ])->getID();
        foreach (['FIRSTTASK', 'SECONDTASK'] as $name) {
            $tasks_id = $this->createItem(Task::class, [
                'plugin_metademands_metademands_id' => $metademands_id,
                'name'        => $name,
                'type'        => Task::TICKET_TYPE,
                'level'       => 1,
                'entities_id' => 0,
            ])->getID();
            $this->createItem(TicketTask::class, [
                'plugin_metademands_tasks_id' => $tasks_id,
                'content'     => 'Contenu',
                'entities_id' => 0,
            ]);
        }

        $result = Metademand::addObjects($metademands_id, ['fields' => [$field => 'value']]);
        $this->assertIsArray($result);
        $parent = (int) $result['id'];

        $children = array_map('intval', array_column(getAllDataFromTable(Ticket_Ticket::getTable(), [
            'tickets_id_2' => $parent,
            'link'         => Ticket_Ticket::SON_OF,
        ]), 'tickets_id_1'));
        $this->assertCount(2, $children);

        return ['parent' => $parent, 'children' => $children];
    }

    private function close(int $tickets_id): void
    {
        $ticket = new Ticket();
        $this->assertTrue($ticket->update(['id' => $tickets_id, 'status' => Ticket::CLOSED]));
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals(Ticket::CLOSED, $ticket->fields['status']);
    }

    private function solutions(int $tickets_id): array
    {
        return array_values(getAllDataFromTable(ITILSolution::getTable(), [
            'itemtype' => Ticket::class,
            'items_id' => $tickets_id,
        ]));
    }

    private function ticketStatus(int $tickets_id): int
    {
        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB($tickets_id));

        return (int) $ticket->fields['status'];
    }

    public function testDisabledByDefault(): void
    {
        $request = $this->createRequest();
        array_map(fn($child) => $this->close($child), $request['children']);

        $this->assertSame(0, Metademand::cronMetademandsAutoSolveParents());
        $this->assertSame([], $this->solutions($request['parent']));
    }

    public function testParentWithAChildStillOpenIsLeftOpen(): void
    {
        $this->configure(1, 0);
        $request = $this->createRequest();
        $this->close($request['children'][0]);

        $this->assertSame(0, Metademand::cronMetademandsAutoSolveParents());
        $this->assertSame([], $this->solutions($request['parent']));
        $this->assertNotContains($this->ticketStatus($request['parent']), [Ticket::SOLVED, Ticket::CLOSED]);
    }

    public function testParentIsSolvedWithTheSolutionTemplate(): void
    {
        $type = $this->createItem(SolutionType::class, ['name' => 'Automatique', 'entities_id' => 0, 'is_recursive' => 1]);
        $template = $this->createItem(SolutionTemplate::class, [
            'name'             => 'Clôture',
            'content'          => 'SOLUTION FROM THE TEMPLATE',
            'solutiontypes_id' => $type->getID(),
            'entities_id'      => 0,
            'is_recursive'     => 1,
        ]);
        $this->configure(1, $template->getID());

        $request = $this->createRequest();
        array_map(fn($child) => $this->close($child), $request['children']);

        $this->assertSame(1, Metademand::cronMetademandsAutoSolveParents());
        $solutions = $this->solutions($request['parent']);
        $this->assertCount(1, $solutions);
        $this->assertStringContainsString('SOLUTION FROM THE TEMPLATE', $solutions[0]['content']);
        $this->assertSame($type->getID(), (int) $solutions[0]['solutiontypes_id']);
        // Closed right away or waiting for approval, as the entity configuration says
        $this->assertContains($this->ticketStatus($request['parent']), [Ticket::SOLVED, Ticket::CLOSED]);

        // Already solved: not solved twice
        $this->assertSame(0, Metademand::cronMetademandsAutoSolveParents());
        $this->assertCount(1, $this->solutions($request['parent']));
    }

    public function testWithoutTemplateADefaultSolutionIsAdded(): void
    {
        $this->configure(1, 0);
        $request = $this->createRequest();
        array_map(fn($child) => $this->close($child), $request['children']);

        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB($request['parent']));
        $this->assertTrue(Ticket_Metademand::isParentReadyToSolve($ticket));

        $this->assertSame(1, Metademand::cronMetademandsAutoSolveParents());
        $solutions = $this->solutions($request['parent']);
        $this->assertCount(1, $solutions);
        $this->assertStringContainsString('All the child tickets are closed.', $solutions[0]['content']);
    }
}
