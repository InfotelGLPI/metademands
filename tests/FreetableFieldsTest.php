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
use GlpiPlugin\Metademands\Fields\Freetable;
use GlpiPlugin\Metademands\Freetablefield;
use GlpiPlugin\Metademands\Metademand;

/**
 * Columns of a free table field (fields/freetable_fields.html.twig): the widgets come
 * from the core macros, the values are escaped by Twig, the delete button carries its
 * column and the add button is driven by a data attribute.
 */
class FreetableFieldsTest extends DbTestCase
{
    private const NAME = '<b>Column</b><script>alert(1)</script>';

    /**
     * @return array{Field, Freetablefield, Freetablefield}
     */
    private function createFreetableWithColumns(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Free table',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'freetable',
            'name'                              => 'Free table',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $text = $this->createItem(Freetablefield::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'internal_name'                => 'column1',
            'type'                         => Freetablefield::TYPE_TEXT,
            'name'                         => 'Text',
            'comment'                      => 'Comment',
            'is_mandatory'                 => 1,
            'rank'                         => 1,
        ]);
        $select = $this->createItem(Freetablefield::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'internal_name'                => 'column2',
            'type'                         => Freetablefield::TYPE_SELECT,
            'name'                         => 'Select',
            'dropdown_values'              => 'A,B',
            'is_mandatory'                 => 0,
            'rank'                         => 2,
        ]);
        // Store markup as legacy data could hold it, whatever the input sanitization
        global $DB;
        $DB->update(Freetablefield::getTable(), ['name' => self::NAME], ['id' => $text->getID()]);

        return [$field, $text, $select];
    }

    private function renderColumns(Field $field): string
    {
        $params = Field::getAllParamsFromField($field);

        ob_start();
        Freetable::showFreetableFields($params);

        return (string) ob_get_clean();
    }

    public function testColumnsAreRenderedByTheCoreWidgets(): void
    {
        $this->login();
        [$field, $text, $select] = $this->createFreetableWithColumns();

        $html = $this->renderColumns($field);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString(htmlescape(self::NAME), $html);
        foreach ([$text, $select] as $column) {
            $id = $column->getID();
            $this->assertStringContainsString('name="internal_name[' . $id . ']"', $html);
            // Dropdown::showFromArray() quotes its attributes with apostrophes
            $this->assertStringContainsString("name='type[" . $id . "]'", $html);
            $this->assertStringContainsString("name='is_mandatory[" . $id . "]'", $html);
            $this->assertStringContainsString('name="id[' . $id . ']"', $html);
            $this->assertStringContainsString('name="delete" value="' . $id . '"', $html);
        }
        $this->assertStringContainsString('name="comment[' . $text->getID() . ']"', $html);
        $this->assertStringContainsString('name="dropdown_values[' . $select->getID() . ']"', $html);
        $this->assertStringContainsString('data-bs-toggle="tooltip"', $html);
        $this->assertStringContainsString('name="update"', $html);

        // Two columns out of six: the add button is offered, without inline handler
        // (the only scripts left are the select2 bootstraps of the core dropdowns)
        $this->assertStringContainsString('data-md-freetablefield-add="' . $field->getID() . '"', $html);
        $this->assertStringContainsString('id="count_custom_values" name="count_custom_values" value="2"', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertSame(4, substr_count($html, '<script'));
    }

    public function testNewColumnFormIsRenderedByTheCoreWidgets(): void
    {
        $this->login();
        [$field] = $this->createFreetableWithColumns();

        ob_start();
        Freetablefield::addNewValue(3, $field->getID());
        $html = (string) ob_get_clean();

        $this->assertSame(1, substr_count($html, '<form'));
        $this->assertStringContainsString('name="rank" value="3"', $html);
        $this->assertStringContainsString('name="fields_id" value="' . $field->getID() . '"', $html);
        $this->assertStringContainsString('name="internal_name_values[3]"', $html);
        $this->assertStringContainsString('name="custom_values[3]"', $html);
        $this->assertStringContainsString('name="comment_values[3]"', $html);
        $this->assertStringContainsString('name="dropdown_values[3]"', $html);
        // Dropdown::showFromArray() quotes its attributes with apostrophes
        $this->assertStringContainsString("name='type_values[3]'", $html);
        $this->assertStringContainsString('data-md-freetablefield-type="3"', $html);
        $this->assertStringContainsString("name='is_mandatory_values[3]'", $html);
        $this->assertSame(2, substr_count($html, 'data-bs-toggle="tooltip"'));
        $this->assertStringContainsString('name="add"', $html);
        // The cells used to share one id, which the add button appends to
        $this->assertStringNotContainsString('show_custom_fields', $html);
    }

    public function testEmptyTableOnlyOffersTheAddButton(): void
    {
        $this->login();
        [$field, $text, $select] = $this->createFreetableWithColumns();
        $this->assertTrue($text->delete(['id' => $text->getID()], true));
        $this->assertTrue($select->delete(['id' => $select->getID()], true));

        $html = $this->renderColumns($field);

        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringContainsString('data-md-freetablefield-add="' . $field->getID() . '"', $html);
        $this->assertStringContainsString('id="count_custom_values" name="count_custom_values" value="-1"', $html);
    }
}
