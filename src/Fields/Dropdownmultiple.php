<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\FieldCustomvalue;
use Group;
use Group_User;
use Html;
use Location;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\MetademandTask;
use Session;
use User;

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
                    if (count($users) > 0) {
                        $criteria['WHERE'][getTableForItemType($data['item']) . '.id'] = $users;
                    }
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
                    $opt,
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
                        $value,
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
                        ],
                    );
                }
            }
        } else {
            if (getItemForItemtype($data["item"])) {
                $item = new $data['item']();
                $criteria = [
                    'FROM'  => getTableForItemType($data['item']),
                    'WHERE' => [],
                ];

                if ($item->maybeDeleted()) {
                    $criteria['WHERE'][getTableForItemType($data['item']) . '.is_deleted'] = 0;
                }

                if ($item->maybeTemplate()) {
                    $crit[getTableForItemType($data['item']) . '.is_template'] = 0;
                    $criteria['WHERE'] = $criteria['WHERE'] + $crit;
                }

                $crit = getEntitiesRestrictCriteria(
                    getTableForItemType($data['item']),
                    '',
                    '',
                    $item->maybeRecursive(),
                );

                $criteria['WHERE'] = $criteria['WHERE'] + $crit;

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
                        if (is_int($custom_value) && $app->getFromDB($custom_value)) {
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
                        ],
                    );
                }
            }
        }

        echo $field;
    }

    public static function loadMultiselectDiv($namefield, $plugin_metademands_metademands_id, $id, $item, $required, $list, $value)
    {

        $name = $namefield . "[" . $id . "][]";

        // Left column: available options (those not already picked). User-supplied labels are
        // passed raw and auto-escaped by Twig {{ }}.
        $left_options = [];
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $k => $val) {
                if (!in_array($k, $value)) {
                    if ($item == 'other') {
                        $left_options[] = ['value' => $k, 'text' => $val['name']];
                    } else {
                        $left_options[] = ['value' => $k, 'text' => $val];
                    }
                }
            }
        }

        if (isset($value) && is_array($value) && count($value) > 0) {
            $required = "";
        }

        // Right column: selected options. getUserName() and Dropdown::getDropdownName()
        // return raw database columns in GLPI 11, so every label is escaped by Twig.
        $right_options = [];
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $k => $val) {
                if ($item == 'other') {
                    if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$id])) {
                        $right_options[] = ['value' => $val, 'text' => $list[$val]['name'], 'selected' => false];
                    } else {
                        $right_options[] = ['value' => $k, 'text' => $val, 'selected' => false];
                    }
                } elseif ($item == User::getType()) {
                    $right_options[] = ['value' => $val, 'text' => getUserName($val, 0, true), 'selected' => true];
                } else {
                    $right_options[] = [
                        'value'    => $val,
                        'text'     => \Dropdown::getDropdownName(getTableForItemType($item), $val),
                        'selected' => true,
                    ];
                }
            }
        }

        return TemplateRenderer::getInstance()->render('@metademands/fields/field_multiselect.html.twig', [
            'id'            => $id,
            'name'          => $name,
            'is_required'   => $required !== '',
            'left_options'  => $left_options,
            'right_options' => $right_options,
        ]);
    }

    public static function loadMultiselectScript($namefield, $id)
    {
        $script = Html::scriptBlock(
            '$(document).ready(function() {
                            var tohide = {};
                            $("#multiselect' . $id . '").multiselect({
                                      search: {
                                          left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __(
                "Search",
            ) . '...\" />",
                                          right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __(
                "Search",
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
                            });',
        );
        return $script;
    }

    public static function showFieldCustomValues($params)
    {
        $custom_values = $params['custom_values'];

        $data = ['mode' => 'empty'];

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

                $found_items = $item->find($criteria, ["name ASC"]);

                $target = FieldCustomvalue::getFormURL();

                $hidden_fields = [];
                if (isset($params['plugin_metademands_fields_id'])) {
                    $hidden_fields = [
                        'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                        'type'                         => $params["type"],
                        'item'                         => $params["item"],
                    ];
                }

                $script_html = Html::scriptBlock("$(function () {
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

                $items = [];
                foreach ($found_items as $key => $v) {
                    $items[] = [
                        'key'     => $key,
                        'name'    => (string) $v["name"],
                        'default' => $default_values[$key] ?? 0,
                        'checked' => (isset($custom_values[$key]) && $custom_values[$key] != 0),
                    ];
                }

                $data = [
                    'mode'                   => 'objects',
                    'form_target'            => $target,
                    'hidden_fields'          => $hidden_fields,
                    'script_html'            => $script_html,
                    'type_name'              => FieldCustomvalue::getTypeName(2),
                    'default_value_label'    => _n('Default value', 'Default values', 1, 'metademands'),
                    'display_dropdown_label' => __('Display value in the dropdown', 'metademands'),
                    'select_all_label'       => __('Select all', 'metademands'),
                    'unselect_all_label'     => __('Unselect all', 'metademands'),
                    'items'                  => $items,
                ];
            } else {
                if ($params['item'] != 'Location') {
                    $data = [
                        'mode' => 'list',
                        'list' => FieldCustomvalue::getListContext($params, false, false, false, 0),
                    ];
                }
            }
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_customvalue_dropdownmultiple.html.twig',
            $data,
        );
    }

    public static function showFieldParameters($params): string
    {
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::DOUBLE_COLUMN_DISPLAY] = __("Double column display", "metademands");

        $is_user = $params["item"] == 'User';
        $user_group = 0;
        $values = [];
        $informations = [];

        if ($is_user) {
            $custom_values = FieldParameter::_unserialize($params['custom_values']);
            $user_group = $custom_values['user_group'] ?? 0;

            $decode = json_decode($params['informations_to_display']);
            $values = empty($decode) ? ['full_name'] : $decode;
            $informations = [
                "full_name" => __('Complete name'),
                "realname"  => __('Surname'),
                "firstname" => __('First name'),
                "name"      => __('Login'),
                "email"     => _n('Email', 'Emails', 1),
            ];
        }

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_dropdownmultiple.html.twig',
            [
                'display_type'                        => $params['display_type'],
                'display_types'                       => $disp,
                'is_user'                             => $is_user,
                'user_group'                          => $user_group,
                'default_use_id_requester'            => $params['default_use_id_requester'],
                'default_use_id_requester_supervisor' => $params['default_use_id_requester_supervisor'],
                'informations_to_display'             => $values,
                'informations'                        => $informations,
            ],
        );
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        ob_start();
        FieldOption::showRegexDropdown($params['check_type_value'], $params['ID']);
        $regex_html = ob_get_clean();

        ob_start();
        switch ($params['check_type_value']) {
            case 1:
                self::showValueToCheck($fieldoption, $params);
                break;
            case 2:
                FieldOption::showRegexInput($params['check_value_regex']);
                break;
            default:
                echo '';
        }
        $cell_content = ob_get_clean();

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => true,
            'with_tech_group' => true,
            'content'         => $cell_content,
        ];

        $params['use_richtext'] = 0;

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => '',
                'label'             => __('Value to check', 'metademands'),
                'label_colspan'     => 1,
                'regex_html'        => $regex_html,
                'valuetocheck'      => $valuetocheck,
                'link_html'         => $link_html,
            ],
        );
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
                        ['value' => $params['check_value'], 'used' => $already_used],
                    );
                }
                break;
        }
    }

    public static function showParamsValueToCheck($params): string
    {
        $value = '';
        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            $value .= __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'User':
                    $value .= getUserName($params['check_value'], 0, true);
                    break;
                case 'Location':
                    $value .= \Dropdown::getDropdownName("glpi_locations", $params['check_value']);
                    break;
                case 'Group':
                    $value .= \Dropdown::getDropdownName("glpi_groups", $params['check_value']);
                    break;
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        $value .= \Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
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
                            $value .= $elements[$params['check_value']];
                        } else {
                            $elements = [];
                            foreach ($params['custom_values'] as $key => $val) {
                                $elements[$val['id']] = $val['name'];
                            }
                            $value .= $elements[$params['check_value']] ?? "";
                        }
                    }
                    break;
            }
        }
        return $value;
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (empty($value)) {
            $value = [];
        }
        if ($check_value == Field::$not_null && is_array($value) && count($value) == 0) {
            return false;
        }
        return true;
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
            && ($fields['value'] === null || $fields['value'] === '')) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data)
    {
        $values = isset($data['value']) && is_array($data['value']) ? array_values($data['value']) : [];
        if ($data['display_type'] != self::CLASSIC_DISPLAY) {
            // The values are rendered on the right side of the multiselect
            $options = count($values) > 0 ? ['restore' => ['refresh' => true]] : [];
            FieldOption::displayMandatoryTrigger($data, ['id' => 'multiselect' . $data['id'] . '_to'], 'listed', $options);
            return;
        }
        $options = [
            'any'           => [0],
            // The regex of the checked values was injected as a JS literal
            'regex_literal' => true,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['val' => array_map('strval', $values)];
        }
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'options', $options);
    }

    public static function taskScript($data)
    {
        if (!getItemForItemtype($data['item'])) {
            return;
        }
        $options = ['defaults' => MetademandTask::getDefaultCustomValues($data['custom_values'] ?? [])];
        if ($data['display_type'] != self::CLASSIC_DISPLAY) {
            MetademandTask::displayTaskTrigger($data, ['id' => 'multiselect' . $data['id']], 'values', $options);
            return;
        }
        // The task is used when an option with the label of the checked value is selected
        $labels = [];
        foreach (array_keys($data['options'] ?? []) as $idc) {
            if ($data['item'] == 'other') {
                $labels[$idc] = $data['custom_values'][$idc]['name'] ?? '';
            } else {
                $labels[$idc] = $data['item']::getFriendlyNameById($idc);
            }
        }
        $options['labels'] = $labels;
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'multiple', $options);
    }

    public static function fieldsHiddenScript($data)
    {
        $values = isset($data['value']) && is_array($data['value']) ? array_values($data['value']) : [];
        if ($data['display_type'] != self::CLASSIC_DISPLAY) {
            // The values are rendered on the right side of the multiselect
            FieldOption::displayHiddenTrigger($data, ['id' => 'multiselect' . $data['id'] . '_to'], 'listed', ['current' => $values]);
            return;
        }
        $options = [
            'any'           => [0],
            // The regex of the checked values was injected as a JS literal
            'regex_literal' => true,
            'current'       => $values,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['val' => array_map('strval', $values)];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'options', $options);
    }

    public static function blocksHiddenScript($data)
    {
        $values = isset($data['value']) && is_array($data['value']) ? array_values($data['value']) : [];
        if ($data['display_type'] != self::CLASSIC_DISPLAY) {
            // The values are rendered on the right side of the multiselect
            FieldOption::displayBlockTrigger($data, ['id' => 'multiselect' . $data['id'] . '_to'], 'listed', []);
            return;
        }
        $options = [
            'any'           => [0],
            // The regex of the checked values was injected as a JS literal
            'regex_literal' => true,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['val' => array_map('strval', $values)];
        }
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'options', $options);
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
                    $title = json_encode($custom_values[$idc]['name']);
                    $script .= "if ($(value).attr('title') == $title) {
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
        Condition::displayTrigger(
            $data,
            $metaparams,
            $data['display_type'] == self::CLASSIC_DISPLAY
                ? ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix']
                : ['id' => 'multiselect' . $data['id']],
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
            // Plain text: the ticket content and the basket summary escape it
            return implode(', ', $parseValue);
        }
    }

    /**
     * Informations of a user to show in the ticket content, in display order, as plain text.
     *
     * @param User          $item        user loaded from the database
     * @param array<string> $information keys chosen in the field parameters
     *
     * @return array<int, string>
     */
    public static function getUserInformations(User $item, array $information): array
    {
        $texts = [];
        if (in_array('full_name', $information)) {
            $texts[] = (string) User::getFriendlyNameById($item->getID());
        }
        if (in_array('realname', $information)) {
            $texts[] = (string) $item->fields["realname"];
        }
        if (in_array('firstname', $information)) {
            $texts[] = (string) $item->fields["firstname"];
        }
        if (in_array('name', $information)) {
            $texts[] = (string) $item->fields["name"];
        }
        if (in_array('email', $information)) {
            $texts[] = (string) $item->getDefaultEmail();
        }

        return $texts;
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $title_style,
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
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field, $lang),
                    'colspan' => $colspan,
                ]],
            );
        } elseif (($field['item'] == 'Location' || $field['item'] == 'Group' || $field['item'] == 'Appliance')
            && $field['value'] > 0) {
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field, $lang),
                    'colspan' => $colspan,
                ]],
            );
        } elseif ($field['item'] == 'User' && ($field['value'] > 0
                || (is_array($field['value']) && count($field['value']) > 0))) {
            $information = json_decode($field['informations_to_display']);

            // legacy support
            if (empty($information)) {
                $information = ['full_name'];
            }

            $rows = [];
            $item = new $field["item"]();
            if (is_array($field['value'])) {
                foreach ($field['value'] as $value) {
                    if ($item->getFromDB($value)) {
                        $rows[] = self::getUserInformations($item, $information);
                    }
                }
            }
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'rows'    => $rows,
                    'colspan' => $colspan,
                ]],
            );
        }

        return $result;
    }
}
