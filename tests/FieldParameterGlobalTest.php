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
use GlpiPlugin\Metademands\FieldParameter;

/**
 * Global parameters of a field (field_parameter_global.html.twig): the template gets
 * values only and renders the widgets through the core macros.
 */
class FieldParameterGlobalTest extends DbTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function getParams(string $type, string $item = '', int $id = 12): array
    {
        return [
            'id'                                => $id,
            'plugin_metademands_metademands_id' => 5,
            'type'                              => $type,
            'item'                              => $item,
            'object_to_create'                  => 'Ticket',
            'is_order'                          => 1,
            'is_mandatory'                      => 1,
            'hide_title'                        => 0,
            'row_display'                       => 1,
            'is_basket'                         => 0,
            'icon'                              => 'ti-star',
            'used_by_ticket'                    => 10,
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
     * Option selected in the select named $name.
     */
    private function assertSelected(string $name, string $value, string $html): void
    {
        $this->assertMatchesRegularExpression(
            '/name=[\'"]' . preg_quote($name, '/') . '[\'"][^>]*>.*?<option value=[\'"]'
            . preg_quote($value, '/') . '[\'"][^>]*selected.*?<\/select>/s',
            $html,
        );
    }

    public function testDropdownMetaOffersEveryParameterWithItsValue(): void
    {
        $this->login();

        $html = FieldParameter::showGlobalParameters($this->getParams('dropdown_meta', 'urgency'));

        $this->assertSelected('is_mandatory', '1', $html);
        $this->assertSelected('hide_title', '0', $html);
        $this->assertSelected('row_display', '1', $html);
        $this->assertSelected('is_basket', '0', $html);
        $this->assertSelected('used_by_ticket', '10', $html);

        // Core web icon picker, value without its "ti " prefix, and the clear checkbox
        $this->assertHasName('icon', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]ti-star[\'"][^>]*selected/', $html);
        $this->assertStringContainsString('WebIconSelector', $html);
        $this->assertStringContainsString('name="_blank_picture"', $html);

        // No script of the plugin, only those of the core widgets
        $this->assertStringNotContainsString('icon_selector', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function testNewFieldGoesIntoTheBasketByDefault(): void
    {
        $this->login();

        $html = FieldParameter::showGlobalParameters($this->getParams('dropdown_meta', 'urgency', 0));

        $this->assertSelected('is_basket', '1', $html);
    }

    public function testTitleBlockOffersOnlyTheIcon(): void
    {
        $this->login();

        $html = FieldParameter::showGlobalParameters($this->getParams('title-block'));

        $this->assertHasName('icon', $html);
        foreach (['is_mandatory', 'hide_title', 'row_display', 'is_basket', 'used_by_ticket', 'plugin_fields_fields_id'] as $name) {
            $this->assertDoesNotMatchRegularExpression('/name=[\'"]' . preg_quote($name, '/') . '[\'"]/', $html);
        }
    }
}
