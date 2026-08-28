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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use CommonGLPI;

class Tools extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';
    private $table = "";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Tools', 'metademands');
    }

    public static function getIcon()
    {
        return "ti ti-tools";
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
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == Config::class) {
            return self::createTabEntry(self::getTypeName());
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
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == Config::class) {
            $self = new self();
            $self->showTools();
        }
        return true;
    }


    public static function showTools()
    {
        global $DB;

        // Section 1: global status action
        $global_status_form = Html::getSimpleForm(
            self::getFormURL(),
            'change_global_status',
            _x('button', 'Verify metademands global status', 'metademands'),
        );

        // Section 2: duplicate field options
        $iterator = $DB->request([
            'SELECT' => [
                'plugin_metademands_fields_id',
                new QueryExpression(
                    'COUNT(' . $DB->quoteName('check_value') . ') AS ' . $DB->quoteName('nbr_doublon'),
                ),
            ],
            'FROM'    => 'glpi_plugin_metademands_fieldoptions',
            'GROUPBY' => ['plugin_metademands_fields_id', 'check_value'],
            'HAVING'  => [
                new QueryExpression('COUNT(' . $DB->quoteName('plugin_metademands_fields_id') . ') > 1'),
                new QueryExpression('COUNT(' . $DB->quoteName('check_value') . ') > 1'),
            ],
        ]);

        $duplicates_has  = count($iterator) > 0;
        $duplicates_rows = [];
        foreach ($iterator as $array) {
            $field = new Field();
            $field->getfromDB($array['plugin_metademands_fields_id']);
            $duplicates_rows[] = [
                'field_link' => $field->getLink(),
                'meta_name'  => \Dropdown::getDropdownName(
                    "glpi_plugin_metademands_metademands",
                    $field->fields['plugin_metademands_metademands_id'],
                ),
                'nbr'        => $array['nbr_doublon'],
            ];
        }

        // Section 3: empty field options.
        // SQL "AND" binds tighter than "OR", so the legacy WHERE is two OR branches:
        //   (big AND group) OR (check_value = 0 AND item != 'other' AND item != 'User').
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_metademands_fieldoptions.id',
                'glpi_plugin_metademands_fieldoptions.plugin_metademands_fields_id',
            ],
            'FROM'      => 'glpi_plugin_metademands_fieldoptions',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_fields' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields'       => 'id',
                        'glpi_plugin_metademands_fieldoptions' => 'plugin_metademands_fields_id',
                    ],
                ],
            ],
            'WHERE'     => [
                'OR' => [
                    [
                        ['OR' => [
                            ['plugin_metademands_tasks_id' => 0],
                            ['plugin_metademands_tasks_id' => null],
                        ]],
                        'fields_link'       => 0,
                        'hidden_link'       => 0,
                        'hidden_block'      => 0,
                        'users_id_validate' => 0,
                        'childs_blocks'     => '[]',
                        'checkbox_value'    => 0,
                        'checkbox_id'       => 0,
                        'parent_field_id'   => 0,
                    ],
                    [
                        'check_value' => 0,
                        ['glpi_plugin_metademands_fields.item' => ['!=', 'other']],
                        ['glpi_plugin_metademands_fields.item' => ['!=', 'User']],
                    ],
                ],
            ],
        ]);

        $empty_options_has  = count($iterator) > 0;
        $empty_options_rows = [];
        foreach ($iterator as $array) {
            $field = new Field();
            $field->getfromDB($array['plugin_metademands_fields_id']);
            $empty_options_rows[] = [
                'field_link' => $field->getLink(),
                'meta_name'  => \Dropdown::getDropdownName(
                    "glpi_plugin_metademands_metademands",
                    $field->fields['plugin_metademands_metademands_id'],
                ),
                'purge_form' => Html::getSimpleForm(
                    Tools::getFormURL(),
                    'purge_emptyoptions',
                    _x('button', 'Delete permanently'),
                    ['id' => $array['id']],
                    'fa-times-circle',
                ),
            ];
        }

        // Section 4: empty custom values
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_metademands_fieldparameters.id',
                'glpi_plugin_metademands_fieldparameters.plugin_metademands_fields_id',
                'glpi_plugin_metademands_fields.type',
                'glpi_plugin_metademands_fieldcustomvalues.name',
            ],
            'FROM'      => 'glpi_plugin_metademands_fieldparameters',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_fields' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields'          => 'id',
                        'glpi_plugin_metademands_fieldparameters' => 'plugin_metademands_fields_id',
                    ],
                ],
                'glpi_plugin_metademands_fieldcustomvalues' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields'            => 'id',
                        'glpi_plugin_metademands_fieldcustomvalues' => 'plugin_metademands_fields_id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_metademands_fields.type' => [
                    'radio',
                    'checkbox',
                    'dropdown_meta',
                    'dropdown_multiple',
                ],
            ],
        ]);

        // The inner rows depend on a `custom_values` key that this query never selects,
        // so this loop renders nothing today; captured as-is to preserve behavior.
        $empty_cv_has = count($iterator) > 0;
        ob_start();
        foreach ($iterator as $array) {
            $field = new Field();
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
                echo \Dropdown::getDropdownName(
                    "glpi_plugin_metademands_metademands",
                    $field->fields['plugin_metademands_metademands_id'],
                );
                echo "</td>";
                echo "<td class='center'>";
                echo Html::getSimpleForm(
                    Tools::getFormURL(),
                    'fix_emptycustomvalues',
                    _x('button', 'Fix empty custom values', 'metademands'),
                    ['id' => $array['id']],
                    'ti ti-circle-check',
                );
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        }
        $empty_cv_rows_html = ob_get_clean();

        // Side effect: realign child entities on their metademand entity.
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_metademands_groups.id',
                'glpi_plugin_metademands_groups.plugin_metademands_metademands_id',
                'glpi_plugin_metademands_groups.entities_id AS field_entity',
                'glpi_plugin_metademands_metademands.entities_id AS meta_entity',
            ],
            'FROM'      => 'glpi_plugin_metademands_groups',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_metademands' => [
                    'ON' => [
                        'glpi_plugin_metademands_groups'      => 'plugin_metademands_metademands_id',
                        'glpi_plugin_metademands_metademands' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                new QueryExpression(
                    $DB->quoteName('glpi_plugin_metademands_metademands.entities_id')
                    . ' != '
                    . $DB->quoteName('glpi_plugin_metademands_groups.entities_id'),
                ),
            ],
        ]);

        if (count($iterator) > 0) {
            foreach ($iterator as $array) {
                $field = new Group();
                $input['entities_id'] = $array["meta_entity"];
                $input['id'] = $array["id"];
                $field->update($input, 1);
            }
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_metademands_ticketfields.id',
                'glpi_plugin_metademands_ticketfields.plugin_metademands_metademands_id',
                'glpi_plugin_metademands_ticketfields.entities_id AS field_entity',
                'glpi_plugin_metademands_metademands.entities_id AS meta_entity',
            ],
            'FROM'      => 'glpi_plugin_metademands_ticketfields',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_metademands' => [
                    'ON' => [
                        'glpi_plugin_metademands_ticketfields' => 'plugin_metademands_metademands_id',
                        'glpi_plugin_metademands_metademands'  => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                new QueryExpression(
                    $DB->quoteName('glpi_plugin_metademands_metademands.entities_id')
                    . ' != '
                    . $DB->quoteName('glpi_plugin_metademands_ticketfields.entities_id'),
                ),
            ],
        ]);

        if (count($iterator) > 0) {
            $field = new TicketField();
            foreach ($iterator as $array) {
                $input['entities_id'] = $array["meta_entity"];
                $input['id'] = $array["id"];
                $field->update($input, 1);
            }
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_metademands_fields.id',
                'glpi_plugin_metademands_fields.plugin_metademands_metademands_id',
                'glpi_plugin_metademands_fields.entities_id AS field_entity',
                'glpi_plugin_metademands_metademands.entities_id AS meta_entity',
            ],
            'FROM'      => 'glpi_plugin_metademands_fields',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_metademands' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields'      => 'plugin_metademands_metademands_id',
                        'glpi_plugin_metademands_metademands' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                new QueryExpression(
                    $DB->quoteName('glpi_plugin_metademands_metademands.entities_id')
                    . ' != '
                    . $DB->quoteName('glpi_plugin_metademands_fields.entities_id'),
                ),
            ],
        ]);

        if (count($iterator) > 0) {
            $field = new Field();
            foreach ($iterator as $array) {
                $input['entities_id'] = $array["meta_entity"];
                $input['id'] = $array["id"];
                $field->update($input, 1);
            }
        }

        // Section 5: fields with non-sequential custom value ranks
        $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

        $metafield = new Field();
        $not_ordered_fields = [];
        $ranks = [];
        $ranks_fields_found = false;
        $ranks_has  = false;
        $ranks_rows = [];

        if ($fields = $metafield->find()) {
            $ranks_fields_found = true;
            foreach ($fields as $field) {
                if (in_array($field['type'], $allowed_customvalues_types)
                    || in_array($field['item'], $allowed_customvalues_items)) {
                    $field_custom = new FieldCustomvalue();
                    if ($fields_custom = $field_custom->find(['plugin_metademands_fields_id' => $field['id']])) {
                        foreach ($fields_custom as $key => $value) {
                            $ranks[$field['id']][] = $value['rank'];
                        }

                        foreach ($fields_custom as $fields_customs) {
                            if (FieldCustomvalue::isSequentialFromZero(
                                $ranks[$field['id']],
                            ) == false) {
                                $not_ordered_fields[] = $field['id'];
                            }
                        }
                    }
                }
            }
            $not_ordered_fields = array_unique($not_ordered_fields);
            $ranks_has = count($not_ordered_fields) > 0;
            foreach ($not_ordered_fields as $not_ordered_field) {
                $field_to_order = new Field();
                $field_to_order->getfromDB($not_ordered_field);
                $ranks_rows[] = [
                    'field_link' => $field_to_order->getLink(),
                    'meta_name'  => \Dropdown::getDropdownName(
                        "glpi_plugin_metademands_metademands",
                        $field_to_order->fields['plugin_metademands_metademands_id'],
                    ),
                    'fix_form'   => Html::getSimpleForm(
                        FieldCustomvalue::getFormURL(),
                        'fixranks',
                        _x('button', 'Do you want to fix them ? Warning you must check your options after!', 'metademands'),
                        ['plugin_metademands_fields_id' => $not_ordered_field],
                        'ti-settings',
                    ),
                ];
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/tools_diagnostic.html.twig', [
            'global_status_form' => $global_status_form,
            'duplicates_has'     => $duplicates_has,
            'duplicates_rows'    => $duplicates_rows,
            'empty_options_has'  => $empty_options_has,
            'empty_options_rows' => $empty_options_rows,
            'empty_cv_has'       => $empty_cv_has,
            'empty_cv_rows_html' => $empty_cv_rows_html,
            'ranks_fields_found' => $ranks_fields_found,
            'ranks_has'          => $ranks_has,
            'ranks_rows'         => $ranks_rows,
        ]);
    }
}
