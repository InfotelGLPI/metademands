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
 * PluginMetademandsDropdownmeta Class
 *
 **/
class PluginMetademandsDropdownmeta extends CommonDBTM
{

    public static $dropdown_meta_items = [
        '',
        'other',
        'ITILCategory_Metademands',
        'urgency',
        'impact',
        'priority',
        'mydevices'
    ];

    const CLASSIC_DISPLAY = 0;
    const ICON_DISPLAY = 1;

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
        return __('Dropdown', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order, $itilcategories_id)
    {
        global $PLUGIN_HOOKS;

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "";
        switch ($data['item']) {
            case 'other':
                if (!empty($data['custom_values'])) {
                    $custom_values = $data['custom_values'];


                    $default_value = "";
                    $choices = [];
                    if (count($custom_values) > 0) {
                        foreach ($custom_values as $key => $label) {
                            if (empty($name = PluginMetademandsField::displayCustomvaluesField($data['id'], $key))) {
                                $name = $label['name'];
                            }

                            $choices[$label['id']] = $name;
                            if ($label['is_default'] == 1) {
                                $default_value = $label['id'];
                            }
                        }
                    }

//                    $custom_values = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
//
//                    foreach ($custom_values as $k => $val) {
//                        if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
//                            $custom_values[$k] = $ret;
//                        }
//                    }
//
//                    $defaults = PluginMetademandsFieldParameter::_unserialize($data['default_values']);
//
//                    $default_values = "";
//                    if ($defaults) {
//                        foreach ($defaults as $k => $v) {
//                            if ($v == 1) {
//                                $default_values = $k;
//                            }
//                        }
//                    }
                    $value = !empty($value) ? $value : $default_value;
                    //                     ksort($data['custom_values']);
                    $field = "";
                    $field .= Dropdown::showFromArray(
                        $namefield . "[" . $data['id'] . "]",
                        $choices,
                        [
                            'value' => $value,
                            'width' => '100%',
                            'display_emptychoice' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? "required" : ""),
                        ]
                    );
                }
                break;

            case 'ITILCategory_Metademands':
                if ($on_order == false) {
                    $nameitil = 'field';
                } else {
                    $nameitil = 'basket';
                }
                $values = json_decode($metademand->fields['itilcategories_id']);
                //from Service Catalog
                if ($itilcategories_id > 0) {
                    $value = $itilcategories_id;
                }
                //                  if (!empty($values) && count($values) == 1) {
                //                     foreach ($values as $key => $val)
                //                        $itilcategories_id = $val;
                //                  }
                //                  if ($itilcategories_id > 0) {
                //                     // itilcat from service catalog
                //                     $itilCategory = new ITILCategory();
                //                     $itilCategory->getFromDB($itilcategories_id);
                //                     $field = "<span>" . $itilCategory->getField('name');
                //                     $field .= "<input type='hidden' name='" . $nameitil . "_type' value='" . $metademand->fields['type'] . "' >";
                //                     $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id' value='" . $itilcategories_id . "' >";
                //                     $field .= "<span>";
                //                  } else {
                $readonly = $data['readonly'];
                $hidden = $data['hidden'];
                if ($hidden == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
                    && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $hidden = 0;
                }

                if ($data['readonly'] == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
                    && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $readonly = 0;
                }
                $opt = [
                    'name' => $nameitil . "_plugin_servicecatalog_itilcategories_id",
                    'right' => 'all',
                    'value' => $value,
                    'condition' => ["id" => $values],
                    'display' => false,
                    'readonly' => $readonly ?? false,
                    'class' => 'form-select itilmeta'
                ];
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                $field = "";
                if ($hidden == 0) {
                    $pass = true;
                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            if (Plugin::isPluginActive($plug)) {
                                $field .= self::getPluginDropdownItilcategory($plug, $opt);
                                $pass = false;
                            }
                        }
                    }
                    if ($pass) {
                        $field .= ITILCategory::dropdown($opt);
                    }

                    $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id_key' value='" . $data['id'] . "' >";
                }

                if ($readonly == 1 || $hidden == 1) {
                    $field .= Html::hidden($nameitil . "_plugin_servicecatalog_itilcategories_id", ['value' => $value]);
                }
                break;
            case 'mydevices':
                $field = "";

                if ($on_order == false) {
                    if ($data["display_type"] == self::ICON_DISPLAY) {
                        // My items
                        //TODO : used_by_ticket -> link with item's ticket
                        $field = "";
                        $default_values = $data['default_values'] ?? [];

                        $_POST['field'] = $namefield . "[" . $data['id'] . "]";
                        //                     $users_id = 0;
                        if ($data['link_to_user'] > 0) {
                            $fieldUser = new PluginMetademandsField();
                            $fieldUser->getFromDBByCrit([
                                'id' => $data['link_to_user'],
                                'type' => "dropdown_object",
                                'item' => User::getType()
                            ]);
                            $_POST['value'] = 0;
                            if (!empty($fieldUser->fields)) {
                                $params = PluginMetademandsField::getAllParamsFromField($fieldUser);
                                $_POST['value'] = ($params['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID(
                                );
                            }

                            $_POST['id_fielduser'] = $data['link_to_user'];
                            $_POST['fields_id'] = $data['id'];
                            $_POST['limit'] = json_encode($default_values);
                            $_POST['metademands_id'] = $data['plugin_metademands_metademands_id'];

                            $selected_items_id = 0;
                            $selected_itemtype = "";

                            $_POST['value'] = $data['link_to_user'];
                            $users_id = $_POST['value'];
                            echo "<div id='mydevices_user$users_id' class=\"input-group\">";

                            if (isset($value) && !empty($value)) {
                                $splitter = explode("_", $value);
                                if (count($splitter) == 2) {
                                    $selected_itemtype = $splitter[0];
                                    $selected_items_id = $splitter[1];
                                }
                            }
                            $_POST['selected_items_id'] = $selected_items_id;
                            $_POST['selected_itemtype'] = $selected_itemtype;
                            $_POST['is_mandatory'] = $data['is_mandatory'] ?? 0;
                            include(PLUGIN_METADEMANDS_DIR . "/ajax/umydevicesUpdate.php");
                            echo "</div>";

                            if ($data['is_mandatory']) {
                                echo "<div class='alertelt active'><div class='alertelttext'><span>";
                                echo __('This field is mandatory, please select your equipment', 'metamemands');
                                echo "</span></div>";
                            }

//                        echo "<div class='tooltipelt'><div class='tooltipelttext'><span>";
//                        echo __('If your equipment is not listed, thanks to add its name on ticket description', 'metamemands');
//                        echo "</span></div>";
                        } else {
                            $rand = mt_rand();

                            $p = [
                                'rand' => $rand,
                                'name' => $_POST["field"],
                                'value' => $data['value'] ?? 0,
                                'is_mandatory' => $data['is_mandatory'] ?? 0,
                                'users_id' => Session::getLoginUserID(),
                                'limit' => $default_values
                            ];
                            $p['selected_itemtype'] = "";
                            $p['selected_items_id'] = 0;
                            if (isset($value) && !empty($value)) {
                                $splitter = explode("_", $value);
                                if (count($splitter) == 2) {
                                    $p['selected_itemtype'] = $splitter[0];
                                    $p['selected_items_id'] = $splitter[1];
                                }
                            }

                            $field .= self::getItemsForUser($p);
                        }
                    } else {
                        // My items
                        //TODO : used_by_ticket -> link with item's ticket
                        $field = "";
                        $default_values = $data['default_values'] ?? [];

                        $_POST['field'] = $namefield . "[" . $data['id'] . "]";
                        //                     $users_id = 0;
                        if ($data['link_to_user'] > 0) {
                            echo "<div id='mydevices_user" . $data['link_to_user'] . "' class=\"input-group\">";
                            $fieldUser = new PluginMetademandsField();
                            $fieldUser->getFromDBByCrit([
                                'id' => $data['link_to_user'],
                                'type' => "dropdown_object",
                                'item' => User::getType()
                            ]);
                            $_POST['value'] = 0;
                            if (!empty($fieldUser->fields)) {
                                $params = PluginMetademandsField::getAllParamsFromField($fieldUser);
                                $_POST['value'] = ($params['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID(
                                );
                            }

                            $_POST['id_fielduser'] = $data['link_to_user'];
                            $_POST['fields_id'] = $data['id'];
                            $_POST['limit'] = json_encode($default_values);
                            $_POST['metademands_id'] = $data['plugin_metademands_metademands_id'];
                            include(PLUGIN_METADEMANDS_DIR . "/ajax/umydevicesUpdate.php");
                            echo "</div>";
                        } else {
                            $rand = mt_rand();
                            $p = [
                                'rand' => $rand,
                                'name' => $_POST["field"],
                                'value' => $data['value'] ?? 0,
                            ];
                            $field .= PluginMetademandsField::dropdownMyDevices(
                                Session::getLoginUserID(),
                                $_SESSION['glpiactiveentities'],
                                0,
                                0,
                                $p,
                                $default_values,
                                false
                            );
                        }
                    }
                } else {
                    $dbu = new DbUtils();
                    $splitter = explode("_", $value);
                    if (count($splitter) == 2) {
                        $itemtype = $splitter[0];
                        $items_id = $splitter[1];
                    }
                    $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "' >";
                    if (isset($itemtype) && isset($items_id)) {
                        $field .= Dropdown::getDropdownName(
                            $dbu->getTableForItemType($itemtype),
                            $items_id
                        );
                    }
                }

                break;
            case 'urgency':
                $field = "";
                $ticket = new Ticket();
                if ($itilcategories_id == 0) {
                    $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                    if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                        foreach ($itilcategories_id_array as $arr) {
                            $itilcategories_id = $arr;
                        }
                    }
                }

                if ($itilcategories_id > 0) {
                    $meta_tt = $ticket->getITILTemplateToUse(
                        0,
                        $metademand->fields['type'],
                        $itilcategories_id,
                        $metademand->fields['entities_id']
                    );
                    if (isset($meta_tt->predefined['urgency'])) {
                        $options['value'] = $meta_tt->predefined['urgency'];
                        if (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) {
                            $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']];
                            if (is_array($session_value)) {
                                foreach ($session_value as $k => $fieldSession) {
                                    if ($fieldSession > 0) {
                                        $options['value'] = $fieldSession;
                                    }
                                }
                            }
                        }
                    }
                }

                if (isset($data['default_values'])) {
                    $defaults = $data['default_values'];
                    if (is_array($defaults) && count($defaults) > 0) {
                        foreach ($defaults as $k => $v) {
                            $options['value'] = $v;
                        }
                    }
                }

                $options['name'] = $namefield . "[" . $data['id'] . "]";
                $options['display'] = false;
                $options['required'] = ((isset($data['is_mandatory']) && $data['is_mandatory'] == 1) ? "required" : "");
                $options['display_emptychoice'] = true;
                $field .= Ticket::dropdownUrgency($options);
                break;
            case 'impact':
                $field = "";
                $ticket = new Ticket();
                if ($itilcategories_id == 0) {
                    $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                    if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                        foreach ($itilcategories_id_array as $arr) {
                            $itilcategories_id = $arr;
                        }
                    }
                }

                if ($itilcategories_id > 0) {
                    $meta_tt = $ticket->getITILTemplateToUse(
                        0,
                        $metademand->fields['type'],
                        $itilcategories_id,
                        $metademand->fields['entities_id']
                    );
                    if (isset($meta_tt->predefined['impact'])) {
                        $options['value'] = $meta_tt->predefined['impact'];
                        if (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) {
                            $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']];
                            if (is_array($session_value)) {
                                foreach ($session_value as $k => $fieldSession) {
                                    if ($fieldSession > 0) {
                                        $options['value'] = $fieldSession;
                                    }
                                }
                            }
                        }
                    }
                }

                if (isset($data['default_values'])) {
                    $defaults = $data['default_values'];
                    if (is_array($defaults) && count($defaults) > 0) {
                        foreach ($defaults as $k => $v) {
                            $options['value'] = $v;
                        }
                    }
                }
                $options['name'] = $namefield . "[" . $data['id'] . "]";
                $options['display'] = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $options['display_emptychoice'] = true;
                $field .= Ticket::dropdownImpact($options);
                break;
            case 'priority':
                $field = "";
                $ticket = new Ticket();
                if ($itilcategories_id == 0) {
                    $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                    if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                        foreach ($itilcategories_id_array as $arr) {
                            $itilcategories_id = $arr;
                        }
                    }
                }
                if ($itilcategories_id > 0) {
                    $meta_tt = $ticket->getITILTemplateToUse(
                        0,
                        $metademand->fields['type'],
                        $itilcategories_id,
                        $metademand->fields['entities_id']
                    );
                    if (isset($meta_tt->predefined['priority'])) {
                        $options['value'] = $meta_tt->predefined['priority'];
                        if (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) {
                            $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']];
                            if (is_array($session_value)) {
                                foreach ($session_value as $k => $fieldSession) {
                                    if ($fieldSession > 0) {
                                        $options['value'] = $fieldSession;
                                    }
                                }
                            }
                        }
                    }
                }
                if (isset($data['default_values'])) {
                    $defaults = $data['default_values'];
                    if (is_array($defaults) && count($defaults) > 0) {
                        foreach ($defaults as $k => $v) {
                            $options['value'] = $v;
                        }
                    }
                }
                $options['name'] = $namefield . "[" . $data['id'] . "]";
                $options['display'] = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $options['display_emptychoice'] = true;
                $field .= Ticket::dropdownPriority($options);
                break;
            default:
                //example other plugin with metademand class
                $cond = [];
                $field = "";
                $opt = [
                    'value' => $value,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'name' => $namefield . "[" . $data['id'] . "]",
                    //                          'readonly'  => true,
                    'condition' => $cond,
                    'display' => false
                ];
                $dbu = new DbUtils();
                if ($dbu->getItemForItemtype($data["item"])) {
                    $container_class = new $data['item']();
                    $field .= $container_class::dropdown($opt);
                }

                break;
        }

        echo $field;
    }


    public
    static function getItemsForUser(
        $values
    ) {
        global $CFG_GLPI, $DB;

        if (!isset($values['users_id'])) {
            return false;
        }
//        $config = new PluginServicecatalogConfig();
        $users_id_requester = $values['users_id'];

//        if ($config->useItemtypesDisplay() == 1) {
//
//        } else {
//            $ticket = new ticket();
//            $params['_users_id_requester'] = $users_id_requester;
//            $params['itemtype'] = 'Ticket';
//            $params['_canupdate'] = true;
//
//            Item_Ticket::itemAddForm($ticket, $params);
//
//            echo "<span style='color: red;text-align: left;padding-top: 5px;'>";
//            echo __('If your equipment is not listed, thanks to add its name on ticket description', 'servicecatalog');
//            echo "</span>";
//        }

        echo "<div class='container' style='display:contents;'>";
        echo "<div class='row' data-toggle='buttons' style='margin-left: 1px;'>";

        $objects_items_id = [];

        $objects = $CFG_GLPI["linkuser_types"];
        if (count($values['limit']) > 0) {
            $objects = $values['limit'];
        }
        $objects[] = "Other";
        echo Html::scriptBlock("var hardwareType = [];");

        if (is_array($objects)
            && count($objects) > 0) {
            echo "<span data-toggle='buttons' style='margin-bottom: 15px;'><h5>" . __('My devices') . "</h5>";
        }
        $i = 0;
        foreach ($objects as $itemtype) {
            if (($item = getItemForItemtype($itemtype))
                && Ticket::isPossibleToAssignType($itemtype)
                && $itemtype != "Other"
                && $itemtype != "Certificate"
                && $itemtype != "Rack"
                && $itemtype != "DatabaseInstance"
                && $itemtype != "Simcard"
                && $itemtype != "PluginSimcardSimcard"
                && $itemtype != "PluginOrderOrder"
                && $itemtype != "Other"
                && $itemtype != "Domain"
                && $itemtype != "Line"
                && $itemtype != "PDU"
                && $itemtype != "PluginBadgesBadge"
                && $itemtype != "PluginResourcesResource"
            ) {
                $where = [];
                $itemtable = getTableForItemType($itemtype);

                if ($itemtype != "Appliance"
                ) {
                    $where['users_id'] = $users_id_requester;
                }

                if (is_array($objects_items_id)
                    && count($objects_items_id) > 0) {
                    $where = [];
                    $where['id'] = $objects_items_id;
                }
                $criteria = [
                    'FROM' => $itemtable,
                    'WHERE' => $where + getEntitiesRestrictCriteria(
                            $itemtable,
                            '',
                            $_SESSION["glpiactive_entity"],
                            $item->maybeRecursive()
                        ),
                    'ORDER' => $item->getNameField()
                ];

                if ($item->maybeDeleted()) {
                    $criteria['WHERE']['is_deleted'] = 0;
                }
                if ($item->maybeTemplate()) {
                    $criteria['WHERE']['is_template'] = 0;
                }

                $user = new User();
                $locations_id = 0;
                if ($user->getFromDB($users_id_requester)) {
                    $locations_id = $user->fields['locations_id'];
                }
                if ($itemtype == "Printer" && $locations_id > 0) {
                    $criteria['WHERE']['locations_id'] = $locations_id;
                }

                if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"]) && $itemtype != "Database") {
                    $criteria['WHERE']['is_helpdesk_visible'] = 1;
                }

                $iterator = $DB->request($criteria);
                $nb = count($iterator);
                if ($nb > 0) {
                    $i = 1;
                    foreach ($iterator as $data) {
                        $items_id = $data["id"];
                        $typename = $item->getTypeName(1);
                        $type = $item->getType();
//                        if ($type == "Appliance"
//                            && !PluginServicecatalogApplianceLink::isApplianceAllowed($items_id)) {
//                            continue;
//                        }

                        $varname = "hardwareType_" . $type . "_" . $items_id;
                        echo Html::scriptBlock("hardwareType.push('$varname');");

                        $checked = "";
                        $active = "";
                        if (isset($values["items_id"]) && is_array($values["items_id"])) {
                            $arr = $values["items_id"];
                            foreach ($arr as $elttype => $arr2) {
                                if (in_array($items_id, $arr2) && $elttype == $itemtype) {
                                    $checked = "checked";
                                    $active = "active buttonelt_color";
                                }
                            }
                        }
                        if (is_array($objects_items_id)
                            && count($objects_items_id) == 1 && in_array($items_id, $objects_items_id)) {
                            $checked = "checked";
                            $active = "active buttonelt_color";
                        }

                        if ($values['selected_items_id'] == $items_id
                            && $values['selected_itemtype'] == $itemtype) {
                            $checked = "checked";
                            $active = "active buttonelt_color";
                        }

                        echo "<label id='$varname' class='btn buttonelt col-md-2 center $active'
                                            onclick='changeBackgroundColor(\"$varname\",\"buttonelt_color\")'>";

                        $value = $itemtype . "_" . $items_id;
                        echo "<input type='radio' class='my_items' name='" . $values['name'] . "' value='$value' $checked>";

                        echo "<div class='center'>";
                        $icon = self::getIconForType($itemtype);

                        $ok = 0;
                        $obj = new $itemtype();
                        if ($obj->getFromDB($items_id)) {
                            $className = strtolower(get_class($obj));
                            $model = $className . "models";
                            if (isset($obj->fields[$model . "_id"]) && !empty($obj->fields[$model . "_id"])) {
                                if ($itemModel = getItemForItemtype($type . 'Model')) {
                                    $itemModel->getFromDB($obj->fields[$model . "_id"]);
                                    $pictures = [];
                                    if ($itemModel->fields['pictures'] != null) {
                                        $pictures = json_decode($itemModel->fields['pictures'], true);

                                        if (isset($pictures) && is_array($pictures)) {
                                            foreach ($pictures as $picture) {
                                                $picture_url = Toolbox::getPictureUrl($picture);
                                                $icon = "<img class='user_picture' style='width: 30%;height: 30%;' 
                                        alt=\"" . _sn('Picture', 'Pictures', 1) . "\" src='" .
                                                    $picture_url . "'>";
                                                $ok = 1;
                                            }
                                        }
                                    }
                                }
                            }
                            if ($itemtype == "Appliance") {
                                if ($obj->fields['pictures'] != null) {
                                    $pictures = json_decode($obj->fields['pictures'], true);

                                    if (isset($pictures) && is_array($pictures)) {
                                        foreach ($pictures as $picture) {
                                            $picture_url = Toolbox::getPictureUrl($picture);
                                            $icon = "<img class='user_picture' style='width: 30%;height: 30%;' 
                                        alt=\"" . _sn('Picture', 'Pictures', 1) . "\" src='" .
                                                $picture_url . "'>";
                                            $ok = 1;
                                        }
                                    }
                                }
                            }
                        }
                        if ($ok == 1) {
                            echo "$icon&nbsp;";
                        } else {
                            echo "<i style='font-size:4em' class='$icon fa-3x mr-3'></i>&nbsp;";
                        }

                        echo "<h5 class='mt-0 mb-1 buttonelt-title'>";
                        echo $data[$item->getNameField()] . "&nbsp;";
                        $comment = "";
                        if (isset($data['serial']) && !empty($data['serial'])) {
                            $comment = __('Serial number') . " : " . $data['serial'];
                        }
                        if (!empty($comment)) {
                            echo "&nbsp;";
                            echo Html::showToolTip($comment);
                        }

                        echo "</h5><br>";
                        echo $typename;
                        echo "</div>";
                        echo "</label>";
                    }
                }
            }
        }
//        if ($itemtype == "Other") {
//            echo Html::scriptBlock("hardwareType.push('hardwareType_0');");
//            $checked = "";
//            $active = "";
//            if (isset($elttype) && $elttype == $itemtype) {
//                $checked = "checked";
//                $active = "active buttonelt_color";
//            }
//
//            echo "<label id='hardwareType_0' class='btn buttonelt col-md-2 center $active'
//                            onclick='changeBackgroundColor(\"hardwareType_0\",\"buttonelt_color\")'>";
//            $value = $itemtype . "_0";
//            echo "<input type='radio' class='my_items' name='my_items' value='$value' $checked>";
//            $icon = "fas fa-question";
//            echo "<div class='center' style=''>";
//            echo "<i style='font-size:4em' class='$icon fa-3x'></i>";
//            echo "<h5 class='mt-0 mb-1 buttonelt-title'>";
//            echo __('My equipment does not appear', 'metamemands');
//            echo "</h5><br>";
//            echo "</div>";
//
//            echo "</label>";
//        }

        if ($i == 0) {
            echo __('No equipment founded', 'metademands');
            echo Html::scriptBlock(
                "var tooltip = document.querySelector('.alertelt');
                         if (tooltip != null) {
                            tooltip.classList.remove('active');
                         }"
            );
        }
        if (is_array($objects)
            && count($objects) > 0) {
            echo "</span>";
        }
        if (Session::haveRight("show_group_hardware", "1")
            && (!is_array($objects_items_id) || count($objects_items_id) == 0)) {
            $entity_restrict = $_SESSION["glpiactive_entity"];
            $iterator = $DB->request([
                'SELECT' => [
                    'glpi_groups_users.groups_id',
                    'glpi_groups.name'
                ],
                'FROM' => 'glpi_groups_users',
                'LEFT JOIN' => [
                    'glpi_groups' => [
                        'ON' => [
                            'glpi_groups_users' => 'groups_id',
                            'glpi_groups' => 'id'
                        ]
                    ]
                ],
                'WHERE' => [
                        'glpi_groups_users.users_id' => $users_id_requester
                    ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
            ]);

            $devices = [];
            $groups = [];
            if (count($iterator)) {
                foreach ($iterator as $data) {
                    $a_groups = getAncestorsOf("glpi_groups", $data["groups_id"]);
                    $a_groups[$data["groups_id"]] = $data["groups_id"];
                    $groups = array_merge($groups, $a_groups);
                }

                foreach ($CFG_GLPI["linkgroup_types"] as $itemtype_groups) {
                    if (
                        ($item = getItemForItemtype($itemtype_groups))
                        && Ticket::isPossibleToAssignType($itemtype_groups)
                    ) {
                        $itemtable = getTableForItemType($itemtype_groups);
                        $criteria = [
                            'FROM' => $itemtable,
                            'WHERE' => [
                                    'groups_id' => $groups
                                ] + getEntitiesRestrictCriteria(
                                    $itemtable,
                                    '',
                                    $entity_restrict,
                                    $item->maybeRecursive()
                                ),
                            'ORDER' => $item->getNameField()
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']['is_deleted'] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']['is_template'] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            if (!isset($already_add[$itemtype_groups])) {
                                $already_add[$itemtype_groups] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype_groups])) {
                                    $devices[$itemtype_groups][] = $data["id"];

                                    $already_add[$itemtype_groups][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    echo "<br><span data-toggle='buttons' style='margin-bottom: 15px;'><h5>" . __(
                            'Devices own by my groups'
                        ) . "</h5>";
//                                $my_devices[__('Devices own by my groups')] = $devices;
//                                Toolbox::loginfo($devices);
                    foreach ($devices as $itemtype_groups => $list_items_id) {
                        if ($item = getItemForItemtype($itemtype_groups)
                            && Ticket::isPossibleToAssignType($itemtype_groups)
                            && $itemtype_groups != "Other"
                            && $itemtype_groups != "Certificate"
                            && $itemtype_groups != "Rack"
                            && $itemtype_groups != "DatabaseInstance"
                            && $itemtype_groups != "Simcard"
                            && $itemtype_groups != "PluginSimcardSimcard"
                            && $itemtype_groups != "PluginOrderOrder"
                            && $itemtype_groups != "Other"
                            && $itemtype_groups != "Domain"
                            && $itemtype_groups != "Line"
                            && $itemtype_groups != "PDU"
                            && $itemtype_groups != "PluginBadgesBadge"
                            && $itemtype_groups != "PluginResourcesResource"
                            && in_array($itemtype_groups, $objects)
                        ) {
                            foreach ($list_items_id as $key => $items_id) {
                                $varname = "hardwareType_" . $itemtype_groups . "_" . $items_id;
                                echo Html::scriptBlock("hardwareType.push('$varname');");

                                $checked = "";
                                $active = "";
                                if (isset($values["items_id"]) && is_array($values["items_id"])) {
                                    $arr = $values["items_id"];
                                    foreach ($arr as $elttype => $arr2) {
                                        if (in_array($items_id, $arr2) && $elttype == $itemtype_groups) {
                                            $checked = "checked";
                                            $active = "active buttonelt_color";
                                        }
                                    }
                                }
                                if (is_array($objects_items_id)
                                    && count($objects_items_id) == 1 && in_array($items_id, $objects_items_id)) {
                                    $checked = "checked";
                                    $active = "active buttonelt_color";
                                }

                                if ($values['selected_items_id'] == $items_id
                                    && $values['selected_itemtype'] == $itemtype_groups) {
                                    $checked = "checked";
                                    $active = "active buttonelt_color";
                                }

                                echo "<label id='$varname' class='btn buttonelt col-md-2 center $active'
                                            onclick='changeBackgroundColor(\"$varname\",\"buttonelt_color\")'>";

                                $value = $itemtype_groups . "_" . $items_id;
                                echo "<input type='radio' class='my_items' name='" . $values['name'] . "' value='$value' $checked>";

                                echo "<div class='center'>";
                                $icon = self::getIconForType($itemtype_groups);

                                $ok = 0;
                                $obj = new $itemtype_groups();
                                if ($obj->getFromDB($items_id)) {
                                    $className = strtolower(get_class($obj));
                                    $model = $className . "models";
                                    if (isset($obj->fields[$model . "_id"]) && !empty($obj->fields[$model . "_id"])) {
                                        if ($itemModel = getItemForItemtype($itemtype_groups . 'Model')) {
                                            $itemModel->getFromDB($obj->fields[$model . "_id"]);
                                            $pictures = [];
                                            if ($itemModel->fields['pictures'] != null) {
                                                $pictures = json_decode($itemModel->fields['pictures'], true);

                                                if (isset($pictures) && is_array($pictures)) {
                                                    foreach ($pictures as $picture) {
                                                        $picture_url = Toolbox::getPictureUrl($picture);
                                                        $icon = "<img class='user_picture' style='width: 30%;height: 30%;'
                                        alt=\"" . _sn('Picture', 'Pictures', 1) . "\" src='" .
                                                            $picture_url . "'>";
                                                        $ok = 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if ($itemtype_groups == "Appliance") {
                                        if ($obj->fields['pictures'] != null) {
                                            $pictures = json_decode($obj->fields['pictures'], true);

                                            if (isset($pictures) && is_array($pictures)) {
                                                foreach ($pictures as $picture) {
                                                    $picture_url = Toolbox::getPictureUrl($picture);
                                                    $icon = "<img class='user_picture' style='width: 30%;height: 30%;'
                                        alt=\"" . _sn('Picture', 'Pictures', 1) . "\" src='" .
                                                        $picture_url . "'>";
                                                    $ok = 1;
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($ok == 1) {
                                    echo "$icon&nbsp;";
                                } else {
                                    echo "<i style='font-size:4em' class='$icon fa-3x mr-3'></i>&nbsp;";
                                }

                                echo "<h5 class='mt-0 mb-1 buttonelt-title'>";
                                echo $obj->getName() . "&nbsp;";
                                $comment = "";
                                if (isset($obj->fields['serial']) && !empty($obj->fields['serial'])) {
                                    $comment = __('Serial number') . " : " . $obj->fields['serial'];
                                }
                                if (!empty($comment)) {
                                    echo "&nbsp;";
                                    echo Html::showToolTip($comment);
                                }

                                echo "</h5><br>";
                                echo $obj->getTypeName();
                                echo "</div>";
                                echo "</label>";
                            }
                        }
                    }
                    echo "</span>";
                }
            }
        }

        echo "</div></div>";

        if ($values['is_mandatory'] && $i > 0) {
            echo Html::scriptBlock(
                "var tooltip = document.querySelector('.alertelt');
                         if (tooltip != null) {
                            tooltip.classList.add('active');
                         }"
            );
        }

        echo Html::scriptBlock(
            "
                        function changeBackgroundColor(idLabel,newCss) {
                        
                           hardwareType.forEach(function(item, index, array) {
                                 document.getElementById(item).className='btn buttonelt col-md-2 center';
                              });
                           document.getElementById(idLabel).className='btn buttonelt col-md-2 center '+newCss;
                           
                           var buttonelt = document.getElementById('hardwareType_0');
                           var tooltip = document.querySelector('.tooltipelt');
                           if (buttonelt.innerHTML.length > 0) {
                               tooltip.classList.remove('active');
                               $('.tooltipelt').hide();
                               buttonelt.addEventListener('click', function() {
                                 tooltip.classList.add('active');
                                 $('.tooltipelt').show();
                               });
                            } else {
                                $('.tooltipelt').hide();
                            }
                        }"
        );
    }

    public static function getIconForType($type)
    {
        if (!empty($type)) {
            $item = new $type();
            return 'fas ' . $item->getIcon();
        } else {
            return 'far fa-question-circle';
        }
    }

    static function showFieldCustomValues($params)
    {
        global $CFG_GLPI;

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='5'>";
        $maxrank = 0;
        $custom_values = $params['custom_values'];
        $default_values = $params['default_values'];

        if (is_array($custom_values)
            && !empty($custom_values)) {
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
            echo "<td colspan='5' align='left' id='show_custom_fields'>";
            PluginMetademandsFieldCustomvalue::initCustomValue(
                $maxrank,
                false,
                true,
                $params["plugin_metademands_fields_id"]
            );
            echo "</td>";
            echo "</tr>";
            PluginMetademandsFieldCustomvalue::importCustomValue($params);
        } else {
            $target = PluginMetademandsFieldCustomvalue::getFormURL();
            if (!in_array($params["item"], PluginMetademandsField::$field_specificobjects)) {
                echo "<form method='post' action=\"$target\">";
                echo "<tr class='tab_bg_1'>";
                echo "<td align='right' id='show_custom_fields' colspan='5'>";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
                }
                PluginMetademandsFieldCustomvalue::initCustomValue(
                    -1,
                    false,
                    true,
                    $params["plugin_metademands_fields_id"]
                );
                echo "</td>";
                echo "</tr>";
                Html::closeForm();
                PluginMetademandsFieldCustomvalue::importCustomValue($params);
            } elseif ($params['item'] == 'urgency'
                || $params['item'] == 'impact'
                || $params['item'] == 'priority') {
                $default_values = $params['default_values'];
                if (is_array($default_values) && count($default_values) > 0) {
                    foreach ($default_values as $key => $default_value) {
                        $options['value'] = $default_value;
                    }
                }
                echo "<form method='post' action=\"$target\">";
                echo "<tr class='tab_bg_1'>";
                echo "<td>";
                $options['name'] = "default[1]";
                $options['display_emptychoice'] = true;
                if ($params['item'] == 'urgency') {
                    Ticket::dropdownUrgency($options);
                } elseif ($params['item'] == 'impact') {
                    Ticket::dropdownImpact($options);
                } elseif ($params['item'] == 'priority') {
                    Ticket::dropdownPriority($options);
                }

                echo "</td>";
                echo "</tr>";
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
            } elseif ($params['item'] == 'mydevices') {
                $default_values = $params['default_values'];

                echo "<form method='post' action=\"$target\">";
                echo "<tr class='tab_bg_1'>";
                echo "<td>";

                $list = [];

                foreach ($CFG_GLPI['linkuser_types'] as $itemtype) {
                    if (!($item = getItemForItemtype($itemtype))) {
                        continue;
                    }
                    if ($item->canView()) {
                        $list[$itemtype] = $item->getTypeName();
                    }
                }

                Dropdown::showFromArray(
                    "default",
                    $list,
                    [
                        'values' => $default_values,
                        'multiple' => true
                    ]
                );

                echo "</td>";
                echo "</tr>";
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
            }
        }
        echo "</td>";
        echo "</tr>";
    }

    static function showFieldParameters($params)
    {
        echo "<tr>";
        if ($params["item"] == "urgency"
            || $params["item"] == "impact"
            || $params["item"] == "priority") {
            echo "<td>";
            echo __('Use this field for child ticket field', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('used_by_child', $params['used_by_child']);
            echo "</td>";
        }
        if ($params["item"] == "mydevices") {
            $disp = [];
            $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
            $disp[self::ICON_DISPLAY] = __("Icon display", "metademands");

            echo "<td>";
            echo __('Display type of the field', 'metademands');
            echo "</td>";
            echo "<td>";
            echo Dropdown::showFromArray("display_type", $disp, [
                'value' => $params['display_type'],
                'display' => false
            ]);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        if ($params["item"] == "mydevices") {
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
        }
        echo "</tr>";

        if ($params["id"] > 0 && ($params['type'] == "dropdown_meta"
                && $params["item"] == "ITILCategory_Metademands")) {
            echo "<tr class='tab_bg_1'>";

            echo "<td>";
            echo __('Read-Only', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('readonly', ($params['readonly']));
            echo "</td>";

            echo "<td>";
            echo __('Hidden field', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('hidden', ($params['hidden']));
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
        global $PLUGIN_HOOKS;

        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        if ($item->getID() == 0) {
            foreach ($existing_options as $existing_option) {
                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
            }
        }
        switch ($params["item"]) {
            case 'ITILCategory_Metademands':
                $metademand = new PluginMetademandsMetademand();
                $metademand->getFromDB($params["plugin_metademands_metademands_id"]);
                $values = json_decode($metademand->fields['itilcategories_id']);

                $name = "check_value";
                $opt = [
                    'name' => $name,
                    'right' => 'all',
                    'value' => $params['check_value'],
                    'condition' => ["id" => $values],
                    'display' => true,
                    'used' => $already_used
                ];

                $pass = false;
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_drop = self::getPluginDropdownItilcategory($plug, $opt);
                        if (Plugin::isPluginActive($plug) && $new_drop != false) {
                            $field .= $new_drop;
                            $pass = true;
                        }
                    }
                }

                if (!$pass) {
                    ITILCategory::dropdown($opt);
                }

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
                    $params['item']::Dropdown([
                        "name" => $name,
                        "value" => $params['check_value'],
                        'used' => $already_used
                    ]);
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
        global $PLUGIN_HOOKS;

        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            echo __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'ITILCategory_Metademands':

                    $pass = false;
                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            $new_drop = self::getPluginDropdownItilcategoryName($plug, $params['check_value']);
                            if (Plugin::isPluginActive($plug) && $new_drop != false) {
                                echo $new_drop;
                                $pass = true;
                            }
                        }
                    }

                    if (!$pass) {
                        echo Dropdown::getDropdownName('glpi_itilcategories', $params['check_value']);
                    }

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
        if (($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } elseif ($check_value != $value
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
            $script = "console.log('taskScript-dropdownmeta $id');";
        }
        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
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

            $name = "field[" . $data["id"] . "]";
            if ($data["item"] == "ITILCategory_Metademands") {
                $name = "field_plugin_servicecatalog_itilcategories_id";
            }

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
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($custom_value['is_default'] == 1) {
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
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
        }

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-dropdownmeta $id');";
        }

        if (count($check_values) > 0) {
            //Initialize default value - force change after onchange fonction
            if (isset($data['custom_values'])
                && is_array($data['custom_values'])
                && count($data['custom_values']) > 0
                && !isset($data['value'])) {
                $custom_values = $data['custom_values'];
                foreach ($custom_values as $k => $custom_value) {
                    if ($custom_value['is_default'] == 1) {
                        $post_onchange .= "$('[name=\"field[" . $id . "]\"]').val('$k').trigger('change');";
                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                $('[name=\"field[" . $hidden_link . "]\"]').removeAttr('required');";
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
            }


            $onchange .= "$('[name=\"$name\"]').change(function() {";

            $onchange .= "var tohide = {};";

            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $onchange .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                            tohide[$hidden_link] = false;
                        }";


                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $hidden_link;
                }

                $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            sessionStorage.setItem('hiddenlink$name', key);
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        } else {
                            $('[id-field =\"field'+key+'\"]').show();
                            $('[name =\"field['+key+']\"]').attr('required', 'required');
                        }
                    });
              ";
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
        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
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

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('blocksHiddenScript-dropdownmeta $id');";
        }

        if (count($check_values) > 0) {
            //Initialize default value - force change after onchange fonction
            foreach ($check_values as $idc => $check_value) {
                //Default values
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && $custom_value['is_default'] == 1) {
                            $post_onchange .= "$('[name=\"$name\"]').prop('checked', true).trigger('change');";
                        }
                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $post_onchange .= "if (document.getElementById('ablock" . $childs . "'))
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
                    }
                }
            }


            //by default - hide all
            $pre_onchange .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $pre_onchange .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"$name\"]').val(" . $data['value'] . ").trigger('change');";
            }

            $onchange .= "$('[name=\"$name\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $hidden_block = $check_value['hidden_block'];

                $onchange .= "if ($hidden_block in tohide) {
                      } else {
                        tohide[$hidden_block] = true;
                      }
                    if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                        tohide[$hidden_block] = false;
                    }";


                $onchange .= "$.each( tohide, function( key, value ) {
                    if (value == true) {
                       var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'none';
                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
                        $('[bloc-id =\"subbloc'+ key +'\"]').hide();
                        sessionStorage.setItem('hiddenbloc$name', key);
                        " . PluginMetademandsFieldoption::setEmptyBlockFields($name) . "";
                $hidden = PluginMetademandsFieldoption::resetMandatoryBlockFields($name);
                $onchange .= "$hidden";
                if (is_array($childs_by_checkvalue)) {
                    foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                        if ($idc == $k) {
                            foreach ($childs_blocks as $childs) {
                                $onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'none';
                                $('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                $('[bloc-id =\"subbloc" . $childs . "\"]').hide();";
                            }
                        }
                    }
                }
                $onchange .= "} else {
                        var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'block';
                        $('[bloc-id =\"bloc'+ key +'\"]').show();
                        $('[bloc-id =\"subbloc'+ key +'\"]').show();
                        ";

                $hidden = PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                $onchange .= "$hidden";
                $onchange .= "}
                });
          ";

                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $hidden_block;
                }

                if ($data["item"] == "ITILCategory_Metademands") {
                    if (isset($_GET['itilcategories_id']) && $idc == $_GET['itilcategories_id']) {
                        $pre_onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                        $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();
                          " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                    }
                }
            }
            if ($display > 0) {
                $pre_onchange .= "if (document.getElementById('ablock" . $display . "'))
                        document.getElementById('ablock" . $display . "').style.display = 'block';
                        $('[bloc-id =\"bloc" . $display . "\"]').show();
                        $('[bloc-id =\"subbloc" . $display . "\"]').show();";
            }

            $onchange .= "fixButtonIndicator();});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        }
    }


    public static function getFieldValue($field, $lang)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (!empty($field['custom_values'])
            && $field['item'] == 'other') {
            //TODO MIGRATE
//            $custom_values = PluginMetademandsFieldParameter::_unserialize($field['custom_values']);
//            $custom_values[0] = Dropdown::EMPTY_VALUE;
//            foreach ($custom_values as $k => $val) {
//                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
//                    $custom_values[$k] = $ret;
//                }
//                if (isset($custom_values[$field['value']])) {
//                    return $custom_values[$field['value']];
//                }
//            }
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            return $custom_values[$field['value']] ?? "";
        } else {
            if ($field['value'] != 0) {
                switch ($field['item']) {
                    case 'ITILCategory_Metademands':

                        $pass = false;
                        if (isset($PLUGIN_HOOKS['metademands'])) {
                            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                                $new_drop = self::getPluginDropdownItilcategoryName($plug, $field['value']);
                                if (Plugin::isPluginActive($plug) && $new_drop != false) {
                                    return $new_drop;
                                    $pass = true;
                                }
                            }
                        }

                        if (!$pass) {
                            return Dropdown::getDropdownName(
                                $dbu->getTableForItemType('ITILCategory'),
                                $field['value']
                            );
                        }

                    case 'mydevices':
                        $splitter = explode("_", $field['value']);
                        if (count($splitter) == 2) {
                            $itemtype = $splitter[0];
                            $items_id = $splitter[1];
                        }
                        if (isset($items_id)) {
                            return Dropdown::getDropdownName(
                                $dbu->getTableForItemType($itemtype),
                                $items_id
                            );
                        } else {
                            return "";
                        }
                    case 'urgency':
                        return Ticket::getUrgencyName($field['value']);
                    case 'impact':
                        return Ticket::getImpactName($field['value']);
                    case 'priority':
                        return Ticket::getPriorityName($field['value']);
                    default:
                        return Dropdown::getDropdownName(
                            $dbu->getTableForItemType($field['item']),
                            $field['value']
                        );
                }
            }
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
            && $field['item'] == 'other' && $field['value'] > 0) {
            $custom_values[0] = Dropdown::EMPTY_VALUE;
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }

            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if (isset($custom_values[$field['value']])) {
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= $label;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= $custom_values[$field['value']];
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }
            }
        } else {
            if ($field['value'] != 0) {
                switch ($field['item']) {
                    case 'mydevices':
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                        }
                        $result[$field['rank']]['content'] .= $label;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
                        }

                        $splitter = explode("_", $field['value']);
                        if (count($splitter) == 2) {
                            $itemtype = $splitter[0];
                            $items_id = $splitter[1];
                        }
                        if ($itemtype && $items_id) {
                            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                        }
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                        break;
                    case 'priority':
                    case 'impact':
                    case 'urgency':
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                        }
                        $result[$field['rank']]['content'] .= $label;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                            $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
                        }
                        $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                        break;
                    default:
                        $hidden = $field['hidden'];
                        if ($hidden == 0) {
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
                        }
                        break;
                }
            }
        }

        return $result;
    }

    private static function getPluginDropdownItilcategory($plug, $opt)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getdropdownItilcategory'])) {
                    return $item->getdropdownItilcategory($opt);
                }
            }
        }
    }

    static function getPluginDropdownItilcategoryName($plug, $opt)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getdropdownItilcategoryName'])) {
                    return $item->getdropdownItilcategoryName($opt);
                }
            }
        }
    }
}
