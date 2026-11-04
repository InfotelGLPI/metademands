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
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;
use Ticket;

/**
 * The wizard skips the steps with a single choice. It has to decide it before the page
 * header, which displays and clears the messages of the session.
 */
class WizardAutoRedirectTest extends DbTestCase
{
    public function setUp(): void
    {
        global $DB;

        parent::setUp();
        $this->login();

        // Only the meta-demands of each test are offered (rolled back with the test)
        $DB->update(Metademand::getTable(), ['is_active' => 0], [new \Glpi\DBAL\QueryExpression('true')]);
    }

    private function setDisplayType(int $display_type): void
    {
        global $DB;

        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), ['id' => 1, 'display_type' => $display_type]);
        } else {
            $DB->update(Config::getTable(), ['display_type' => $display_type], ['id' => 1]);
        }
        (new \ReflectionProperty(Config::class, 'instance'))->setValue(null, null);
    }

    private function createMetademand(int $type): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Auto redirect ' . $this->getUniqueString(),
            'entities_id'      => 0,
            'is_recursive'     => 1,
            'is_active'        => 1,
            'is_order'         => 1,
            'object_to_create' => 'Ticket',
            'type'             => $type,
        ]);
    }

    private function getFormUrl(Metademand $metademand): string
    {
        return PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php?metademands_id=' . $metademand->getID()
            . '&step=' . Metademand::STEP_SHOW;
    }

    private function getListUrl(int $type): string
    {
        return PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php?step=' . Metademand::STEP_LIST . '&meta_type=' . $type;
    }

    public function testTheOnlyMetademandIsOpenedFromTheFirstStep(): void
    {
        $this->setDisplayType(1);
        $metademand = $this->createMetademand(Ticket::DEMAND_TYPE);

        $this->assertSame($this->getFormUrl($metademand), Wizard::getAutoRedirectUrl(Metademand::STEP_INIT));
        $this->assertSame($this->getFormUrl($metademand), Wizard::getAutoRedirectUrl(Metademand::STEP_LIST, Ticket::DEMAND_TYPE));
    }

    public function testTheListOfTheOnlyTypeIsShownWithoutTheCards(): void
    {
        // The plain list never skips to the meta-demand, even when it is the only one
        $this->setDisplayType(0);
        $this->createMetademand(Ticket::DEMAND_TYPE);

        $this->assertSame($this->getListUrl(Ticket::DEMAND_TYPE), Wizard::getAutoRedirectUrl(Metademand::STEP_INIT));
        $this->assertNull(Wizard::getAutoRedirectUrl(Metademand::STEP_LIST, Ticket::DEMAND_TYPE));
    }

    public function testSeveralMetademandsOfTheOnlyTypeAreListed(): void
    {
        $this->setDisplayType(1);
        $this->createMetademand(Ticket::DEMAND_TYPE);
        $this->createMetademand(Ticket::DEMAND_TYPE);

        $this->assertSame($this->getListUrl(Ticket::DEMAND_TYPE), Wizard::getAutoRedirectUrl(Metademand::STEP_INIT));
        $this->assertNull(Wizard::getAutoRedirectUrl(Metademand::STEP_LIST, Ticket::DEMAND_TYPE));
    }

    public function testSeveralTypesAreListed(): void
    {
        $this->setDisplayType(1);
        $this->createMetademand(Ticket::DEMAND_TYPE);
        $this->createMetademand(Ticket::INCIDENT_TYPE);

        $this->assertNull(Wizard::getAutoRedirectUrl(Metademand::STEP_INIT));
        // Each type still opens its only meta-demand
        $this->assertNotNull(Wizard::getAutoRedirectUrl(Metademand::STEP_LIST, Ticket::INCIDENT_TYPE));
    }

    public function testTheOtherStepsAreNeverSkipped(): void
    {
        $this->setDisplayType(1);
        $this->createMetademand(Ticket::DEMAND_TYPE);

        $this->assertNull(Wizard::getAutoRedirectUrl(Metademand::STEP_SHOW));
        $this->assertNull(Wizard::getAutoRedirectUrl(Metademand::STEP_CREATE));
    }
}
