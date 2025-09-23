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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\MetademandTask;
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Url Class
 *
 **/
class Url extends CommonDBTM
{
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
        return __('URL');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $size = "35";
        if ($data['row_display'] == 1) {
            $size = "70";
        }
        $name = $namefield . "[" . $data['id'] . "]";
        $opt = [
            'id-field' => $name,
            'id' => $name,
            'value' => $value,
            'placeholder' => (!$comment == null) ? RichText::getTextFromHtml($comment) : "",
            'size' => $size,
        ];
        $opt['type'] = "url";

        if ($data['is_mandatory'] == 1) {
            $opt['required'] = "required";
        }
        $updateJs = '';
        if (!empty($data['used_by_ticket']) && empty($value)) {
            $updateJs .= "let field{$data['id']} = $(\"[id-field='field{$data['id']}'] input\");
                        field{$data['id']}.val(response[{$data['used_by_ticket']}] ?? '');
                        field{$data['id']}.trigger('input');
                        ";
        }
        $ID = $data['link_to_user'];
        echo "<script type='text/javascript'>
                        $(function() {
                            $(\"[name='field[$ID]']\").ready(function() {
                                 $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/uTextFieldUpdate.php',
                                     data: {
                                         id : $(\"[name='field[$ID]']\").val()
                                     },
                                  success: function(response){
                                       response = JSON.parse(response);
                                       $updateJs
                                    },
                                });
                            })
                        })
                    </script>";

        $field = Html::input($name, $opt);

        echo $field;
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params)
    {
        //        echo "<tr class='tab_bg_1'>";
        //        echo "<td>";
        //        echo __('Link this to a user field', 'metademands');
        //        echo "</td>";
        //        echo "<td>";
        //
        //        $arrayAvailable[0] = \Dropdown::EMPTY_VALUE;
        //        $field = new Field();
        //        $fields = $field->find([
        //            "plugin_metademands_metademands_id" => $params['plugin_metademands_metademands_id'],
        //            'type' => "dropdown_object",
        //            "item" => User::getType()
        //        ]);
        //        foreach ($fields as $f) {
        //            $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
        //        }
        //        \Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $params['link_to_user']]);
        //        echo "</td>";
        //
        //
        //        if ($params['link_to_user'] > 0) {
        //            echo "<td>" . __('User information to get', 'metademands') . "</td>";
        //            $options = [
        //                0 => \Dropdown::EMPTY_VALUE,
        //                6 => _n('Phone', 'Phones', 0),
        //                11 => __('Mobile phone'),
        //            ];
        //            echo "</td>";
        //            echo "<td>";
        //            \Dropdown::showFromArray(
        //                'used_by_ticket',
        //                $options,
        //                ['value' => $params["used_by_ticket"]]
        //            );
        //            echo "</td>";
        //        } else {
        //            echo "<td colspan='2'></td>";
        //        }
        //
        //        echo "<tr class='tab_bg_1'>";
        //        echo "<td>";
        //        echo __('Regex to respect', 'metademands');
        //        //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
        //        echo "</td>";
        //        echo "<td>";
        //        echo Html::input('regex', ['value' => $params["regex"], 'size' => 50]);
        //        echo "</td>";
        //        echo "<td colspan='2'></td>";
        //        echo "</tr>";

    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('If field empty', 'metademands');
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

        if ($params['check_value'] == '') {
            $params['check_value'] = 1;
        }

        echo FieldOption::showLinkHtml($item->getID(), $params);
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        $options[1] = __('No');
        //cannot use it
        //        $options[2] = __('Yes');
        \Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
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
            && empty($fields['value'])) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == 2 && $value != "")) {
            return false;
        } elseif ($check_value == 1 && $value == "") {
            return false;
        }
    }

    public static function showParamsValueToCheck($params)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        echo $options[$params['check_value']] ?? "";
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
            $onchange = "console.log('fieldsMandatoryScript-tel $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    if (isset($idc) && $idc == 1) {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                     sessionStorage.setItem('hiddenlink$name', $fields_link);
                                      " . Fieldoption::resetMandatoryFieldsByField($name) . "
                                  } else {
                                     $('#metademands_wizard_red" . $fields_link . "').html('*');
                                     $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                     //Special case Upload field
                                      sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                     " . Fieldoption::checkMandatoryFile($fields_link, $name) . "
                                  }
                                ";
                    } else {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                     $('#metademands_wizard_red" . $fields_link . "').html('*');
                                     $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                     //Special case Upload field
                                      sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                     " . Fieldoption::checkMandatoryFile($fields_link, $name) . "
                                 } else {
                                    $('#metademands_wizard_red" . $fields_link . "').html('');
                                    sessionStorage.setItem('hiddenlink$name', $fields_link);
                                     " . Fieldoption::resetMandatoryFieldsByField($name) . "
                                 }";
                    }
                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $fields_link;
                    }
                }
            }

            if ($display > 0) {
                $pre_onchange .= Fieldoption::setMandatoryFieldsByField($id, $display);
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
            $script = "console.log('taskScript-url $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
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

            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    $script .= "if ($(this).val().trim().length < 1) {
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
//                                if (typeof document.getElementById('nextBtn') !== 'undefined'
//                                && document.getElementById('nextBtn').value){
                                    if(document.getElementById('nextBtn') != null) {document.getElementById('nextBtn').innerHTML = '$title'};
//                                 }
                                 ";

                    $script .= "      } else {
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

                                 ";
                    $script .= "}";
                }
            }
            $script .= "});";

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
            $onchange = "console.log('fieldsHiddenScript-url $id');";
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
            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                    $('[id-field =\"field" . $hidden_link . "-2\"]').hide();";
                }
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    if (isset($idc) && $idc == 1) {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                 $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                 $('[id-field =\"field" . $hidden_link . "-2\"]').hide();
                                 sessionStorage.setItem('hiddenlink$name', $hidden_link);
                                  " . Fieldoption::resetMandatoryFieldsByField($name);

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
                                 $('[id-field =\"field" . $hidden_link . "\"]').show();
                              }
                            ";

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_link;
                        }
                    } else {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                $('[id-field =\"field" . $hidden_link . "\"]').show();
                             } else {
                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                $('[id-field =\"field" . $hidden_link . "-2\"]').hide();
                                sessionStorage.setItem('hiddenlink$name', $hidden_link);
                                 " . Fieldoption::resetMandatoryFieldsByField($name);

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
                        $onchange .= "}";

                        $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                        $('[id-field =\"field" . $hidden_link . "-2\"]').hide();";

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_link;
                        }
                    }
                }
            }

            if ($display > 0) {
                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                $pre_onchange .= Fieldoption::setMandatoryFieldsByField($id, $display);
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
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('blocksHiddenScript-url $id');";
        }

        if (count($check_values) > 0) {
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $script .= "var tohide = {};";

            //by default - hide all
            $script2 .= Fieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $script2 .= Fieldoption::emptyAllblockbyDefault($check_values);
            }
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_block'] as $hidden_block) {
                    $blocks_idc = [];

                    if (isset($idc) && $idc == 1) {
                        $script .= "if ($(this).val().trim().length > 0) {";
                        $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                        $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
                        $('[bloc-id =\"subbloc'+$hidden_block+'\"]').show();";
                        $script .= Fieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $options = getAllDataFromTable('glpi_plugin_metademands_fieldoptions',
                                            ['hidden_block' => $childs]);
                                        if (count($options) == 0) {
                                            $script .= "if (document.getElementById('ablock" . $childs . "'))
                        document.getElementById('ablock" . $childs . "').style.display = 'block';
                        $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " .Fieldoption::setMandatoryBlockFields(
                                                    $metaid,
                                                    $childs
                                                );
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_block;
                        }
                        $script .= " } else {";

                        //specific - one value
                        $script .= Fieldoption::hideAllblockbyDefault($data);

                        $script .= " }";
                        //                $script .= " }";
                        //
                        //                $script .= "if ($(this).val() != $idc) {";
                        //                if (is_array($blocks_idc) && count($blocks_idc) > 0) {
                        //                    foreach ($blocks_idc as $k => $block_idc) {
                        //                        $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
                        //                    }
                        //                }
                        //                $script .= " }";
                    }
                }
            }

            if ($display > 0) {
                $script2 .= "$('[bloc-id =\"bloc" . $display . "\"]').show();
                $('[bloc-id =\"subbloc" . $display . "\"]').show();";
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

    public static function getFieldValue($field)
    {
        $field['value'] = RichText::getSafeHtml($field['value']);
        $field['value'] = RichText::getTextFromHtml($field['value']);
        return $field['value'];
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
        if ($field['value'] != 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        }

        return $result;
    }

}
