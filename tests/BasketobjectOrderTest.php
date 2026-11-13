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
use GlpiPlugin\Metademands\Basketobject;
use GlpiPlugin\Metademands\Basketobjecttype;
use GlpiPlugin\Metademands\Fields\Basket;
use GlpiPlugin\Metademands\Metademand;

/**
 * Display order of the references of the catalog in a basket field.
 */
class BasketobjectOrderTest extends DbTestCase
{
    private function createReference(int $types_id, string $name, int $rank): Basketobject
    {
        return $this->createItem(Basketobject::class, [
            'name'                                    => $name,
            'reference'                               => 'REF-' . $name,
            'plugin_metademands_basketobjecttypes_id' => $types_id,
            'rank'                                    => $rank,
        ]);
    }

    public function testTheBasketListsTheReferencesByDisplayOrderThenName(): void
    {
        $this->login();

        $type = $this->createItem(Basketobjecttype::class, ['name' => 'Ordered type']);
        $this->createReference($type->getID(), 'Charlie', 1);
        $this->createReference($type->getID(), 'Alpha', 2);
        $this->createReference($type->getID(), 'Bravo', 1);
        // Not ordered yet: first, as before the option existed
        $this->createReference($type->getID(), 'Delta', 0);

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Basket order',
            'entities_id'      => 0,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);

        ob_start();
        Basket::showWizardField([
            'id'                                => 1,
            'item'                              => $type->getID(),
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'custom_values'                     => '["0","0"]',
        ]);
        $html = (string) ob_get_clean();

        $positions = [];
        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            $positions[$name] = strpos($html, 'REF-' . $name);
            $this->assertNotFalse($positions[$name], $name . ' must be listed');
        }
        asort($positions);
        $this->assertSame(['Delta', 'Bravo', 'Charlie', 'Alpha'], array_keys($positions));
    }

    public function testReorderNumbersTheReferencesOfTheTypeFromOne(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Basketobject::$rightname] = ALLSTANDARDRIGHT;

        $type    = $this->createItem(Basketobjecttype::class, ['name' => 'Reorder type']);
        $other   = $this->createItem(Basketobjecttype::class, ['name' => 'Other type']);
        $alpha   = $this->createReference($type->getID(), 'Golf', 0);
        $bravo   = $this->createReference($type->getID(), 'Hotel', 0);
        $foreign = $this->createReference($other->getID(), 'India', 5);

        $this->assertTrue(Basketobject::reorder($type->getID(), [$bravo->getID(), $alpha->getID()]));
        $this->assertSame(
            [$bravo->getID(), $alpha->getID()],
            array_keys(Basketobject::getOrderedOfType($type->getID())),
        );
        $this->assertSame(1, (int) Basketobject::getOrderedOfType($type->getID())[$bravo->getID()]['rank']);

        // A reference of another type is left as it is
        $this->assertFalse(Basketobject::reorder($type->getID(), [$foreign->getID(), $alpha->getID()]));
        $this->assertTrue($foreign->getFromDB($foreign->getID()));
        $this->assertSame(5, (int) $foreign->fields['rank']);
        $this->assertTrue($alpha->getFromDB($alpha->getID()));
        $this->assertSame(1, (int) $alpha->fields['rank']);
    }

    public function testTheBasketOptionsListTheReferencesToReorder(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Basketobject::$rightname] = ALLSTANDARDRIGHT;

        $type = $this->createItem(Basketobjecttype::class, ['name' => 'Options type']);
        $this->createReference($type->getID(), 'Kilo', 2);
        $this->createReference($type->getID(), 'Juliet', 1);

        ob_start();
        Basket::showFieldCustomValues(['item' => $type->getID(), 'custom_values' => []]);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('data-md-basket-order', $html);
        $this->assertStringContainsString('data-md-types-id="' . $type->getID() . '"', $html);
        $this->assertLessThan(strpos($html, 'REF-Kilo'), strpos($html, 'REF-Juliet'));
        // No input: the options form around it is left untouched
        $zone = substr($html, strpos($html, 'data-md-basket-order'));
        $this->assertStringNotContainsString('<input', $zone);

        // Without the right to update the references: no zone
        $_SESSION['glpiactiveprofile'][Basketobject::$rightname] = READ;
        ob_start();
        Basket::showFieldCustomValues(['item' => $type->getID(), 'custom_values' => []]);
        $this->assertStringNotContainsString('data-md-basket-order', (string) ob_get_clean());
    }

    public function testTheDisplayOrderIsAPositiveInteger(): void
    {
        $this->login();

        $type      = $this->createItem(Basketobjecttype::class, ['name' => 'Rank type']);
        $reference = $this->createReference($type->getID(), 'Echo', 3);

        $this->assertTrue($reference->update(['id' => $reference->getID(), 'rank' => '-4']));
        $this->assertSame(0, (int) $reference->fields['rank']);

        $this->assertTrue($reference->update(['id' => $reference->getID(), 'rank' => '7']));
        $this->assertSame(7, (int) $reference->fields['rank']);
    }
}
