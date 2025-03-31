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

/**
 *
 */
class PluginMetademandsTools extends CommonDBTM
{

    static $rightname = 'plugin_metademands';
    private $table = "";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return __('Tools', 'metademands');
    }

    public static function getTable($classname = null)
    {
        return "glpi_plugin_metademands_configs";
    }

    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     * @see CommonGLPI::getTabNameForItem()
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsConfig') {
            return self::getTypeName();
        }
        return '';
    }

    /**
     * @param \CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     * @see CommonGLPI::displayTabContentForItem()
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsConfig') {
            $self = new self();
            $self->showTools();
        }
        return true;
    }


    public static function showTools()
    {
        global $DB;

        echo "<div class='left'>";
        echo "<table class='tab_cadre_fixe'>";

        $query = "SELECT plugin_metademands_fields_id, COUNT(plugin_metademands_fields_id),
                        check_value, COUNT(check_value) as nbr_doublon
                    FROM
                        `glpi_plugin_metademands_fieldoptions`
                    GROUP BY 
                        plugin_metademands_fields_id, 
                        check_value
                    HAVING 
                           (COUNT(plugin_metademands_fields_id) > 1) AND 
                           (COUNT(check_value) > 1)";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='left'>";
            echo __('Duplicates fields options', 'metademands');
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td class='left'>";

            while ($array = $DB->fetchAssoc($result)) {
                $field = new PluginMetademandsField();
                $field->getfromDB($array['plugin_metademands_fields_id']);

                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='left' width='50%'>";
                echo __('Field');
                echo "</th>";
                echo "<th class='left'>";
                echo _n('Meta-Demand', 'Meta-Demands', 1, 'metademands');
                echo "</th>";
                echo "<th class='left'>";
                echo __('Number of duplicates', 'metademands');
                echo "</th>";
                echo "</tr>";

                echo "<tr class='tab_bg_2'>";
                echo "<td class='left'>";
                echo $field->getLink();
                echo "</td>";
                echo "<td class='left'>";
                echo Dropdown::getDropdownName(
                    "glpi_plugin_metademands_metademands",
                    $field->fields['plugin_metademands_metademands_id']
                );
                echo "</td>";
                echo "<td class='left'>";
                echo $array['nbr_doublon'];
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='left'>";
            echo __('No duplicates founded', 'metademands');
            echo "</th>";
            echo "</tr>";
        }

        echo "</table></div>";

        echo "<br><div class='left'>";
        echo "<table class='tab_cadre_fixe'>";

        $query = "SELECT `glpi_plugin_metademands_fieldoptions`.`id`, `glpi_plugin_metademands_fieldoptions`.`plugin_metademands_fields_id`
                    FROM
                        `glpi_plugin_metademands_fieldoptions`
                    LEFT JOIN `glpi_plugin_metademands_fields` 
                        ON (`glpi_plugin_metademands_fields`.`id` = `glpi_plugin_metademands_fieldoptions`.`plugin_metademands_fields_id`)
                    WHERE
                        ((`plugin_metademands_tasks_id` = 0 OR `plugin_metademands_tasks_id` IS NULL) AND
                        `fields_link` = 0 AND
                        `hidden_link` = 0 AND
                        `hidden_block` = 0 AND
                        `users_id_validate` = 0 AND
                        `childs_blocks` = '[]' AND
                        `checkbox_value` = 0 AND
                        `checkbox_id` = 0 AND
                        `parent_field_id` = 0) OR `check_value` = 0 
                                                      AND `glpi_plugin_metademands_fields`.`item` != 'other'
                    AND `glpi_plugin_metademands_fields`.`item` != 'User'";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='left'>";
            echo __('Empty fields options', 'metademands');
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td class='left'>";

            while ($array = $DB->fetchAssoc($result)) {
                $field = new PluginMetademandsField();
                $field->getfromDB($array['plugin_metademands_fields_id']);

                echo "<table class='tab_cadre_fixe'>";

                echo "<tr class='tab_bg_2'>";
                echo "<th class='left' width='50%'>";
                echo __('Field');
                echo "</th>";
                echo "<th class='left'>";
                echo _n('Meta-Demand', 'Meta-Demands', 1, 'metademands');
                echo "</th>";
                echo "<th class='center'>";
                echo "</th>";
                echo "</tr>";

                echo "<tr class='tab_bg_2'>";
                echo "<td class='left'>";
                echo $field->getLink();
                echo "</td>";
                echo "<td class='left'>";
                echo Dropdown::getDropdownName(
                    "glpi_plugin_metademands_metademands",
                    $field->fields['plugin_metademands_metademands_id']
                );
                echo "</td>";
                echo "<td class='center'>";
                echo Html::getSimpleForm(
                    PluginMetademandsTools::getFormURL(),
                    'purge_emptyoptions',
                    _x('button', 'Delete permanently'),
                    ['id' => $array['id']],
                    'fa-times-circle'
                );
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='left'>";
            echo __('No empty field options founded', 'metademands');
            echo "</th>";
            echo "</tr>";
        }

        echo "</table></div>";


        echo "<br><div class='left'>";
        echo "<table class='tab_cadre_fixe'>";

        $query = "SELECT `glpi_plugin_metademands_fieldparameters`.`id`, 
                            `glpi_plugin_metademands_fieldparameters`.`plugin_metademands_fields_id`, 
                           `glpi_plugin_metademands_fields`.`type`, 
                           `glpi_plugin_metademands_fieldcustomvalues`.`name`
                    FROM
                        `glpi_plugin_metademands_fieldparameters`
                    LEFT JOIN `glpi_plugin_metademands_fields` 
                        ON (`glpi_plugin_metademands_fields`.`id` = `glpi_plugin_metademands_fieldparameters`.`plugin_metademands_fields_id`)
                    LEFT JOIN `glpi_plugin_metademands_fieldcustomvalues` 
                        ON (`glpi_plugin_metademands_fields`.`id` = `glpi_plugin_metademands_fieldcustomvalues`.`plugin_metademands_fields_id`)
                    WHERE
                        `glpi_plugin_metademands_fields`.`type` = 'radio' 
                        OR `glpi_plugin_metademands_fields`.`type` = 'checkbox' 
                        OR `glpi_plugin_metademands_fields`.`type` = 'dropdown_meta'
                        OR `glpi_plugin_metademands_fields`.`type` = 'dropdown_multiple'";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='left'>";
            echo __('Empty custom values', 'metademands');
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td class='left'>";

            while ($array = $DB->fetchAssoc($result)) {
                $field = new PluginMetademandsField();
                $field->getfromDB($array['plugin_metademands_fields_id']);

                if (isset($array['custom_values'])) {
                    $test = json_decode($array['custom_values'], true);

                    if ($test == null) {
                        continue;
                    }
                    if ($test != null && !array_key_exists('0', $test)) {
                        continue;
                    }
                    echo "<table class='tab_cadre_fixe'>";
                    echo "<tr class='tab_bg_2'>";
                    echo "<th class='left' width='50%'>";
                    echo __('Field');
                    echo "</th>";
                    echo "<th class='center'>";
                    echo __('Type');
                    echo "</th>";
                    echo "<th class='center'>";
                    echo __('Value');
                    echo "</th>";
                    echo "<th class='left'>";
                    echo _n('Meta-Demand', 'Meta-Demands', 1, 'metademands');
                    echo "</th>";
                    echo "<th class='center'>";
                    echo "</th>";
                    echo "</tr>";

                    echo "<tr class='tab_bg_2'>";
                    echo "<td class='left'>";
                    echo $field->getLink();
                    echo "</td>";
                    echo "<td class='left'>";
                    echo $array['type'];
                    echo "</td>";
                    echo "<td class='left'>";
                    var_dump($test);
                    $start_one = array_combine(range(1, count($test)), array_values($test));
                    var_dump($start_one);
                    echo "</td>";
                    echo "<td class='left'>";
                    echo Dropdown::getDropdownName(
                        "glpi_plugin_metademands_metademands",
                        $field->fields['plugin_metademands_metademands_id']
                    );
                    echo "</td>";
                    echo "<td class='center'>";
                    echo Html::getSimpleForm(
                        PluginMetademandsTools::getFormURL(),
                        'fix_emptycustomvalues',
                        _x('button', 'Fix empty custom values', 'metademands'),
                        ['id' => $array['id']],
                        'fa-check-circle'
                    );
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                }
            }
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='left'>";
            echo __('No empty custom values founded', 'metademands');
            echo "</th>";
            echo "</tr>";
        }


        $query = "SELECT `glpi_plugin_metademands_groups`.`id`,
       `glpi_plugin_metademands_groups`.`plugin_metademands_metademands_id`,
       `glpi_plugin_metademands_groups`.`entities_id` as field_entity,
       `glpi_plugin_metademands_metademands`.`entities_id` as meta_entity
                    FROM
                        `glpi_plugin_metademands_groups`
                    LEFT JOIN `glpi_plugin_metademands_metademands` 
                        ON (`glpi_plugin_metademands_groups`.`plugin_metademands_metademands_id` = `glpi_plugin_metademands_metademands`.`id`)
                    WHERE
                        `glpi_plugin_metademands_metademands`.`entities_id` != `glpi_plugin_metademands_groups`.`entities_id`";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            while ($array = $DB->fetchAssoc($result)) {
                $field = new PluginMetademandsGroup();
                $input['entities_id'] = $array["meta_entity"];
                $input['id'] = $array["id"];
                $field->update($input, 1);
            }
        }

        $query = "SELECT `glpi_plugin_metademands_ticketfields`.`id`,
       `glpi_plugin_metademands_ticketfields`.`plugin_metademands_metademands_id`,
       `glpi_plugin_metademands_ticketfields`.`entities_id` as field_entity,
       `glpi_plugin_metademands_metademands`.`entities_id` as meta_entity
                    FROM
                        `glpi_plugin_metademands_ticketfields`
                    LEFT JOIN `glpi_plugin_metademands_metademands` 
                        ON (`glpi_plugin_metademands_ticketfields`.`plugin_metademands_metademands_id` = `glpi_plugin_metademands_metademands`.`id`)
                    WHERE
                        `glpi_plugin_metademands_metademands`.`entities_id` != `glpi_plugin_metademands_ticketfields`.`entities_id`";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            $field = new PluginMetademandsTicketField();
            while ($array = $DB->fetchAssoc($result)) {
                $input['entities_id'] = $array["meta_entity"];
                $input['id'] = $array["id"];
                $field->update($input, 1);
            }
        }

        $query = "SELECT `glpi_plugin_metademands_fields`.`id`,
       `glpi_plugin_metademands_fields`.`plugin_metademands_metademands_id`,
       `glpi_plugin_metademands_fields`.`entities_id` as field_entity,
       `glpi_plugin_metademands_metademands`.`entities_id` as meta_entity
                    FROM
                        `glpi_plugin_metademands_fields`
                    LEFT JOIN `glpi_plugin_metademands_metademands` 
                        ON (`glpi_plugin_metademands_fields`.`plugin_metademands_metademands_id` = `glpi_plugin_metademands_metademands`.`id`)
                    WHERE
                        `glpi_plugin_metademands_metademands`.`entities_id` != `glpi_plugin_metademands_fields`.`entities_id`";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            $field = new PluginMetademandsField();
            while ($array = $DB->fetchAssoc($result)) {
                $input['entities_id'] = $array["meta_entity"];
                $input['id'] = $array["id"];
                $field->update($input, 1);
            }
        }

        echo "</table></div>";

        echo "<br><div class='left'>";
        echo "<table class='tab_cadre_fixe'>";

        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        $metafield = new PluginMetademandsField();
        $not_ordered_fields = [];

        if ($fields = $metafield->find()) {
            foreach ($fields as $field) {
                if (in_array($field['type'], $allowed_customvalues_types)
                    || in_array($field['item'], $allowed_customvalues_items)) {
                    $field_custom = new PluginMetademandsFieldCustomvalue();
                    if ($fields_custom = $field_custom->find(['plugin_metademands_fields_id' => $field['id']])) {
                        foreach ($fields_custom as $key => $value) {
                            $ranks[$field['id']][] = $value['rank'];
                        }

                        foreach ($fields_custom as $fields_custom) {
                            if (PluginMetademandsFieldCustomvalue::isSequentialFromZero(
                                    $ranks[$field['id']]
                                ) == false) {
                                $not_ordered_fields[] = $field['id'];
                            }
                        }
                    }
                }
            }
            $not_ordered_fields = array_unique($not_ordered_fields);
            if (count($not_ordered_fields) > 0) {
                echo "<tr class='tab_bg_2'>";
                echo "<th class='left'>";
                echo __('Ranks problem with fields', 'metademands');
                echo "</th>";
                echo "</tr>";

                echo "<tr class='tab_bg_2'>";
                echo "<td class='left'>";

                foreach ($not_ordered_fields as $not_ordered_field) {
                    $field_to_order = new PluginMetademandsField();
                    $field_to_order->getfromDB($not_ordered_field);
                    echo "<table class='tab_cadre_fixe'>";
                    echo "<tr class='tab_bg_2'>";
                    echo "<th class='left' width='50%'>";
                    echo __('Field');
                    echo "</th>";
                    echo "<th class='left'>";
                    echo _n('Meta-Demand', 'Meta-Demands', 1, 'metademands');
                    echo "</th>";
                    echo "<th class='center'>";
                    echo "</th>";
                    echo "</tr>";

                    echo "<tr class='tab_bg_2'>";
                    echo "<td class='left'>";
                    echo $field_to_order->getLink();
                    echo "</td>";
                    echo "<td class='left'>";
                    echo Dropdown::getDropdownName(
                        "glpi_plugin_metademands_metademands",
                        $field_to_order->fields['plugin_metademands_metademands_id']
                    );
                    echo "</td>";
                    echo "<td class='right'>";
                    echo Html::getSimpleForm(
                        PluginMetademandsFieldCustomvalue::getFormURL(),
                        'fixranks',
                        _x('button', 'Do you want to fix them ? Warning you must check your options after!', 'metademands'),
                        ['plugin_metademands_fields_id' => $not_ordered_field],
                        'fa-wrench',
                    );
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                }
            }  else {
                echo "<tr class='tab_bg_2'>";
                echo "<th class='left'>";
                echo __('No problem with founded', 'metademands');
                echo "</th>";
                echo "</tr>";
            }
            echo "</table></div>";
        }
    }
}
