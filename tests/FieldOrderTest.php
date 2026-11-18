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
use GlpiPlugin\Metademands\Metademand;

/**
 * "Display field after" of the field forms (Field::getOrderDropdownData()): after the last
 * field of the block by default; a block title always first (Field::recalculateOrder())
 */
class FieldOrderTest extends DbTestCase
{
    /** @var array<string, Field> */
    private array $fields = [];

    private Metademand $metademand;

    public function setUp(): void
    {
        parent::setUp();
        $this->login();

        $this->metademand = $this->createItem(Metademand::class, [
            'name'             => 'Order',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        foreach ([['FIRST', 2, 1], ['SECOND', 2, 2], ['LAST', 2, 3], ['OTHER', 4, 1]] as [$name, $rank, $order]) {
            $this->fields[$name] = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $this->metademand->getID(),
                'type'                              => 'text',
                'name'                              => $name,
                'rank'                              => $rank,
                'order'                             => $order,
                'entities_id'                       => $this->getTestRootEntity(true),
            ]);
        }
    }

    private function orderOf(array $params): array
    {
        return (new Field())->getOrderDropdownData($params + [
            'plugin_metademands_metademands_id' => $this->metademand->getID(),
            'plugin_metademands_fields_id'      => 0,
        ]);
    }

    public function testANewFieldGoesAfterTheLastFieldOfItsBlock(): void
    {
        // Form displayed, then reloaded by a change of block (ajax/viewtypefields.php, step order)
        $this->assertSame($this->fields['LAST']->getID(), $this->orderOf(['rank' => 2])['value']);
        $this->assertSame($this->fields['OTHER']->getID(), $this->orderOf(['rank' => 4, 'id' => 0])['value']);
        // An empty block: nothing to follow
        $this->assertSame(0, $this->orderOf(['rank' => 7])['value']);
    }

    public function testAnExistingFieldKeepsItsPlace(): void
    {
        $first = $this->fields['FIRST']->fields;
        $this->assertSame(0, (int) $this->orderOf(['id' => $first['id'], 'rank' => 2, 'order' => 1])['value']);

        $second = $this->fields['SECOND']->fields;
        $this->assertSame(
            $this->fields['FIRST']->getID(),
            (int) $this->orderOf(['id' => $second['id'], 'rank' => 2, 'order' => 2])['value'],
        );

        // Taken to block 4: its previous field stayed in block 2, it goes last there
        $this->assertSame($this->fields['OTHER']->getID(), $this->orderOf([
            'id'                           => $second['id'],
            'rank'                         => 4,
            'plugin_metademands_fields_id' => $this->fields['FIRST']->getID(),
        ])['value']);
    }

    public function testABlockTitleAlwaysGoesFirst(): void
    {
        $title = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $this->metademand->getID(),
            'type'                              => 'title-block',
            'name'                              => 'TITLE',
            'rank'                              => 2,
            'order'                             => 4,
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);
        // Posted after the last field, as the new field form now proposes for the other types
        (new Field())->recalculateOrder([
            'id'                                => $title->getID(),
            'type'                              => 'title-block',
            'rank'                              => 2,
            'plugin_metademands_metademands_id' => $this->metademand->getID(),
            'plugin_metademands_fields_id'      => $this->fields['LAST']->getID(),
        ]);

        $rows = (new Field())->find([
            'plugin_metademands_metademands_id' => $this->metademand->getID(),
            'rank'                              => 2,
        ], ['order']);
        $this->assertSame(['TITLE', 'FIRST', 'SECOND', 'LAST'], array_values(array_column($rows, 'name')));
    }
}
