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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\MailTask;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use User;

/**
 * Child object form of a metademand (task_form.html.twig): the object chosen as a card, the
 * unavailable ones greyed with the reason, then the settings beside the tags of the metademand
 */
class ChildObjectFormTest extends DbTestCase
{
    private function createMetademand(int $force_create_tasks = 0): Metademand
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Task::$rightname]       = ALLSTANDARDRIGHT;
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;

        $metademand = $this->createItem(Metademand::class, [
            'name'               => 'Children',
            'entities_id'        => $this->getTestRootEntity(true),
            'object_to_create'   => 'Ticket',
            'type'               => 0,
            'maintenance_mode'   => 1,
            'force_create_tasks' => $force_create_tasks,
        ]);
        $field = fn(string $name, string $type, int $rank, int $order, string $item = '') => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => $type,
            'item'                              => $item,
            'name'                              => $name,
            'rank'                              => $rank,
            'order'                             => $order,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['item']);
        $field('Accident', 'title-block', 2, 1);
        $field('Place', 'text', 2, 2);
        $field('Manager', 'dropdown_object', 2, 3, User::class);
        $field('Section', 'title', 3, 1);
        $field('Comment', 'textarea', 3, 2);

        return $metademand;
    }

    /**
     * @return array<int, array<string, mixed>> cards by type
     */
    private function cards(Metademand $metademand): array
    {
        return array_column(Task::getChildObjectCards($metademand), null, 'type');
    }

    private function render(Metademand $metademand, int $id = -1): string
    {
        ob_start();
        (new Task())->showForm($id, ['parent' => $metademand]);

        return (string) ob_get_clean();
    }

    public function testCardsGreyTheObjectsTheMetademandCannotCreate(): void
    {
        $metademand = $this->createMetademand();
        $cards = $this->cards($metademand);

        $this->assertSame('', $cards[Task::TICKET_TYPE]['reason']);
        $this->assertSame('', $cards[Task::MAIL_TYPE]['reason']);
        $this->assertSame('', $cards[Task::METADEMAND_TYPE]['reason']);
        $this->assertStringContainsString('Create tasks (not child tickets)', $cards[Task::TASK_TYPE]['reason']);
        $this->assertFalse($cards[Task::METADEMAND_TYPE]['tags']);

        // Only one child metademand
        $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'name'                              => 'Next',
            'type'                              => Task::METADEMAND_TYPE,
            'level'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);
        $this->assertStringContainsString('Only one child metademand', $this->cards($metademand)[Task::METADEMAND_TYPE]['reason']);

        // A metademand creating tasks: no child ticket
        $tasks = $this->cards($this->createMetademand(1));
        $this->assertSame('', $tasks[Task::TASK_TYPE]['reason']);
        $this->assertStringContainsString('Not available with', $tasks[Task::TICKET_TYPE]['reason']);
    }

    public function testTagsAreGroupedByBlock(): void
    {
        $metademand = $this->createMetademand();
        $fields = array_column((new Field())->find(['plugin_metademands_metademands_id' => $metademand->getID()]), 'id', 'name');

        $groups = Task::getTagGroups($metademand->getID());
        $this->assertSame(['Requester and entity', 'Block 2 · Accident', 'Block 3'], array_column($groups, 'title'));
        $this->assertSame('requester.login', $groups[0]['tags'][0]['tag']);
        $this->assertSame('entity', $groups[0]['tags'][4]['tag']);

        // The block title and the title give no value; a user field has its attributes too
        $block2 = array_column($groups[1]['tags'], 'tag');
        $this->assertSame([
            (string) $fields['Place'],
            (string) $fields['Manager'],
            $fields['Manager'] . '.login',
            $fields['Manager'] . '.name',
            $fields['Manager'] . '.firstname',
            $fields['Manager'] . '.email',
        ], $block2);
        $this->assertSame([(string) $fields['Comment']], array_column($groups[2]['tags'], 'tag'));

        // Every tag offered is known by the check of the child objects list
        $known = Task::getKnownTags($metademand->getID());
        foreach ($groups as $group) {
            foreach ($group['tags'] as $row) {
                $this->assertContains($row['tag'], $known);
            }
        }
    }

    public function testANewChildStartsWithTheCards(): void
    {
        $metademand = $this->createMetademand();
        $html = $this->render($metademand);

        $this->assertStringContainsString('md-child-picking', $html);
        $this->assertMatchesRegularExpression('/data-md-child-card="' . Task::TICKET_TYPE . '"/', $html);
        $this->assertMatchesRegularExpression('/data-md-child-card="' . Task::TASK_TYPE . '"[^>]*\sdisabled>/', $html);
        $this->assertMatchesRegularExpression('/name=["\x27]taskType["\x27]/', $html);
        // The settings wait for the object; the tags are beside them
        $this->assertMatchesRegularExpression('/<div data-md-child-settings hidden>/', $html);
        $this->assertStringContainsString('data-md-child-tags-panel', $html);
        $this->assertStringContainsString('data-md-tag="#requester.login#"', $html);
        $this->assertStringContainsString('data-md-known-tags="', $html);
        $this->assertStringContainsString('data-md-tag-target="name"', $html);
        $this->assertStringContainsString('data-md-tag-target="content"', $html);
        // No more header row of the legacy form
        $this->assertStringNotContainsString('id="show_add_task_form"></span>', $html);
    }

    public function testANewChildUsesEveryBlock(): void
    {
        $metademand = $this->createMetademand();

        foreach ([
            fn() => TicketTask::showTicketTaskForm($metademand->getID(), true, Task::TICKET_TYPE),
            fn() => MailTask::showMailTaskForm($metademand->getID(), Task::MAIL_TYPE),
        ] as $render) {
            ob_start();
            $render();
            $html = (string) ob_get_clean();
            // Every block, named by its title
            $this->assertMatchesRegularExpression('/<option value=["\x27]2["\x27] selected[^>]*>Block 2 · Accident<\/option>/', $html);
            $this->assertMatchesRegularExpression('/<option value=["\x27]3["\x27] selected[^>]*>Block 3<\/option>/', $html);
        }

        // A child keeps its own blocks
        ob_start();
        TicketTask::showTicketTaskForm($metademand->getID(), true, Task::TICKET_TYPE, ['block_use' => '[3]']);
        $html = (string) ob_get_clean();
        $this->assertMatchesRegularExpression('/<option value=["\x27]2["\x27]\s*>Block 2 · Accident<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option value=["\x27]3["\x27] selected/', $html);
    }

    public function testAnExistingChildOpensOnItsSettings(): void
    {
        $metademand = $this->createMetademand();
        $child = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'name'                              => 'Next',
            'type'                              => Task::METADEMAND_TYPE,
            'level'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);

        $html = $this->render($metademand, $child->getID());
        $this->assertStringNotContainsString('md-child-picking', $html);
        $this->assertStringNotContainsString('data-md-child-card', $html);
        $this->assertMatchesRegularExpression('/<div data-md-child-settings>/', $html);
        // A child metademand takes no tag
        $this->assertStringContainsString('md-child-layout md-child-no-tags', $html);
    }
}
