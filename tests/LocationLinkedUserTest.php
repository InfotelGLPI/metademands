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
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Metademand;
use Location;
use Session;
use Ticket;
use User;

/**
 * A location field linked to a user field is rendered by ajax/ulocationUpdate.php, at load
 * time and each time the user changes: it must keep the root location (and the maximum
 * depth) of the field, as the location field not linked to a user does.
 */
class LocationLinkedUserTest extends DbTestCase
{
    /** @var array{root: int, child: int, grandchild: int, outside: int} */
    private array $locations;

    public function setUp(): void
    {
        parent::setUp();

        $this->login('glpi', 'glpi');
        foreach (['plugin_metademands', 'plugin_metademands_createmeta', 'plugin_metademands_fillform'] as $right) {
            $_SESSION['glpiactiveprofile'][$right] = ALLSTANDARDRIGHT;
        }

        $location = fn(string $name, int $parent) => $this->createItem(Location::class, [
            'name'         => $name,
            'entities_id'  => 0,
            'is_recursive' => 1,
            'locations_id' => $parent,
        ])->getID();
        $root = $location('MDROOT', 0);
        $child = $location('MDCHILD', $root);
        $this->locations = [
            'root'       => $root,
            'child'      => $child,
            'grandchild' => $location('MDGRANDCHILD', $child),
            'outside'    => $location('MDOUTSIDE', 0),
        ];
    }

    /**
     * @return array{metademand: int, user: int, location: int}
     */
    private function createForm(int $display_type, int $location_depth = 0): array
    {
        $metademands_id = $this->createItem(Metademand::class, [
            'name'               => 'Lieu lié à un utilisateur',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
        ])->getID();

        $field = fn(string $name, string $type, string $item, int $order) => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => $type,
            'item'        => $item,
            'name'        => $name,
            'rank'        => 1,
            'order'       => $order,
            'entities_id' => 0,
        ], ['item'])->getID();

        $user = $field('USERFIELD', 'dropdown_object', User::class, 1);
        $location = $field('LOCATIONFIELD', 'dropdown', Location::class, 2);
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $location,
            'link_to_user'                 => $user,
            'root_items_id'                => $this->locations['root'],
            'location_depth'               => $location_depth,
            'display_type'                 => $display_type,
        ]);

        return ['metademand' => $metademands_id, 'user' => $user, 'location' => $location];
    }

    /**
     * As the user field calls it when the user changes (Dropdownobject::getLinkedUserDescriptors())
     */
    private function reload(array $form, int $locations_id_of_user): string
    {
        $this->updateItem(User::class, Session::getLoginUserID(), ['locations_id' => $locations_id_of_user]);
        unset($_SESSION['plugin_metademands']);

        $_POST = [
            'id_fielduser'   => $form['user'],
            'users_id'       => Session::getLoginUserID(),
            'metademands_id' => $form['metademand'],
        ];
        ob_start();
        try {
            include dirname(__DIR__) . '/ajax/ulocationUpdate.php';
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    public function testAllowedLocationsStartBelowTheRoot(): void
    {
        $this->assertNull(Dropdown::getAllowedLocationIds(0, 0));

        $allowed = Dropdown::getAllowedLocationIds($this->locations['root'], 0);
        $this->assertEqualsCanonicalizing([$this->locations['child'], $this->locations['grandchild']], $allowed);

        $allowed = Dropdown::getAllowedLocationIds($this->locations['root'], 1);
        $this->assertSame([$this->locations['child']], $allowed);
    }

    public function testCascadingListKeepsTheRoot(): void
    {
        $form = $this->createForm(Dropdown::SPLITTED_DISPLAY);

        $output = $this->reload($form, $this->locations['child']);
        $this->assertStringContainsString('MDCHILD', $output);
        $this->assertStringContainsString('MDGRANDCHILD', $output);
        $this->assertStringNotContainsString('MDOUTSIDE', $output);
        $this->assertStringContainsString('&quot;selected&quot;:&quot;' . $this->locations['child'] . '&quot;', $output);
    }

    public function testCascadingListKeepsTheMaximumDepth(): void
    {
        $form = $this->createForm(Dropdown::SPLITTED_DISPLAY, 1);

        $output = $this->reload($form, $this->locations['child']);
        $this->assertStringContainsString('MDCHILD', $output);
        $this->assertStringNotContainsString('MDGRANDCHILD', $output);
    }

    public function testLocationOfTheUserOutsideTheRootIsNotPreselected(): void
    {
        $form = $this->createForm(Dropdown::CLASSIC_DISPLAY);

        $output = $this->reload($form, $this->locations['outside']);
        $this->assertStringNotContainsString('MDOUTSIDE', $output);

        $output = $this->reload($form, $this->locations['child']);
        $this->assertStringContainsString('MDCHILD', $output);
    }

    public function testClassicListKeepsTheRoot(): void
    {
        $form = $this->createForm(Dropdown::CLASSIC_DISPLAY);
        $this->reload($form, $this->locations['child']);

        // The condition of the ajax dropdown, kept in session for ajax/getDropdownValue.php
        $this->assertContains(
            ['id' => [$this->locations['child'], $this->locations['grandchild']]],
            $_SESSION['glpicondition'] ?? [],
        );
    }
}
