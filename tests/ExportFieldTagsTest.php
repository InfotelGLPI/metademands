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
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\MailTask;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use User;

/**
 * Import of an exported metademand: the field tags (#id#, #id.attr#) of the titles and
 * contents of its children point at the fields created by the import, so that they need not
 * be written again on the target GLPI.
 */
class ExportFieldTagsTest extends DbTestCase
{
    public function testRemapFieldTags(): void
    {
        $map = [12 => 112, 13 => 113, 7 => 12];

        $this->assertSame('#112#', Export::remapFieldTags('#12#', $map));
        $this->assertSame('#112.login# / #113.email#', Export::remapFieldTags('#12.login# / #13.email#', $map));
        // Tags sharing their '#', as the resolver reads them
        $this->assertSame('#112#113#', Export::remapFieldTags('#12#13#', $map));
        // Alternation and markup
        $this->assertSame('[#112#|#113#] <p>#112#</p>', Export::remapFieldTags('[#12#|#13#] <p>#12#</p>', $map));
        // One pass: the new id of 7 is not mapped again
        $this->assertSame('#12# #112#', Export::remapFieldTags('#7# #12#', $map));
        // Left as is: other fields, requester tags, colors, entities, non canonical numbers
        foreach (['#99#', '#requester.login#', '#entity#', 'color: #123456;', '&#12;', '#012#', '# 12#', '#12'] as $text) {
            $this->assertSame($text, Export::remapFieldTags($text, $map), $text);
        }
        $this->assertSame('#12#', Export::remapFieldTags('#12#', []));
    }

    public function testImportPointsTheTagsOfTheChildrenAtTheNewFields(): void
    {
        $this->login();

        $entities_id = $this->getTestRootEntity(true);
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Export field tags',
            'entities_id'      => $entities_id,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $base = [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'rank'                              => 1,
            'entities_id'                       => $entities_id,
        ];
        $text = $this->createItem(Field::class, $base + ['type' => 'text', 'item' => '', 'name' => 'Text', 'order' => 1], ['order', 'item']);
        $user = $this->createItem(Field::class, $base + ['type' => 'dropdown_object', 'item' => User::class, 'name' => 'User', 'order' => 2], ['order']);
        $t = $text->getID();
        $u = $user->getID();

        $task_base = [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'level'                             => 1,
            'entities_id'                       => $entities_id,
            'formatastable'                     => 0,
            'useBlock'                          => 0,
            'block_use'                         => '[]',
        ];
        $child = $this->createItem(Task::class, $task_base + ['type' => Task::TICKET_TYPE, 'name' => "Request #$t# for #$u.login#"]);
        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $child->getID(),
            'content'                     => "<p>[#$t#|none] - #$u.firstname# #$u.name#</p>",
            'entities_id'                 => 0,
        ]);
        $task = $this->createItem(Task::class, $task_base + ['type' => Task::TASK_TYPE, 'name' => "Task #$t#"]);
        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $task->getID(),
            'content'                     => "<p>Check #$t#</p>",
            'entities_id'                 => 0,
        ]);
        $mail = $this->createItem(Task::class, $task_base + ['type' => Task::MAIL_TYPE, 'name' => "Mail #$u.name#"]);
        $this->createItem(MailTask::class, [
            'plugin_metademands_tasks_id' => $mail->getID(),
            'content'                     => "<p>Hello #$u.firstname#, #requester.name# asks #$t#</p>",
            'users_id_recipient'          => 0,
            'groups_id_recipient'         => 0,
            'email'                       => '',
            'itilcategories_id'           => 0,
        ]);

        $target = Export::exportAsXMLForMetademands($metademand->getID());
        $this->assertNotEmpty($target);

        $import = new \ReflectionMethod(Export::class, 'importXmlFile');
        $new_id = (int) $import->invoke(null, $target['path']);
        @unlink($target['path']);
        $this->assertGreaterThan(0, $new_id);

        $field = new Field();
        $new_t = array_key_first($field->find(['plugin_metademands_metademands_id' => $new_id, 'type' => 'text']));
        $new_u = array_key_first($field->find(['plugin_metademands_metademands_id' => $new_id, 'type' => 'dropdown_object']));
        $this->assertNotNull($new_t);
        $this->assertNotNull($new_u);
        $this->assertNotSame($t, $new_t);

        $tasks = [];
        foreach ((new Task())->find(['plugin_metademands_metademands_id' => $new_id]) as $row) {
            $tasks[(int) $row['type']] = $row;
        }
        $this->assertCount(3, $tasks);

        // Ticket child: title and content
        $this->assertSame("Request #$new_t# for #$new_u.login#", $tasks[Task::TICKET_TYPE]['name']);
        $tickettask = new TicketTask();
        $this->assertTrue($tickettask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks[Task::TICKET_TYPE]['id']]));
        $this->assertSame("<p>[#$new_t#|none] - #$new_u.firstname# #$new_u.name#</p>", $tickettask->fields['content']);

        // Task of the parent ticket: its content is exported, tags included
        $this->assertSame("Task #$new_t#", $tasks[Task::TASK_TYPE]['name']);
        $this->assertTrue($tickettask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks[Task::TASK_TYPE]['id']]));
        $this->assertSame("<p>Check #$new_t#</p>", $tickettask->fields['content']);

        // Mail: subject and content, the requester tag untouched
        $this->assertSame("Mail #$new_u.name#", $tasks[Task::MAIL_TYPE]['name']);
        $mailtask = new MailTask();
        $this->assertTrue($mailtask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks[Task::MAIL_TYPE]['id']]));
        $this->assertSame("<p>Hello #$new_u.firstname#, #requester.name# asks #$new_t#</p>", $mailtask->fields['content']);

        // No task row lent from another task
        $this->assertCount(2, $tickettask->find(['plugin_metademands_tasks_id' => array_column($tasks, 'id')]));
    }
}
