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
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTask;
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

                // The selected id descends straight from $_POST['field'], carried across
                // reloads by $_SESSION['plugin_metademands'][...]['fields'], so it may name
                // any user of the instance. Replay the rule ajax/utooltipUpdate.php carries
                // -- the caller's own supervisor, or anyone Config::canCurrentUserViewRequester()
                // allows -- and drop the value outright when it fails: the dropdown label and
                // the tooltip below both read $opt['value'], and clearing it also keeps the
                // illegitimate id from surviving down to the ticket creation.
                if ((int) $opt['value'] > 0) {
                    $me            = new User();
                    $my_supervisor = 0;
                    if ($me->getFromDB(Session::getLoginUserID())) {
                        $my_supervisor = (int) ($me->fields['users_id_supervisor'] ?? 0);
                    }

                    if (
                        (int) $opt['value'] !== $my_supervisor
                        && !Config::canCurrentUserViewRequester((int) $opt['value'])
                    ) {
                        $opt['value'] = 0;
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
                $info_user = null;
                // $opt['value'] has been cleared above when the caller may not see that
                // user, so the tooltip is only ever built for a target they may view.
                if ($data['display_type'] == 1 && (int) $opt['value'] > 0) {
                    $user_tooltip = new User();
                    if ($user_tooltip->getFromDB((int) $opt['value'])) {
                        $info_user = $user_tooltip;
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
                        'info_user'          => $info_user,
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
        $custom_values = [];
        $link_to_user = [];
        $used_by_child = [];

        if ($params['item'] == 'User' || $params['item'] == 'Group') {
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
            $link_to_user = [
                'label'    => __('Link this to a user field', 'metademands'),
                'name'     => 'link_to_user',
                'value'    => $params['link_to_user'],
                'elements' => $arrayAvailable,
            ];

            $custom_values = FieldParameter::_unserialize($params['custom_values']);
            $used_by_child = $params['object_to_create'] == 'Ticket'
                ? ['label' => __('Use this field for child ticket field', 'metademands'), 'name' => 'used_by_child', 'value' => $params['used_by_child']]
                : ['empty' => true];
        }

        if ($params['item'] == 'User') {
            $rows[] = [
                $link_to_user,
                ['label' => __('Show a identity card of user', 'metademands'), 'name' => 'display_type', 'value' => $params['display_type']],
            ];
            $rows[] = [
                ['label' => __('Only users of my groups', 'metademands'), 'name' => 'user_group', 'value' => $custom_values['user_group'] ?? 0],
                $used_by_child,
            ];
            $rows[] = [
                ['label' => __('Use id of requester by default', 'metademands'), 'name' => 'default_use_id_requester', 'value' => $params['default_use_id_requester']],
                ['label' => __('Use id of supervisor requester by default', 'metademands'), 'name' => 'default_use_id_requester_supervisor', 'value' => $params['default_use_id_requester_supervisor']],
            ];

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

            $rows[] = [
                ['label' => __('Read-Only', 'metademands'), 'name' => 'readonly', 'value' => $params['readonly']],
                [
                    'label'    => __('Informations to display in ticket and PDF', 'metademands'),
                    'name'     => 'informations_to_display',
                    'value'    => $values,
                    'elements' => $informations,
                    'multiple' => true,
                ],
            ];
        } elseif ($params["item"] == "Group") {
            $rows[] = [$link_to_user, $used_by_child];
            $rows[] = [
                ['label' => __('Requester'), 'name' => 'is_requester', 'value' => $custom_values['is_requester'] ?? 0],
                ['label' => __('Observer'), 'name' => 'is_watcher', 'value' => $custom_values['is_watcher'] ?? 0],
            ];
            $rows[] = [
                ['label' => __('Assigned'), 'name' => 'is_assign', 'value' => $custom_values['is_assign'] ?? 0],
                ['label' => __('My groups'), 'name' => 'user_group', 'value' => $custom_values['user_group'] ?? 0],
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

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => true,
            'with_tech_group' => true,
            'content'         => $cell_content,
        ];

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
        return $value;
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
        $name = 'field[' . $data['id'] . ']';
        if ($data['item'] == 'ITILCategory_Metademands') {
            $name = 'field_plugin_servicecatalog_itilcategories_id';
        }
        $options = ['any' => [0, -1]];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayMandatoryTrigger($data, ['name' => $name, 'match' => 'exact'], 'select', $options);
    }

    public static function taskScript($data)
    {
        $options = [
            'any'        => [0, -1],
            'defaults'   => MetademandTask::getDefaultValues($data['default'] ?? ''),
            'next_title' => 'savenext',
        ];
        $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? null;
        if (is_array($session_value)) {
            foreach ($session_value as $value) {
                if ($value > 0) {
                    $options['restore'] = ['val' => (string) $value];
                }
            }
        }
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact'], 'select', $options);
    }

    public static function fieldsHiddenScript($data)
    {
        $options = ['any' => [0, -1]];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact'], 'select', $options);
    }

    public static function blocksHiddenScript($data)
    {
        $options = ['any' => [-1]];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
        }
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact'], 'select', $options);
    }

    public static function checkConditions($data, $metaparams)
    {
        Condition::displayTrigger($data, $metaparams, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact']);
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

    public static function displayFieldItems(&$result, $formatAsTable, $title_style, $label, $field, $return_value, $lang, $is_order = false)
    {

        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;

        if ($field['value'] != 0) {
            switch ($field['item']) {
                case 'User':
                    $item = new $field['item']();
                    $content = "";
                    $information = json_decode($field['informations_to_display']);

                    // legacy support
                    if (empty($information)) {
                        $information = ['full_name'];
                    }

                    if ($item->getFromDB($field['value'])) {
                        foreach (Dropdownmultiple::getUserInformations($item, $information) as $text) {
                            $content .= $text . " ";
                        }
                    }
                    $result[$field['rank']]['content'] .= Field::renderContentCells(
                        (bool) $formatAsTable,
                        (string) $title_style,
                        [[
                            'label'   => (string) $label,
                            'value'   => empty($content) ? (string) self::getFieldValue($field) : $content,
                            'colspan' => $colspan,
                        ]],
                    );

                    break;
                default:
                    $result[$field['rank']]['content'] .= Field::renderContentCells(
                        (bool) $formatAsTable,
                        (string) $title_style,
                        [[
                            'label'   => (string) $label,
                            'value'   => (string) self::getFieldValue($field),
                            'colspan' => $colspan,
                        ]],
                    );

                    break;
            }
        }

        return $result;
    }
}
