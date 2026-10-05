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
use GlpiPlugin\Metademands\TicketField;

/**
 * Predefined ticket fields of a metademand (ticketfield_list.html.twig): the value
 * rendered by getValueToDisplay() is sanitized, the massive actions are called from
 * the template.
 */
class TicketFieldListTest extends DbTestCase
{
    /**
     * A metademand predefining the ticket title (search option 1) with a script in it.
     *
     * @return array{Metademand, TicketField}
     */
    private function createMetademandWithTicketField(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Ticket field list',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $ticketfield = $this->createItem(TicketField::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'num'                               => 1,
            'value'                             => 'Title',
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);
        // Store markup as legacy data could hold it, whatever the input sanitization
        global $DB;
        $DB->update(
            TicketField::getTable(),
            ['value' => '<b>Title</b><script>alert(1)</script>'],
            ['id' => $ticketfield->getID()],
        );

        return [$metademand, $ticketfield];
    }

    private function renderTicketFields(Metademand $metademand): string
    {
        ob_start();
        TicketField::displayTabContentForItem($metademand);

        return (string) ob_get_clean();
    }

    public function testListSanitizesTheValueAndRendersTheMassiveActions(): void
    {
        $this->login();
        [$metademand, $ticketfield] = $this->createMetademandWithTicketField();

        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderTicketFields($metademand);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('Title', $html);
        $this->assertStringContainsString('data-md-subitem-params=', $html);
        $this->assertStringContainsString('massiveaction', $html);
        $this->assertStringContainsString('item[' . TicketField::class . '][' . $ticketfield->getID() . ']', $html);

        // The massive action bar scans the checkboxes under '#' + container: a namespace
        // separator in the id is read as a selector escape, and nothing is selected
        $this->assertSame(1, preg_match("/<form name='(mass[^']*)' id='\\1'/", $html, $matches));
        $this->assertStringNotContainsString('\\', $matches[1]);
        $this->assertStringContainsString("checkAsCheckboxes(this, '" . $matches[1] . "'", $html);
    }

    public function testReadOnlyListHasNoFormNorMassiveActions(): void
    {
        $this->login();
        [$metademand] = $this->createMetademandWithTicketField();

        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = READ | CREATE;
        $html = $this->renderTicketFields($metademand);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        foreach (['data-md-subitem-params', 'massiveaction', 'template_sync'] as $absent) {
            $this->assertStringNotContainsString($absent, $html);
        }
    }
}
