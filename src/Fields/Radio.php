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

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\FieldCustomvalue;
use Html;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\MetademandTask;
use Session;

/**
 * Radio Class
 *
 **/
class Radio extends CommonDBTM
{
    public const CLASSIC_DISPLAY = 0;
    public const BLOCK_DISPLAY = 1;

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Radio button', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (empty($data['custom_values'])) {
            return;
        }

        $custom_values = $data['custom_values'];
        $inline = "";
        if ($data['row_display'] == 1) {
            $inline = 'form-check-inline';
        }
        $is_block = ($data["display_type"] == self::BLOCK_DISPLAY);

        $options = [];
        $scripts = "";

        if (count($custom_values) > 0) {
            foreach ($custom_values as $key => $label) {
                $is_checked = false;

                if (empty($value) && isset($label['is_default']) && $on_order == false) {
                    $is_checked = ($label['is_default'] == 1);
                }
                if (isset($value) && $value == $key) {
                    $is_checked = true;
                }

                // Option label / comment / icon come from user-supplied custom-value data stored
                // raw (GLPI 10+): passed raw to the template so {{ }} auto-escapes them (label/icon),
                // the comment is sanitized here (getSafeHtml) and escaped or shown by |safe_html.
                if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                    $name = $label['name'];
                }

                $has_comment = isset($label['comment']) && !empty($label['comment']);
                $comment_html = '';
                $has_icon = false;
                $icon = '';
                $icon_is_fa = false;

                if ($has_comment) {
                    if (empty(
                        $comment = Field::displayCustomvaluesField(
                            $data['id'],
                            $key,
                            "comment",
                        )
                    )) {
                        $comment = $label['comment'];
                    }
                    $comment_html = RichText::getSafeHtml($comment);
                }

                if ($is_block) {
                    $icon = $label['icon'];
                    if (empty($label['icon'])) {
                        $icon = $data['icon'];
                    }
                    $has_icon = !empty($icon);
                    $icon_is_fa = str_contains((string) $icon, 'fa-');
                }

                $options[] = [
                    'key'                  => $key,
                    'name'                 => $name,
                    'is_checked'           => $is_checked,
                    'has_comment'          => $has_comment,
                    'comment_html'         => $comment_html,
                    'has_icon'             => $has_icon,
                    'icon'                 => (string) $icon,
                    'icon_is_fa'           => $icon_is_fa,
                ];

                // The per-option childs_blocks toggle stays an inline scriptBlock (emitted in PHP
                // after the template render) rather than living in the Twig template.
                $childs_blocks = [];
                $fieldopt = new FieldOption();
                if ($opts = $fieldopt->find(
                    ["plugin_metademands_fields_id" => $data['id'], "check_value" => $key],
                )) {
                    foreach ($opts as $opt) {
                        if (!empty($opt['childs_blocks'])) {
                            $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                        }
                    }
                }
                if (!empty($childs_blocks)) {
                    $id = $data['id'];
                    $blockScript = "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                    $blockScript .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').is(':checked')) { ";

                    foreach ($childs_blocks as $customvalue => $childs) {
                        if ($customvalue != $key) {
                            // $childs peut être [1,2,3] (plat) ou ['val'=>[1,2]] (associatif)
                            $block_ids = [];
                            foreach ((array) $childs as $entry) {
                                if (is_array($entry)) {
                                    foreach ($entry as $bid) {
                                        $block_ids[] = (int) $bid;
                                    }
                                } else {
                                    $block_ids[] = (int) $entry;
                                }
                            }
                            foreach ($block_ids as $v) {
                                $blockScript .= "sessionStorage.setItem('hiddenbloc$v', $v);";
                                $blockScript .= FieldOption::resetMandatoryBlockFields($namefield);
                                $blockScript .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                            }
                        }
                    }
                    $blockScript .= "}";
                    $blockScript .= "});";

                    $scripts .= Html::scriptBlock($blockScript);
                }
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_radio.html.twig', [
            'is_block'    => $is_block,
            'is_required' => $data['is_mandatory'] == 1,
            'inline'      => $inline,
            'namefield'   => $namefield,
            'id'          => $data['id'],
            'options'     => $options,
        ]);

        echo $scripts;
    }

    public static function showFieldCustomValues($params)
    {
        FieldCustomvalue::showList(FieldCustomvalue::getListContext($params, true, true, true));
    }

    public static function showFieldParameters($params): string
    {
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::BLOCK_DISPLAY] = __("Block display", "metademands");
        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_checkbox_radio.html.twig',
            [
                'display_type'  => $params['display_type'],
                'display_types' => $disp,
            ],
        );
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        ob_start();
        self::showValueToCheck($fieldoption, $params);
        $cell_content = ob_get_clean();

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => false,
            'with_tech_group' => true,
            'content'         => $cell_content,
        ];

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => '',
                'label'             => __('Value to check', 'metademands'),
                'label_colspan'     => 2,
                'regex_html'        => '',
                'valuetocheck'      => $valuetocheck,
                'link_html'         => $link_html,
            ],
        );
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        \Dropdown::showFromArray(
            "check_value",
            $elements,
            ['value' => $params['check_value'], 'used' => $already_used],
        );
    }

    public static function showParamsValueToCheck($params): string
    {
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        return $elements[$params['check_value']] ?? "";
    }

    /**
     * @param array $value
     * @param array $fields
     * @return array
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && ($fields['value'] === null || $fields['value'] === '')) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (empty($value) && $value != 0) {
            return false;
        } elseif ($check_value != $value) {
            return false;
        }
        return true;
    }

    public static function fieldsMandatoryScript($data)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";
        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsMandatoryScript-radio $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            //specific

            if (isset($data['value'])) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {

                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                        }
                    } else {
                        $values = $data['value'];
                        $pre_onchange .= "$('[id=\"field[" . $id . "][" . $values . "]\"]').prop('checked', true).trigger('change');";
                    }

                } else {
                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                        }
                    }
                }
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = [];
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    $onchange .= "if ($fields_link in tohide) {
                        } else {
                            tohide[$fields_link] = true;
                        }
                        if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$fields_link] = false;
                        }";
                }
            }
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        if (isset($data['value']) && is_array($data['value'])) {
                            $values = $data['value'];
                            foreach ($values as $value) {
                                if ($idc == $value) {
                                    $display[] = $fields_link;
                                }
                            }
                        }
                    } else {
                        if (isset($data['value'])) {
                            $values = $data['value'];
                            if ($idc == $values) {
                                $display[] = $fields_link;
                            }
                        }
                    }

                    $onchange .= "$.each( tohide, function( key, value ) {
                                if (value == true) {
                                    var id = '#metademands_wizard_red'+ key;
                                    $(id).html('');
                                    sessionStorage.setItem('hiddenlink$name', key);
                                    " . FieldOption::resetMandatoryFieldsByField($name) . "
                                    $('[name =\"field['+key+']\"]').removeAttr('required');
                                    $('[name =\"field['+key+'-2]\"]').removeAttr('required');
                                } else {
                                     var id = '#metademands_wizard_red'+ key;
                                     var fieldid = 'field'+ key;
                                     $(id).html('*');
                                     $('[name =\"field[' + key + ']\"]').attr('required', 'required');
                                     $('[name =\"field[' + key + '-2]\"]').attr('required', 'required');
                                     //Special case Upload field
                                          sessionStorage.setItem('mandatoryfile$name', key);
                                         " . FieldOption::checkMandatoryFile($fields_link, $name) . "
                                }
                            });";
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= FieldOption::setMandatoryFieldsByField($id, $see);
                }
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function taskScript($data)
    {
        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('taskScript-radio $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {

                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $script2 .= "$('[name=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true);";
                        }
                    } else {
                        $values = $data['value'];
                        $script2 .= "$('[name=\"field[" . $id . "][" . $values . "]\"]').prop('checked', true);";
                    }

                } else {
                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $script2 .= "$('[name=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true);";
                        }
                    }
                }
            }


            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    if ($tasks_id) {
                        if (MetademandTask::setUsedTask($tasks_id, 0)) {
                            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                            $script .= "plugin_metademands_wizard_setNextBtnTitle('post')";
                            $script .= "});";
                        }
                    }
                }
            }

            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $script .= "var tohide = {};";
            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    $script .= "if ($tasks_id in tohide) {
                        } else {
                            tohide[$tasks_id] = true;
                        }
                        if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$tasks_id] = false;
                        }";

                    $script .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                           $.ajax({
                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                type: 'POST',
                                    type: 'POST',
                                    data: { tasks_id: $tasks_id,
                                      used: 0 },
                                    success: function(response){
                                       if (response != 1) {
                                           plugin_metademands_wizard_setNextBtnTitle('post')
                                       }
                                    },
                             });
                        } else {
                             $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                             type: 'POST',
                                type: 'POST',
                                dataType: 'text',
                                data: { tasks_id: $tasks_id,
                                  used: 1 },
                                success: function(response){
                                   if (response != 1) {
                                       plugin_metademands_wizard_setNextBtnTitle('next')
                                   }
                                },
                             });
                        }
                    });";
                }
            }
            $script .= "});";

            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['plugin_metademands_tasks_id'] as $tasks_id) {
                    if (isset($data['custom_values'])
                        && is_array($data['custom_values'])
                        && count($data['custom_values']) > 0) {
                        $custom_values = $data['custom_values'];
                        foreach ($custom_values as $k => $custom_value) {
                            if ($custom_value['is_default'] == 1) {
                                if ($idc == $k) {
                                    if (MetademandTask::setUsedTask($tasks_id, 1)) {
                                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                                        $script .= "plugin_metademands_wizard_setNextBtnTitle('next')";
                                        $script .= "});";
                                    }
                                } else {
                                    if (MetademandTask::setUsedTask($tasks_id, 0)) {
                                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                                        $script .= "plugin_metademands_wizard_setNextBtnTitle('post')";
                                        $script .= "});";
                                    }
                                }
                            }
                        }
                    }
                }
            }

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    public static function fieldsHiddenScript($data)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";
        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-radio $id');";
        }

        //add childs by idc
        $childs_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $child) {
                                $childs_by_checkvalue[$idc][] = $child;
                            }
                        }
                    }
                }
            }
        }

        if (count($check_values) > 0) {
            //Initialize default value - force change after onchange fonction
            if (isset($data['custom_values'])
                && is_array($data['custom_values'])
                && count($data['custom_values']) > 0) {
                $custom_values = $data['custom_values'];
                foreach ($custom_values as $k => $custom_value) {
                    if ($custom_value['is_default'] == 1) {
                        $post_onchange .= "$('[id=\"field[$id][$k]\"]').prop('checked', true).trigger('change');";
                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                    $('[id-field =\"field" . $hidden_link . "-2\"]').hide();";
                }
            }

            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                        }
                    } else {
                        $values = $data['value'];
                        $pre_onchange .= "$('[id=\"field[" . $id . "][" . $values . "]\"]').prop('checked', true).trigger('change');";
                    }
                } else {
                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                        }
                    }
                }
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = [];
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $onchange .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$hidden_link] = false;
                        }";
                }
            }

            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        if (isset($data['value']) && is_array($data['value'])) {
                            $values = $data['value'];
                            foreach ($values as $value) {
                                if ($idc == $value) {
                                    $display[] = $hidden_link;
                                }
                            }
                        }
                    } else {
                        if (isset($data['value'])) {
                            $values = $data['value'];
                            if ($idc == $values) {
                                $display[] = $hidden_link;
                            }
                        }
                    }

                    $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            $('[id-field =\"field'+key+'-2\"]').hide();
                            sessionStorage.setItem('hiddenlink$name', key);
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                            $('[name =\"field['+key+'-2]\"]').removeAttr('required');
                            " . FieldOption::resetMandatoryFieldsByFieldForHidden($name);
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                            $('[bloc-id =\"subbloc" . $childs . "\"]').hide();
                                            if (document.getElementById('ablock" . $childs . "'))
                                            document.getElementById('ablock" . $childs . "').style.display = 'none';";
                                }
                            }
                        }
                    }
                    $onchange .= "} else {
                            $('[id-field =\"field'+key+'\"]').show();
                            $('[id-field =\"field'+key+'-2\"]').show();
                        }
                    });";
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= "$('[id-field =\"field" . $see . "\"]').show();";
                    $pre_onchange .= "$('[id-field =\"field" . $see . "-2\"]').show();";
                    $pre_onchange .= FieldOption::setMandatoryFieldsByField($id, $see);
                }
            }
            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";

        //add childs by idc
        $childs_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $child) {
                                $childs_by_checkvalue[$idc][] = $child;
                            }
                        }
                    }
                }
            }
        }

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('blocksHiddenScript-radio $id');";
        }

        if (count($check_values) > 0) {
            //Initialize default value - force change after onchange fonction
            foreach ($check_values as $idc => $check_value) {
                //Default values
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && $custom_value['is_default'] == 1) {
                            $post_onchange .= "$('[name=\"$name\"]').prop('checked', true).trigger('change');";

                            if (is_array($childs_by_checkvalue)) {
                                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                    if ($idc == $k) {
                                        foreach ($childs_blocks as $childs) {
                                            $options = getAllDataFromTable(
                                                'glpi_plugin_metademands_fieldoptions',
                                                ['hidden_block' => $childs],
                                            );
                                            if (count($options) == 0) {
                                                $post_onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                                                document.getElementById('ablock" . $childs . "').style.display = 'block';
                                                                $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                             " . FieldOption::setMandatoryBlockFields(
                                                    $metaid,
                                                    $childs,
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //by default - hide all
            $pre_onchange .= FieldOption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $pre_onchange .= FieldOption::emptyAllblockbyDefault($check_values);
            }

            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                        }
                    } else {
                        $values = $data['value'];
                        $pre_onchange .= "$('[id=\"field[" . $id . "][" . $values . "]\"]').prop('checked', true).trigger('change');";
                    }
                } else {
                    if (is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                        }
                    }
                }
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";

            $display = [];
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_block'] as $hidden_block) {
                    $onchange .= "if ($hidden_block in tohide) {

                      } else {
                        tohide[$hidden_block] = true;
                      }
                    if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0  || $idc == -1)) {
                        tohide[$hidden_block] = false;
                    }";

                    $onchange .= "$.each( tohide, function( key, value ) {
                    if (value == true) {
                       var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'none';
                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
                        $('[bloc-id =\"subbloc'+ key +'\"]').hide();
                        sessionStorage.setItem('hiddenbloc$name', key);
                        " . FieldOption::setEmptyBlockFields($name) . "";
                    $hidden = FieldOption::resetMandatoryBlockFields($name);
                    $onchange .= "$hidden";
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'none';
                                $('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                $('[bloc-id =\"subbloc" . $childs . "\"]').hide();";
                                }
                            }
                        }
                    }
                    $onchange .= "} else {
                        var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'block';
                        $('[bloc-id =\"bloc'+ key +'\"]').show();
                        $('[bloc-id =\"subbloc'+ key +'\"]').show();
                         sessionStorage.setItem('showbloc$name', key);
                        ";

                    $hidden = FieldOption::setMandatoryBlockFields($metaid, $hidden_block);

                    $onchange .= "$hidden";
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $options = getAllDataFromTable(
                                        'glpi_plugin_metademands_fieldoptions',
                                        ['hidden_block' => $childs],
                                    );
                                    if (count($options) == 0) {
                                        $onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'block';
                                $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                $('[bloc-id =\"subbloc" . $childs . "\"]').show();";
                                    }
                                }
                            }
                        }
                    }
                    $onchange .= "}
                });
          ";

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        if (isset($data['value']) && is_array($data['value'])) {
                            $values = $data['value'];
                            foreach ($values as $value) {
                                if ($idc == $value) {
                                    $display[] = $hidden_block;
                                }
                            }
                        }
                    } else {
                        if (isset($data['value'])) {
                            $values = $data['value'];
                            if ($idc == $values) {
                                $display[] = $hidden_block;
                            }
                        }
                    }

                    if ($data["item"] == "ITILCategory_Metademands") {
                        if (isset($_GET['itilcategories_id']) && $idc == (int) $_GET['itilcategories_id']) {
                            $pre_onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                        $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();
                          " . FieldOption::setMandatoryBlockFields($metaid, $hidden_block);
                        }
                    }
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= "if (document.getElementById('ablock" . $see . "'))
                    document.getElementById('ablock" . $see . "').style.display = 'block';
                    $('[bloc-id =\"bloc" . $see . "\"]').show();
                    $('[bloc-id =\"subbloc" . $see . "\"]').show();";
                }
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function checkConditions($data, $metaparams)
    {
        $use_condition = $metaparams['use_condition'] ?? '';
        $show_rule     = $metaparams['show_rule'] ?? '';
        $show_button   = $metaparams['show_button'] ?? '';
        $use_richtext  = $metaparams['use_richtext'] ?? '';
        $richtext_id   = $metaparams['richtext_id'] ?? 0;

        $conditions = Condition::conditionsTab($data['plugin_metademands_metademands_id']);
        $condition_fields = [];
        foreach ($conditions as $cid => $condition) {
            $condition_fields[] = $condition['plugin_metademands_fields_id'];
        }

        if ($show_rule != Condition::SHOW_RULE_ALWAYS && in_array($data['id'], $condition_fields)) {
            $root_doc = PLUGIN_METADEMANDS_WEBDIR;
            $onchange = "window.metademandconditionsparams = {};
                        metademandconditionsparams.use_condition = '$use_condition';
                        metademandconditionsparams.show_rule = '$show_rule';
                        metademandconditionsparams.show_button = '$show_button';
                        metademandconditionsparams.use_richtext = '$use_richtext';
                        metademandconditionsparams.richtext_ids = {$richtext_id};
                        metademandconditionsparams.root_doc = '$root_doc';";

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $onchange . '});',
            );
        }
    }

    public static function getFieldValue($field, $label, $lang)
    {
        if (!empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = Field::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            //TODO MIGRATE
            if ($field['value'] != "") {
                $field['value'] = FieldParameter::_unserialize($field['value']);
            }

            $custom_radio = "";
            foreach ($custom_values as $key => $val) {
                if ($field['value'] == $key && $field['value'] !== "") {
                    $custom_radio = $val;
                }
            }
            return $custom_radio;
        } else {
            if ($field['value']) {
                return $label;
            }
        }
        return "";
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $title_style,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if (!empty($field['custom_values']) && $field['value'] > 0) {
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field, $label, $lang),
                    'colspan' => $colspan,
                ]],
            );
        } else {
            if ($field['value']) {
                $result[$field['rank']]['content'] .= Field::renderContentCells(
                    (bool) $formatAsTable,
                    (string) $title_style,
                    [[
                        'value'      => (string) $label,
                        'colspan'    => $colspan,
                        'value_only' => true,
                    ]],
                );
            }
        }

        return $result;
    }
}
