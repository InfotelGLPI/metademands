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
 * PluginMetademandsBasket Class
 *
 **/
class PluginMetademandsBasket extends CommonDBTM
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
        return __('Basket', 'metademands');
    }

    static function showWizardField($data, $on_order = false, $itilcategories_id = 0, $idline = 0)
    {
        global $DB;

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);
        $custom_values = PluginMetademandsField::_unserialize($data['custom_values']);
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }

        if ($on_order == false) {
            $namefield = 'field';
        } else {
            $namefield = 'field_basket_' . $idline;
        }

        $criteria = [
            'FROM' => [PluginMetademandsBasketobject::getTable()],
            'FIELDS' => [PluginMetademandsBasketobject::getTable() => '*'],
            'WHERE' => [],
            'ORDER' => ['name', 'description'],
        ];

        $criteria['WHERE'] = ['plugin_metademands_basketobjecttypes_id' => $data['item']];

        $where = [];
        if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
            $where = [
                'OR' => [
                    'estimated_price' => ['>', 0],
                    'is_specific' => 1,
                ]
            ];
            if (count($where)) {
                $criteria['LEFT JOIN'][PluginOrdermaterialMaterial::getTable()] = [
                    'ON' => [
                        PluginOrdermaterialMaterial::getTable() => 'plugin_metademands_basketobjects_id',
                        PluginMetademandsBasketobject::getTable() => 'id'
                    ]
                ];
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {

            $fields = [PluginOrderfollowupMaterial::getTable() => 'unit'];

            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                $where = ['unit_price' => ['>', 0]];
            }

            if (count($where)) {
                $criteria['LEFT JOIN'][PluginOrderfollowupMaterial::getTable()] = [
                    'ON' => [
                        PluginOrderfollowupMaterial::getTable() => 'plugin_metademands_basketobjects_id',
                        PluginMetademandsBasketobject::getTable() => 'id'
                    ]
                ];
            }
        }

        if (count($where)) {
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $where);
        }

        $materials = $DB->request($criteria);
        $nb = count($materials);

        $field = "<table class='tab_cadre_fixehov'>";
        $field .= "<tr class='tab_bg_1'>";
        $field .= "<th>" . __('Object', 'metademands') . "</th>";

        $field .= "<th>" . __('Description') . "</th>";

        if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
            $ordermaterialmeta = new PluginOrdermaterialMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                $field .= "<th>" . __('Estimated unit price', 'ordermaterial') . "</th>";
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $ordermaterialmeta = new PluginOrderfollowupMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                $field .= "<th>" . __('Unit', 'orderfollowup') . "</th>";

                if (isset($custom_values[1]) && $custom_values[1] == 1) {
                    $field .= "<th>" . __('Estimated unit price', 'orderfollowup') . "</th>";
                }
            }
        }

        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            $field .= "<th>" . __('Quantity', 'metademands') . "</th>";
        }
        if (isset($custom_values[0]) && $custom_values[0] == 0) {
            $field .= "<th>" . __('Select', 'metademands') . "</th>";
        }
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            $field .= "<th style='text-align: right;'>" . __('Total', 'metademands') . "</th>";
        }

        $field .= "</tr>";

        if ($nb > 10) {
            $field .= "<tr class='tab_bg_1'>";
            $field .= "<th>";
            $field .= "<input type='text' id='searchname' placeholder='" . __('Search for names..', 'metademands') . "'>";
            $field .= "</th>";
            $field .= "<th>";
            $field .= "<input type='text' id='searchdescription' placeholder='" . __('Search for description..', 'metademands') . "'>";
            $field .= "</th>";
            $field .= "<th colspan='4'>";
            $field .= "</th>";
            $field .= "</tr>";
        }

        $field .= "<tbody id='tablesearch'>";

        if (isset($custom_values[0]) && $custom_values[0] == 0) {

            foreach ($materials as $material) {
                $key = $material['id'];

                $field .= "<tr class='tab_bg_1'>";
                $field .= "<td>";
                $field .= $material['name'];
                $field .= "</td>";

                $field .= "<td>";
                $field .= Glpi\RichText\RichText::getSafeHtml($material['description']);
                $field .= "</td>";

                if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) &&  $custom_values[1] == 1) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            if ($ordermaterial->fields['is_specific'] == 1) {
                                $field .= "<td>";
                                $field .= __('On quotation', 'ordermaterial');
                                $field .= "</td>";
                            } else {
                                $field .= "<td>";
                                $field .= Html::formatNumber($ordermaterial->fields['estimated_price'], false, 2) . " €";
                                $field .= "</td>";
                            }
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                        $ordermaterial = new PluginOrderfollowupMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {

                            $field .= "<td>";
                            $field .= $ordermaterial->fields['unit'];
                            $field .= "</td>";

                            if (isset($custom_values[1]) &&  $custom_values[1] == 1) {
                                $field .= "<td>";
                                $field .= Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €";
                                $field .= "</td>";
                            }
                        }
                    }
                }

                $field .= "<td>";
                $checked = '';
                $required = "";
//                if ($data['is_mandatory'] == 1) {
//                    $required = "required=required";
//                }
                $value_check = $key;
                if (isset($value) && is_array($value)) {
                    foreach ($value as $val) {
                        if ($val == $key) {
                            $checked = "checked";
                        }
                    }
                }
                $field .= "<input $required class='form-check-input' type='checkbox'
                check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]'
                key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$value_check' $checked>";

                $field .= "</td>";
                $field .= "</tr>";
            }

        } else {

            foreach ($materials as $material) {

                $key = $material['id'];
                $field .= "<tr class='tab_bg_1'>";
                $field .= "<td>";
                $field .= $material['name'];
                $field .= "</td>";

                $field .= "<td>";
                $field .= Glpi\RichText\RichText::getSafeHtml($material['description']);
                $field .= "</td>";

                if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            if ($ordermaterial->fields['is_specific'] == 1) {
                                $field .= "<td>";
                                $field .= __('On quotation', 'ordermaterial');
                                $field .= "</td>";
                            } else {
                                $field .= "<td>";
                                $field .= Html::formatNumber($ordermaterial->fields['estimated_price'], false, 2) . " €";
                                $field .= "</td>";
                            }
                        } else {
                            $field .= "<td>";
                            $field .= "</td>";
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                        $ordermaterial = new PluginOrderfollowupMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {

                            $field .= "<td>";
                            $field .= $ordermaterial->fields['unit'];
                            $field .= "</td>";

                            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                                $field .= "<td>";
                                $field .= Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €";
                                $field .= "</td>";
                            }

                        } else {

                            $field .= "<td>";
                            $field .= "</td>";

                            $field .= "<td>";
                            $field .= "</td>";
                        }
                    }
                }

                $field .= "<td>";
                $functiontotal = "plugin_metademands_load_totalrow" . $key;

                $rand = mt_rand();
                $name_field = "dropdown_quantity[" . $data['id'] . "][" . $key . "]";

                $opt = ['min' => 0,
                    'max' => 1000,
                    'step' => 1,
                    'display' => false,
                    'rand' => $rand,
                    'on_change' => $functiontotal . '()',
                ];

                if (isset($value) && is_array($value)) {
                    foreach ($value as $k => $val) {
                        if ($k == $key) {
                            $opt['value'] = $val;
                        }
                    }
                }

                if (isset($data["is_mandatory"]) && $data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => 'required', 'ismultiplenumber' => 'ismultiplenumber'];
                }

                $field .= Dropdown::showNumber("quantity[" . $data['id'] . "][" . $key . "]", $opt);

                $check_hidden = $namefield . "[" . $data['id'] . "]";
                $name_hidden = $namefield . "[" . $data['id'] . "][" . $key . "]";
                $field .= "<script type='text/javascript'>";
                $field .= "function plugin_metademands_load_totalrow$key(){";
                $params = ['action' => 'loadTotalrow',
                    'quantity' => '__VALUE__',
                    'plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                    'check' => $check_hidden,
                    'name' => $name_hidden,
                    'key' => $key
                ];
                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            $params['estimated_price'] = $ordermaterial->fields['estimated_price'];
                        }
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']])) {
                        $ordermaterial = new PluginOrderfollowupMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            $params['unit_price'] = $ordermaterial->fields['unit_price'];
                        }
                    }
                }
                $rand_totalrow = mt_rand();
                $field .= Ajax::updateItemJsCode('plugin_metademands_totalrow' . $rand_totalrow,
                    PLUGIN_METADEMANDS_WEBDIR . '/ajax/totalrow.php',
                    $params, $name_field . $rand, false);

//                $params_total = ['action' => 'loadGrandTotal'];
//                $field .= Ajax::updateItemJsCode('plugin_ordermaterial_grandtotal',
//                    PLUGIN_ORDERMATERIAL_WEBDIR . '/ajax/totalrow.php',
//                    $params_total, $name_field . $rand, false);
                $field .= "}";

                $field .= "</script>";
                $field .= "</td>";

                $field .= "<td style='text-align: right;' id='plugin_metademands_totalrow$rand_totalrow'>";
                $field .= "</td>";

                $field .= "</tr>";
            }
        }
        $field .= "</tbody>";
        $field .= "</table>";

        echo $field;
    }

    static function showFieldCustomValues($values, $key, $params)
    {

        $params['custom_values'] = PluginMetademandsField::_unserialize($params['custom_values']);
        $quantity = $params['custom_values'][0] ?? 0;
        $price = $params['custom_values'][1] ?? 0;

        echo "<tr><td>";
        echo "<table class='metademands_show_custom_fields'>";
        echo "<tr><td>";
        echo __('With quantity', 'metademands');
        echo '</td>';
        echo "<td>";
        Dropdown::showYesNo('custom_values[0]', $quantity);
        echo "</td></tr>";
        if (Plugin::isPluginActive('ordermaterial') || Plugin::isPluginActive('orderfollowup')) {
            echo "<tr><td>";
            echo __('With estimated unit price', 'metademands');
            echo '</td>';
            echo "<td>";
            Dropdown::showYesNo('custom_values[1]', $price);
            echo "</td></tr>";
        }
        echo "</table>";
        echo "</td></tr>";
    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
        echo "</td>";
        echo "<td>";

        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
//        if ($item == 0) {
//            foreach ($existing_options as $existing_option) {
//                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
//            }
//        }
        $name = "check_value";
        $cond = [];


        if (!empty($params['custom_values'])) {
            $options = $params['custom_values'];
            if (is_array($options)) {
                foreach ($options as $type_group => $values) {
                    $cond[$type_group] = $values;
                }
            }
        }
        $cond['plugin_metademands_basketobjecttypes_id'] = $params['item'];

        PluginMetademandsBasketobject::dropdown(['name' => $name,
            'entity' => $_SESSION['glpiactiveentities'],
            'value' => $params['check_value'],
            //                                            'readonly'  => true,
            'condition' => $cond,
            'display' => true,
            'used' => $already_used
        ]);

        echo "</td>";

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params, 1, 1, 1);
    }

    static function showValueToCheck($item, $params)
    {
//        $field = new PluginMetademandsFieldOption();
//        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
//        $already_used = [];
//        if ($item->getID() == 0) {
//            foreach ($existing_options as $existing_option) {
//                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
//            }
//        }
        return Dropdown::getDropdownName('glpi_plugin_metademands_basketobjects', $params['check_value']);
//        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }


    /**
     * @param array $value
     * @param array $fields
     * @return bool
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {

//        $msg = "";
//        $checkKo = 0;
//        // Check fields empty
//        if ($value['is_mandatory']
//            && empty($fields['value'])) {
//            $msg = $value['name'];
//            $checkKo = 1;
//        }
//
//        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    static function isCheckValueOK($value, $check_value)
    {
//        if (($check_value == 2 && $value != "")) {
//            return false;
//        } elseif ($check_value == 1 && $value == "") {
//            return false;
//        }
    }

    static function showParamsValueToCheck($params)
    {

        return Dropdown::getDropdownName('glpi_plugin_metademands_basketobjects', $params['check_value']);

    }

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function fieldsHiddenScript($data)
    {

        $check_values = $data['options'] ??[];
        $id = $data["id"];

        $withquantity = false;
        $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
        if (isset($data['custom_values'][0]) &&  $data['custom_values'][0] == 1) {
            $withquantity = true;
        }

        if ($withquantity == false) {
            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        } else {
            $name = "quantity[" . $data["id"] . "]";

            $script = "$('[name^=\"$name\"]').change(function() {";
        }

        $script2 = "";
        $script .= "var tohide = {};";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];

            if ($withquantity == false) {

            $script .= " if (this.checked){";
            //                                        foreach ($hidden_link as $key => $fields) {
            $script .= " if ($(this).val() == $idc || $idc == -1) { ";

            } else {
                $script .= "if ($(this).val() > 0 ) { ";

            }
            $script .= "if ($hidden_link in tohide) {
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

            //checkbox
            $script .= "$.each( tohide, function( key, value ) {
                                                        if(value == true){
                                                           $('[id-field =\"field'+key+'\"]').hide();
                                                           $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {
        
                                                         switch(this.type) {
                                                                case 'password':
                                                                case 'text':
                                                                case 'textarea':
                                                                case 'file':
                                                                case 'date':
                                                                case 'number':
                                                                case 'tel':
                                                                case 'email':
                                                                    jQuery(this).val('');
                                                                    break;
                                                                case 'select-one':
                                                                case 'select-multiple':
                                                                    jQuery(this).val('0').trigger('change');
                                                                    jQuery(this).val('0');
                                                                    break;
                                                                case 'checkbox':
                                                                case 'radio':
                                                                     if(this.checked == true) {
                                                                            this.click();
                                                                            this.checked = false;
                                                                            break;
                                                                        }
                                                            }
                                                            regex = /multiselectfield.*_to/g;
                                                            totest = this.id;
                                                            found = totest.match(regex);
                                                            if(found !== null) {
                                                              regex = /multiselectfield[0-9]*/;
                                                               found = totest.match(regex);
                                                               $('#'+found[0]+'_leftAll').click();
                                                            }
                                                        });
                                                           $('[name =\"field['+key+']\"]').removeAttr('required');
                                                        } else {
                                                           $('[id-field =\"field'+key+'\"]').show();
                                                        }
                                                     });";

            if ($withquantity == false) {
                $script .= "} else {";
                //                                        foreach ($hidden_link as $key => $fields) {
                $script .= "if($(this).val() == $idc){
                            if($hidden_link in tohide){

                            }else{
                               tohide[$hidden_link] = true;
                            }
                            $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                    $script .= "if($(value).val() == $idc || $idc == -1 ){
                                   tohide[$hidden_link] = false;
                                }";
                    $script .= "});";

                $script .= "}";



            $script .= "$.each( tohide, function( key, value ) {
                            if(value == true){
                               $('[id-field =\"field'+key+'\"]').hide();
                               $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {

                             switch(this.type) {
                                    case 'password':
                                    case 'text':
                                    case 'textarea':
                                    case 'file':
                                    case 'date':
                                    case 'number':
                                    case 'tel':
                                    case 'email':
                                        jQuery(this).val('');
                                        break;
                                    case 'select-one':
                                    case 'select-multiple':
                                        jQuery(this).val('0').trigger('change');
                                        jQuery(this).val('0');
                                        break;
                                    case 'checkbox':
                                    case 'radio':
                                         if(this.checked == true) {
                                                this.click();
                                                this.checked = false;
                                                break;
                                            }
                                }
                                regex = /multiselectfield.*_to/g;
                                totest = this.id;
                                found = totest.match(regex);
                                if(found !== null) {
                                  regex = /multiselectfield[0-9]*/;
                                   found = totest.match(regex);
                                   $('#'+found[0]+'_leftAll').click();
                                }
                            });
                               $('[name =\"field['+key+']\"]').removeAttr('required');
                            }else{
                               $('[id-field =\"field'+key+'\"]').show();
                            }
                         });";
            $script .= "}";
            }
//                                    }
        }
        $script .= "});console.log('fieldshidden-checkbox');";

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
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $withquantity = false;
        $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
        if (isset($data['custom_values'][0]) && $data['custom_values'][0] == 1) {
            $withquantity = true;
        }

        if ($withquantity == false) {
            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        } else {
            $name = "quantity[" . $data["id"] . "]";

            $script = "$('[name^=\"$name\"]').change(function() {";
        }

        $script2 = "";
        $script .= "var tohide = {};";
        foreach ($check_values as $idc => $check_value) {
            $hidden_block = $check_value['hidden_block'];

            if ($withquantity == false) {
                $script .= " if (this.checked){ ";

                $script .= "if($(this).val() == $idc || $idc == -1 ){ ";
            } else {
                $script .= "if($(this).val() > 0 ){ ";

            }
            $script .= " if ($hidden_block in tohide) {
                             } else {
                                tohide[$hidden_block] = true;
                             }
                             tohide[$hidden_block] = false;
                          }";


            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                    if ($fieldSession == $idc || $idc == -1) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }
            //                                        }

            $script .= "$.each( tohide, function( key, value ) {
                            if(value == true){
                             $('[bloc-id =\"bloc'+key+'\"]').hide();
                             $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                switch(this.type) {
                                       case 'password':
                                       case 'text':
                                       case 'textarea':
                                       case 'file':
                                       case 'date':
                                       case 'number':
                                       case 'tel':
                                       case 'email':
                                           jQuery(this).val('');
                                           break;
                                       case 'select-one':
                                       case 'select-multiple':
                                           jQuery(this).val('0').trigger('change');
                                           jQuery(this).val('0');
                                           break;
                                       case 'checkbox':
                                       case 'radio':
                                           this.checked = false;
                                           break;
                                   }
                               });
                            } else {
                            $('[bloc-id =\"bloc'+key+'\"]').show();

                            }

                         });";
            $script .= "fixButtonIndicator();console.log('hidden-checkbox1');";

            if ($withquantity == false) {
                $script .= " } else { ";
                //                                        foreach ($hidden_block as $key => $fields) {
                $script .= "if ($(this).val() == $idc) {
                                 if ($hidden_block in tohide) {

                                 } else {
                                    tohide[$hidden_block] = true;
                                 }
                                 $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                $script .= "if ($(value).val() == $idc || $idc == -1 ) {
                                   tohide[$hidden_block] = false;
                                }";
                $script .= " });
                        }
                        fixButtonIndicator();console.log('hidden-checkbox2');";
                $script .= " }";
            }
            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                    if ($fieldSession == $idc || $idc == -1) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }

            $script .= "$.each( tohide, function( key, value ) {
                            if(value == true){
                                 $('[bloc-id =\"bloc'+key+'\"]').hide();
                                 $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                    switch(this.type) {
                                           case 'password':
                                           case 'text':
                                           case 'textarea':
                                           case 'file':
                                           case 'date':
                                           case 'number':
                                           case 'tel':
                                           case 'email':
                                               jQuery(this).val('');
                                               break;
                                           case 'select-one':
                                           case 'select-multiple':
                                               jQuery(this).val('0').trigger('change');
                                               jQuery(this).val('0');
                                               break;
                                           case 'checkbox':
                                           case 'radio':
                                                if(this.checked == true) {
                                                    this.click();
                                                    this.checked = false;
                                                    break;
                                                }
                                       }
                                   });
                            } else {
                                $('[bloc-id =\"bloc'+key+'\"]').show();
                            }
                        });
                        fixButtonIndicator();console.log('hidden-checkbox3');
                        ";
        }
        $script .= "});";

        //Initialize id default value
        foreach ($check_values as $idc => $check_value) {

            //include child blocks
            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        foreach ($childs as $childs_block) {
                            $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();";
                            $hiddenblocks[] = $childs_block;
                            $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                        }
                    }
                }
            }

            $hidden_block = $check_value['hidden_block'];
            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);
                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                        }
                    }
                }
            }
        }


        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

    }

    /**
     * @param $idline
     * @param $values
     * @param $fields
     */
    public static function retrieveDatasByType($id, $value)
    {

//        foreach ($fields as $k => $v) {

        $field = new PluginMetademandsField();
        if ($field->getFromDB($id)) {
            //hide blocks
//            if ($field->fields['type'] == 'informations' || $field->fields['type'] == 'title-block' || $field->fields['type'] == 'title') {
//                continue;
//            }

            if (!empty($value) && isset($field->fields['type']) && $field->fields['type'] != "basket") {
//            echo "<tr class='tab_bg_1'>";

                echo "<td>";

                if (empty($label = PluginMetademandsField::displayField($field->fields['id'], 'name'))) {
                    $label = $field->fields['name'];
                    echo $label;
                }

                if ($field->fields['type'] == "date_interval") {
                    if (empty($label2 = PluginMetademandsField::displayField($field->fields['id'], 'label2'))) {
                        $label2 = $field->fields['label2'];
                    }
                    echo "<br><br><br>" . Toolbox::stripTags($label2);
                }

                echo "</td>";

                echo "<td>";
                $lang = $_SESSION['glpilanguage'];

                $values['value'] = $value;
                $values['item'] = $field->fields['item'];

//            if ($field->fields['type'] == "date_interval" || $field->fields['type'] == "datetime_interval") {
//                if (isset($value['value2'])) {
//                    $v['value'] = $value['value2'];
//                }
//
//            }

                switch ($field->fields['type']) {
                    case 'dropdown':
                        echo PluginMetademandsDropdown::getFieldValue($values);
                        break;
                    case 'dropdown_object':
                        echo PluginMetademandsDropdownobject::getFieldValue($values);
                        break;
                    case 'dropdown_meta':
                        echo PluginMetademandsDropdownmeta::getFieldValue($values, $lang);
                        break;
                    case 'dropdown_multiple':
                        echo PluginMetademandsDropdownmultiple::getFieldValue($values, $lang);
                        break;
                    case 'link':
                        echo PluginMetademandsLink::getFieldValue($values);
                        break;
                    case 'textarea':
                        echo PluginMetademandsTextarea::getFieldValue($values);
                        break;
                    case 'text':
                        echo PluginMetademandsText::getFieldValue($values);
                        break;
                    case 'checkbox':
                        $values['custom_values'] = $field->fields['custom_values'];
                        $values['id'] = $id;
                        echo PluginMetademandsCheckbox::getFieldValue($values, $lang);
                        break;
                    case 'radio':
                        $values['custom_values'] = $field->fields['custom_values'];
                        $values['id'] = $id;
                        echo PluginMetademandsRadio::getFieldValue($values, $label, $lang);
                        break;
                    case 'date':
                        echo PluginMetademandsDate::getFieldValue($values);
                        break;
                    case 'datetime':
                        echo PluginMetademandsDatetime::getFieldValue($values);
                        break;
                    case 'date_interval':
                        echo PluginMetademandsDateinterval::getFieldValue($values);
                        break;
                    case 'datetime_interval':
                        echo PluginMetademandsDatetimeinterval::getFieldValue($values);
                        break;
                    case 'number':
                        echo PluginMetademandsNumber::getFieldValue($values);
                        break;
                    case 'yesno':
                        echo PluginMetademandsYesno::getFieldValue($values);
                        break;
                    default:
                        echo $value;
                        break;
                }
                echo "</td>";
            }
        }
    }

    static function displayBasketSummary($fields)
    {

        $materials = $fields["field"] ?? [];
        $freeinputs = $fields["freeinputs"] ?? [];
        $quantities = $fields["quantity"] ?? [];
        $content = "";

        $count = 0;
        $columns = 2;

        if (is_array($materials) && count($materials) > 0) {

//            $meta = new PluginMetademandsMetademand();
//            if ($meta->getFromDB($fields['metademands_id'])) {
//                $title_color = "#000";
//                if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
//                    $title_color = $meta->fields['title_color'];
//                }
//
//                $color = PluginMetademandsWizard::hex2rgba($title_color, "0.03");
//                $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 15px;'";
//                echo "<div class='card-header d-flex justify-content-between align-items-center md-color' $style_background>";// alert alert-light
//
//                echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
//                echo __('Demand details', 'metademands');
//                echo "</h2>";
//
//                echo "</div>";
//            }

            echo "<table class='tab_cadre_fixe'>";
            foreach ($materials as $idline => $fieldlines) {
                $count++;
                if ($count > $columns) {
                    echo "<tr class='tab_bg_1'>";
                }
                self::retrieveDatasByType($idline, $fieldlines);
                if ($count > $columns) {
                    echo "</tr>";
                    $count = 0;
                }
            }
            echo "</table>";
        }

        $meta = new PluginMetademandsMetademand();
        if ($meta->getFromDB($fields['metademands_id'])) {
            $title_color = "#000";
            if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
                $title_color = $meta->fields['title_color'];
            }

            $color = PluginMetademandsWizard::hex2rgba($title_color, "0.03");
            $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 15px;margin-top: 15px;'";
            echo "<div class='card-header d-flex justify-content-between align-items-center md-color' $style_background>";// alert alert-light

            echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
            echo __('Basket summary', 'metademands');
            echo "</h2>";

            echo "</div>";
        }

        if (is_array($materials) && count($materials) > 0) {

            $content .= "<table class='tab_cadre_fixe'>";
            $content .= "<tr class='tab_bg_1'>";
            $content .= "<th style='border: 1px solid black;'>" . __('Object', 'metademands') . "</th>";
            if (Plugin::isPluginActive('ordermaterial')) {
                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                    $content .= "<th style='border: 1px solid black;'>" . __('Estimated unit price', 'ordermaterial') . "</th>";
                }
            }
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                    $content .= "<th style='border: 1px solid black;'>" . __('Estimated unit price', 'orderfollowup') . "</th>";
                }
            }

            $content .= "<th style='border: 1px solid black;'>" . __('Quantity', 'metademands') . "</th>";
            $content .= "<th style='border: 1px solid black;text-align: right;'>" . __('Total', 'metademands') . "</th>";
            $content .= "</tr>";

            $grandtotal = 0;
            $withprice = false;
            $withquantity = false;

            foreach ($materials as $id => $material) {

                $field = new PluginMetademandsField();
                $field->getFromDB($id);

                if (isset($field->fields['custom_values'])) {
                    $custom_values = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                    if (isset($custom_values[0]) && $custom_values[0] == 1) {
                        $withquantity = true;
                    }

                    if (isset($custom_values[1]) && $custom_values[1] == 1) {
                        $withprice = true;
                    }
                }
                if (is_array($material)) {
                    foreach ($material as $mat_id) {

                        $material = new PluginMetademandsBasketobject();
                        if ($material->getFromDB($mat_id)) {

                            if ($withquantity == false) {
                                $quantity = 1;
                            } else {
                                $quantity = 0;
                            }

                            if (isset($quantities[$id][$mat_id])) {
                                $quantity = $quantities[$id][$mat_id];
                            }

                            if ($withquantity == true && $quantity == 0) {
                                continue;
                            }

                            $content .= "<tr class='tab_bg_1'>";

                            $content .= "<td style='border: 1px solid black;'>";
                            $content .= $material->getName();
                            $content .= "</td>";


                            if (Plugin::isPluginActive('ordermaterial')) {
                                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                                    $ordermaterial = new PluginOrdermaterialMaterial();
                                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                                        if ($ordermaterial->fields['is_specific'] == 1) {
                                            $content .= "<td style='border: 1px solid black;'>";
                                            $content .= __('On quotation', 'ordermaterial');
                                            $content .= "</td>";
                                        } else {
                                            $content .= "<td style='border: 1px solid black;'>";
                                            if ($withprice) {
                                                $content .= Html::formatNumber($ordermaterial->fields['estimated_price'], false, 2) . " €";
                                            }
                                            $content .= "</td>";
                                        }
                                    } else {
                                        $content .= "<td style='border: 1px solid black;'>";
                                        $content .= "</td>";
                                    }
                                }
                            }

                            if (Plugin::isPluginActive('orderfollowup')) {
                                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                                    $ordermaterial = new PluginOrderfollowupMaterial();
                                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                                        $content .= "<td style='border: 1px solid black;'>";
                                        if ($withprice) {
                                            $content .= Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €";
                                        }
                                        $content .= "</td>";
                                    } else {
                                        $content .= "<td style='border: 1px solid black;'>";
                                        $content .= "</td>";
                                    }
                                }
                            }

                            $content .= "<td style='border: 1px solid black;'>";
                            $content .= $quantity;
                            $content .= "</td>";

                            $content .= "<td style='border: 1px solid black;text-align: right;'>";

                            $totalrow = $quantity;
                            if (Plugin::isPluginActive('ordermaterial')) {
                                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                                    $ordermaterial = new PluginOrdermaterialMaterial();
                                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id]) && $withprice) {
                                        $totalrow = $quantity * $ordermaterial->fields['estimated_price'];
                                    }
                                }
                            }
                            if (Plugin::isPluginActive('orderfollowup')) {
                                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                                    $ordermaterial = new PluginOrderfollowupMaterial();
                                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id]) && $withprice) {
                                        $totalrow = $quantity * $ordermaterial->fields['unit_price'];
                                    }
                                }
                            }

                            if (Plugin::isPluginActive('ordermaterial') && $withprice) {
                                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                                    $ordermaterial = new PluginOrdermaterialMaterial();
                                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                                        if ($ordermaterial->fields['is_specific'] == 1) {
                                            $content .= __('On quotation', 'ordermaterial');
                                        } else {
                                            $content .= Html::formatNumber($totalrow, false, 2);
                                            $content .= " €";
                                        }
                                    }
                                }
                            } else if (Plugin::isPluginActive('orderfollowup') && $withprice) {
                                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']])) {
                                    $ordermaterial = new PluginOrderfollowupMaterial();
                                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                                        $content .= Html::formatNumber($totalrow, false, 2);
                                        $content .= " €";
                                    }
                                }
                            } else {
                                $content .= Html::formatNumber($totalrow, false, 0);
                            }

                            $grandtotal += $totalrow;
                            $content .= "</td>";

                            $content .= "</tr>";
                        }
                    }
                }
            }
            if (Plugin::isPluginActive('ordermaterial')) {
                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']]) && $withprice) {
                    $content .= "<tr class='tab_bg_1'>";
                    $content .= "<th style='border: 1px solid black;' colspan='3'>" . __('Grand total', 'ordermaterial') . "</th>";
                    $content .= "<th style='border: 1px solid black;text-align: right;'>" . Html::formatNumber($grandtotal, false, 2) . " €</th>";
                    $content .= "</tr>";
                    $content .= "<tr class='tab_bg_1'>";
                    $content .= "<td colspan='4' style='border: 1px solid black;'></td>";
                    $content .= "<td colspan='4'>" . __('* The prices are estimates and do not act as an estimate', 'ordermaterial') . "</td>";
                    $content .= "</tr>";
                }
            }
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $fields['metademands_id']]) && $withprice) {
                    $content .= "<tr class='tab_bg_1'>";
                    $content .= "<th style='border: 1px solid black;' colspan='3'>" . __('Grand total', 'orderfollowup') . "</th>";
                    $content .= "<th style='border: 1px solid black;text-align: right;'>" . Html::formatNumber($grandtotal, false, 2) . " €</th>";
                    $content .= "</tr>";
                }
            }
            $content .= "</table>";

//            if ($grandtotal > 0) {
            $content .= "<br><span style='float:right'>";
            $title = "<i class='fas fa-shopping-basket'></i> " . _sx('button', 'Send order', 'metademands');

            $current_ticket = $fields["current_ticket_id"] = $fields["tickets_id"];
            $content .= Html::submit($title, ['name' => 'send_order',
                'form' => '',
                'id' => 'submitOrder',
                'class' => 'btn btn-success right']);
            $content .= "</span>";

            $paramUrl = "";
            $meta_validated = false;
            if ($current_ticket > 0 && !$meta_validated) {
                $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
            }
            $post = json_encode($fields);
            $meta_id = $fields['metademands_id'];
            $content .= "<script>
                          $('#submitOrder').click(function() {
                             var meta_id = $meta_id;
                             $.ajax({
                               url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                               type: 'POST',
                               data: $post,
                               success: function (response) {
                                  $('#ajax_loader').hide();
                                  if (response == 1) {
                                     window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=2';
                                  } else {
                                     $.ajax({
                                        url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
                                        type: 'POST',
                                        data: $post,
                                        success: function (response) {
                                           window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=create_metademands';
                                        },
                                        error: function (xhr, status, error) {
                                           console.log(xhr);
                                           console.log(status);
                                           console.log(error);
                                        }
                                     });
                                  }
                               },
                               error: function (xhr, status, error) {
                                  console.log(xhr);
                                  console.log(status);
                                  console.log(error);
                               }
                            });
                          });
                          $('#prevBtn').hide();
                          $('.step_wizard').hide();
                          
                        </script>";
//            }

        } else if (is_array($freeinputs) && count($freeinputs) > 0) {

            $content .= "<table class='tab_cadre_fixe'>";
            var_dump($freeinputs);

            var_dump($fields);

            if (isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
                $freeinputs = $_SESSION['plugin_orderfollowup']['freeinputs'];
                foreach ($freeinputs as $freeinput) {
                    $fields['field'][] = $freeinput;
                }
            }


            $content .= "</table>";

//            if ($grandtotal > 0) {
            $content .= "<br><span style='float:right'>";
            $title = "<i class='fas fa-shopping-basket'></i> " . _sx('button', 'Send order', 'metademands');

            $current_ticket = $fields["current_ticket_id"] = $fields["tickets_id"];
            $content .= Html::submit($title, ['name' => 'send_order',
                'form' => '',
                'id' => 'submitOrder',
                'class' => 'btn btn-success right']);
            $content .= "</span>";

            $paramUrl = "";
            $meta_validated = false;
            if ($current_ticket > 0 && !$meta_validated) {
                $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
            }
            $post = json_encode($fields);
            $meta_id = $fields['metademands_id'];
            $content .= "<script>
                          $('#submitOrder').click(function() {
                             var meta_id = $meta_id;
                             $.ajax({
                               url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                               type: 'POST',
                               data: $post,
                               success: function (response) {
                                  $('#ajax_loader').hide();
                                  if (response == 1) {
                                     window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=2';
                                  } else {
                                     $.ajax({
                                        url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
                                        type: 'POST',
                                        data: $post,
                                        success: function (response) {
                                           window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=create_metademands';
                                        },
                                        error: function (xhr, status, error) {
                                           console.log(xhr);
                                           console.log(status);
                                           console.log(error);
                                        }
                                     });
                                  }
                               },
                               error: function (xhr, status, error) {
                                  console.log(xhr);
                                  console.log(status);
                                  console.log(error);
                               }
                            });
                          });
                          $('#prevBtn').hide();
                          $('.step_wizard').hide();
                          
                        </script>";
//            }

        } else {
            $content .= "<table>";
            $content .= "<tr class='tab_bg_1'>";
            $content .= "<th colspan='4'>" . __('No items on basket', 'metademands') . "</th>";
            $content .= "</tr>";
            $content .= "</table>";
        }
        return $content;
    }

    public static function getFieldValue($field)
    {

        return $field['value'];
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        $result[$field['rank']]['display'] = true;
        
        $meta_id = $field['plugin_metademands_metademands_id'];
        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'])) {
            $quantities = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'];
        }
        $style_td = "style = \'border: 1px solid black; \'";

        $materials = $field["value"];

        if (is_object($materials)) {
            $materials = json_decode(json_encode($materials), true);
        }

        $total = 0;
        $nb = 3;
        if (Plugin::isPluginActive('ordermaterial')) {
            $ordermaterialmeta = new PluginOrdermaterialMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                $nb = 4;
            }
        }
        if (Plugin::isPluginActive('orderfollowup')) {
            $ordermaterialmeta = new PluginOrderfollowupMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                $nb = 4;
            }
        }
        if (is_array($materials) && count($materials) > 0) {

            if ($formatAsTable) {
//                $result .= "<table $style_td>";
                $result[$field['rank']]['content'] .= "<tr>";
                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Object', 'metademands') . "</th>";

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __('Order type', 'ordermaterial') . "</th>";
                    }
                }
                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Reference', 'metademands') . "</th>";

                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Quantity', 'metademands') . "</th>";

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __('Estimated unit price', 'ordermaterial') . "</th>";
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __('Estimated unit price', 'orderfollowup') . "</th>";
                    }
                }

                $result[$field['rank']]['content'] .= "</tr>";
            }

            foreach ($materials as $id => $mat_id) {
                $totalrow = 0;

                $material = new PluginMetademandsBasketobject();
                $material->getFromDB($mat_id);

                $field['value'] = $material->getName();

                $fieldmeta = new PluginMetademandsField();
                $fieldmeta->getFromDB($field['id']);
                $withquantity = false;
                $custom_values = PluginMetademandsField::_unserialize($fieldmeta->fields['custom_values']);
                if ($custom_values[0] == 1) {
                    $withquantity = true;
                }

                if ($withquantity == false) {
                    $quantity = 1;
                } else {
                    $quantity = 0;
                }

                if (isset($quantities[$field['id']][$mat_id])) {
                    $quantity = $quantities[$field['id']][$mat_id];
                }
                if ($withquantity == true && $quantity == 0) {
                    continue;
                }

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<tr>";
                    $result[$field['rank']]['content'] .= "<td $style_td>";
                }
                $result[$field['rank']]['content'] .= ($field['value']);
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($ordermaterial->fields['is_specific']) {
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "<td $style_td>";
                                }
                                $result[$field['rank']]['content'] .= __('On quotation', 'ordermaterial');
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "</td $style_td>";
                                }
                            } else {
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "<td $style_td>";
                                }
                                $result[$field['rank']]['content'] .= __('On catalog', 'ordermaterial');
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "</td>";
                                }
                            }
                        }
                    }
                }


                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td $style_td>";
                }
                $result[$field['rank']]['content'] .= $material->fields['reference'];

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }

                if ($quantity > 0) {
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_td>";
                    }
                    $result[$field['rank']]['content'] .= $quantity;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }
                } else {
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_td>";
                    }
                    $result[$field['rank']]['content'] .= "1";
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }
                }

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "<td $style_td>";
                            }
                            $result[$field['rank']]['content'] .= Html::formatNumber($ordermaterial->fields['estimated_price'], false, 2) . " €";

                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td>";
                            }
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrderfollowupMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "<td $style_td>";
                            }
                            $result[$field['rank']]['content'] .= Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €";

                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td>";
                            }
                        }
                    }
                }

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</tr>";
                }
                $totalrow = $quantity;

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            $totalrow = $quantity * $ordermaterial->fields['estimated_price'];
                        }
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrderfollowupMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            $totalrow = $quantity * $ordermaterial->fields['unit_price'];
                        }
                    }
                }
                $total+= $totalrow;
            }

            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<tr>";
                $colspan = $nb - 1;
                $result[$field['rank']]['content'] .= "<td $style_td colspan='$colspan'>" . __('Total', 'metademands') . "</td>";
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_td>";
            }
            $total_final = Html::formatNumber($total, false, 2);
            if (Plugin::isPluginActive('ordermaterial')) {
                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                    $total_final .= " €";
                }
            }
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                    $total_final .= " €";
                }
            }
            $result[$field['rank']]['content'] .= $total_final;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
                $result[$field['rank']]['content'] .= "</tr>";
            }
        }

        return $result;
    }

    static function displayFieldPDF($elt, $fields, $label)
    {
        $value = " ";

        if (isset($_SESSION['plugin_metademands'][$elt['plugin_metademands_metademands_id']]['quantities'])) {
            $quantities = $_SESSION['plugin_metademands'][$elt['plugin_metademands_metademands_id']]['quantities'];
        }

        $materials = $fields[$elt['id']];
        foreach ($materials as $id => $mat_id) {

            $material = new PluginMetademandsBasketobject();
            $material->getFromDB($mat_id);

            $value = $material->getName();
            $quantity = 0;
            if (isset($quantities[$elt['id']][$mat_id])) {
                $quantity = $quantities[$elt['id']][$mat_id];
                if ($quantity > 0) {
                    $value .= " - " . __('Quantity', 'metademands') . " : " . $quantity;
                }
            }
            if ($value != null) {
                $value = Toolbox::decodeFromUtf8($value);
            }
        }
        return $value;
    }

}
