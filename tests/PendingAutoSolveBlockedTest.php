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

use CommonITILObject;
use CronTask;
use Glpi\Plugin\Hooks;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Metademand;
use GlpiPlugin\Metademands\Ticket_Task;
use ITILSolution;
use PendingReason;
use PendingReason_Item;
use PendingReasonCron;
use SolutionTemplate;
use Ticket;

/**
 * A parent ticket of a metademand whose child ticket blocks its resolution must not be solved by
 * the automatic resolution of a pending ticket (PendingReasonCron): that automatic action runs
 * without a logged-in user, so the hooks blocking the resolution must be registered then too.
 */
final class PendingAutoSolveBlockedTest extends DbTestCase
{
    /** @var array<string, mixed> state of the Plugin class before the test */
    private array $plugin_state = [];

    private array $plugin_hooks = [];

    public function setUp(): void
    {
        global $PLUGIN_HOOKS;

        parent::setUp();

        // The test environment does not activate the plugin: Plugin::doHook() would skip its
        // hooks. It is declared active for the test, the state restored in tearDown().
        foreach (['activated_plugins', 'plugins_initialized'] as $property) {
            $ref = new \ReflectionProperty(\Plugin::class, $property);
            $this->plugin_state[$property] = $ref->getValue();
        }
        (new \ReflectionProperty(\Plugin::class, 'activated_plugins'))
            ->setValue(null, array_merge($this->plugin_state['activated_plugins'], ['metademands']));
        (new \ReflectionProperty(\Plugin::class, 'plugins_initialized'))->setValue(null, true);
        $this->plugin_hooks = $PLUGIN_HOOKS ?? [];
    }

    public function tearDown(): void
    {
        global $PLUGIN_HOOKS;

        foreach ($this->plugin_state as $property => $value) {
            (new \ReflectionProperty(\Plugin::class, $property))->setValue(null, $value);
        }
        $PLUGIN_HOOKS = $this->plugin_hooks;

        parent::tearDown();
    }

    /**
     * The hooks of the plugin as plugin_init_metademands() registers them without a logged-in
     * user, as for an automatic action
     */
    private function initPluginWithoutUser(): void
    {
        global $PLUGIN_HOOKS;

        $this->logOut();
        unset($PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['metademands'], $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['metademands']);
        plugin_init_metademands();
    }

    public function testResolutionHooksAreRegisteredWithoutLoggedInUser(): void
    {
        global $PLUGIN_HOOKS;

        $this->initPluginWithoutUser();

        $this->assertSame(
            [\GlpiPlugin\Metademands\Ticket::class, 'pre_add_solution'],
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['metademands']['ITILSolution'] ?? null,
        );
        $this->assertSame(
            [\GlpiPlugin\Metademands\Ticket::class, 'pre_update_ticket'],
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['metademands']['Ticket'] ?? null,
        );
    }

    public function testAutoSolveOfPendingParentBlockedByChildTicket(): void
    {
        global $DB, $PLUGIN_HOOKS;

        $this->login();
        $entity = $this->getTestRootEntity(true);

        // A metademand and a task blocking the resolution of the parent ticket
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Pending auto-solve',
            'entities_id'      => $entity,
            'object_to_create' => 'Ticket',
            'type'             => Ticket::DEMAND_TYPE,
        ]);
        $task = $this->createItem(Task::class, [
            'name'                              => 'Blocking task',
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => Task::TICKET_TYPE,
            'block_parent_ticket_resolution'    => 1,
            'entities_id'                       => $entity,
        ]);

        // The parent ticket and its open child ticket
        $parent = $this->createItem(Ticket::class, [
            'name'        => 'Parent',
            'content'     => 'Parent',
            'entities_id' => $entity,
        ]);
        $child = $this->createItem(Ticket::class, [
            'name'        => 'Child',
            'content'     => 'Child',
            'entities_id' => $entity,
        ]);
        $DB->insert(Ticket_Metademand::getTable(), [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'tickets_id'                        => $parent->getID(),
            'parent_tickets_id'                 => 0,
        ]);
        $DB->insert(Ticket_Task::getTable(), [
            'plugin_metademands_tasks_id' => $task->getID(),
            'level'                       => 1,
            'tickets_id'                  => $child->getID(),
            'parent_tickets_id'           => $parent->getID(),
        ]);

        // The parent waits, with an automatic resolution already due
        $template = $this->createItem(SolutionTemplate::class, [
            'name'        => 'Auto solution',
            'content'     => 'Solved automatically',
            'entities_id' => $entity,
        ]);
        $reason = $this->createItem(PendingReason::class, [
            'name'                        => 'Waiting',
            'entities_id'                 => $entity,
            'followup_frequency'          => 3600,
            'followups_before_resolution' => -1,
            'solutiontemplates_id'        => $template->getID(),
        ]);
        $this->assertTrue($parent->update(['id' => $parent->getID(), 'status' => CommonITILObject::WAITING]));
        $DB->insert(PendingReason_Item::getTable(), [
            'itemtype'                    => Ticket::class,
            'items_id'                    => $parent->getID(),
            'pendingreasons_id'           => $reason->getID(),
            'followup_frequency'          => 3600,
            'followups_before_resolution' => -1,
            'bump_count'                  => 0,
            'last_bump_date'              => date('Y-m-d H:i:s', strtotime('-2 days')),
        ]);

        // The automatic action, run without a logged-in user
        $this->initPluginWithoutUser();
        $this->assertArrayHasKey('ITILSolution', $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['metademands']);
        $_SESSION['glpicronuserrunning'] = 'cron_pendingreason_autobump_autosolve';
        try {
            PendingReasonCron::cronPendingreason_autobump_autosolve(new CronTask());
        } finally {
            unset($_SESSION['glpicronuserrunning']);
        }

        // No solution, the parent still waits
        $this->assertSame(0, countElementsInTable(ITILSolution::getTable(), [
            'itemtype' => Ticket::class,
            'items_id' => $parent->getID(),
        ]));
        $parent->getFromDB($parent->getID());
        $this->assertSame(CommonITILObject::WAITING, (int) $parent->fields['status']);
    }
}
