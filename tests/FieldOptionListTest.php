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
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;
use ITILValidationTemplate;

/**
 * Option list of a field (field_option_options.html.twig): the values are escaped by
 * Twig, and the modal is driven by data-md-option-* attributes instead of a generated
 * inline script.
 */
class FieldOptionListTest extends DbTestCase
{
    private const LABEL = '<b>Option</b>';

    /**
     * A checkbox field whose single custom value is bound to an option.
     *
     * @return array{Field, FieldOption}
     */
    private function createCheckboxOption(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Option list',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'checkbox',
            'name'                              => 'Checkbox',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);
        $custom = $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'name'                         => self::LABEL,
            'rank'                         => 0,
            'is_default'                   => 0,
        ], ['name']);
        // The input is stripped of its tags on save: store markup as legacy data would hold it
        global $DB;
        $DB->update(FieldCustomvalue::getTable(), ['name' => self::LABEL], ['id' => $custom->getID()]);
        $option = $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'check_value'                  => $custom->getID(),
        ], ['check_value']);

        return [$field, $option];
    }

    private function renderOptions(Field $field): string
    {
        ob_start();
        FieldOption::showOptions($field);

        return (string) ob_get_clean();
    }

    public function testValueToCheckIsPlainText(): void
    {
        $this->login();
        [$field, $option] = $this->createCheckboxOption();

        $params = $option->fields + [
            'type'          => 'checkbox',
            'item'          => '',
            'custom_values' => [['id' => $option->fields['check_value'], 'name' => self::LABEL]],
        ];
        $this->assertSame(self::LABEL, FieldOption::getValueToCheck($params));

        $params['check_type_value'] = 2;
        $params['check_value_regex'] = '/<a>/';
        $this->assertSame('/<a>/', FieldOption::getValueToCheck($params));
    }

    public function testListEscapesTheValueAndDrivesTheModalByAttributes(): void
    {
        $this->login();
        [$field, $option] = $this->createCheckboxOption();

        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Field::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderOptions($field);

        // The value to check is shown escaped, in its rule
        $this->assertMatchesRegularExpression('/<span class="md-rule-value"[^>]*>&lt;b&gt;Option&lt;\/b&gt;<\/span>/', $html);
        $this->assertStringNotContainsString(self::LABEL, $html);
        // The only scripts left are the ones of the core massive actions
        foreach (['showFieldOptionModal', 'addOption', 'reloadviewOption', 'viewEditOption'] as $generated) {
            $this->assertStringNotContainsString($generated, $html);
        }
        $this->assertMatchesRegularExpression('/data-md-option-open="[^"]+"\s+data-md-option-id="-1"/', $html);
        $this->assertMatchesRegularExpression(
            '/data-md-option-open="[^"]+"\s+data-md-option-id="' . $option->getID() . '"/',
            $html,
        );
        $this->assertStringContainsString('data-md-option-url="', $html);
        // The option form reloaded on a value change is a field option of this field
        $this->assertMatchesRegularExpression('/data-md-option-reload-params="[^"]*Field&quot;/', $html);
    }

    public function testReadOnlyListHasNoModalNorMassiveActions(): void
    {
        $this->login();
        [$field] = $this->createCheckboxOption();

        $_SESSION['glpiactiveprofile'][Field::$rightname] = READ;
        $html = $this->renderOptions($field);

        $this->assertStringContainsString('&lt;b&gt;Option&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('data-md-option-', $html);
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
    }
    /**
     * A radio field (ACCIDENT, INCIDENT, SUGGESTION) of block 1, a block 2 titled
     * "Second block" and a text field.
     *
     * @return array{Field, array<string, int>, Field}
     */
    private function createRadioForm(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Rules',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = fn(string $name, string $type, int $rank) => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => $type,
            'item'                              => $type === 'radio' ? 'radio' : '',
            'name'                              => $name,
            'rank'                              => $rank,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['item']);

        $radio  = $field('Kind', 'radio', 1);
        $values = [];
        foreach (['ACCIDENT', 'INCIDENT', 'SUGGESTION'] as $rank => $name) {
            $values[$name] = $this->createItem(FieldCustomvalue::class, [
                'plugin_metademands_fields_id' => $radio->getID(),
                'name'                         => $name,
                'rank'                         => $rank,
                'is_default'                   => 0,
            ])->getID();
        }
        $field('Second block', 'title-block', 2);
        $text = $field('Comment', 'text', 2);

        return [$radio, $values, $text];
    }

    /**
     * Option form of a field, as the modal loads it.
     */
    private function renderForm(Field $field, int $options_id = -1): string
    {
        ob_start();
        (new FieldOption())->showForm($options_id, ['parent' => $field]);

        return (string) ob_get_clean();
    }

    public function testRulesNameTheirBlocksAndListTheValuesWithoutOption(): void
    {
        $this->login();
        [$radio, $values] = $this->createRadioForm();
        $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $radio->getID(),
            'check_value'                  => $values['ACCIDENT'],
            'is_negated'                   => 1,
            'hidden_block'                 => 2,
        ], ['check_value']);

        $_SESSION['glpiactiveprofile'][Field::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderOptions($radio);

        // "≠ ACCIDENT → Block 2 · Second block"
        $this->assertMatchesRegularExpression('/<span class="badge bg-orange-lt md-rule-op"[^>]*>≠<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<span class="md-rule-value"[^>]*>ACCIDENT<\/span>/', $html);
        $this->assertStringContainsString('Block 2 · Second block', $html);
        // The option applies to INCIDENT and SUGGESTION: only ACCIDENT is left without option
        $this->assertSame(1, preg_match_all('/data-md-option-value="(\d+)"/', $html, $matches));
        $this->assertSame((string) $values['ACCIDENT'], $matches[1][0]);
    }

    public function testFormOffersTheNegationAndTheValidationTemplate(): void
    {
        $this->login();
        [$radio, $values, $text] = $this->createRadioForm();
        $_SESSION['glpiactiveprofile'][Field::$rightname] = ALLSTANDARDRIGHT;

        $html = $this->renderForm($radio);
        $this->assertMatchesRegularExpression('/<input type="radio" class="btn-check" name="is_negated" value="0"[^>]*checked>/', $html);
        $this->assertMatchesRegularExpression('/<input type="radio" class="btn-check" name="is_negated" value="1"/', $html);
        // The validation: its approver and its template, switched off on a new option
        $this->assertMatchesRegularExpression('/data-md-action="validation" aria-pressed="false"/', $html);
        $this->assertMatchesRegularExpression('/<fieldset[^>]*data-md-action-panel="validation"\s+disabled hidden>/', $html);
        $this->assertStringContainsString('name="itilvalidationtemplates_id"', $html);
        $this->assertMatchesRegularExpression(
            '/<fieldset data-md-action-reset="validation" hidden >\s*<input type="hidden" name="users_id_validate" value="0">\s*<input type="hidden" name="itilvalidationtemplates_id" value="0">/',
            $html,
        );
        // The settings of the two block actions are one card
        $this->assertStringNotContainsString('data-md-action="same_block"', $html);
        $this->assertStringContainsString('Block 2 · Second block', $html);

        // A text field has no values to exclude
        $html = $this->renderForm($text);
        $this->assertStringContainsString('<input type="hidden" name="is_negated" value="0">', $html);
        $this->assertStringNotContainsString('btn-check', $html);
    }

    public function testFormShowsTheActionsOfTheOptionSwitchedOn(): void
    {
        $this->login();
        [$radio, $values] = $this->createRadioForm();
        $_SESSION['glpiactiveprofile'][Field::$rightname] = ALLSTANDARDRIGHT;
        $template = $this->createItem(ITILValidationTemplate::class, [
            'name'        => 'Template',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $option = $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $radio->getID(),
            'check_value'                  => $values['INCIDENT'],
            'is_negated'                   => 1,
            'itilvalidationtemplates_id'   => $template->getID(),
        ], ['check_value']);

        $html = $this->renderForm($radio, $option->getID());
        $this->assertMatchesRegularExpression('/name="is_negated" value="1"[^>]*checked>/', $html);
        $this->assertMatchesRegularExpression('/data-md-action="validation" aria-pressed="true"/', $html);
        $this->assertMatchesRegularExpression('/<fieldset[^>]*data-md-action-panel="validation"\s+>/', $html);
        $this->assertStringContainsString('<fieldset data-md-action-reset="validation" hidden disabled>', $html);
        $this->assertMatchesRegularExpression('/data-md-action="task" aria-pressed="false"/', $html);

        // Reloaded after a change of the value: the actions switched on stay on
        $_POST = [
            'check_value'    => (string) $values['SUGGESTION'],
            'is_negated'     => '0',
            'active_actions' => '["task"]',
        ];
        try {
            $html = $this->renderForm($radio);
        } finally {
            $_POST = [];
        }
        $this->assertMatchesRegularExpression('/data-md-action="task" aria-pressed="true"/', $html);
        $this->assertMatchesRegularExpression('/name="is_negated" value="0"[^>]*checked>/', $html);
    }
}
