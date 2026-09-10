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

use Ajax;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Metademands\Wizard;
use GlpiPlugin\Resources\Resource;
use Group;
use Group_User;
use Html;
use Location;
use Session;
use User;
use UserCategory;
use UserTitle;

/**
 * Dropdownobject Class
 *
 **/
class Dropdownobject extends CommonDBTM
{
    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param int $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Glpi Object', 'metademands');
    }

    /**
     * Descriptors of the fields whose value follows the user selected in this dropdown.
     * Each entry tells which field to look for, the id prefix of the block to refresh,
     * the endpoint that renders it and the extra parameters that endpoint expects.
     *
     * @param array $data
     *
     * @return array
     */
    private static function getLinkedUserDescriptors($data)
    {
        return [
            ['type'     => "dropdown",
                'item'     => Location::getType(),
                'prefix'   => "location_user",
                'endpoint' => "ulocationUpdate.php",
                'extra'    => ['display_type' => $data['display_type'] ?? 0]],
            ['type'     => "dropdown",
                'item'     => UserTitle::getType(),
                'prefix'   => "title_user",
                'endpoint' => "utitleUpdate.php",
                'extra'    => []],
            ['type'     => "dropdown",
                'item'     => UserCategory::getType(),
                'prefix'   => "category_user",
                'endpoint' => "ucategoryUpdate.php",
                'extra'    => []],
            ['type'     => "dropdown_object",
                'item'     => Group::getType(),
                'prefix'   => "group_user",
                'endpoint' => "ugroupUpdate.php",
                'extra'    => []],
            ['type'     => "dropdown_object",
                'item'     => Entity::getType(),
                'prefix'   => "entity_user",
                'endpoint' => "uentityUpdate.php",
                'extra'    => ['readonly' => $data['readonly'] ?? 0]],
            ['type'     => "dropdown_meta",
                'item'     => "mydevices",
                'prefix'   => "mydevices_user",
                'endpoint' => "umydevicesUpdate.php",
                'extra'    => []],
            ['type'     => "dropdown_object",
                'item'     => User::getType(),
                'prefix'   => "manager_user",
                'endpoint' => "umanagerUpdate.php",
                'extra'    => []],
        ];
    }

    /**
     * Build the "toupdate" entries handed to User::dropdown(): each field linked to this
     * user field is reloaded from its own endpoint whenever the selection changes.
     *
     * @param array $data
     *
     * @return array
     */
    private static function getLinkedUserUpdates($data)
    {
        $field          = new Field();
        $fieldparameter = new FieldParameter();
        $toupdate       = [];

        foreach (self::getLinkedUserDescriptors($data) as $descriptor) {
            $linked_fields = $field->find([
                'type'                              => $descriptor['type'],
                'plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                'item'                              => $descriptor['item'],
            ]);

            foreach ($linked_fields as $linked_field) {
                if (!$fieldparameter->getFromDBByCrit([
                    'plugin_metademands_fields_id' => $linked_field['id'],
                    'link_to_user'                 => ['>', 0],
                ])) {
                    continue;
                }

                $linked_id = $fieldparameter->fields['plugin_metademands_fields_id'];

                $toupdate[] = [
                    'value_fieldname' => 'value',
                    'id_fielduser'    => $data['id'],
                    'to_update'       => $descriptor['prefix'] . $data['id'] . $linked_id,
                    'url'             => PLUGIN_METADEMANDS_WEBDIR . "/ajax/" . $descriptor['endpoint'],
                    'moreparams'      => array_merge(
                        ['users_id' => '__VALUE__', 'id_fielduser' => $data['id']],
                        $descriptor['extra'],
                        ['metademands_id' => $data['plugin_metademands_metademands_id']],
                    ),
                ];
            }
        }

        return $toupdate;
    }

    /**
     * Map the text fields fed by this user dropdown to the keys returned by
     * ajax/uTextFieldUpdate.php. Consumed by public/scripts/dropdownobject_linked_text_fields.js.
     *
     * @param array  $data
     * @param string $namefield
     *
     * @return array Empty when no text field is actually fed by this dropdown.
     */
    private static function getLinkedTextFieldsConfig($data, $namefield)
    {
        $field           = new Field();
        $field_parameter = new FieldParameter();
        $targets         = [];

        $text_fields = $field->find([
            'plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
            'type'                              => ['text', 'email', 'tel'],
        ]);

        foreach ($text_fields as $text_field) {
            $parameters = $field_parameter->find([
                'plugin_metademands_fields_id' => $text_field['id'],
                'link_to_user'                 => $data['id'],
            ]);

            foreach ($parameters as $parameter) {
                if (empty($parameter['used_by_ticket'])) {
                    continue;
                }

                $targets[] = [
                    'id_field'     => $namefield . $text_field['id'],
                    'response_key' => $parameter['used_by_ticket'],
                ];
            }
        }

        if (count($targets) === 0) {
            return [];
        }

        return [
            'url'     => PLUGIN_METADEMANDS_WEBDIR . "/ajax/uTextFieldUpdate.php",
            'targets' => $targets,
        ];
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $itilcategories_id)
    {

        $metademand = new Metademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        $field = "";
        switch ($data['item']) {
            case 'User':
                $userrand       = mt_rand();
                $toupdate       = [];
                $tooltip_script = "";

                if ($data['display_type'] == 1) {
                    $paramstooltip
                        = ['users_id' => '__VALUE__',
                            'fieldname' => $namefield,
                            'id_fielduser'   => $data['id'],
                            'display_type' => $data['display_type'],
                            'metademands_id' => $data['plugin_metademands_metademands_id'],
                            'default_use_id_requester' => $data['default_use_id_requester'] ?? 0,
                            'default_use_id_requester_supervisor' => $data['default_use_id_requester_supervisor'] ?? 0];

                    $toupdate[] = ['value_fieldname' => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update'    => "tooltip_user" . $data['id'],
                        'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                        'moreparams'   => $paramstooltip];

                    $tooltip_script = Ajax::updateItem(
                        "tooltip_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                        $paramstooltip,
                        "dropdown_" . $namefield . "[" . $data['id'] . "]" . $userrand,
                        false,
                    );
                }

                // Every field linked to this user field refreshes itself from its own
                // endpoint: core renders that JS out of the "toupdate" option below.
                $toupdate = array_merge($toupdate, self::getLinkedUserUpdates($data));

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
                    $options = FieldParameter::_unserialize($data['custom']);
                    if (isset($options['user_group']) && $options['user_group'] == 1) {
                        $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                        $group_user_data = Group_User::getUserGroups(Session::getLoginUserID(), $condition);

                        $requester_groups = [];
                        foreach ($group_user_data as $groups) {
                            $requester_groups[] = $groups['id'];
                        }
                        $right = "groups";
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

                if (Session::getCurrentInterface() != 'central') {
                    $opt['toadd'][Session::getLoginUserID()] = [
                        'id' => Session::getLoginUserID(),
                        'text' => getUserName(Session::getLoginUserID()),
                    ];
                }
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }

                $wrapper_id = "";
                if ($data['link_to_user'] > 0) {
                    $wrapper_id = "manager_user" . $data['link_to_user'] . $data['id'];

                    $fieldUser = new Field();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new FieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit([
                        'plugin_metademands_fields_id' => $fieldUser->fields['id'],
                    ])) {
                        if (empty($opt['value']) || $opt['value'] == 0) {
                            $opt['value'] = (isset($fieldparameter->fields['default_use_id_requester'])
                                && $fieldparameter->fields['default_use_id_requester'] == 1)
                                ? Session::getLoginUserID() : 0;
                        }

                        if (empty($opt['value']) || $opt['value'] == 0) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $opt['value'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }
                }

                $widget_html = User::dropdown($opt);
                if ($opt['readonly']) {
                    $widget_html .= Html::hidden($opt['name'], ['value' => $opt['value']]);
                }

                $update_script = "";
                if ($data['link_to_user'] > 0) {
                    $optAjax = $opt;
                    $optAjax['name'] = 'manager_user' . $data['link_to_user'] . $data['id'];
                    $optAjax['rand'] = '';
                    $optAjax['id_fielduser'] = $data['link_to_user'];
                    $optAjax['field'] = $opt['name'];
                    $optAjax['metademands_id'] = $data['plugin_metademands_metademands_id'];
                    $update_script = Ajax::commonDropdownUpdateItem($optAjax, false);
                }

                // The anchor is always rendered when the tooltip is enabled: it is the
                // target utooltipUpdate.php loads into on every change.
                $user_informations = "";
                if ($data['display_type'] == 1 && $opt['value'] > 0) {
                    $user_tooltip = new User();
                    if ($user_tooltip->getFromDB($opt['value'])) {
                        ob_start();
                        Wizard::showUserInformations($user_tooltip);
                        $user_informations = ob_get_clean();
                    }
                }

                $field = TemplateRenderer::getInstance()->render(
                    '@metademands/fields/dropdownobject_user.html.twig',
                    [
                        'tooltip_script'     => $tooltip_script,
                        'wrapper_id'         => $wrapper_id,
                        'widget_html'        => $widget_html,
                        'update_script'      => $update_script,
                        'with_tooltip'       => $data['display_type'] == 1,
                        'field_id'           => $data['id'],
                        'user_informations'  => $user_informations,
                        'linked_text_fields' => self::getLinkedTextFieldsConfig($data, $namefield),
                    ],
                );
                break;
            case 'Group':
                $field = "";
                $cond  = [];
                $_POST['field'] = $namefield . "[" . $data['id'] . "]";

                if ($data['link_to_user'] > 0) {
                    $fieldparameter            = new FieldParameter();
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['link_to_user']])) {
                        $_POST['value']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['value'])) {
                            $_POST['value'] = 0;
                        }
                    }

                    $_POST['groups_id'] = $value;
                    $fieldparameter            = new FieldParameter();
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['link_to_user']])) {
                        $_POST['users_id']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['users_id'])) {
                            $_POST['users_id'] = 0;
                        }
                    }
                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    $_POST['is_mandatory'] = $data['is_mandatory'] ?? 0;

                    // The endpoint echoes the dropdown for the selected user: capture it
                    // and let the template provide the input-group wrapper.
                    ob_start();
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/ugroupUpdate.php");
                    $group_html = ob_get_clean();

                    $field = TemplateRenderer::getInstance()->render(
                        '@metademands/fields/dropdownobject_linked_wrapper.html.twig',
                        [
                            'wrapper_id'  => "group_user" . $data['link_to_user'] . $data['id'],
                            'widget_html' => $group_html,
                        ],
                    );
                } else {
                    $name = $namefield . "[" . $data['id'] . "]";

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

                if (!empty($data['custom_values'])) {
                    $options = FieldParameter::_unserialize($data['custom_values']);
                    foreach ($options as $k => $val) {
                        if (!empty($ret = Field::displayField($data["id"], "custom" . $k))) {
                            $options[$k] = $ret;
                        }
                    }
                    foreach ($options as $type_group => $val) {
                        $cond[$type_group] = $val;
                    }
                }

                if ($data['item'] == 'Ticket') {
                    $opt = ['value'     => $value,
                        'entity'    => $_SESSION['glpiactiveentities'],
                        'name'      => $namefield . "[" . $data['id'] . "]",
                        'displaywith' => ['id'],
                        'condition' => $cond,
                        'display'   => false];
                } else {
                    $opt = ['value'     => $value,
                        'entity'    => $_SESSION['glpiactiveentities'],
                        'name'      => $namefield . "[" . $data['id'] . "]",
                        'condition' => $cond,
                        'display'   => false];
                }

                if (isset($data['readonly']) && $data['readonly'] == 1 && $data['item'] == "Entity") {
                    $opt['readonly'] = true;
                    if ($data['link_to_user'] == 0) {
                        $field .= Html::hidden($namefield . "[" . $data['id'] . "]", ['value' => $value]);
                    }
                }
                if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                if (!getItemForItemtype($data['item'])) {
                    break;
                }
                if ($data['item'] == "Entity") {
                    if ($data['link_to_user'] > 0) {
                        $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                        $_POST['entities_id'] = $value;
                        $fieldUser             = new Field();
                        $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                            'type' => "dropdown_object",
                            'item' => User::getType()]);

                        $_POST['users_id']        = (isset($fieldUser->fields['default_use_id_requester'])
                            && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                        $_POST['id_fielduser'] = $data['link_to_user'];
                        $_POST['fields_id']    = $data['id'];
                        $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                        $_POST['readonly'] = $data['readonly'];

                        // Same contract as the Group branch: the endpoint echoes the
                        // dropdown, the template provides the wrapper.
                        ob_start();
                        include(PLUGIN_METADEMANDS_DIR . "/ajax/uentityUpdate.php");
                        $entity_html = ob_get_clean();

                        $field = TemplateRenderer::getInstance()->render(
                            '@metademands/fields/dropdownobject_linked_wrapper.html.twig',
                            [
                                'wrapper_id'  => "entity_user" . $data['link_to_user'] . $data['id'],
                                'widget_html' => $entity_html,
                            ],
                        );
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
                    if ($data['item'] == Resource::class) {
                        $opt['showHabilitations'] = true;
                    }

                    $field = Dropdown::show($data['item'], $opt);
                }
                break;
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_widget.html.twig',
            ['widget_html' => $field],
        );
    }

    public static function showFieldCustomValues($values) {}

    public static function showFieldParameters($params): string
    {
        $rows = [];

        if ($params['item'] == 'User') {
            $custom_values = FieldParameter::_unserialize($params['custom_values']);
            $user_group = $custom_values['user_group'] ?? 0;

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

            ob_start();
            \Dropdown::showYesNo('display_type', $params['display_type']);
            $display_type_html = ob_get_clean();

            $rows[] = [
                ['label' => __('Link this to a user field', 'metademands'), 'html' => $link_to_user_html],
                ['label' => __('Show a identity card of user', 'metademands'), 'html' => $display_type_html],
            ];

            ob_start();
            \Dropdown::showYesNo('user_group', $user_group);
            $user_group_html = ob_get_clean();

            if ($params['object_to_create'] == 'Ticket') {
                ob_start();
                \Dropdown::showYesNo('used_by_child', $params['used_by_child']);
                $used_by_child_html = ob_get_clean();
                $rows[] = [
                    ['label' => __('Only users of my groups', 'metademands'), 'html' => $user_group_html],
                    ['label' => __('Use this field for child ticket field', 'metademands'), 'html' => $used_by_child_html],
                ];
            } else {
                $rows[] = [
                    ['label' => __('Only users of my groups', 'metademands'), 'html' => $user_group_html],
                    ['colspan' => 2, 'html' => ''],
                ];
            }

            ob_start();
            \Dropdown::showYesNo('default_use_id_requester', $params['default_use_id_requester']);
            $default_use_id_requester_html = ob_get_clean();

            ob_start();
            \Dropdown::showYesNo('default_use_id_requester_supervisor', $params['default_use_id_requester_supervisor']);
            $default_use_id_requester_supervisor_html = ob_get_clean();

            $rows[] = [
                ['label' => __('Use id of requester by default', 'metademands'), 'html' => $default_use_id_requester_html],
                ['label' => __('Use id of supervisor requester by default', 'metademands'), 'html' => $default_use_id_requester_supervisor_html],
            ];

            ob_start();
            \Dropdown::showYesNo('readonly', $params['readonly']);
            $readonly_html = ob_get_clean();

            $decode = "";
            if (!is_array($params['informations_to_display'])) {
                $decode = json_decode($params['informations_to_display']);
            }
            $values = empty($decode) ? ['full_name'] : $decode;
            $informations = [
                "full_name" => __('Complete name'),
                "realname"  => __('Surname'),
                "firstname" => __('First name'),
                "name"      => __('Login'),
                "email"     => _n('Email', 'Emails', 1),
            ];
            $informations_html = \Dropdown::showFromArray('informations_to_display', $informations, [
                'values'   => $values,
                'display'  => false,
                'multiple' => true,
            ]);

            $rows[] = [
                ['label' => __('Read-Only', 'metademands'), 'html' => $readonly_html],
                ['label' => __('Informations to display in ticket and PDF', 'metademands'), 'html' => $informations_html],
            ];
        } elseif ($params["item"] == "Group") {
            $custom_values = FieldParameter::_unserialize($params['custom_values']);
            $is_assign = $custom_values['is_assign'] ?? 0;
            $is_watcher = $custom_values['is_watcher'] ?? 0;
            $is_requester = $custom_values['is_requester'] ?? 0;
            $user_group = $custom_values['user_group'] ?? 0;

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

            if ($params['object_to_create'] == 'Ticket') {
                ob_start();
                \Dropdown::showYesNo('used_by_child', $params['used_by_child']);
                $used_by_child_html = ob_get_clean();
                $rows[] = [
                    ['label' => __('Link this to a user field', 'metademands'), 'html' => $link_to_user_html],
                    ['label' => __('Use this field for child ticket field', 'metademands'), 'html' => $used_by_child_html],
                ];
            } else {
                $rows[] = [
                    ['label' => __('Link this to a user field', 'metademands'), 'html' => $link_to_user_html],
                    ['colspan' => 2, 'html' => ''],
                ];
            }

            ob_start();
            \Dropdown::showYesNo('is_requester', $is_requester);
            $is_requester_html = ob_get_clean();

            ob_start();
            \Dropdown::showYesNo('is_watcher', $is_watcher);
            $is_watcher_html = ob_get_clean();

            $rows[] = [
                ['label' => __('Requester'), 'html' => $is_requester_html],
                ['label' => __('Observer'), 'html' => $is_watcher_html],
            ];

            ob_start();
            \Dropdown::showYesNo('is_assign', $is_assign);
            $is_assign_html = ob_get_clean();

            ob_start();
            \Dropdown::showYesNo('user_group', $user_group);
            $user_group_html = ob_get_clean();

            $rows[] = [
                ['label' => __('Assigned'), 'html' => $is_assign_html],
                ['label' => __('My groups'), 'html' => $user_group_html],
            ];
        }

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_dropdownobject.html.twig',
            ['rows' => $rows],
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
        $already_used = [];
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
                    'used' => $already_used,
                ]);
                break;
            case 'Group':
                $name = "check_value";
                $cond = [];
                if (!empty($params['custom_values'])) {
                    $options = FieldParameter::_unserialize($params['custom_values']);
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
                    'used' => $already_used,
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
                        "value" => $params['check_value'],
                        'used' => $already_used,
                        'toadd' => ['-1' => __('Not null value', 'metademands')]]);
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
        $value = '';
        if ($params['check_value'] == -1 || $params['check_value'] == 0) {
            $value .= __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'User':
                    $value .= getUserName($params['check_value'], 0, true);
                    break;
                case 'Group':
                    $value .= \Dropdown::getDropdownName('glpi_groups', $params['check_value']);
                    break;
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        $value .= \Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
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
                            $value .= $elements[$params['check_value']] ?? "";
                        } else {
                            $elements[-1] = __('Not null value', 'metademands');
                            if (is_array(json_decode($params['custom_values'], true))) {
                                $elements += json_decode($params['custom_values'], true);
                            }
                            foreach ($elements as $key => $val) {
                                $elements[$key] = urldecode($val);
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
            $onchange = "console.log('fieldsMandatoryScript-dropdownobject $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value']) &&  $data['value'] > 0) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
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
                            }
                            ";


                    if ($check_value['check_type_value'] == 2) {
                        $onchange .= "
                        let regex$compteur = new RegExp(" . json_encode((string) $idc) . ");
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

                if ($display > 0) {
                    $pre_onchange .= FieldOption::setMandatoryFieldsByField($id, $display);
                }

            }
            $onchange .= "});";
            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});',
            );
        }
    }

    public static function taskScript($data)
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

        $title = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
        $nextsteptitle = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


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
        if (count($check_values) > 0) {
            $name = "field[" . $data["id"] . "]";
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
                        let regex$compteur = new RegExp(" . json_encode((string) $idc) . ");
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
                    if (is_array(FieldParameter::_unserialize($data['default']))) {
                        $default_values = FieldParameter::_unserialize($data['default']);

                        foreach ($default_values as $k => $v) {
                            if ($v == 1) {
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

    public static function fieldsHiddenScript($data)
    {


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
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    if (isset($data['default_values'])
                        && is_array(FieldParameter::_unserialize($data['default_values']))) {
                        $default_values = FieldParameter::_unserialize($data['default_values']);

                        foreach ($default_values as $k => $v) {
                            if ($v == 1) {
                                if ($idc == $k) {
                                    $post_onchange .= "$('[name=\"field[" . $id . "]\"]').prop('checked', true).trigger('change');";
                                }
                            }
                        }
                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                    $('[id-field =\"field" . $hidden_link . "-2\"]').hide();";
                }
            }


            //Si la valeur est en session
            if (isset($data['value']) &&  $data['value'] > 0) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
            }

            if (count($check_values) > 0) {
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
                                let regex$compteur = new RegExp(" . json_encode((string) $idc) . ");
                                let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                                if(regex$compteur.test(val$compteur)) {
                                    tohide[$hidden_link] = false;
                                }
                                ";
                            $compteur += 1;
                        } else {
                            $onchange .= "if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0  || $idc == -1)) {
                            tohide[$hidden_link] = false;
                        }";
                        }
                    }
                }

                foreach ($check_values as $idc => $check_value) {
                    foreach ($check_value['hidden_link'] as $hidden_link) {

                        //if reload form
                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_link;
                        }
                        //Obsolete ?
                        //                    if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                        //                        if (Session::getLoginUserID() == $idc) {
                        //                            $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                        //                        }
                        //                    }

                        $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            $('[id-field =\"field'+key+'-2\"]').hide();
                            sessionStorage.setItem('hiddenlink$name', key);
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                            $('[name =\"field['+key+'-2]\"]').removeAttr('required');
                            " . FieldOption::resetMandatoryFieldsByFieldForHidden($name);

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
        if (count($check_values) > 0) {
            $script .= "$('[name=\"$name\"]').change(async function() {";

            $script .= "var tohide = {}; answer = false;";

            //by default - hide all
            $script2 .= FieldOption::hideAllblockbyDefault($data);

            $script2 .= FieldOption::emptyAllblockbyDefault($check_values);

            $compteur = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_block'] as $hidden_block) {
                    $blocks_idc = [];

                    if ($check_value['check_type_value'] == 2) {
                        $script .= "
                       let regex$compteur = new RegExp(" . json_encode((string) $idc) . ");
                        let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                        if(regex$compteur.test(val$compteur)) {
                            ";
                        $compteur += 1;
                    } else {
                        $script .= "if ($(this).val() == $idc || $idc == -1 ) {";
                    }

                    //specific for radio / dropdowns - one value
                    $script .= FieldOption::hideAllblockbyDefault($data);

                    $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
                document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
                $('[bloc-id =\"subbloc'+$hidden_block+'\"]').show();";
                    $script .= FieldOption::setMandatoryBlockFields($metaid, $hidden_block);

                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $options = getAllDataFromTable(
                                        'glpi_plugin_metademands_fieldoptions',
                                        ['hidden_block' => $childs],
                                    );
                                    if (count($options) == 0) {
                                        $script .= "if (document.getElementById('ablock" . $childs . "'))
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

                    if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                        $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                        if (is_array($session_value)) {
                            foreach ($session_value as $k => $fieldSession) {
                                if ($fieldSession == $idc && $hidden_block > 0) {
                                    $script2 .= "if (document.getElementById('ablock" . $hidden_block . "'))
                                            document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                                            $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                            $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();";
                                }
                            }
                        } else {
                            if ($session_value == $idc && $hidden_block > 0) {
                                $script2 .= "if (document.getElementById('ablock" . $hidden_block . "'))
                                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                                        $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                        $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();";
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
                                $script2 .= "if (document.getElementById('ablock" . $hidden_block . "'))
                                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                                        $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                        $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();";
                            }
                        }
                    }

                    $script .= " }";

                    if ($check_value['check_type_value'] == 2) {
                        $script .= "
                            let regex$compteur = new RegExp(" . json_encode((string) $idc) . ");
                        let val$compteur = $('[name=\"$name\"] option:selected').text().replaceAll('" . \Dropdown::EMPTY_VALUE . "', '');

                        if(!regex$compteur.test(val$compteur)) {
                            ";
                        $compteur += 1;
                    } else {
                        $script .= "if ($(this).val() != $idc) {";
                    }


                    //                    if (is_array($blocks_idc) && count($blocks_idc) > 0) {
                    //                        foreach ($blocks_idc as $k => $block_idc) {
                    //                            $script .= "if (document.getElementById('ablock" . $block_idc . "'))
                    //                                    document.getElementById('ablock" . $block_idc . "').style.display = 'none';
                    //                                    $('[bloc-id =\"bloc" . $block_idc . "\"]').hide();
                    //                                    $('[bloc-id =\"subbloc" . $block_idc . "\"]').hide();";
                    //                        }
                    //                    }
                    $script .= " }";

                    $script .= "if ($(this).val() == 0 || $(this).val() != $idc) {";
                    $script .= FieldOption::hideAllblockbyDefault($data);
                    $script .= " }";
                }
            }

            $script .= "});";

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
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
            $onchange .= "$('[name=\"$name\"]').change(function() {";
            $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $onchange . '});',
            );
        }
    }

    public static function getFieldValue($field)
    {

        $dbu = new DbUtils();
        switch ($field['item']) {
            case 'User':
                return getUserName($field['value'], 0, true);
            default:
                return \Dropdown::getDropdownName(
                    $dbu->getTableForItemType($field['item']),
                    $field['value'],
                );
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {

        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;

        if ($field['value'] != 0) {
            switch ($field['item']) {
                case 'User':
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                    }
                    $result[$field['rank']]['content'] .= $label;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }

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
                            $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
                        }
                        $result[$field['rank']]['content'] .= self::getFieldValue($field);
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                    } else {
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
                        }
                        $result[$field['rank']]['content'] .= $content;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                    }

                    break;
                default:
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                    }
                    $result[$field['rank']]['content'] .= $label;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
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
