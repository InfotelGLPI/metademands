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
 * PluginMetademandsCheckbox Class
 *
 **/
class PluginMetademandsCheckbox extends CommonDBTM
{

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
        return __('Checkbox', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_basket)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (!empty($data['custom_values'])) {
            $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
            foreach ($data['custom_values'] as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                    $data['custom_values'][$k] = $ret;
                }
            }
            $data['comment_values'] = PluginMetademandsField::_unserialize($data['comment_values']);
            $defaults = PluginMetademandsField::_unserialize($data['default_values']);
            if (!empty($value)) {
                $value = PluginMetademandsField::_unserialize($value);
            }
            $nbr = 0;
            $inline = "";
            if ($data['row_display'] == 1) {
                $inline = 'custom-control-inline';
            }
            $field = "";


            foreach ($data['custom_values'] as $key => $label) {
                $field .= "<div class='custom-control custom-checkbox $inline'>";
                $checked = "";
                if (isset($value[$key])) {
                    $checked = isset($value[$key]) ? 'checked' : '';
                } elseif (isset($defaults[$key]) && $on_basket == false) {
                    $checked = ($defaults[$key] == 1) ? 'checked' : '';
                }
                $required = "";
                if ($data['is_mandatory'] == 1) {
                    $required = "required=required";
                }
                $field .= "<input $required class='form-check-input' type='checkbox' check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                $nbr++;
                $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
                if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                    $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                    $field .= Html::showToolTip(
                        Glpi\RichText\RichText::getSafeHtml($data['comment_values'][$key]),
                        ['awesome-class' => 'fa-info-circle',
                            'display' => false]
                    );
                    $field .= "</span>";
                }
                $field .= "</div>";

                $childs_blocks = [];
                $fieldopt = new PluginMetademandsFieldOption();
                if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $data['id'], "check_value" => $key])) {
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
                        $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($childs);
                        $script .= "$('div[bloc-id=\"bloc$childs\"]').hide();";
                    }
                    $script .= "}";
                    $script .= "})";
                    $script .= "</script>";

                    $field .= $script;
                }
            }
        } else {
            $checked = $value ? 'checked' : '';
            $field = "<input class='form-check-input' type='checkbox' name='" . $namefield . "[" . $data['id'] . "]' value='checkbox' $checked>";
        }

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params)
    {

        $default_values = PluginMetademandsField::_unserialize($params['default_values']);
        $comment_values = PluginMetademandsField::_unserialize($params['comment_values']);

        echo "<tr>";
        echo "<td>";
        if (is_array($values) && !empty($values)) {
            echo "<div id='drag'>";
            echo "<table class='tab_cadre_fixe'>";
            foreach ($values as $key => $value) {
                echo "<tr>";

                echo '<td class="rowhandler control center">';
                echo "<p id='custom_values$key'>";
                echo __('Value') . " " . $key . " ";
                $name = "custom_values[$key]";
                echo Html::input($name, ['value' => $value, 'size' => 30]);
                echo '</p>';
                echo "</td>";

                echo '<td class="rowhandler control center">';
                echo "<p id='comment_values$key'>";
                echo " " . __('Comment') . " ";
                $value_comment = "";
                if (isset($comment_values[$key])) {
                    $value_comment = $comment_values[$key];
                }
                $name = "comment_values[" . $key . "]";
                echo Html::input($name, ['value' => $value_comment, 'size' => 30]);
                echo '</p>';
                echo "</td>";
                echo "<td>";
                echo "<p id='default_values$key'>";
                echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                $name = "default_values[" . $key . "]";
                $value = ($default_values[$key] ?? 0);
                Dropdown::showYesNo($name, $value);
                echo '</p>';
                echo "</td>";

                echo '<td class="rowhandler control center">';
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                if (isset($params['id'])) {
                    echo PluginMetademandsField::showSimpleForm(
                        PluginMetademandsField::getFormURL(),
                        'delete_field_custom_values',
                        _x('button', 'Delete permanently'),
                        ['id' => $key,
                            'plugin_metademands_fields_id' => $params['id'],
                        ],
                        'fa-times-circle'
                    );
                }
                echo '</div>';
                echo '</td>';

                echo "</tr>";
            }
            if (isset($params['id'])) {
                echo Html::hidden('fields_id', ['value' => $params["id"], 'id' => 'fields_id']);
            }
            echo '</table>';
            echo '</div>';
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');

            echo "<tr>";
            echo "<td colspan='4' align='right' id='show_custom_fields'>";
            PluginMetademandsField::initCustomValue(max(array_keys($values)), true, true);
            echo "</td>";
            echo "</tr>";
        } else {
            echo __('Value') . " 0 ";
            echo Html::input('custom_values[0]', ['size' => 30]);
            echo "</td>";
            echo "<td>";
            echo " " . __('Comment') . " ";
            echo Html::input('comment_values[0]', ['size' => 30]);
            echo "</td>";
            echo "<td>";
            //                  echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            //                  echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
            echo "<p id='default_values$key'>";
            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            $name = "default_values[" . $key . "]";
            $value = ($default[$key] ?? 0);
            Dropdown::showYesNo($name, $value);
            echo '</p>';
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td colspan='3' align='right'  id='show_custom_fields'>";
            PluginMetademandsField::initCustomValue(0, true, true);
            echo "</td>";
            echo "</tr>";
        }
        echo "</tr>";

    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
        echo "</td>";
        echo "<td>";
        self::showValueToCheck($fieldoption, $params);
        echo "</td>";

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params, 1, 1, 1);
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
        $elements[-1] = Dropdown::EMPTY_VALUE;
        if (is_array(json_decode($params['custom_values'], true))) {
            $elements += json_decode($params['custom_values'], true);
        }
        foreach ($elements as $key => $val) {
            $elements[$key] = urldecode($val);
        }
        Dropdown::showFromArray(
            "check_value",
            $elements,
            ['value' => $params['check_value'], 'used' => $already_used]
        );
    }

    static function showParamsValueToCheck($params)
    {
        $elements = [];
        if (is_array(json_decode($params['custom_values'], true))) {
            $elements += json_decode($params['custom_values'], true);
        }
        foreach ($elements as $key => $val) {
            $elements[$key] = urldecode($val);
        }
        echo $elements[$params['check_value']] ?? 0;

    }

    static function isCheckValueOK($value, $check_value)
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
            } else if (is_array(json_decode($value, true))) {
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

    static function fieldsLinkScript($data, $idc, $rand)
    {

        $fields_link = $data['options'][$idc]['fields_link'];

        $script = "";
        $script .= "var metademandWizard$rand = $(document).metademandWizard();";
        $script .= "metademandWizard$rand.metademand_setMandatoryField(
                                        'metademands_wizard_red" . $fields_link . "',
                                        'field[" . $data['id'] . "][" . $idc . "]',[";
        $script .= $idc;
        $script .= "], '" . $data['item'] . "');";

        return $script;
    }

    static function fieldsHiddenScript($data)
    {

        $check_values = $data['options'];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('fieldsHiddenScript-checkbox $id');";
        }
        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        $script .= "var tohide = {};";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];

            $script .= " if (this.checked){";
            //                                        foreach ($hidden_link as $key => $fields) {
            $script .= " if ($(this).val() == $idc || $idc == -1) {
                            if ($hidden_link in tohide) {
                            } else {
                                tohide[$hidden_link] = true;
                            }
                            tohide[$hidden_link] = false;
                        }";

            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                    if ($fieldSession == $idc) {
                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                    }
                }
            }

            $script .= "$.each( tohide, function( key, value ) {                      
                            if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                                " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                                $('[name =\"field['+key+']\"]').removeAttr('required');
                            } else {
                                $('[id-field =\"field'+key+'\"]').show();
//                                " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                            }
                        });";

            $script .= "} else {";
            //not checked
            $script .= "if($(this).val() == $idc){
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

            $script .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                               $('[id-field =\"field'+key+'\"]').hide();
                               " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                               $('[name =\"field['+key+']\"]').removeAttr('required');
                            } else {
                               $('[id-field =\"field'+key+'\"]').show();
//                               " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                            }
                         });";
            $script .= "}";
        }
        $script .= "});";

        foreach ($check_values as $idc => $check_value) {

            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                    if ($fieldSession == $idc) {
                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                    }
                }
            }
            $hidden_link = $check_value['hidden_link'];
            //Initialize id default value
            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                        }
                    }
                }
            }
        }
        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('blocksHiddenScript-checkbox $id');";
        }
        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        $script .= "var tohide = {};";

        foreach ($check_values as $idc => $check_value) {

            $hidden_block = $check_value['hidden_block'];

            $script .= " if (this.checked) {";

            $script .= "if ($(this).val() == $idc || $idc == -1 ) {
                            if ($hidden_block in tohide) {
                            } else {
                                tohide[$hidden_block] = true;
                            }
                            tohide[$hidden_block] = false;
                        }";

            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
            $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);

            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                    if ($fieldSession == $idc || $idc == -1) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }

            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);

                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $childs_block) {
                                $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();
                                             " . PluginMetademandsFieldoption::resetMandatoryBlockFields($childs_block);
                                $hiddenblocks[] = $childs_block;
                                $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                            }
                        }
                    }
                }
            }

            $script .= "$.each(tohide, function( key, value ) {
                        if (value == true) {
                        } else {
                            $('[bloc-id =\"bloc'+key+'\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block) . "
                        }
                    });";

            $script .= " } else { ";

            $script .= "if($(this).val() == $idc){
                            if ($hidden_block in tohide) {
                            } else {
                                tohide[$hidden_block] = true;
                            }
                            $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){
                                if ($(value).val() == $idc || $idc == -1) {
                                    tohide[$hidden_block] = false;
                                }
                             });
                        }
                        fixButtonIndicator();";



//            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
//            " . PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);

            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                    if ($fieldSession == $idc || $idc == -1) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }

            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);

                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $childs_block) {
                                $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();
                                             " . PluginMetademandsFieldoption::resetMandatoryBlockFields($childs_block);
                                $hiddenblocks[] = $childs_block;
                                $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                            }
                        }
                    }
                }
            }

            $script .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                                $('[bloc-id =\"bloc'+key+'\"]').hide();
                                " . PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block) . "
                            } else {
                                $('[bloc-id =\"bloc'+key+'\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block) . "
                            }
                        });";

            $script .= " }";

            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);
                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                        }
                    }
                }
            }
        }


        $script .= "fixButtonIndicator();
        });";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function getFieldValue($field, $lang)
    {
        if (!empty($field['custom_values'])) {
            $custom_values = PluginMetademandsField::_unserialize($field['custom_values']);
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if (!empty($field['value'])) {
                if (is_string($field['value'])) {
                    $field['value'] = PluginMetademandsField::_unserialize($field['value']);
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

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if (!empty($field['custom_values'])) {
            $result[$field['rank']]['display'] = true;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } else {
            if ($field['value']) {
                $result[$field['rank']]['display'] = true;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td>";
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
