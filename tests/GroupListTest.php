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
use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\Metademand;

/**
 * Groups allowed to enter a metademand (group.html.twig): the group selector comes
 * from the core macro, the added groups are listed with their escaped link.
 */
class GroupListTest extends DbTestCase
{
    public function testAddedGroupIsListedAndNotOfferedAgain(): void
    {
        $this->login();
        // A fresh CLI install grants no right of the plugin
        $_SESSION["glpiactiveprofile"][Group::$rightname] = ALLSTANDARDRIGHT;

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Groups',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $added = $this->createItem(\Group::class, [
            'name'        => 'Added <b>group</b>',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $other = $this->createItem(\Group::class, [
            'name'        => 'Other group',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $link = $this->createItem(Group::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'groups_id'                         => $added->getID(),
        ]);

        ob_start();
        (new Group())->showForMetademand($metademand);
        $html = (string) ob_get_clean();

        // Multiple selector of the core, without the group already added
        $this->assertMatchesRegularExpression('/name=[\'"]groups_id\[\][\'"]/', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]' . $other->getID() . '[\'"]/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value=[\'"]' . $added->getID() . '[\'"]/', $html);

        // Added group listed with its escaped name and its massive action checkbox
        $this->assertStringNotContainsString('Added <b>group</b>', $html);
        $this->assertStringContainsString('Added &lt;b&gt;group&lt;/b&gt;', $html);
        $this->assertStringContainsString('item[' . Group::class . '][' . $link->getID() . ']', $html);

        // Add form and massive actions form, without CSRF token (GLPI 12)
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
        $this->assertSame(2, substr_count($html, '<form'));
        $this->assertSame(2, substr_count($html, '</form>'));
    }
}
