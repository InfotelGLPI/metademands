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
use GlpiPlugin\Metademands\TicketTask;
use Ticket;
use Ticket_Ticket;

/**
 * Non regression tests for the blocks reported on the son tickets.
 *
 * A ticket task can be configured with a subset of the form blocks (useBlock +
 * block_use). Each son ticket must receive exactly the blocks configured on its
 * own task, whatever the other tasks of the same metademand are configured with.
 */
class ChildTicketBlocksTest extends DbTestCase
{
    /** Marker prefixed to the field names so they can be located in the son contents. */
    private const MARKER = 'BLOCKMARKER';

    public function setUp(): void
    {
        parent::setUp();

        // Reset Config singleton so each test reloads config from DB
        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), [
                'id'                    => 1,
                'create_pdf'            => 0,
                'show_form_changes'     => 0,
                'childs_parent_content' => 1,
            ]);
        } else {
            // The parent content must be reported on the sons for these tests
            $DB->update(Config::getTable(), ['childs_parent_content' => 1], ['id' => 1]);
        }
    }

    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'                 => 'Test blocs tickets enfants',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ]);
    }

    /**
     * Create one text field per block, each one carrying a distinctive marker
     * so the blocks can be identified in the generated contents.
     *
     * @param int[] $ranks
     * @return array<int, int> rank => field id
     */
    private function createBlocks(int $metademands_id, array $ranks): array
    {
        $fields = [];
        foreach ($ranks as $rank) {
            $field = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademands_id,
                'type'        => 'text',
                'name'        => self::MARKER . $rank,
                'rank'        => $rank,
                'order'       => 1,
                'entities_id' => 0,
            ]);
            $fields[$rank] = $field->getID();
        }

        return $fields;
    }

    private function createTicketTask(
        int $metademands_id,
        string $name,
        int $useBlock,
        string $block_use,
        int $formatastable = 1,
    ): Task {
        $task = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'name'          => $name,
            'type'          => Task::TICKET_TYPE,
            'level'         => 1,
            'entities_id'   => 0,
            'formatastable' => $formatastable,
            'useBlock'      => $useBlock,
            'block_use'     => $block_use,
        ]);

        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $task->getID(),
            'content'     => 'Contenu de ' . $name,
            'entities_id' => 0,
        ]);

        return $task;
    }

    /**
     * @param array<int, int> $fields rank => field id
     */
    private function buildValues(array $fields): array
    {
        $values = ['fields' => []];
        foreach ($fields as $rank => $field_id) {
            $values['fields'][$field_id] = 'Valeur du bloc ' . $rank;
        }

        return $values;
    }

    /**
     * Blocks actually reported on each son ticket, indexed by the name of the
     * task that produced it.
     *
     * @param string[] $task_names
     * @param int[]    $ranks      blocks to look for
     * @return array<string, int[]> task name => reported block ranks
     */
    private function getBlocksPerSon(int $parent_tickets_id, array $task_names, array $ranks): array
    {
        $links = getAllDataFromTable(
            Ticket_Ticket::getTable(),
            [
                'tickets_id_2' => $parent_tickets_id,
                'link'         => Ticket_Ticket::SON_OF,
            ],
        );

        $blocks = [];
        foreach ($links as $link) {
            $son = new Ticket();
            $this->assertTrue($son->getFromDB($link['tickets_id_1']));

            $content = strip_tags(
                html_entity_decode($son->fields['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            );

            foreach ($task_names as $task_name) {
                if (!str_contains($son->fields['name'], $task_name)) {
                    continue;
                }
                $blocks[$task_name] = [];
                foreach ($ranks as $rank) {
                    if (str_contains($content, self::MARKER . $rank)) {
                        $blocks[$task_name][] = $rank;
                    }
                }
            }
        }

        return $blocks;
    }

    /**
     * A form as returned by constructMetademands() : fields indexed by id,
     * each one carrying the rank of its block.
     */
    private function sampleForm(): array
    {
        return [
            11 => ['rank' => 1, 'name' => self::MARKER . 1],
            12 => ['rank' => 2, 'name' => self::MARKER . 2],
            13 => ['rank' => 3, 'name' => self::MARKER . 3],
        ];
    }

    /**
     * Root cause of the bug : the form is shared by every task of the metademand.
     * Filtering it must never modify the caller copy, otherwise the tasks
     * processed afterwards only get the intersection of the previous selections.
     */
    public function testFilterFormOnBlocksDoesNotAlterTheSharedForm(): void
    {
        $form      = $this->sampleForm();
        $untouched = $form;

        Metademand::filterFormOnBlocks($form, ['1']);

        $this->assertSame(
            $untouched,
            $form,
            'Le formulaire partage ne doit pas etre modifie par le filtrage',
        );
    }

    /**
     * Filtering the same form for successive tasks must give each task its own
     * blocks, whatever the order they are processed in.
     */
    public function testFilterFormOnBlocksIsIndependentBetweenTasks(): void
    {
        $form = $this->sampleForm();

        $first  = Metademand::filterFormOnBlocks($form, ['1']);
        $second = Metademand::filterFormOnBlocks($form, ['2', '3']);
        $third  = Metademand::filterFormOnBlocks($form, ['1', '2', '3']);

        $this->assertSame([11], array_keys($first), 'La premiere tache ne garde que le bloc 1');
        $this->assertSame(
            [12, 13],
            array_keys($second),
            'La deuxieme tache doit garder ses blocs 2 et 3, pas l intersection avec la precedente',
        );
        $this->assertSame(
            [11, 12, 13],
            array_keys($third),
            'Une tache selectionnant tous les blocs doit tous les recevoir',
        );
    }

    /**
     * An empty selection means "no block filtering" : the whole form is kept.
     */
    public function testFilterFormOnBlocksKeepsWholeFormWhenNoBlockSelected(): void
    {
        $form = $this->sampleForm();

        $this->assertSame($form, Metademand::filterFormOnBlocks($form, []));
        $this->assertSame($form, Metademand::filterFormOnBlocks($form, null));
    }

    /**
     * Two tasks configured with disjoint blocks : each son must receive its own
     * blocks. Before the fix the filtering was applied in place on the shared
     * form, so the second task ended up with the intersection of both selections
     * (i.e. nothing).
     */
    public function testEachSonTicketGetsItsOwnConfiguredBlocks(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $fields     = $this->createBlocks($metademand->getID(), [1, 2, 3]);

        $this->createTicketTask($metademand->getID(), 'TASKONE', 1, '["1"]');
        $this->createTicketTask($metademand->getID(), 'TASKTWO', 1, '["2","3"]');

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($fields));
        $this->assertGreaterThan(0, $result['id']);

        $blocks = $this->getBlocksPerSon($result['id'], ['TASKONE', 'TASKTWO'], [1, 2, 3]);

        $this->assertArrayHasKey('TASKONE', $blocks, 'Le ticket enfant de TASKONE doit exister');
        $this->assertArrayHasKey('TASKTWO', $blocks, 'Le ticket enfant de TASKTWO doit exister');

        $this->assertSame(
            [1],
            $blocks['TASKONE'],
            'TASKONE ne doit recevoir que le bloc 1',
        );
        $this->assertSame(
            [2, 3],
            $blocks['TASKTWO'],
            'TASKTWO doit recevoir tous ses blocs configures (2 et 3)',
        );
    }

    /**
     * A task selecting every block must get every block, even when it is
     * processed after a task restricted to a single one.
     */
    public function testSonTicketWithAllBlocksSelectedGetsAllBlocks(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $fields     = $this->createBlocks($metademand->getID(), [1, 2, 3]);

        $this->createTicketTask($metademand->getID(), 'TASKONE', 1, '["1"]');
        $this->createTicketTask($metademand->getID(), 'TASKALL', 1, '["1","2","3"]');

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($fields));
        $this->assertGreaterThan(0, $result['id']);

        $blocks = $this->getBlocksPerSon($result['id'], ['TASKONE', 'TASKALL'], [1, 2, 3]);

        $this->assertArrayHasKey('TASKALL', $blocks, 'Le ticket enfant de TASKALL doit exister');
        $this->assertSame(
            [1, 2, 3],
            $blocks['TASKALL'],
            'TASKALL doit recevoir les trois blocs configures',
        );
    }

    /**
     * A task that does not use the block selection must not inherit the content
     * computed for the previous task.
     */
    public function testSonTicketWithoutBlockSelectionDoesNotInheritPreviousTask(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $fields     = $this->createBlocks($metademand->getID(), [1, 2]);

        $this->createTicketTask($metademand->getID(), 'TASKONE', 1, '["1"]');
        $this->createTicketTask($metademand->getID(), 'TASKNOBLOCK', 0, '[]');

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($fields));
        $this->assertGreaterThan(0, $result['id']);

        $blocks = $this->getBlocksPerSon($result['id'], ['TASKONE', 'TASKNOBLOCK'], [1, 2]);

        $this->assertArrayHasKey('TASKNOBLOCK', $blocks, 'Le ticket enfant de TASKNOBLOCK doit exister');
        $this->assertSame(
            [],
            $blocks['TASKNOBLOCK'],
            'Une tache sans selection de blocs ne doit pas heriter du contenu de la tache precedente',
        );
    }

    /**
     * formatastable only drives the presentation : the configured blocks must be
     * reported on the son ticket in both cases.
     */
    public function testConfiguredBlocksAreReportedWhenFormatAsTableIsDisabled(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $fields     = $this->createBlocks($metademand->getID(), [1, 2]);

        $this->createTicketTask($metademand->getID(), 'TASKPLAIN', 1, '["1","2"]', 0);

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($fields));
        $this->assertGreaterThan(0, $result['id']);

        $blocks = $this->getBlocksPerSon($result['id'], ['TASKPLAIN'], [1, 2]);

        $this->assertArrayHasKey('TASKPLAIN', $blocks, 'Le ticket enfant de TASKPLAIN doit exister');
        $this->assertSame(
            [1, 2],
            $blocks['TASKPLAIN'],
            'Les blocs configures doivent etre reportes meme sans mise en forme en tableau',
        );
    }
}
