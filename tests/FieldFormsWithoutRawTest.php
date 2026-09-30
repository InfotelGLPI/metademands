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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Freetablefield;
use GlpiPlugin\Metademands\Metademand;

/**
 * Field, option, custom value, parameter and free table forms: their widgets used to be
 * captured and injected with |raw, the templates now call the core helpers themselves.
 */
class FieldFormsWithoutRawTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Field forms',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    private function createField(Metademand $metademand, string $type, string $item = ''): Field
    {
        return $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => $type,
            'item'                              => $item,
            'name'                              => 'Field',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
    }

    private function capture(callable $render): string
    {
        ob_start();
        try {
            $render();
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }

    private function grantRights(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $_SESSION['glpiactiveprofile'][Field::$rightname]      = ALLSTANDARDRIGHT;
    }

    private function assertHasName(string $name, string $html): void
    {
        $this->assertMatchesRegularExpression('/name=[\'"]' . preg_quote($name, '/') . '[\'"]/', $html);
    }

    public function testFieldFormsRenderTheirDropdowns(): void
    {
        $this->grantRights();
        $metademand = $this->createMetademand();
        $field      = $this->createField($metademand, 'dropdown', 'Location');

        $html = $this->capture(fn() => (new Field())->showForm($field->getID()));
        // The type of an existing field is fixed, the item may still change
        foreach (['type', 'rank', 'item', 'plugin_metademands_fields_id'] as $name) {
            $this->assertHasName($name, $html);
        }
        $this->assertStringContainsString('id="show_order"', $html);
        $this->assertStringContainsString('id="show_item"', $html);

        $html = $this->capture(fn() => (new Field())->showForm(-1, ['parent' => $metademand]));
        foreach (['type', 'rank', 'plugin_metademands_fields_id'] as $name) {
            $this->assertHasName($name, $html);
        }
        $this->assertMatchesRegularExpression('/id=[\'"]dropdown_type\d+[\'"]/', $html);

        $html = $this->capture(fn() => (new Field())->showExistingForm(-1, ['parent' => $metademand]));
        foreach (['type', 'rank', 'plugin_metademands_fields_id'] as $name) {
            $this->assertHasName($name, $html);
        }

        $html = $this->capture(fn() => Field::searchForm($metademand, ['type' => 'dropdown', 'item' => 'Location']));
        foreach (['block', 'type', 'item'] as $name) {
            $this->assertHasName($name, $html);
        }
    }

    public function testMassiveActionFormsRenderTheCoreWidgets(): void
    {
        $this->login();
        $renderer = TemplateRenderer::getInstance();

        $html = $renderer->render('@metademands/forms/massiveaction_field.html.twig', [
            'widget'       => 'color',
            'submit_label' => '<b>Post</b>',
            'stacked'      => true,
        ]);
        $this->assertHasName('color', $html);
        $this->assertStringContainsString('<br>', $html);
        $this->assertStringContainsString('name="massiveaction" value="&lt;b&gt;Post&lt;/b&gt;"', $html);
        $this->assertStringNotContainsString('<b>Post</b>', $html);

        $html = $renderer->render('@metademands/forms/massiveaction_field.html.twig', [
            'widget'       => 'icon',
            'submit_label' => 'Post',
        ]);
        $this->assertHasName('icon', $html);
        $this->assertStringContainsString('WebIconSelector', $html);
        $this->assertStringContainsString('&nbsp;', $html);

        $html = $renderer->render('@metademands/forms/massiveaction_field.html.twig', [
            'submit_label' => 'Validate',
        ]);
        $this->assertStringNotContainsString('<select', $html);
        $this->assertStringContainsString('name="massiveaction" value="Validate"', $html);
    }

    public function testParentValueKeepsTheSignatureButNotTheScripts(): void
    {
        $renderer = TemplateRenderer::getInstance();
        $value    = '<img src="data:image/png;base64,AAAA"><script>alert(1)</script>';

        $html = $renderer->render('@metademands/fields/field_parent_value.html.twig', [
            'inputs'  => [],
            'values'  => [$value],
            'is_html' => true,
        ]);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringNotContainsString('<script', $html);

        $html = $renderer->render('@metademands/fields/field_parent_value.html.twig', [
            'inputs'  => [],
            'values'  => [$value],
            'is_html' => false,
        ]);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function testOptionWidgetsAreRenderedByTheTemplates(): void
    {
        $this->login();

        $html = $this->capture(fn() => FieldOption::showRegexInput('"><script>alert(1)</script>'));
        $this->assertStringContainsString('name="check_value"', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);

        $html = $this->capture(fn() => FieldOption::showRegexDropdown(2, 7));
        $this->assertHasName('check_type_value', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]2[\'"][^>]*selected/', $html);

        $html = TemplateRenderer::getInstance()->render(
            '@metademands/forms/field_option_parent_field_row.html.twig',
            ['parent_fields' => [3 => '<b>Parent</b>']],
        );
        $this->assertHasName('parent_field_id', $html);
        $this->assertStringContainsString('name="check_value" value="0"', $html);
        $this->assertStringNotContainsString('<b>Parent</b>', $html);
    }

    public function testLinkBlocksPrintTheirWidgets(): void
    {
        $this->grantRights();
        $metademand = $this->createMetademand();
        $field      = $this->createField($metademand, 'radio', 'radio');

        $html = FieldOption::showLinkHtml($field->getID(), [
            'type'                              => 'radio',
            'check_type_value'                  => 1,
            'check_value'                       => 1,
            'plugin_metademands_fields_id'      => $field->getID(),
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'plugin_metademands_tasks_id'       => 0,
            'fields_link'                       => 0,
            'hidden_link'                       => 0,
            'hidden_block'                      => 0,
            'hidden_block_same_block'           => 0,
            'childs_blocks'                     => '[]',
            'users_id_validate'                 => 0,
            'checkbox_id'                       => 0,
            'checkbox_value'                    => 0,
            'assign_tech_group'                 => [],
        ]);

        foreach (['plugin_metademands_tasks_id', 'fields_link', 'hidden_link', 'hidden_block', 'hidden_block_same_block', 'users_id_validate'] as $name) {
            $this->assertHasName($name, $html);
        }
        $this->assertStringNotContainsString('<table', $html);
    }

    public function testCustomValuesOfferToFixTheRanksWithANativeForm(): void
    {
        $this->grantRights();
        $metademand = $this->createMetademand();
        $field      = $this->createField($metademand, 'radio', 'radio');
        foreach ([1, 3] as $rank) {
            $this->createItem(FieldCustomvalue::class, [
                'plugin_metademands_fields_id' => $field->getID(),
                'name'                         => 'Choice ' . $rank,
                'rank'                         => $rank,
                'is_default'                   => 0,
            ]);
        }
        $field->getFromDB($field->getID());

        $html = $this->capture(fn() => FieldCustomvalue::showFieldCustomValues(Field::getAllParamsFromField($field)));

        $this->assertStringContainsString('name="fixranks"', $html);
        $this->assertStringContainsString('name="plugin_metademands_fields_id" value="' . $field->getID() . '"', $html);
        $this->assertStringContainsString('action="' . FieldCustomvalue::getFormURL() . '"', $html);
        $this->assertStringContainsString('name="_glpi_csrf_token"', $html);
        $this->assertStringContainsString('Choice 3', $html);
    }

    public function testParameterAndFreetableFormsPrintTheirBody(): void
    {
        $this->grantRights();
        $metademand = $this->createMetademand();
        $field      = $this->createField($metademand, 'text');

        $html = $this->capture(fn() => (new FieldParameter())->showParameterForm(-1, ['parent' => $field]));
        $this->assertHasName('is_mandatory', $html);
        $this->assertStringNotContainsString('field_parameters_html', $html);

        $freetable = $this->createField($metademand, 'freetable');
        $html = $this->capture(fn() => (new Freetablefield())->showFieldsForm(-1, ['parent' => $freetable]));
        $this->assertStringContainsString('name="plugin_metademands_fields_id" value="' . $freetable->getID() . '"', $html);
        $this->assertStringContainsString('name="type" value="freetable"', $html);
        $this->assertMatchesRegularExpression('/<i class="ti ti-info-circle" data-bs-toggle="tooltip" title="\(6 fields maximum\)"><\/i>/', $html);
        $this->assertStringContainsString('data-md-freetablefield-add="' . $freetable->getID() . '"', $html);
    }
}
