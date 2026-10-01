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
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

/**
 * The fields a condition tests re-evaluate the conditions through a data-md-condition-*
 * marker read by public/scripts/wizard_form.js, instead of one generated script per field.
 */
class ConditionTriggerTest extends DbTestCase
{
    /**
     * @return array{Metademand, Field, Field}
     */
    private function createMetademand(int $show_rule): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Conditions',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'show_rule'        => $show_rule,
        ]);
        $fields = [];
        foreach (['Tested', 'Other'] as $order => $name) {
            $fields[] = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'type'                              => 'text',
                'name'                              => $name,
                'rank'                              => 1,
                'order'                             => $order + 1,
                'entities_id'                       => $this->getTestRootEntity(true),
            ], ['order']);
        }
        $this->createItem(Condition::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'plugin_metademands_fields_id'      => $fields[0]->getID(),
            'type'                              => 'text',
            'check_value'                       => 'Value',
            'show_logic'                        => Condition::SHOW_LOGIC_AND,
            'show_condition'                    => Condition::SHOW_CONDITION_EQ,
            'order'                             => 1,
        ], ['check_value']);

        return [$metademand, $fields[0], $fields[1]];
    }

    /**
     * @param class-string $class
     */
    private function renderTrigger(string $class, Metademand $metademand, Field $field, array $extra = []): string
    {
        ob_start();
        $class::checkConditions(
            $extra + $field->fields,
            Wizard::getConditionsParams($metademand),
        );

        return (string) ob_get_clean();
    }

    public function testTestedFieldEmitsTheMarkerWithoutScript(): void
    {
        $this->login();
        [$metademand, $tested] = $this->createMetademand(Condition::SHOW_RULE_HIDDEN);

        $html = $this->renderTrigger(Text::class, $metademand, $tested);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('data-md-condition-trigger="', $html);
        $this->assertStringContainsString('&quot;show_rule&quot;:&quot;' . Condition::SHOW_RULE_HIDDEN . '&quot;', $html);
        $this->assertStringContainsString('&quot;richtext_ids&quot;:[]', $html);
        $this->assertStringContainsString(
            'data-md-condition-source="{&quot;name&quot;:&quot;field[' . $tested->getID() . ']&quot;,&quot;match&quot;:&quot;prefix&quot;}"',
            $html,
        );
    }

    public function testRichTextFieldListensToTheEditors(): void
    {
        $this->login();
        [$metademand, $tested] = $this->createMetademand(Condition::SHOW_RULE_SHOWN);

        $html = $this->renderTrigger(Textarea::class, $metademand, $tested, ['use_richtext' => 1]);

        $this->assertStringContainsString('data-md-condition-source="{&quot;richtext&quot;:true}"', $html);
    }

    public function testNothingIsEmittedWithoutConditionOnTheField(): void
    {
        $this->login();
        [$metademand, $tested, $other] = $this->createMetademand(Condition::SHOW_RULE_HIDDEN);
        $this->assertSame('', $this->renderTrigger(Text::class, $metademand, $other));

        [$always, $always_tested] = $this->createMetademand(Condition::SHOW_RULE_ALWAYS);
        $this->assertSame('', $this->renderTrigger(Text::class, $always, $always_tested));
    }
}
