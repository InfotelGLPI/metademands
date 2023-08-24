<?php

/**
 * -------------------------------------------------------------------------
 * Metademands plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Metademands.
 *
 * Metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2013-2022 by Metademands plugin team.
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/Metademands
 * -------------------------------------------------------------------------
 */

include('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (isset($_POST['fields_id'])) {
    $fields_id = $_POST['fields_id'];
}


$field = new PluginMetademandsField();
$metademand = new PluginMetademandsMetademand();
if ($field->getFromDB($fields_id)) {
    $metademand->getFromDB($field->fields['plugin_metademands_metademands_id']);
    $name = 'check_value';
    $item = $field->fields['item'];
    $type = $field->fields['type'];
    $options = [
        'name' => $name,
        'required' => true,
        'right' => 'all',
        'entity' => $_SESSION['glpiactive_entity'],
        'entity_sons' => $_SESSION['glpiactive_entity_recursive']
    ];
    if ($item != ''
        && ($type == 'dropdown'
            || $type == 'dropdown_object'
            || $type == 'dropdown_multiple'
            || $type == 'dropdown_meta')) {
        if ($type == 'dropdown_meta') {
            switch ($item) {
                case 'other':
                    $choices = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                    Dropdown::showFromArray(
                        $options['name'],
                        $choices,
                        ['width' => '100%',
                        ]
                    );
                    break;
                case 'ITILCategory_Metademands':

                    $values = json_decode($metademand->fields['itilcategories_id']);
                    $params = [
                        'name' => $name,
                        'right' => 'all',
                        'class' => 'form-select itilmeta',
                        'condition' => ['id' => $values]
                    ];
                    ITILCategory::dropdown($params);
                    break;
                case 'mydevices':
                    $params = [
                        'name' => $name
                    ];
                    PluginMetademandsField::dropdownMyDevices(Session::getLoginUserID(), $_SESSION['glpiactiveentities'], 0, 0, $params);
                    break;
                case 'urgency':
                    $params = [
                        'name' => $name,
                    ];
                    Ticket::dropdownUrgency($params);
                    break;
                case 'impact':
                    $params = [
                        'name' => $name,
                    ];
                    Ticket::dropdownImpact($params);
                    break;
                case 'priority':
                    $params = [
                        'name' => $name,
                    ];
                    Ticket::dropdownPriority($params);
                    break;
            }
        } else {
            $item::dropdown($options);
            echo Html::hidden('check_item', ['value' => 'check_item']);
        }
    } else {
        switch ($type) {
            default :
                echo Html::input(
                    "$name",
                    [
                        'type' => 'text',
                        'required' => true,
                    ]
                );
                break;
            case 'number' :
                echo Html::input(
                    "$name",
                    [
                        'type' => 'number',
                        'required' => true
                    ]
                );
                break;
            case 'radio':
            case 'checkbox' :
                $options = [
                    'display_emptychoice' => false,
                ];
                $choices = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                Dropdown::showFromArray(
                    "$name",
                    $choices,
                    $options
                );
                break;
            case 'date' :
                $options = [
                    'required' => true,
                    'size' => 40
                ];
                echo "<span style='width: 50%!important;display: -webkit-box;'>";
                echo Html::showDateField(
                    "$name",
                    $options
                );
                echo "</span>";
                break;
            case 'datetime' :
                $options = [
                    'required' => true,
                    'size' => 40
                ];
                echo "<span style='width: 50%!important;display: -webkit-box;'>";
                echo Html::showDateTimeField(
                    "$name",
                    $options
                );
                echo "</span>";
                break;

            case 'yesno' :
                $choice[1] = __('No');
                $choice[2] = __('Yes');
                Dropdown::showFromArray($name, $choice, [
                        'display_emptychoice' => false,
                        'width' => '70px',
                    ]
                );
                break;
        }

    }
}
