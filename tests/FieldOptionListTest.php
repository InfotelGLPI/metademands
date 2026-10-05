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
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;

/**
 * Option list of a field (field_option_options.html.twig): the values are escaped by
 * Twig, and the modal is driven by data-md-option-* attributes instead of a generated
 * inline script.
 */
class FieldOptionListTest extends DbTestCase
{
    private const LABEL = '<b>Option</b>';

    /**
     * A checkbox field whose single custom value is bound to an option.
     *
     * @return array{Field, FieldOption}
     */
    private function createCheckboxOption(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Option list',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'checkbox',
            'name'                              => 'Checkbox',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);
        $custom = $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => self::LABEL,
            'rank'                         => 0,
            'is_default'                   => 0,
        ], ['name']);
        // The input is stripped of its tags on save: store markup as legacy data would hold it
        global $DB;
        $DB->update(FieldCustomvalue::getTable(), ['name' => self::LABEL], ['id' => $custom->getID()]);
        $option = $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'check_value'                  => $custom->getID(),
        ], ['check_value']);

        return [$field, $option];
    }

    private function renderOptions(Field $field): string
    {
        ob_start();
        FieldOption::showOptions($field);

        return (string) ob_get_clean();
    }

    public function testValueToCheckIsPlainText(): void
    {
        $this->login();
        [$field, $option] = $this->createCheckboxOption();

        $params = $option->fields + [
            'type'          => 'checkbox',
            'item'          => '',
            'custom_values' => [['id' => $option->fields['check_value'], 'name' => self::LABEL]],
        ];
        $this->assertSame(self::LABEL, FieldOption::getValueToCheck($params));

        $params['check_type_value'] = 2;
        $params['check_value_regex'] = '/<a>/';
        $this->assertSame('/<a>/', FieldOption::getValueToCheck($params));
    }

    public function testListEscapesTheValueAndDrivesTheModalByAttributes(): void
    {
        $this->login();
        [$field, $option] = $this->createCheckboxOption();

        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Field::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderOptions($field);

        $this->assertStringContainsString('<td>&lt;b&gt;Option&lt;/b&gt;</td>', $html);
        $this->assertStringNotContainsString(self::LABEL, $html);
        // The only scripts left are the ones of the core massive actions
        foreach (['showFieldOptionModal', 'addOption', 'reloadviewOption', 'viewEditOption'] as $generated) {
            $this->assertStringNotContainsString($generated, $html);
        }
        $this->assertMatchesRegularExpression('/data-md-option-open="[^"]+"\s+data-md-option-id="-1"/', $html);
        $this->assertMatchesRegularExpression(
            '/data-md-option-open="[^"]+"\s+data-md-option-id="' . $option->getID() . '"/',
            $html,
        );
        $this->assertStringContainsString('data-md-option-url="', $html);
        // The option form reloaded on a value change is a field option of this field
        $this->assertMatchesRegularExpression('/data-md-option-reload-params="[^"]*Field&quot;/', $html);
    }

    public function testReadOnlyListHasNoModalNorMassiveActions(): void
    {
        $this->login();
        [$field] = $this->createCheckboxOption();

        $_SESSION['glpiactiveprofile'][Field::$rightname] = READ;
        $html = $this->renderOptions($field);

        $this->assertStringContainsString('&lt;b&gt;Option&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('data-md-option-', $html);
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
    }
}
