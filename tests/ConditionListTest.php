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
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use Location;

/**
 * Conditions of a metademand (condition_list.html.twig): the value to check comes back
 * as data escaped by Twig, and a row opens its edit form through data-md-subitem-*
 * attributes instead of one generated function per row.
 */
class ConditionListTest extends DbTestCase
{
    private const MARKUP = '<b>Value</b><script>alert(1)</script>';

    /**
     * @return array{Metademand, Condition, Condition}
     */
    private function createMetademandWithConditions(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Conditions',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $text = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'Text',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $dropdown = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'dropdown',
            'item'                              => Location::class,
            'name'                              => 'Location',
            'rank'                              => 1,
            'order'                             => 2,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $location = $this->createItem(Location::class, [
            'name'        => 'Room <i>1</i>',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $text_condition = $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'plugin_metademands_fields_id'      => $text->getID(),
            'type'                              => 'text',
            'check_value'                       => 'Value',
            'show_logic'                        => Condition::SHOW_LOGIC_AND,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'order'                             => 1,
        ], ['check_value']);
        $dropdown_condition = $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'plugin_metademands_fields_id'      => $dropdown->getID(),
            'type'                              => 'dropdown',
            'item'                              => Location::class,
            'items_id'                          => $location->getID(),
            'show_logic'                        => Condition::SHOW_LOGIC_OR,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'order'                             => 1,
        ]);
        // Store markup as legacy data could hold it, whatever the input sanitization
        global $DB;
        $DB->update(Condition::getTable(), ['check_value' => self::MARKUP], ['id' => $text_condition->getID()]);

        return [$metademand, $text_condition, $dropdown_condition];
    }

    private function renderConditions(Metademand $metademand): string
    {
        ob_start();
        Condition::listConditions($metademand);

        return (string) ob_get_clean();
    }

    public function testValuesAreEscapedAndRowsOpenByAttributes(): void
    {
        $this->login();
        [$metademand, $text_condition, $dropdown_condition] = $this->createMetademandWithConditions();

        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderConditions($metademand);

        // The text value is decoded from HTML then escaped, never output as markup
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>Value</b>', $html);
        // The dropdown value links to its item, with an escaped name
        $this->assertStringContainsString('Room &lt;i&gt;1&lt;/i&gt; (', $html);
        $this->assertStringNotContainsString('Room <i>1</i>', $html);
        $this->assertMatchesRegularExpression('/<a href="[^"]*location\.form\.php\?id=\d+"[^>]*data-md-subitem-noopen>/', $html);

        // One edit trigger per row, no generated function
        $this->assertStringNotContainsString('viewEditcondition', $html);
        // (the only inline handlers left are those of the core massive action helpers)
        $this->assertSame(
            substr_count($html, "onclick='modal_massiveaction_window")
            + substr_count($html, 'onclick= "if ( checkAsCheckboxes('),
            substr_count($html, 'onclick'),
        );
        foreach ([$text_condition, $dropdown_condition] as $condition) {
            $this->assertMatchesRegularExpression(
                '/data-md-subitem-params="[^"]*&quot;id&quot;:' . $condition->getID() . '[,}]/',
                $html,
            );
        }
        $this->assertStringContainsString('massiveaction', $html);
    }

    public function testReadOnlyListHasNoEditTriggerNorMassiveActions(): void
    {
        $this->login();
        [$metademand] = $this->createMetademandWithConditions();

        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = READ;
        $html = $this->renderConditions($metademand);

        $this->assertStringContainsString('Room &lt;i&gt;1&lt;/i&gt; (', $html);
        foreach (['data-md-subitem-target', 'massiveaction', '<form'] as $absent) {
            $this->assertStringNotContainsString($absent, $html);
        }
    }
}
