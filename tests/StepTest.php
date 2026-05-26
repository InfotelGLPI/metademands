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
 the Free Software Foundation; either version 2 of the License, or
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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;

class StepTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Step Test Metademand',
            'entities_id'      => 0,
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'step_by_step_mode' => 1,
        ]);
    }

    public function testStepCanBeCreatedForMetademand(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $step = $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 1,
            'groups_id'                         => 0,
        ]);

        $this->assertGreaterThan(0, $step->getID());
        $this->assertSame(
            $metademand->getID(),
            (int) $step->getField('plugin_metademands_metademands_id')
        );
    }

    public function testMultipleStepsCanBeCreatedForSameMetademand(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 1,
            'groups_id'                         => 0,
        ]);
        $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 2,
            'groups_id'                         => 0,
        ]);

        $count = countElementsInTable(
            Step::getTable(),
            ['plugin_metademands_metademands_id' => $metademand->getID()]
        );
        $this->assertSame(2, $count);
    }

    public function testStepGetTypeNameIsNotEmpty(): void
    {
        $this->assertNotEmpty(Step::getTypeName());
    }

    public function testGetForbiddenStandardMassiveActionIncludesUpdate(): void
    {
        $this->login('glpi', 'glpi');

        $step = new Step();
        $forbidden = $step->getForbiddenStandardMassiveAction();

        $this->assertContains('update', $forbidden);
    }
}
