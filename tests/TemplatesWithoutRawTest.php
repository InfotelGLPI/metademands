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
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\TicketField;
use GlpiPlugin\Metademands\Tools;

/**
 * Forms whose widgets used to be captured and injected with |raw: the templates now
 * call the widgets themselves and own their <form> and buttons.
 */
class TemplatesWithoutRawTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Without raw',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'show_rule'        => Condition::SHOW_RULE_HIDDEN,
        ]);
    }

    private function capture(callable $render): string
    {
        ob_start();
        $render();

        return (string) ob_get_clean();
    }

    public function testToolsActionsArePostForms(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Tools::$rightname] = ALLSTANDARDRIGHT;

        $html = $this->capture(fn() => Tools::showTools());

        $this->assertStringNotContainsString('submitGetLink', $html);
        $this->assertStringContainsString('name="change_global_status"', $html);
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
    }

    public function testExportFormsOwnTheirButtons(): void
    {
        $this->login();
        $metademand = $this->createMetademand();

        $html = $this->capture(fn() => Export::showExportFromMetademands($metademand->getID()));
        $this->assertStringContainsString('name="plugin_metademands_metademands_id" value="' . $metademand->getID() . '"', $html);
        $this->assertStringContainsString('name="exportMetademandsXML"', $html);
        $this->assertStringContainsString('name="exportMetademandsJSON"', $html);
        $this->assertStringContainsString('</form>', $html);

        $html = $this->capture(fn() => Export::showImportForm());
        $this->assertStringContainsString('name="import_file"', $html);
    }

    public function testConditionFormsRenderTheirDropdowns(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $metademand = $this->createMetademand();
        $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'Text',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);

        $html = $this->capture(fn() => (new Condition())->showForMetademand($metademand));
        foreach (['show_rule', 'show_logic', 'plugin_metademands_fields_id', 'order'] as $name) {
            $this->assertMatchesRegularExpression('/name=["\']' . $name . '["\']/', $html);
        }

        $html = $this->capture(fn() => (new Condition())->showForm(-1, ['parent' => $metademand]));
        foreach (['show_logic', 'plugin_metademands_fields_id', 'show_condition', 'order'] as $name) {
            $this->assertMatchesRegularExpression('/name=["\']' . $name . '["\']/', $html);
        }
    }

    public function testTicketFieldFormRendersTheRowBetweenHeaderAndButtons(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][TicketField::$rightname] = ALLSTANDARDRIGHT;
        $metademand  = $this->createMetademand();
        $ticketfield = $this->createItem(TicketField::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'num'                               => 1,
            'value'                             => 'Title',
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);

        $html = $this->capture(fn() => (new TicketField())->showForm($ticketfield->getID()));

        $this->assertStringContainsString('name="plugin_metademands_metademands_id" value="' . $metademand->getID() . '"', $html);
        $this->assertStringContainsString('show_massiveaction_field', $html);
        $this->assertLessThan(strpos($html, 'name="update"'), strpos($html, 'show_massiveaction_field'));
    }
}
