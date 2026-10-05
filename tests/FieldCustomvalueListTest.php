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
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;
use GlpiPlugin\Metademands\Fields\Radio;

/**
 * Custom values of a field (field_customvalue_list.html.twig): the values come back as
 * data escaped by Twig, the widgets from the core macros, and the add, import and
 * delete buttons carry no inline handler.
 */
class FieldCustomvalueListTest extends DbTestCase
{
    private const MARKUP = '<b>Value</b><script>alert(1)</script>';

    /**
     * @return array<string, mixed>
     */
    private function getParams(string $type, string $item = ''): array
    {
        return [
            'plugin_metademands_fields_id' => 42,
            'type'                         => $type,
            'item'                         => $item,
            'display_type'                 => 0,
            'default_values'               => [],
            'custom_values'                => [
                7 => [
                    'rank'       => 0,
                    'name'       => self::MARKUP,
                    'comment'    => '<i>Comment</i>',
                    'is_default' => 1,
                    'icon'       => 'ti-star',
                ],
                8 => [
                    'rank'       => 1,
                    'name'       => 'Second',
                    'comment'    => '',
                    'is_default' => 0,
                    'icon'       => '',
                ],
            ],
        ];
    }

    /**
     * Name attribute of a core widget, quoted either way.
     */
    private function assertHasName(string $name, string $html): void
    {
        $this->assertMatchesRegularExpression('/name=[\'"]' . preg_quote($name, '/') . '[\'"]/', $html);
    }

    /**
     * @param callable(): void $display
     */
    private function capture(callable $display): string
    {
        ob_start();
        $display();

        return (string) ob_get_clean();
    }

    private function assertNoPluginInlineScript(string $html): void
    {
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('formToggle', $html);
        $this->assertStringNotContainsString('metademandWizard', $html);
        // (the scripts left are those of the core widgets: select2, web icon picker)
    }

    public function testCheckboxValuesAreEscapedAndButtonsDriven(): void
    {
        $this->login();

        $html = $this->capture(fn() => Checkbox::showFieldCustomValues($this->getParams('checkbox')));

        $this->assertStringNotContainsString(self::MARKUP, $html);
        $this->assertStringContainsString('value="&lt;b&gt;Value&lt;/b&gt;&lt;script&gt;', $html);
        $this->assertStringContainsString('value="&lt;i&gt;Comment&lt;/i&gt;"', $html);
        $this->assertNoPluginInlineScript($html);
        $this->assertStringNotContainsString('bg-outline-', $html);

        // Default value and icon widgets of each row
        $this->assertHasName('is_default[7]', $html);
        $this->assertHasName('is_default[8]', $html);
        $this->assertHasName('icon[7]', $html);
        $this->assertStringContainsString('name="_blank_picture[8]"', $html);
        $this->assertStringContainsString('WebIconSelector', $html);

        // One delete form per row, posting the rank and the field
        $this->assertMatchesRegularExpression(
            '/name="customvalues_id" value="8">\s*<input type="hidden" name="rank" value="1">\s*'
            . '<input type="hidden" name="plugin_metademands_fields_id" value="42">/',
            $html,
        );
        $this->assertSame(2, substr_count($html, 'name="delete"'));

        // Add button read by public/scripts/wizard_form.js, counting from the last rank
        $this->assertStringContainsString('data-md-customvalue-add="42"', $html);
        $this->assertMatchesRegularExpression('/id="count_custom_values" name="count_custom_values" value="1"/', $html);
        $this->assertMatchesRegularExpression('/id="display_comment" name="display_comment" value="1"/', $html);
        $this->assertMatchesRegularExpression('/id="display_icon" name="display_icon" value="1"/', $html);

        // CSV import, confirmed by public/scripts/wizard_form.js
        $this->assertStringContainsString('name="importreplacecsv"', $html);
        $this->assertStringContainsString('data-md-confirm="', $html);
        $this->assertStringContainsString('data-bs-target="#md-customvalue-import42"', $html);

        // Row forms + delete forms + import form, without CSRF token (GLPI 12)
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
        $this->assertSame(5, substr_count($html, '<form'));
    }

    public function testRadioWithoutValueOffersOnlyAddAndImport(): void
    {
        $this->login();

        $params = $this->getParams('radio');
        $params['custom_values'] = [];
        $html = $this->capture(fn() => Radio::showFieldCustomValues($params));

        $this->assertStringContainsString('id="show_custom_fields"', $html);
        $this->assertStringContainsString('data-md-customvalue-add="42"', $html);
        $this->assertMatchesRegularExpression('/id="count_custom_values" name="count_custom_values" value="-1"/', $html);
        $this->assertStringContainsString('name="importreplacecsv"', $html);
        $this->assertStringNotContainsString('name="delete"', $html);
        $this->assertNoPluginInlineScript($html);
    }

    public function testDropdownMultipleHasNeitherCommentNorIcon(): void
    {
        $this->login();

        $html = $this->capture(fn() => Dropdownmultiple::showFieldCustomValues($this->getParams('dropdown_multiple', 'other')));

        $this->assertStringNotContainsString(self::MARKUP, $html);
        $this->assertHasName('is_default[7]', $html);
        $this->assertStringNotContainsString('name="comment[7]"', $html);
        $this->assertStringNotContainsString('name="icon[', $html);
        $this->assertMatchesRegularExpression('/id="display_icon" name="display_icon" value="0"/', $html);
        $this->assertStringContainsString('data-md-customvalue-add="42"', $html);
        $this->assertNoPluginInlineScript($html);
    }

    public function testDropdownMetaBoundToUrgencyOffersTheCoreDropdown(): void
    {
        $this->login();

        $params = $this->getParams('dropdown_meta', 'urgency');
        $params['custom_values'] = [];
        $params['default_values'] = [1 => 4];
        $html = $this->capture(fn() => Dropdownmeta::showFieldCustomValues($params));

        $this->assertHasName('default[1]', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]4[\'"][^>]*selected/', $html);
        $this->assertStringContainsString('name="item" value="urgency"', $html);
        $this->assertStringNotContainsString('data-md-customvalue-add', $html);
        $this->assertStringNotContainsString('importreplacecsv', $html);
    }

    public function testDropdownMetaBoundToMyDevicesOffersTheDeviceTypes(): void
    {
        $this->login();

        $params = $this->getParams('dropdown_meta', 'mydevices');
        $params['custom_values'] = [];
        $params['default_values'] = ['Computer'];
        $html = $this->capture(fn() => Dropdownmeta::showFieldCustomValues($params));

        $this->assertHasName('default[]', $html);
        $this->assertStringContainsString('multiple', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]Computer[\'"][^>]*selected/', $html);
        $this->assertStringNotContainsString('data-md-customvalue-add', $html);
    }

    public function testAddedValueUsesTheCoreWidgets(): void
    {
        $this->login();

        $html = $this->capture(fn() => FieldCustomvalue::addNewValue(3, 1, 1, 42, true));

        $this->assertStringContainsString('name="custom_values[3]"', $html);
        $this->assertStringContainsString('name="comment_values[3]"', $html);
        $this->assertHasName('default_values[3]', $html);
        $this->assertHasName('icon[3]', $html);
        $this->assertStringContainsString('name="_blank_picture[3]"', $html);
        $this->assertStringContainsString('bg-secondary-lt', $html);
        $this->assertNoPluginInlineScript($html);
    }
}
