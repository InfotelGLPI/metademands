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

    static $rightname = 'plugin_metademands';
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
        return __('Free table', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        $field = "";

        $plugin_metademands_metademands_id = $data['plugin_metademands_metademands_id'];
        $meta = new PluginMetademandsMetademand();
        $meta->getFromDB($plugin_metademands_metademands_id);
        $background_color = "";
        if (isset($meta->fields['background_color'])
        && $meta->fields['background_color'] != "") {
            $background_color = "background-color:".$meta->fields['background_color'].";";
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
        $field .= Html::hidden('is_freetable_mandatory['.$data['id'].']', ['value' => $data['is_mandatory']]);
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
                    ['display' => false, 'awesome-class' => 'fa-info-circle']
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
        $field .= "<th style='text-align: center;$background_color' colspan='2' onclick='addLine$rand()'><i class='fa-solid fa-plus btn btn-info'></i></th>";
        $field .= "</tr>";

        $style = "";

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
                        if ($types[$k] == 1) {
                            $field .= "<td $style><input id=\"$k.'_'.$idline\" name=\"$k\" type = \"text\" value=\"$l[$k]\" size=\"$size\" disabled></td>";
                        } else {
                            $field .= "<td $style><select id=\"$k.'_'.$idline\" name=\"$k\">";

                            foreach ($dropdown_values[$k] as $dropdown_value) {
                                $selected = "";
                                if ($dropdown_value == $l[$k]) {
                                    $selected = "selected";
                                }
                                $field .= "<option $selected value=\"$dropdown_value\">" . $dropdown_value . "</option>";
                            }
                            $field .= "</select></td>";
                        }
                    }
                }

                $field .= "<td $style></td>";
                $field .= "<td><button onclick =\"editLine$rand($idline)\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\"><i class =\"fas fa-pen\"></i></button></td>";
                $field .= "<td><button onclick =\"removeLine$rand($idline)\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\"><i class =\"fas fa-trash\"></i></button></td>";
                $field .= "</tr>";
                $idline++;

                $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables'][$idline] = $l;
            }
        }

        $existLine = __('You can\'t create a new line when there is an existing one', 'metademands');
        $style_td = "";

        $field .= "<script>
                function getNextIndex() {
                    const nb = localStorage.getItem('nextnb');
                    return nb ? parseInt(nb) : 0;
                }
                function addLine$rand() {
                    
                    var fields = $encoded_fields;
                    var mandatory_fields = $mandatory_encoded_fields;
                    var type_fields = $types_encoded_fields;
                    var dropdown_values_fields = $dropdown_values_encoded_fields;
                    const tabfields = [];

                    if (!document.querySelector('#freetable_table$rand .add_item')) {

                        if ($('#freetable_table$rand tr[id^=line_' + $rand + '_]:first').length > 0) {
                            
                            tabtr = '<tr class=\"tab_bg_1\" id=\"line_' + $rand + '_' + i + '\">';
                            
                           $.each(fields,function(index, valuej){
                               if (type_fields[index] == 1) {
                                   tabfields.push('<td><input id = \"' + index +'\" type = \"text\" name=\"' + index +'\" size=\"$size\" ></td>');
                               } else {
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
                               }
                               
                           });
                           
                           var str = '<button class =\"btn btn-success add_item\" type = \"button\" name =\"add_item\" onclick=\"confirmLine$rand(this)\">';
                           tabbutton = '<td style=\"text-align: center;\" colspan=\"2\">'
                           + str
                           + '<i class =\"fas fa-check\"></i></button></td>'
//                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
//                               + '<i class =\"fas fa-trash\"></i></button></td>'
                               + '</tr>';
                           
                           var joined = [tabtr, tabfields, tabbutton].join(' ');

                            $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after(joined);
                            
                        } else {
                            
                            tabtr = '<tr class=\"tab_bg_1\" id=\"line_' + $rand + '_' + i + '\">';
                            
                            $.each(fields,function(index, valuej){
                               if (type_fields[index] == 1) {
                                   tabfields.push('<td><input id = \"' + index +'\" type = \"text\" name=\"' + index +'\" size=\"$size\" ></td>');
                               } else {
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
                               }
                           });
                             var str = '<button class =\"btn btn-success add_item\" type = \"button\" name =\"add_item\" onclick=\"confirmLine$rand(this)\">';
                           tabbutton = '<td style=\"text-align: center;\" colspan=\"2\">'
                           + str
                           + '<i class =\"fas fa-check\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"fas fa-trash\"></i></button></td>'
                               + '</tr>';
                             var joined = [tabtr, tabfields, tabbutton].join(' ');
                           
                            $('#freetable_table$rand tr[class^=tab_bg_1]:last').after(joined);
                        }
                        
                    } else {
                        alert(\"$existLine\");
                    }
                    
                    
                }
                
                function confirmLine$rand (node) {
                    
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
                        if (type_fields[index] == 1) {
                            l[index] = elem_parent.find('input[name='+ index +']').val();
                        } else {
                            l[index] = elem_parent.find('select[name='+ index +']').val();
                        }
                    });

                    let type = 'add';

                    $.each(data.lines, function (key, datas) {
                        $.each(datas, function (key_data, data_lines) {
                            if(key_data == 'id'){
                                if(data_lines == ind){
                                   data['lines$rand'][key] = l;
                                   type = 'modif';
                                }
                            }
                        });
                    });
                    
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
                        
                            if (type_fields[index] == 1) {
                                if (elem_parent.find('input[name='+ index +']').val() === '') {
                                    elem_parent.find('input[name=' + index +']').css('border-color', 'red');
                                    ko = 1;
                                } else {
                                    elem_parent.find('input[name=' + index +']').css('border-color', '');
                                }
                            } else {
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
                            }
                        }
                        
                    });
                    if (ko == 0) {
                        if ($('[id^=line_' + $rand + '_]').length == 0) {
                            
                             tabtr = '<tr name=\"data\" $style id=\"line_' + $rand + '_' + i + '\" disabled>';
                            
                            $.each(fields,function(index, valuej){
                               
                               if (type_fields[index] == 1) {
                                   tabfields.push('<td $style><input id = \"' + index +'_' + i +'\" type = \"text\" name=\"' + index +'\" size=\"$size\" disabled ></td>');
                               } else {
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
                               }
                               
                            });
                            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine$rand(' + i +')\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                               + '<i class =\"fas fa-pen\"></i></button></td>'
                               + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"fas fa-trash\"></i></button></td></tr>'
                           
                            var joined = [tabtr, tabfields, tabbutton].join(' ');
                           
                            $('#freetable_table$rand tr:last').before(joined);
                            $('#name_' + i).val(name);
                            
                            elem_parent.find('input[name=name$rand]').val('');
                            $.each(fields,function(index, valuej){
                                if (type_fields[index] == 1) {
                                    $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                    elem_parent.find('input[name='+ index + ']').val('');
                                } else {
                                    $('#'+ index +'_' + i).val(elem_parent.find('select[name='+ index +']').val());
                                    elem_parent.find('select[name='+ index + ']').val('');
                                }
                            });

                        } else if (type == 'add') {
                            
                            tabtr = '<tr name=\"data\" $style id=\"line_' + $rand + '_' + i + '\" disabled>';
                            
                            $.each(fields,function(index, valuej){
                               
                               if (type_fields[index] == 1) {
                                   tabfields.push('<td $style><input id = \"' + index +'_' + i +'\" type = \"text\" name=\"' + index +'\" size=\"$size\" disabled ></td>');
                               } else {
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
                               }
                               
                            });
                            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine$rand(' + i +')\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                               + '<i class =\"fas fa-pen\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + i +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"fas fa-trash\"></i></button></td></tr>'
                           
                            var joined = [tabtr, tabfields, tabbutton].join(' ');
                            
                            $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after(joined);
                               $('#name_' + i).val(name);
                               elem_parent.find('input[name=name$rand]').val('');
                               
                               $.each(fields,function(index, valuej){
                                    if (type_fields[index] == 1) {
                                        $('#'+ index +'_' + i).val(elem_parent.find('input[name='+ index +']').val());
                                        elem_parent.find('input[name='+ index + ']').val('');
                                    } else {
                                        $('#'+ index +'_' + i).val(elem_parent.find('select[name='+ index +']').val());
                                        elem_parent.find('select[name='+ index + ']').val('');
                                    
                                    }
                                });

                        } else {
                            
                            tabtr = '<tr name=\"data\" $style id=\"line_' + $rand + '_' + ind + '\">';
                            
                            $.each(fields,function(index, valuej){

                               if (type_fields[index] == 1) {
                                   tabfields.push('<td $style><input id = \"' + index +'_' + ind +'\" type = \"text\" name=\"' + index +'\" size=\"$size\" disabled ></td>');
                               } else {
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
                               }
                            });
                            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine$rand(' + ind +')\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                               + '<i class =\"fas fa-pen\"></i></button></td>'
                           + '<td style=\"text-align: center;\"><button onclick =\"removeLine$rand(' + ind +')\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                               + '<i class =\"fas fa-trash\"></i></button></td></tr>'
                           
                           var joined = [tabtr, tabfields, tabbutton].join(' ');
                            
                             $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after(joined);
                           
                            $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #name_' + ind).val(name);
                            $.each(fields,function(index, valuej){
                                if (type_fields[index] == 1) {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('input[name='+ index +']').val());
                                } else {
                                    $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last #' + index + '_' + ind).val(elem_parent.find('select[name='+ index +']').val());
                                }
                            });
                          
                       }
                       node.parentNode.parentNode.remove();
                       if(type == 'add'){
                           nb++;
                           localStorage.setItem('nextnb', nb);
                           i++;
                       }
                    }
//                    if (ko == 0) {
//                        showConfirmButton$rand();
//                    }
                    
                   
                }
                
//                function showConfirmButton$rand() {
//                    
//                    var tabdatas =  $('[id^=line_' + $rand + '_]');
//                    
//                    if (tabdatas.length == 0) {
//                         $('#add_freetables$rand').css('display', 'none');
//                         $('#div_save_draft').css('display', 'none');
//                         if ($('#button_save_mydraft')) {
//                            $('#button_save_mydraft').css('display', 'none');
//                         }
//                         
//                    } else {
//                         $('#add_freetables$rand').css('display', 'inline-block');
//                         $('#div_save_draft').css('display', 'inline-block');
//                         if ($('#button_save_mydraft')) {
//                            $('#button_save_mydraft').css('display', 'inline-block');
//                         }
//                    }
//                }

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
                    
//                    line.querySelector('button[name=\"delete_item\"]').parentNode.remove();
                    line.querySelector('button[name=\"edit_item\"]').parentNode.remove();
                    
                    let td = document.createElement('td');
                    td.setAttribute('class', 'tbl-center');
                    let button = document.createElement('button');
                    button.className = 'btn btn-success';
                    button.type = 'button';
                    
                    let ico = document.createElement('i');
                    ico.className = 'fas fa-check';
                    button.appendChild(ico);
                    button.dataset.id = l;
                    button.addEventListener('click',function() {
                      confirmLine$rand(this, this.dataset.id);
                    });
                    td.appendChild(button);
                    line.appendChild(td);
                }
//                $(document).ready(function() {
//                    showConfirmButton$rand();
//                });
                var data = {
                          lines$rand:[]
                        };
                var i = 0;
                </script>";
//validate list why ? for other plugin - let's in place
//        $msg = __('List validated', 'metademands');
//        $colspan = $colspan + $colspanfields;
//        $field .= "<script>
//                    function saveInput$rand() {
//                        i = 0;
//                        $('[id^=line_' + $rand + '_]').each(function (){
//                             i++;
//                        });
//                        $('[id^=line_' + $rand + '_]').css('background-color', '#eeeeee');
//                        if (!document.querySelector('#tr_valid$rand')) {
//                           $('#freetable_table$rand tr[id^=line_' + $rand + '_]:last').after('<tr id=\"tr_valid$rand\" $style><th style=\"text-align: center;\" colspan=\"$colspan\">$msg</th></tr>');
//                        }
////                        $('#nextBtn').show();
//                    }
//
//               </script>";


//        $field .= "<tr>";
//        $field .= "<td colspan='$colspan' style ='text-align:center;'>";
//        $field .= "<button onclick='saveInput$rand()' type = 'button' id='add_freetables$rand' class='btn btn-success' style='display: none;'>";
//        $field .= "<span>" . __('Validate the list', 'metademands') . "</span>";
//        $field .= "</button>";
//        $field .= "</td>";
//        $field .= "</tr>";

        $field .= "</table>";

        echo $field;
    }

    static function showFreetableFields($params)
    {
        $custom_values = $params['custom_values'];

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $maxrank = 0;

        $nbfields = 0;
        $field_custom = new PluginMetademandsFreetablefield();
        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $params['plugin_metademands_fields_id']],
            "rank")) {
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
                    echo Html::hidden('type', ['value' => $params["type"], 'id' => 'type']);
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
                Dropdown::showFromArray('type[' . $key . ']', PluginMetademandsFreetablefield::getTypeFields(), ['value' => $value['type'], 'size' => 20]);
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
                } else {
                    echo "<td class='rowhandler control left'>";
                    echo "<span id='dropdown_values$key'>";
                    echo " " . __('Dropdown values', 'metademands') . " ";
                    $label =  __('One value by line, separated by comma', 'metademands');
                    Html::showToolTip(
                        Glpi\RichText\RichText::getSafeHtml($label),
                        ['awesome-class' => 'fa-info-circle']
                    );
                    Html::textarea([
                        'name' => 'dropdown_values[' . $key . ']',
                        'value' => $value['dropdown_values'],
                        'rows' => 3,
                        'cols' => 5
                    ]);
                    echo "</span>";
                    echo Html::hidden('comment[' . $key . ']', ['value' => ""]);
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
                echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                echo "</div>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo Html::hidden('id[' . $key . ']', ['value' => $key]);
                echo Html::submit("", [
                    'name' => 'update',
                    'class' => 'btn btn-primary',
                    'icon' => 'fas fa-save'
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
                    'fa-times-circle',
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
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');

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

    static function fieldsMandatoryScript($data) {
    }

    static function fieldsHiddenScript($data)
    {
    }

    public static function blocksHiddenScript($data)
    {
    }

    public static function getFieldValue($field)
    {
        return $field['value'];
    }

    static function displayFieldPDF($elt, $fields, $label)
    {
        $values = [];

        $values_elt = isset($fields[$elt['id']]) ? $fields[$elt['id']] : [];

        if (is_array($values_elt) && count($values_elt) > 0) {
            foreach ($values_elt as $k => $value_elt) {
                foreach ($value_elt as $internal_name => $value) {
                    $field_custom = new PluginMetademandsFreetablefield();
                    if ($customs = $field_custom->find(["internal_name" => $internal_name,
                        "plugin_metademands_fields_id" => $elt['id']])) {
                        if (count($customs) > 0) {
                            foreach ($customs as $id => $custom) {
                                $values[$elt['id']][$k][Toolbox::decodeFromUtf8(
                                    $custom['name']
                                )] = $value;
                            }
                        }
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

                        if (isset($dropdown_values[$fi[$k]])) {
                            $content .= $dropdown_values[$fi[$k]];
                        } else {
                            $content .= $fi[$k];
                        }

                        if ($formatAsTable) {
                            $content .= "</td>";
                        }
                    }
                    if ($formatAsTable) {
                        $content .= "</tr>";
                    }
                }
            }
            $result[$field['rank']]['content'] .= $content;
        }


        return $content;
    }
}
