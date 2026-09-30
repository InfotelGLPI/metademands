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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;

/**
 * Task list of a metademand (task_list.html.twig): the values are escaped by Twig,
 * and the add / edit links are driven by data-md-subitem-* attributes instead of
 * generated inline scripts.
 */
class TaskListTest extends DbTestCase
{
    private const NAME = '<b>Task</b>';

    /**
     * A metademand in maintenance mode (so its tasks are editable) with one ticket task.
     *
     * @return array{Metademand, Task}
     */
    private function createMetademandWithTask(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Task list',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'maintenance_mode' => 1,
        ]);
        $task = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'name'                              => 'Task',
            'type'                              => Task::TICKET_TYPE,
            'level'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
            'formatastable'                     => 0,
            'useBlock'                          => 0,
            'block_use'                         => '[]',
        ]);
        // Store markup as legacy data could hold it, whatever the input sanitization
        global $DB;
        $DB->update(Task::getTable(), ['name' => self::NAME], ['id' => $task->getID()]);

        return [$metademand, $task];
    }

    private function renderTasks(Metademand $metademand): string
    {
        ob_start();
        Task::displayTabContentForItem($metademand);

        return (string) ob_get_clean();
    }

    public function testListEscapesTheNameAndDrivesTheFormByAttributes(): void
    {
        $this->login();
        [$metademand, $task] = $this->createMetademandWithTask();

        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Task::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderTasks($metademand);

        $this->assertStringContainsString('&lt;b&gt;Task&lt;/b&gt;', $html);
        $this->assertStringNotContainsString(self::NAME, $html);
        foreach (['addchild', 'viewEditchild', 'javascript:'] as $generated) {
            $this->assertStringNotContainsString($generated, $html);
        }
        // Add button (id -1) and editable row (task id)
        $this->assertMatchesRegularExpression('/data-md-subitem-params="[^"]*&quot;id&quot;:-1[,}]/', $html);
        $this->assertMatchesRegularExpression(
            '/data-md-subitem-params="[^"]*&quot;id&quot;:' . $task->getID() . '[,}]/',
            $html,
        );
        $this->assertStringContainsString('data-md-subitem-noopen', $html);
        $this->assertStringContainsString('/lib/treetable/treetable', $html);
        $this->assertStringContainsString('id="tags"', $html);
        $this->assertStringContainsString('name="_glpi_csrf_token"', $html);
    }

    public function testReadOnlyListHasNoFormNorMassiveActions(): void
    {
        $this->login();
        [$metademand] = $this->createMetademandWithTask();

        $_SESSION['glpiactiveprofile'][Task::$rightname] = READ;
        $html = $this->renderTasks($metademand);

        $this->assertStringContainsString('&lt;b&gt;Task&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('data-md-subitem-target', $html);
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
    }
}
