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
use DbUtils;
use Group_User;
use Html;
use Location;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\MetademandTask;
use Session;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Dropdownmultiple Class
 *
 **/
class Dropdownmultiple extends CommonDBTM
{
    public static $dropdown_multiple_items = ['other', 'Location', 'Appliance', 'User', 'Group'];

    public static $dropdown_multiple_objects = ['Location', 'Appliance', 'User', 'Group'];
    public const CLASSIC_DISPLAY = 0;
    public const DOUBLE_COLUMN_DISPLAY = 1;

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
        return __('Dropdown multiple', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        global $DB;

        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }
        $field = "";

        if ($data["display_type"] != self::CLASSIC_DISPLAY) {
            $js = Html::script(PLUGIN_METADEMANDS_WEBDIR . "/lib/multiselect2/dist/js/multiselect.js");
            $css = Html::css(PLUGIN_METADEMANDS_WEBDIR . "/lib/multiselect2/dist/css/multiselect.css");

            $field = $js;
            $field .= $css;
        }

        $required = "";
        if ($data['is_mandatory'] == 1) {
            $required = "required=required";
        }

        if ($data['item'] == User::getType()) {
            $self = new Field();

            $criteria = $self->getDistinctUserCriteria() + $self->getProfileJoinCriteria();
            $criteria['FROM'] = getTableForItemType($data['item']);
            $criteria['WHERE'][getTableForItemType($data['item']) . '.is_deleted'] = 0;
            $criteria['WHERE'][getTableForItemType($data['item']) . '.is_active'] = 1;
            $criteria['ORDER'] = ['realname, firstname ASC'];

            if (!empty($data['custom_values'])) {
                $options = FieldParameter::_unserialize($data['custom_values']);

                if (isset($options['user_group']) && $options['user_group'] == 1) {
                    $condition = getEntitiesRestrictCriteria(\Group::getTable(), '', '', true);
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
                $field .= self::loadMultiselectDiv($namefield, $data['plugin_metademands_metademands_id'], $data['id'], $data['item'], $required, $list, $value);

                $field .= self::loadMultiselectScript($namefield, $data['id']);
            } else {
                $opt = [
                    'values' => $value,
                    'width' => '250px',
                    'multiple' => true,
                    'display' => false,
                    'required' => ($data['is_mandatory'] ? "required" : ""),
                ];
                if (count($value) == 0) {
                    $default_user = $data['default_use_id_requester'] == 0 ? 0 : Session::getLoginUserID();

                    if ($default_user == 0) {
                        $user = new User();
                        $user->getFromDB(Session::getLoginUserID());
                        $default_user = ($data['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                    }
                    $opt['value'] = $default_user;
                }


                $field = \Dropdown::showFromArray(
                    $namefield . "[" . $data['id'] . "]",
                    $list,
                    $opt
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
                        $data['plugin_metademands_metademands_id'],
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
                    $field = \Dropdown::showFromArray(
                        $namefield . "[" . $data['id'] . "]",
                        $custom_values,
                        [
                            'values' => $value,
                            'width' => '250px',
                            'multiple' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? "required" : ""),
                        ]
                    );
                }
            }
        } else {
            if (getItemForItemtype($data["item"])) {
                $item = new $data['item']();
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
                    $field .= self::loadMultiselectDiv($namefield, $data['plugin_metademands_metademands_id'], $data['id'], $data['item'], $required, $list, $value);

                    $field .= self::loadMultiselectScript($namefield, $data['id']);
                } else {
                    $field = \Dropdown::showFromArray(
                        $namefield . "[" . $data['id'] . "]",
                        $list,
                        [
                            'values' => $value,
                            'width' => '250px',
                            'multiple' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? "required" : ""),
                        ]
                    );
                }
            }
        }

        echo $field;
    }

    public static function loadMultiselectDiv($namefield, $plugin_metademands_metademands_id, $id, $item, $required, $list, $value)
    {

        $name = $namefield . "[" . $id . "][]";

        $div = "<div class='row'>";
        $div .= "<div class='zone'>";
        $div .= "<select name='from[]' id=\"multiselect" . $id . "\" class='formCol' size='8' multiple='multiple' style='font-size: 1em;'>";
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

        $div .= " <div class=\"centralCol\" style='width: 10%;'>
                       <button type=\"button\" style='display: none' id=\"multiselect" . $id . "_rightAll\" class=\"btn  buttonCol\"><i class=\"ti ti-chevrons-right\"></i></button>
                       <button type=\"button\" id=\"multiselect" . $id . "_rightSelected\" class=\"btn buttonColTop buttonCol\"><i class=\"ti ti-chevron-right\"></i></button>
                       <button type=\"button\" id=\"multiselect" . $id . "_leftSelected\" class=\"btn buttonCol\"><i class=\"ti ti-chevron-left\"></i></button>
                       <button type=\"button\" style='display: none' id=\"multiselect" . $id . "_leftAll\" class=\"btn buttonCol\"><i class=\"ti ti-chevrons-left\"></i></button>
                   </div>";

        $div .= "<div class='zone'>";
        if (isset($value) && is_array($value) && count($value) > 0) {
            $required = "";
        }
        $div .= "<select class='form-select formCol' $required name='$name' id=\"multiselect" . $id . "_to\" size='8' multiple='multiple' style='font-size: 1em;'>";
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $k => $val) {
                if ($item == 'other') {
                    if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$id])) {
                        $div .= "<option value=\"$val\">" . $list[$val]['name'] . "</option>";
                    } else {
                        $div .= "<option value=\"$k\">" . $val . "</option>";
                    }
                } elseif ($item == User::getType()) {
                    $div .= "<option selected value=\"$val\" >" . getUserName($val, 0, true) . "</option>";
                } else {
                    $div .= "<option selected value=\"$val\" >" . \Dropdown::getDropdownName(
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

    public static function loadMultiselectScript($namefield, $id)
    {
        $script = Html::scriptBlock(
            '$(document).ready(function() {
                            var tohide = {};
                            $("#multiselect' . $id . '").multiselect({
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

    public static function showFieldCustomValues($params)
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
                $item = new $params['item']();
                $criteria = [];
                $default_values = $params['default_values'];

                $items = $item->find($criteria, ["name ASC"]);

                $target = FieldCustomvalue::getFormURL();
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
                echo FieldCustomvalue::getTypeName(2);
                echo "</th>";

                echo "<th width='20%'>";
                echo _n('Default value', 'Default values', 1, 'metademands');
                echo "</th>";

                echo "<th width='20%'>";
                echo __('Display value in the dropdown', 'metademands');
                echo "<br><a href='#' id='checkall'>" . __('Select all', 'metademands') . "</a>";
                echo " / <a href='#' id='uncheckall'>" . __('Unselect all', 'metademands') . "</a>";
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
                    $value = ($default_values[$key] ?? 0);
                    \Dropdown::showYesNo($name, $value);
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
                    'icon' => 'ti ti-device-floppy',
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
                            //                echo "<span id='comment_values$key'>";
                            //                echo __('Comment') . " ";
                            //                echo Html::input('comment['.$key.']', ['value' => $value['comment'], 'size' => 30]);
                            //                echo "</span>";
                            echo "</td>";

                            echo "<td class='rowhandler control center'>";
                            echo "<span id='default_values$key'>";
                            echo _n('Default value', 'Default values', 1, 'metademands') . " ";
                            \Dropdown::showYesNo('is_default[' . $key . ']', $value['is_default']);
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
                        echo '</td>';

                        echo "<tr class='tab_bg_1'>";
                        echo "<td colspan='4' align='left' id='show_custom_fields'>";
                        FieldCustomvalue::initCustomValue($maxrank, false, true, $params["plugin_metademands_fields_id"]);
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
                        FieldCustomvalue::initCustomValue(-1, false, true, $params["plugin_metademands_fields_id"]);
                        echo "</td>";
                        echo "</tr>";
                        Html::closeForm();
                        FieldCustomvalue::importCustomValue($params);
                    }
                }
            }
        }
    }

    public static function showFieldParameters($params)
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

        echo \Dropdown::showFromArray("display_type", $disp, ['value' => $params['display_type'], 'display' => false]);
        echo "</td>";
        echo "</tr>";

        if ($params["item"] == 'User') {
            $custom_values = FieldParameter::_unserialize($params['custom_values']);
            $user_group = $custom_values['user_group'] ?? 0;
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Only users of my groups', 'metademands');
            echo "</td>";
            echo "<td>";
            // user_group
            \Dropdown::showYesNo('user_group', $user_group);
            echo "<td colspan='2'></td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Use id of requester by default', 'metademands');
            echo "</td>";
            echo "<td>";
            \Dropdown::showYesNo('default_use_id_requester', $params['default_use_id_requester']);
            echo "</td>";

            echo "<td>";
            echo __('Use id of supervisor requester by default', 'metademands');
            echo "</td>";
            echo "<td>";
            \Dropdown::showYesNo(
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
            echo \Dropdown::showFromArray('informations_to_display', $informations, [
                'values' => $values,
                'display' => false,
                'multiple' => true,
            ]);
            echo "</td>";
            echo "</tr>";
        }
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
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
        $params['use_richtext'] = 0;
        echo FieldOption::showLinkHtml($item->getID(), $params);
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];

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
                    'used' => $already_used,
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
                    'used' => $already_used,
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
                    'used' => $already_used,
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
                        'used' => $already_used,
                    ]);
                    //                    ,
                    //                    'toadd' => [-1 => __('Not null value', 'metademands')]
                } else {
                    //                    $elements[-1] = __('Not null value', 'metademands');
                    $elements[0] = \Dropdown::EMPTY_VALUE;

                    if ($params["item"] != "other"
                        && $params["item"] != "Location"
                        && $params["type"] == "dropdown_multiple") {
                        if ($params["item"] == "Appliance") {
                            $params['custom_values'] = FieldParameter::_unserialize($params['custom_values']);
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
                    \Dropdown::showFromArray(
                        "check_value",
                        $elements,
                        ['value' => $params['check_value'], 'used' => $already_used]
                    );
                }
                break;
        }
    }


    public static function showParamsValueToCheck($params)
    {
        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            echo __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'User':
                    echo getUserName($params['check_value'], 0, true);
                    break;
                case 'Location':
                    echo \Dropdown::getDropdownName("glpi_locations", $params['check_value']);
                    break;
                case 'Group':
                    echo \Dropdown::getDropdownName("glpi_groups", $params['check_value']);
                    break;
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        echo \Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
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

    public static function isCheckValueOK($value, $check_value)
    {
        if (empty($value)) {
            $value = [];
        }
        if ($check_value == Field::$not_null && is_array($value) && count($value) == 0) {
            return false;
        }
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

    public static function fieldsMandatoryScript($data)
    {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $onchange = "";
            $pre_onchange = "";
            $post_onchange = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $onchange = "console.log('fieldsLinkScript-dropdownmultiple $id');";
            }
            if (count($check_values) > 0) {
                //Si la valeur est en session
                if (isset($data['value']) && is_array($data['value'])) {
                    $values = $data['value'];
                    foreach ($values as $value) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $value . "').trigger('change');";
                    }
                }

                $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                $onchange .= "var tohide = {};";

                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['fields_link'] as $fields_link) {
                        $onchange .= "$.each($(this).val(), function( keys, values ) {

                            if ($fields_link in tohide) {
                            } else {
                                tohide[$fields_link] = true;
                            }
                            if (values != 0 && (values == $idc || $idc == 0 )) {
                                tohide[$fields_link] = false;
                            }";

                        $onchange .= "});";

                        $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            var id = '#metademands_wizard_red'+ key;
                            $(id).html('');
                            sessionStorage.setItem('hiddenlink$name', key);
                            " . Fieldoption::resetMandatoryFieldsByField($name) . "
                            $('[name =\"field['+key+']\"]').removeAttr('required');
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
                    }
                }

                $onchange .= "});";

                echo Html::scriptBlock(
                    '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
                );
            }
        } else {
            $onchange = "";
            $pre_onchange = "";
            $post_onchange = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $onchange = "console.log('fieldsLinkScript-dropdownmultiple $id');";
            }
            if (count($check_values) > 0) {
                //Si la valeur est en session
                if (isset($data['value']) && is_array($data['value'])) {
                    $values = $data['value'];
                    foreach ($values as $value) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $value . "').trigger('change');";
                    }
                }


                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['fields_link'] as $fields_link) {
                        $onchange .= "$('.centralCol').on('click', 'button', function () {
                    const index = $(this).index();
                    setTimeout(() => {
                        if (index === 1) {
                            id = $('#multiselect" . $data['id'] . "_to').val();
                            if (id == $idc) {
                                 $('#metademands_wizard_red" . $fields_link . "').html('*');
                                 $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                 //Special case Upload field
                                  sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                 " . Fieldoption::checkMandatoryFile($fields_link, $name) . "
                            }
                        } else if (index === 2) {
                            id = $('#multiselect" . $data['id'] . "').val();
                            if (id == $idc) {
                                $('#metademands_wizard_red" . $fields_link . "').html('');
                                  sessionStorage.setItem('hiddenlink$name', $fields_link);
                                " . Fieldoption::resetMandatoryFieldsByField($name) . "
                            }
                        }
                    }, 50);
                });";

                        $onchange .= "$('#multiselect" . $data["id"] . " option').on('dblclick', function() {
                            id = $('#multiselect" . $data['id'] . "').val();
                            setTimeout(() => {
                                if (id == $idc) {
                                    $('#metademands_wizard_red" . $fields_link . "').html('*');
                                     $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                     //Special case Upload field
                                  sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                 " . Fieldoption::checkMandatoryFile($fields_link, $name) . "
                                }
                            }, 50);
                            });";

                        $onchange .= "$('#multiselect" . $data["id"] . "_to option').on('dblclick', function() {
                            id = $('#multiselect" . $data['id'] . "_to').val();
                            setTimeout(() => {
                                if (id == $idc) {
                                    $('#metademands_wizard_red" . $fields_link . "').html('');
                                    sessionStorage.setItem('hiddenlink$name', $fields_link);
                                    " . Fieldoption::resetMandatoryFieldsByField($name) . "
                                }
                            }, 50);
                            });";
                    }
                }

                echo Html::scriptBlock(
                    '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
                );
            }
        }
    }

    public static function taskScript($data)
    {
        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        if (getItemForItemtype($data["item"])) {
            if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                $script = "";
                $script2 = "";
                $debug = (isset($_SESSION['glpi_use_mode'])
                && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
                if ($debug) {
                    $script = "console.log('taskScript-dropdownmultiple $id');";
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

                    $custom_value = $data['custom_values'] ?? [];
                    //            $custom_value = FieldParameter::_unserialize($data['custom_values']);
                    $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                    $script .= "var tohide = {};";
                    foreach ($check_values as $idc => $check_value) {
                        foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
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
                                            $val = $custom_value['name'];
                                            //Pas compris
                                            $script .= "if ($(value).attr('title') == '$val') {
                                        tohide[" . $tasks_id . "] = false;
                                    }";
                                        }
                                    }
                                }
                            } else {
                                $script .= "if ($(value).attr('title') == '" . $data["item"]::getFriendlyNameById(
                                    $tasks_id
                                ) . "') {
                                    tohide[" . $tasks_id . "] = false;
                                }";
                            }

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
                    }
                    $script .= "});";

                    foreach ($check_values as $idc => $check_value) {
                        foreach ($check_value['plugin_metademands_tasks_id'] as $tasks_id) {
                            //Initialize id default value
                            if (isset($data['custom_values'])
                                && is_array($data['custom_values'])
                                && count($data['custom_values']) > 0) {
                                $custom_values = $data['custom_values'];
                                foreach ($custom_values as $k => $custom_value) {
                                    if (isset($custom_value['is_default'])
                                        && $custom_value['is_default'] == 1) {
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
            } else {
                $script = "";
                $script2 = "";
                $debug = (isset($_SESSION['glpi_use_mode'])
                && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
                if ($debug) {
                    $script = "console.log('taskScript-dropdownmultiple $id');";
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

                    $script .= "$('#multiselect" . $data["id"] . "').on('change', function() {";
                    $script .= "var tohide = {};";
                    foreach ($check_values as $idc => $check_value) {
                        foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                            $script .= "if ($tasks_id in tohide) {
                            } else {
                                tohide[$tasks_id] = true;
                            }";
                            //
                            //                    $script .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {";
                            $script .= "if ($(this).val() == '$idc') {
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
                            //                    $script .= "});";
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
                    }
                    $script .= "});";

                    foreach ($check_values as $idc => $check_value) {
                        foreach ($check_value['plugin_metademands_tasks_id'] as $tasks_id) {
                            //Initialize id default value
                            if (isset($data['custom_values'])
                                && is_array($data['custom_values'])
                                && count($data['custom_values']) > 0) {
                                $custom_values = $data['custom_values'];
                                foreach ($custom_values as $k => $custom_value) {
                                    if (isset($custom_value['is_default']) && $custom_value['is_default'] == 1) {
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
        }
    }

    public static function fieldsHiddenScript($data)
    {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $onchange = "";
            $pre_onchange = "";
            $post_onchange = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $onchange = "console.log('fieldsHiddenScript-dropdownmultiple $id');";
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
                //Initialize id default value
                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {
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
                }

                //default hide of all hidden links
                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {
                        $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                    }
                }

                //Si la valeur est en session
                if (isset($data['value']) && is_array($data['value'])) {
                    $values = $data['value'];
                    foreach ($values as $value) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $value . "').trigger('change');";
                    }
                }

                $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                $onchange .= "var tohide = {};";
                $display = [];

                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {
                        //                    $onchange .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( keys, values ) {";
                        $onchange .= "$.each($(this).val(), function( keys, values ) {
                            if ($hidden_link in tohide) {
                            } else {
                                tohide[$hidden_link] = true;
                            }
                            if (values != 0 && (values == $idc || $idc == 0 )) {
                                tohide[$hidden_link] = false;
                            }";

                        $onchange .= "});";
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
                                $('[name =\"field['+key+']\"]').removeAttr('required');
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
                                $('[id-field =\"field'+key+'\"]').show();
                            }
                        });";
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
        } else {
            $onchange = "";
            $pre_onchange = "";
            $post_onchange = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $onchange = "console.log('fieldsHiddenScript-dropdownmultiple $id');";
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
                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {
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
                }

                //default hide of all hidden links
                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {
                        $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                    }
                }

                //Si la valeur est en session
                if (isset($data['value']) && is_array($data['value'])) {
                    $values = $data['value'];
                    foreach ($values as $value) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $value . "').trigger('change');";
                    }
                }

                $onchange .= "var tohide = {};";

                $onchange .= "$('#multiselect" . $data["id"] . "').on('change', function() {";
                $display = [];

                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {
                        //                    $onchange .= "if ($(this).val() == $idc || $idc == -1) {
                        //                            if ($hidden_link in tohide) {
                        //
                        //                            } else {
                        //                                tohide[$hidden_link] = true;
                        //                            }
                        //                            tohide[$hidden_link] = false;
                        //                        }";
                        //
                        //
                        //                    if (isset($data['value']) && is_array($data['value'])) {
                        //                        $values = $data['value'];
                        //                        foreach ($values as $value) {
                        //                            if ($idc == $value) {
                        //                                $display[] = $hidden_link;
                        //                            }
                        //                        }
                        //                    }
                        //
                        //                    $onchange .= "$.each( tohide, function( key, value ) {
                        //                        if (value == true) {
                        //
                        //                            $('[id-field =\"field'+key+'\"]').hide();
                        //                            sessionStorage.setItem('hiddenlink$name', key);
                        //                            " . Fieldoption::resetMandatoryFieldsByField($name) . "
                        //                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        //                        } else {
                        //                            $('[id-field =\"field'+key+'\"]').show();
                        //                            $('[name =\"field['+key+']\"]').attr('required', 'required');
                        //                        }
                        //                    });";

                        $onchange .=  "$('.centralCol').on('click', 'button', function () {
                    const index = $(this).index();
                    setTimeout(() => {
                        if (index === 1) {
                            id = $('#multiselect" . $data['id'] . "_to').val();
                            if (id == $idc) {
                                $('[id-field =\"field'+ $hidden_link +'\"]').show();
                            }
                        } else if (index === 2) {
                            id = $('#multiselect" . $data['id'] . "').val();
                            if (id == $idc) {
                                $('[id-field =\"field'+ $hidden_link +'\"]').hide();
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
                        $onchange .= "}
                        }
                    }, 50);
                });";

                        $onchange .=  "$('#multiselect" . $data["id"] . " option').on('dblclick', function() {
                            id = $('#multiselect" . $data['id'] . "').val();
                            setTimeout(() => {
                                if (id == $idc) {
                                    $('[id-field =\"field'+ $hidden_link +'\"]').show();
                                }
                            }, 50);
                            });";

                        $onchange .=  "$('#multiselect" . $data["id"] . "_to option').on('dblclick', function() {
                            id = $('#multiselect" . $data['id'] . "_to').val();
                            setTimeout(() => {
                                if (id == $idc) {
                                    $('[id-field =\"field'+ $hidden_link +'\"]').hide();
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
                        $onchange .= "}
                            }, 50);
                            });";
                    }
                }

                $onchange .= "});";
                if (is_array($display) && count($display) > 0) {
                    foreach ($display as $see) {
                        $pre_onchange .= "$('[id-field =\"field" . $see . "\"]').show();";
                        $pre_onchange .= Fieldoption::setMandatoryFieldsByField($id, $see);
                    }
                }

                echo Html::scriptBlock(
                    '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
                );
            }
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

            if (count($check_values) > 0) {
                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                $script .= "var tohide = {};";

                //by default - hide all
                $script2 .= Fieldoption::hideAllblockbyDefault($data);
                if (!isset($data['value'])) {
                    $script2 .= Fieldoption::emptyAllblockbyDefault($check_values);
                }

                //multiple value at each time
                $display = [];
                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_block'] as $hidden_block) {
                        $script .= "if ($hidden_block in tohide) {
                        } else {
                            tohide[$hidden_block] = true;
                        }";

                        //                    $script .= "$.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {";
                        $script .= "$.each($(this).val(), function( keys, values ) {
                        if ($hidden_block in tohide) {
                        } else {
                            tohide[$hidden_block] = true;
                        }
                        if (values != 0 && (values == $idc || $idc == 0 )) {
                            tohide[$hidden_block] = false;
                        }";

                        $script .= "});";
                        $script .= "$.each( tohide, function( key, value ) {
                    if (value == true) {
                       var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'none';
                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
                        $('[bloc-id =\"subbloc'+ key +'\"]').hide();
                        sessionStorage.setItem('hiddenbloc$name', key);
                        " . Fieldoption::setEmptyBlockFields($name) . "";
                        $hidden = Fieldoption::resetMandatoryBlockFields($name);
                        $script .= "$hidden";
                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $script .= "if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'none';
                                $('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                $('[bloc-id =\"subbloc" . $childs . "\"]').hide();";
                                    }
                                }
                            }
                        }
                        $script .= "} else {
                        var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'block';
                        $('[bloc-id =\"bloc'+ key +'\"]').show();
                        $('[bloc-id =\"subbloc'+ key +'\"]').show();
                        ";

                        $hidden = Fieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                        $script .= "$hidden";

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
                        $script .= "}
                });";

                        if (isset($data['value']) && is_array($data['value'])) {
                            $values = $data['value'];
                            foreach ($values as $value) {
                                if ($idc == $value) {
                                    $display[] = $hidden_block;
                                }
                            }
                        }
                    }
                }
                if (is_array($display) && count($display) > 0) {
                    foreach ($display as $see) {
                        $script2 .= "if (document.getElementById('ablock" . $see . "'))
                                document.getElementById('ablock" . $see . "').style.display = 'none';
                                $('[bloc-id =\"bloc" . $see . "\"]').show();
                                $('[bloc-id =\"subbloc" . $see . "\"]').show();";
                    }
                }
                $script .= "});";

                echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
            }
        } else {
            $script = "";
            $script2 = "";
            $debug = (isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
            if ($debug) {
                $script = "console.log('blocksHiddenScript-dropdownmultiple $id');";
            }

            if (count($check_values) > 0) {
                $script .= "$('#multiselect" . $data["id"] . "').on('change', function() {";

                //            $custom_value = FieldParameter::_unserialize($data['custom_values']);
                $script .= "var tohide = {};";

                //by default - hide all
                $script2 .= Fieldoption::hideAllblockbyDefault($data);
                if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $script2 .= Fieldoption::emptyAllblockbyDefault($check_values);
                }

                //multiple value at each time
                $display = [];
                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_block'] as $hidden_block) {
                        //                    $script .= "if ($(this).val() == $idc || $idc == -1) {
                        //                            if ($hidden_block in tohide) {
                        //
                        //                            } else {
                        //                                tohide[$hidden_block] = true;
                        //                            }
                        //                            tohide[$hidden_block] = false;
                        //                        }";
                        //
                        //                    $script .= "$.each( tohide, function( key, value ) {
                        //                    if (value == true) {
                        //                       var id = 'ablock'+ key;
                        //                        if (document.getElementById(id))
                        //                        document.getElementById(id).style.display = 'none';
                        //                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
                        //                        $('[bloc-id =\"subbloc'+ key +'\"]').hide();
                        //                        sessionStorage.setItem('hiddenbloc$name', key);
                        //                        " . Fieldoption::setEmptyBlockFields($name) . "";
                        //                    $hidden = Fieldoption::resetMandatoryBlockFields($name);
                        //                    $script .= "$hidden";
                        //                    if (is_array($childs_by_checkvalue)) {
                        //                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                        //                            if ($idc == $k) {
                        //                                foreach ($childs_blocks as $childs) {
                        //                                    $script .= "if (document.getElementById('ablock" . $childs . "'))
                        //                                document.getElementById('ablock" . $childs . "').style.display = 'none';
                        //                                $('[bloc-id =\"bloc" . $childs . "\"]').hide();
                        //                                $('[bloc-id =\"subbloc" . $childs . "\"]').hide();";
                        //                                }
                        //                            }
                        //                        }
                        //                    }
                        //                    $script .= "} else {
                        //                        var id = 'ablock'+ key;
                        //                        if (document.getElementById(id))
                        //                        document.getElementById(id).style.display = 'block';
                        //                        $('[bloc-id =\"bloc'+ key +'\"]').show();
                        //                        $('[bloc-id =\"subbloc'+ key +'\"]').show();
                        //                        ";
                        //
                        //                    $hidden = Fieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                        //
                        //                    $script .= "$hidden";
                        //                    $script .= "}
                        //                });";

                        $script .= "$('.centralCol').on('click', 'button', function () {
                    const index = $(this).index();
                    setTimeout(() => {
                        if (index === 1) {
                            id = $('#multiselect" . $data['id'] . "_to').val();
                            if (id == $idc) {
                                 var ida = 'ablock'+ $hidden_block;
                                 if (document.getElementById(ida))
                                    document.getElementById(ida).style.display = 'block';
                                 $('[bloc-id =\"bloc'+ $hidden_block +'\"]').show();
                                 $('[bloc-id =\"subbloc'+ $hidden_block +'\"]').show();
                            }
                        } else if (index === 2) {
                            id = $('#multiselect" . $data['id'] . "').val();
                            if (id == $idc) {
                                $('[bloc-id =\"bloc'+ $hidden_block +'\"]').hide();
                                $('[bloc-id =\"subbloc'+ $hidden_block +'\"]').hide();
                                sessionStorage.setItem('hiddenbloc$name', $hidden_block);
                            }
                        }
                    }, 50);
                });";

                        $script .= "$('#multiselect" . $data["id"] . " option').on('dblclick', function() {
                            id = $('#multiselect" . $data['id'] . "').val();
                            setTimeout(() => {
                                if (id == $idc) {
                                    var ida = 'ablock'+ $hidden_block;
                                    if (document.getElementById(ida))
                                        document.getElementById(ida).style.display = 'block';
                                    $('[bloc-id =\"bloc'+ $hidden_block +'\"]').show();
                                    $('[bloc-id =\"subbloc'+ $hidden_block +'\"]').show();
                                }
                            }, 50);
                            });";

                        $script .= "$('#multiselect" . $data["id"] . "_to option').on('dblclick', function() {
                            id = $('#multiselect" . $data['id'] . "_to').val();
                            setTimeout(() => {
                                if (id == $idc) {
                                    $('[bloc-id =\"bloc'+ $hidden_block +'\"]').hide();
                                    $('[bloc-id =\"subbloc'+ $hidden_block +'\"]').hide();
                                    sessionStorage.setItem('hiddenbloc$name', $hidden_block);
                                }
                            }, 50);
                            });";

                        if (isset($data['value']) && is_array($data['value'])) {
                            $values = $data['value'];
                            foreach ($values as $value) {
                                if ($idc == $value) {
                                    $display[] = $hidden_block;
                                }
                            }
                        }
                    }
                }
                if (is_array($display) && count($display) > 0) {
                    foreach ($display as $see) {
                        $script2 .= "if (document.getElementById('ablock" . $see . "'))
                                document.getElementById('ablock" . $see . "').style.display = 'block';
                                $('[bloc-id =\"bloc" . $see . "\"]').show();
                                $('[bloc-id =\"subbloc" . $see . "\"]').show();";
                    }
                }
                $script .= "});";

                echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
            }

            //            $script3 = "";
            //            if (count($check_values) > 0) {
            //                $script3 .= "$('#multiselectfield" . $data["id"] . "_to').on('change', function() {";
            //
            //                $script3 .= "var tohide = {};";
            //
            //                foreach ($check_values as $idc => $check_value) {
            //                    $hidden_block = $check_value['hidden_block'];
            //
            //
            //                    $script3 .= "if ($(this).val() == $idc || $idc == -1) {
            //                            if ($hidden_block in tohide) {
            //
            //                            } else {
            //                                tohide[$hidden_block] = true;
            //                            }
            //                            tohide[$hidden_block] = false;
            //                        }";
            //
            //                    $script3 .= "$.each( tohide, function( key, value ) {
            //                    if (value == true) {
            //                       var id = 'ablock'+ key;
            //                        if (document.getElementById(id))
            //                        document.getElementById(id).style.display = 'none';
            //                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
            //                        $('[bloc-id =\"subbloc'+ key +'\"]').hide();
            //                        sessionStorage.setItem('hiddenbloc$name', key);
            //                        " . Fieldoption::setEmptyBlockFields($name) . "";
            //                    $hidden = Fieldoption::resetMandatoryBlockFields($name);
            //                    $script3 .= "$hidden";
            //                    if (is_array($childs_by_checkvalue)) {
            //                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
            //                            if ($idc == $k) {
            //                                foreach ($childs_blocks as $childs) {
            //                                    $script .= "if (document.getElementById('ablock" . $childs . "'))
            //                                document.getElementById('ablock" . $childs . "').style.display = 'none';
            //                                $('[bloc-id =\"bloc" . $childs . "\"]').hide();
            //                                $('[bloc-id =\"subbloc" . $childs . "\"]').hide();";
            //                                }
            //                            }
            //                        }
            //                    }
            //                    $script3 .= "}
            //                });";
            //
            //                }
            //
            //                $script3 .= "});";
            //                echo Html::scriptBlock('$(document).ready(function() {' . $script3 . '});');
            //            }
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
                    $title = $custom_values[$idc]['name'];
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
                        });";

            echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
        } else {
            $script = "$('#multiselect" . $data["id"] . "').on('change', function() {";

            if (isset($data['options'][$idc]['hidden_link'])
                && !empty($data['options'][$idc]['hidden_link'])) {
                $checkbox_id = $data['options'][$idc]['checkbox_id'];
                $checkbox_value = $data['options'][$idc]['checkbox_value'];

                //                $script .= "$.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {";

                if (isset($checkbox_id) && $checkbox_id > 0) {
                    $script .= "
                           if($(this).val() == '$idc'){
                              document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                           }
                        ";
                }
                $script .= "});";
            }

            echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
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

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        } else {
            $onchange .= "$('#multiselect" . $data["id"] . "').on('change', function() {";
        }
        $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
        $onchange .= "});";

        echo Html::scriptBlock(
            '$(document).ready(function() {' . $onchange . '});'
        );
    }

    public static function getFieldValue($field, $lang)
    {
        if (!empty($field['custom_values'])
            && $field['item'] != 'User'
            && $field['item'] != 'Location'
            && $field['item'] != 'Group'
            && $field['item'] != 'Appliance') {
            if ($field['item'] != "other") {
                $custom_values = FieldParameter::_unserialize($field['custom_values']);
                foreach ($custom_values as $k => $val) {
                    $custom_values[$k] = $field["item"]::getFriendlyNameById($k);
                }
                $field['value'] = FieldParameter::_unserialize($field['value']);
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
                    if (!empty($ret = Field::displayField($field["id"], "custom" . $k, $lang))) {
                        $custom_values[$k] = $ret;
                    }
                }
                $field['value'] = FieldParameter::_unserialize($field['value']);
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
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if (!empty($field['custom_values'])
            && $field['item'] != 'User'
            && $field['item'] != 'Location'
            && $field['item'] != 'Group'
            && $field['item'] != 'Appliance' && $field['value'] > 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } elseif (($field['item'] == 'Location' || $field['item'] == 'Group' || $field['item'] == 'Appliance')
            && $field['value'] > 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
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
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $dataItems;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        }

        return $result;
    }
}
