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
use GlpiPlugin\Metademands\Metademand;

/**
 * The field form only takes back the input of an add or update it refused
 * (Field::checkMandatoryFields()): the "new field" modal used to open with the data of the
 * last field added anywhere (import, duplication, parameters...).
 */
class FieldSavedInputTest extends DbTestCase
{
    private const SESSION_KEY = 'glpi_plugin_metademands_fields';

    public function setUp(): void
    {
        parent::setUp();

        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $_SESSION['glpiactiveprofile'][Field::$rightname]      = ALLSTANDARDRIGHT;
        unset($_SESSION[self::SESSION_KEY]);
    }

    private function createMetademand(string $name): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => $name,
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    private function newFieldForm(Metademand $metademand): string
    {
        ob_start();
        try {
            (new Field())->showForm(-1, ['parent' => $metademand]);
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }

    /**
     * An add refused for a missing type, the label posted being KEPTLABEL
     */
    private function refuseAdd(Metademand $metademand): void
    {
        $this->assertFalse((new Field())->add([
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => '',
            'name'                              => 'KEPTLABEL',
            'rank'                              => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ]));
        $this->hasSessionMessages(ERROR, ['Mandatory fields are not filled. Please correct: Type']);
    }

    public function testAcceptedInputIsNotKept(): void
    {
        $metademand = $this->createMetademand('Saved input');
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'LASTFIELD',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $parameter = $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $field->getID(),
        ]);
        $this->updateItem(FieldParameter::class, $parameter->getID(), ['is_mandatory' => 1]);
        $this->updateItem(Field::class, $field->getID(), ['name' => 'RENAMED']);

        $this->assertArrayNotHasKey(self::SESSION_KEY, $_SESSION);
        // The label input stays empty (the existing field is still listed in "display after")
        $this->assertStringContainsString('name="name" class="form-control" value=""', $this->newFieldForm($metademand));
    }

    public function testRefusedAddRefillsTheNewFieldFormOnce(): void
    {
        $metademand = $this->createMetademand('Saved input');
        $this->refuseAdd($metademand);

        $this->assertSame('KEPTLABEL', $_SESSION[self::SESSION_KEY][0]['name']);
        $this->assertStringContainsString('name="name" class="form-control" value="KEPTLABEL"', $this->newFieldForm($metademand));
        $this->assertArrayNotHasKey(self::SESSION_KEY, $_SESSION);
        $this->assertStringContainsString('name="name" class="form-control" value=""', $this->newFieldForm($metademand));
    }

    public function testRefusedAddIsNotGivenToAnotherMetademand(): void
    {
        $refused = $this->createMetademand('Refused');
        $other   = $this->createMetademand('Other');
        $this->refuseAdd($refused);

        $this->assertStringContainsString('name="name" class="form-control" value=""', $this->newFieldForm($other));
        $this->assertArrayNotHasKey(self::SESSION_KEY, $_SESSION);
    }
}
