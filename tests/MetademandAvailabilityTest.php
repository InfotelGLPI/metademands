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
use GlpiPlugin\Metademands\Metademand;

/**
 * #377: an inactive meta-demand, one in the trashbin or a template is not filled from its URL, but by its
 * designers and by the next actor of a step form already sent.
 */
class MetademandAvailabilityTest extends DbTestCase
{
    private function createMetademand(array $input = []): Metademand
    {
        return $this->createItem(Metademand::class, $input + [
            'name'             => 'Availability',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'is_active'        => 1,
        ]);
    }

    /**
     * @param array<string, int> $state
     */
    private function setState(Metademand $metademand, array $state): Metademand
    {
        global $DB;

        $DB->update(Metademand::getTable(), $state, ['id' => $metademand->getID()]);
        $metademand->getFromDB($metademand->getID());

        return $metademand;
    }

    public function testOnlyAnActiveMetademandIsFilledByTheRequesters(): void
    {
        $this->login();
        $metademand = $this->createMetademand();
        // A requester: no update right on the meta-demands
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = READ;

        $this->assertTrue($metademand->canBeFilled());
        $this->assertFalse($this->setState($metademand, ['is_active' => 0])->canBeFilled(), 'inactive');
        $this->assertFalse($this->setState($metademand, ['is_active' => 1, 'is_deleted' => 1])->canBeFilled(), 'trashbin');
        $this->assertFalse($this->setState($metademand, ['is_deleted' => 0, 'is_template' => 1])->canBeFilled(), 'template');
    }

    public function testDesignersOnlyPreviewAnInactiveMetademand(): void
    {
        $this->login();
        $metademand = $this->setState($this->createMetademand(), ['is_deleted' => 1]);
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;

        // Neither its URL nor the creation of the ticket: the "Preview" button of the "Wizard creation" tab only
        $this->assertFalse($metademand->canBeFilled());
        $this->assertTrue($metademand->canBeFilled(true));

        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = READ;
        $this->assertFalse($metademand->canBeFilled(true), 'preview without the update right');
    }

    public function testAStepFormAlreadySentIsStillCompleted(): void
    {
        $this->login();
        $metademand = $this->setState($this->createMetademand(), ['is_active' => 0]);
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = READ;

        // Loaded by its next actor (ajax/loadstepform.php)
        $_SESSION['plugin_metademands'][$metademand->getID()]['plugin_metademands_stepforms_id'] = 12;
        try {
            $this->assertTrue($metademand->canBeFilled());
            $this->assertFalse($this->setState($metademand, ['is_active' => 1, 'is_template' => 1])->canBeFilled(), 'template');
        } finally {
            unset($_SESSION['plugin_metademands'][$metademand->getID()]);
        }
    }
}
