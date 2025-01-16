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

    public static $dropdown_multiple_items = ['other', 'Location', 'Appliance', 'User', 'Group'];

    public static $dropdown_multiple_objects = ['Location', 'Appliance', 'User', 'Group'];
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

    static function showWizardField($data, $namefield, $value, $on_order)
    {
        global $DB;

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }
        $field = "";

        if ($data["display_type"] != self::CLASSIC_DISPLAY) {
            $js = Html::script(PLUGIN_METADEMANDS_DIR_NOFULL . "/lib/multiselect2/dist/js/multiselect.js");
            $css = Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/lib/multiselect2/dist/css/multiselect.css");

            $field = $js;
            $field .= $css;
        }

        $required = "";
        if ($data['is_mandatory'] == 1) {
            $required = "required=required";
        }

        if ($data['item'] == User::getType()) {
            $self = new PluginMetademandsField();

            $criteria = $self->getDistinctUserCriteria() + $self->getProfileJoinCriteria();
            $criteria['FROM'] = getTableForItemType($data['item']);
            $criteria['WHERE'][getTableForItemType($data['item']) . '.is_deleted'] = 0;
            $criteria['WHERE'][getTableForItemType($data['item']) . '.is_active'] = 1;
            $criteria['ORDER'] = ['realname, firstname ASC'];

            if (!empty($data['custom_values'])) {
                $options = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);

                if (isset($options['user_group']) && $options['user_group'] == 1) {
                    $condition = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                    $group_user_data = Group_User::getUserGroups(Session::getLoginUserID(), $condition);
                    $users = [];
                    foreach ($group_user_data as $groups) {
                        $requester_users = Group_User::getGroupUsers($groups['id']);
                        foreach ($requester_users as $k => $v) {
                            $users[] = $v['id'];
                        }
                    }

                    $criteria['WHERE'][getTableForItemType($data['item']) . '.id'] = $users;
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
                $default_user = $data['default_use_id_requester'] == 0 ? 0 : Session::getLoginUserID();
                if ($default_user == 0) {
                    $user = new User();
                    $user->getFromDB(Session::getLoginUserID());
                    $default_user = ($data['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                }
                if ($default_user > 0) {
                    $value = [$default_user];
                } else {
                    $value = [];
                }
            }

            if ($data["display_type"] != self::CLASSIC_DISPLAY) {
                $field .= self::loadMultiselectDiv($namefield, $data['id'], $data['item'], $required, $list, $value);

                $field .= self::loadMultiselectScript($namefield, $data['id']);
            } else {
                $default_user = $data['default_use_id_requester'] == 0 ? 0 : Session::getLoginUserID();

                if ($default_user == 0) {
                    $user = new User();
                    $user->getFromDB(Session::getLoginUserID());
                    $default_user = ($data['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                }

                $field = Dropdown::showFromArray(
                    $namefield . "[" . $data['id'] . "]",
                    $list,
                    [
                        'values' => $value,
                        'value' => $default_user,
                        'width' => '250px',
                        'multiple' => true,
                        'display' => false,
                        'required' => ($data['is_mandatory'] ? "required" : "")
                    ]
                );
            }
        } elseif ($data['item'] == 'other') {
            if (!empty($data['custom_values'])) {
                $custom_values = $data['custom_values'] ?? [];

                if (!empty($value) && !is_array($value)) {
                    $value = json_decode($value);
                }
                if (!is_array($value)) {
                    $value = [];
                    foreach ($custom_values as $custom_value) {
                        if ($custom_value['is_default'] == 1) {
                            $value[$custom_value['id']] = $custom_value['name'];
                        }
                    }
                }

                if ($data["display_type"] != self::CLASSIC_DISPLAY) {
                    $field .= self::loadMultiselectDiv(
                        $namefield,
                        $data['id'],
                        $data['item'],
                        $required,
                        $custom_values,
                        $value
                    );

                    $field .= self::loadMultiselectScript($namefield, $data['id']);
                } else {
                    if (count($custom_values) > 0) {
                        foreach ($custom_values as $k => $val) {
                            $custom_values[$k] = $val['name'];
                        }
                    }
                    if (!is_array($value)) {
                        $value = [];
                    }
                    $field = Dropdown::showFromArray(
                        $namefield . "[" . $data['id'] . "]",
                        $custom_values,
                        [
                            'values' => $value,
                            'width' => '250px',
                            'multiple' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? "required" : "")
                        ]
                    );
                }
            }
        } else {
            $item = new $data['item'];
            $criteria['FROM'] = getTableForItemType($data['item']);

            if ($item->maybeDeleted()) {
                $criteria['WHERE'][getTableForItemType($data['item']) . '.is_deleted'] = 0;
            }

            if ($item->maybeTemplate()) {
                $criteria['WHERE'][getTableForItemType($data['item']) . '.is_template'] = 0;
            }

            $criteria['WHERE'] = getEntitiesRestrictCriteria(
                getTableForItemType($data['item']),
                '',
                '',
                $item->maybeRecursive()
            );

            if ($data['item'] == Location::getType() || $data['item'] == Group::getType()) {
                $criteria['ORDER'] = ['completename ASC'];
            } else {
                $criteria['ORDER'] = ['name ASC'];
            }


            $iterator = $DB->request($criteria);

            $list = [];
            if ($data['item'] == Location::getType() || $data['item'] == Group::getType()) {
                foreach ($iterator as $datau) {
                    $list[$datau['id']] = $datau['completename'];
                }
            } else {
                foreach ($iterator as $datau) {
                    $list[$datau['id']] = $datau['name'];
                }
            }

            $custom_values = $data['custom_values'] ?? [];
            if (count($custom_values) > 0 && ($data['item'] == "Appliance" || $data['item'] == "Group")) {
                $list = [];
                foreach ($custom_values as $k => $custom_value) {
                    $app = new $data['item']();
                    if ($app->getFromDB($custom_value)) {
                        $list[$custom_value] = $app->getName();
                    }
                }
            }

            if (!empty($value) && !is_array($value)) {
                $value = json_decode($value);
            }
            if (!is_array($value)) {
                $value = [];
            }

            $default_values = $data['default_values'] ?? [];
            if (count($value) == 0 && count($default_values) > 0 && ($data['item'] == "Appliance" || $data['item'] == "Group")) {
                $value = [];
                foreach ($default_values as $k => $as_default) {
                    if ($as_default == 1) {
                        $value[$k] = $k;
                    }
                }
            }

            if ($data["display_type"] != self::CLASSIC_DISPLAY) {
                $field .= self::loadMultiselectDiv($namefield, $data['id'], $data['item'], $required, $list, $value);

                $field .= self::loadMultiselectScript($namefield, $data['id']);
            } else {
                $field = Dropdown::showFromArray(
                    $namefield . "[" . $data['id'] . "]",
                    $list,
                    [
                        'values' => $value,
                        'width' => '250px',
                        'multiple' => true,
                        'display' => false,
                        'required' => ($data['is_mandatory'] ? "required" : "")
                    ]
                );
            }
        }

        echo $field;
    }

    static function loadMultiselectDiv($namefield, $id, $item, $required, $list, $value)
    {

        $name = $namefield . "[" . $id . "][]";

        $div = "<div class='row'>";
        $div .= "<div class='zone'>";
        $div .= "<select name='from' id=\"multiselect$namefield" . $id . "\" class='formCol' size='8' multiple='multiple'>";
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $k => $val) {
                if (!in_array($k, $value)) {
                    if ($item == 'other') {
                        $div .= "<option value=\"$k\">" . $val['name'] . "</option>";
                    } else {
                        $div .= "<option value=\"$k\" >$val</option>";
                    }
                }
            }
        }
        $div .= "</select>";
        $div .= "</div>";

        $div .= " <div class=\"centralCol\" style='width: 3%;'>
                                   <button type=\"button\" id=\"multiselect$namefield" . $id . "_rightAll\" class=\"btn buttonColTop buttonCol\"><i class=\"fas fa-angle-double-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $id . "_rightSelected\" class=\"btn  buttonCol\"><i class=\"fas fa-angle-right\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $id . "_leftSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-left\"></i></button>
                                   <button type=\"button\" id=\"multiselect$namefield" . $id . "_leftAll\" class=\"btn buttonCol\"><i class=\"fas fa-angle-double-left\"></i></button>
                               </div>";

        $div .= "<div class='zone'>";
        $div .= "<select class='form-select formCol' $required name='$name' id=\"multiselect$namefield" . $id . "_to\" size='8' multiple='multiple'>";
        if (is_array($value) && count($value) > 0) {

            foreach ($value as $k => $val) {
                if ($item == 'other') {
//                    $div .= "<option value=\"$val\">" . $list[$val]['name'] . "</option>";
                    $div .= "<option value=\"$k\">" . $val . "</option>";
                } else if ($item == User::getType()) {
                    $div .= "<option selected value=\"$val\" >" . getUserName($val, 0, true) . "</option>";
                } else {
                    $div .= "<option selected value=\"$val\" >" . Dropdown::getDropdownName(
                            getTableForItemType($item),
                            $val
                        ) . "</option>";
                }
            }
        }
        $div .= "</select>";
        $div .= "</div>";
        $div .= "</div>";

        return $div;
    }

    static function loadMultiselectScript($namefield, $id)
    {
        $script = Html::scriptBlock(
            '$(document).ready(function() {
                            $("#multiselect' . $namefield . $id . '").multiselect({
                                      search: {
                                          left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __(
                "Search"
            ) . '...\" />",
                                          right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __(
                "Search"
            ) . '...\" />",
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
                            });'
        );
        return $script;
    }

    static function showFieldCustomValues($params)
    {
        $custom_values = $params['custom_values'];

        echo "<tr>";
        echo "<td>";
        $maxrank = 0;

        $dbu = new DbUtils();
        if ($params["item"] != "User") {
            if ($params["item"] != "other"
                && $params["item"] != "Location"
                && !empty($params["item"])
                && $dbu->getItemForItemtype($params["item"])
            ) {
                $item = new $params['item'];
                $criteria = [];
                $default_values = $params['default_values'];

                $items = $item->find($criteria, ["name ASC"]);

                $target = PluginMetademandsFieldCustomvalue::getFormURL();
                echo "<form method='post' id='customvalues_form' action=\"$target\">";

                echo Html::scriptBlock("$(function () {
                    $('#checkall').click(function () {
                            var checkboxes = document.querySelectorAll('input[type=\"checkbox\"]');
                            for (var i = 0; i < checkboxes.length; i++) {
                            if (checkboxes[i].type == 'checkbox')
                            checkboxes[i].checked = true;
                        }
                    });
                    $('#uncheckall').click(function () {
                            var checkboxes = document.querySelectorAll('input[type=\"checkbox\"]');
                            for (var i = 0; i < checkboxes.length; i++) {
                            if (checkboxes[i].type == 'checkbox')
                            checkboxes[i].checked = false;
                        }
                    });
                });");
                echo "<tr class='tab_bg_1'>";

                echo "<th>";
                echo _n('Custom value', 'Custom values',2,'metademands');
                echo "</th>";

                echo "<th width='20%'>";
                echo _n('Default value', 'Default values', 1, 'metademands');
                echo "</th>";

                echo "<th width='20%'>";
                echo __('Display value in the dropdown', 'metademands');
                echo "<br><a href='#' id='checkall'>".__('Select all', 'metademands')."</a>";
                echo " / <a href='#' id='uncheckall'>".__('Unselect all', 'metademands')."</a>";
                echo "</th>";

                echo "</tr>";
                foreach ($items as $key => $v) {
                    echo "<tr class='tab_bg_1'>";

                    echo "<td>";
                    echo "<span id='custom_values$key'>";
                    echo $v["name"];
                    echo "</span>";
                    echo "</td>";

                    echo "<td width='20%'>";
                    echo "<span id='default_values$key'>";
                    $name = "default[" . $key . "]";
                    $value = (isset($default_values[$key]) ? $default_values[$key] : 0);
                    Dropdown::showYesNo($name, $value);
                    echo "</span>";
                    echo "</td>";

                    echo "<td width='20%'>";
                    echo "<span id='present_values$key'>";
                    $checked = "";
                    if (isset($custom_values[$key])
                        && $custom_values[$key] != 0) {
                        $checked = "checked";
                    }
                    echo "<input type='checkbox' name='custom[" . $key . "]'  value='$key' $checked />";
                    echo "</span>";
                    echo "</td>";

                    echo "</tr>";
                }

                echo "<tr class='tab_bg_1'>";
                echo "<td>";
                echo Html::submit("", [
                    'name' => 'update',
                    'class' => 'btn btn-primary',
                    'icon' => 'fas fa-save'
                ]);
                echo "</td>";
                echo "</tr>";
                Html::closeForm();

            } else {
                if ($params['item'] != 'Location') {
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
                            }
                            echo "</td>";

                            echo "<td class='rowhandler control center'>";
                            echo "<span id='custom_values$key'>";
                            echo Html::input('name[' . $key . ']', ['value' => $value['name'], 'size' => 30]);
                            echo "</span>";
                            echo "</td>";

                            echo "<td class='rowhandler control center'>";
//                echo "<span id='comment_values$key'>";
//                echo __('Comment') . " ";
//                echo Html::input('comment['.$key.']', ['value' => $value['comment'], 'size' => 30]);
//                echo "</span>";
                            echo "</td>";

                            echo "<td class='rowhandler control center'>";
                            echo "<span id='default_values$key'>";
                            echo _n('Default value', 'Default values', 1, 'metademands') . " ";
                            Dropdown::showYesNo('is_default[' . $key . ']', $value['is_default']);
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
                                    'customvalues_id' => $key,
                                    'rank' => $value['rank'],
                                    'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                                ],
                                'fa-times-circle',
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
                        echo '</td>';

                        echo "<tr class='tab_bg_1'>";
                        echo "<td colspan='4' align='left' id='show_custom_fields'>";
                        PluginMetademandsFieldCustomvalue::initCustomValue($maxrank, false, true);
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
                        PluginMetademandsFieldCustomvalue::initCustomValue(-1, false, true);
                        echo "</td>";
                        echo "</tr>";
                        Html::closeForm();
                        PluginMetademandsFieldCustomvalue::importCustomValue($params);
                    }
                }
            }
        }
    }

    static function showFieldParameters($params)
    {
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::DOUBLE_COLUMN_DISPLAY] = __("Double column display", "metademands");
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Display type of the field', 'metademands');
        //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
        echo "</td>";
        echo "<td>";

        echo Dropdown::showFromArray("display_type", $disp, ['value' => $params['display_type'], 'display' => false]);
        echo "</td>";
        echo "</tr>";

        if ($params["item"] == 'User') {
            $custom_values = PluginMetademandsFieldParameter::_unserialize($params['custom_values']);
            $user_group = $custom_values['user_group'] ?? 0;
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Only users of my groups', 'metademands');
            echo "</td>";
            echo "<td>";
            // user_group
            Dropdown::showYesNo('user_group', $user_group);
            echo "<td colspan='2'></td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Use id of requester by default', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('default_use_id_requester', $params['default_use_id_requester']);
            echo "</td>";

            echo "<td>";
            echo __('Use id of supervisor requester by default', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo(
                'default_use_id_requester_supervisor',
                $params['default_use_id_requester_supervisor']
            );
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __("Informations to display in ticket and PDF", "metademands");
            echo "</td>";
            echo "<td>";
            $decode = json_decode($params['informations_to_display']);
            $values = empty($decode) ? ['full_name'] : $decode;

            $informations["full_name"] = __('Complete name');
            $informations["realname"] = __('Surname');
            $informations["firstname"] = __('First name');
            $informations["name"] = __('Login');
            //                     $informations["group"]             = Group::getTypeName(1);
            $informations["email"] = _n('Email', 'Emails', 1);
            echo Dropdown::showFromArray('informations_to_display', $informations, [
                'values' => $values,
                'display' => false,
                'multiple' => true
            ]);
            echo "</td>";
            echo "</tr>";
        }
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
                User::dropdown([
                    'name' => $name,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'right' => 'all',
                    'rand' => $userrand,
                    'value' => $params['check_value'],
                    'display' => true,
                    'used' => $already_used
                ]);
                break;
            case 'Group':
                $lrand = mt_rand();
                $name = "check_value";
                Group::dropdown([
                    'name' => $name,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'rand' => $lrand,
                    'value' => $params['check_value'],
                    'display' => true,
                    'used' => $already_used
                ]);
                break;
            case 'Location':
                $lrand = mt_rand();
                $name = "check_value";
                Location::dropdown([
                    'name' => $name,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'rand' => $lrand,
                    'value' => $params['check_value'],
                    'display' => true,
                    'used' => $already_used
                ]);
//                ,
//                'toadd' => [-1 => __('Not null value', 'metademands')]
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
                    $params['item']::dropdown([
                        "name" => $name,
                        "value" => $params['check_value'],
                        'used' => $already_used
                    ]);
//                    ,
//                    'toadd' => [-1 => __('Not null value', 'metademands')]
                } else {
//                    $elements[-1] = __('Not null value', 'metademands');
                    $elements[0] = Dropdown::EMPTY_VALUE;

                    if ($params["item"] != "other"
                        && $params["item"] != "Location"
                        && $params["type"] == "dropdown_multiple") {

                        if ($params["item"] == "Appliance") {
                            $params['custom_values'] = PluginMetademandsFieldParameter::_unserialize($params['custom_values']);
                        }

                        if (is_array($params['custom_values'])) {
                            $elements += $params['custom_values'];
                        }
                        foreach ($elements as $key => $val) {
                            if ($key != 0) {
                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                            }
                        }
                    } else {
                        foreach ($params['custom_values'] as $key => $val) {
                            $elements[$val['id']] = $val['name'];
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
        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            echo __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'User':
                    echo getUserName($params['check_value'], 0, true);
                    break;
                case 'Location':
                    echo Dropdown::getDropdownName("glpi_locations", $params['check_value']);
                    break;
                case 'Group':
                    echo Dropdown::getDropdownName("glpi_groups", $params['check_value']);
                    break;
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        echo Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
                    } else {
                        if ($params["item"] != "other"
                            && $params["item"] != "Location"
                            && $params["type"] == "dropdown_multiple") {
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
                            foreach ($params['custom_values'] as $key => $val) {
                                $elements[$val['id']] = $val['name'];
                            }
                            echo $elements[$params['check_value']] ?? "";
                        }
                    }
                    break;
            }
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

    static function taskScript($data)
    {
        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('taskScript-dropdownmultiple $id');";
            }

            //if reload form on loading
            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                if (is_array($session_value)) {
                    foreach ($session_value as $k => $fieldSession) {
                        if ($fieldSession > 0) {
                            $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('$fieldSession').trigger('change');";
                        }
                    }
                }
            }

            $title = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . __(
                    'Next',
                    'metademands'
                ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


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

            $custom_value = $data['custom_values'] ?? [];
//            $custom_value = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $script .= "var tohide = {};";
            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];

                $script .= "if ($tasks_id in tohide) {
                             } else {
                                tohide[$tasks_id] = true;
                             }";
                $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";

                if ($data["item"] == "other") {
                    if (isset($data['custom_values'])
                        && is_array($data['custom_values'])
                        && count($data['custom_values']) > 0) {
                        $custom_values = $data['custom_values'];
                        foreach ($custom_values as $k => $custom_value) {
                            if ($k == $idc) {
                                $val = Toolbox::addslashes_deep($custom_value['name']);
                                //Pas compris
                                $script .= "if ($(value).attr('title') == '$val') {
                                        tohide[" . $tasks_id . "] = false;
                                    }";
                            }
                        }
                    }
                } else {
                    $script .= "if ($(value).attr('title') == '" . $data["item"]::getFriendlyNameById($tasks_id) . "') {
                                    tohide[" . $tasks_id . "] = false;
                                }";
                }
//
//                $script .= "if ($tasks_id in tohide) {
//                        } else {
//                            tohide[$tasks_id] = true;
//                        }
//                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
//                            tohide[$tasks_id] = false;
//                        }";

//            $script2 .= "$('[id-field =\"field" . $tasks_id . "\"]').hide();";
//
//            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
//                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
//                if (is_array($session_value)) {
//                    foreach ($session_value as $k => $fieldSession) {
//                        if ($fieldSession == $idc && $tasks_id > 0) {
//                            $script2 .= "$('[id-field =\"field" . $tasks_id . "\"]').show();";
//                        }
//                    }
//                }
//            }
                $script .= "});";
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
            }
            $script .= "});";

            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $check_value['plugin_metademands_tasks_id'];

                //Initialize id default value
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if (isset($custom_value['is_default'])
                            && $custom_value['is_default'] == 1) {
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

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        } else {
            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('taskScript-dropdownmultiple $id');";
            }

            //if reload form on loading
            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                if (is_array($session_value)) {
                    foreach ($session_value as $k => $fieldSession) {
                        if ($fieldSession > 0) {
                            $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('$fieldSession').trigger('change');";
                        }
                    }
                }
            }

            $title = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . __(
                    'Next',
                    'metademands'
                ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


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

            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";
            $script .= "var tohide = {};";
            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];

                $script .= "if ($tasks_id in tohide) {
                            } else {
                                tohide[$tasks_id] = true;
                            }";
//
                $script .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {";
                $script .= "if ($(value).attr('value') == '$idc') {
                               tohide[" . $tasks_id . "] = false;
                            }";
//
//                $script .= "if ($tasks_id in tohide) {
//                        } else {
//                            tohide[$tasks_id] = true;
//                        }
//                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
//                            tohide[$tasks_id] = false;
//                        }";

//            $script2 .= "$('[id-field =\"field" . $tasks_id . "\"]').hide();";
//
//            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
//                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
//                if (is_array($session_value)) {
//                    foreach ($session_value as $k => $fieldSession) {
//                        if ($fieldSession == $idc && $tasks_id > 0) {
//                            $script2 .= "$('[id-field =\"field" . $tasks_id . "\"]').show();";
//                        }
//                    }
//                }
//            }
                $script .= "});";
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
                    });";
            }
            $script .= "});";

            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $check_value['plugin_metademands_tasks_id'];
                //Initialize id default value
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if (isset($custom_value['is_default']) && $custom_value['is_default'] == 1) {
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
            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    static function fieldsHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $onchange = "";
            $pre_onchange = "";
            $post_onchange = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $onchange = "console.log('fieldsHiddenScript-dropdownmultiple $id');";
            }

            //Initialize id default value
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];

                //Initialize id default value
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && isset($custom_value['is_default']) && $custom_value['is_default'] == 1) {
                            $onchange .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                        }
                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";

            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $onchange .= "if ($hidden_link in tohide) {
                             } else {
                                tohide[$hidden_link] = true;
                             }";
                $onchange .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";

                if ($data["item"] == "other") {
                    if (isset($data['custom_values'])
                        && is_array($data['custom_values'])
                        && count($data['custom_values']) > 0) {
                        $custom_values = $data['custom_values'];
                        foreach ($custom_values as $k => $custom_value) {
                            if ($k == $idc) {
                                $val = Toolbox::addslashes_deep($custom_value['name']);
                                //Pas compris
                                $onchange .= "if ($(value).attr('title') == '$val') {
                                        tohide[" . $hidden_link . "] = false;
                                    }";
                            }
                        }
                    }
                } else {
                    $onchange .= "if ($(value).attr('title') == '" . $data["item"]::getFriendlyNameById($hidden_link) . "') {
                                    tohide[" . $hidden_link . "] = false;
                                }";
                }

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == $idc && $hidden_link > 0) {
                                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                            }
                        }
                    }
                }

                $onchange .= "});";

                $onchange .= "$.each( tohide, function( key, value ) {
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


            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        } else {
            $onchange = "";
            $pre_onchange = "";
            $post_onchange = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $onchange = "console.log('fieldsHiddenScript-dropdownmultiple $id');";
            }

            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                //Initialize id default value
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && isset($custom_value['is_default']) && $custom_value['is_default'] == 1) {
                            $onchange .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                        }
                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
            }

            $onchange .= "var tohide = {};";

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";

            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];

                $onchange .= "if ($hidden_link in tohide) {
                             } else {
                                tohide[$hidden_link] = true;
                             }";

                $onchange .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, select ) {";

                $onchange .= "if ($(select).attr('value') == '$idc') {
                               tohide[" . $hidden_link . "] = false;
                            }";

                $onchange .= "});";

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == $idc && $hidden_link > 0) {
                                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                            }
                        }
                    }
                }

                $onchange .= "$.each( tohide, function( key, value ) {
                
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

        //hidden_blocks by idc
        $hiddenblocks_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            if (isset($check_value['hidden_block'])) {
                $hiddenblocks_by_checkvalue[$idc] = $check_value['hidden_block'];
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

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('blocksHiddenScript-dropdownmultiple $id');";
            }
            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $script .= "var tohide = {};";

            //by default - hide all
            $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }

            foreach ($check_values as $idc => $check_value) {
                $hidden_block = $check_value['hidden_block'];
                $script .= "if ($hidden_block in tohide) {
                        } else {
                            tohide[$hidden_block] = true;
                        }";

                $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";
                $val = 0;
                if ($data["item"] == "other") {
                    if (isset($data['custom_values'])
                        && is_array($data['custom_values'])
                        && count($data['custom_values']) > 0) {
                        $custom_values = $data['custom_values'];

                        foreach ($custom_values as $k => $custom_value) {
                            if ($k == $idc) {
                            $val = Toolbox::addslashes_deep($custom_value['name']);
                            //Pas compris
                            $script .= "if ($(value).attr('title') == '$val') {
                                        tohide[" . $hidden_block . "] = false;
                                    }";
                            }
                        }
                    }
                }
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
                                            case 'range':
                                            case 'tel':
                                            case 'email':
                                            case 'url':
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
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block) . "
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
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && isset($custom_value['is_default']) && $custom_value['is_default'] == 1) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
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

//            $custom_value = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
            $script .= "var tohide = {};";

            //by default - hide all
            $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }
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
                                            case 'range':
                                            case 'tel':
                                            case 'email':
                                            case 'url':
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
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block) . "
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
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && isset($custom_value['is_default']) && $custom_value['is_default'] == 1) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
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

            $custom_values = $data['custom_values'];

            $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";

            if (isset($checkbox_id) && $checkbox_id > 0) {
                if ($data["item"] == "other") {
                    $title = Toolbox::addslashes_deep($custom_values[$idc]['name']);
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
            && $field['item'] != 'User'
            && $field['item'] != 'Location'
            && $field['item'] != 'Group'
            && $field['item'] != 'Appliance') {
            if ($field['item'] != "other") {
                $custom_values = PluginMetademandsFieldParameter::_unserialize($field['custom_values']);
                foreach ($custom_values as $k => $val) {
                    $custom_values[$k] = $field["item"]::getFriendlyNameById($k);
                }
                $field['value'] = PluginMetademandsFieldParameter::_unserialize($field['value']);
                $parseValue = [];
                foreach ($field['value'] as $value) {
                    $parseValue[] = $custom_values[$value];
                }
                return implode(', ', $parseValue);
            } else {

                $custom_values = [];
                foreach ($field['custom_values'] as $key => $val) {
                    $custom_values[$val['id']] = $val['name'];
                }

                foreach ($custom_values as $k => $val) {
                    if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                        $custom_values[$k] = $ret;
                    }
                }
                $field['value'] = PluginMetademandsFieldParameter::_unserialize($field['value']);
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
        } elseif ($field['item'] == 'Location' || $field['item'] == 'Group' || $field['item'] == 'Appliance') {
            $parseValue = [];
            $item = new $field["item"]();
            foreach ($field['value'] as $value) {
                if ($item->getFromDB($value)) {
                    $parseValue[] = $field["item"]::getFriendlyNameById($value);
                }
            }
            return implode('<br>', $parseValue);
        }
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $style_title,
        $label,
        $field,
        $return_value,
        $lang
    ) {
        if (!empty($field['custom_values'])
            && $field['item'] != 'User'
            && $field['item'] != 'Location'
            && $field['item'] != 'Group'
            && $field['item'] != 'Appliance' && $field['value'] > 0) {
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
        } elseif (($field['item'] == 'Location' || $field['item'] == 'Group' || $field['item'] == 'Appliance')
            && $field['value'] > 0) {
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
        } elseif ($field['item'] == 'User' && ($field['value'] > 0
                || (is_array($field['value']) && count($field['value']) > 0))) {
            $information = json_decode($field['informations_to_display']);

            // legacy support
            if (empty($information)) {
                $information = ['full_name'];
            }

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
