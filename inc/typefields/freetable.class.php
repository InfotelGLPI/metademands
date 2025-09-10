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
 * PluginMetademandsFreetable Class
 *
 **/
class PluginMetademandsFreetable extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

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
        return __('Free table', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        $field = "";

        $plugin_metademands_metademands_id = $data['plugin_metademands_metademands_id'];
        $meta = new PluginMetademandsMetademand();
        $meta->getFromDB($plugin_metademands_metademands_id);
        $background_color = "";
        if (isset($meta->fields['background_color'])
            && $meta->fields['background_color'] != "") {
            $background_color = "background-color:" . $meta->fields['background_color'] . ";";
        }
        $plugin_metademands_fields_id = $data['id'];

        if (!isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$data['id']])) {
            unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables']);
        }
        $nb = 0;
        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables'][$data['id']])) {
            $nb = count($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables'][$data['id']]);
        }
        $values = [];

        $idline = 0;

        if (isset($data['value'])
            && is_array($data['value'])) {
            $values = $data['value'];
        }
        $colspan = '4';

        $style_th = "style='text-align: left;$background_color'";
        $field .= Html::hidden('is_freetable_mandatory[' . $data['id'] . ']', ['value' => $data['is_mandatory']]);
        $colspanfields = 0;
        $addfields = [];
        $commentfields = [];
        $size = 30;
        $field_custom = new PluginMetademandsFreetablefield();
        $is_mandatory = [];
        $types = [];
        $dropdown_values = [];
        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
            if (count($customs) > 0) {
                foreach ($customs as $custom) {
                    $addfields[$custom['internal_name']] = $custom['name'];
                    $commentfields[$custom['internal_name']] = $custom['comment'];
                    if ($custom['is_mandatory'] == 1) {
                        $is_mandatory[] = $custom['internal_name'];
                    }
                    $types[$custom['internal_name']] = $custom['type'];

                    $dropdown_values_array = [];
                    $dropdown_values_array[0] = Dropdown::EMPTY_VALUE;
                    if (!empty($custom['dropdown_values'])) {
                        $explode = explode(",", $custom['dropdown_values']);
                        foreach ($explode as $val) {
                            $dropdown_values_array[] = Toolbox::cleanNewLines($val);
                        }
                    }

                    if ($custom['type'] == PluginMetademandsFreetablefield::TYPE_SELECT) {
                        $dropdown_values[$custom['internal_name']] = $dropdown_values_array;
                    }
                }
                $colspanfields = count($customs);
                if (count($customs) > 3) {
                    $size = 20;
                }
                if (count($customs) > 4) {
                    $size = 10;
                }
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $addfields['total'] = __('Total (TTC)', 'orderfollowup');
            $commentfields['total'] = '';
            $types['total'] = PluginMetademandsFreetablefield::TYPE_READONLY;
            $size = 17;
        }

        $rand = $data['id'];
        $field .= "<script>localStorage.setItem('nextnb', $nb);</script>";
        $field .= "<table class='tab_cadre' width='100%' id ='freetable_table$rand' style='overflow: auto;width:100%;$background_color'>";//display: block;
        $field .= "<tr class='tab_bg_1'>";
        foreach ($addfields as $k => $addfield) {
            $field .= "<th $style_th>";
            $field .= $addfield;
            if (in_array($k, $is_mandatory)) {
                $field .= "<span style='color : red'> *</span>";
            }
            if (isset($commentfields[$addfield]) && !empty($commentfields[$addfield])) {
                $field .= Html::showToolTip(
                    $commentfields[$addfield],
                    ['display' => false, 'awesome-class' => 'ti ti-info-circle']
                );
            }
            $field .= "</th>";
        }

        $encoded_fields = json_encode($addfields);
        $mandatory_encoded_fields = json_encode($is_mandatory);
        $empty_value = Dropdown::EMPTY_VALUE;
        $types_encoded_fields = json_encode($types);
        $dropdown_values_encoded_fields = [];
        $dropdown_values_encoded_fields = json_encode($dropdown_values);
        $field .= "<th style='text-align: right;$background_color' colspan='4' onclick='addLine$rand()'><i class='ti ti-plus btn btn-info'></i></th>";
        $field .= "</tr>";

        $style = "";
        $stylereadonly = "style= \'white-space: nowrap;text-align: right;background-color: #ffffff;\'";
        if (is_array($values) && count($values) > 0) {
            foreach ($values as $value) {
                $l = [
                    'id' => $idline,
                ];

                foreach ($addfields as $k => $addfield) {
                    if (isset($value[$k])) {
                        $l[$k] = $value[$k];
                    }
                }

                $field .= "<tr name=\"data\" $style id=\"line_" . $rand . "_$idline\" disabled>";

                foreach ($addfields as $k => $addfield) {
                    if (isset($l[$k])) {
                        if ($types[$k] == PluginMetademandsFreetablefield::TYPE_TEXT) {
                            $id = $k . '_' . $idline;
                            $field .= "<td $style><input id=\"$id\" name=\"$k\" type=\"text\" value=\"$l[$k]\" size=\"$size\" disabled></td>";
                        } elseif ($types[$k] == PluginMetademandsFreetablefield::TYPE_SELECT) {
                            $id = $k . '_' . $idline;
                            $field .= "<td $style><select id=\"$id\" name=\"$k\">";

                            foreach ($dropdown_values[$k] as $dropdown_value) {
                                $selected = "";
                                if ($dropdown_value == $l[$k]) {
                                    $selected = "selected";
                                }
                                $field .= "<option $selected value=\"$dropdown_value\">" . $dropdown_value . "</option>";
                            }
                            $field .= "</select></td>";
                        } elseif ($types[$k] == PluginMetademandsFreetablefield::TYPE_NUMBER) {
                            $id = $k . '_' . $idline;
                            $field .= "<td $style><input add=-1 id=\"$id\" name=\"$k\" type=\"number\" min=\"0\" value=\"$l[$k]\" style=\"width: 7em;\" disabled></td>";
                        } elseif ($types[$k] == PluginMetademandsFreetablefield::TYPE_DATE) {
                            $id = $k . '_' . $idline;
                            $field .= "<td $style><input add=-1 id=\"$id\" name=\"$k\" type=\"date\" value=\"$l[$k]\" disabled></td>";
                        } elseif ($types[$k] == PluginMetademandsFreetablefield::TYPE_TIME) {
                            $id = $k . '_' . $idline;
                            $field .= "<td $style><input add=-1 id=\"$id\" name=\"$k\" type=\"time\" value=\"$l[$k]\" disabled></td>";
                        }

                        if (Plugin::isPluginActive('orderfollowup')) {
                            $quantity = $l['quantity'];
                            $unit_price = $l['unit_price'];
                        }
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $linetotal = number_format($quantity * $unit_price, 2, '.', ' ');
                    $field .= "<td $style id=\"linetotal\">$linetotal €</td>";
                }

                $field .= "<td><button onclick =\"editLine$rand($idline)\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\"><i class =\"ti ti-pencil\"></i></button></td>";
                $field .= "<td><button onclick =\"removeLine$rand($idline)\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\"><i class =\"ti ti-trash\"></i></button></td>";
                $field .= "</tr>";
                $idline++;
            }
        }

        $existLine = __('You can\'t create a new line when there is an existing one', 'metademands');

        $orderfollowup_is_active = 0;
        if (Plugin::isPluginActive('orderfollowup')) {
            $orderfollowup_is_active = 1;
        }

        $field .= "<script>
                function getNextIndex() {
                    const nb = localStorage.getItem('nextnb');
                    return nb ? parseInt(nb) : 0;
                }
                var orderfollowup_is_active = $orderfollowup_is_active;

                function addLine$rand() {
                    var orderfollowup_is_active = $orderfollowup_is_active;
                    var fields = $encoded_fields;
                    var mandatory_fields = $mandatory_encoded_fields;
                    var type_fields = $types_encoded_fields;
                    var dropdown_values_fields = $dropdown_values_encoded_fields;
                    const tabfields = [];

                    if (!document.querySelector('#freetable_table$rand .add_item')) {

                        if ($('#freetable_table$rand tr[id^=line_' + $rand + '_]:first').length > 0) {

                            tabtr = '<tr class=\"tab_bg_1\" id=\"line_' + $rand + '_' + i + '\">';

                           $.each(fields,function(index, valuej){
                               if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                   tabfields.push('<td><input id = \"' + index +'\" type=\"text\" name=\"' + index +'\" size=\"$size\" ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                   var select_open = '<td><select id = \"' + index +'\" name=\"' + index +'\">';
                                   var select_options = '';
                                   $.each(dropdown_values_fields,function(indexv, values){
                                       $.each(values,function(indexd, valued){
                                            if (index == indexv) {
                                                select_options += '<option value=\"' + valued +'\">' + valued +'</option>';
                                            }
                                      });
                                   });
                                   var select_close = '</select></td>';
                                   var select = [select_open, select_options, select_close].join(' ');
                                   tabfields.push(select);
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                   tabfields.push('<td><input add=0 id = \"' + index +'\" type=\"number\" min=\"0\" name=\"' + index +'\" style=\"width: 7em;\" ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_READONLY . "  && orderfollowup_is_active) {
                                   tabfields.push('<td></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                   tabfields.push('<td><input add=0 id = \"' + index +'\" type=\"date\" name=\"' + index +'\" ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                   tabfields.push('<td><input add=0 id = \"' + index +'\" type=\"time\" name=\"' + index +'\" ></td>');
                               }

                           });

                           var str = '<button class =\"btn btn-success add_item\" type = \"button\" name =\"add_item\" onclick=\"confirmLine$rand(this)\">';
                           tabbutton = '<td style=\"text-align: right;\" colspan=\"2\">'
                           + str
                           + '<i class =\"ti ti-check\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"ti ti-trash\"></i></button></td>'
                               + '</tr>';

                           var joined = [tabtr, tabfields, tabbutton].join(' ');

                            $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after(joined);

                        } else {

                            tabtr = '<tr class=\"tab_bg_1\" id=\"line_' + $rand + '_' + i + '\">';

                            $.each(fields,function(index, valuej){
                               if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                   tabfields.push('<td><input id = \"' + index +'\" type=\"text\" name=\"' + index +'\" size=\"$size\" ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                   var select_open = '<td><select id = \"' + index +'\" name=\"' + index +'\">';
                                   var select_options = '';
                                   $.each(dropdown_values_fields,function(indexv, values){
                                       $.each(values,function(indexd, valued){
                                            if (index == indexv) {
                                                select_options += '<option value=\"' + valued +'\">' + valued +'</option>';
                                            }
                                      });
                                   });
                                   var select_close = '</select></td>';
                                   var select = [select_open, select_options, select_close].join(' ');
                                   tabfields.push(select);
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                   tabfields.push('<td><input add=1 id=\"' + index +'\" value=\"0\" type=\"number\" min=\"0\" name=\"' + index +'\" style=\"width: 7em;\" ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_READONLY . " && orderfollowup_is_active) {
                                   tabfields.push('<td></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                   tabfields.push('<td><input add=1 id=\"' + index +'\" value=\"0\" type=\"date\" name=\"' + index +'\" ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                   tabfields.push('<td><input add=1 id=\"' + index +'\" value=\"0\" type=\"time\" name=\"' + index +'\" ></td>');
                               }
                           });
                             var str = '<button class =\"btn btn-success add_item\" type = \"button\" name =\"add_item\" onclick=\"confirmLine$rand(this)\">';
                           tabbutton = '<td style=\"text-align: center;\" colspan=\"2\">'
                           + str
                           + '<i class =\"ti ti-check\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"ti ti-trash\"></i></button></td>'
                               + '</tr>';
                             var joined = [tabtr, tabfields, tabbutton].join(' ');

                            $('#freetable_table$rand tr[class^=tab_bg_1]:last').after(joined);
                        }

                    } else {
                        alert(\"$existLine\");
                    }


                }

                function confirmLine$rand (node) {

                    var orderfollowup_is_active = $orderfollowup_is_active;
                    var fields = $encoded_fields;
                    var type_fields = $types_encoded_fields;
                    var dropdown_values_fields = $dropdown_values_encoded_fields;
                    var mandatory_fields = $mandatory_encoded_fields;
                    var empty_value = '$empty_value';
                    var elem_parent = $(node).parent().parent();
                    var tabfields = [];
                    let nb = getNextIndex();

                    l = {
                        'id':nb,
                    };
                    $.each(fields,function(index, valuej){
                        if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                            l[index] = elem_parent.find('input[name='+ index +']').val();
                        } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                            l[index] = elem_parent.find('select[name='+ index +']').val();
                        } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                            l[index] = elem_parent.find('input[name='+ index +']').val();
                        } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                            l[index] = elem_parent.find('input[name='+ index +']').val();
                        } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                            l[index] = elem_parent.find('input[name='+ index +']').val();
                        }
                        l['type'] = type_fields[index];
                    });
                    var unit_price = elem_parent.find('input[name=unit_price]').val();
                    var quantity = elem_parent.find('input[name=quantity]').val();
                    //orderfollowup
                    if (orderfollowup_is_active) {
                        var total = quantity * unit_price;
                        l[4] = total;
                    }


                    let type = 'add';

//                    $.each(data.lines$rand, function (key, datas) {
//                        $.each(datas, function (key_data, data_lines) {
//                            if(key_data == 'id'){
//                                if(data_lines == ind){
//                                   data['lines$rand'][key] = l;
//                                   type = 'modif';
//                                }
//                            }
//                        });
//                    });

                    if(type == 'add') {
                        data['lines$rand'] = { ind : l };
                        data['metademands_id'] = $plugin_metademands_metademands_id;
                        data['fields_id'] = $plugin_metademands_fields_id;
                    }

                    $.ajax({
                           url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/freetable_item.php',
                           type: 'POST',
                           data: data
                        });
                    ko = 0;

                    $.each(fields,function(index, valuej){

                        if (mandatory_fields.includes(index)) {
                            if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                if (elem_parent.find('input[name='+ index +']').val() === '') {
                                    elem_parent.find('input[name=' + index +']').css('border-color', 'red');
                                    ko = 1;
                                } else {
                                    elem_parent.find('input[name=' + index +']').css('border-color', '');
                                }
                            } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                var select = document.getElementById(index);
                                if(select.selectedIndex != undefined) {
                                    var text = select.options[select.selectedIndex].text;
                                    if (text == empty_value) {
                                        select.style.borderColor = 'red';
                                        ko = 1;
                                    } else {
                                        select.style.borderColor = '';
                                    }
                                }
                            } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                if (elem_parent.find('input[name='+ index +']').val() == 0) {
                                    elem_parent.find('input[name=' + index +']').css('border-color', 'red');
                                    ko = 1;
                                } else {
                                    elem_parent.find('input[name=' + index +']').css('border-color', '');
                                }
                            } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                if (elem_parent.find('input[name='+ index +']').val() == 0) {
                                    elem_parent.find('input[name=' + index +']').css('border-color', 'red');
                                    ko = 1;
                                } else {
                                    elem_parent.find('input[name=' + index +']').css('border-color', '');
                                }
                            } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                if (elem_parent.find('input[name='+ index +']').val() == 0) {
                                    elem_parent.find('input[name=' + index +']').css('border-color', 'red');
                                    ko = 1;
                                } else {
                                    elem_parent.find('input[name=' + index +']').css('border-color', '');
                                }
                            }
                        }

                    });
                    //orderfollowup
                    if (orderfollowup_is_active) {
                        total = Math.round((total + Number.EPSILON) * 100)/100;
                    }
                    if (ko == 0) {
                        if ($('[id^=line_' + $rand + '_]').length == 0) {

                             tabtr = '<tr name=\"data\" $style id=\"line_' + $rand + '_' + i + '\" disabled>';

                            $.each(fields,function(index, valuej){

                               if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                   tabfields.push('<td $style><input id = \"' + index +'_' + i +'\" type=\"text\" name=\"' + index +'\" size=\"$size\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                   var select_open = '<td $style><select id = \"' + index +'_' + i +'\" name=\"' + index +'\">';
                                   var select_options = '';
                                   $.each(dropdown_values_fields,function(indexv, values){
                                       $.each(values,function(indexd, valued){
                                            if (index == indexv) {
                                                select_options += '<option value=\"' + valued +'\">' + valued +'</option>';
                                            }
                                      });
                                   });
                                   var select_close = '</select></td>';
                                   var select = [select_open, select_options, select_close].join(' ');
                                   tabfields.push(select);
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                    tabfields.push('<td $style><input add=2 id= \"' + index +'_' + i +'\" type=\"number\" min=\"0\" name=\"' + index +'\" style=\"width: 7em;\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_READONLY . " && orderfollowup_is_active) {
                                   //orderfollowup
                                   tabfields.push('<td $style id=\"linetotal\">' + total.toFixed(2) +' €</td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                    tabfields.push('<td $style><input add=2 id= \"' + index +'_' + i +'\" type=\"date\" name=\"' + index +'\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                    tabfields.push('<td $style><input add=2 id= \"' + index +'_' + i +'\" type=\"time\" name=\"' + index +'\" disabled ></td>');
                               }

                            });
                            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine$rand(' + i +')\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                               + '<i class =\"ti ti-pencil\"></i></button></td>'
                               + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"ti ti-trash\"></i></button></td></tr>'

                            var joined = [tabtr, tabfields, tabbutton].join(' ');

                            $('#freetable_table$rand tr:last').before(joined);
                            $('#name_' + i).val(name);

                            elem_parent.find('input[name=name$rand]').val('');
                            $.each(fields,function(index, valuej){
                                if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                    $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                    elem_parent.find('input[name='+ index + ']').val('');
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                    $('#'+ index +'_' + i).val(elem_parent.find('select[name='+ index +']').val());
                                    elem_parent.find('select[name='+ index + ']').val('');
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                    $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                    elem_parent.find('input[name='+ index + ']').val(0);
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                    $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                    elem_parent.find('input[name='+ index + ']').val(0);
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                    $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                    elem_parent.find('input[name='+ index + ']').val(0);
                                }
                            });

                        } else if (type == 'add') {

                            tabtr = '<tr name=\"data\" $style id=\"line_' + $rand + '_' + i + '\" disabled>';

                            $.each(fields,function(index, valuej){

                               if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                   tabfields.push('<td $style><input id = \"' + index +'_' + i +'\" type=\"text\" name=\"' + index +'\" size=\"$size\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                   var select_open = '<td $style><select id = \"' + index +'_' + i +'\" name=\"' + index +'\">';
                                   var select_options = '';
                                   $.each(dropdown_values_fields,function(indexv, values){
                                       $.each(values,function(indexd, valued){
                                            if (index == indexv) {
                                                select_options += '<option value=\"' + valued +'\">' + valued +'</option>';
                                            }
                                      });
                                   });
                                   var select_close = '</select></td>';
                                   var select = [select_open, select_options, select_close].join(' ');
                                   tabfields.push(select);
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                    tabfields.push('<td $style><input add=3 id=\"' + index +'_' + i +'\" type=\"number\" min=\"0\" name=\"' + index +'\" style=\"width: 7em;\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_READONLY . " && orderfollowup_is_active) {
                                   //orderfollowup
                                   tabfields.push('<td $style id=\"linetotal\">' + total.toFixed(2) +' €</td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                    tabfields.push('<td $style><input add=3 id=\"' + index +'_' + i +'\" type=\"date\" name=\"' + index +'\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                    tabfields.push('<td $style><input add=3 id=\"' + index +'_' + i +'\" type=\"time\" name=\"' + index +'\" disabled ></td>');
                               }

                            });
                            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine$rand(' + i +')\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                               + '<i class =\"ti ti-pencil\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"ti ti-trash\"></i></button></td></tr>'

                            var joined = [tabtr, tabfields, tabbutton].join(' ');

                            $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after(joined);
                               $('#name_' + i).val(name);
                               elem_parent.find('input[name=name$rand]').val('');

                               $.each(fields,function(index, valuej){
                                    if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                        $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                        elem_parent.find('input[name='+ index + ']').val('');
                                    } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                        $('#'+ index +'_' + i).val(elem_parent.find('select[name='+ index +']').val());
                                        elem_parent.find('select[name='+ index + ']').val('');
                                    } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                        $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                        elem_parent.find('input[name='+ index + ']').val(0);
                                    } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                        $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                        elem_parent.find('input[name='+ index + ']').val(0);
                                    } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                        $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                        elem_parent.find('input[name='+ index + ']').val(0);
                                    }
                                });

                        } else {

                            tabtr = '<tr name=\"data\" $style id=\"line_' + $rand + '_' + ind + '\">';

                            $.each(fields,function(index, valuej){

                               if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                   tabfields.push('<td $style><input id = \"' + index +'_' + ind +'\" type=\"text\" name=\"' + index +'\" size=\"$size\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                   var select_open = '<td $style><select id = \"' + index +'_' + ind +'\" name=\"' + index +'\">';
                                   var select_options = '';
                                   $.each(dropdown_values_fields,function(indexv, values){
                                       $.each(values,function(indexd, valued){
                                            if (index == indexv) {
                                                select_options += '<option value=\"' + valued +'\">' + valued +'</option>';
                                            }
                                      });
                                   });
                                   var select_close = '</select></td>';
                                   var select = [select_open, select_options, select_close].join(' ');
                                   tabfields.push(select);
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                    tabfields.push('<td $style><input add=4 id=\"' + index +'_' + ind +'\" type=\"number\" min=\"0\" name=\"' + index +'\" style=\"width: 7em;\" disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_READONLY . " && orderfollowup_is_active) {
                                   //orderfollowup
                                   tabfields.push('<td $style id=\"linetotal\">' + total.toFixed(2) +' €</td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                    tabfields.push('<td $style><input add=4 id=\"' + index +'_' + ind +'\" type=\"date\" name=\"' + index +'\"  disabled ></td>');
                               } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                    tabfields.push('<td $style><input add=4 id=\"' + index +'_' + ind +'\" type=\"time\" name=\"' + index +'\"  disabled ></td>');
                               }
                            });
                            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine$rand(' + ind +')\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                               + '<i class =\"ti ti-pencil\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + ind +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"ti ti-trash\"></i></button></td></tr>'

                           var joined = [tabtr, tabfields, tabbutton].join(' ');

                             $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after(joined);

                            $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #name_' + ind).val(name);
                            $.each(fields,function(index, valuej){
                                if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TEXT . ") {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('input[name='+ index +']').val());
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_SELECT . ") {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('select[name='+ index +']').val());
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_NUMBER . ") {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('input[name='+ index +']').val());
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_DATE . ") {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('input[name='+ index +']').val());
                                } else if (type_fields[index] == " . PluginMetademandsFreetablefield::TYPE_TIME . ") {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('input[name='+ index +']').val());
                                }
                            });

                       }
                       node.parentNode.parentNode.remove();
                       if(type == 'add'){
                           nb++;
                           localStorage.setItem('nextnb', nb);
                           i++;
                       }
                       //orderfollowup
                       if (orderfollowup_is_active) {
                           showConfirmButton();
                       }

                    }
                }
                //orderfollowup
                function showConfirmButton(){
                    var tabdatas =  $('[id^=line_]');
                    $('#nextBtn').hide();

                    if(tabdatas.length == 0){
                         $('#add_freeinputs').css('display', 'none');
                         $('#div_save_draft').css('display', 'none');
                         if($('#button_save_mydraft')){
                            $('#button_save_mydraft').css('display', 'none');
                         }

                    } else {
                         $('#add_freeinputs').css('display', 'inline-block');
                         $('#div_save_draft').css('display', 'inline-block');
                         if($('#button_save_mydraft')){
                            $('#button_save_mydraft').css('display', 'inline-block');
                         }
                    }
                }

                function removeLine$rand (l) {
                    $('#line_' + $rand + '_'+ l).remove();
                    var line = {
                        'remove': l,
                        'metademands_id' : $plugin_metademands_metademands_id,
                        'fields_id' : $plugin_metademands_fields_id
                    };

                    $.ajax({
                               url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/freetable_item.php',
                               type: 'POST',
                               data: line
                            });
                    var tabdatas = $('[id^=line_' + $rand + '_]');
                    if (tabdatas.length == 0) {
                        $('#add_freetables$rand').css('display', 'none');
                        $('#div_save_draft').css('display', 'none');
                        if ($('#button_save_mydraft')) {
                            $('#button_save_mydraft').css('display', 'none');
                         }
                    }
                    if (document.querySelector('tr[id=\"tr_valid$rand\"]')) {
                        document.querySelector('tr[id=\"tr_valid$rand\"]').remove();
                    }
                }

                function editLine$rand (l) {

                    let line = document.querySelector('#line_'+ $rand + '_' + l);

                    let inputs = line.querySelectorAll('input');
                    let selects = line.querySelectorAll('select');
                    let areas = line.querySelectorAll('textarea');

                    for(var i = 0; i < inputs.length; i++) {
                        inputs[i].disabled = false;
                    }

                    for(var i = 0; i < selects.length; i++) {
                        inputs[i].disabled = false;
                    }

                    for(var i = 0; i < areas.length; i++) {
                        inputs[i].disabled = false;
                    }

                    line.querySelector('button[name=\"delete_item\"]').parentNode.remove();
                    line.querySelector('button[name=\"edit_item\"]').parentNode.remove();

                    let td = document.createElement('td');
                    td.setAttribute('class', 'tbl-center');
                    let button = document.createElement('button');
                    button.className = 'btn btn-success';
                    button.type = 'button';

                    let ico = document.createElement('i');
                    ico.className = 'ti ti-check';
                    button.appendChild(ico);
                    button.dataset.id = l;
                    button.addEventListener('click',function() {
                      confirmLine$rand(this, this.dataset.id);
                    });
                    td.appendChild(button);
                    line.appendChild(td);

                    let td1 = document.createElement('td');
                    td1.setAttribute('class', 'tbl-center');
                    let button1 = document.createElement('button');
                    button1.className = 'btn btn-danger';
                    button1.type = 'button';

                    let ico1 = document.createElement('i');
                    ico1.className = 'ti ti-trash';
                    button1.appendChild(ico1);
                    button1.dataset.id = l;
                    button1.addEventListener('click',function() {
                      removeLine$rand(this, this.dataset.id);
                    });
                    td1.appendChild(button1);
                    line.appendChild(td1);
                }
                //orderfollowup
                if (orderfollowup_is_active) {
                    $(document).ready(function() {
                        showConfirmButton();
                    });
                }

                var data = {
                          lines$rand:[]
                        };
                var i = 0;
                </script>";

        $field .= "</table>";

        if (Plugin::isPluginActive('orderfollowup')) {
            $conf = new PluginOrderfollowupConfig();
            $conf->getFromDB(1);
            $tva = $conf->fields['use_tva'] ?? "20";
            $tva_calc = $tva / 100;
            $grandtotal = __('Grand total (TTC)', 'orderfollowup');
            $grandtotalHT = __('Grand total (HT)', 'orderfollowup') . " " . __('(if VAT 20%)', 'orderfollowup');
            $field .= "<script>
                    function saveInput() {
                        var grandtotal = 0;
                        var tva = $tva_calc;
                        i = 0;
                        $('[id^=line_]').each(function (){
                             i++;
                             grandtotal += $(this).find('[id^=unit_price_]').val() * $(this).find('[id^=quantity_]').val();
                             grandtotalht = grandtotal / (1 + tva);
                        });

                        $('[id^=line_]').css('background-color', '#f7f7f7');
                        let tr_grantotal = document.getElementById('grandtotal');
                        if (tr_grantotal == null) {
                            grandtotalht = grandtotal / (1 + tva);
                             $('#freetable_table$rand tr[id^=line_]:last').after('<tr $style id=\"grandtotal\">' +
                         '<th colspan=\"6\" style= \'background-color: #ffffff;\' > $grandtotal </th><th $stylereadonly id=\"amount_grandtotal\" >' + grandtotal.toFixed(2) + ' €</th></tr>' +
                          '<tr $style id=\"grandtotalht\">' +
                         '<th colspan=\"6\" style= \'background-color: #ffffff;\' > $grandtotalHT </th><th $stylereadonly id=\"amount_grandtotalht\" >' + grandtotalht.toFixed(2) + ' €</th></tr>');
                        } else {

                           $('#amount_grandtotal').text(grandtotal.toFixed(2) +' €');
                           $('#amount_grandtotalht').text(grandtotalht.toFixed(2)+' €');
                        }
                        $('#nextBtn').show();
                    }
               </script>";

            $field .= "<table class='tab_cadre' width='100%' style='overflow: auto;width:100%;$background_color'>";
            $field .= "<tr class='tab_bg_1'>";
            $field .= "<td colspan='8' style ='text-align:center;'>";
            $field .= "<button onclick='saveInput()' type = 'button' id='add_freeinputs' class='btn btn-primary' style='display: none;'>";
            $field .= "<span>" . __('Validate the basket', 'orderfollowup') . "</span>";
            $field .= "</button>";
            $field .= "</td>";
            $field .= "</tr>";
            $field .= "</table>";
        }


        echo $field;
    }

    public static function showFreetableFields($params)
    {
        $custom_values = $params['custom_values'];

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $maxrank = 0;

        $nbfields = 0;
        $field_custom = new PluginMetademandsFreetablefield();
        if ($customs = $field_custom->find(
            ["plugin_metademands_fields_id" => $params['plugin_metademands_fields_id']],
            "rank"
        )) {
            if (count($customs) > 0) {
                $nbfields = count($customs);
            }
        }

        if (is_array($custom_values) && !empty($custom_values)) {
            echo "<div id='drag'>";
            $target = PluginMetademandsFreetablefield::getFormURL();
            echo "<form method='post' action=\"$target\">";
            echo "<table class='tab_cadre_fixe'>";
            foreach ($custom_values as $key => $value) {
                echo "<tr class='tab_bg_1'>";

                echo "<td class='rowhandler control center'>";
                echo __('Rank', 'metademands') . " " . $value['rank'] . " ";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden(
                        'fields_id',
                        ['value' => $params["plugin_metademands_fields_id"], 'id' => 'fields_id']
                    );
                    echo Html::hidden('type_object', ['value' => $params["type"], 'id' => 'type_object']);
                }
                echo "</td>";

                echo "<td class='rowhandler control left'>";
                echo "<span id='internal_name_values$key'>";
                echo " " . __('Internal name', 'metademands') . " ";
                echo Html::input('internal_name[' . $key . ']', ['value' => $value['internal_name'], 'size' => 20]);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control left'>";
                echo "<span id='type_values$key'>";
                echo " " . __('Type', 'metademands') . "<br>";
                Dropdown::showFromArray(
                    'type[' . $key . ']',
                    PluginMetademandsFreetablefield::getTypeFields(),
                    ['value' => $value['type'], 'size' => 20]
                );
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control left'>";
                echo "<span id='custom_values$key'>";
                echo " " . __('Display name', 'metademands') . " ";
                echo Html::input('name[' . $key . ']', ['value' => $value['name'], 'size' => 20]);
                echo "</span>";
                echo "</td>";

                if ($value['type'] == PluginMetademandsFreetablefield::TYPE_TEXT) {
                    echo "<td class='rowhandler control left'>";
                    echo "<span id='comment_values$key'>";
                    echo __('Comment') . " ";
                    echo Html::input('comment[' . $key . ']', ['value' => $value['comment'], 'size' => 20]);
                    echo "</span>";
                    echo Html::hidden('dropdown_values[' . $key . ']', ['value' => []]);
                    echo "</td>";
                } elseif ($value['type'] == PluginMetademandsFreetablefield::TYPE_SELECT) {
                    echo "<td class='rowhandler control left'>";
                    echo "<span id='dropdown_values$key'>";
                    echo " " . __('Dropdown values', 'metademands') . " ";
                    $label = __('One value by line, separated by comma', 'metademands');
                    Html::showToolTip(
                        Glpi\RichText\RichText::getSafeHtml($label),
                        ['awesome-class' => 'ti ti-info-circle']
                    );
                    Html::textarea([
                        'name' => 'dropdown_values[' . $key . ']',
                        'value' => $value['dropdown_values'],
                        'rows' => 3,
                        'cols' => 5,
                    ]);
                    echo "</span>";
                    echo Html::hidden('comment[' . $key . ']', ['value' => ""]);
                    echo "</td>";
                } elseif ($value['type'] == PluginMetademandsFreetablefield::TYPE_NUMBER
                    || $value['type'] == PluginMetademandsFreetablefield::TYPE_DATE
                    || $value['type'] == PluginMetademandsFreetablefield::TYPE_TIME) {
                    echo "<td class='rowhandler control left'>";
                    echo Html::hidden('comment[' . $key . ']', ['value' => ""]);
                    echo Html::hidden('dropdown_values[' . $key . ']', ['value' => []]);
                    echo "</td>";
                }

                echo "<td class='rowhandler control left'>";
                echo "<span id='is_mandatory_values$key'>";
                echo __('Mandatory', 'metademands') . "<br>";
                Dropdown::showYesNo('is_mandatory[' . $key . ']', $value['is_mandatory']);
                echo "</span>";
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
                        'freetablefield_id' => $key,
                        'rank' => $value['rank'],
                        'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                    ],
                    'ti-circle-x',
                    "class='btn btn-primary'"
                );
                echo "</td>";

                echo "</tr>";

                $maxrank = $value['rank'];
            }
            echo Html::hidden('plugin_metademands_fields_id', ['value' => $params['plugin_metademands_fields_id']]);
            echo "</table>";
            Html::closeForm();
            echo "</div>";
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_freetableredipsInit()});');

            if ($nbfields < 6) {
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='4' align='left' id='show_custom_fields'>";
                PluginMetademandsFreetablefield::initCustomValue(
                    $maxrank,
                    $params["plugin_metademands_fields_id"]
                );
                echo "</td>";
                echo "</tr>";
            }
        } else {
            if ($nbfields < 6) {
                echo "<tr class='tab_bg_1'>";
                echo "<td align='right'  id='show_custom_fields'>";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
                }
                PluginMetademandsFreetablefield::initCustomValue(-1, $params["plugin_metademands_fields_id"]);
                echo "</td>";
                echo "</tr>";
            }
        }
        echo "</td>";
        echo "</tr>";
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
        //            && $fields['value'] == null) {
        //            $msg = $value['name'];
        //            $checkKo = 1;
        //        }
        //
        //        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        return $field['value'];
    }

    public static function displayFieldPDF($elt, $fields, $label)
    {
        $values = [];

        $values_elt = $fields[$elt['id']] ?? [];

        if (is_array($values_elt) && count($values_elt) > 0) {
            foreach ($values_elt as $k => $value_elt) {
                foreach ($value_elt as $internal_name => $value) {
                    $field_custom = new PluginMetademandsFreetablefield();
                    if ($customs = $field_custom->find([
                        "internal_name" => $internal_name,
                        "plugin_metademands_fields_id" => $elt['id'],
                    ])) {
                        if (count($customs) > 0) {
                            foreach ($customs as $id => $custom) {
                                $values[$elt['id']][$k][Toolbox::decodeFromUtf8(
                                    $custom['name']
                                )] = $value;
                            }
                        }
                        //TODO
                        //                        if (Plugin::isPluginActive('orderfollowup')) {
                        //                            $total = $item['unitprice'] * $item['quantity'];
                        //                            $values[$id][Toolbox::decodeFromUtf8(
                        //                                __('Total (TTC)', 'orderfollowup')
                        //                            )] = Html::formatNumber($total, false, 2);
                        //                        }
                    }
                }
            }
        }

        return $values;
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
        //        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'])) {
        //            $quantities = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'];
        //        }
        $style_td = "style = \'border: 1px solid #CCC; \'";

        $materials = $field["value"];

        //        if (is_object($materials)) {
        //            $materials = json_decode(json_encode($materials), true);
        //        }
        $content = "";
        $result[$field['rank']]['display'] = true;
        //        $total = 0;
        $addfields = [];
        $dropdown_values = [];
        $colspan_title = $is_order ? 12 : 2;
        $field_custom = new PluginMetademandsFreetablefield();
        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $field['id']], "rank")) {
            if (count($customs) > 0) {
                foreach ($customs as $custom) {
                    $addfields[$custom['internal_name']] = $custom['name'];
                }
                if ($custom['type'] == PluginMetademandsFreetablefield::TYPE_SELECT) {
                    $dropdown_values[$custom['internal_name']] = explode(",", $custom['dropdown_values']);
                }
            }
        }
        $nb = count($customs);

        if ($nb == 1) {
            $colspan = 12;
        }
        if ($nb == 2) {
            $colspan = 6;
        }
        if ($nb == 3) {
            $colspan = 4;
        }
        if ($nb == 4) {
            $colspan = 3;
        }
        if ($nb == 5) {
            $colspan = 2;
        }
        if ($nb == 6) {
            $colspan = 2;
        }
        if (Plugin::isPluginActive('orderfollowup')) {
            $total = 0;
        }
        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['freetables'][$field['id']])) {
            $freetables = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['freetables'][$field['id']];

            if (is_array($freetables) && count($freetables) > 0) {
                if ($formatAsTable) {
                    $content .= "<tr>";
                    $content .= "<td $style_title colspan='$colspan_title'>";
                }
                $content .= $label;
                if ($formatAsTable) {
                    $content .= "</td>";
                    $content .= "</tr>";
                }
                if ($formatAsTable) {
                    $content .= "<tr>";
                    foreach ($addfields as $k => $addfield) {
                        $content .= "<th $style_td colspan='$colspan'>" . $addfield . "</th>";
                    }
                    if (Plugin::isPluginActive('orderfollowup')) {
                        $content .= "<th $style_td>" . __('Total (TTC)', 'orderfollowup') . "</th>";
                    }
                    $content .= "</tr>";
                }

                foreach ($freetables as $fi) {
                    if ($formatAsTable) {
                        $content .= "<tr>";
                    }

                    foreach ($addfields as $k => $addfield) {
                        if ($formatAsTable) {
                            $content .= "<td $style_td colspan='$colspan'>";
                        }

                        if ($fi['type'] == PluginMetademandsFreetablefield::TYPE_SELECT) {
                            if (isset($dropdown_values[$fi[$k]])) {
                                $content .= $dropdown_values[$fi[$k]];
                            }
                        }
                        if ($fi['type'] == PluginMetademandsFreetablefield::TYPE_DATE) {
                            $content .= Html::convDate($fi[$k]);
                        } elseif ($fi['type'] == PluginMetademandsFreetablefield::TYPE_TIME) {
                            $content .= $fi[$k];
                        } else {
                            $content .= $fi[$k];
                        }

                        if ($formatAsTable) {
                            $content .= "</td>";
                        }
                    }
                    if (Plugin::isPluginActive('orderfollowup')) {
                        if ($formatAsTable) {
                            $content .= "<td $style_td>";
                        }
                        $totalrow = floatval($fi['quantity']) * floatval($fi['unit_price']);
                        $content .= Html::formatNumber($totalrow, false, 2) . " €";
                        if ($formatAsTable) {
                            $content .= "</td>";
                        }
                        $total += $totalrow;
                    }
                    if ($formatAsTable) {
                        $content .= "</tr>";
                    }
                }
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $grandtotal = __('Grand total (TTC)', 'orderfollowup');
            $grandtotalHT = __('Grand total (HT)', 'orderfollowup') . " " . __('(if VAT 20%)', 'orderfollowup');
            $content .= "<tr>";
            $content .= "<th $style_td colspan='10'>" . $grandtotal . "</th>";
            $content .= "<td $style_td>" . Html::formatNumber($total, false, 2) . " €</td></tr>";
            $content .= "<tr>";
            $content .= "<th $style_td colspan='10'>" . $grandtotalHT . "</th>";
            $conf = new PluginOrderfollowupConfig();
            $conf->getFromDB(1);
            $tva = $conf->fields['use_tva'] ?? "20";
            $totalHT = $total / (1 + ($tva / 100));
            $content .= "<td $style_td>" . Html::formatNumber($totalHT, false, 2) . " €</td></tr>";
        }

        $result[$field['rank']]['content'] .= $content;

        return $content;
    }
}
