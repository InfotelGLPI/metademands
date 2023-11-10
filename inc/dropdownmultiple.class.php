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
 * PluginMetademandsDropdownmultiple Class
 *
 **/
class PluginMetademandsDropdownmultiple extends CommonDBTM
{

    public static $dropdown_multiple_items = ['other', 'Appliance', 'User'];

    const CLASSIC_DISPLAY = 0;
    const DOUBLE_COLUMN_DISPLAY = 1;

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
        return __('Dropdown multiple', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_basket)
    {
        global $DB;

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if ($data['item'] == User::getType()) {
            $self = new PluginMetademandsField();
//            $data['custom_values'] = [];
            $criteria = $self->getDistinctUserCriteria() + $self->getProfileJoinCriteria();
            $criteria['FROM'] = User::getTable();
            $criteria['WHERE'][User::getTable() . '.is_deleted'] = 0;
            $criteria['WHERE'][User::getTable() . '.is_active'] = 1;
            $criteria['ORDER'] = ['NAME ASC'];

            if (!empty($data['custom_values'])) {
                $options = PluginMetademandsField::_unserialize($data['custom_values']);

                if (isset($options['user_group']) && $options['user_group'] == 1) {
                    $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                    $group_user_data = Group_User::getUserGroups(Session::getLoginUserID(), $condition);

                    $requester_users = [];
                    foreach ($group_user_data as $groups) {
                        $requester_users = Group_User::getGroupUsers($groups['id']);
                    }

                    $users = [];
                    foreach ($requester_users as $k => $v) {
                        $users[] = $v['id'];
                    }

                    $criteria['WHERE'][User::getTable() . '.id'] = $users;
                }
            }

            $iterator = $DB->request($criteria);

            $list = [];
            foreach ($iterator as $datau) {
                $list[$datau['users_id']] = getUserName($datau['users_id'], 0, true);
            }

            if (!empty($value) && !is_array($value)) {
                $value = json_decode($value);
            }
            if (!is_array($value)) {
                $value = [];
            }

            if ($data["display_type"] != self::CLASSIC_DISPLAY) {
                $name = $namefield . "[" . $data['id'] . "][]";
                $css = Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/doubleform.css");
                $field = "$css
                           <div class=\"row\">";
                $field .= "<div class=\"zone\">
                                   <select name=\"from\" id=\"multiselect$namefield" . $data["id"] . "\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";

                if (is_array($list) && count($list) > 0) {
                    foreach ($list as $k => $val) {
                        if (!in_array($k, $value)) {
                            $field .= "<option value=\"$k\" >$val</option>";
                        }
                    }
                }

                $field .= "</select></div>";

                $field .= " <div class=\"centralCol\" style='width: 3%;'>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightAll\" class=\"btn buttonColTop buttonCol\"><i class=\"fas fa-angle-double-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightSelected\" class=\"btn  buttonCol\"><i class=\"fas fa-angle-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-left\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftAll\" class=\"btn buttonCol\"><i class=\"fas fa-angle-double-left\"></i></button>
                               </div>";

                $required = "";
                if ($data['is_mandatory'] == 1) {
                    $required = "required=required";
                }
                $field .= "<div class=\"zone\">
                                   <select class='form-select' $required name=\"$name\" id=\"multiselect$namefield" . $data["id"] . "_to\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";
                if (is_array($value) && count($value) > 0) {
                    foreach ($value as $k => $val) {
                        $field .= "<option selected value=\"$val\" >" . getUserName($val, 0, true) . "</option>";
                    }
                }
                $field .= "</select></div>";

                $field .= "</div>";

                $field .= '<script src="../lib/multiselect2/dist/js/multiselect.js" type="text/javascript"></script>
                           <script type="text/javascript">
                           jQuery(document).ready(function($) {
                                  $("#multiselect' . $namefield . $data["id"] . '").multiselect({
                                      search: {
                                          left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                          right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                      },
                                      keepRenderingSort: true,
                                      fireSearch: function(value) {
                                          return value.length > 2;
                                      },
                                      moveFromAtoB: function(Multiselect, $source, $destination, $options, event, silent, skipStack ) {
                                        let self = Multiselect;
                        
                                        $options.each(function(index, option) {
                                            let $option = $(option);
                        
                                            if (self.options.ignoreDisabled && $option.is(":disabled")) {
                                                return true;
                                            }
                        
                                            if ($option.is("optgroup") || $option.parent().is("optgroup")) {
                                                let $sourceGroup = $option.is("optgroup") ? $option : $option.parent();
                                                let optgroupSelector = "optgroup[" + self.options.matchOptgroupBy + "=\'" + $sourceGroup.prop(self.options.matchOptgroupBy) + "\']";
                                                let $destinationGroup = $destination.find(optgroupSelector);
                        
                                                if (!$destinationGroup.length) {
                                                    $destinationGroup = $sourceGroup.clone(true);
                                                    $destinationGroup.empty();
                        
                                                    $destination.move($destinationGroup);
                                                }
                        
                                                if ($option.is("optgroup")) {
                                                    let disabledSelector = "";
                        
                                                    if (self.options.ignoreDisabled) {
                                                        disabledSelector = ":not(:disabled)";
                                                    }
                        
                                                    $destinationGroup.move($option.find("option" + disabledSelector));
                                                } else {
                                                    $destinationGroup.move($option);
                                                }
                        
                                                $sourceGroup.removeIfEmpty();
                                            } else {
                                                $destination.move($option);
                                                //Color change when multiselect value is switch
                                                $destination[0].value = $options[index].value;
                                                let selected = $destination[0].selectedIndex;
                                                let destOption = $destination[0].options[selected];
                                                if(destOption.style.color!="red" && destOption.style.color!="green") {
                                                    if($destination[0].name=="from"){
                                                        destOption.style.color = "red";
                                                    } else{
                                                        destOption.style.color = "green";
                                                    }
                                                } else{
                                                    destOption.style.color="#555555";
                                                }
                                            }
                                        });                        
                                        return self;
                                          
                                      }
                                  });
                              });
                           </script>';
            } else {
                $field = Dropdown::showFromArray(
                    $namefield . "[" . $data['id'] . "]",
                    $list,
                    ['values' => $value,
                        'width' => '250px',
                        'multiple' => true,
                        'display' => false,
                        'required' => ($data['is_mandatory'] ? "required" : "")
                    ]
                );
            }
        } else {
            if (!empty($data['custom_values'])) {
                $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
                foreach ($data['custom_values'] as $k => $val) {
                    if ($data['item'] != "other") {
                        $data['custom_values'][$k] = $data["item"]::getFriendlyNameById($k);
                    } else {
                        if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                            $data['custom_values'][$k] = $ret;
                        }
                    }
                }

                $defaults = PluginMetademandsField::_unserialize($data['default_values']);
                $default_values = [];
                if ($defaults) {
                    foreach ($defaults as $k => $v) {
                        if ($v == 1) {
                            $default_values[] = $k;
                        }
                    }
                }
                //                  sort($data['custom_values']);
                if (!empty($value) && !is_array($value)) {
                    $value = json_decode($value);
                }
                $value = is_array($value) ? $value : $default_values;

                if ($data["display_type"] != self::CLASSIC_DISPLAY) {
                    $name = $namefield . "[" . $data['id'] . "][]";
                    $css = Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/doubleform.css");
                    $field = "$css
                           <div class=\"row\">";
                    $field .= "<div class=\"zone\">
                                   <select class='form-select' name=\"from\" id=\"multiselect$namefield" . $data["id"] . "\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";

                    foreach ($data['custom_values'] as $k => $val) {
                        if (!in_array($k, $value)) {
                            $field .= "<option value=\"$k\" >$val</option>";
                        }
                    }

                    $field .= "</select></div>";

                    $field .= " <div class=\"centralCol\" style='width: 3%;'>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightAll\" class=\"btn buttonColTop buttonCol\"><i class=\"fas fa-angle-double-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_rightSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-left\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $data["id"] . "_leftAll\" class=\"btn buttonCol\"><i class=\"fas fa-angle-double-left\"></i></button>
                               </div>";

                    $required = "";
                    if ($data['is_mandatory'] == 1) {
                        $required = "required=required";
                    }
                    $field .= "<div class=\"zone\">
                                   <select class='form-select' $required name=\"$name\" id=\"multiselect$namefield" . $data["id"] . "_to\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";
                    foreach ($data['custom_values'] as $k => $val) {
                        if (in_array($k, $value)) {
                            $field .= "<option selected value=\"$k\" >$val</option>";
                        }
                    }

                    $field .= "</select></div>";

                    $field .= "</div>";

                    $field .= '<script src="../lib/multiselect2/dist/js/multiselect.js" type="text/javascript"></script>
                           <script type="text/javascript">
                           jQuery(document).ready(function($) {
                                  $("#multiselect' . $namefield . $data["id"] . '").multiselect({
                                      search: {
                                          left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                          right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                      },
                                      keepRenderingSort: true,
                                      fireSearch: function(value) {
                                          return value.length > 2;
                                      },
                                      moveFromAtoB: function(Multiselect, $source, $destination, $options, event, silent, skipStack ) {
                                        let self = Multiselect;
                        
                                        $options.each(function(index, option) {
                                            let $option = $(option);
                        
                                            if (self.options.ignoreDisabled && $option.is(":disabled")) {
                                                return true;
                                            }
                        
                                            if ($option.is("optgroup") || $option.parent().is("optgroup")) {
                                                let $sourceGroup = $option.is("optgroup") ? $option : $option.parent();
                                                let optgroupSelector = "optgroup[" + self.options.matchOptgroupBy + "=\'" + $sourceGroup.prop(self.options.matchOptgroupBy) + "\']";
                                                let $destinationGroup = $destination.find(optgroupSelector);
                        
                                                if (!$destinationGroup.length) {
                                                    $destinationGroup = $sourceGroup.clone(true);
                                                    $destinationGroup.empty();
                        
                                                    $destination.move($destinationGroup);
                                                }
                        
                                                if ($option.is("optgroup")) {
                                                    let disabledSelector = "";
                        
                                                    if (self.options.ignoreDisabled) {
                                                        disabledSelector = ":not(:disabled)";
                                                    }
                        
                                                    $destinationGroup.move($option.find("option" + disabledSelector));
                                                } else {
                                                    $destinationGroup.move($option);
                                                }
                        
                                                $sourceGroup.removeIfEmpty();
                                            } else {
                                                $destination.move($option);
                                                //Color change when multiselect value is switch
                                                $destination[0].value = $options[index].value;
                                                let selected = $destination[0].selectedIndex;
                                                let destOption = $destination[0].options[selected];
                                                if(destOption.style.color!="red" && destOption.style.color!="green") {
                                                    if($destination[0].name=="from"){
                                                        destOption.style.color = "red";
                                                    } else{
                                                        destOption.style.color = "green";
                                                    }
                                                } else{
                                                    destOption.style.color="#555555";
                                                }
                                            }
                                        });                        
                                        return self;
                                          
                                      }
                                  });
                              });
                           </script>';
                    //
                    //                     $field .= "<script src=\"../lib/multiselect2/dist/js/multiselect.min.js\" type=\"text/javascript\"></script>
                    //                           <script type=\"text/javascript\">
                    //                           jQuery(document).ready(function($) {
                    //                                  $('#multiselect$namefield" . $data["id"] . "').multiselect({
                    //                                      search: {
                    //                                          left: '<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol form-control\" placeholder=\"" . __('Search') . "...\" />',
                    //                                          right: '<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol form-control\" placeholder=\"" . __('Search') . "...\" />',
                    //                                      },
                    //                                      keepRenderingSort: true,
                    //                                      fireSearch: function(value) {
                    //                                          return value.length > 2;
                    //                                      },
                    //                                  });
                    //                              });
                    //                           </script>";
                } else {
                    $field = Dropdown::showFromArray(
                        $namefield . "[" . $data['id'] . "]",
                        $data['custom_values'],
                        ['values' => $value,
                            'width' => '250px',
                            'multiple' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? "required" : "")
                        ]
                    );
                }
            }
        }

        echo $field;
    }

    static function showFieldCustomValues($values, $key, $params)
    {

        $default_values = PluginMetademandsField::_unserialize($params['default_values']);
        echo "<tr>";
        echo "<td>";

        if ($params["item"] != "User") {
            if ($params["item"] != "other"
                && !empty($params["item"])
            ) {
                $item = new $params['item'];

                $items = $item->find(["is_deleted" => 0], ["name ASC"]);
                foreach ($items as $key => $v) {

                    echo "<tr>";
                    echo "<td>";
                    echo "<p id='custom_values$key'>";
                    echo $v["name"] . " ";
                    echo '</p>';
                    echo "</td>";

                    echo "<td>";
                    echo "<p id='default_values$key'>";
                    echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                    $name = "default_values[" . $key . "]";
                    $value = (isset($default[$key]) ? $default[$key] : 0);
                    Dropdown::showYesNo($name, $value);
                    echo '</p>';
                    echo "</td>";

                    echo "<td>";
                    echo "<p id='present_values$key'>";
                    echo " " . __('Display value in the dropdown', 'metademands') . " ";
                    $checked = "";
                    if (isset($values[$key])
                        && $values[$key] != 0) {
                        $checked = "checked";
                    }
                    echo "<input type='checkbox' name='custom_values[" . $key . "]'  value='$key' $checked />";
                    echo '</p>';
                    echo "</td>";

                    echo "</tr>";
                }
            } else {
                if (is_array($values) && !empty($values)) {
                    echo "<div id='drag'>";
                    echo "<table class='tab_cadre_fixe'>";
                    foreach ($values as $key => $value) {
                        echo "<tr>";

                        echo '<td class="rowhandler control center">';
                        echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                        echo "<p id='custom_values$key'>";
                        echo __('Value');
                        $name = "custom_values[$key]";
                        echo Html::input($name, ['value' => $value, 'size' => 50]);
                        echo '</p>';
                        echo '</div>';
                        echo '</td>';

                        echo '<td class="rowhandler control center">';
                        echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                        //                     echo "<p id='default_values$key'>";
                        $display_default = false;
                        //                     if ($params['value'] == 'dropdown_multiple') {
                        $display_default = true;
                        //                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                        $checked = "";
                        //                        if (isset($default[$key])
                        //                            && $default[$key] == 1) {
                        //                           $checked = "checked";
                        //                        }
                        //                        echo "<input type='checkbox' name='default_values[" . $key . "]'  value='1' $checked />";
                        echo "<p id='default_values$key'>";
                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                        $name = "default_values[" . $key . "]";
                        $value = ($default_values[$key] ?? 0);
                        Dropdown::showYesNo($name, $value);
                        echo '</p>';
                        //                     }
                        //                     echo '</p>';
                        echo '</div>';
                        echo '</td>';

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
                    echo '</td>';

                    echo "</tr>";
                    echo "<tr>";
                    echo "<td colspan='4' align='right' id='show_custom_fields'>";
                    PluginMetademandsField::initCustomValue(max(array_keys($values)), false, $display_default);
                    echo "</td>";
                    echo "</tr>";
                } else {
                    //                  echo "<tr>";
                    //                  echo "<td>";
                    echo __('Value') . " 1 ";
                    echo Html::input('custom_values[1]', ['size' => 50]);
                    echo "</td>";
                    echo "<td>";
                    $display_default = false;
                    //                  if ($params['value'] == 'dropdown_multiple') {
                    $display_default = true;
                    //                     echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                    //                     echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
                    echo "<p id='default_values1'>";
                    echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                    $name = "default_values[1]";
                    $value = 0;
                    Dropdown::showYesNo($name, $value);
                    echo '</p>';
                    echo "</td>";
                    //                  }
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td colspan='2' align='right' id='show_custom_fields'>";
                    PluginMetademandsField::initCustomValue(1, false, $display_default);
                    echo "</td>";
                    echo "</tr>";
                }
            }
        }
    }

    static function showFieldCustomFields($params)
    {

        $disp                              = [];
        $disp[self::CLASSIC_DISPLAY]       = __("Classic display", "metademands");
        $disp[self::DOUBLE_COLUMN_DISPLAY] = __("Double column display", "metademands");
        echo "<tr><td>";
        echo "<table class='metademands_show_custom_fields'>";
        echo "<tr><td>";
        echo __('Display type of the field', 'metademands');
        //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
        echo '</td>';
        echo "<td>";

        echo Dropdown::showFromArray("display_type", $disp, ['value' => $params['display_type'], 'display' => false]);
        echo "</td></tr>";

        if ($params["item"] == 'User') {
            echo "<tr>";
            echo "<td colspan='2' class='center'>";
            echo __("Informations to display in ticket and PDF", "metademands");
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2' class='center'>";
            $params['informations_to_display'] = json_decode($params['informations_to_display']) ?? [];

            $informations["full_name"] = __('Complete name');
            $informations["realname"]  = __('Surname');
            $informations["firstname"] = __('First name');
            $informations["name"]      = __('Login');
            //                     $informations["group"]             = Group::getTypeName(1);
            $informations["email"] = _n('Email', 'Emails', 1);
            echo Dropdown::showFromArray('informations_to_display', $informations, ['values'   => $params['informations_to_display'],
                'display'  => false,
                'multiple' => true]);
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</td></tr>";

    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
//        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
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
        switch ($params["item"]) {
            case 'User':
                $userrand = mt_rand();
                $name = "check_value";
                User::dropdown(['name' => $name,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'right' => 'all',
                    'rand' => $userrand,
                    'value' => $params['check_value'],
                    'display' => true,
                    'used' => $already_used
                ]);
                break;
            default:
                $dbu = new DbUtils();
                if ($item = $dbu->getItemForItemtype($params["item"])
                    && $params['type'] != "dropdown_multiple") {
                    //               if ($params['value'] == 'group') {
                    //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                    //               } else {
                    $name = "check_value";
                    //               }
                    $params['item']::Dropdown(["name" => $name,
                        "value" => $params['check_value'], 'used' => $already_used]);
                } else {
                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                        $elements[0] = Dropdown::EMPTY_VALUE;
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            if ($key != 0) {
                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                            }
                        }
                    } else {
                        $elements[0] = Dropdown::EMPTY_VALUE;
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            $elements[$key] = urldecode($val);
                        }
                    }
                    Dropdown::showFromArray(
                        "check_value",
                        $elements,
                        ['value' => $params['check_value'], 'used' => $already_used]
                    );
                }
                break;
        }
    }


    static function showParamsValueToCheck($params)
    {
        switch ($params["item"]) {
            case 'User':
                echo getUserName($params['check_value'], 0, true);
                break;
            default:
                $dbu = new DbUtils();
                if ($item = $dbu->getItemForItemtype($params["item"])
                    && $params['type'] != "dropdown_multiple") {
                    echo Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
                } else {
                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                        $elements = [];
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            if ($key != 0) {
                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                            }
                        }
                        echo $elements[$params['check_value']];
                    } else {
                        $elements = [];
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            $elements[$key] = urldecode($val);
                        }
                        echo $elements[$params['check_value']] ?? "";
                    }
                }
                break;
        }
    }

    static function isCheckValueOK($value, $check_value)
    {
        if (empty($value)) {
            $value = [];
        }
        if ($check_value == PluginMetademandsField::$not_null && is_array($value) && count($value) == 0) {
            return false;
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

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function fieldsHiddenScript($data)
    {

        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {

            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('fieldsHiddenScript-dropdownmultiple $id');";
            }
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
            $script .= "var tohide = {};";

            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $script .= "if ($hidden_link in tohide) {
                             } else {
                                tohide[$hidden_link] = true;
                             }";
                $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";

                if ($data["item"] == "other") {
                    $val = Toolbox::addslashes_deep($custom_value[$idc]);
                    $script .= "if ($(value).attr('title') == '$val') {
                                    tohide[" . $hidden_link . "] = false;
                                }";
                } else {
                    $script .= "if ($(value).attr('title') == '" . $data["item"]::getFriendlyNameById($hidden_link) . "') {
                                    tohide[" . $hidden_link . "] = false;
                                }";
                }

                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == $idc && $hidden_link > 0) {
                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                            }
                        }
                    }
                }

//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
//                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//                }
//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
//                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
//                        if ($fieldSession == $idc) {
//                            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//                        }
//                    }
//                }
                $script .= "});";

                $script .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                                $('[id-field =\"field'+key+'\"]').hide();
                                " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                                $('[name =\"field['+key+']\"]').removeAttr('required');
                            } else {
                                $('[id-field =\"field'+key+'\"]').show();
                                " . PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link) . "
                            }
                        });";
            }



            $script .= "});";

            //Initialize id default value
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
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
        } else {

            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('fieldsHiddenScript-dropdownmultiple $id');";
            }
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";

            $script .= "var tohide = {};";

            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];

                $script .= "if ($hidden_link in tohide) {
                            } else {
                                tohide[$hidden_link] = true;
                            }";
//
                $script .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {";
                $script .= "if ($(value).attr('value') == '$idc') {
                               tohide[" . $hidden_link . "] = false;
                            }";
                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
//                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//                }
//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] )) {
//                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
//                        if ($fieldSession == $idc) {
//                            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//                        }
//                    }
//                }

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == $idc && $hidden_link > 0) {
                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                            }
                        }
                    }
                }

                $script .= "});";
                $script .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                                $('[id-field =\"field'+key+'\"]').hide();
                                " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                                $('[name =\"field['+key+']\"]').removeAttr('required');
                            } else {
                                $('[id-field =\"field'+key+'\"]').show();
                                " . PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link) . "
                            }
                        });";
            }



            $script .= "});";
//                                    }
            foreach ($check_values as $idc => $check_value) {
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

    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {

            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('blocksHiddenScript-dropdownmultiple $id');";
            }
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
            $script .= "var tohide = {};";

            //by default - hide all
            $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);

            $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);

            foreach ($check_values as $idc => $check_value) {
                $hidden_block = $check_value['hidden_block'];
                $script .= "if ($hidden_block in tohide) {
                        } else {
                            tohide[$hidden_block] = true;
                        }";

                $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";
                $val = 0;
                if (isset($custom_value[$idc])) {
                    $val = Toolbox::addslashes_deep($custom_value[$idc]);
                }

                $script .= "if ($(value).attr('title') == '$val') {
                            tohide[" . $hidden_block . "] = false;
                        }";

                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
                            " . PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);

//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
//                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//                }
//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
//                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
//                        if ($fieldSession == $idc) {
//                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//                        }
//                    }
//                }

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == $idc && $hidden_block > 0) {
                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                            }
                        }
                    } else {
                        if ($session_value == $idc && $hidden_block > 0) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                        }
                    }
                }

                $script .= "});";

                $script .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[bloc-id=\"bloc'+key+'\"]').hide();
                            $.each(tohide, function( key, value ) {
                                $('div[bloc-id =\"bloc'+key+'\"]').find(':input').each(function() {
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
                                                if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                    tinymce.get(this.id).setContent('');
                                                }
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                jQuery(this).val('0');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                                 this.checked = false;
                                                 var checkname = this.name;
                                                 $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                        }
                                        jQuery(this).removeAttr('required');
                                        regex = /multiselectfield.*_to/g;
                                        totest = this.id;
                                        found = totest.match(regex);
                                        if(found !== null) {
                                          regex = /multiselectfield[0-9]*/;
                                           found = totest.match(regex);
                                           $('#'+found[0]+'_leftAll').click();
                                        }
                                    });
                            });
                         } else {
                            $('[bloc-id =\"bloc'+key+'\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block)."
                        }
                    });";

                //include child blocks
//                if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
//                    $childs_blocks = json_decode($check_value['childs_blocks'], true);
//                    if (isset($childs_blocks)
//                        && is_array($childs_blocks)
//                        && count($childs_blocks) > 0) {
//                        foreach ($childs_blocks as $childs) {
//                            if (is_array($childs)) {
//                                foreach ($childs as $childs_block) {
//                                    $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();
//                                                            " . PluginMetademandsFieldoption::resetMandatoryBlockFields($childs_block);
//                                    $hiddenblocks[] = $childs_block;
//                                    $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['hidden_blocks'] = $hiddenblocks;
//                                }
//                            }
//                        }
//                    }
//                }
                //Initialize id default value
                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                    $hidden_block = $data['options'][$idc]['hidden_block'];
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
            $script .= "fixButtonIndicator();});";

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        } else {

            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('blocksHiddenScript-dropdownmultiple $id');";
            }
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";

            $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
            $script .= "var tohide = {};";

            //by default - hide all
            $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);

            $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);

            foreach ($check_values as $idc => $check_value) {
                $hidden_block = $check_value['hidden_block'];
                $script .= "if ($hidden_block in tohide) {
                        } else {
                            tohide[$hidden_block] = true;
                        }";

                $script .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {";
                $script .= "if ($(value).attr('value') == '$idc') {
                               tohide[" . $hidden_block . "] = false;
                            }";

//                $val =  0;
//                if (isset($custom_value[$idc])) {
//                    $val =  Toolbox::addslashes_deep($custom_value[$idc]);
//                }
//
//                $script .= "if ($(value).attr('title') == '$val') {
//                            tohide[" . $hidden_block . "] = false;
//                        }";


                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
                            " . PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);

//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
//                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//                }
//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] )) {
//                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
//                        if ($fieldSession == $idc) {
//                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//                        }
//                    }
//                }
                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == $idc && $hidden_block > 0) {
                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                            }
                        }
                    } else {
                        if ($session_value == $idc && $hidden_block > 0) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                        }
                    }
                }

                $script .= "});";

                $script .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[bloc-id=\"bloc'+key+'\"]').hide();
                            $.each(tohide, function( key, value ) {
                                $('div[bloc-id =\"bloc'+key+'\"]').find(':input').each(function() {
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
                                                if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                    tinymce.get(this.id).setContent('');
                                                }
                                                break;
                                            case 'select-one':
                                            case 'select-multiple':
                                                jQuery(this).val('0').trigger('change');
                                                jQuery(this).val('0');
                                                break;
                                            case 'checkbox':
                                            case 'radio':
                                                 this.checked = false;
                                                 var checkname = this.name;
                                                 $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                        }
                                        jQuery(this).removeAttr('required');
                                        regex = /multiselectfield.*_to/g;
                                        totest = this.id;
                                        found = totest.match(regex);
                                        if(found !== null) {
                                          regex = /multiselectfield[0-9]*/;
                                           found = totest.match(regex);
                                           $('#'+found[0]+'_leftAll').click();
                                        }
                                    });
                            });
                         } else {
                            $('[bloc-id =\"bloc'+key+'\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block)."
                        }
                    });";

                //include child blocks
//                if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
//                    $childs_blocks = json_decode($check_value['childs_blocks'], true);
//                    if (isset($childs_blocks)
//                        && is_array($childs_blocks)
//                        && count($childs_blocks) > 0) {
//                        foreach ($childs_blocks as $childs) {
//                            if (is_array($childs)) {
//                                foreach ($childs as $childs_block) {
//                                    $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();
//                                                            " . PluginMetademandsFieldoption::resetMandatoryBlockFields($childs_block);
//                                    $hiddenblocks[] = $childs_block;
//                                    $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['hidden_blocks'] = $hiddenblocks;
//                                }
//                            }
//                        }
//                    }
//                }
                //Initialize id default value
                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                    $hidden_block = $data['options'][$idc]['hidden_block'];
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
            $script .= "fixButtonIndicator();});";

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

        }
    }

    public static function checkboxScript($data, $idc)
    {
        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $checkbox_id = $data['options'][$idc]['checkbox_id'];
            $checkbox_value = $data['options'][$idc]['checkbox_value'];

            $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
            $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";

            if (isset($checkbox_id) && $checkbox_id > 0) {
                if ($data["item"] == "other") {
                    $title = Toolbox::addslashes_deep($custom_value[$idc]);
                    $script .= "if ($(value).attr('title') == '$title') {
                                    document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                                }";
                } else {
                    $script .= "if ($(value).attr('title') == '" . $data["item"]::getFriendlyNameById($idc) . "') {
                                    document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                                }";
                }
            }

            $script .= "});
                        fixButtonIndicator();
                        });";

            echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
        } else {
            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";

            if (isset($data['options'][$idc]['hidden_link'])
                && !empty($data['options'][$idc]['hidden_link'])) {
                $checkbox_id = $data['options'][$idc]['checkbox_id'];
                $checkbox_value = $data['options'][$idc]['checkbox_value'];

                $script .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {";

                if (isset($checkbox_id) && $checkbox_id > 0) {
                    $script .= " 
                           if($(value).attr('value') == '$idc'){
                              document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                           }
                        ";
                }
                $script .= "});
                           fixButtonIndicator();
                           });";
            }

            echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
        }

    }

    public static function getFieldValue($field, $lang)
    {

        if (!empty($field['custom_values'])
            && $field['item'] != 'User') {
            if ($field['item'] != "other") {
                $custom_values = PluginMetademandsField::_unserialize($field['custom_values']);
                foreach ($custom_values as $k => $val) {
                    $custom_values[$k] = $field["item"]::getFriendlyNameById($k);
                }
                $field['value'] = PluginMetademandsField::_unserialize($field['value']);
                $parseValue = [];
                foreach ($field['value'] as $value) {
                    $parseValue[] = $custom_values[$value];
                }
                return implode(', ', $parseValue);
            } else {
                $custom_values = PluginMetademandsField::_unserialize($field['custom_values']);

                foreach ($custom_values as $k => $val) {
                    if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                        $custom_values[$k] = $ret;
                    }
                }
                $field['value'] = PluginMetademandsField::_unserialize($field['value']);
                $parseValue = [];
                if (is_array($field['value'])) {
                    foreach ($field['value'] as $k => $value) {
                        $parseValue[] = $custom_values[$value];
                    }
                }

                return implode(', ', $parseValue);
            }
        } elseif ($field['item'] == 'User') {
            $parseValue = [];
            $item = new $field["item"]();
            foreach ($field['value'] as $value) {
                if ($item->getFromDB($value)) {
                    $parseValue[] = $field["item"]::getFriendlyNameById($value);
                }
            }
            return implode(',', $parseValue);

        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if (!empty($field['custom_values'])
            && $field['item'] != 'User') {
            $result[$field['rank']]['display'] = true;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } elseif ($field['item'] == 'User') {
            $information = json_decode($field['informations_to_display']);

            if ($formatAsTable) {
                $dataItems = "<table style='border:0;'>";
            }
            $item = new $field["item"]();
            if (is_array($field['value'])) {
                foreach ($field['value'] as $value) {
                    if ($item->getFromDB($value)) {
                        if ($formatAsTable) {
                            $dataItems .= "<tr>";
                        }

                        if (in_array('full_name', $information)) {
                            if ($formatAsTable) {
                                $dataItems .= "<td>";
                            }
                            $dataItems .= $field["item"]::getFriendlyNameById($value);
                            if ($formatAsTable) {
                                $dataItems .= "</td>";
                            }
                        }
                        if (in_array('realname', $information)) {
                            if ($formatAsTable) {
                                $dataItems .= "<td>";
                            }
                            $dataItems .= $item->fields["realname"];
                            if ($formatAsTable) {
                                $dataItems .= "</td>";
                            }
                        }
                        if (in_array('firstname', $information)) {
                            if ($formatAsTable) {
                                $dataItems .= "<td>";
                            }
                            $dataItems .= $item->fields["firstname"];
                            if ($formatAsTable) {
                                $dataItems .= "</td>";
                            }
                        }
                        if (in_array('name', $information)) {
                            if ($formatAsTable) {
                                $dataItems .= "<td>";
                            }
                            $dataItems .= $item->fields["name"];
                            if ($formatAsTable) {
                                $dataItems .= "</td>";
                            }
                        }
                        if (in_array('email', $information)) {
                            if ($formatAsTable) {
                                $dataItems .= "<td>";
                            }
                            $dataItems .= $item->getDefaultEmail();
                            if ($formatAsTable) {
                                $dataItems .= "</td>";
                            }
                        }
                        if ($formatAsTable) {
                            $dataItems .= "</tr>";
                        }
                    }
                }
            }
            if ($formatAsTable) {
                $dataItems .= "</table>";
            }
            $result[$field['rank']]['display'] = true;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td>";
            }
            $result[$field['rank']]['content'] .= $dataItems;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        }

        return $result;
    }
}
