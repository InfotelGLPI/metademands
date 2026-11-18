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
use GlpiPlugin\Metademands\Metademand;

/**
 * Custom values typed in the new field form, added with the field
 * (FieldCustomvalue::addInitialValues())
 */
class FieldInitialValuesTest extends DbTestCase
{
    private const VALUES = [
        ['name' => ' First '],
        ['name' => ''],
        ['name' => 'Second', 'is_default' => '1'],
        ['name' => 'Third', 'is_default' => '1'],
    ];

    private function createField(string $type, string $item): int
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Initial values',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);

        return $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => $type,
            'item'                              => $item,
            'name'                              => 'Field',
            'rank'                              => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['item'])->getID();
    }

    /**
     * @return array<int, array{name: string, rank: int, is_default: int}>
     */
    private function values(int $fields_id): array
    {
        return array_map(
            static fn($row) => ['name' => $row['name'], 'rank' => (int) $row['rank'], 'is_default' => (int) $row['is_default']],
            array_values(getAllDataFromTable(FieldCustomvalue::getTable(), [
                'WHERE' => ['plugin_metademands_fields_id' => $fields_id],
                'ORDER' => 'rank',
            ])),
        );
    }

    public function testRadioKeepsOneDefaultValue(): void
    {
        $fields_id = $this->createField('radio', 'radio');

        $this->assertSame(3, FieldCustomvalue::addInitialValues($fields_id, self::VALUES));
        $this->assertSame([
            ['name' => 'First', 'rank' => 0, 'is_default' => 0],
            ['name' => 'Second', 'rank' => 1, 'is_default' => 1],
            ['name' => 'Third', 'rank' => 2, 'is_default' => 0],
        ], $this->values($fields_id));
    }

    public function testCheckboxKeepsSeveralDefaultValues(): void
    {
        $fields_id = $this->createField('checkbox', 'checkbox');

        $this->assertSame(3, FieldCustomvalue::addInitialValues($fields_id, self::VALUES));
        $this->assertSame([0, 1, 1], array_column($this->values($fields_id), 'is_default'));
    }

    public function testSpecificDropdownTakesValuesForItsOtherObjectOnly(): void
    {
        $other = $this->createField('dropdown_meta', 'other');
        $this->assertSame(3, FieldCustomvalue::addInitialValues($other, self::VALUES));

        $urgency = $this->createField('dropdown_meta', 'urgency');
        $this->assertSame(0, FieldCustomvalue::addInitialValues($urgency, self::VALUES));
        $this->assertSame([], $this->values($urgency));

        $multiple = $this->createField('dropdown_multiple', 'other');
        $this->assertSame(3, FieldCustomvalue::addInitialValues($multiple, self::VALUES));
    }

    public function testOtherTypesTakeNoValues(): void
    {
        $fields_id = $this->createField('text', '');

        $this->assertSame(0, FieldCustomvalue::addInitialValues($fields_id, self::VALUES));
        $this->assertSame(0, FieldCustomvalue::addInitialValues($fields_id, 'not a list'));
        $this->assertSame([], $this->values($fields_id));
    }

    public function testValuesFollowTheTypeAndTheObject(): void
    {
        $this->assertTrue(FieldCustomvalue::hasInitialValues('checkbox', null));
        $this->assertTrue(FieldCustomvalue::hasInitialValues('dropdown_meta', 'other'));
        $this->assertTrue(FieldCustomvalue::hasInitialValues('dropdown_multiple', 'other'));
        // Only once "custom values" is chosen as the object
        $this->assertFalse(FieldCustomvalue::hasInitialValues('dropdown_meta', ''));
        $this->assertFalse(FieldCustomvalue::hasInitialValues('dropdown_multiple', ''));
        $this->assertFalse(FieldCustomvalue::hasInitialValues('dropdown_meta', 'impact'));
        $this->assertFalse(FieldCustomvalue::hasInitialValues('dropdown_multiple', 'User'));
        $this->assertFalse(FieldCustomvalue::hasInitialValues('yesno', ''));
    }
}
