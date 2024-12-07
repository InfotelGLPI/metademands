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
 * PluginMetademandsDropdownobject Class
 *
 **/
class PluginMetademandsDropdownobject extends CommonDBTM
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
        return __('Glpi Object', 'metademands');
    }

    static function showWizardField($data, $namefield, $value,  $on_order, $itilcategories_id) {

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        switch ($data['item']) {
            case 'User':
                $userrand = mt_rand();
                $field    = "";

                if ($on_order == false) {
                    $paramstooltip
                        = ['value'          => '__VALUE__',
                        'id_fielduser'   => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update'    => "tooltip_user" . $data['id'],
                        'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                        'moreparams'   => $paramstooltip];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "tooltip_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                        $paramstooltip,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                if (!isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data["id"]])) {
                    $paramsloc
                        = ['value' => '__VALUE__',
                        'id_fielduser' => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update' => "location_user" . $data['id'],
                        'url' => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ulocationUpdate.php",
                        'moreparams' => $paramsloc];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "location_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/ulocationUpdate.php",
                        $paramsloc,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                if (!isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data["id"]])) {
                    $paramstit
                        = ['value' => '__VALUE__',
                        'id_fielduser' => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update' => "title_user" . $data['id'],
                        'url' => PLUGIN_METADEMANDS_WEBDIR . "/ajax/utitleUpdate.php",
                        'moreparams' => $paramstit];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "title_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/utitleUpdate.php",
                        $paramstit,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                if (!isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data["id"]])) {
                    $paramscat
                        = ['value' => '__VALUE__',
                        'id_fielduser' => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update' => "category_user" . $data['id'],
                        'url' => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ucategoryUpdate.php",
                        'moreparams' => $paramscat];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "category_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/ucategoryUpdate.php",
                        $paramscat,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                if (!isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data["id"]])) {
                    $paramsgroup
                        = ['value' => '__VALUE__',
                        'id_fielduser' => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update' => "group_user" . $data['id'],
                        'url' => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ugroupUpdate.php",
                        'moreparams' => $paramsgroup];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "group_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/ugroupUpdate.php",
                        $paramsgroup,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";

                }
                $paramsentity
                    = ['value'          => '__VALUE__',
                    'id_fielduser'   => $data['id'],
                    'readonly' => $data['readonly'],
                    'metademands_id' => $data['plugin_metademands_metademands_id']];

                $toupdate[] = ['value_fieldname'
                => 'value',
                    'id_fielduser' => $data['id'],
                    'to_update'    => "entity_user" . $data['id'],
                    'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/uentityUpdate.php",
                    'moreparams'   => $paramsentity];

                $field .= "<script type='text/javascript'>";
                $field .= "$(function() {";
                Ajax::updateItemJsCode(
                    "entity_user" . $data['id'],
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/uentityUpdate.php",
                    $paramsentity,
                    $namefield . "[" . $data['id'] . "]",
                    false
                );
                $field .= "});</script>";

                if (!isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data["id"]])) {
                    $paramsdev
                        = ['value' => '__VALUE__',
                        'id_fielduser' => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update' => "mydevices_user" . $data['id'],
                        'url' => PLUGIN_METADEMANDS_WEBDIR . "/ajax/umydevicesUpdate.php",
                        'moreparams' => $paramsdev];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "mydevices_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/umydevicesUpdate.php",
                        $paramsdev,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                if (!isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data["id"]])) {
                    $paramsman
                        = ['value' => '__VALUE__',
                        'id_fielduser' => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update' => "manager_user" . $data['id'],
                        'url' => PLUGIN_METADEMANDS_WEBDIR . "/ajax/umanagerUpdate.php",
                        'moreparams' => $paramsman];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "manager_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/umanagerUpdate.php",
                        $paramsman,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                if (empty($value)) {
                    $value = ($data['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                }
                if (empty($value)) {
                    $user = new User();
                    $user->getFromDB(Session::getLoginUserID());
                    $value = ($data['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                }

                $right = "all";

                if (!empty($data['custom'])) {
                    $options = PluginMetademandsFieldParameter::_unserialize($data['custom']);
                    if (isset($options['user_group']) && $options['user_group'] == 1) {
                        $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                        $group_user_data = Group_User::getUserGroups(Session::getLoginUserID(), $condition);

                        $requester_groups = [];
                        foreach ($group_user_data as $groups) {
                            $requester_groups[] = $groups['id'];
                        }
                        if (count($requester_groups) > 0) {
                            $right = "groups";
                        }
                    }
                }

                $opt = ['name' => $namefield . "[" . $data['id'] . "]",
                    'entity' => $_SESSION['glpiactiveentities'],
                    'right' => $right,
                    'rand' => $userrand,
                    'value' => $value,
                    'display' => false,
                    'toupdate' => $toupdate,
                    'readonly' => $data['readonly'] ?? false,
                ];
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                if ($data['readonly'] == 1) {
                    $field .= Html::hidden($namefield . "[" . $data['id'] . "]", ['value' => $value]);
                }

                if ($data['link_to_user'] > 0) {

                    echo "<div id='manager_user" . $data['link_to_user'] . "' class=\"input-group\">";
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['users_id_supervisor'] = $value;
                    $fieldUser             = new PluginMetademandsField();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {
                        $_POST['value']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['value'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['value'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    if ($data['is_mandatory'] == 1) {
                        $_POST['is_mandatory'] = 1;
                    }
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/umanagerUpdate.php");
                    echo "</div>";
                } else {
                    echo User::dropdown($opt);
                }
                $relatedTextFields = new PluginMetademandsField();
                $relatedTextFields = $relatedTextFields->find([
                    'plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                    'type' => ['text', 'email', 'tel'],
                ]);
                $field_parameter = new PluginMetademandsFieldParameter();


                if (count($relatedTextFields)) {
                    $updateJs = '';

                    foreach ($relatedTextFields as $textField) {
                        if ($fields = $field_parameter->find(
                            ["plugin_metademands_fields_id" => $textField['id'],
                                'link_to_user' => $data['id']]
                        )) {
                            foreach ($fields as $f) {
                                if (!empty($f['used_by_ticket'])) {
                                    $updateJs .= "let field{$textField['id']} = $(\"[id-field='field{$textField['id']}'] input\");
                        field{$textField['id']}.val(response[{$f['used_by_ticket']}] ?? '');
                        field{$textField['id']}.trigger('input');
                        ";

                                }
                            }
                        }
                    }
                    echo "<script type='text/javascript'>
                        $(function() {
                            $(\"[id-field='field{$data['id']}'] select\").on('change', function(e) {
                                 $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/uTextFieldUpdate.php',
                                     data: { 
                                         id : $(this).val()
                                     },
                                  success: function(response){
                                       response = JSON.parse(response);
                                       $updateJs
                                    },
                                });
                            })
                        })
                    </script>";
                }
                break;
            case 'Group':
                $field = "";
                $cond  = [];
                $_POST['field'] = $namefield . "[" . $data['id'] . "]";

                if ($data['link_to_user'] > 0) {

                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['link_to_user']])) {
                        $_POST['value']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['value'])) {
                            $_POST['value'] = 0;
                        }
                    }

                    echo "<div id='group_user" . $data['link_to_user'] . "' class=\"input-group\">";
                    $_POST['groups_id'] = $value;
//                    $fieldUser          = new PluginMetademandsField();
//                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
//                        'type' => "dropdown_object",
//                        'item' => User::getType()]);

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    $_POST['is_mandatory'] = $data['is_mandatory'] ?? 0;
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/ugroupUpdate.php");
                    echo "</div>";
                } else {
                    $name = $namefield . "[" . $data['id'] . "]";

//                    if (!empty($data['custom_values'])) {
//                        $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
//                            && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
//
//                        if ($_POST['value'] > 0) {
//                            $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
//                            $group_user_data = Group_User::getUserGroups($_POST['value'], $condition);
//
//                            $requester_groups = [];
//                            foreach ($group_user_data as $groups) {
//                                $requester_groups[] = $groups['id'];
//                            }
//                            $options = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
//
//                            foreach ($options as $type_group => $values) {
//                                if ($type_group != 'user_group') {
//                                    $cond[$type_group] = $values;
//                                } else {
//                                    if (count($requester_groups) > 0) {
//                                        $cond["glpi_groups.id"] = $requester_groups;
//                                    }
//                                }
//                            }
//                            unset($cond['user_group']);
//                        }
//                    }
                    $val_group = (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])
                        && !is_array($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) ? $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] : 0;

                    $opt = ['name'      => $name,
                        'entity'    => $_SESSION['glpiactiveentities'],
                        'value'     => $val_group,
                        'condition' => $cond,
                        'display'   => false];
                    if ($data['is_mandatory'] == 1) {
                        $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }

                    $field .= Group::dropdown($opt);
                }


                break;

            default:
                $cond = [];
                $field = "";

                if (!empty($data['custom_values']) && $data['item'] == 'Group') {
                    $options = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
                    foreach ($options as $k => $val) {
                        if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                            $options[$k] = $ret;
                        }
                    }
                    foreach ($options as $type_group => $val) {
                        $cond[$type_group] = $val;
                    }
                }
                $opt = ['value'     => $value,
                    'entity'    => $_SESSION['glpiactiveentities'],
                    'name'      => $namefield . "[" . $data['id'] . "]",
                    //                          'readonly'  => true,
                    'condition' => $cond,
                    'display'   => false];

                if (isset($data['readonly']) && $data['readonly'] == 1 && $data['item'] == "Entity") {
                    $opt['readonly'] = true;
                    if ($data['link_to_user'] == 0) {
                        $field .= Html::hidden($namefield . "[" . $data['id'] . "]", ['value' => $value]);
                    }
                }
                if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                if (!($item = getItemForItemtype($data['item']))) {
                    break;
                }
                if ($data['item'] == "Entity") {
                    if ($data['link_to_user'] > 0) {
                        echo "<div id='entity_user" . $data['link_to_user'] . "' class=\"input-group\">";
                        $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                        $_POST['entities_id'] = $value;
                        $fieldUser             = new PluginMetademandsField();
                        $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                            'type' => "dropdown_object",
                            'item' => User::getType()]);

                        $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                            && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                        $_POST['id_fielduser'] = $data['link_to_user'];
                        $_POST['fields_id']    = $data['id'];
                        $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                        $_POST['readonly'] = $data['readonly'];
                        include(PLUGIN_METADEMANDS_DIR . "/ajax/uentityUpdate.php");
                        echo "</div>";
                    } else {
                        $options['name']    = $namefield . "[" . $data['id'] . "]";
                        $options['display'] = false;
                        if ($data['is_mandatory'] == 1) {
                            $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                        }
                        //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                        $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                        $field            .= Entity::dropdown($options);
                    }
                } else {
                    if ($data['item'] == "PluginResourcesResource") {
                        $opt['showHabilitations'] = true;
                    }
                    $container_class = new $data['item']();
                    $field           = "";
                    $field           .= $container_class::dropdown($opt);
                }
                break;
        }

        echo $field;
    }

    static function showFieldCustomValues($values) {

    }

    static function showFieldParameters($params)
    {

        if ($params['item'] == 'User') {

            $custom_values = PluginMetademandsFieldParameter::_unserialize($params['custom_values']);
            $user_group = $custom_values['user_group'] ?? 0;


            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Link this to a user field', 'metademands');
            echo "</td>";

            echo "<td>";
            $arrayAvailable[0] = Dropdown::EMPTY_VALUE;
            $field = new PluginMetademandsField();
            $fields = $field->find([
                "plugin_metademands_metademands_id" => $params['plugin_metademands_metademands_id'],
                'type' => "dropdown_object",
                "item" => User::getType()
            ]);
            foreach ($fields as $f) {
                $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
            }
            Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $params['link_to_user']]);
            echo "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Only users of my groups', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('user_group', $user_group);
            echo "</td>";

            if ($params['object_to_create'] == 'Ticket') {
                echo "<td>";
                echo __('Use this field for child ticket field', 'metademands');
                echo "</td>";
                echo "<td>";
                Dropdown::showYesNo('used_by_child', $params['used_by_child']);
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
            }
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
            echo __('Read-Only', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('readonly', ($params['readonly']));
            echo "</td>";

            echo "<td>";
            echo __("Informations to display in ticket and PDF", "metademands");
            echo "</td>";
            echo "<td>";
            $decode = "";
            if (!is_array($params['informations_to_display'])) {
                $decode = json_decode($params['informations_to_display']);
            }
            $values = empty($decode) ? ['full_name'] : $decode;
            $informations["full_name"]         = __('Complete name');
            $informations["realname"]          = __('Surname');
            $informations["firstname"]         = __('First name');
            $informations["name"]              = __('Login');
            //                  $informations["group"]             = Group::getTypeName(1);
            $informations["email"] = _n('Email', 'Emails', 1);
            echo Dropdown::showFromArray('informations_to_display', $informations, [
                'values'   => $values,
                'display'  => false,
                'multiple' => true
            ]);
            echo "</tr>";

        } else if ($params["item"] == "Group") {

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Link this to a user field', 'metademands');
            echo "</td>";
            echo "<td>";

            $arrayAvailable[0] = Dropdown::EMPTY_VALUE;
            $field = new PluginMetademandsField();
            $fields = $field->find([
                "plugin_metademands_metademands_id" => $params['plugin_metademands_metademands_id'],
                'type' => "dropdown_object",
                "item" => User::getType()
            ]);
            foreach ($fields as $f) {
                $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
            }
            Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $params['link_to_user']]);
            echo "</td>";

            if ($params['object_to_create'] == 'Ticket') {
                echo "<td>";
                echo __('Use this field for child ticket field', 'metademands');
                echo "</td>";
                echo "<td>";
                Dropdown::showYesNo('used_by_child', $params['used_by_child']);
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
            }
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            $custom_values = PluginMetademandsFieldParameter::_unserialize($params['custom_values']);
            $is_assign = $custom_values['is_assign'] ?? 0;
            $is_watcher = $custom_values['is_watcher'] ?? 0;
            $is_requester = $custom_values['is_requester'] ?? 0;
            $user_group = $custom_values['user_group'] ?? 0;
            echo "<td>";
            echo __('Requester');
            echo "</td>";
            echo "<td>";
            // Assigned group
            Dropdown::showYesNo('is_requester', $is_requester);
            echo "</td>";
            echo "<td>";
            echo __('Watcher');
            echo "</td>";
            echo "<td>";
            // Watcher group
            Dropdown::showYesNo('is_watcher', $is_watcher);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Assigned');
            echo "</td>";
            echo "<td>";
            // Requester group
            Dropdown::showYesNo('is_assign', $is_assign);
            echo "</td>";

            echo "<td>";
            echo __('My groups');
            echo "</td>";
            echo "<td>";
            // user_group
            Dropdown::showYesNo('user_group', $user_group);
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
                User::dropdown(['name' => $name,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'right' => 'all',
                    'rand' => $userrand,
                    'value' => $params['check_value'],
                    'display' => true,
                    'used' => $already_used
                ]);
                break;
            case 'Group':
                $name = "check_value";
                $cond = [];
                if (!empty($params['custom_values'])) {
                    $options = PluginMetademandsFieldParameter::_unserialize($params['custom_values']);
                    foreach ($options as $type_group => $values) {
                        $cond[$type_group] = $values;
                    }
                }
                Group::dropdown(['name' => $name,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'value' => $params['check_value'],
                    //                                            'readonly'  => true,
                    'condition' => $cond,
                    'display' => true,
                    'used' => $already_used
                ]);
                break;
            default:
                $dbu = new DbUtils();
                if ($item = $dbu->getItemForItemtype($params["item"])) {
                    //               if ($params['value'] == 'group') {
                    //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                    //               } else {
                    $name = "check_value";
                    //               }
                    $params['item']::Dropdown(["name" => $name,
                        "value" => $params['check_value'], 'used' => $already_used]);
                } else {
                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                        $elements[-1] = __('Not null value', 'metademands');
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            if ($key != 0) {
                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                            }
                        }
                    } else {
                        $elements[-1] = __('Not null value', 'metademands');
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
        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            echo __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'User':
                    echo getUserName($params['check_value'], 0, true);
                    break;
                case 'Group':
                    echo Dropdown::getDropdownName('glpi_groups', $params['check_value']);
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
    }

    static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } else if ($check_value != $value
            && ($check_value != PluginMetademandsField::$not_null && $check_value != 0)) {
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

    static function fieldsLinkScript($data, $idc, $rand) {

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
            $script = "console.log('taskScript-dropdownobject $id');";
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

        $name = "field[" . $data["id"] . "]";
        $script .= "$('[name=\"$name\"]').change(function() {";
        $script .= "var tohide = {};";
        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];


            $script .= "if ($tasks_id in tohide) {
                        } else {
                            tohide[$tasks_id] = true;
                        }
                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                            tohide[$tasks_id] = false;
                        }";

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
            if (is_array(PluginMetademandsFieldParameter::_unserialize($data['default']))) {
                $default_values = PluginMetademandsFieldParameter::_unserialize($data['default']);

                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
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

    static function fieldsHiddenScript($data) {

        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-dropdownobject $id');";
        }

        //Initialize default value - force change after onchange fonction
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            if (isset($data['default_values'])
                && is_array(PluginMetademandsFieldParameter::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsFieldParameter::_unserialize($data['default_values']);

                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $post_onchange .= "$('[name=\"field[" . $id . "]\"]').prop('checked', true).trigger('change');";
                        }
                    }
                }
            }
        }

        //default hide of all hidden links
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
        }


        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    if ($fieldSession > 0) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('$fieldSession').trigger('change');";
                    }
                }
            }
        }

        $onchange .= "$('[name=\"$name\"]').change(function() {";

        $onchange .= "var tohide = {};";
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            $onchange .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                            tohide[$hidden_link] = false;
                        }";

            //if reload form
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

            else {
                if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                    if (Session::getLoginUserID() == $idc) {
                        $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                    }
                }
            }

            $onchange .= "$.each( tohide, function( key, value ) {           
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            " .PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link)."
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        } else {
                            $('[id-field =\"field'+key+'\"]').show();
                            " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                        }
                    });
              ";
        }
        $onchange .= "});";

        echo Html::scriptBlock('$(document).ready(function() {' . $pre_onchange . " " . $onchange. " " . $post_onchange . '});');

    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

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
            $script = "console.log('blocksHiddenScript-dropdownobject $id');";
        }
        $script .= "$('[name=\"$name\"]').change(function() {";

        $script .= "var tohide = {};";

        //by default - hide all
        $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

        $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);

        foreach ($check_values as $idc => $check_value) {
            $blocks_idc = [];
            $hidden_block = $check_value['hidden_block'];

            $script .= "if ($(this).val() == $idc || $idc == -1 ) {";

            //specific for radio / dropdowns - one value
            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

            $script .= "$('[bloc-id =\"bloc'+$hidden_block+'\"]').show();";
            $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

            if (is_array($childs_by_checkvalue)) {
                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                    if ($idc == $k) {
                        foreach ($childs_blocks as $childs) {
                            $script .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $childs);
                        }
                    }
                }
            }

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

//            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
//                    || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
//                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//            }

            else {
                if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                    if (Session::getLoginUserID() == $idc) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }

            $script .= " }";

            $script .= "if ($(this).val() != $idc) {";
            if (is_array($blocks_idc) && count($blocks_idc) > 0) {
                foreach ($blocks_idc as $k => $block_idc) {
                    $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
                }
            }
            $script .= " }";

            $script .= "if ($(this).val() == 0 ) {";
            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            $script .= " }";

        }

        $script .= "fixButtonIndicator();});";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function getFieldValue($field)
    {

        $dbu = new DbUtils();
        switch ($field['item']) {
            case 'User':
                return getUserName($field['value'], 0, true);
            default:
                return Dropdown::getDropdownName(
                    $dbu->getTableForItemType($field['item']),
                    $field['value']
                );
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if ($field['value'] != 0) {
            switch ($field['item']) {
                case 'User':
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_title>";
                    }
                    $result[$field['rank']]['content'] .= $label;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }

                    $result[$field['rank']]['display'] = true;
                    $item = new $field['item']();
                    $content = "";
                    $information = json_decode($field['informations_to_display']);

                    // legacy support
                    if (empty($information)) {
                        $information = ['full_name'];
                    }

                    if ($item->getFromDB($field['value'])) {
                        if (in_array('full_name', $information)) {
                            $content .= "" . $field["item"]::getFriendlyNameById($field['value']) . " ";
                        }
                        if (in_array('realname', $information)) {
                            $content .= "" . $item->fields["realname"] . " ";
                        }
                        if (in_array('firstname', $information)) {
                            $content .= "" . $item->fields["firstname"] . " ";
                        }
                        if (in_array('name', $information)) {
                            $content .= "" . $item->fields["name"] . " ";
                        }
                        if (in_array('email', $information)) {
                            $content .= "" . $item->getDefaultEmail() . " ";
                        }
                    }
                    if (empty($content)) {
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td>";
                        }
                        $result[$field['rank']]['content'] .= self::getFieldValue($field);
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                    } else {
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td>";
                        }
                        $result[$field['rank']]['content'] .= $content;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                    }

                    break;
                default:
                    $result[$field['rank']]['display'] = true;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_title>";
                    }
                    $result[$field['rank']]['content'] .= $label;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td><td>";
                    }
                    $result[$field['rank']]['content'] .= self::getFieldValue($field);
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }

                    break;
            }
        }

        return $result;
    }
}
