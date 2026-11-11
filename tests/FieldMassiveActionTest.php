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
use GlpiPlugin\Metademands\Fields\Yesno;
use GlpiPlugin\Metademands\Metademand;
use MassiveAction;

/**
 * Massive actions of the "Wizard creation" tab: display of the yes / no fields, mandatory or
 * optional fields. The fields they do not apply to are reported, not changed.
 */
class FieldMassiveActionTest extends DbTestCase
{
    /**
     * @return array<string, Field> fields by name
     */
    private function createFields(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Massive',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $fields = [];
        foreach (['text' => 'Text', 'title' => 'Title', 'yesno' => 'Yes no'] as $type => $name) {
            $fields[$type] = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'type'                              => $type,
                'item'                              => $type === 'yesno' ? 'yesno' : '',
                'name'                              => $name,
                'rank'                              => 1,
                'order'                             => count($fields) + 1,
                'entities_id'                       => $this->getTestRootEntity(true),
            ], ['order', 'item']);
            $this->createItem(FieldParameter::class, [
                'plugin_metademands_fields_id' => $fields[$type]->getID(),
                'is_mandatory'                 => 0,
                'display_type'                 => Yesno::CLASSIC_DISPLAY,
            ], ['display_type']);
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $input
     * @param int[]                $ids
     *
     * @return array<int, int> status of each field
     */
    private function process(string $action, array $input, array $ids): array
    {
        $done = [];
        $ma = $this->createStub(MassiveAction::class);
        $ma->method('getAction')->willReturn($action);
        $ma->method('getInput')->willReturn($input);
        $ma->method('itemDone')->willReturnCallback(function ($itemtype, $id, $status) use (&$done) {
            $done[$id] = $status;
        });
        Field::processMassiveActionsForOneItemtype($ma, new Field(), $ids);

        return $done;
    }

    private function parameter(Field $field, string $key): int
    {
        $parameter = new FieldParameter();
        $parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field->getID()]);

        return (int) $parameter->fields[$key];
    }

    public function testMandatoryIsSetOnTheFieldsThatCanBe(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $fields = $this->createFields();

        $done = $this->process('change_mandatory', ['is_mandatory' => 1], array_map(static fn($f) => $f->getID(), $fields));
        $this->assertSame(MassiveAction::ACTION_OK, $done[$fields['text']->getID()]);
        $this->assertSame(MassiveAction::ACTION_OK, $done[$fields['yesno']->getID()]);
        // A title cannot be mandatory
        $this->assertSame(MassiveAction::ACTION_KO, $done[$fields['title']->getID()]);
        $this->assertSame(1, $this->parameter($fields['text'], 'is_mandatory'));
        $this->assertSame(0, $this->parameter($fields['title'], 'is_mandatory'));

        $this->process('change_mandatory', ['is_mandatory' => 0], [$fields['text']->getID()]);
        $this->assertSame(0, $this->parameter($fields['text'], 'is_mandatory'));
    }

    public function testYesNoDisplayIsChangedOnTheYesNoOnly(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $fields = $this->createFields();

        $done = $this->process(
            'change_yesno_display',
            ['display_type' => Yesno::BUTTONS_DISPLAY],
            [$fields['yesno']->getID(), $fields['text']->getID()],
        );
        $this->assertSame(MassiveAction::ACTION_OK, $done[$fields['yesno']->getID()]);
        $this->assertSame(MassiveAction::ACTION_KO, $done[$fields['text']->getID()]);
        $this->assertSame(Yesno::BUTTONS_DISPLAY, $this->parameter($fields['yesno'], 'display_type'));
        $this->assertSame(Yesno::CLASSIC_DISPLAY, $this->parameter($fields['text'], 'display_type'));

        // Default value of the yes / no: "yes", stored raw as its form does
        $done = $this->process('change_yesno_default', ['custom' => 2], [$fields['yesno']->getID(), $fields['text']->getID()]);
        $this->assertSame(MassiveAction::ACTION_OK, $done[$fields['yesno']->getID()]);
        $this->assertSame(MassiveAction::ACTION_KO, $done[$fields['text']->getID()]);
        $parameter = new FieldParameter();
        $parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fields['yesno']->getID()]);
        $this->assertSame('2', (string) $parameter->fields['custom']);
        // Back to no default value
        $this->process('change_yesno_default', ['custom' => 0], [$fields['yesno']->getID()]);
        $parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fields['yesno']->getID()]);
        $this->assertSame('0', (string) $parameter->fields['custom']);

        // An unknown display is refused for every field
        $done = $this->process('change_yesno_display', ['display_type' => 9], [$fields['yesno']->getID()]);
        $this->assertSame(MassiveAction::ACTION_KO, $done[$fields['yesno']->getID()]);
        $this->assertSame(Yesno::BUTTONS_DISPLAY, $this->parameter($fields['yesno'], 'display_type'));
    }
}
