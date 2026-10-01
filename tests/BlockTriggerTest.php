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
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Checkbox;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Metademand;

/**
 * The fields show or hide their linked blocks through a data-md-block-trigger
 * marker read by public/scripts/wizard_form.js, instead of one generated script per field.
 */
class BlockTriggerTest extends DbTestCase
{
    private function render(string $class, array $data, int $metademand_id = 0): string
    {
        ob_start();
        $class::blocksHiddenScript($data + [
            'id'                                => 42,
            'plugin_metademands_metademands_id' => $metademand_id,
        ]);

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $html): array
    {
        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame(1, preg_match('/data-md-block-trigger="([^"]*)"/', $html, $matches));

        return json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
    }

    private function createField(int $metademand_id, string $type, int $rank, bool $mandatory): Field
    {
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand_id,
            'type'                              => $type,
            'item'                              => '',
            'name'                              => $this->getUniqueString(),
            'rank'                              => $rank,
            'order'                             => 1,
            'entities_id'                       => 0,
        ]);
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'is_mandatory'                 => (int) $mandatory,
        ]);

        return $field;
    }

    public function testTextFieldsDescribeTheirBlocks(): void
    {
        $this->login();

        $metademand = $this->createItem(Metademand::class, [
            'name'              => $this->getUniqueString(),
            'entities_id'       => 0,
            'object_to_create'  => 'Ticket',
            'type'              => 0,
            'step_by_step_mode' => 1,
        ]);
        $required = $this->createField($metademand->getID(), 'text', 9001, true);
        $this->createField($metademand->getID(), 'text', 9001, false);
        $upload = $this->createField($metademand->getID(), 'upload', 9003, true);
        // Block 9004 is controlled by another field: not opened with its parent value
        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $required->getID(),
            'check_value'                  => 1,
            'hidden_block'                 => 9004,
        ]);

        $config = $this->config($this->render(Text::class, [
            'options' => [
                1 => ['hidden_block' => [9001, 0], 'childs_blocks' => '[[9003,9004],[9003]]'],
                0 => ['hidden_block' => [9002]],
            ],
            'value'   => 'kept',
        ], $metademand->getID()));

        $this->assertSame(['name' => 'field[42]', 'match' => 'prefix'], $config['source']);
        $this->assertSame('filled', $config['mode']);
        // Value 1: blocks shown when filled, value 0: when empty
        $this->assertSame([9001], $config['rules'][0]['blocks']);
        $this->assertFalse($config['rules'][0]['negate']);
        $this->assertSame([9003, 9004], $config['rules'][0]['childs']);
        $this->assertSame([9003], $config['rules'][0]['open']);
        $this->assertSame([9002], $config['rules'][1]['blocks']);
        $this->assertTrue($config['rules'][1]['negate']);
        $this->assertSame([], $config['rules'][1]['childs']);
        $this->assertEqualsCanonicalizing([
            ['id' => $required->getID(), 'block' => 9001, 'upload' => false],
            ['id' => $upload->getID(), 'block' => 9003, 'upload' => true],
        ], $config['mandatory']);
        $this->assertTrue($config['step']);
        // A value is kept: the blocks are not emptied when the form is displayed
        $this->assertFalse($config['empty']);
        $this->assertSame(['val' => 'kept'], $config['restore']);
    }

    public function testChoiceFieldsDescribeTheirRules(): void
    {
        $this->login();

        $config = $this->config($this->render(Dropdown::class, [
            'item'    => 'Location',
            'options' => [
                3  => ['hidden_block' => [9001], 'check_type_value' => 1],
                -1 => ['hidden_block' => [9002], 'check_type_value' => 1],
                0  => ['hidden_block' => [9003], 'check_type_value' => 1],
            ],
        ]));
        $this->assertSame(['name' => 'field[42]', 'match' => 'exact'], $config['source']);
        $this->assertSame('select', $config['mode']);
        $this->assertFalse($config['rules'][0]['any']);
        $this->assertTrue($config['rules'][1]['any']);
        // Value 0 means any value for the blocks, not "nothing selected"
        $this->assertTrue($config['rules'][2]['any']);
        $this->assertFalse($config['rules'][2]['negate']);
        $this->assertSame([], $config['mandatory']);
        $this->assertFalse($config['step']);
        $this->assertTrue($config['empty']);
        $this->assertArrayNotHasKey('restore', $config);

        $config = $this->config($this->render(Checkbox::class, [
            'options' => [2 => ['hidden_block' => [9001]]],
            'value'   => [2],
        ]));
        $this->assertSame('checked', $config['mode']);
        $this->assertSame(['check' => ['2']], $config['restore']);
    }

    public function testNothingIsEmittedWithoutBlocksOrForRichText(): void
    {
        $this->login();

        $this->assertSame('', $this->render(Text::class, []));
        $this->assertSame('', $this->render(Text::class, ['options' => [1 => ['hidden_block' => [0]]]]));
        $this->assertSame('', $this->render(Textarea::class, [
            'options'      => [1 => ['hidden_block' => [9001]]],
            'use_richtext' => 1,
        ]));
    }
}
