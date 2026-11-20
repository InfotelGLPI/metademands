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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Number;
use GlpiPlugin\Metademands\Metademand;
use Ticket;

/**
 * With "Hide the No and empty values of fields in the tickets", a number left to 0 is
 * hidden as not filled in. A mandatory number whose minimal mandatory value is 0 can only
 * hold 0 because it was chosen: it must stay in the ticket.
 */
class NumberZeroValueTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setValue(null, null);

        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), ['id' => 1, 'create_pdf' => 0, 'show_form_changes' => 0]);
        }

        $this->login('glpi', 'glpi');
    }

    /**
     * @return array{metademand: int, fields: array<string, int>}
     */
    private function createForm(): array
    {
        $metademands_id = $this->createItem(Metademand::class, [
            'name'               => 'Nombres à zéro',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
            'hide_no_field'      => 1,
        ])->getID();

        $fields = [];
        $order = 0;
        foreach (['MANDATORYNUMBER' => 1, 'OPTIONALNUMBER' => 0] as $name => $is_mandatory) {
            $fields[$name] = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademands_id,
                'type'        => 'number',
                'item'        => '',
                'name'        => $name,
                'rank'        => 1,
                'order'       => ++$order,
                'entities_id' => 0,
            ], ['item'])->getID();
            // Minimal count 0, maximal count 10, step 1, minimal mandatory 0
            $this->createItem(FieldParameter::class, [
                'plugin_metademands_fields_id' => $fields[$name],
                'is_mandatory'                 => $is_mandatory,
                'custom'                       => FieldParameter::_serialize(['0', '10', '1', '0']),
            ], ['custom']);
        }

        return ['metademand' => $metademands_id, 'fields' => $fields];
    }

    public function testZeroOfAMandatoryFieldIsAnAnswer(): void
    {
        $form = $this->createForm();

        $this->assertTrue(Number::isZeroAnswered($form['fields']['MANDATORYNUMBER']));
        $this->assertFalse(Number::isZeroAnswered($form['fields']['OPTIONALNUMBER']));
    }

    public function testMandatoryZeroStaysInTheTicket(): void
    {
        $form = $this->createForm();

        // As posted by the wizard: strings
        $result = Metademand::addObjects($form['metademand'], [
            'fields' => array_fill_keys(array_values($form['fields']), '0'),
        ]);
        $this->assertIsArray($result);

        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB((int) $result['id']));
        $content = (string) $ticket->fields['content'];

        $this->assertMatchesRegularExpression('/MANDATORYNUMBER<\/td><td[^>]*>0<\/td>/', $content);
        $this->assertStringNotContainsString('OPTIONALNUMBER', $content);
    }
}
