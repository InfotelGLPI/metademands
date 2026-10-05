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
use GlpiPlugin\Metademands\Fields\Basket;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Fields\Time;
use GlpiPlugin\Metademands\Metademand;

/**
 * Wizard templates of the fields (templates/fields/*): they get values only, escape them
 * and render the core widgets themselves; rich texts go through |safe_html.
 */
class FieldWizardDisplayTest extends DbTestCase
{
    private const MARKUP = '"><script>alert(1)</script>';

    private function render(string $template, array $context): string
    {
        return TemplateRenderer::getInstance()->render('@metademands/fields/' . $template . '.html.twig', $context);
    }

    private function assertNoInjectedScript(string $html): void
    {
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function testBasketSummaryEscapesTheFilledFieldsAndRendersTheButton(): void
    {
        $this->login();

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Basket',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $text = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'Reference',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $textarea = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'textarea',
            'name'                              => 'Comment',
            'rank'                              => 1,
            'order'                             => 2,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);

        $html = Basket::displayBasketSummary([
            'metademands_id' => $metademand->getID(),
            'field'          => [
                $text->getID()     => 'a &lt;b&gt;tag&lt;/b&gt;',
                $textarea->getID() => '<p><strong>kept</strong></p><script>alert(1)</script>',
            ],
        ]);

        // Plain text value escaped, rich text sanitized
        $this->assertStringContainsString('Reference', $html);
        $this->assertStringContainsString('<td>a &lt;b&gt;tag&lt;/b&gt;</td>', $html);
        $this->assertStringContainsString('<strong>kept</strong>', $html);
        $this->assertNoInjectedScript($html);

        // Static order button
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*name="send_order"[^>]*id="submitOrder"/', $html);
        $this->assertStringContainsString('ti ti-shopping-bag', $html);
    }

    public function testBasketRowCellsEscapeTheValueUnlessRich(): void
    {
        $html = $this->render('field_basket_row_cells', [
            'label'   => self::MARKUP,
            'label2'  => '',
            'value'   => self::MARKUP,
            'is_rich' => false,
        ]);
        $this->assertSame(2, substr_count($html, '&lt;script&gt;'));
        $this->assertNoInjectedScript($html);

        $html = $this->render('field_basket_row_cells', [
            'label'   => 'Label',
            'label2'  => 'End',
            'value'   => '<em>rich</em>' . self::MARKUP,
            'is_rich' => true,
        ]);
        $this->assertStringContainsString('<em>rich</em>', $html);
        $this->assertStringContainsString('<br><br><br>End', $html);
        $this->assertNoInjectedScript($html);
    }

    public function testBasketCellsRenderEachKind(): void
    {
        $this->login();

        $html = $this->render('field_basket', [
            'background_style' => '',
            'nb'               => 1,
            'search_id'        => 1,
            'headers'          => [['style' => '', 'label' => self::MARKUP]],
            'rows'             => [[
                ['t' => self::MARKUP],
                ['rich' => '<b>bold</b>' . self::MARKUP],
                ['number' => ['name' => 'quantity[3][7]', 'options' => ['display' => true, 'min' => 0, 'max' => 5, 'value' => 2]]],
                ['total_id' => 'plugin_metademands_totalrow42'],
                ['checkbox' => ['check' => 'field[3]', 'name' => "field[3]['x]", 'key' => "'x", 'is_checked' => true]],
            ]],
        ]);

        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('name="quantity[3][7]"', $html);
        $this->assertStringContainsString("id='plugin_metademands_totalrow42'", $html);
        $this->assertStringContainsString("key='&#039;x'", $html);
        $this->assertStringContainsString(' checked>', $html);
    }

    public function testCustomvalueFixedRendersCoreWidgetsAndClosesTheForm(): void
    {
        $this->login();

        $html = $this->render('field_customvalue_fixed', [
            'rows' => [[
                ['label' => self::MARKUP, 'widget' => ['type' => 'yesno', 'name' => 'custom[1]', 'value' => 1]],
                ['text' => 'Min', 'widget' => ['type' => 'number', 'name' => 'custom[min]', 'value' => 3, 'options' => ['min' => 0, 'max' => 10]]],
                ['hint' => self::MARKUP, 'colspan' => 2, 'widget' => ['type' => 'text', 'name' => 'custom[2]', 'value' => self::MARKUP]],
                ['widget' => ['type' => 'array', 'name' => 'custom[3]', 'value' => 'b', 'elements' => ['a' => 'A', 'b' => 'B']]],
            ]],
            'close_form' => true,
        ]);

        $this->assertNoInjectedScript($html);
        foreach (['custom[1]', 'custom[min]', 'custom[2]', 'custom[3]'] as $name) {
            $this->assertMatchesRegularExpression('/name=[\'"]' . preg_quote($name, '/') . '[\'"]/', $html);
        }
        $this->assertStringContainsString('colspan="2"', $html);
        $this->assertStringContainsString('name="update"', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    public function testCustomvalueDropdownmultipleEscapesTheItems(): void
    {
        $this->login();

        $html = $this->render('field_customvalue_dropdownmultiple', [
            'mode'                   => 'objects',
            'form_target'            => '/front/x.php',
            'hidden_fields'          => ['fields_id' => self::MARKUP],
            'type_name'              => 'Type',
            'default_value_label'    => 'Default',
            'display_dropdown_label' => 'Display',
            'select_all_label'       => 'All',
            'unselect_all_label'     => 'None',
            'items'                  => [['key' => 4, 'name' => self::MARKUP, 'default' => 1, 'checked' => true]],
        ]);

        $this->assertNoInjectedScript($html);
        $this->assertMatchesRegularExpression('/name=[\'"]default\[4\][\'"]/', $html);
        $this->assertStringContainsString('name="custom[4]" value="4" checked', $html);
        $this->assertStringContainsString('<a href="#" data-md-check-all="1">All</a>', $html);
        $this->assertStringContainsString('<a href="#" data-md-check-all="0">None</a>', $html);
    }

    public function testUploadDeleteLinkEncodesThePostedFields(): void
    {
        $this->login();

        $html = $this->render('field_upload', [
            'files'        => [['name' => self::MARKUP, 'post' => ['id' => self::MARKUP]]],
            'delete_url'   => '/plugins/metademands/front/wizard.form.php',
            'file_options' => null,
        ]);

        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('submitGetLink(', $html);
        $this->assertStringContainsString('delete_basket_file', $html);
    }

    public function testSwitchRangeMultiselectAndLocationCarryStatesAsBooleans(): void
    {
        $html = $this->render('field_switch', [
            'id' => 'sw1', 'name' => 'field[1]', 'is_checked' => true, 'value' => 2,
        ]);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString("checked='checked'", $html);
        $this->assertStringContainsString("data-md-switch='field[1]'", $html);
        $this->assertStringContainsString('type="hidden" name="field[1]" id="field[1]" value="2"', $html);

        $html = $this->render('field_range', [
            'field_id' => 'r1', 'id' => 1, 'name' => 'field[1]', 'value' => 4,
            'min' => 0, 'max' => 10, 'step' => 5, 'is_required' => true, 'minimal_mandatory' => 2,
            'show_all_ticks' => true,
        ]);
        $this->assertStringContainsString("required='required'", $html);
        $this->assertStringContainsString("minimal_mandatory='2'", $html);
        $this->assertStringContainsString('range.css', $html);
        $this->assertSame(3, substr_count($html, '<span>'));
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString("data-md-range-value='rangevalue_1'", $html);

        $html = $this->render('field_multiselect', [
            'id' => 7, 'name' => 'field[7][]', 'is_required' => false,
            'left_options'  => [['value' => 1, 'text' => self::MARKUP]],
            'right_options' => [['value' => 2, 'text' => 'B', 'selected' => true]],
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringNotContainsString('required', $html);
        $this->assertStringContainsString('selected value="2"', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('id="multiselect7" class=\'formCol\'', $html);
        $this->assertStringContainsString('data-md-multiselect="' . htmlescape(__('Search')) . '..."', $html);

        $html = $this->render('field_location_chained', [
            'name' => 'field[9]', 'id' => 'loc9', 'is_required' => true,
            'chained' => ['data' => ['Site' => [3 => self::MARKUP]], 'selected' => '3'],
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('jquery.chained.selects', $html);
        $this->assertStringContainsString('id="loc9-dropdown" required', $html);
        $this->assertSame(1, preg_match('/data-md-chained-locations="([^"]*)"/', $html, $matches));
        $this->assertSame(
            ['data' => ['Site' => ['3' => self::MARKUP]], 'selected' => '3', 'target' => 'loc9'],
            json_decode(html_entity_decode($matches[1], ENT_QUOTES), true),
        );
        $this->assertStringContainsString('<input type="hidden" name="field[9]" id="loc9">', $html);
    }

    public function testFreetableAndSignatureEscapeTheirData(): void
    {
        $html = $this->render('field_freetable', [
            'rand' => 3, 'background_color' => '', 'params' => [], 'autoadd' => false,
            'columns' => [['label' => 'Col', 'mandatory' => true, 'comment' => self::MARKUP]],
            'rows' => [], 'has_orderfollowup' => false,
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('class="form-help"', $html);

        $html = $this->render('field_signature', [
            'canvas_id' => 'c', 'field_id' => 1, 'metademands_id' => 2, 'is_mandatory' => 0,
            'save_id' => 's', 'clear_id' => 'cl', 'result_id' => 'r', 'hidden_id' => 'h',
            'add_url' => '/a', 'remove_url' => '/r', 'messages' => [], 'has_value' => false,
            'picture_url' => '', 'label_add' => self::MARKUP, 'label_clear' => 'Clear',
            'name' => 'field[1]', 'value' => self::MARKUP,
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('signature/js/signature_pad.umd', $html);
        $this->assertStringContainsString('signature/css/signature_pad.umd', $html);
    }

    public function testTitleAndInformationSanitizeTheRichTexts(): void
    {
        $html = $this->render('field_display_title', [
            'id' => 5, 'color' => '#000', 'color_rgba' => 'rgba(0,0,0,0.1)', 'has_icon' => false,
            'icon_is_fa' => false, 'icon' => '', 'label' => self::MARKUP, 'debug' => false,
            'has_label2' => true, 'label2' => '<b>l2</b>' . self::MARKUP, 'preview' => false,
            'config_url' => '', 'has_comment' => true, 'comment' => '<i>c</i>' . self::MARKUP,
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('data-bs-html="true"', $html);
        $this->assertStringContainsString('&lt;b&gt;l2&lt;/b&gt;', $html);
        $this->assertStringContainsString('<i>c</i>', $html);

        $html = $this->render('field_display_information', [
            'display_class' => 'alert-info', 'show_content' => true, 'has_icon' => false,
            'icon_is_fa' => false, 'icon' => '', 'color' => '#000', 'name' => self::MARKUP,
            'comment' => '<em>c</em>' . self::MARKUP, 'label2' => '', 'preview' => false, 'config_url' => '',
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('<em>c</em>', $html);
    }

    public function testValueToCheckCellAndUserDropdownWithoutUser(): void
    {
        $html = $this->render('field_value_to_check_cell', [
            'option_id' => self::MARKUP, 'with_check_type' => true, 'with_tech_group' => false, 'content' => '<select></select>',
        ]);
        $this->assertNoInjectedScript($html);
        $this->assertStringContainsString('data-md-check-type="1"', $html);
        $this->assertStringContainsString('data-md-tech-group="0"', $html);

        $html = $this->render('dropdownobject_user', [
            'tooltip_script' => '', 'wrapper_id' => '', 'widget_html' => '<select></select>',
            'update_script' => '', 'with_tooltip' => true, 'field_id' => 3, 'info_user' => null,
            'linked_text_fields' => [],
        ]);
        $this->assertStringContainsString('id="tooltip_user3"', $html);
        $this->assertStringNotContainsString('alert-info', $html);
    }

    public function testTimeFieldDescribesItsPicker(): void
    {
        $this->login();

        $html = Time::showTimeField('field[5]', [
            'value'    => self::MARKUP,
            'display'  => false,
            'rand'     => 12,
            'timestep' => 15,
        ]);

        $this->assertNoInjectedScript($html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame(1, preg_match('/id="showtime12" data-md-timepicker="([^"]*)"/', $html, $matches));
        $config = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertSame(15, $config['step']);
        $this->assertArrayHasKey('language', $config);
        $this->assertArrayHasKey('region', $config);
    }

    public function testRichTextareaDescribesItsEditor(): void
    {
        $this->login();

        $html = Textarea::textarea([
            'name'            => 'field[42]',
            'editor_id'       => 'field42',
            'value'           => self::MARKUP,
            'placeholder'     => '<b>Hint</b>' . self::MARKUP,
            'enable_richtext' => true,
            'enable_images'   => false,
            'required'        => true,
            'rows'            => 4,
            'display'         => false,
        ]);

        $this->assertNoInjectedScript($html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame(1, preg_match('/id=\'field42\'[^>]* required data-md-richtext="([^"]*)">/', $html, $matches));
        $config = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertSame(96, $config['height']);
        $this->assertFalse($config['readonly']);
        $this->assertStringContainsString(',img', $config['invalid_elements']);
        $this->assertNotContains('image', $config['plugins']);
        // The core editor adds autolink, this fork never did
        $this->assertNotContains('autolink', $config['plugins']);
        // Rich text comment, sanitized on the server side
        $this->assertStringStartsWith('<div id="placeholder"><b>Hint</b>', $config['placeholder']);
        $this->assertStringNotContainsString('<script', $config['placeholder']);
        $this->assertSame(__('The description field is mandatory', 'servicecatalog'), $config['mandatory_msg']);
    }
}
