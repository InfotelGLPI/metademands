<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
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

use Glpi\Toolbox\Sanitizer;

/**
 * Class PluginMetademandsDraft
 */
class PluginMetademandsForm_Value extends CommonDBTM
{

    static $rightname = 'plugin_metademands';

    /**
     * @param $parent_fields
     * @param $values
     * @param $tickets_id
     */
    static function setFormValues($metademands_id, $parent_fields, $values, $form_id)
    {

        $input = [];

        $form_value = new self();
        if (count($parent_fields)) {
            foreach ($parent_fields as $fields_id => $field) {

                $field['value'] = '';
                if (isset($values[$fields_id]) && !is_array($values[$fields_id])) {

                    if ($field['type'] == "textarea") {
                        $field['value'] = Toolbox::convertTagToImage($values[$fields_id], $form_value, $input, false);
                        $field['value'] = Sanitizer::unsanitize($field['value']);
                        $field['value'] = Toolbox::addslashes_deep($field['value']);
                    } else {
                        $field['value'] = $values[$fields_id];
                    }

                } else if (isset($values[$fields_id]) && is_array($values[$fields_id])) {

                    $metafield = new PluginMetademandsField();
                    if ($metafield->getFromDB($fields_id)) {
                        if ($metafield->fields["type"] == "ordermaterial_basket") {
                            if (isset($_SESSION['plugin_metademands'][$field["plugin_metademands_metademands_id"]]['quantities'])) {
                                $quantities = $_SESSION['plugin_metademands'][$field["plugin_metademands_metademands_id"]]['quantities'];
                                if (isset($quantities[$fields_id])) {
                                    foreach ($quantities[$fields_id] as $k => $q) {
                                        if ($q > 0) {
                                            $field['value'] = json_encode($quantities[$fields_id]);
                                        }
                                    }
                                }
                            } else {
                                $field['value'] = json_encode($values[$fields_id]);
                            }
                        } else {
                            $field['value'] = json_encode($values[$fields_id]);
                        }
                    }
                }
                $field['value2'] = '';
                if (isset($values[$fields_id . "-2"]) && !is_array($values[$fields_id . "-2"])) {
                    $field['value2'] = $values[$fields_id . "-2"];
                } else if (isset($values[$fields_id . "-2"]) && is_array($values[$fields_id . "-2"])) {
                    $field['value2'] = json_encode($values[$fields_id . "-2"]);
                }

                $form_value->add([
                    'value' => $field['value'],
                    'value2' => $field['value2'],
                    'plugin_metademands_forms_id' => $form_id,
                    'plugin_metademands_fields_id' => $fields_id]);
            }
        }
    }

    /**
     * @param $plugin_metademands_metademands_id
     * @param $plugin_metademands_forms_id
     */
    static function loadFormValues($plugin_metademands_metademands_id, $plugin_metademands_forms_id)
    {
        $form_value = new self();
        $forms_values = $form_value->find(['plugin_metademands_forms_id' => $plugin_metademands_forms_id]);

        foreach ($forms_values as $values) {
            if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$values['plugin_metademands_fields_id']])) {
                unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$values['plugin_metademands_fields_id']]);
            }
            if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$values['plugin_metademands_fields_id'] . "-2"])) {
                unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$values['plugin_metademands_fields_id'] . "-2"]);
            }
            $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$values['plugin_metademands_fields_id']] = Toolbox::addslashes_deep(json_decode($values['value'], true)) ?? Toolbox::addslashes_deep($values['value']);
            if (!empty($values['value2'])) {
                $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$values['plugin_metademands_fields_id'] . "-2"] = Toolbox::addslashes_deep(json_decode($values['value2'], true)) ?? Toolbox::addslashes_deep($values['value2']);
            }
        }
    }
}
