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
use GlpiPlugin\Metademands\Interticketfollowup;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Ticket_Metademand;
use GlpiPlugin\Metademands\Ticket_Task;
use Ticket;

/**
 * Inter ticket followups of the tickets of a meta-demand: a followup sent to "all tickets"
 * (targets_id = 0) is stored once and shown in the timeline of every ticket of the family.
 */
class InterticketFollowupTest extends DbTestCase
{
    /**
     * A meta-demand ticket and two son tickets
     *
     * @return array{Ticket, Ticket, Ticket}
     */
    private function createFamily(): array
    {
        $entities_id = $this->getTestRootEntity(true);
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Inter ticket followups',
            'entities_id'      => $entities_id,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $tickets = [];
        foreach (['Parent', 'Son 1', 'Son 2'] as $name) {
            $tickets[] = $this->createItem(Ticket::class, [
                'name'        => $name,
                'content'     => $name,
                'entities_id' => $entities_id,
            ]);
        }
        [$parent, $son_1, $son_2] = $tickets;

        $this->createItem(Ticket_Metademand::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'tickets_id'                        => $parent->getID(),
            'parent_tickets_id'                 => $parent->getID(),
        ]);
        foreach ([$son_1, $son_2] as $son) {
            $this->createItem(Ticket_Task::class, [
                'tickets_id'        => $son->getID(),
                'parent_tickets_id' => $parent->getID(),
                'level'             => 1,
            ]);
        }

        return $tickets;
    }

    /**
     * Inter ticket followups of the timeline of a ticket
     *
     * @return int[]
     */
    private function getTimelineFollowupIds(Ticket $ticket): array
    {
        $result = Interticketfollowup::getlistItems(['item' => $ticket, 'timeline' => []]);
        $ids = [];
        foreach ($result['timeline'] as $entry) {
            if ($entry['type'] === Interticketfollowup::class) {
                $ids[] = (int) $entry['item']['id'];
            }
        }

        return $ids;
    }

    public function testAllTicketsFollowupIsShownInEveryTicketOfTheFamily(): void
    {
        $this->login();
        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Interticketfollowup::$rightname] = ALLSTANDARDRIGHT;
        [$parent, $son_1, $son_2] = $this->createFamily();

        $this->assertArrayHasKey(0, Interticketfollowup::getTargets($parent->getID()));
        $this->assertArrayHasKey(0, Interticketfollowup::getTargets($son_1->getID()));

        $followups = [];
        foreach (['from the parent' => $parent, 'from a son' => $son_1] as $origin => $ticket) {
            $followups[$origin] = $this->createItem(Interticketfollowup::class, [
                'itemtype'   => Ticket::class,
                'tickets_id' => $ticket->getID(),
                'targets_id' => 0,
                'content'    => 'To all tickets ' . $origin,
            ], ['itemtype', 'content']);
        }

        foreach ([$parent, $son_1, $son_2] as $ticket) {
            $ids = $this->getTimelineFollowupIds($ticket);
            foreach ($followups as $origin => $followup) {
                $this->assertContains($followup->getID(), $ids, sprintf('%s, followup %s', $ticket->fields['name'], $origin));
            }
        }
    }

    /**
     * A son created by a task of type meta-demand has a meta-demand row of its own, whose parent is the ticket of the
     * meta-demand: it is a ticket of the family, not the first ticket of another one.
     */
    public function testAllTicketsFollowupReachesASubMetademandSon(): void
    {
        global $DB;

        $this->login();
        $_SESSION['glpiactiveprofile'][Interticketfollowup::$rightname] = ALLSTANDARDRIGHT;
        [$parent, $son_1] = $this->createFamily();

        $sub_metademand = $this->createItem(Metademand::class, [
            'name'             => 'Sub meta-demand',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $sub_son = $this->createItem(Ticket::class, [
            'name'        => 'Sub meta-demand son',
            'content'     => 'Sub meta-demand son',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        // Task of type meta-demand of the meta-demand of the parent, and the ticket it created
        $DB->insert('glpi_plugin_metademands_metademandtasks', [
            'plugin_metademands_metademands_id' => $sub_metademand->getID(),
            'plugin_metademands_tasks_id'       => 0,
        ]);
        $this->createItem(Ticket_Metademand::class, [
            'plugin_metademands_metademands_id' => $sub_metademand->getID(),
            'tickets_id'                        => $sub_son->getID(),
            'parent_tickets_id'                 => $parent->getID(),
        ]);

        $this->assertSame($parent->getID(), (int) Interticketfollowup::getFirstTicket($sub_son->getID()));
        $this->assertArrayHasKey($parent->getID(), Interticketfollowup::getTargets($sub_son->getID()));

        $from_parent = $this->createItem(Interticketfollowup::class, [
            'itemtype'   => Ticket::class,
            'tickets_id' => $parent->getID(),
            'targets_id' => 0,
            'content'    => 'To all tickets from the parent',
        ], ['itemtype', 'content']);
        $from_sub_son = $this->createItem(Interticketfollowup::class, [
            'itemtype'   => Ticket::class,
            'tickets_id' => $sub_son->getID(),
            'targets_id' => 0,
            'content'    => 'To all tickets from the sub meta-demand son',
        ], ['itemtype', 'content']);

        $this->assertContains($from_parent->getID(), $this->getTimelineFollowupIds($sub_son));
        foreach ([$parent, $son_1] as $ticket) {
            $this->assertContains($from_sub_son->getID(), $this->getTimelineFollowupIds($ticket), $ticket->fields['name']);
        }
    }
}
