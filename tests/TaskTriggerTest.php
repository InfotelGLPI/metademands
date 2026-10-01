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
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;

/**
 * The text-like fields flag their child metademands through a data-md-task-trigger
 * marker read by public/scripts/wizard_form.js, instead of one generated script per field.
 */
class TaskTriggerTest extends DbTestCase
{
    private function render(string $class, array $data): string
    {
        ob_start();
        $class::taskScript($data + [
            'id'                                => 42,
            'plugin_metademands_metademands_id' => 1,
        ]);

        return (string) ob_get_clean();
    }

    public function testFieldWithTasksEmitsTheMarkerWithoutScript(): void
    {
        $this->login();

        $html = $this->render(Text::class, [
            'options' => [1 => ['plugin_metademands_tasks_id' => [5]]],
        ]);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('data-md-task-trigger="', $html);
        $this->assertStringContainsString(
            '&quot;source&quot;:{&quot;name&quot;:&quot;field[42]&quot;,&quot;match&quot;:&quot;prefix&quot;}',
            $html,
        );
        $this->assertStringContainsString('&quot;mode&quot;:&quot;filled&quot;', $html);
        $this->assertStringContainsString('&quot;tasks_id&quot;:5', $html);
        $this->assertStringContainsString('ajax\/set_session.php', $html);
    }

    public function testChoiceFieldsDescribeTheirRules(): void
    {
        $this->login();

        $html = $this->render(Dropdown::class, [
            'options' => [
                3 => ['plugin_metademands_tasks_id' => [5], 'check_type_value' => 1],
                0 => ['plugin_metademands_tasks_id' => [6], 'check_type_value' => 1],
            ],
            'value'   => 3,
            'default' => '',
        ]);
        $this->assertStringContainsString('&quot;match&quot;:&quot;exact&quot;', $html);
        $this->assertStringContainsString('&quot;mode&quot;:&quot;select&quot;', $html);
        $this->assertStringContainsString('{&quot;tasks_id&quot;:6,&quot;value&quot;:&quot;0&quot;,&quot;any&quot;:true', $html);
        $this->assertStringContainsString('&quot;restore&quot;:{&quot;val&quot;:&quot;3&quot;}', $html);

        $html = $this->render(Checkbox::class, [
            'options'       => [2 => ['plugin_metademands_tasks_id' => [5]]],
            'value'         => [2],
            'custom_values' => [],
        ]);
        $this->assertStringContainsString('&quot;mode&quot;:&quot;checked&quot;', $html);
        $this->assertStringContainsString('&quot;restore&quot;:{&quot;check&quot;:[&quot;2&quot;]}', $html);
    }

    public function testNothingIsEmittedWithoutOptionsOrForRichText(): void
    {
        $this->login();

        $this->assertSame('', $this->render(Text::class, []));
        // A value without task
        $this->assertSame('', $this->render(Text::class, ['options' => [1 => ['plugin_metademands_tasks_id' => [0]]]]));
        $this->assertSame('', $this->render(Textarea::class, [
            'options'      => [1 => ['plugin_metademands_tasks_id' => [5]]],
            'use_richtext' => 1,
        ]));
    }
}
