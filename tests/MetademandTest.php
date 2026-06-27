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
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;

class MetademandTest extends DbTestCase
{
    private function createMetademand(array $extra = []): Metademand
    {
        return $this->createItem(Metademand::class, array_merge([
            'name'             => 'Test Metademand',
            'entities_id'      => 0,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ], $extra));
    }

    public function testMetademandCanBeCreatedAndRetrieved(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand(['name' => 'CRUD Metademand']);

        $this->assertGreaterThan(0, $metademand->getID());
        $this->assertSame('CRUD Metademand', $metademand->getField('name'));
    }

    public function testMetademandCreationFailsWhenObjectToCreateIsMissing(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = new Metademand();
        $result = $metademand->prepareInputForAdd([
            'name'        => 'Missing object_to_create',
            'entities_id' => 0,
            'type'        => 0,
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(1, ['Object to create is mandatory']);
    }

    public function testPrepareInputForAddJsonEncodesItilCategoriesId(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = new Metademand();
        $result = $metademand->prepareInputForAdd([
            'name'               => 'Cat Test',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => 0,
            'itilcategories_id'  => null,
        ]);

        $this->assertSame('', $result['itilcategories_id']);
    }

    public function testPrepareInputForAddSetsForceCreateTasksForProblem(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = new Metademand();
        $result = $metademand->prepareInputForAdd([
            'name'             => 'Problem Meta',
            'entities_id'      => 0,
            'object_to_create' => 'Problem',
            'type'             => 1,
        ]);

        $this->assertSame(1, $result['force_create_tasks']);
        $this->assertSame(0, $result['type']);
    }

    public function testMetademandCanBeUpdated(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $this->updateItem(Metademand::class, $metademand->getID(), [
            'name'             => 'Updated Name',
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);

        $metademand->getFromDB($metademand->getID());
        $this->assertSame('Updated Name', $metademand->getField('name'));
    }

    public function testMetademandCanBeDeleted(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $id = $metademand->getID();

        $metademand->delete(['id' => $id], true);

        $remaining = countElementsInTable(
            Metademand::getTable(),
            ['id' => $id]
        );
        $this->assertSame(0, $remaining);
    }

    public function testCleanDBonPurgeRemovesRelatedFields(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'field_label',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => 0,
        ]);

        $metademand->delete(['id' => $metademand->getID()], true);

        $remaining = countElementsInTable(
            Field::getTable(),
            ['plugin_metademands_metademands_id' => $metademand->getID()]
        );
        $this->assertSame(0, $remaining);
    }

    public function testCleanDBonPurgeRemovesRelatedSteps(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand(['step_by_step_mode' => 1]);

        $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 1,
            'groups_id'                         => 0,
        ]);

        $metademand->delete(['id' => $metademand->getID()], true);

        $remaining = countElementsInTable(
            Step::getTable(),
            ['plugin_metademands_metademands_id' => $metademand->getID()]
        );
        $this->assertSame(0, $remaining);
    }

    public function testCleanDBonPurgeRemovesRelatedConditions(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'cond_field',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => 0,
        ]);

        $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'plugin_metademands_fields_id'      => $field->getID(),
            'items_id'                          => 0,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'show_logic'                        => Condition::SHOW_LOGIC_AND,
        ]);

        $metademand->delete(['id' => $metademand->getID()], true);

        $remaining = countElementsInTable(
            Condition::getTable(),
            ['plugin_metademands_metademands_id' => $metademand->getID()]
        );
        $this->assertSame(0, $remaining);
    }
}
