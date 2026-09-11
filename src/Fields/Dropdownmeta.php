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
use CommonITILObject;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Badges\Badge;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Resources\Resource;
use Group_Item;
use Html;
use ITILCategory;
use Plugin;
use Session;
use Toolbox;
use User;

/**
 * Dropdownmeta Class
 *
 **/
class Dropdownmeta extends CommonDBTM
{
    public static $dropdown_meta_items = [
        '',
        'other',
        'ITILCategory_Metademands',
        'urgency',
        'impact',
        'priority',
        'mydevices',
    ];

    public const CLASSIC_DISPLAY = 0;
    public const ICON_DISPLAY = 1;

    public const BLOCK_DISPLAY = 2;
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
        return __('Specific dropdown', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $itilcategories_id)
    {
        global $PLUGIN_HOOKS;

        $metademand = new Metademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "";
        switch ($data['item']) {
            case 'other':
                if (!empty($data['custom_values'])) {
                    $custom_values = $data['custom_values'];

                    if ($data["display_type"] == self::BLOCK_DISPLAY) {
                        $field .= "<div class='row flex-row'>";
                    }

                    $default_value = "";
                    $choices = [];
                    $block_options = [];
                    if (count($custom_values) > 0) {
                        foreach ($custom_values as $key => $label) {
                            if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                                $name = $label['name'];
                            }

                            $choices[$label['id']] = $name;
                            if ($label['is_default'] == 1) {
                                $default_value = $label['id'];
                            }

                            if ($data["display_type"] == self::BLOCK_DISPLAY) {
                                $icon = $label['icon'];
                                if (empty($label['icon'])) {
                                    $icon = $data['icon'];
                                }
                                $has_icon = !empty($icon);

                                if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                                    $name = $label['name'];
                                }

                                $comment_html = "";
                                if (isset($label['comment']) && !empty($label['comment'])) {
                                    if (empty(
                                        $comment = Field::displayCustomvaluesField(
                                            $data['id'],
                                            $key,
                                            "comment",
                                        )
                                    )) {
                                        $comment = $label['comment'];
                                    }
                                    // Sanitize raw user-supplied option comment to prevent stored XSS.
                                    $comment_html = RichText::getSafeHtml($comment);
                                }

                                $checked = "";
                                if (empty($value) && isset($label['is_default']) && $on_order == false) {
                                    $checked = ($label['is_default'] == 1) ? 'checked' : '';
                                }
                                if (isset($value) && $value == $key) {
                                    $checked = 'checked';
                                }

                                // Raw label/icon/comment are auto-escaped by the Twig template
                                // ({{ }} applies htmlspecialchars ENT_QUOTES|ENT_SUBSTITUTE).
                                $block_options[] = [
                                    'key'          => $key,
                                    'name'         => (string) $name,
                                    'has_icon'     => $has_icon,
                                    'icon'         => (string) $icon,
                                    'icon_is_fa'   => $has_icon && str_contains((string) $icon, 'fa-'),
                                    'comment_html' => $comment_html,
                                    'checked'      => $checked,
                                ];
                            }
                        }
                    }

                    $value = !empty($value) ? $value : $default_value;

                    if ($data["display_type"] == self::BLOCK_DISPLAY) {
                        $field .= TemplateRenderer::getInstance()->render(
                            '@metademands/fields/field_dropdownmeta_block.html.twig',
                            [
                                'options'   => $block_options,
                                'namefield' => $namefield,
                                'id'        => $data['id'],
                                'required'  => ($data['is_mandatory'] == 1) ? "required=required" : "",
                            ],
                        );
                        $field .= "</div>";
                    }

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $field = "";
                        $field .= \Dropdown::showFromArray(
                            $namefield . "[" . $data['id'] . "]",
                            $choices,
                            [
                                'value' => $value,
                                'width' => '100%',
                                'display_emptychoice' => true,
                                'display' => false,
                                'required' => ($data['is_mandatory'] ? "required" : ""),
                            ],
                        );
                    }
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
                    'condition' => [(new ITILCategory())::getTable() . ".id" => $values],
                    'display' => false,
                    'readonly' => $readonly ?? false,
                    'class' => 'form-select itilmeta',
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
                                if ($field .= self::getPluginDropdownItilcategory($plug, $opt)) {
                                    $pass = false;
                                }
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
                    if (isset($data["display_type"])
                        && $data["display_type"] == self::ICON_DISPLAY) {
                        // My items
                        //TODO : used_by_ticket -> link with item's ticket
                        $field = "";
                        $default_values = $data['default_values'] ?? [];

                        $_POST['field'] = $namefield . "[" . $data['id'] . "]";
                        //                     $users_id = 0;
                        if ($data['link_to_user'] > 0) {
                            $fieldUser = new Field();
                            $fieldUser->getFromDBByCrit([
                                'id' => $data['link_to_user'],
                                'type' => "dropdown_object",
                                'item' => User::getType(),
                            ]);
                            //First load POST datas
                            if (!empty($fieldUser->fields)) {
                                $params = Field::getAllParamsFromField($fieldUser);
                                $_POST['users_id'] = ($params['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                            } else {
                                $_POST['users_id'] = 0;
                            }
                            $_POST['display_type'] = $data['display_type'];
                            $_POST['id_fielduser'] = $data['link_to_user'];
                            $_POST['fields_id'] = $data['id'];
                            $_POST['limit'] = json_encode($default_values);
                            $_POST['metademands_id'] = $data['plugin_metademands_metademands_id'];

                            $selected_items_id = 0;
                            $selected_itemtype = "";

                            $users_id = $_POST['users_id'];
                            // The included endpoint prints the field: capture it and let the
                            // template own the input-group wrapper, which two distant `echo`
                            // used to open and close.
                            ob_start();

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
                            $field .= TemplateRenderer::getInstance()->render(
                                '@metademands/fields/field_input_group.html.twig',
                                [
                                    'id'      => 'mydevices_user' . $data['link_to_user'] . $data['id'],
                                    'content' => ob_get_clean(),
                                ],
                            );

                            if ($data['is_mandatory']) {
                                $field .= TemplateRenderer::getInstance()->render(
                                    '@metademands/fields/field_alert_elt.html.twig',
                                    [
                                        'message' => __('This field is mandatory, please select your equipment', 'metademands'),
                                    ],
                                );
                            }

                            //                        echo "<div class='tooltipelt'><div class='tooltipelttext'><span>";
                            //                        echo __('If your equipment is not listed, thanks to add its name on ticket description', 'metademands');
                            //                        echo "</span></div>";
                        } else {
                            $rand = mt_rand();

                            $p = [
                                'rand' => $rand,
                                'name' => $_POST["field"],
                                'value' => $data['value'] ?? 0,
                                'is_mandatory' => $data['is_mandatory'] ?? 0,
                                'users_id' => Session::getLoginUserID(),
                                'limit' => $default_values,
                                'required' => $data['is_mandatory'] ?? 0,
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
                            // The included endpoint prints the field: capture it and let the
                            // template own the input-group wrapper, which two distant `echo`
                            // used to open and close.
                            ob_start();
                            $fieldUser = new Field();
                            $fieldUser->getFromDBByCrit([
                                'id' => $data['link_to_user'],
                                'type' => "dropdown_object",
                                'item' => User::getType(),
                            ]);
                            //First load POST datas
                            if (!empty($fieldUser->fields)) {
                                $params = Field::getAllParamsFromField($fieldUser);
                                $_POST['users_id'] = ($params['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                            } else {
                                $_POST['users_id'] = 0;
                            }
                            $_POST['display_type'] = $data['display_type'];
                            $_POST['id_fielduser'] = $data['link_to_user'];
                            $_POST['fields_id'] = $data['id'];
                            $_POST['is_mandatory'] = $data['is_mandatory'];
                            $_POST['limit'] = json_encode($default_values);
                            $_POST['metademands_id'] = $data['plugin_metademands_metademands_id'];
                            include(PLUGIN_METADEMANDS_DIR . "/ajax/umydevicesUpdate.php");
                            $field .= TemplateRenderer::getInstance()->render(
                                '@metademands/fields/field_input_group.html.twig',
                                [
                                    'id'      => 'mydevices_user' . $data['link_to_user'] . $data['id'],
                                    'content' => ob_get_clean(),
                                ],
                            );
                        } else {
                            $rand = mt_rand();
                            $p = [
                                'rand' => $rand,
                                'name' => $_POST["field"],
                                'value' => $data['value'] ?? 0,
                                'required' => $data['is_mandatory'] ?? 0,
                            ];
                            $field .= Field::dropdownMyDevices(
                                Session::getLoginUserID(),
                                $_SESSION['glpiactiveentities'],
                                0,
                                0,
                                $p,
                                $default_values,
                                false,
                            );
                        }
                    }
                } else {
                    $dbu = new DbUtils();
                    $itemtype = null;
                    $items_id = null;
                    $splitter = explode("_", $value);
                    if (count($splitter) == 2) {
                        $itemtype = $splitter[0];
                        $items_id = $splitter[1];
                    }
                    $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . htmlescape($value) . "' >";
                    // The itemtype half is carved out of a session value the requester controls, so
                    // it is validated before being resolved into a table name rather than trusted:
                    // only a real CommonDBTM may be read, and only at a positive row id.
                    if (is_a($itemtype, CommonDBTM::class, true) && (int) $items_id > 0) {
                        $field .= \Dropdown::getDropdownName(
                            $dbu->getTableForItemType($itemtype),
                            (int) $items_id,
                        );
                    }
                }

                break;
            case 'urgency':
                $field = "";
                $ticket = new \Ticket();
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
                        $metademand->fields['entities_id'],
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
                $field .= \Ticket::dropdownUrgency($options);
                break;
            case 'impact':
                $field = "";
                $ticket = new \Ticket();
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
                        $metademand->fields['entities_id'],
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
                $field .= \Ticket::dropdownImpact($options);
                break;
            case 'priority':
                $field = "";
                $ticket = new \Ticket();
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
                        $metademand->fields['entities_id'],
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
                $field .= \Ticket::dropdownPriority($options);
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
                    'display' => false,
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

    /**
     * Tiles of the devices a requester may pick: the ones they hold themselves,
     * then the ones their groups hold.
     *
     * @param array $values
     *
     * @return false|void
     */
    public static function getItemsForUser(
        $values
    ) {
        global $CFG_GLPI, $DB;

        if (!isset($values['users_id'])) {
            return false;
        }

        $users_id_requester = (int) $values['users_id'];

        $objects = $CFG_GLPI['assignable_types'];
        if (count($values['limit']) > 0) {
            $objects = $values['limit'];
        }
        $objects[] = 'Other';

        $device_items = [];
        $found = false;

        if ($users_id_requester > 0) {
            // The requester was reloaded once per item type by the legacy loop.
            $user = new User();
            $locations_id = 0;
            if ($user->getFromDB($users_id_requester)) {
                $locations_id = (int) $user->fields['locations_id'];
            }

            foreach ($objects as $itemtype) {
                if (!self::isSelectableDeviceType($itemtype)
                    || !($item = getItemForItemtype($itemtype))) {
                    continue;
                }

                $criteria = self::getOwnedDevicesCriteria($item, $itemtype, $users_id_requester, $locations_id);
                foreach ($DB->request($criteria) as $data) {
                    $found = true;
                    $device_items[] = self::buildDeviceRow(
                        $itemtype,
                        (int) $data['id'],
                        $values,
                        (string) $data[$item->getNameField()],
                        (string) $item->getTypeName(1),
                        (string) ($data['serial'] ?? ''),
                    );
                }
            }
        }

        $sections = [[
            'title' => __('My devices'),
            'separator' => false,
            'items' => $device_items,
            'empty_message' => $found ? '' : __('No equipment founded', 'metademands'),
        ]];

        if (Session::haveRight('show_group_hardware', '1')) {
            $entity_restrict = (int) $_SESSION['glpiactive_entity'];
            $groups = self::getRequesterGroups($users_id_requester, $entity_restrict);

            if (count($groups) > 0) {
                $group_objects = $CFG_GLPI['linkgroup_types'];
                if (count($values['limit']) > 0) {
                    $group_objects = $values['limit'];
                }

                $group_items = [];
                foreach (self::getGroupDevices($group_objects, $groups, $entity_restrict) as $itemtype => $items_ids) {
                    if (!self::isSelectableDeviceType($itemtype)
                        || !in_array($itemtype, $group_objects)) {
                        continue;
                    }

                    foreach ($items_ids as $items_id) {
                        $group_items[] = self::buildDeviceRow($itemtype, (int) $items_id, $values);
                    }
                }

                // The legacy code opened the section as soon as a device had been
                // collected, even when the exclusion list emptied it right after.
                if (count($group_items) > 0) {
                    $sections[] = [
                        'title' => __('Devices own by my groups', 'metademands'),
                        'separator' => true,
                        'items' => $group_items,
                        'empty_message' => '',
                    ];
                }
            }
        }

        $alert = '';
        if (!$found) {
            $alert = 'off';
        } elseif (!empty($values['is_mandatory'])) {
            $alert = 'on';
        }

        TemplateRenderer::getInstance()->display('@metademands/fields/field_dropdownmeta_devices.html.twig', [
            'sections' => $sections,
            'alert' => $alert,
        ]);
    }

    /**
     * Item types the device picker refuses: the ones a ticket cannot be assigned to,
     * and the ones the tiles were never meant to list.
     *
     * @param string $itemtype
     *
     * @return bool
     */
    private static function isSelectableDeviceType($itemtype): bool
    {
        $excluded = [
            'Other',
            'Certificate',
            'Rack',
            'DatabaseInstance',
            'Simcard',
            'PluginSimcardSimcard',
            'PluginOrderOrder',
            'Domain',
            'Line',
            'PDU',
            Badge::class,
            Resource::class,
        ];

        return !in_array($itemtype, $excluded) && \Ticket::isPossibleToAssignType($itemtype);
    }

    /**
     * Criteria listing the devices the requester holds for one item type.
     *
     * @param \CommonDBTM $item
     * @param string      $itemtype
     * @param int         $users_id_requester
     * @param int         $locations_id       location of the requester, printers only
     *
     * @return array
     */
    private static function getOwnedDevicesCriteria($item, $itemtype, $users_id_requester, $locations_id): array
    {
        global $CFG_GLPI;

        $itemtable = getTableForItemType($itemtype);
        $where = [];

        // An appliance carries no holder: it is listed for the whole entity.
        if ($itemtype != 'Appliance') {
            $where['users_id'] = $users_id_requester;
        }

        $criteria = [
            'FROM' => $itemtable,
            'WHERE' => $where + getEntitiesRestrictCriteria(
                $itemtable,
                '',
                $_SESSION['glpiactive_entity'],
                $item->maybeRecursive(),
            ),
            'ORDER' => $item->getNameField(),
        ];

        if ($item->maybeDeleted()) {
            $criteria['WHERE']['is_deleted'] = 0;
        }
        if ($item->maybeTemplate()) {
            $criteria['WHERE']['is_template'] = 0;
        }
        if ($itemtype == 'Printer' && $locations_id > 0) {
            $criteria['WHERE']['locations_id'] = $locations_id;
        }
        if (in_array($itemtype, $CFG_GLPI['helpdesk_visible_types']) && $itemtype != 'Database') {
            $criteria['WHERE']['is_helpdesk_visible'] = 1;
        }

        return $criteria;
    }

    /**
     * Groups the requester belongs to, ancestors included.
     *
     * @param int $users_id_requester
     * @param int $entity_restrict
     *
     * @return array
     */
    private static function getRequesterGroups($users_id_requester, $entity_restrict): array
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['glpi_groups_users.groups_id'],
            'FROM' => 'glpi_groups_users',
            'LEFT JOIN' => [
                'glpi_groups' => [
                    'ON' => [
                        'glpi_groups_users' => 'groups_id',
                        'glpi_groups' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_groups_users.users_id' => $users_id_requester,
            ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true),
        ]);

        $groups = [];
        foreach ($iterator as $data) {
            $ancestors = getAncestorsOf('glpi_groups', $data['groups_id']);
            $ancestors[$data['groups_id']] = $data['groups_id'];
            $groups = array_merge($groups, $ancestors);
        }

        return $groups;
    }

    /**
     * Devices held by the groups of the requester, indexed by item type.
     *
     * @param array $itemtypes
     * @param array $groups
     * @param int   $entity_restrict
     *
     * @return array
     */
    private static function getGroupDevices($itemtypes, $groups, $entity_restrict): array
    {
        global $DB;

        $devices = [];
        foreach ($itemtypes as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))
                || !\Ticket::isPossibleToAssignType($itemtype)) {
                continue;
            }

            $itemtable = getTableForItemType($itemtype);
            $criteria = [
                'SELECT' => $itemtable . '.id',
                'FROM' => $itemtable,
                'LEFT JOIN' => [
                    'glpi_groups_items' => [
                        'ON' => [
                            'glpi_groups_items' => 'items_id',
                            $itemtable => 'id', [
                                'AND' => [
                                    'glpi_groups_items.itemtype' => $itemtype,
                                    'glpi_groups_items.type' => Group_Item::GROUP_TYPE_NORMAL,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE' => [
                    'glpi_groups_items.groups_id' => $groups,
                ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                'ORDER' => $item->getNameField(),
            ];

            if ($item->maybeDeleted()) {
                $criteria['WHERE']['is_deleted'] = 0;
            }
            if ($item->maybeTemplate()) {
                $criteria['WHERE']['is_template'] = 0;
            }

            foreach ($DB->request($criteria) as $data) {
                // The join repeats a device once per group it is linked to.
                $devices[$itemtype][$data['id']] = $data['id'];
            }
        }

        return $devices;
    }

    /**
     * One selection tile of the device picker.
     *
     * The two lists feed the same template: the personal one already read its row,
     * the group one only knows an id, so the name, the type name and the serial
     * number are taken from the loaded object when the caller passes nothing.
     *
     * @param string      $itemtype
     * @param int         $items_id
     * @param array       $values
     * @param string|null $name
     * @param string|null $typename
     * @param string|null $serial
     *
     * @return array
     */
    private static function buildDeviceRow(
        $itemtype,
        $items_id,
        $values,
        $name = null,
        $typename = null,
        $serial = null,
    ): array {
        $obj = getItemForItemtype($itemtype);
        $loaded = $obj !== false && $obj->getFromDB($items_id);

        if ($serial === null) {
            $serial = $loaded ? (string) ($obj->fields['serial'] ?? '') : '';
        }

        $comment = '';
        if ($serial !== '') {
            $comment = __('Serial number') . ' : ' . $serial;
        }

        return [
            'varname' => 'hardwareType_' . $itemtype . '_' . $items_id,
            'values_name' => (string) $values['name'],
            'value' => $itemtype . '_' . $items_id,
            'selected' => self::isDeviceSelected($itemtype, $items_id, $values),
            'icon_html' => self::getDeviceIcon($itemtype, $loaded ? $obj : null),
            'name' => (string) ($name ?? ($loaded ? $obj->getName() : '')),
            'typename' => (string) ($typename ?? ($loaded ? $obj->getTypeName() : '')),
            // showToolTip() prints by default, so the markup used to be flushed
            // before the tiles while the row itself carried nothing.
            'tooltip_html' => $comment === '' ? '' : Html::showToolTip($comment, ['display' => false]),
        ];
    }

    /**
     * Whether the requester already picked that device.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @param array  $values
     *
     * @return bool
     */
    private static function isDeviceSelected($itemtype, $items_id, $values): bool
    {
        if (isset($values['items_id']) && is_array($values['items_id'])) {
            foreach ($values['items_id'] as $selected_itemtype => $selected_items_id) {
                if ($selected_itemtype == $itemtype && in_array($items_id, $selected_items_id)) {
                    return true;
                }
            }
        }

        return ($values['selected_items_id'] ?? 0) == $items_id
            && ($values['selected_itemtype'] ?? '') == $itemtype;
    }

    /**
     * Illustration of a device: the picture of its model when it carries one, the
     * icon of its item type otherwise.
     *
     * @param string           $itemtype
     * @param \CommonDBTM|null $obj      the loaded device, null when it could not be read
     *
     * @return string
     */
    private static function getDeviceIcon($itemtype, $obj): string
    {
        $pictures = [];

        if ($obj !== null) {
            $model_field = strtolower(get_class($obj)) . 'models_id';
            if (!empty($obj->fields[$model_field])
                && ($item_model = getItemForItemtype($itemtype . 'Model'))
                && $item_model->getFromDB($obj->fields[$model_field])
                && $item_model->fields['pictures'] !== null) {
                $pictures = json_decode($item_model->fields['pictures'], true);
            }

            if ($itemtype == 'Appliance' && ($obj->fields['pictures'] ?? null) !== null) {
                $pictures = json_decode($obj->fields['pictures'], true);
            }
        }

        if (is_array($pictures) && count($pictures) > 0) {
            // The legacy loop rebuilt the tag on every picture: the last one won.
            $picture_url = Toolbox::getPictureUrl(end($pictures));

            return '<img class="user_picture" style="width: 30%;height: 30%;" alt="'
                . _sn('Picture', 'Pictures', 1) . '" src="' . $picture_url . '">';
        }

        $icon = self::getIconForType($itemtype);

        if (str_contains($icon, 'fa-')) {
            return "<i style='font-size:4em' class='fas " . $icon . " fa-3x mr-3'></i>";
        }

        return "<i style='font-size:4em' class='ti " . $icon . " mr-3'></i>";
    }

    public static function getIconForType($type)
    {
        if (!empty($type)) {
            $item = new $type();
            return $item->getIcon();
        } else {
            return 'ti ti-exclamation-circle';
        }
    }

    public static function showFieldCustomValues($params)
    {
        global $CFG_GLPI;

        $custom_values = $params['custom_values'];
        $default_values = $params['default_values'];
        $target = FieldCustomvalue::getFormURL();
        $maxrank = -1;
        $rows = [];

        if (is_array($custom_values) && !empty($custom_values)) {
            foreach ($custom_values as $key => $value) {
                ob_start();
                \Dropdown::showYesNo('is_default[' . $key . ']', $value['is_default']);
                $default_html = ob_get_clean();

                $icon_html = FieldCustomvalue::showIconSelector($key, (string) $value['icon']);

                ob_start();
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
                    "class='btn btn-sm btn-danger'",
                );
                $delete_form_html = ob_get_clean();

                $rows[] = [
                    'id' => $key,
                    'rank' => $value['rank'],
                    'name' => $value['name'],
                    'comment' => $value['comment'] ?? '',
                    'default_html' => $default_html,
                    'icon_html' => $icon_html,
                    'delete_form_html' => $delete_form_html,
                ];
                $maxrank = $value['rank'];
            }
        }

        $item = $params['item'] ?? '';
        $init_form_html = '';
        $import_html = '';
        $specific_dropdown_html = '';

        if (!in_array($item, Field::$field_specificobjects)) {
            ob_start();
            FieldCustomvalue::initCustomValue($maxrank, false, true, $params["plugin_metademands_fields_id"], true);
            $init_form_html = ob_get_clean();

            ob_start();
            FieldCustomvalue::importCustomValue($params);
            $import_html = ob_get_clean();
        } else {
            $options = [];
            if (is_array($default_values) && count($default_values) > 0) {
                foreach ($default_values as $key => $default_value) {
                    $options['value'] = $default_value;
                }
            }
            $options['name'] = "default[1]";
            $options['display_emptychoice'] = true;

            ob_start();
            if ($item == 'urgency') {
                \Ticket::dropdownUrgency($options);
            } elseif ($item == 'impact') {
                \Ticket::dropdownImpact($options);
            } elseif ($item == 'priority') {
                \Ticket::dropdownPriority($options);
            } elseif ($item == 'mydevices') {
                $list = [];
                foreach ($CFG_GLPI['assignable_types'] as $itemtype) {
                    if (!($obj = getItemForItemtype($itemtype))) {
                        continue;
                    }
                    if ($obj->canView()) {
                        $list[$itemtype] = $obj->getTypeName();
                    }
                }
                \Dropdown::showFromArray("default", $list, [
                    'values' => $default_values,
                    'multiple' => true,
                ]);
            }
            $specific_dropdown_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display(
            '@metademands/fields/field_customvalue_list.html.twig',
            [
                'rows' => $rows,
                'form_target' => $target,
                'fields_id' => $params['plugin_metademands_fields_id'] ?? '',
                'type' => $params['type'] ?? '',
                'item' => $item,
                'show_comment' => isset($params["display_type"]) && $params["display_type"] == self::BLOCK_DISPLAY,
                'init_form_html' => $init_form_html,
                'import_html' => $import_html,
                'specific_dropdown_html' => $specific_dropdown_html,
                'reorder_url' => PLUGIN_METADEMANDS_WEBDIR . '/ajax/reorder.php',
            ],
        );
    }

    public static function showFieldParameters($params): string
    {
        $show_used_by_child = in_array($params["item"], ["urgency", "impact", "priority"]);
        $used_by_child_html = '';
        if ($show_used_by_child) {
            ob_start();
            \Dropdown::showYesNo('used_by_child', $params['used_by_child']);
            $used_by_child_html = ob_get_clean();
        }

        $show_display_type = $params["item"] == "mydevices";
        $display_type_html = '';
        if ($show_display_type) {
            $disp = [];
            $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
            $disp[self::ICON_DISPLAY] = __("Icon display", "metademands");
            $display_type_html = \Dropdown::showFromArray("display_type", $disp, [
                'value'   => $params['display_type'],
                'display' => false,
            ]);
        }

        $show_link_to_user = $params["item"] == "mydevices";
        $link_to_user_html = '';
        if ($show_link_to_user) {
            $arrayAvailable[0] = \Dropdown::EMPTY_VALUE;
            $field = new Field();
            $fields = $field->find([
                "plugin_metademands_metademands_id" => $params['plugin_metademands_metademands_id'],
                'type' => "dropdown_object",
                "item" => User::getType(),
            ]);
            foreach ($fields as $f) {
                $arrayAvailable[$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
            }
            ob_start();
            \Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $params['link_to_user']]);
            $link_to_user_html = ob_get_clean();
        }

        $show_itil_options = $params["id"] > 0
            && $params['type'] == "dropdown_meta"
            && $params["item"] == "ITILCategory_Metademands";
        $readonly_html = '';
        $hidden_html = '';
        if ($show_itil_options) {
            ob_start();
            \Dropdown::showYesNo('readonly', $params['readonly']);
            $readonly_html = ob_get_clean();

            ob_start();
            \Dropdown::showYesNo('hidden', $params['hidden']);
            $hidden_html = ob_get_clean();
        }

        $show_other_display_type = $params["id"] > 0
            && $params['type'] == "dropdown_meta"
            && $params["item"] == "other";
        $other_display_type_html = '';
        if ($show_other_display_type) {
            $disp = [];
            $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
            $disp[self::BLOCK_DISPLAY] = __("Block display", "metademands");
            $other_display_type_html = \Dropdown::showFromArray("display_type", $disp, [
                'value'   => $params['display_type'],
                'display' => false,
            ]);
        }

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_dropdownmeta.html.twig',
            [
                'show_used_by_child'      => $show_used_by_child,
                'used_by_child_html'      => $used_by_child_html,
                'show_display_type'       => $show_display_type,
                'display_type_html'       => $display_type_html,
                'show_link_to_user'       => $show_link_to_user,
                'link_to_user_html'       => $link_to_user_html,
                'show_itil_options'       => $show_itil_options,
                'readonly_html'           => $readonly_html,
                'hidden_html'             => $hidden_html,
                'show_other_display_type' => $show_other_display_type,
                'other_display_type_html' => $other_display_type_html,
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

        // The per-cell inline <script> moved to public/scripts/fieldoption_valuetocheck.js;
        // the wrapping cell now carries its parameters as data-* attributes.
        $valuetocheck_html = TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_value_to_check_cell.html.twig',
            [
                'option_id'       => $params['ID'],
                'with_check_type' => true,
                'with_tech_group' => true,
                'content'         => $cell_content,
            ],
        );

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => '',
                'label'             => __('Value to check', 'metademands'),
                'label_colspan'     => 1,
                'regex_html'        => $regex_html,
                'valuetocheck_html' => $valuetocheck_html,
                'link_html'         => $link_html,
            ],
        );
    }

    public static function showValueToCheck($item, $params)
    {
        global $PLUGIN_HOOKS;

        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        $name = "check_value";

        switch ($params["item"]) {
            case 'urgency':
                \Ticket::dropdownUrgency(["name" => $name,'value' => $params['check_value']]);

                break;
            case 'ITILCategory_Metademands':
                $metademand = new Metademand();
                $metademand->getFromDB($params["plugin_metademands_metademands_id"]);
                $values = json_decode($metademand->fields['itilcategories_id']);

                $opt = [
                    'name' => $name,
                    'right' => 'all',
                    'value' => $params['check_value'],
                    'condition' => [(new ITILCategory())::getTable() . ".id" => $values],
                    'display' => true,
                    'used' => $already_used,
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
                    //               }
                    $params['item']::Dropdown([
                        "name" => $name,
                        "value" => $params['check_value'],
                        'used' => $already_used,
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
                    \Dropdown::showFromArray(
                        "check_value",
                        $elements,
                        ['value' => $params['check_value'], 'used' => $already_used],
                    );
                }
                break;
        }
    }

    public static function showParamsValueToCheck($params)
    {
        global $PLUGIN_HOOKS;

        $value = '';
        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            $value .= __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'urgency':
                    $value .= CommonITILObject::getUrgencyName($params['check_value']);
                    break;
                case 'ITILCategory_Metademands':
                    $pass = false;
                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            $new_drop = self::getPluginDropdownItilcategoryName($plug, $params['check_value']);
                            if (Plugin::isPluginActive($plug) && $new_drop != false) {
                                $value .= $new_drop;
                                $pass = true;
                            }
                        }
                    }

                    if (!$pass) {
                        $value .= \Dropdown::getDropdownName('glpi_itilcategories', $params['check_value']);
                    }

                    break;
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        $value .= \Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
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
        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_value_to_check.html.twig', [
            'value' => $value,
        ]);
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == Field::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } elseif ($check_value != $value
            && ($check_value != Field::$not_null && $check_value != 0)) {
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
            && ($fields['value'] === null || $fields['value'] === '' || $fields['value'] === 0)) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data, $itilcategories_id = 0)
    {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
        }
        if ($itilcategories_id > 0 && $data['item'] == "ITILCategory_Metademands") {
            $data['value'] = $itilcategories_id;
        }

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsMandatoryScript-dropdownmeta $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            // The value is encoded at the sink below: it travels POST -> session -> database ->
            // another user's session, and the `> 0` test degrades to a string comparison as soon
            // as it is not numeric. It cannot be tightened into an integer cast here, a
            // dropdown_meta value legitimately being an `<Itemtype>_<id>` pair.
            if (isset($data['value']) &&  $data['value'] > 0) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    $values = $data['value'];
                    if ($values) {
                        $pre_onchange .= "$('[name=\"$name\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").prop('checked', true).trigger('change');";
                    }
                } else {
                    $pre_onchange .= "$('[name=\"$name\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
                }
            }

            $onchange .= "$('[name=\"$name\"]').change(async function() {";

            $onchange .= "var tohide = {};";

            $display = 0;
            $compteur = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    $onchange .= "if ($fields_link in tohide) {
                        } else {
                            tohide[$fields_link] = true;
                        }";

                    if ($check_value['check_type_value'] == 2) {
                        $onchange .= "
                        let regex$compteur = $idc;
                        let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                        if(regex$compteur.test(val$compteur)) {
                            tohide[$fields_link] = false;
                        }
                        ";
                        $compteur += 1;
                    } else {
                        $onchange .= "if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0  || $idc == -1)) {
                            tohide[$fields_link] = false;
                        }";
                    }

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $fields_link;
                    }

                    $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            var id = '#metademands_wizard_red'+ key;
                            $(id).html('');
                            sessionStorage.setItem('hiddenlink$name', key);
                            " . FieldOption::resetMandatoryFieldsByField($name) . "
                            $('[name =\"field['+ key +']\"]').removeAttr('required');
                        } else {
                             var id = '#metademands_wizard_red'+ key;
                             var fieldid = 'field'+ key;
                             $(id).html('*');
                             $('[name =\"field[' + key + ']\"]').attr('required', 'required');
                             $('[name =\"field[' + key + '-2]\"]').attr('required', 'required');
                             //Special case Upload field
                                  sessionStorage.setItem('mandatoryfile$name', key);
                                 " . FieldOption::checkMandatoryFile($fields_link, $name) . "
                        }
                    });
              ";
                }
            }

            if ($display > 0) {
                $pre_onchange .= FieldOption::setMandatoryFieldsByField($id, $display);
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function taskScript($data, $itilcategories_id = 0)
    {
        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        if ($itilcategories_id > 0 && $data['item'] == "ITILCategory_Metademands") {
            $data['value'] = $itilcategories_id;
        }
        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('taskScript-dropdownmeta $id');";
        }
        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value']) &&  $data['value'] > 0) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    $values = $data['value'];
                    if ($values) {
                        $script2 .= "$('[name^=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").prop('checked', true).trigger('change');";
                    }
                } else {
                    $script2 .= "$('[name^=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
                }
            }

            $title = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = __(
                'Next',
                'metademands',
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

            $name = "field[" . $data["id"] . "]";
            if ($data["item"] == "ITILCategory_Metademands") {
                $name = "field_plugin_servicecatalog_itilcategories_id";
            }

            $script .= "$('[name=\"$name\"]').change(async function() {";
            $script .= "var tohide = {};";
            $compteur = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    $script .= "if ($tasks_id in tohide) {
                        } else {
                            tohide[$tasks_id] = true;
                        }
                        ";

                    if ($check_value['check_type_value'] == 2) {
                        $script .= "
                        let regex$compteur = new RegExp(" . json_encode($idc) . ");
                        let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                        if(regex$compteur.test(val$compteur)) {
                            tohide[$tasks_id] = false;
                        }
                        ";
                        $compteur += 1;
                    } else {
                        $script .= "if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0  || $idc == -1)) {
                            tohide[$tasks_id] = false;
                        }";
                    }

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
                                     type: 'POST',
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
                                     type: 'POST',
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
                    if (isset($data['custom_values'])
                        && is_array($data['custom_values'])
                        && count($data['custom_values']) > 0) {
                        $custom_values = $data['custom_values'];
                        foreach ($custom_values as $k => $custom_value) {
                            if ($custom_value['is_default'] == 1) {
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

    public static function fieldsHiddenScript($data, $itilcategories_id = 0)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
        }
        if ($itilcategories_id > 0 && $data['item'] == "ITILCategory_Metademands") {
            $data['value'] = $itilcategories_id;
        }
        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-dropdownmeta $id');";
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

            //Initialize default value - force change after onchange fonction
            //CANNOT DO THIS OR DEFINE ONLY AS CHECKED

            if ($data["display_type"] != self::BLOCK_DISPLAY) {
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

                //urgency..
                if (isset($data['default_values'])
                    && is_array($data['default_values'])
                    && count($data['default_values']) > 0) {
                    $default_values = $data['default_values'];

                    foreach ($default_values as $k => $default_value) {
                        $post_onchange .= "$('[name=\"field[" . $id . "]\"]').val('$default_value').trigger('change');";

                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                    $('[id-field =\"field" . $hidden_link . "-2\"]').hide();
                    $('[name=\"field[" . $hidden_link . "]\"]').removeAttr('required');";
                }
            }

            //Si la valeur est en session
            if (isset($data['value']) &&  $data['value'] > 0) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    $values = $data['value'];
                    if ($values) {
                        $pre_onchange .= "$('[id=\"field[" . $id . "][" . $values . "]\"]').prop('checked', true).trigger('change');";
                    }
                } else {
                    $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
                }
            }

            $onchange .= "$('[name=\"$name\"]').change(async function() {";

            $onchange .= "var tohide = {};";

            $display = 0;

            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $onchange .= " if ($hidden_link in tohide) {} else {tohide[$hidden_link] = true;}
                    ";
                }
            }

            $compteur = 0;

            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    if ($check_value['check_type_value'] == 2) {
                        $onchange .= "
                        let regex$compteur = new RegExp(" . json_encode($idc) . ");
                        let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                        if(regex$compteur.test(val$compteur)) {
                            tohide[$hidden_link] = false;
                        }
                        ";
                        $compteur += 1;
                    } else {
                        $onchange .= " if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$hidden_link] = false;
                        }";
                    }
                }
            }

            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_link;
                    }

                    $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            $('[id-field =\"field'+key+'-2\"]').hide();
                            sessionStorage.setItem('hiddenlink$name', key);
                            " . FieldOption::resetMandatoryFieldsByFieldForHidden($name) . "
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                            $('[name =\"field['+key+'-2]\"]').removeAttr('required');";
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
                            $('[id-field =\"field'+key+'-2\"]').show();
                        }
                    });
              ";
                }
            }

            if ($display > 0) {
                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                $pre_onchange .= FieldOption::setMandatoryFieldsByField($id, $display);
            }
            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function blocksHiddenScript($data, $itilcategories_id = 0)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
        }
        if ($itilcategories_id > 0  && $data['item'] == "ITILCategory_Metademands") {
            $data['value'] = $itilcategories_id;
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

                            if (is_array($childs_by_checkvalue)) {
                                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                    if ($idc == $k) {
                                        foreach ($childs_blocks as $childs) {
                                            $options = getAllDataFromTable(
                                                'glpi_plugin_metademands_fieldoptions',
                                                ['hidden_block' => $childs],
                                            );
                                            if (count($options) == 0) {
                                                $post_onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                            document.getElementById('ablock" . $childs . "').style.display = 'block';
                                            $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                             " . FieldOption::setMandatoryBlockFields(
                                                    $metaid,
                                                    $childs,
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //by default - hide all
            $pre_onchange .= FieldOption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $pre_onchange .= FieldOption::emptyAllblockbyDefault($check_values);
            }

            //Si la valeur est en session
            // The value is encoded at the sink below: it travels POST -> session -> database ->
            // another user's session, and the `> 0` test degrades to a string comparison as soon
            // as it is not numeric. It cannot be tightened into an integer cast here, a
            // dropdown_meta value legitimately being an `<Itemtype>_<id>` pair.
            if (isset($data['value']) &&  $data['value'] > 0) {
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    $values = $data['value'];
                    if ($values) {
                        $pre_onchange .= "$('[name=\"$name\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").prop('checked', true).trigger('change');";
                    }
                } else {
                    $pre_onchange .= "$('[name=\"$name\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
                }
            }

            $onchange .= "$('[name=\"$name\"]').change(async function() {";

            $onchange .= "var tohide = {};";
            $display = 0;
            $compteur = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_block'] as $hidden_block) {
                    $onchange .= "if ($hidden_block in tohide) {
                      } else {
                        tohide[$hidden_block] = true;
                      }";

                    if ($check_value['check_type_value'] == 2) {
                        $onchange .= "
                        let regex$compteur = new RegExp(" . json_encode($idc) . ");
                        let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                        if(regex$compteur.test(val$compteur)) {
                            tohide[$hidden_block] = false;
                        }
                        ";
                        $compteur += 1;
                    } else {
                        $onchange .= "if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0  || $idc == -1)) {
                        tohide[$hidden_block] = false;
                    }";
                    }

                    $onchange .= "$.each( tohide, function( key, value ) {
                    if (value == true) {
                       var id = 'ablock'+ key;
                        if (document.getElementById(id))
                        document.getElementById(id).style.display = 'none';
                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
                        $('[bloc-id =\"subbloc'+ key +'\"]').hide();
                        sessionStorage.setItem('hiddenbloc$name', key);
                        " . FieldOption::setEmptyBlockFields($name) . "";
                    $hidden = FieldOption::resetMandatoryBlockFields($name);
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

                    $hidden = FieldOption::setMandatoryBlockFields($metaid, $hidden_block);

                    $onchange .= "$hidden";
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $options = getAllDataFromTable(
                                        'glpi_plugin_metademands_fieldoptions',
                                        ['hidden_block' => $childs],
                                    );
                                    if (count($options) == 0) {
                                        $onchange .= "if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'block';
                                $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                $('[bloc-id =\"subbloc" . $childs . "\"]').show();";
                                    }
                                }
                            }
                        }
                    }
                    $onchange .= "}
                });
          ";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_block;
                    }

                    if ($data["item"] == "ITILCategory_Metademands") {
                        if (isset($_GET['itilcategories_id']) && $idc == (int) $_GET['itilcategories_id']) {
                            $pre_onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                        $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();
                          " . FieldOption::setMandatoryBlockFields($metaid, $hidden_block);
                        }
                    }
                }
            }
            if ($display > 0) {
                $pre_onchange .= "if (document.getElementById('ablock" . $display . "'))
                        document.getElementById('ablock" . $display . "').style.display = 'block';
                        $('[bloc-id =\"bloc" . $display . "\"]').show();
                        $('[bloc-id =\"subbloc" . $display . "\"]').show();";
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function checkConditions($data, $metaparams)
    {

        $submittitle   = $metaparams['submittitle'] ?? '';
        $nextsteptitle = $metaparams['nextsteptitle'] ?? '';
        $use_condition = $metaparams['use_condition'] ?? '';
        $show_rule     = $metaparams['show_rule'] ?? '';
        $show_button   = $metaparams['show_button'] ?? '';
        $use_richtext  = $metaparams['use_richtext'] ?? '';
        $richtext_id   = $metaparams['richtext_id'] ?? 0;

        $conditions = Condition::conditionsTab($data['plugin_metademands_metademands_id']);
        $condition_fields = [];
        foreach ($conditions as $cid => $condition) {
            $condition_fields[] = $condition['plugin_metademands_fields_id'];
        }

        if ($show_rule != Condition::SHOW_RULE_ALWAYS && in_array($data['id'], $condition_fields)) {

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
            $name = "field[" . $data["id"] . "]";
            if ($data["item"] == "ITILCategory_Metademands") {
                $name = "field_plugin_servicecatalog_itilcategories_id";
            }
            $onchange .= "$('[name=\"$name\"]').change(function() {";
            $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $onchange . '});',
            );
        }
    }

    public static function getFieldValue($field, $lang)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (!empty($field['custom_values'])
            && $field['item'] == 'other') {
            $custom_values = [];
            foreach ($field['custom_values'] as $val) {
                $translated = Field::displayField($field["id"], "custom" . $val['rank'], $lang);
                $custom_values[$val['id']] = $translated !== '' ? $translated : $val['name'];
            }
            return $custom_values[$field['value']] ?? "";
        } else {
            if ($field['value'] != 0) {
                switch ($field['item']) {
                    case 'ITILCategory_Metademands':
                        if (isset($PLUGIN_HOOKS['metademands'])) {
                            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                                $new_drop = self::getPluginDropdownItilcategoryName($plug, $field['value']);
                                if (Plugin::isPluginActive($plug) && $new_drop != false) {
                                    return $new_drop;
                                }
                            }
                        }
                        return \Dropdown::getDropdownName(
                            $dbu->getTableForItemType('ITILCategory'),
                            $field['value'],
                        );
                    case 'mydevices':
                        $splitter = explode("_", $field['value']);
                        if (count($splitter) == 2) {
                            $itemtype = $splitter[0];
                            $items_id = $splitter[1];
                        }
                        if (isset($items_id)) {
                            return \Dropdown::getDropdownName(
                                $dbu->getTableForItemType($itemtype),
                                $items_id,
                            );
                        }
                        return "";
                    case 'urgency':
                        return \Ticket::getUrgencyName($field['value']);
                    case 'impact':
                        return \Ticket::getImpactName($field['value']);
                    case 'priority':
                        return \Ticket::getPriorityName($field['value']);
                    default:
                        return \Dropdown::getDropdownName(
                            $dbu->getTableForItemType($field['item']),
                            $field['value'],
                        );
                }
            }
        }
        return "";
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
            $custom_values[0] = \Dropdown::EMPTY_VALUE;
            foreach ($field['custom_values'] as $val) {
                $translated = Field::displayField($field["id"], "custom" . $val['rank'], $lang);
                $custom_values[$val['id']] = $translated !== '' ? $translated : $val['name'];
            }
            if (isset($custom_values[$field['value']])) {
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= $label;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= htmlspecialchars((string) $custom_values[$field['value']], ENT_QUOTES, 'UTF-8');
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
                        $itemtype = count($splitter) == 2 ? $splitter[0] : null;
                        $items_id = count($splitter) == 2 ? $splitter[1] : null;
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
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getdropdownItilcategory'])) {
                    return $item->getdropdownItilcategory($opt);
                }
            }
        }
    }

    public static function getPluginDropdownItilcategoryName($plug, $opt)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getdropdownItilcategoryName'])) {
                    return $item->getdropdownItilcategoryName($opt);
                }
            }
        }
    }
}
