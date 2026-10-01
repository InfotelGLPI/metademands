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
use GlpiPlugin\Metademands\Fields\Checkbox;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Fields\Yesno;

/**
 * The fields show or hide their linked fields through a data-md-hidden-trigger
 * marker read by public/scripts/wizard_form.js, instead of one generated script per field.
 */
class HiddenTriggerTest extends DbTestCase
{
    private function render(string $class, array $data): string
    {
        ob_start();
        $class::fieldsHiddenScript($data + [
            'id'                                => 42,
            'plugin_metademands_metademands_id' => 1,
        ]);

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $html): array
    {
        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame(1, preg_match('/data-md-hidden-trigger="([^"]*)"/', $html, $matches));

        return json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
    }

    public function testTextFieldsDescribeWhenTheirLinksAreShown(): void
    {
        $this->login();

        $config = $this->config($this->render(Text::class, [
            'options' => [
                1 => ['hidden_link' => [7], 'childs_blocks' => '[[3,4],[5]]'],
                0 => ['hidden_link' => [8, 0]],
            ],
            'value'   => 'kept',
        ]));

        $this->assertSame(['name' => 'field[42]', 'match' => 'prefix'], $config['source']);
        $this->assertSame('filled', $config['mode']);
        // Value 1: when filled, value 0: when empty
        $this->assertSame([7], $config['rules'][0]['targets']);
        $this->assertFalse($config['rules'][0]['negate']);
        $this->assertSame([3, 4, 5], $config['rules'][0]['blocks']);
        $this->assertSame([8], $config['rules'][1]['targets']);
        $this->assertTrue($config['rules'][1]['negate']);
        $this->assertSame([], $config['rules'][1]['blocks']);
        $this->assertSame(['val' => 'kept'], $config['restore']);
    }

    public function testChoiceFieldsDescribeTheirRules(): void
    {
        $this->login();

        $config = $this->config($this->render(Dropdown::class, [
            'item'    => 'Location',
            'options' => [
                3  => ['hidden_link' => [7], 'check_type_value' => 1],
                -1 => ['hidden_link' => [8], 'check_type_value' => 1],
                0  => ['hidden_link' => [9], 'check_type_value' => 1],
            ],
            'value'   => 3,
        ]));
        $this->assertSame(['name' => 'field[42]', 'match' => 'exact'], $config['source']);
        $this->assertSame('select', $config['mode']);
        $this->assertFalse($config['rules'][0]['any']);
        $this->assertTrue($config['rules'][1]['any']);
        // Value 0: shown when nothing is selected
        $this->assertTrue($config['rules'][2]['negate']);
        $this->assertSame(['val' => '3'], $config['restore']);

        $config = $this->config($this->render(Checkbox::class, [
            'options' => [2 => ['hidden_link' => [7]]],
            'value'   => [2],
        ]));
        $this->assertSame('checked', $config['mode']);
        $this->assertSame(['check' => ['2']], $config['restore']);

        $config = $this->config($this->render(Yesno::class, [
            'display_type' => Yesno::SWITCH_DISPLAY,
            'options'      => [
                1 => ['hidden_link' => [7]],
                2 => ['hidden_link' => [8]],
            ],
        ]));
        $this->assertSame('switch', $config['mode']);
        // No: shown when the switch is off
        $this->assertTrue($config['rules'][0]['negate']);
        $this->assertFalse($config['rules'][1]['negate']);

        $config = $this->config($this->render(Dropdownmultiple::class, [
            'display_type' => Dropdownmultiple::DOUBLE_COLUMN_DISPLAY,
            'options'      => [5 => ['hidden_link' => [7]]],
            'value'        => [5],
        ]));
        $this->assertSame(['id' => 'multiselect42_to'], $config['source']);
        $this->assertSame('listed', $config['mode']);
    }

    public function testNothingIsEmittedWithoutLinksOrForRichText(): void
    {
        $this->login();

        $this->assertSame('', $this->render(Text::class, []));
        $this->assertSame('', $this->render(Text::class, ['options' => [1 => ['hidden_link' => [0]]]]));
        $this->assertSame('', $this->render(Textarea::class, [
            'options'      => [1 => ['hidden_link' => [7]]],
            'use_richtext' => 1,
        ]));
    }
}
