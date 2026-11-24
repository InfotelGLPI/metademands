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
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\MailTask;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketField;
use GlpiPlugin\Metademands\TicketTask;
use Group;
use Location;
use Search;
use Ticket;
use User;

/**
 * What points at a field, at a custom value or at a GLPI object in a metademand, kept on the
 * copy (duplication) and on another GLPI (export / import): "Link this field to a user field",
 * the checkbox field of an option, the value of a condition, the "display after" field, the
 * fields of the ticket and the tags of the texts.
 */
class ExportFieldLinksTest extends DbTestCase
{
    /**
     * Search option of a field of the ticket templates: the location, the assigned group (the
     * requester group is filled by the plugin itself, and may not be predefined).
     */
    private function getTicketOption(string $table): int
    {
        $field = ['glpi_locations' => 'locations_id', 'glpi_groups' => '_groups_id_assign'][$table];
        $num   = array_search($field, \TicketTemplate::getAllowedFields(true, true), true);
        $this->assertNotFalse($num, $field);
        $this->assertSame($table, Search::getOptions(Ticket::class)[$num]['table']);

        return (int) $num;
    }

    /**
     * @return array<string, int> ids of the source metademand and of its items
     */
    private function createSource(int $force_create_tasks = 0): array
    {
        global $DB;

        $entities_id = $this->getTestRootEntity(true);
        $metademand = $this->createItem(Metademand::class, [
            'name'               => 'Field links ' . mt_rand(),
            'entities_id'        => $entities_id,
            'object_to_create'   => 'Ticket',
            'type'               => 0,
            'force_create_tasks' => $force_create_tasks,
        ]);
        $meta_id = $metademand->getID();
        $base = [
            'plugin_metademands_metademands_id' => $meta_id,
            'rank'                              => 1,
            'entities_id'                       => $entities_id,
        ];
        $user = $this->createItem(Field::class, $base + ['type' => 'dropdown_object', 'item' => User::class, 'name' => 'User', 'order' => 1], ['order']);
        $text = $this->createItem(Field::class, $base + ['type' => 'text', 'item' => '', 'name' => 'Text', 'order' => 2], ['order', 'item']);
        $checkbox = $this->createItem(Field::class, $base + ['type' => 'checkbox', 'item' => '', 'name' => 'Checkbox', 'order' => 3], ['order', 'item']);
        $radio = $this->createItem(Field::class, $base + ['type' => 'radio', 'item' => '', 'name' => 'Radio', 'order' => 4], ['order', 'item']);
        // "Display field after": the text after the user field
        $DB->update(Field::getTable(), ['plugin_metademands_fields_id' => $user->getID()], ['id' => $text->getID()]);

        foreach ([$user, $checkbox, $radio] as $field) {
            $this->createItem(FieldParameter::class, ['plugin_metademands_fields_id' => $field->getID()]);
        }
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $text->getID(),
            'link_to_user'                 => $user->getID(),
        ]);

        $radio_value = $this->createItem(FieldCustomvalue::class, ['plugin_metademands_fields_id' => $radio->getID(), 'name' => 'R', 'rank' => 0]);
        $this->createItem(FieldCustomvalue::class, ['plugin_metademands_fields_id' => $checkbox->getID(), 'name' => 'C1', 'rank' => 0]);
        $checkbox_value = $this->createItem(FieldCustomvalue::class, ['plugin_metademands_fields_id' => $checkbox->getID(), 'name' => 'C2', 'rank' => 1]);

        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $radio->getID(),
            'check_value'                  => $radio_value->getID(),
            'checkbox_id'                  => $checkbox->getID(),
            'checkbox_value'               => $checkbox_value->getID(),
            'hidden_block'                 => 2,
        ], ['check_value']);
        $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $meta_id,
            'plugin_metademands_fields_id'      => $radio->getID(),
            'type'                              => 'radio',
            'check_value'                       => $radio_value->getID(),
            'show_logic'                        => Condition::SHOW_LOGIC_AND,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'order'                             => 1,
        ], ['check_value']);

        $task_base = [
            'plugin_metademands_metademands_id' => $meta_id,
            'level'                             => 1,
            'entities_id'                       => $entities_id,
            'formatastable'                     => 0,
            'useBlock'                          => 0,
            'block_use'                         => '[]',
        ];
        $t = $text->getID();
        $u = $user->getID();
        $child = $this->createItem(Task::class, $task_base + ['type' => Task::TICKET_TYPE, 'name' => "Child #$t#"]);
        $this->createItem(TicketTask::class, ['plugin_metademands_tasks_id' => $child->getID(), 'content' => "<p>#$u.login#</p>", 'entities_id' => 0]);
        $task = $this->createItem(Task::class, $task_base + ['type' => Task::TASK_TYPE, 'name' => 'Task']);
        $this->createItem(TicketTask::class, ['plugin_metademands_tasks_id' => $task->getID(), 'content' => "<p>Check #$t#</p>", 'entities_id' => 0]);
        $mail = $this->createItem(Task::class, $task_base + ['type' => Task::MAIL_TYPE, 'name' => 'Mail']);
        $this->createItem(MailTask::class, [
            'plugin_metademands_tasks_id' => $mail->getID(),
            'content'                     => "<p>#$u.firstname#</p>",
            'users_id_recipient'          => 0,
            'groups_id_recipient'         => 0,
            'email'                       => '',
            'itilcategories_id'           => 0,
        ]);

        $location = $this->createItem(Location::class, ['name' => 'Field links location ' . mt_rand(), 'entities_id' => $entities_id]);
        $group = $this->createItem(Group::class, ['name' => 'Field links group ' . mt_rand(), 'entities_id' => $entities_id, 'is_recursive' => 1]);
        $ticketfield_base = ['plugin_metademands_metademands_id' => $meta_id, 'entities_id' => $entities_id, 'is_deletable' => 1];
        $this->createItem(TicketField::class, $ticketfield_base + ['num' => 1, 'value' => "Request of #$u.name# - #$t#"]);
        $this->createItem(TicketField::class, $ticketfield_base + ['num' => $this->getTicketOption('glpi_locations'), 'value' => $location->getID()]);
        $this->createItem(TicketField::class, $ticketfield_base + ['num' => $this->getTicketOption('glpi_groups'), 'value' => $group->getID()]);

        return [
            'metademand' => $meta_id,
            'user'       => $u,
            'text'       => $t,
            'checkbox'   => $checkbox->getID(),
            'radio'      => $radio->getID(),
            'location'   => $location->getID(),
            'group'      => $group->getID(),
        ];
    }

    /**
     * Check the links of a metademand made from the source one (copy or import).
     *
     * @param array<string, int> $source
     * @param bool               $same_glpi  the GLPI objects are the same ones (copy)
     * @param int[]              $task_types the types of children made (a copy keeps those of
     *                                       the mode of the metademand only, as before)
     */
    private function checkTarget(int $meta_id, array $source, bool $same_glpi, array $task_types): void
    {
        $this->assertGreaterThan(0, $meta_id);
        $this->assertNotSame($source['metademand'], $meta_id);

        $ids = [];
        foreach ((new Field())->find(['plugin_metademands_metademands_id' => $meta_id]) as $id => $row) {
            $ids[$row['name']] = (int) $id;
        }
        $this->assertCount(4, $ids);
        foreach (['User' => 'user', 'Text' => 'text', 'Checkbox' => 'checkbox', 'Radio' => 'radio'] as $name => $key) {
            $this->assertNotSame($source[$key], $ids[$name]);
        }

        // "Link this field to a user field"
        $parameter = new FieldParameter();
        $this->assertTrue($parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $ids['Text']]));
        $this->assertSame($ids['User'], (int) $parameter->fields['link_to_user']);

        // The option of the radio: its own custom value, the checkbox field and its custom value
        $radio_value = array_key_first((new FieldCustomvalue())->find(['plugin_metademands_fields_id' => $ids['Radio']]));
        $checkbox_value = array_key_first((new FieldCustomvalue())->find(['plugin_metademands_fields_id' => $ids['Checkbox'], 'name' => 'C2']));
        $option = new FieldOption();
        $this->assertTrue($option->getFromDBByCrit(['plugin_metademands_fields_id' => $ids['Radio']]));
        $this->assertSame($radio_value, (int) $option->fields['check_value']);
        $this->assertSame($ids['Checkbox'], (int) $option->fields['checkbox_id']);
        $this->assertSame($checkbox_value, (int) $option->fields['checkbox_value']);

        // The condition of the radio: its own custom value
        $condition = new Condition();
        $this->assertTrue($condition->getFromDBByCrit(['plugin_metademands_fields_id' => $ids['Radio']]));
        $this->assertSame($radio_value, (int) $condition->fields['check_value']);

        // Tags of the children
        $u = $ids['User'];
        $t = $ids['Text'];
        $tasks = [];
        foreach ((new Task())->find(['plugin_metademands_metademands_id' => $meta_id]) as $row) {
            $tasks[(int) $row['type']] = $row;
        }
        $made_types = array_keys($tasks);
        sort($made_types);
        sort($task_types);
        $this->assertSame($task_types, $made_types);
        $tickettask = new TicketTask();
        if (in_array(Task::TICKET_TYPE, $task_types, true)) {
            $this->assertSame("Child #$t#", $tasks[Task::TICKET_TYPE]['name']);
            $this->assertTrue($tickettask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks[Task::TICKET_TYPE]['id']]));
            $this->assertSame("<p>#$u.login#</p>", $tickettask->fields['content']);
        }
        if (in_array(Task::TASK_TYPE, $task_types, true)) {
            // Its content used to be neither exported nor copied
            $this->assertTrue($tickettask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks[Task::TASK_TYPE]['id']]));
            $this->assertSame("<p>Check #$t#</p>", $tickettask->fields['content']);
        }
        $mailtask = new MailTask();
        $this->assertTrue($mailtask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks[Task::MAIL_TYPE]['id']]));
        $this->assertSame("<p>#$u.firstname#</p>", $mailtask->fields['content']);

        // Fields of the ticket: the tags of the title, the location found again
        $values = [];
        foreach ((new TicketField())->find(['plugin_metademands_metademands_id' => $meta_id]) as $row) {
            $values[(int) $row['num']] = $row['value'];
        }
        $this->assertSame("Request of #$u.name# - #$t#", $values[1]);
        $this->assertSame((string) $source['location'], (string) $values[$this->getTicketOption('glpi_locations')]);
        if ($same_glpi) {
            $this->assertSame((string) $source['group'], (string) $values[$this->getTicketOption('glpi_groups')]);
        }
    }

    /**
     * @return iterable<string, array{int, int[]}>
     */
    public static function modeProvider(): iterable
    {
        yield 'child tickets' => [0, [Task::TICKET_TYPE, Task::MAIL_TYPE]];
        yield 'tasks of the ticket' => [1, [Task::TASK_TYPE, Task::MAIL_TYPE]];
    }

    /**
     * @param int[] $task_types
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('modeProvider')]
    public function testDuplicateKeepsTheLinksOnTheCopy(int $force_create_tasks, array $task_types): void
    {
        $this->login();
        $source = $this->createSource($force_create_tasks);
        $name = Metademand::getById($source['metademand'])->fields['name'];

        $this->assertTrue((new Metademand())->executeDuplicate(['metademands_id' => $source['metademand']]));
        $copies = (new Metademand())->find(['name' => $name, 'NOT' => ['id' => $source['metademand']]]);
        $this->assertCount(1, $copies);

        $this->checkTarget((int) array_key_first($copies), $source, true, $task_types);
    }

    public function testImportKeepsTheLinksAndFindsTheObjectsByName(): void
    {
        $this->login();
        $source = $this->createSource();

        // The "display after" field of the text is the user field
        $field = new Field();

        $target = Export::exportAsXMLForMetademands($source['metademand']);
        $this->assertNotEmpty($target);
        $xml = (string) file_get_contents($target['path']);
        @unlink($target['path']);
        // The values pointing at objects carry their names
        $this->assertStringContainsString('<value_itemtype>Location</value_itemtype>', $xml);

        // On the target GLPI, the group does not exist
        $group = new Group();
        $this->assertTrue($group->getFromDB($source['group']));
        $group_name = $group->fields['name'];
        $this->assertTrue($group->delete(['id' => $source['group']], true));

        $file = GLPI_TMP_DIR . '/md_export_field_links.xml';
        file_put_contents($file, $xml);
        $import = new \ReflectionMethod(Export::class, 'importXmlFile');
        $new_id = (int) $import->invoke(null, $file);
        @unlink($file);

        // The import keeps every child, whatever the mode
        $this->checkTarget($new_id, $source, false, [Task::TICKET_TYPE, Task::TASK_TYPE, Task::MAIL_TYPE]);

        // "Display field after"
        $this->assertTrue($field->getFromDBByCrit(['plugin_metademands_metademands_id' => $new_id, 'name' => 'Text']));
        $user_field = new Field();
        $this->assertTrue($user_field->getFromDBByCrit(['plugin_metademands_metademands_id' => $new_id, 'name' => 'User']));
        $this->assertSame($user_field->getID(), (int) $field->fields['plugin_metademands_fields_id']);

        // The field of the missing group is left out rather than pointing at another group,
        // and the user is told so
        $this->assertCount(0, (new TicketField())->find([
            'plugin_metademands_metademands_id' => $new_id,
            'num'                               => $this->getTicketOption('glpi_groups'),
        ]));
        $this->hasSessionMessages(WARNING, [
            sprintf(
                __('Fields of the ticket not imported, no item of that name here: %s', 'metademands'),
                // The names of the file are escaped: the messages are displayed as HTML
                htmlescape(Group::getTypeName(1) . ' "' . $group_name . '"'),
            ),
        ]);
    }
}
