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
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;
use GlpiPlugin\Metademands\Stepform;
use GlpiPlugin\Metademands\Task;
use Ticket;

/**
 * A block takes another, free, number with its fields and everything naming it by its number
 * (Field::renumberBlock())
 */
class RenumberBlockTest extends DbTestCase
{
    /** @var array{metademand: Metademand, radio: int, option: int, task: int, step: int, stepform: int} */
    private array $form;

    public function setUp(): void
    {
        parent::setUp();

        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $this->form = $this->createForm();
    }

    private function addField(Metademand $metademand, string $type, string $name, int $rank, int $order): int
    {
        return $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => 0,
            'type'                              => $type,
            'item'                              => $type === 'radio' ? 'radio' : '',
            'name'                              => $name,
            'rank'                              => $rank,
            'order'                             => $order,
        ], ['item'])->getID();
    }

    private function createForm(): array
    {
        global $DB;

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Blocks ' . mt_rand(),
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => Ticket::DEMAND_TYPE,
        ]);
        $radio = $this->addField($metademand, 'radio', 'Choice', 1, 1);
        $this->addField($metademand, 'title-block', 'Second block', 2, 1);
        $this->addField($metademand, 'text', 'Text', 2, 2);
        $this->addField($metademand, 'text', 'Third', 3, 1);

        $option = $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $radio,
            'check_value'                  => 1,
            'hidden_block'                 => 2,
            'childs_blocks'                => '[[2,3]]',
        ], ['childs_blocks']);

        $DB->insert(Task::getTable(), [
            'name'                              => 'Child',
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_use'                         => '["1","2"]',
        ]);
        $task = (int) $DB->insertId();
        $DB->insert(Step::getTable(), ['plugin_metademands_metademands_id' => $metademand->getID(), 'block_id' => 2]);
        $step = (int) $DB->insertId();
        $DB->insert(Stepform::getTable(), [
            'name'                              => 'In progress',
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 2,
        ]);
        $stepform = (int) $DB->insertId();

        return [
            'metademand' => $metademand,
            'radio'      => $radio,
            'option'     => $option->getID(),
            'task'       => $task,
            'step'       => $step,
            'stepform'   => $stepform,
        ];
    }

    /**
     * @return array<string, int> rank by field name
     */
    private function ranks(): array
    {
        $ranks = [];
        foreach ((new Field())->find(['plugin_metademands_metademands_id' => $this->form['metademand']->getID()]) as $field) {
            $ranks[$field['name']] = (int) $field['rank'];
        }
        ksort($ranks);

        return $ranks;
    }

    public function testTheBlockAndWhatNamesItTakeTheNewNumber(): void
    {
        $this->assertTrue(Field::renumberBlock($this->form['metademand']->getID(), 2, 13));
        $this->hasSessionMessages(INFO, ['Block 2 is now block 13']);

        $this->assertSame(['Choice' => 1, 'Second block' => 13, 'Text' => 13, 'Third' => 3], $this->ranks());

        $option = new FieldOption();
        $option->getFromDB($this->form['option']);
        $this->assertSame(13, (int) $option->fields['hidden_block']);
        $this->assertSame([[13, 3]], json_decode($option->fields['childs_blocks'], true));

        $rows = static fn(string $table, int $id): array => getAllDataFromTable($table, ['id' => $id])[$id];
        $this->assertSame(['1', '13'], json_decode($rows(Task::getTable(), $this->form['task'])['block_use'], true));
        $this->assertSame(13, (int) $rows(Step::getTable(), $this->form['step'])['block_id']);
        $this->assertSame(13, (int) $rows(Stepform::getTable(), $this->form['stepform'])['block_id']);
    }

    public function testOnlyAFreeNumberIsAccepted(): void
    {
        $metademands_id = $this->form['metademand']->getID();

        $this->assertFalse(Field::renumberBlock($metademands_id, 2, 3));
        $this->hasSessionMessages(ERROR, ['Block 3 already has fields: choose a free block number']);
        $this->assertFalse(Field::renumberBlock($metademands_id, 2, 0));
        $this->hasSessionMessages(ERROR, ['The number of a block goes from 1 to 40']);
        $this->assertFalse(Field::renumberBlock($metademands_id, 2, 41));
        $this->hasSessionMessages(ERROR, ['The number of a block goes from 1 to 40']);
        $this->assertFalse(Field::renumberBlock($metademands_id, 7, 8));
        $this->hasSessionMessages(ERROR, ['Block 7 has no field']);

        // The block of the form information keeps its number
        $this->assertGreaterThan(0, (new Field())->add([
            'plugin_metademands_metademands_id' => $metademands_id,
            'entities_id'                       => 0,
            'type'                              => 'forminformation',
            'item'                              => 'forminformation',
            'name'                              => 'Information',
        ]));
        $this->assertFalse(Field::renumberBlock($metademands_id, 0, 9));
        $this->hasSessionMessages(ERROR, ['The block of the form information keeps its number']);

        // Nothing moved
        $this->assertSame(['Choice' => 1, 'Information' => 0, 'Second block' => 2, 'Text' => 2, 'Third' => 3], $this->ranks());
        $option = new FieldOption();
        $option->getFromDB($this->form['option']);
        $this->assertSame(2, (int) $option->fields['hidden_block']);
    }

    public function testEachBlockOffersToChangeItsNumber(): void
    {
        (new Field())->add([
            'plugin_metademands_metademands_id' => $this->form['metademand']->getID(),
            'entities_id'                       => 0,
            'type'                              => 'forminformation',
            'item'                              => 'forminformation',
            'name'                              => 'Information',
        ]);

        ob_start();
        Field::displayTabContentForItem($this->form['metademand']);
        $html = (string) ob_get_clean();

        foreach ([1, 2, 3] as $block) {
            $this->assertStringContainsString('name="new_rank[' . $block . ']"', $html);
            $this->assertMatchesRegularExpression(
                '/<button type="submit" class="btn btn-primary" form="md-renumber-block-\d+"\s+name="rank" value="' . $block . '">/',
                $html,
            );
        }
        // Not the block of the form information
        $this->assertStringNotContainsString('name="new_rank[0]"', $html);
        $this->assertMatchesRegularExpression('/<form id="md-renumber-block-\d+" method="post"[^>]*>\s*<input type="hidden" name="plugin_metademands_metademands_id"/', $html);
        $this->assertStringContainsString('<input type="hidden" name="renumber_block" value="1">', $html);
    }
}
