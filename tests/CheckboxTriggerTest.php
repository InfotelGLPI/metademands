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
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;

/**
 * The multiple dropdowns check their linked checkboxes through a data-md-checkbox-trigger
 * marker read by public/scripts/wizard_form.js, instead of one generated script per value.
 */
class CheckboxTriggerTest extends DbTestCase
{
    private function render(array $data): string
    {
        ob_start();
        FieldOption::checkboxScript($data + [
            'id'   => 42,
            'type' => 'dropdown_multiple',
        ]);

        return (string) ob_get_clean();
    }

    public function testPickedValuesCheckTheirLinkedCheckboxes(): void
    {
        $this->login();

        $html = $this->render([
            'display_type' => Dropdownmultiple::CLASSIC_DISPLAY,
            'options'      => [
                5 => ['checkbox_id' => 7, 'checkbox_value' => 3],
                6 => ['checkbox_id' => 0, 'checkbox_value' => 3],
            ],
        ]);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame(1, preg_match('/data-md-checkbox-trigger="([^"]*)"/', $html, $matches));
        $this->assertSame(
            [
                'source' => ['name' => 'field[42]', 'match' => 'prefix'],
                'rules'  => [['value' => '5', 'target' => 'field[7][3]']],
            ],
            json_decode(html_entity_decode($matches[1], ENT_QUOTES), true),
        );
    }

    public function testDoubleColumnNeedsALinkedField(): void
    {
        $this->login();

        $html = $this->render([
            'display_type' => Dropdownmultiple::DOUBLE_COLUMN_DISPLAY,
            'options'      => [
                5 => ['checkbox_id' => 7, 'checkbox_value' => 3, 'hidden_link' => [9]],
                6 => ['checkbox_id' => 8, 'checkbox_value' => 4, 'hidden_link' => []],
            ],
        ]);

        $this->assertSame(1, preg_match('/data-md-checkbox-trigger="([^"]*)"/', $html, $matches));
        $config = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertSame(['id' => 'multiselect42'], $config['source']);
        $this->assertSame([['value' => '5', 'target' => 'field[7][3]']], $config['rules']);
    }

    public function testNothingIsEmittedWithoutLinkedCheckbox(): void
    {
        $this->login();

        $this->assertSame('', $this->render(['display_type' => 0, 'options' => []]));
        $this->assertSame('', $this->render(['display_type' => 0, 'options' => [5 => ['checkbox_id' => 7]]]));
        $this->assertSame('', $this->render([
            'type'    => 'checkbox',
            'options' => [5 => ['checkbox_id' => 7, 'checkbox_value' => 3]],
        ]));
    }
}
