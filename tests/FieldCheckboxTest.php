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
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\Fields\Checkbox;

/**
 * Checkbox field of the wizard (fields/field_checkbox.html.twig): the checked and
 * required states come as booleans, the option comment as sanitized HTML shown in a
 * Bootstrap tooltip or, in the block display, under the option.
 */
class FieldCheckboxTest extends DbTestCase
{
    private const FIELD_ID = 987654;

    private const COMMENT = '<b>Help</b><script>alert(1)</script>';

    /**
     * @param array<string, mixed> $data
     */
    private function render(array $data, mixed $value, bool $on_order = false): string
    {
        $data += [
            'id'           => self::FIELD_ID,
            'comment'      => '',
            'icon'         => '',
            'row_display'  => 0,
            'display_type' => 0,
            'is_mandatory' => 0,
        ];

        ob_start();
        Checkbox::showWizardField($data, 'field', $value, $on_order);

        return (string) ob_get_clean();
    }

    /**
     * Custom values as the wizard reads them: rows indexed by their id.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int}
     */
    private function createCustomValues(): array
    {
        $values = [];
        foreach ([
            ['rank' => 0, 'name' => 'First <i>one</i>', 'comment' => self::COMMENT, 'is_default' => 1],
            ['rank' => 1, 'name' => 'Second', 'comment' => '', 'is_default' => 0],
        ] as $input) {
            $value = $this->createItem(
                FieldCustomvalue::class,
                $input + ['plugin_metademands_fields_id' => self::FIELD_ID],
                ['comment', 'name'],
            );
            $values[$value->getID()] = $value->fields;
        }
        [$first, $second] = array_keys($values);

        return [$values, $first, $second];
    }

    private function assertOption(int $key, bool $checked, string $html): void
    {
        $this->assertMatchesRegularExpression(
            "/id='field\[" . self::FIELD_ID . "\]\[$key\]'\s+value='$key'" . ($checked ? ' checked' : '') . '>/',
            $html,
        );
    }

    public function testSimpleCheckboxIsCheckedFromItsValue(): void
    {
        $this->login();

        $this->assertStringContainsString("value='checkbox' checked>", $this->render([], 1));
        $this->assertStringContainsString("value='checkbox'>", $this->render([], 0));
    }

    public function testClassicDisplayCarriesStatesAndATooltip(): void
    {
        $this->login();

        [$values, $first, $second] = $this->createCustomValues();
        // Markup in the label as the wizard may receive it (the input of an add strips it)
        $values[$first]['name'] = 'First <i>one</i>';
        $html = $this->render(['custom_values' => $values, 'is_mandatory' => 1], [$second => $second], true);

        // Checked from the value, not from the default while ordering; every option required
        $this->assertOption($first, false, $html);
        $this->assertOption($second, true, $html);
        $this->assertSame(2, substr_count($html, '<input required '));

        // Escaped name, sanitized comment escaped into the Bootstrap tooltip
        $this->assertStringContainsString('First &lt;i&gt;one&lt;/i&gt;', $html);
        $this->assertStringContainsString('class="form-help" data-bs-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-bs-title="&lt;b&gt;Help&lt;/b&gt;"', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('qtip', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testBlockDisplayShowsTheDefaultAndTheSanitizedComment(): void
    {
        $this->login();

        [$values, $first] = $this->createCustomValues();
        $html = $this->render([
            'custom_values' => $values,
            'display_type'  => Checkbox::BLOCK_DISPLAY,
        ], null);

        $this->assertOption($first, true, $html);
        $this->assertStringNotContainsString('<input required', $html);
        $this->assertMatchesRegularExpression("/class='form-hint'><b>Help<\/b>\s*<\/small>/", $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }
}
