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

        // Section 2: duplicate field options.
        // Several rows sharing the same (field, check_value) pair are NOT duplicates: the storage
        // model is one row per linked target, so a single value driving two fields, a task and a
        // hidden block legitimately owns several rows (see FieldOption::showFieldOptions()). Only
        // rows that are identical on every behaviour column are real duplicates, hence the full
        // tuple in the GROUP BY instead of the two columns the check used to group on.
        $duplicate_columns = [
            'plugin_metademands_fields_id',
            'check_value',
            'plugin_metademands_tasks_id',
            'fields_link',
            'hidden_link',
            'hidden_block',
            'users_id_validate',
            'childs_blocks',
            'checkbox_value',
            'checkbox_id',
            'parent_field_id',
            'hidden_block_same_block',
            'check_type_value',
            'check_value_regex',
            'assign_tech_group',
        ];

        $iterator = $DB->request([
            'SELECT' => array_merge(
                ['plugin_metademands_fields_id'],
                [new QueryExpression('COUNT(*) AS ' . $DB->quoteName('nbr_doublon'))],
            ),
            'FROM'    => 'glpi_plugin_metademands_fieldoptions',
            'GROUPBY' => $duplicate_columns,
            'HAVING'  => [new QueryExpression('COUNT(*) > 1')],
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
        //   (row links nothing) OR (check_value = 0 AND item != 'other' AND item != 'User').
        // Both branches are widened here because the rows they offer to delete permanently are
        // gone for good:
        //  - branch A predates hidden_block_same_block, check_type_value, check_value_regex and
        //    assign_tech_group, so a row whose only payload sits in one of those columns used to
        //    look empty;
        //  - branch B assumes check_value = 0 means "no value to check", but a regex option
        //    (check_type_value = 2) keeps its value in check_value_regex and a 'parent_field'
        //    writes a literal 0 (FieldOption::showValueToCheck()) - both legitimately store 0,
        //    exactly like the 'other' and 'User' items the original carve-out already spared.
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
                        'fields_link'             => 0,
                        'hidden_link'             => 0,
                        'hidden_block'            => 0,
                        'users_id_validate'       => 0,
                        'childs_blocks'           => '[]',
                        'checkbox_value'          => 0,
                        'checkbox_id'             => 0,
                        'parent_field_id'         => 0,
                        'hidden_block_same_block' => 0,
                        ['OR' => [
                            ['assign_tech_group' => '[]'],
                            ['assign_tech_group' => ''],
                            ['assign_tech_group' => null],
                        ]],
                        ['OR' => [
                            ['check_value_regex' => ''],
                            ['check_value_regex' => null],
                        ]],
                    ],
                    [
                        'check_value' => 0,
                        ['NOT' => ['check_type_value' => 2]],
                        ['glpi_plugin_metademands_fields.item' => ['!=', 'other']],
                        ['glpi_plugin_metademands_fields.item' => ['!=', 'User']],
                        ['glpi_plugin_metademands_fields.type' => ['!=', 'parent_field']],
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

        // The "empty custom values" diagnostic that used to sit here has been removed: it read a
        // `custom_values` JSON column that no longer exists anywhere. FieldParameter renamed it to
        // `custom` and dropped it from the fields table when the options moved to their own
        // glpi_plugin_metademands_fieldcustomvalues rows, so the query selected no such key, the
        // render loop was unreachable and only the panel header ever showed. What it looked for -
        // option keys starting at 0 instead of 1 - is now covered by the rank check below.

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
                    // Order by rank, like every other reader of this table does: find() otherwise
                    // returns the rows in id order, and a single drag & drop reorder is enough to
                    // make a perfectly sequential set of ranks look scrambled to
                    // isSequentialFromZero(), which compares the values position by position.
                    $fields_custom = $field_custom->find(
                        ['plugin_metademands_fields_id' => $field['id']],
                        ['rank'],
                    );
                    if (count($fields_custom) > 0) {
                        foreach ($fields_custom as $value) {
                            $ranks[$field['id']][] = $value['rank'];
                        }

                        if (FieldCustomvalue::isSequentialFromZero($ranks[$field['id']]) == false) {
                            $not_ordered_fields[] = $field['id'];
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

        TemplateRenderer::getInstance()->display('@metademands/tools_diagnostic.html.twig', [
            'global_status_form' => $global_status_form,
            'duplicates_has'     => $duplicates_has,
            'duplicates_rows'    => $duplicates_rows,
            'empty_options_has'  => $empty_options_has,
            'empty_options_rows' => $empty_options_rows,
            'ranks_fields_found' => $ranks_fields_found,
            'ranks_has'          => $ranks_has,
            'ranks_rows'         => $ranks_rows,
        ]);
    }
}
