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

use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * PluginMetademandsTextarea Class
 *
 **/
class PluginMetademandsTextarea extends CommonDBTM
{

    private $uploads = [];
    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    static function getTypeName($nb = 0)
    {
        return __('Textarea', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }
        $value = Html::cleanPostForTextArea($value);
        $self = new self();
        $required = "";
        if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
            $required = "required='required'";
        }
        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            $rand = mt_rand();
            $name = 'field['. $data['id'] .']';
            $field = Html::textarea([
                'name' => $name,
                'value' => $value,
                'rand' => $rand,
                'editor_id' => $namefield . $data['id'],
                'enable_richtext' => true,
                'enable_fileupload' => false,
//                'enable_images' => true,
                'display' => false,
                'required' => ($data['is_mandatory'] ? "required" : ""),
                'cols' => 80,
                'rows' => 6,
//                'uploads' => $self->uploads
            ]);
            $field .=  '<div style="display:none;">';
            $field .= Html::file(['editor_id'    => $namefield . $data['id'],
                'filecontainer' => "filecontainer$rand",
                'onlyimages'    => true,
                'showtitle'     => false,
                'multiple'      => true,
                'display'       => false]);
            $field .=  '</div>';
            $field .="<style>
                        .fileupload.only-uploaded-files {
                            display: none;
                        }

                     </style>";


        } else {

            if (!empty($comment)) {
                $comment = Glpi\RichText\RichText::getTextFromHtml($comment);
            }
            $field = "<textarea $required class='form-control' rows='6' cols='80' 
               placeholder=\"" . $comment . "\" 
               name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "]'>" . $value . "</textarea>";
        }

        echo $field;
    }

    static function showFieldCustomValues($params)
    {

    }

    static function showFieldParameters($params)
    {

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Use richt text', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('use_richtext', ($params['use_richtext']));
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";
    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {

        echo "<tr>";
        echo "<td>";
        echo __('If field empty', 'metademands');
        echo "</td>";
        echo "<td>";
        if ($params['use_richtext'] == 0) {
            self::showValueToCheck($fieldoption, $params);
        } else {
            echo __('Not available with Rich text option', 'metademands');
        }
        echo "</td>";
        if ($params['use_richtext'] == 0) {
            echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params, 1, 0, 1);
        }
    }

    static function showValueToCheck($item, $params)
    {
        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        if ($item->getID() == 0) {
            foreach ($existing_options as $existing_option) {
                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
            }
        }
        $options[1] = __('No');
        //cannot use it
//        $options[2] = __('Yes');
        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    static function showParamsValueToCheck($params)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        echo $options[$params['check_value']] ?? "";

    }

    static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == 2 && $value != "")) {
            return false;
        } elseif ($check_value == 1 && $value == "") {
            return false;
        }
    }

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function taskScript($data)
    {

        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('taskScript-textarea $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('".$data['value']."').trigger('change');";
            }

            $title = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];
                if ($tasks_id) {
                    if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                        $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
                        $script .= "});";
                    }
                }
            }
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];

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
            $script .= "});";

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    static function fieldsHiddenScript($data)
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
            $onchange = "console.log('fieldsHiddenScript-textarea $id');";
        }

        if (count($check_values) > 0) {
            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('".$data['value']."').trigger('change');";
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];

                if (isset($idc) && $idc == 1) {
                    $onchange .= "if ($(this).val().trim().length < 1) {
                                 $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                 sessionStorage.setItem('hiddenlink$name', $hidden_link);
                                  " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                              } else {
                                 $('[id-field =\"field" . $hidden_link . "\"]').show();
                              }
                                                    ";
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_link;
                    }
                } else {
                    $onchange .= "if ($(this).val().trim().length < 1) {
                                $('[id-field =\"field" . $hidden_link . "\"]').show();
                             } else {
                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                sessionStorage.setItem('hiddenlink$name', $hidden_link);
                                 " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                             }";

                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_link;
                    }
                }
            }
            if ($display > 0) {
                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
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
            $script = "console.log('blocksHiddenScript-textarea $id');";
        }

        if (count($check_values) > 0) {
            if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
//            $script .= "if (typeof tinymce !== 'undefined') {
//                            tinymce.init({
//                                selector: '#field$id',
//                                setup: function (editor) {
//                                    editor.on('change', function () {
//                                        // Handle the change event here
//                                        console.log('Content has changed.');
//                                    });
//                                }
//                            });
//                        }";
            } else {
                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                $script .= "var tohide = {};";

                //by default - hide all
                $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
                if (!isset($data['value'])) {
                    $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
                }
                $display = 0;
                foreach ($check_values as $idc => $check_value) {
                    $blocks_idc = [];
                    $hidden_block = $check_value['hidden_block'];

                    if (isset($idc) && $idc == 1) {
                        $script .= "if ($(this).val().trim().length > 0) {";
                        $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                        $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                        $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();";
                        $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $script .= "if (document.getElementById('ablock" . $childs . "'))
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

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_block;
                        }

                        $script .= " } else {";

                        //specific - one value
                        $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                        $script .= " }";

//                    $script .= " }";
//
//                    $script .= "if ($(this).val() != $idc) {";
//                    if (is_array($blocks_idc) && count($blocks_idc) > 0) {
//                        foreach ($blocks_idc as $k => $block_idc) {
//                            $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
//                        }
//                    }
//                    $script .= " }";
                    }
                }

                if ($display > 0) {
                    $script2 .= "$('[bloc-id =\"bloc" . $display . "\"]').show();";
                }


                $script .= "fixButtonIndicator();";
                $script .= "});";
            }
            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
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
            && empty($fields['value'])) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function getFieldValue($field)
    {
        $field['value'] = Glpi\RichText\RichText::getSafeHtml($field['value']);
        return $field['value'];
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
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
