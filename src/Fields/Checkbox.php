<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\FieldCustomvalue;
use Html;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\MetademandTask;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Checkbox Class
 *
 **/
class Checkbox extends CommonDBTM
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
        return __('Checkbox', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }


        $field = "";
        if (!empty($data['custom_values'])) {
            $custom_values = $data['custom_values'];

            //            foreach ($custom_values as $k => $val) {
            //                if (!empty($ret = Field::displayField($data["id"], "custom" . $k))) {
            //                    $data['custom_values'][$k] = $ret;
            //                }
            //            }
            //            $data['comment_values'] = FieldCustomvalue::_unserialize($data['comment_values']);
            //            $defaults = FieldCustomvalue::_unserialize($data['default_values']);
            //            if (!empty($value)) {
            //                $value = FieldCustomvalue::_unserialize($value);
            //            }
            $nbr = 0;
            $inline = "";
            if ($data['row_display'] == 1) {
                $inline = 'custom-control-inline';
            }

            if ($data["display_type"] == self::BLOCK_DISPLAY) {
                $field .= "<div class='row flex-row'>";
            }

            if (count($custom_values) > 0) {
                foreach ($custom_values as $key => $label) {
                    $checked = "";
                    if (isset($value[$key]) && $value[$key] == $key) {
                        $checked = 'checked';
                    } elseif (isset($label['is_default']) && $on_order == false) {
                        $checked = ($label['is_default'] == 1) ? 'checked' : '';
                    }
                    $required = "";
                    if ($data['is_mandatory'] == 1) {
                        $required = "required=required";
                    }

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $field .= "<div class='custom-control custom-checkbox $inline'>";

                        $field .= "<input $required class='form-check-input' type='checkbox' check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                        $nbr++;
                        if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                            $name = $label['name'];
                        }
                        $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>" . $name . "</label>";
                        if (isset($label['comment']) && !empty($label['comment'])) {
                            $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                            if (empty(
                            $comment = Field::displayCustomvaluesField(
                                $data['id'],
                                $key,
                                "comment"
                            )
                            )) {
                                $comment = $label['comment'];
                            }
                            $field .= Html::showToolTip(
                                RichText::getSafeHtml($comment),
                                [
                                    'awesome-class' => 'ti ti-info-circle',
                                    'display' => false,
                                ]
                            );
                            $field .= "</span>";
                        }
                        $field .= "</div>";
                    } else {
                        $field .= "<div class='col-12 col-lg-6 col-xxl-4 mb-2'>";
                        $field .= "<label class='form-selectgroup-boxes flex-fill w-100 h-100' style='min-height: 70px;'>";

                        //                        $field .= '
                        //<input type="checkbox" name="capacities[3][is_active]" value="1" class="form-selectgroup-input"
                        //data-capacity-checkbox="1"  data-is-used="0" checked="">';

                        $field .= "<div class='form-selectgroup-label d-flex align-items-center h-100 shadow-none p-0 px-3'>";

                        $icon = $label['icon'];
                        if (empty($label['icon'])) {
                            $icon = $data['icon'];
                        }

                        if (!empty($icon)) {
                            $field .= "<span class='me-2 mt-1'>";
                            if (str_contains($icon, 'fa-')) {
                                $field .= "<i class='fas $icon fa-2x text-secondary' style=\"font-family:'Font Awesome 6 Free', 'Font Awesome 6 Brands';\"></i>";
                            } else {
                                $field .= "<i class='ti $icon text-secondary'></i>";
                            }
                            $field .= "</span>";
                        }


                        $field .= "<div class='text-start'>";
                        $field .= "<div class='d-flex align-items-center'>";
                        //                        $field .= "<div class='fw-bold'>";

                        if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                            $name = $label['name'];
                        }
                        $field .= $name;
                        //                        $field .= "</div>";
                        $field .= "</div>";
                        $field .= "<small class='form-hint'>";
                        if (isset($label['comment']) && !empty($label['comment'])) {
                            if (empty(
                            $comment = Field::displayCustomvaluesField(
                                $data['id'],
                                $key,
                                "comment"
                            )
                            )) {
                                $comment = $label['comment'];
                            }
                            $field .= $comment;
                        }
                        $field .= "</small>";

                        $field .= "</div>";

                        $field .= "<div class='me-2 ms-auto'>";
                        $field .= "<input $required class='form-check-input' type='checkbox' check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                        $field .= "</div>";

                        $field .= "</div>";
                        $field .= "</label>";
                        $field .= "</div>";
                    }

                    $childs_blocks = [];
                    $fieldopt = new FieldOption();
                    if ($opts = $fieldopt->find(
                        ["plugin_metademands_fields_id" => $data['id'], "check_value" => $key]
                    )) {
                        foreach ($opts as $opt) {
                            if (!empty($opt['childs_blocks'])) {
                                $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                            }
                        }
                    }

                    if (isset($childs_blocks[$key])) {
                        $id = $data['id'];
                        $script = "<script type='text/javascript'>";
                        $script .= "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                        $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').not(':checked')) { ";

                        foreach ($childs_blocks[$key] as $customvalue => $childs) {
                            $script .= "sessionStorage.setItem('hiddenbloc$childs', $childs);";
                            $script .= Fieldoption::resetMandatoryBlockFields($childs);
                            $script .= "$('div[bloc-id=\"bloc$childs\"]').hide();";
                        }
                        $script .= "}";
                        $script .= "})";
                        $script .= "</script>";

                        $field .= $script;
                    }
                }
            }
            if ($data["display_type"] == self::BLOCK_DISPLAY) {
                $field .= "</div>";
            }
        } else {
            $checked = $value ? 'checked' : '';
            $field = "<input class='form-check-input' type='checkbox' name='" . $namefield . "[" . $data['id'] . "]' value='checkbox' $checked>";
        }

        echo $field;
    }

    public static function showFieldCustomValues($params)
    {
        $custom_values = $params['custom_values'];

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $maxrank = 0;

        if (is_array($custom_values) && !empty($custom_values)) {
            echo "<div id='drag'>";
            echo "<table class='tab_cadre_fixe'>";

            foreach ($custom_values as $key => $value) {
                $target = FieldCustomvalue::getFormURL();
                echo "<form method='post' action=\"$target\">";
                echo "<tr class='tab_bg_1'>";

                echo "<td class='rowhandler control center'>";
                echo __('Rank', 'metademands') . " " . $value['rank'] . " ";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden(
                        'fields_id',
                        ['value' => $params["plugin_metademands_fields_id"], 'id' => 'fields_id']
                    );
                    echo Html::hidden('type', ['value' => $params["type"], 'id' => 'type']);
                }
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='custom_values$key'>";
                echo Html::input('name[' . $key . ']', ['value' => $value['name'], 'size' => 30]);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='comment_values$key'>";
                echo __('Comment') . " ";
                echo Html::input('comment[' . $key . ']', ['value' => $value['comment'], 'size' => 30]);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='default_values$key'>";
                echo _n('Default value', 'Default values', 1, 'metademands') . " ";
                \Dropdown::showYesNo('is_default[' . $key . ']', $value['is_default']);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='icon$key'>";
                $icon_selector_id = 'icon_' . mt_rand();
                echo Html::select(
                    'icon[' . $key . ']',
                    [$value['icon'] => $value['icon']],
                    [
                        'id' => $icon_selector_id,
                        'selected' => $value['icon'],
                        'style' => 'width:175px;',
                    ]
                );

                echo Html::script('js/modules/Form/WebIconSelector.js');
                echo Html::scriptBlock(
                    <<<JAVASCRIPT
         $(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );
        JAVASCRIPT
                );

                $blank = "_blank_picture[$key]";
                echo "&nbsp;<input type='checkbox' name='$blank'>&nbsp;" . __('Clear');
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<i class=\"ti ti-grip-horizontal grip-rule\"></i>";
                echo "</div>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo Html::hidden('id[' . $key . ']', ['value' => $key]);

                echo Html::submit("", [
                    'name' => 'update',
                    'class' => 'btn btn-primary',
                    'icon' => 'ti ti-device-floppy',
                ]);
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                Html::showSimpleForm(
                    $target,
                    'delete',
                    _x('button', 'Delete permanently'),
                    [
                        'customvalues_id' => $key,
                        'rank' => $value['rank'],
                        'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                    ],
                    'ti-circle-x',
                    "class='btn btn-primary'"
                );
                echo "</td>";

                echo "</tr>";

                $maxrank = $value['rank'];

                Html::closeForm();
            }

            echo "</table>";
            echo "</div>";
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' align='left' id='show_custom_fields'>";
            FieldCustomvalue::initCustomValue(
                $maxrank,
                true,
                true,
                $params["plugin_metademands_fields_id"]
            );
            echo "</td>";
            echo "</tr>";
            FieldCustomvalue::importCustomValue($params);
        } else {
            $target = FieldCustomvalue::getFormURL();
            echo "<form method='post' action=\"$target\">";
            echo "<tr class='tab_bg_1'>";
            echo "<td align='right'  id='show_custom_fields'>";
            if (isset($params['plugin_metademands_fields_id'])) {
                echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
            }
            FieldCustomvalue::initCustomValue(-1, true, true, $params["plugin_metademands_fields_id"]);
            echo "</td>";
            echo "</tr>";
            Html::closeForm();
            FieldCustomvalue::importCustomValue($params);
        }
        echo "</td>";
        echo "</tr>";
    }

    public static function showFieldParameters($params)
    {
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::BLOCK_DISPLAY] = __("Block display", "metademands");
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Display type of the field', 'metademands');
        echo "</td>";
        echo "<td>";

        echo \Dropdown::showFromArray(
            "display_type",
            $disp,
            ['value' => $params['display_type'], 'display' => false]
        );
        echo "</td>";

        echo "<td>";
        echo __('Icon') . "&nbsp;";
        echo "</td>";
        echo "<td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon',
            [$params['icon'] => $params['icon']],
            [
                'id' => $icon_selector_id,
                'selected' => $params['icon'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock(
            <<<JAVASCRIPT
         $(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );
        JAVASCRIPT
        );


        echo "&nbsp;<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
        echo "</td>";
        echo "</tr>";
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        //        echo " ( " . \Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
        echo "</td>";
        echo "<td class = 'dropdown-valuetocheck'>";
        self::showValueToCheck($fieldoption, $params);
        echo "</td>";

        echo "<script type = \"text/javascript\">
                 $('td.dropdown-valuetocheck select').on('change', function() {
                 let formOption = [
                     " . $params['ID'] . ",
                         $(this).val(),
                         $('select[name=\"plugin_metademands_tasks_id\"]').val(),
                         $('select[name=\"fields_link\"]').val(),
                         $('select[name=\"hidden_link\"]').val(),
                         $('select[name=\"hidden_block\"]').val(),
                         JSON.stringify($('select[name=\"childs_blocks[][]\"]').val()),
                         $('select[name=\"users_id_validate\"]').val(),
                         $('select[name=\"checkbox_id\"]').val()
                  ];

                     reloadviewOption(formOption);
                 });";


        echo " </script>";

        echo FieldOption::showLinkHtml($item->getID(), $params);
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
            ['value' => $params['check_value'], 'used' => $already_used]
        );
    }

    public static function showParamsValueToCheck($params)
    {
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        echo $elements[$params['check_value']] ?? 0;
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
            && $fields['value'] == null) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (!empty($value)) {
            $ok = false;
            if ($check_value == -1) {
                $ok = true;
            }
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    //                     if ($key != 0) {
                    if ($check_value == $key) {
                        $ok = true;
                    }
                    //                     }
                }
            } elseif (is_array(json_decode($value, true))) {
                foreach (json_decode($value, true) as $key => $v) {
                    //                     if ($key != 0) {
                    if ($check_value == $key) {
                        $ok = true;
                    }
                    //                     }
                }
            }
            if (!$ok) {
                return false;
            }
        } else {
            return false;
        }
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
            $pre_onchange = "console.log('fieldsMandatoryScript-checkbox $id');";
        }
        if (count($check_values) > 0) {
            //Si la valeur est en session
            //specific
            if (isset($data['value']) && is_array($data['value'])) {
                $values = $data['value'];
                foreach ($values as $value) {
                    $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                }
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = [];
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    $onchange .= "if (this.checked){";
                    $onchange .= " if ($(this).val() == $idc || $idc == -1) {
                                if ($fields_link in tohide) {
                                } else {
                                    tohide[$fields_link] = true;
                                }
                                tohide[$fields_link] = false;
                            }";

                    if (isset($data['value']) && is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            if ($idc == $value) {
                                $display[] = $fields_link;
                            }
                        }
                    }

                    $onchange .= "$.each(tohide, function( key, value ) {
                                if (value == true) {
                                    var id = '#metademands_wizard_red'+ key;
                                    $(id).html('');
                                    sessionStorage.setItem('hiddenlink$name', key);
                                    " . Fieldoption::resetMandatoryFieldsByField($name) . "
                                    $('[name =\"field[' + key + ']\"]').removeAttr('required');
                                } else {
                                    var id = '#metademands_wizard_red'+ key;
                                    var fieldid = 'field'+ key;
                                    $(id).html('*');
                                    $('[name =\"field[' + key + ']\"]').attr('required', 'required');
                                    //Special case Upload field
                                      sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                     " . Fieldoption::checkMandatoryFile($fields_link, $name) . "
                                }

                            });";

                    $onchange .= "} else {";
                    //not checked
                    $onchange .= "if ($(this).val() == $idc) {
                                if ($fields_link in tohide) {
                                } else {
                                   tohide[$fields_link] = true;
                                }
                                $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){
                                    if($(value).val() == $idc || $idc == -1 ){
                                        tohide[$fields_link] = false;
                                    }
                                });
                            }";


                    $onchange .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                                var id = '#metademands_wizard_red'+ key;
                                $(id).html('');
                                sessionStorage.setItem('hiddenlink$name', key);
                                " . Fieldoption::resetMandatoryFieldsByField($name) . "
                                $('[name =\"field[' + key + ']\"]').removeAttr('required');
                            } else {
                               var id = '#metademands_wizard_red'+ key;
                               var fieldid = 'field'+ key;
                               $(id).html('*');
                               $('[name =\"field[' + key + ']\"]').attr('required', 'required');
                               //Special case Upload field
                                  sessionStorage.setItem('mandatoryfile$name', key);
                                 " . Fieldoption::checkMandatoryFile($fields_link, $name) . "
                            }
                         });";
                    $onchange .= "}";
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= Fieldoption::setMandatoryFieldsByField($id, $see);
                }
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
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
            $script = "console.log('taskScript-checkbox $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            //specific
            if (isset($data['value']) && is_array($data['value'])) {
                $values = $data['value'];
                foreach ($values as $value) {
                    $script2 .= "$('[name=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true);";
                }
            }

            $title = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = __(
                    'Next',
                    'metademands'
                ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    if ($tasks_id) {
                        if (MetademandTask::setUsedTask($tasks_id, 0)) {
                            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                            $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
                            $script .= "});";
                        }
                    }
                }
            }

            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $script .= "var tohide = {};";
            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    $script .= " if (this.checked){";
                    //                                        foreach ($hidden_link as $key => $fields) {
                    $script .= " if ($(this).val() == $idc || $idc == -1) {
                            if ($tasks_id in tohide) {
                            } else {
                                tohide[$tasks_id] = true;
                            }
                            tohide[$tasks_id] = false;
                        }";


                    $script .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                     data: { tasks_id: $tasks_id,
                                  used: 0 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$title'
                                       }
                                    },
                                });
                        } else {
                             $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                     data: { tasks_id: $tasks_id,
                                  used: 1 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
                                       }
                                    },
                                });

                        }
                    });
              ";
                    $script .= "} else {
                $.ajax({
                                 url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                 data: { tasks_id: $tasks_id,
                              used: 0 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$title'
                                       }
                                    },
                            });
            }
            ";
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
                                        $script .= "document.getElementById('nextBtn').innerHTML = '$nextsteptitle'";
                                        $script .= "});";
                                    }
                                } else {
                                    if (MetademandTask::setUsedTask($tasks_id, 0)) {
                                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                                        $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
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
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $pre_onchange = "console.log('fieldsHiddenScript-checkbox $id');";
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
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                }
            }

            //Si la valeur est en session
            //specific
            if (isset($data['value']) && is_array($data['value'])) {
                $values = $data['value'];
                foreach ($values as $value) {
                    $pre_onchange .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                }
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = [];
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $onchange .= " if (this.checked){";
                    //                                        foreach ($hidden_link as $key => $fields) {
                    $onchange .= " if ($(this).val() == $idc || $idc == -1) {
                            if ($hidden_link in tohide) {
                            } else {
                                tohide[$hidden_link] = true;
                            }
                            tohide[$hidden_link] = false;
                        }";

                    if (isset($data['value']) && is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            if ($idc == $value) {
                                $display[] = $hidden_link;
                            }
                        }
                    }

                    $onchange .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                               sessionStorage.setItem('hiddenlink$name', key);
                                " . Fieldoption::resetMandatoryFieldsByField($name) . "
                            } else {
                                $('[id-field =\"field'+key+'\"]').show();
                            }
                        });";

                    $onchange .= "} else {";
                    //not checked
                    $onchange .= "if($(this).val() == $idc){
                            if ($hidden_link in tohide) {
                            } else {
                               tohide[$hidden_link] = true;
                            }
                            $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){
                                if($(value).val() == $idc || $idc == -1 ){
                                    tohide[$hidden_link] = false;
                                }
                            });
                        }";

                    $onchange .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                               $('[id-field =\"field'+key+'\"]').hide();
                               sessionStorage.setItem('hiddenlink$name', key);
                               " . Fieldoption::resetMandatoryFieldsByField($name) . "
                               $('[name =\"field[' + key + ']\"]').removeAttr('required');
                            } else {
                               $('[id-field =\"field'+key+'\"]').show();
                            }
                         });";
                    $onchange .= "}";
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= "$('[id-field =\"field" . $see . "\"]').show();";
                    $pre_onchange .= Fieldoption::setMandatoryFieldsByField($id, $see);
                }
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        }
    }

    public static function blocksHiddenScript($data)
    {
        //specific
        if (isset($data['value'])
            && $data['value'] == 0) {
            $data['value'] = [];
        }
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

        $script = "";
        $script2 = "";
        $debug = isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        if ($debug) {
            $script = "console.log('blocksHiddenScript-checkbox $id');";
        }
        if (count($check_values) > 0) {
            //by default - hide all
            $script2 .= Fieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $script2 .= Fieldoption::emptyAllblockbyDefault($check_values);
            }

            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                if (is_array($data['value'])) {
                    $values = $data['value'];
                    foreach ($values as $value) {
                        $script2 .= "$('[id=\"field[" . $id . "][" . $value . "]\"]').prop('checked', true).trigger('change');";
                    }
                }
            }

            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $script .= "var tohide = {};";

            //checkbox : multiple value at each time
            $display = [];
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_block'] as $hidden_block) {
                    //Default values
                    if (isset($data['custom_values'])
                        && is_array($data['custom_values'])
                        && count($data['custom_values']) > 0) {
                        $custom_values = $data['custom_values'];
                        foreach ($custom_values as $k => $custom_value) {
                            if ($custom_value['is_default'] == 1) {
                                if ($idc == $k) {
                                    $script2 .= "
                                if (document.getElementById('ablock" . $hidden_block . "'))
                                document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                                $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();
                                " . Fieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                                    if (is_array($childs_by_checkvalue)) {
                                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                            if ($idc == $k) {
                                                foreach ($childs_blocks as $childs) {
                                                    $options = getAllDataFromTable(
                                                        'glpi_plugin_metademands_fieldoptions',
                                                        ['hidden_block' => $childs]
                                                    );
                                                    if (count($options) == 0) {
                                                        $script2 .= "
                                                           if (document.getElementById('ablock" . $childs . "'))
                                                            document.getElementById('ablock" . $childs . "').style.display = 'block';
                                                           $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                 " . Fieldoption::setMandatoryBlockFields(
                                                                $metaid,
                                                                $childs
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
                    //end Default values

                    $script .= " if (this.checked) {";

                    $script .= "if ($(this).val() == $idc || $idc == -1 ) {";
                    $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                        $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
                        $('[bloc-id =\"subbloc'+$hidden_block+'\"]').show();";

                    $script .= Fieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $options = getAllDataFromTable(
                                        'glpi_plugin_metademands_fieldoptions',
                                        ['hidden_block' => $childs]
                                    );
                                    if (count($options) == 0) {
                                        $script .= "if (document.getElementById('ablock" . $childs . "'))
                                        document.getElementById('ablock" . $childs . "').style.display = 'block';
                                        $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                        $('[bloc-id =\"subbloc" . $childs . "\"]').show();";
                                    }
                                }
                            }
                        }
                    }
                    if (isset($data['value']) && is_array($data['value'])) {
                        $values = $data['value'];
                        foreach ($values as $value) {
                            if ($idc == $value) {
                                $display[] = $hidden_block;
                            }
                        }
                    }
                    $script .= " }";

                    $script .= " } else { ";

                    //if reload form


                    $script .= "if($(this).val() == $idc){
                            if (document.getElementById('ablock" . $hidden_block . "'))
                            document.getElementById('ablock" . $hidden_block . "').style.display = 'none';
                            $('[bloc-id =\"bloc'+$hidden_block+'\"]').hide();
                            $('[bloc-id =\"subbloc'+$hidden_block+'\"]').hide();
                            sessionStorage.setItem('hiddenbloc$name', $hidden_block);";
                    $script .= Fieldoption::resetMandatoryBlockFields($name)
                        . Fieldoption::setEmptyBlockFields($name);

                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $script .= "if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'none';
                                $('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                sessionStorage.setItem('hiddenbloc$childs', $childs);
                                                         " . Fieldoption::setEmptyBlockFields($childs)
                                        . Fieldoption::resetMandatoryBlockFields($childs);
                                }
                            }
                        }
                    }

                    if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                        $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                        if (is_array($session_value)) {
                            foreach ($session_value as $k => $fieldSession) {
                                if ($fieldSession == $idc && $hidden_block > 0) {
                                    $script2 .= "if (document.getElementById('ablock" . $hidden_block . "'))
                                document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                                $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();";
                                }
                                if (is_array($childs_by_checkvalue)) {
                                    foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                        if ($idc == $k) {
                                            foreach ($childs_blocks as $childs) {
                                                $options = getAllDataFromTable(
                                                    'glpi_plugin_metademands_fieldoptions',
                                                    ['hidden_block' => $childs]
                                                );
                                                if (count($options) == 0) {
                                                    $script2 .= "if (document.getElementById('ablock" . $childs . "'))
                                                         document.getElementById('ablock" . $childs . "').style.display = 'block';
                                                         $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . Fieldoption::setMandatoryBlockFields(
                                                            $metaid,
                                                            $childs
                                                        );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $script .= "}";

                    $script .= " }";

                    if (is_array($display) && count($display) > 0) {
                        foreach ($display as $see) {
                            $script2 .= "if (document.getElementById('ablock" . $see . "'))
                    document.getElementById('ablock" . $see . "').style.display = 'block';
                    $('[bloc-id =\"bloc" . $see . "\"]').show();
                    $('[bloc-id =\"subbloc" . $see . "\"]').show();";
                        }
                    }
                }
            }
            $script .= "});";

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    public static function checkConditions($data, $metaparams)
    {
        foreach ($metaparams as $key => $val) {
            if (isset($metaparams[$key])) {
                $$key = $metaparams[$key];
            }
        }

        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $onchange = "window.metademandconditionsparams = {};
                        metademandconditionsparams.submittitle = '$submittitle';
                        metademandconditionsparams.nextsteptitle = '$nextsteptitle';
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
            '$(document).ready(function() {' . $onchange . '});'
        );
    }

    public static function getFieldValue($field, $lang)
    {
        if (isset($field['custom_values']) && !empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = Field::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if (!empty($field['value'])) {
                if (is_string($field['value'])) {
                    $field['value'] = FieldCustomvalue::_unserialize($field['value']);
                } else {
                    $field['value'] = json_decode(json_encode($field['value']), true);
                }
            }
            $custom_checkbox = [];

            foreach ($custom_values as $key => $val) {
                $checked = isset($field['value'][$key]) ? 1 : 0;
                if ($checked) {
                    $custom_checkbox[] .= $val;
                }
            }
            return implode(',', $custom_checkbox);
        } else {
            if ($field['value']) {
                return $field['value'];
            }
        }
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $style_title,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        if (is_string($field['value'])) {
            $field['value'] = FieldCustomvalue::_unserialize($field['value']);
        } else {
            $field['value'] = json_decode(json_encode($field['value']), true);
        }

        $result[$field['rank']]['display'] = true;
        if (!empty($field['custom_values']) && $field['value'] > 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } else {
            if ($field['value']) {
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }
            }
        }

        return $result;
    }
}
