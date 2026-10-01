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
use DropdownTranslation;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Resources\Resource;
use Location;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTask;
use Session;
use User;
use UserCategory;
use UserTitle;

/**
 * Dropdown Class
 *
 **/
class Dropdown extends CommonDBTM
{
    public const CLASSIC_DISPLAY = 0;
    public const SPLITTED_DISPLAY = 1;
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
        return __('Dropdown');
    }

    public static function getLocations(
        $entities_id = 0,
        int $root_items_id = 0,
        int $location_depth = 0
    ) {
        /** @var DBmysql $DB */
        global $DB;

        // Build entity restriction
        $entity_criteria = getEntitiesRestrictCriteria(
            'glpi_locations',
            '',
            $entities_id,
            true,
        );

        $locations = [];
        $locations[] = [
            'id' => 0,
            'name' => \Dropdown::EMPTY_VALUE,
            'locations_id' => 0,
        ];
        foreach (
            /** @phpstan-ignore-next-line */
            $DB->request([
                'FROM' => Location::getTable(),
                'WHERE' => $entity_criteria,
            ]) as $location
        ) {
            $location['name'] = DropdownTranslation::getTranslatedValue(
                $location['id'],
                Location::class,
                'name',
                $_SESSION['glpilanguage'],
            ) ?: $location['name'];
            $locations[$location['id']] = $location;
        }
        uasort($locations, function ($a, $b) {
            return $a['name'] <=> $b['name'];
        });

        $locs = self::buildLocationTree($locations, $root_items_id, $location_depth);

        return $locs;
    }

    public static function buildLocationTree(array $locations, int $root_items_id = 0, int $location_depth = 0)
    {
        $indexed = [];
        foreach ($locations as $loc) {
            $indexed[$loc['id']] = $loc + ['children' => []];
        }

        foreach ($indexed as &$loc) {
            if ($loc['locations_id'] && isset($indexed[$loc['locations_id']])) {
                $indexed[$loc['locations_id']]['children'][] = &$loc;
            }
        }
        unset($loc);

        if ($root_items_id > 0 && isset($indexed[$root_items_id])) {
            $result = [];
            foreach ($indexed[$root_items_id]['children'] as $child) {
                $result[$child['name']] = self::transformNode($child, $location_depth, 1);
            }
            return $result;
        }

        $roots = array_filter($indexed, fn($loc) => $loc['locations_id'] == 0);
        $result = [];

        foreach ($roots as $root) {
            $result[$root['name']] = self::transformNode($root, $location_depth, 1);
        }

        return $result;
    }

    public static function transformNode($node, int $location_depth = 0, int $current_depth = 1)
    {
        // If depth limit reached, expose node as a leaf (selectable, no sub-levels shown)
        $depth_reached = $location_depth > 0 && $current_depth >= $location_depth;

        if (empty($node['children']) || $depth_reached) {
            return [$node['id'] => $node['name']];
        }

        $result = [];

        foreach ($node['children'] as $child) {
            $child_depth_reached = $location_depth > 0 && ($current_depth + 1) >= $location_depth;
            if (empty($child['children']) || $child_depth_reached) {
                $result[$child['id']] = $child['name'];
            } else {
                $result[$child['name']] = self::transformNode($child, $location_depth, $current_depth + 1);
            }
        }
        return $result;
    }

    /**
     * Cascading location selector. Returns its markup instead of printing it: the only
     * caller concatenates the result into the field it is building, so the legacy `echo`
     * pushed the selector out of that field and ahead of it.
     *
     * @param array $opt
     *
     * @return string
     */
    public static function locationDropdown($opt)
    {
        $root_items_id  = (int) ($opt['root_items_id'] ?? 0);
        $location_depth = (int) ($opt['location_depth'] ?? 0);
        $locations      = self::getLocations($_SESSION['glpiactiveentities'], $root_items_id, $location_depth);
        $name           = $opt['name'];
        $id             = $opt['fields_id'];


        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_location_chained.html.twig',
            [
                'name'        => $name,
                'id'          => $id,
                'is_required' => !empty($opt['required']),
                'chained'     => [
                    'data'     => $locations,
                    'selected' => (string) $opt['value'],
                ],
            ],
        );
    }


    public static function showWizardField($data, $namefield, $value, $on_order, $itilcategories_id)
    {

        $metademand = new Metademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        $field = "";

        $opt = ['value'     => $value,
            'entity'    => $_SESSION['glpiactiveentities'],
            'name'      => $namefield . "[" . $data['id'] . "]",
            'display'   => false,
            //                    'width' => '400px'
        ];
        if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
            $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
        }
        //        if (!($item = getItemForItemtype($data['item']))) {
        //            break;
        //        }

        switch ($data['item']) {
            case "Location" :
                if ($data['link_to_user'] > 0) {
                    // The included endpoint prints the field: capture it and let the template
                    // own the input-group wrapper, which two distant `echo` used to open and close.
                    ob_start();
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['locations_id'] = $value;
                    $fieldUser             = new Field();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new FieldParameter();
                    if (isset($fieldUser->fields['id'])
                        && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {

                        $_POST['users_id']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['users_id'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['users_id'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['display_type']    = $data['display_type'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    if ($data['is_mandatory'] == 1) {
                        $_POST['is_mandatory'] = 1;
                    }
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/ulocationUpdate.php");
                    $field .= TemplateRenderer::getInstance()->render(
                        '@metademands/fields/field_input_group.html.twig',
                        [
                            'id'      => 'location_user' . $data['link_to_user'] . $data['id'],
                            'content' => ob_get_clean(),
                        ],
                    );
                } else {
                    $options['name']    = $namefield . "[" . $data['id'] . "]";
                    $options['width']    = "400px";
                    $options['display'] = false;
                    $options['display_type'] = $data['display_type'];
                    if ($data['is_mandatory'] == 1) {
                        $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }
                    //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                    $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $root_items_id  = (int) ($data['root_items_id'] ?? 0);
                        $location_depth = (int) ($data['location_depth'] ?? 0);
                        $allowed_ids = null;
                        if ($root_items_id > 0) {
                            $dbu = new DbUtils();
                            $sons = $dbu->getSonsOf(Location::getTable(), $root_items_id);
                            unset($sons[$root_items_id]);
                            $allowed_ids = array_keys($sons);
                        }
                        if ($location_depth > 0) {
                            global $DB;
                            $root_level = 0;
                            if ($root_items_id > 0) {
                                $row = $DB->request([
                                    'SELECT' => ['level'],
                                    'FROM'   => Location::getTable(),
                                    'WHERE'  => ['id' => $root_items_id],
                                ])->current();
                                $root_level = (int) ($row['level'] ?? 0);
                            }
                            $max_level = $root_level + $location_depth;
                            $depth_ids = [];
                            foreach ($DB->request([
                                'SELECT' => ['id'],
                                'FROM'   => Location::getTable(),
                                'WHERE'  => ['level' => ['<=', $max_level]],
                            ]) as $row) {
                                $depth_ids[] = $row['id'];
                            }
                            $allowed_ids = ($allowed_ids !== null)
                                ? array_values(array_intersect($allowed_ids, $depth_ids))
                                : $depth_ids;
                        }
                        if ($allowed_ids !== null) {
                            $options['condition'] = ['id' => $allowed_ids];
                        }
                        $field            .= Location::dropdown($options);
                    } else {
                        $opt['value']          = $value;
                        $opt['fields_id']      = $_POST['fields_id'] ?? $data['id'];
                        $opt['required']       = ($data['is_mandatory'] == 1 ? "required" : "");
                        $opt['root_items_id']  = (int) ($data['root_items_id'] ?? 0);
                        $opt['location_depth'] = (int) ($data['location_depth'] ?? 0);
                        $field .= self::locationDropdown($opt);
                    }
                }
                break;

            case "UserTitle" :
                if ($data['link_to_user'] > 0) {
                    // The included endpoint prints the field: capture it and let the template
                    // own the input-group wrapper, which two distant `echo` used to open and close.
                    ob_start();
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['fields_id']    = $data['id'];
                    $_POST['usertitles_id'] = $value;
                    $fieldUser             = new Field();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new FieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {
                        $_POST['users_id']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['users_id'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['users_id'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    if ($data['is_mandatory'] == 1) {
                        $_POST['is_mandatory'] = 1;
                    }
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/utitleUpdate.php");
                    $field .= TemplateRenderer::getInstance()->render(
                        '@metademands/fields/field_input_group.html.twig',
                        [
                            'id'      => 'title_user' . $data['link_to_user'] . $data['id'],
                            'content' => ob_get_clean(),
                        ],
                    );
                } else {
                    $options['name']    = $namefield . "[" . $data['id'] . "]";
                    $options['width']    = "400px";
                    $options['display'] = false;
                    if ($data['is_mandatory'] == 1) {
                        $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }
                    //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                    $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                    $field            .= UserTitle::dropdown($options);
                }
                break;
            case "UserCategory" :
                if ($data['link_to_user'] > 0) {
                    // The included endpoint prints the field: capture it and let the template
                    // own the input-group wrapper, which two distant `echo` used to open and close.
                    ob_start();
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['usercategories_id'] = $value;
                    $fieldUser             = new Field();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new FieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {
                        $_POST['users_id']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['users_id'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['users_id'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    if ($data['is_mandatory'] == 1) {
                        $_POST['is_mandatory'] = 1;
                    }
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/ucategoryUpdate.php");
                    $field .= TemplateRenderer::getInstance()->render(
                        '@metademands/fields/field_input_group.html.twig',
                        [
                            'id'      => 'category_user' . $data['link_to_user'] . $data['id'],
                            'content' => ob_get_clean(),
                        ],
                    );
                } else {
                    $options['name']    = $namefield . "[" . $data['id'] . "]";
                    $options['width']    = "400px";
                    $options['display'] = false;
                    if ($data['is_mandatory'] == 1) {
                        $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }
                    //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                    $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                    $field            .= UserCategory::dropdown($options);
                }
                break;
            default:
                if ($data["display_type"] == self::BLOCK_DISPLAY) {
                    $classfield = getItemForItemtype($data["item"]);
                    if ($classfield instanceof CommonDBTM) {
                        // Keep the boundary of the standard rendering below ('entity' =>
                        // active entities): a bare find() would list every row of the
                        // table, whatever its entity, to anyone filling the form.
                        $criteria = [];
                        if ($classfield->isEntityAssign()) {
                            $criteria += getEntitiesRestrictCriteria(
                                $classfield->getTable(),
                                '',
                                $_SESSION['glpiactiveentities'] ?? [],
                                $classfield->maybeRecursive(),
                            );
                        }
                        if ($classfield->maybeDeleted()) {
                            $criteria['is_deleted'] = 0;
                        }
                        if ($classfield->maybeTemplate()) {
                            $criteria['is_template'] = 0;
                        }
                        $custom_values = $classfield->find($criteria);

                        $field = "";
                        if (!empty($custom_values)) {
                            // Option label is escaped by the template, the comment sanitized there.
                            // The field-level icon is shared.
                            $options = [];
                            foreach ($custom_values as $key => $label) {
                                $options[] = [
                                    'key'        => $key,
                                    'name'       => $label['name'],
                                    'is_checked' => isset($value) && $value == $key,
                                    'comment'    => (string) $label['comment'],
                                ];
                            }

                            $field = TemplateRenderer::getInstance()->render(
                                '@metademands/fields/field_dropdown_block.html.twig',
                                [
                                    'namefield'   => $namefield,
                                    'id'          => $data['id'],
                                    'is_required' => $data['is_mandatory'] == 1,
                                    'has_icon'  => !empty($data['icon']),
                                    'icon'      => (string) $data['icon'],
                                    'options'   => $options,
                                ],
                            );
                        }
                    }
                } else {
                    if ($data['item'] == Resource::class) {
                        $opt['showHabilitations'] = true;
                    }
                    if ($item = getItemForItemtype($data['item'])) {
                        $container_class = new $data['item']();
                        $field = "";
                        $field .= $container_class::dropdown($opt);
                    }
                }
                break;
        }

        echo $field;
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        $show_used_by_child = $params['object_to_create'] == 'Ticket'
            && in_array($params["item"], ["Location", "RequestType"]);

        $show_link_to_user = in_array($params["item"], ["Location", "UserTitle", "UserCategory"]);
        $arrayAvailable = [];
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
        }

        $show_location_options = $params["item"] == "Location";
        $depths = [];
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        if ($show_location_options) {
            $disp[self::SPLITTED_DISPLAY] = __("Splitted display", "metademands");
            $depths = [0 => __('No limit', 'metademands')];
            for ($i = 1; $i <= 6; $i++) {
                $depths[$i] = sprintf(_n('%d level', '%d levels', $i, 'metademands'), $i);
            }
        } else {
            $disp[self::BLOCK_DISPLAY] = __("Block display", "metademands");
        }

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_dropdown.html.twig',
            [
                'show_used_by_child'    => $show_used_by_child,
                'used_by_child'         => $params['used_by_child'],
                'show_link_to_user'     => $show_link_to_user,
                'link_to_user'          => $params['link_to_user'],
                'user_fields'           => $arrayAvailable,
                'show_location_options' => $show_location_options,
                'root_items_id'         => $params['root_items_id'] ?? 0,
                'location_depth'        => $params['location_depth'] ?? 0,
                'depths'                => $depths,
                'show_display_type'     => !$show_location_options,
                'display_type'          => $params['display_type'],
                'display_types'         => $disp,
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
                        'display_emptychoice' => false,
                        'toadd' => [-1 => __('Not null value', 'metademands')]]);
                }
                //                else {
                //                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                //                        $elements[-1] = __('Not null value', 'metademands');
                //                        if (is_array(json_decode($params['custom_values'], true))) {
                //                            $elements += json_decode($params['custom_values'], true);
                //                        }
                //                        foreach ($elements as $key => $val) {
                //                            if ($key != 0) {
                //                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                //                            }
                //                        }
                //                    } else {
                //                        $elements[-1] = __('Not null value', 'metademands');
                //                        if (is_array(json_decode($params['custom_values'], true))) {
                //                            $elements += json_decode($params['custom_values'], true);
                //                        }
                //                        foreach ($elements as $key => $val) {
                //                            $elements[$key] = urldecode($val);
                //                        }
                //                    }
                //                    Dropdown::showFromArray(
                //                        "check_value",
                //                        $elements,
                //                        ['value' => $params['check_value'], 'used' => $already_used]
                //                    );
                //                }
                break;
        }
    }


    public static function showParamsValueToCheck($params): string
    {
        $value = '';
        if ($params['check_value'] == -1) {
            $value .= __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
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
                            if (!is_array($params['custom_values'])
                                && $params['custom_values'] != null
                                && is_array(json_decode($params['custom_values'], true))) {
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
        $options = ['any' => [0]];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayMandatoryTrigger($data, ['name' => $name, 'match' => 'exact'], 'select', $options);
    }


    public static function taskScript($data)
    {
        $options = [
            'any'      => [0],
            'defaults' => MetademandTask::getDefaultValues($data['default'] ?? ''),
        ];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
        }
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact'], 'select', $options);
    }

    public static function fieldsHiddenScript($data)
    {
        $options = ['any' => [0, -1], 'negate' => [0]];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact'], 'select', $options);
    }

    public static function blocksHiddenScript($data)
    {
        $name = 'field[' . $data['id'] . ']';
        if ($data['item'] == 'ITILCategory_Metademands') {
            $name = 'field_plugin_servicecatalog_itilcategories_id';
            // The category chosen in the service catalog
            if (!isset($data['value']) && isset($_GET['itilcategories_id'])) {
                $data['value'] = (int) $_GET['itilcategories_id'];
            }
        }
        $options = ['any' => [0, -1]];
        if (isset($data['value']) && $data['value'] > 0) {
            $options['restore'] = ['val' => (string) $data['value']];
        }
        FieldOption::displayBlockTrigger($data, ['name' => $name, 'match' => 'exact'], 'select', $options);
    }

    public static function checkConditions($data, $metaparams)
    {
        Condition::displayTrigger($data, $metaparams, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact']);
    }

    public static function getFieldValue($field)
    {
        switch ($field['item']) {
            default:
                $dbu = new DbUtils();
                if (!empty($field['item'])) {
                    return \Dropdown::getDropdownName(
                        $dbu->getTableForItemType($field['item']),
                        $field['value'],
                    );
                }
                return '';
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $title_style, $label, $field, $return_value, $lang, $is_order = false)
    {

        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            switch ($field['item']) {
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
