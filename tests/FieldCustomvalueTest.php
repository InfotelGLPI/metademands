<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;

class FieldCustomvalueTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => $this->getUniqueString(),
            'entities_id'      => 0,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    private function createField(int $metademand_id, string $type, string $item = ''): Field
    {
        return $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand_id,
            'type'                              => $type,
            'item'                              => $item,
            'name'                              => $this->getUniqueString(),
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => 0,
        ]);
    }

    private function createFieldParameter(int $field_id, array $custom, array $default = []): FieldParameter
    {
        return $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $field_id,
            'custom'                       => FieldParameter::_serialize($custom),
            'default'                      => FieldParameter::_serialize($default),
        ]);
    }

    // -------------------------------------------------------------------------
    // link
    // -------------------------------------------------------------------------

    public function testLinkFieldWithButtonTypeStoresCustomValues(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'link');
        $this->createFieldParameter($field->getID(), ['button', 'https://example.com']);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('link', $params['type']);
        $cv = $params['custom_values'];
        $this->assertSame('button', $cv[0]);
        $this->assertSame('https://example.com', $cv[1]);
    }

    public function testLinkFieldWithLinkATypeStoresCustomValues(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'link');
        $this->createFieldParameter($field->getID(), ['link_a', 'https://glpi-project.org']);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $cv = $params['custom_values'];
        $this->assertSame('link_a', $cv[0]);
        $this->assertSame('https://glpi-project.org', $cv[1]);
    }

    // -------------------------------------------------------------------------
    // number
    // -------------------------------------------------------------------------

    public function testNumberFieldStoresMinMaxStepCustomValues(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'number');
        $this->createFieldParameter($field->getID(), ['0', '100', '5', '1']);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('number', $params['type']);
        $cv = $params['custom_values'];
        $this->assertSame('0', $cv[0]);
        $this->assertSame('100', $cv[1]);
        $this->assertSame('5', $cv[2]);
        $this->assertSame('1', $cv[3]);
    }

    // -------------------------------------------------------------------------
    // range
    // -------------------------------------------------------------------------

    public function testRangeFieldStoresMinMaxStepCustomValues(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'range');
        $this->createFieldParameter($field->getID(), ['10', '50', '2', '0']);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('range', $params['type']);
        $cv = $params['custom_values'];
        $this->assertSame('10', $cv[0]);
        $this->assertSame('50', $cv[1]);
        $this->assertSame('2', $cv[2]);
        $this->assertSame('0', $cv[3]);
    }

    // -------------------------------------------------------------------------
    // yesno
    // -------------------------------------------------------------------------

    public function testYesnoFieldStoresDefaultValueAsCustom(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'yesno');
        $this->createFieldParameter($field->getID(), [2]);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('yesno', $params['type']);
        $this->assertSame('2', $params['custom_values'][0]);
    }

    // -------------------------------------------------------------------------
    // basket
    // -------------------------------------------------------------------------

    public function testBasketFieldStoresWithQuantityFlag(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'basket');
        $this->createFieldParameter($field->getID(), ['1', '0']);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('basket', $params['type']);
        $cv = $params['custom_values'];
        $this->assertSame('1', $cv[0]);
        $this->assertSame('0', $cv[1]);
    }

    // -------------------------------------------------------------------------
    // checkbox  (FieldCustomvalue rows, one per choice)
    // -------------------------------------------------------------------------

    public function testCheckboxFieldStoresCustomValueRows(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'checkbox', 'checkbox');

        $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => 'Option A',
            'rank'                         => 0,
            'is_default'                   => 0,
        ]);
        $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => 'Option B',
            'rank'                         => 1,
            'is_default'                   => 1,
        ]);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('checkbox', $params['type']);
        $this->assertCount(2, $params['custom_values']);
        $names = array_column($params['custom_values'], 'name');
        $this->assertContains('Option A', $names);
        $this->assertContains('Option B', $names);
    }

    // -------------------------------------------------------------------------
    // radio  (FieldCustomvalue rows, one per choice)
    // -------------------------------------------------------------------------

    public function testRadioFieldStoresCustomValueRows(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'radio', 'radio');

        $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => 'Choice 1',
            'rank'                         => 0,
            'is_default'                   => 1,
        ]);
        $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => 'Choice 2',
            'rank'                         => 1,
            'is_default'                   => 0,
        ]);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('radio', $params['type']);
        $this->assertCount(2, $params['custom_values']);
        $defaultRow = array_filter($params['custom_values'], fn($r) => $r['is_default'] == 1);
        $this->assertCount(1, $defaultRow);
        $this->assertSame('Choice 1', reset($defaultRow)['name']);
    }

    // -------------------------------------------------------------------------
    // dropdown_meta / other  (FieldCustomvalue rows)
    // -------------------------------------------------------------------------

    public function testDropdownMetaFieldStoresCustomValueRows(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'dropdown_meta', 'other');

        $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => 'Entry X',
            'rank'                         => 0,
            'is_default'                   => 0,
        ]);

        $field->getFromDB($field->getID());
        $params = Field::getAllParamsFromField($field);

        $this->assertSame('dropdown_meta', $params['type']);
        $this->assertCount(1, $params['custom_values']);
        $this->assertSame('Entry X', reset($params['custom_values'])['name']);
    }

    // -------------------------------------------------------------------------
    // purge cascades
    // -------------------------------------------------------------------------

    public function testPurgingFieldDeletesItsFieldParameter(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'link');
        $param = $this->createFieldParameter($field->getID(), ['button', 'https://example.com']);

        $field->delete(['id' => $field->getID()], true);

        $remaining = countElementsInTable(
            FieldParameter::getTable(),
            ['plugin_metademands_fields_id' => $field->getID()]
        );
        $this->assertSame(0, $remaining);
    }

    public function testPurgingFieldDeletesItsCustomValueRows(): void
    {
        $this->login('glpi', 'glpi');

        $meta  = $this->createMetademand();
        $field = $this->createField($meta->getID(), 'checkbox', 'checkbox');

        $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => 'To be deleted',
            'rank'                         => 0,
            'is_default'                   => 0,
        ]);

        $field->delete(['id' => $field->getID()], true);

        $remaining = countElementsInTable(
            FieldCustomvalue::getTable(),
            ['plugin_metademands_fields_id' => $field->getID()]
        );
        $this->assertSame(0, $remaining);
    }
}
