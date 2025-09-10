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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * PluginMetademandsRadio Class
 *
 **/
class PluginMetademandsRadio extends CommonDBTM
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
        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "";
        if (!empty($data['custom_values'])) {
            $custom_values = $data['custom_values'];
            //            $data['custom_values'] = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
            //            foreach ($data['custom_values'] as $k => $val) {
            //                if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
            //                    $data['custom_values'][$k] = $ret;
            //                }
            //            }
            //            $data['comment_values'] = PluginMetademandsFieldParameter::_unserialize($data['comment_values']);
            //            $defaults               = PluginMetademandsFieldParameter::_unserialize($data['default_values']);
            //            if ($value != null) {
            //                $value = PluginMetademandsFieldParameter::_unserialize($value);
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

                    if (empty($value) && isset($label['is_default']) && $on_order == false) {
                        $checked = ($label['is_default'] == 1) ? 'checked' : '';
                    }
                    if (isset($value) && $value == $key) {
                        $checked = 'checked';
                    }
                    $required = "";
                    if ($data['is_mandatory'] == 1) {
                        $required = "required=required";
                    }

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $field .= "<div class='custom-control custom-radio $inline'>";

                        $field .= "<input $required class='form-check-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";

                        if (empty($name = PluginMetademandsField::displayCustomvaluesField($data['id'], $key))) {
                            $name = $label['name'];
                        }
                        $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>" . $name . "</label>";
                        if (isset($label['comment']) && !empty($label['comment'])) {
                            $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                            if (empty(
                                $comment = PluginMetademandsField::displayCustomvaluesField(
                                    $data['id'],
                                    $key,
                                    "comment"
                                )
                            )) {
                                $comment = $label['comment'];
                            }
                            $field .= Html::showToolTip(
                                Glpi\RichText\RichText::getSafeHtml($comment),
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
                            $field .= "<i class='fas $icon fa-2x text-secondary' style=\"font-family:'Font Awesome 6 Free', 'Font Awesome 6 Brands';\"></i>";
                            $field .= "</span>";
                        }


                        $field .= "<div class='text-start'>";
                        $field .= "<div class='d-flex align-items-center'>";
//                        $field .= "<div class='fw-bold'>";

                        if (empty($name = PluginMetademandsField::displayCustomvaluesField($data['id'], $key))) {
                            $name = $label['name'];
                        }
                        $field .= $name;
//                        $field .= "</div>";
                        $field .= "</div>";
                        $field .= "<small class='form-hint'>";
                        if (isset($label['comment']) && !empty($label['comment'])) {
                            if (empty(
                                $comment = PluginMetademandsField::displayCustomvaluesField(
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

                        $field .= "<input $required class='form-check-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                        $field .= "</div>";

                        $field .= "</div>";
                        $field .= "</label>";
                        $field .= "</div>";
                    }
                }

                $childs_blocks = [];
                $fieldopt = new PluginMetademandsFieldOption();
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
                    $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').is(':checked')) { ";

                    foreach ($childs_blocks as $customvalue => $childs) {
                        if ($customvalue != $key) {
                            foreach ($childs as $k => $v) {
                                $script .= "sessionStorage.setItem('hiddenbloc$childs', $childs);";
                                $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($namefield);
                                $script .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                            }
                        }
                    }
                    $script .= "}";
                    $script .= "})";
                    $script .= "</script>";

                    $field .= $script;
                }
            }
            if ($data["display_type"] == self::BLOCK_DISPLAY) {
                $field .= "</div>";
            }
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
                $target = PluginMetademandsFieldCustomvalue::getFormURL();
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
                Dropdown::showYesNo('is_default[' . $key . ']', $value['is_default']);
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
            PluginMetademandsFieldCustomvalue::initCustomValue(
                $maxrank,
                true,
                true,
                $params["plugin_metademands_fields_id"]
            );
            echo "</td>";
            echo "</tr>";
            PluginMetademandsFieldCustomvalue::importCustomValue($params);
        } else {
            $target = PluginMetademandsFieldCustomvalue::getFormURL();
            echo "<form method='post' action=\"$target\">";
            echo "<tr class='tab_bg_1'>";
            echo "<td align='right'  id='show_custom_fields'>";
            if (isset($params['plugin_metademands_fields_id'])) {
                echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
            }
            PluginMetademandsFieldCustomvalue::initCustomValue(-1, true, true, $params["plugin_metademands_fields_id"]);
            echo "</td>";
            echo "</tr>";
            Html::closeForm();
            PluginMetademandsFieldCustomvalue::importCustomValue($params);
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

        echo Dropdown::showFromArray(
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
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        //        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
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

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params);
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        Dropdown::showFromArray(
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
     * @return bool
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
        if (empty($value) && $value != 0) {
            return false;
        } elseif ($check_value != $value) {
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
                                    " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                                    $('[name =\"field['+key+']\"]').removeAttr('required');
                                } else {
                                     var id = '#metademands_wizard_red'+ key;
                                     var fieldid = 'field'+ key;
                                     $(id).html('*');
                                     $('[name =\"field[' + key + ']\"]').attr('required', 'required');
                                     //Special case Upload field
                                          sessionStorage.setItem('mandatoryfile$name', key);
                                         " . PluginMetademandsFieldoption::checkMandatoryFile($fields_link, $name) . "
                                }
                            });";
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $see);
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

            $title = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = __(
                'Next',
                'metademands'
            ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    if ($tasks_id) {
                        if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
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
                                type: 'POST',
                                dataType: 'text',
                                data: { tasks_id: $tasks_id,
                                  used: 1 },
                                success: function(response){
                                   if (response != 1) {
                                       document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
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
                                    if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 1)) {
                                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                                        $script .= "document.getElementById('nextBtn').innerHTML = '$nextsteptitle'";
                                        $script .= "});";
                                    }
                                } else {
                                    if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
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
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
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
                            sessionStorage.setItem('hiddenlink$name', key);
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name);
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
                        }
                    });";
                }
            }

            if (is_array($display) && count($display) > 0) {
                foreach ($display as $see) {
                    $pre_onchange .= "$('[id-field =\"field" . $see . "\"]').show();";
                    $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $see);
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
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";

        //hidden_blocks by idc
        $hiddenblocks_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            foreach ($check_value['hidden_block'] as $hidden_block) {
                if (isset($hidden_block)) {
                    $hiddenblocks_by_checkvalue[$idc] = $hidden_block;
                }
            }
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
                                                ['hidden_block' => $childs]
                                            );
                                            if (count($options) == 0) {
                                                $post_onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                                                document.getElementById('ablock" . $childs . "').style.display = 'block';
                                                                $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                             " . PluginMetademandsFieldoption::setMandatoryBlockFields(
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

            //by default - hide all
            $pre_onchange .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $pre_onchange .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
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
                        " . PluginMetademandsFieldoption::setEmptyBlockFields($name) . "";
                    $hidden = PluginMetademandsFieldoption::resetMandatoryBlockFields($name);
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

                    $hidden = PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                    $onchange .= "$hidden";
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $options = getAllDataFromTable(
                                        'glpi_plugin_metademands_fieldoptions',
                                        ['hidden_block' => $childs]
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
                        if (isset($_GET['itilcategories_id']) && $idc == $_GET['itilcategories_id']) {
                            $pre_onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                        $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();
                          " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
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
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
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

    public static function getFieldValue($field, $label, $lang)
    {
        if (!empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            //TODO MIGRATE
            if ($field['value'] != "") {
                $field['value'] = PluginMetademandsFieldParameter::_unserialize($field['value']);
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
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $label, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } else {
            if ($field['value']) {
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= $label;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }
            }
        }

        return $result;
    }
}
