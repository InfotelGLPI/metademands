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
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Orderfollowup\Order;
use Html;
use Plugin;
use GlpiPlugin\Metademands\Basketobject;
use GlpiPlugin\Metademands\Basketobjecttype;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Orderfollowup\Material;
use GlpiPlugin\Orderfollowup\Metademand as OrderMetademand;
use PluginOrdermaterialMaterial;
use PluginOrdermaterialMetademand;
use Toolbox;

/**
 * Basket Class
 *
 **/
class Basket extends CommonDBTM
{
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
        return __('Basket', 'metademands');
    }

    public static function showWizardField($data, $on_order = false, $itilcategories_id = 0, $idline = 0)
    {
        global $DB;

        $metademand = new Metademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);
        $background_color = "";
        if (isset($metademand->fields['background_color'])
            && $metademand->fields['background_color'] != "") {
            $background_color = "background-color:" . Metademand::toThemedBackground($metademand->fields['background_color']) . ";";
        }
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize(
            $data['custom_values'],
        ) : [];
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }

        if ($on_order == false) {
            $namefield = 'field';
        } else {
            $namefield = 'field_basket_' . $idline;
        }

        // Display order chosen on each reference, then alphabetical as before
        $criteria = [
            'FROM' => [Basketobject::getTable()],
            'SELECT' => [Basketobject::getTable() => '*'],
            'ORDER' => [
                Basketobject::getTable() . '.rank',
                Basketobject::getTable() . '.name',
                Basketobject::getTable() . '.description',
            ],
        ];

        $criteria['WHERE'] = ['plugin_metademands_basketobjecttypes_id' => $data['item']];

        $where = [];
        if (Plugin::isPluginActive('ordermaterial')) {
            if (isset($custom_values[2]) && $custom_values[2] == 1) {
                $where = [
                    'OR' => [
                        'glpi_plugin_ordermaterial_materials.estimated_price' => ['>', 0],
                        'glpi_plugin_ordermaterial_materials.is_specific' => 1,
                    ],
                ];
            }
            if (isset($custom_values[3]) && $custom_values[3] == 1) {
                $where['glpi_plugin_ordermaterial_materials.is_accessory'] = 1;
            }
            if (count($where)) {
                $criteria['LEFT JOIN'][PluginOrdermaterialMaterial::getTable()] = [
                    'ON' => [
                        PluginOrdermaterialMaterial::getTable() => 'plugin_metademands_basketobjects_id',
                        Basketobject::getTable() => 'id',
                    ],
                ];
            }
        }

        // "With unit price (HT)" (custom index 1) only adds the price column: a reference
        // priced 0 € is still offered, as in the basket object form.

        if (count($where)) {
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $where);
        }

        $materials = $DB->request($criteria);
        $nb = count($materials);

        // The tabular/conditional logic and the ordermaterial / orderfollowup integration
        // stay in PHP; the cells carry values only (see field_basket.html.twig), the
        // quantity widget (showNumber, carrying the data-md-totalrow-* attributes read by
        // wizard_form.js) being rendered by the template.
        // Enabled by default: baskets saved before these options kept both columns.
        $with_reference = ($custom_values[5] ?? 1) == 1;
        $with_description = ($custom_values[6] ?? 1) == 1;

        $headers = [];
        if ($with_reference) {
            $headers[] = ['label' => __('Reference', 'metademands'), 'style' => $background_color];
        }
        $headers[] = ['label' => __('Designation', 'metademands'), 'style' => $background_color];
        if ($with_description) {
            $headers[] = ['label' => __('Description'), 'style' => $background_color];
        }

        if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
            $ordermaterialmeta = new PluginOrdermaterialMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(
                ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
            )) {
                $headers[] = ['label' => __('Estimated unit price', 'ordermaterial'), 'style' => $background_color];
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $ordermaterialmeta = new OrderMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(
                ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
            )) {
                // Without quantity, every object counts for one unit: the column only shows 1.
                if (isset($custom_values[0]) && $custom_values[0] == 1) {
                    $headers[] = ['label' => __('Unit', 'orderfollowup'), 'style' => $background_color];
                }

                if (isset($custom_values[1]) && $custom_values[1] == 1) {
                    $headers[] = ['label' => __('Unit price (HT)', 'orderfollowup'), 'style' => $background_color];
                }
            }
        }

        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            $headers[] = ['label' => __('Quantity', 'metademands'), 'style' => $background_color];
        }
        if (isset($custom_values[0]) && $custom_values[0] == 0) {
            $headers[] = ['label' => __('Select', 'metademands'), 'style' => $background_color];
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $ordermaterialmeta = new OrderMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(
                ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
            )) {
                if (isset($custom_values[0]) && $custom_values[0] == 1) {
                    $headers[] = [
                        'label' => __('Total (HT)', 'orderfollowup'),
                        'style' => 'text-align: right;' . $background_color,
                    ];
                }
            } else {
                if (isset($custom_values[0]) && $custom_values[0] == 1) {
                    $headers[] = [
                        'label' => __('Total', 'metademands'),
                        'style' => 'text-align: right;' . $background_color,
                    ];
                }
            }
        } else {
            if (isset($custom_values[0]) && $custom_values[0] == 1) {
                $headers[] = [
                    'label' => __('Total', 'metademands'),
                    'style' => 'text-align: right;' . $background_color,
                ];
            }
        }

        $search_id = $data['id'];

        $rows = [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            foreach ($materials as $material) {
                $key = $material['id'];
                $cells = [];

                if ($with_reference) {
                    $cells[] = ['t' => (string) $material['reference'], 'search' => 'ref'];
                }
                $cells[] = ['t' => (string) $material['name'], 'search' => 'name'];
                if ($with_description) {
                    $cells[] = ['rich' => (string) $material['description'], 'search' => 'description'];
                }

                if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            if ($ordermaterial->fields['is_specific'] == 1) {
                                $cells[] = ['t' => __('On quotation', 'ordermaterial')];
                            } else {
                                $cells[] = [
                                    't' => Html::formatNumber(
                                        $ordermaterial->fields['estimated_price'],
                                        false,
                                        2,
                                    ) . " €",
                                ];
                            }
                        } else {
                            $cells[] = ['t' => ''];
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new Material();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            $cells[] = ['t' => (string) $ordermaterial->fields['unit']];

                            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                                $cells[] = [
                                    't' => Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €",
                                ];
                            }
                        } else {
                            // No price set for this object: blank unit and price, one cell per header
                            $cells[] = ['t' => ''];
                            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                                $cells[] = ['t' => ''];
                            }
                        }
                    }
                }

                $opt = [
                    'min' => 0,
                    'max' => 1000,
                    'step' => 1,
                    'display' => false,
                    'specific_tags' => [],
                ];

                if (isset($value) && is_array($value)) {
                    foreach ($value as $k => $val) {
                        if ($k == $key) {
                            $opt['value'] = $val;
                        }
                    }
                }

                if (isset($data["is_mandatory"]) && $data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => 'required', 'ismultiplenumber' => 'ismultiplenumber'];
                }

                $check_hidden = $namefield . "[" . $data['id'] . "]";
                $name_hidden = $namefield . "[" . $data['id'] . "][" . $key . "]";
                // The quantity is added by public/scripts/wizard_form.js on each change.
                $params = [
                    'action' => 'loadTotalrow',
                    'plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                    'check' => $check_hidden,
                    'name' => $name_hidden,
                    'key' => $key,
                ];
                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            $params['estimated_price'] = $ordermaterial->fields['estimated_price'];
                        }
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new Material();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            $params['unit_price'] = $ordermaterial->fields['unit_price'];
                        }
                    }
                }
                $rand_totalrow = mt_rand();
                // Line total reloaded by public/scripts/wizard_form.js on each change of
                // the quantity. Html::select() escapes the attributes.
                $opt['specific_tags']['data-md-totalrow-target'] = 'plugin_metademands_totalrow' . $rand_totalrow;
                $opt['specific_tags']['data-md-totalrow-url'] = PLUGIN_METADEMANDS_WEBDIR . '/ajax/totalrow.php';
                $opt['specific_tags']['data-md-totalrow-params'] = json_encode($params);

                $cells[] = ['number' => [
                    'name'    => "quantity[" . $data['id'] . "][" . $key . "]",
                    'options' => ['display' => true] + $opt,
                ]];
                $cells[] = ['total_id' => 'plugin_metademands_totalrow' . $rand_totalrow];

                $rows[] = $cells;
            }
        } else {
            foreach ($materials as $material) {
                $key = $material['id'];
                $cells = [];

                if ($with_reference) {
                    $cells[] = ['t' => (string) $material['reference'], 'search' => 'ref'];
                }
                $cells[] = ['t' => (string) $material['name'], 'search' => 'name'];
                if ($with_description) {
                    $cells[] = ['rich' => (string) $material['description'], 'search' => 'description'];
                }

                if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            if ($ordermaterial->fields['is_specific'] == 1) {
                                $cells[] = ['t' => __('On quotation', 'ordermaterial')];
                            } else {
                                $cells[] = [
                                    't' => Html::formatNumber(
                                        $ordermaterial->fields['estimated_price'],
                                        false,
                                        2,
                                    ) . " €",
                                ];
                            }
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        if (isset($custom_values[1]) && $custom_values[1] == 1) {
                            $ordermaterial = new Material();
                            // No price set for this object: blank cell under the price header
                            $cells[] = [
                                't' => $ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])
                                    ? Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €"
                                    : '',
                            ];
                        }
                    }
                }

                $is_checked = false;
                if (isset($value) && is_array($value)) {
                    foreach ($value as $val) {
                        if ($val == $key) {
                            $is_checked = true;
                        }
                    }
                }
                $cells[] = ['checkbox' => [
                    'check'      => $namefield . "[" . $data['id'] . "]",
                    'name'       => $namefield . "[" . $data['id'] . "][" . $key . "]",
                    'key'        => $key,
                    'is_checked' => $is_checked,
                ]];

                $rows[] = $cells;
            }
        }


        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_basket.html.twig',
            [
                'background_style' => $background_color,
                'headers' => $headers,
                'nb' => $nb,
                'with_search' => ($custom_values[4] ?? 1) == 1,
                'with_reference' => $with_reference,
                'with_description' => $with_description,
                'search_id' => $search_id,
                'ph_ref' => __('Search..', 'metademands'),
                'ph_name' => __('Search for names..', 'metademands'),
                'ph_desc' => __('Search for description..', 'metademands'),
                'rows' => $rows,
            ],
        );
    }

    public static function showFieldCustomValues($params)
    {
        $quantity = $params['custom_values'][0] ?? 0;
        $price = $params['custom_values'][1] ?? 0;

        $rows = [];

        $rows[] = [
            ['text' => __('With quantity', 'metademands')],
            ['widget' => ['type' => 'yesno', 'name' => 'custom[0]', 'value' => $quantity]],
            ['colspan' => 2],
        ];

        // Enabled by default: baskets saved before this option kept their search inputs.
        $rows[] = [
            ['text' => __('With integrated search engine', 'metademands')],
            ['widget' => ['type' => 'yesno', 'name' => 'custom[4]', 'value' => $params['custom_values'][4] ?? 1]],
            ['colspan' => 2],
        ];
        $rows[] = [
            ['text' => __('With reference', 'metademands')],
            ['widget' => ['type' => 'yesno', 'name' => 'custom[5]', 'value' => $params['custom_values'][5] ?? 1]],
            ['colspan' => 2],
        ];
        $rows[] = [
            ['text' => __('With description', 'metademands')],
            ['widget' => ['type' => 'yesno', 'name' => 'custom[6]', 'value' => $params['custom_values'][6] ?? 1]],
            ['colspan' => 2],
        ];

        if (Plugin::isPluginActive('ordermaterial')
            || Plugin::isPluginActive('orderfollowup')) {
            $rows[] = [
                ['text' => __('With unit price (HT)', 'metademands')],
                ['widget' => ['type' => 'yesno', 'name' => 'custom[1]', 'value' => $price]],
                ['colspan' => 2],
            ];
        }

        if (Plugin::isPluginActive('ordermaterial')) {
            $is_specific = $params['custom_values'][2] ?? 0;
            $is_accessory = $params['custom_values'][3] ?? 0;

            $rows[] = [
                ['text' => __('On quotation', 'ordermaterial')],
                ['widget' => ['type' => 'yesno', 'name' => 'custom[2]', 'value' => $is_specific]],
                ['colspan' => 2],
            ];
            $rows[] = [
                ['text' => __('Accessory', 'ordermaterial')],
                ['widget' => ['type' => 'yesno', 'name' => 'custom[3]', 'value' => $is_accessory]],
                ['colspan' => 2],
            ];
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_customvalue_fixed.html.twig',
            ['rows' => $rows],
        );

        // Display order of the references of the type, reordered by drag and drop
        // (public/scripts/basket_order.js)
        $types_id = (int) ($params['item'] ?? 0);
        if ($types_id > 0 && Basketobject::canUpdate()) {
            echo TemplateRenderer::getInstance()->render('@metademands/fields/field_basket_order.html.twig', [
                'types_id'    => $types_id,
                'type_name'   => \Dropdown::getDropdownName(Basketobjecttype::getTable(), $types_id),
                'references'  => Basketobject::getOrderedOfType($types_id),
                'order_url'   => PLUGIN_METADEMANDS_WEBDIR . '/ajax/basketobject_order.php',
                'catalog_url' => Basketobject::getSearchURL(),
                'form_url'    => Basketobject::getFormURL(),
            ]);
        }
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        $label = __('Value to check', 'metademands')
            . " ( " . \Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";

        ob_start();

        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        //        if ($item == 0) {
        //            foreach ($existing_options as $existing_option) {
        //                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
        //            }
        //        }
        $name = "check_value";
        $cond = [];

        if (!empty($params['custom_values'])) {
            $options = $params['custom_values'];
            if (is_array($options)) {
                foreach ($options as $type_group => $values) {
                    $cond[$type_group] = $values;
                }
            }
        }
        $cond['plugin_metademands_basketobjecttypes_id'] = $params['item'];

        Basketobject::dropdown([
            'name' => $name,
            'entity' => $_SESSION['glpiactiveentities'],
            'value' => $params['check_value'],
            //                                            'readonly'  => true,
            'condition' => $cond,
            'display' => true,
            'used' => $already_used,
        ]);

        $cell_content = ob_get_clean();

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => false,
            'with_tech_group' => false,
            'content'         => $cell_content,
        ];

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => '',
                'label'             => $label,
                'label_colspan'     => 2,
                'regex_html'        => '',
                'valuetocheck'      => $valuetocheck,
                'link_html'         => $link_html,
            ],
        );
    }

    public static function showValueToCheck($item, $params)
    {
        //        $field = new FieldOption();
        //        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        //        $already_used = [];
        //        if ($item->getID() == 0) {
        //            foreach ($existing_options as $existing_option) {
        //                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
        //            }
        //        }
        return \Dropdown::getDropdownName('glpi_plugin_metademands_basketobjects', $params['check_value']);
        //        \Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    /**
     * @param array $value
     * @param array $fields
     * @return bool
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg     = "";
        $checkKo = 0;

        if ($value['is_mandatory'] && ($fields['value'] === null || $fields['value'] === '')) {
            $msg     = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        return true;
    }

    public static function showParamsValueToCheck($params): string
    {
        return \Dropdown::getDropdownName('glpi_plugin_metademands_basketobjects', $params['check_value']);
    }

    public static function fieldsMandatoryScript($data)
    {
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize($data['custom_values']) : [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            // With quantities: any quantity above zero
            FieldOption::displayMandatoryTrigger(
                $data,
                ['name' => 'quantity[' . $data['id'] . ']', 'match' => 'prefix'],
                'quantity',
                ['current' => isset($data['value']) && !is_array($data['value']) ? [$data['value']] : []],
            );
            return;
        }
        $values = $data['value'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = [
            'any'     => [-1],
            'current' => $values,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['check' => array_map('strval', array_values($values))];
        }
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function taskScript($data)
    {
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize($data['custom_values']) : [];
        $withquantity = isset($custom_values[0]) && $custom_values[0] == 1;
        $options = [
            'any'        => [-1],
            'defaults'   => MetademandTask::getDefaultValues($data['default'] ?? ''),
            'next_title' => 'savenext',
        ];
        if ($withquantity) {
            MetademandTask::displayTaskTrigger(
                $data,
                ['name' => 'quantity[' . $data['id'] . ']', 'match' => 'prefix'],
                'quantity',
                $options,
            );
            return;
        }
        if (isset($data['value']) && is_array($data['value'])) {
            $options['restore'] = ['check' => array_map('strval', array_values($data['value']))];
        }
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function fieldsHiddenScript($data)
    {
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize($data['custom_values']) : [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            // With quantities: any quantity above zero
            FieldOption::displayHiddenTrigger(
                $data,
                ['name' => 'quantity[' . $data['id'] . ']', 'match' => 'prefix'],
                'quantity',
                ['current' => isset($data['value']) && !is_array($data['value']) ? [$data['value']] : []],
            );
            return;
        }
        $values = $data['value'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = [
            'any'     => [-1],
            'current' => $values,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['check' => array_map('strval', array_values($values))];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function blocksHiddenScript($data)
    {
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize($data['custom_values']) : [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            // With quantities: any quantity above zero
            FieldOption::displayBlockTrigger(
                $data,
                ['name' => 'quantity[' . $data['id'] . ']', 'match' => 'prefix'],
                'quantity',
                [],
            );
            return;
        }
        $values = $data['value'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = [
            'any' => [-1],
        ];
        if (count($values) > 0) {
            $options['restore'] = ['check' => array_map('strval', array_values($values))];
        }
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }
    /**
     * Label and value of one field of a basket, as a pair of table cells. The method
     * used to print them, which forced its only caller to buffer the output.
     *
     * @param int   $id    identifier of the field
     * @param mixed $value value the requester filled in
     *
     * @return string
     */
    public static function retrieveDatasByType($id, $value): string
    {
        $cells = self::getFilledFieldCells($id, $value);
        if ($cells === null) {
            return '';
        }

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_basket_row_cells.html.twig',
            $cells,
        );
    }

    /**
     * Context of field_basket_row_cells.html.twig for one field the requester filled in.
     *
     * @param int   $id    field id
     * @param mixed $value value the requester filled in
     *
     * @return array|null null for a basket field or a field left empty
     */
    private static function getFilledFieldCells($id, $value): ?array
    {
        $field = new Field();
        if (!$field->getFromDB($id)) {
            return null;
        }

        // A basket field, or a field left empty, contributes no cell at all.
        if (empty($value) || !isset($field->fields['type']) || $field->fields['type'] == "basket") {
            return null;
        }

        if (empty($label = Field::displayField($field->fields['id'], 'name'))) {
            $label = $field->fields['name'];
        }

        $label2 = '';
        if ($field->fields['type'] == "date_interval") {
            if (empty($label2 = Field::displayField($field->fields['id'], 'label2'))) {
                $label2 = $field->fields['label2'];
            }
            $label2 = Toolbox::stripTags($label2);
        }

        $lang = $_SESSION['glpilanguage'];

        $values['value'] = $value;
        $values['item']  = $field->fields['item'];

        $params = Field::getAllParamsFromField($field);
        $values = array_merge($values, $params);

        // Every branch below but one returns plain text read back from the database — a
        // dropdown label, an LDAP attribute, a custom value, a raw column for number, range
        // and time — auto-escaped by the template. Only the textarea branch carries markup,
        // sanitized by the template as rich text.
        $is_rich = false;

        switch ($field->fields['type']) {
            case 'dropdown':
                $value_html = Dropdown::getFieldValue($values);
                break;
            case 'dropdown_object':
                $value_html = Dropdownobject::getFieldValue($values);
                break;
            case 'dropdown_ldap':
                $value_html = Ldapdropdown::getFieldValue($values);
                break;
            case 'dropdown_meta':
                $value_html = Dropdownmeta::getFieldValue($values, $lang);
                break;
            case 'dropdown_multiple':
                $value_html = Dropdownmultiple::getFieldValue($values, $lang);
                break;
            case 'link':
                $value_html = Link::getFieldValue($values);
                break;
            case 'textarea':
                $value_html = Textarea::getFieldValue($values);
                $is_rich = true;
                break;
            case 'text':
                $value_html = Text::getFieldValue($values);
                break;
            case 'tel':
                $value_html = Tel::getFieldValue($values);
                break;
            case 'email':
                $value_html = Email::getFieldValue($values);
                break;
            case 'url':
                $value_html = Url::getFieldValue($values);
                break;
            case 'checkbox':
                $value_html = Checkbox::getFieldValue($values, $lang);
                break;
            case 'radio':
                $value_html = Radio::getFieldValue($values, $label, $lang);
                break;
            case 'date':
                $value_html = Date::getFieldValue($values);
                break;
            case 'time':
                $value_html = Time::getFieldValue($values);
                break;
            case 'datetime':
                $value_html = Datetime::getFieldValue($values);
                break;
            case 'date_interval':
                $value_html = Dateinterval::getFieldValue($values);
                break;
            case 'datetime_interval':
                $value_html = Datetimeinterval::getFieldValue($values);
                break;
            case 'number':
                $value_html = Number::getFieldValue($values);
                break;
            case 'range':
                $value_html = Range::getFieldValue($values);
                break;
            case 'yesno':
                $value_html = Yesno::getFieldValue($values);
                break;
            default:
                $value_html = (string) $value;
                break;
        }

        return [
            'label'   => (string) $label,
            'label2'  => (string) $label2,
            'value'   => (string) $value_html,
            'is_rich' => $is_rich,
        ];
    }

    /**
     * Render the summary of a basket: the fields the requester filled in, the objects
     * they picked, their quantities and, when a price plugin is active, their prices.
     *
     * @param array $fields
     *
     * @return string
     */
    public static function displayBasketSummary($fields)
    {
        $materials = $fields['field'] ?? [];
        $quantities = $fields['quantity'] ?? [];
        $meta_id = $fields['metademands_id'] ?? 0;
        $has_materials = is_array($materials) && count($materials) > 0;

        $metademands = new Metademand();
        $has_meta = $metademands->getFromDB($meta_id);

        $title_color = '#000';
        if ($has_meta && !empty($own_title_color = Metademand::getTitleColor($metademands->fields))) {
            $title_color = $own_title_color;
        }

        $summary = $has_materials
            ? self::getBasketSummaryTable($materials, $quantities, $meta_id)
            : [];

        $config = Config::getInstance();
        $use_draft = $has_materials && $config['use_draft'];

        // The order button used to carry its own inline <script>, with the meta-demand
        // name interpolated into a JS string literal. Every value it needs now travels
        // as a data attribute read by public/scripts/wizard_form.js.
        $order = [];
        if ($has_materials && $has_meta) {
            $current_ticket = (int) ($fields['tickets_id'] ?? 0);
            $order = [
                'meta_id' => (int) $meta_id,
                'form_name' => $metademands->fields['name'] . '_'
                    . $_SESSION['glpi_currenttime'] . '_' . $_SESSION['glpiID'],
                'add_url' => PLUGIN_METADEMANDS_WEBDIR . '/ajax/addform.php',
                'create_url' => PLUGIN_METADEMANDS_WEBDIR . '/ajax/createmetademands.php',
                // meta_validated is deliberately left empty: the legacy code interpolated
                // a variable that was hardcoded to false right above it.
                'wizard_url' => PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php?'
                    . ($current_ticket > 0 ? 'current_ticket_id=' . $current_ticket . '&meta_validated=&' : '')
                    . 'metademands_id=' . (int) $meta_id . '&step=create_metademands',
            ];
        }

        return TemplateRenderer::getInstance()->render('@metademands/fields/basket_summary.html.twig', [
            'field_rows' => $has_materials ? self::getFilledFieldRows($materials) : [],
            'has_meta' => $has_meta,
            'title_color' => $title_color,
            'has_materials' => $has_materials,
            'headers' => $summary['headers'] ?? [],
            'rows' => $summary['rows'] ?? [],
            'footers' => $summary['footers'] ?? [],
            'use_draft' => $use_draft,
            'draft_mode' => Draft::BASKET_MODE,
            'order' => $order,
        ]);
    }


    /**
     * Label and value of every field the requester filled in, grouped
     * two fields to a row.
     *
     * @param array $materials
     *
     * @return array[] rows, each holding up to two contexts of field_basket_row_cells.html.twig
     */
    private static function getFilledFieldRows(array $materials): array
    {
        $cells = [];
        foreach ($materials as $id => $value) {
            // A basket field, or a field left empty, renders nothing at all.
            $field_cells = self::getFilledFieldCells($id, $value);
            if ($field_cells !== null) {
                $cells[] = $field_cells;
            }
        }

        // The legacy loop opened a <tr> only on the third field and closed it right
        // away, leaving the first two fields as cells outside any row. Two fields to a
        // row is what its $columns = 2 meant.
        return array_chunk($cells, 2);
    }


    /**
     * Tell whether the basket fields ask for a quantity and for a price.
     *
     * @param array $materials
     *
     * @return array [with price, with quantity, with reference, with description]
     */
    private static function getBasketPriceFlags(array $materials): array
    {
        $withprice = false;
        $withquantity = false;
        $withreference = false;
        $withdescription = false;

        foreach (array_keys($materials) as $id) {
            $field = new Field();
            if (!$field->getFromDB($id)) {
                continue;
            }

            // getAllParamsFromField() already returns the values decoded, the legacy
            // code ran them through FieldParameter::_unserialize() a second time.
            $params = Field::getAllParamsFromField($field);
            $custom_values = $params['custom_values'] ?? [];

            if (($custom_values[0] ?? 0) == 1) {
                $withquantity = true;
            }
            if (($custom_values[1] ?? 0) == 1) {
                $withprice = true;
            }
            // Enabled by default, see showFieldCustomValues().
            if (($custom_values[5] ?? 1) == 1) {
                $withreference = true;
            }
            if (($custom_values[6] ?? 1) == 1) {
                $withdescription = true;
            }
        }

        return [$withprice, $withquantity, $withreference, $withdescription];
    }


    /**
     * Build the headers, the rows and the totals of the basket table.
     *
     * @param array $materials
     * @param array $quantities
     * @param int   $meta_id
     *
     * @return array
     */
    private static function getBasketSummaryTable(array $materials, array $quantities, $meta_id): array
    {
        // The classes of the price plugins only exist when those plugins are loaded.
        $has_ordermaterial = Plugin::isPluginActive('ordermaterial')
            && (new PluginOrdermaterialMetademand())->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id]);

        $has_orderfollowup = Plugin::isPluginActive('orderfollowup')
            && (new OrderMetademand())->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id]);

        // The legacy code read these two flags twice: once to pick the headers, once to
        // fill the rows. The second read tested $field->fields['custom_values'], a
        // column glpi_plugin_metademands_fields does not have, so it was always false:
        // the price columns were announced by the header and then left empty, and the
        // grand total never showed up at all.
        [$withprice, $withquantity, $withreference, $withdescription] = self::getBasketPriceFlags($materials);

        $headers = [];
        if ($withreference) {
            $headers[] = ['label' => __('Reference', 'metademands')];
        }
        $headers[] = ['label' => __('Designation', 'metademands')];
        if ($withdescription) {
            $headers[] = ['label' => __('Description')];
        }
        if ($has_ordermaterial) {
            $headers[] = ['label' => __('Estimated unit price', 'ordermaterial')];
        }
        if ($has_orderfollowup) {
            if ($withquantity) {
                $headers[] = ['label' => __('Unit', 'orderfollowup')];
            }
            if ($withprice) {
                $headers[] = ['label' => __('Unit price (HT)', 'orderfollowup')];
            }
        }
        // "With quantity" disabled: every object counts for one, the quantity column and
        // a total counting objects would only show 1 (getBasketSummaryRow())
        if ($withquantity) {
            $headers[] = ['label' => __('Quantity', 'metademands')];
        }
        if ($withprice && ($has_ordermaterial || $has_orderfollowup)) {
            $headers[] = [
                'label' => $has_orderfollowup
                    ? __('Total (HT)', 'orderfollowup')
                    : __('Total', 'metademands'),
                'align' => 'right',
            ];
        } elseif ($withquantity) {
            $headers[] = ['label' => __('Total', 'metademands'), 'align' => 'right'];
        }

        $rows = [];
        $grandtotal = 0;
        foreach ($materials as $id => $material) {
            if (!is_array($material)) {
                continue;
            }

            foreach ($material as $mat_id) {
                $basket_obj = new Basketobject();
                if (!$basket_obj->getFromDB($mat_id)) {
                    continue;
                }

                $quantity = $withquantity ? 0 : 1;
                if (isset($quantities[$id][$mat_id])) {
                    $quantity = $quantities[$id][$mat_id];
                }
                if ($withquantity && $quantity == 0) {
                    continue;
                }

                $rows[] = self::getBasketSummaryRow(
                    $basket_obj,
                    $mat_id,
                    $quantity,
                    $withprice,
                    $has_ordermaterial,
                    $has_orderfollowup,
                    $withquantity,
                    $withreference,
                    $withdescription,
                    $grandtotal,
                );
            }
        }

        $footers = [];
        if ($withprice && ($has_ordermaterial || $has_orderfollowup)) {
            $footers[] = [
                // The legacy colspan was a literal that did not match the number of
                // columns the headers above had just declared.
                'colspan' => count($headers) - 1,
                'label' => $has_ordermaterial
                    ? __('Grand total', 'ordermaterial')
                    : __('Grand total (HT)', 'orderfollowup'),
                'value' => Html::formatNumber($grandtotal, false, 2) . ' €',
                'note' => $has_ordermaterial
                    ? __('* The prices are estimates and do not act as an estimate', 'ordermaterial')
                    : '',
            ];
        }

        return ['headers' => $headers, 'rows' => $rows, 'footers' => $footers];
    }


    /**
     * Build one row of the basket table and add its total to the grand total.
     *
     * @param Basketobject $basket_obj
     * @param int          $mat_id
     * @param int|float    $quantity
     * @param bool         $withprice
     * @param bool         $has_ordermaterial
     * @param bool         $has_orderfollowup
     * @param bool         $withquantity
     * @param bool         $withreference
     * @param bool         $withdescription
     * @param float        $grandtotal
     *
     * @return array
     */
    private static function getBasketSummaryRow(
        $basket_obj,
        $mat_id,
        $quantity,
        bool $withprice,
        bool $has_ordermaterial,
        bool $has_orderfollowup,
        bool $withquantity,
        bool $withreference,
        bool $withdescription,
        &$grandtotal,
    ): array {
        $cells = [];
        if ($withreference) {
            $cells[] = ['value' => $basket_obj->fields['reference']];
        }
        $cells[] = ['value' => $basket_obj->getName()];
        if ($withdescription) {
            $cells[] = ['value' => $basket_obj->fields['description']];
        }

        $totalrow = $quantity;
        $on_quotation = false;

        if ($has_ordermaterial) {
            $material = new PluginOrdermaterialMaterial();
            if ($material->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                $on_quotation = $material->fields['is_specific'] == 1;
                $cells[] = ['value' => $on_quotation
                    ? __('On quotation', 'ordermaterial')
                    : ($withprice ? Html::formatNumber($material->fields['estimated_price'], false, 2) . ' €' : '')];

                if ($withprice) {
                    $totalrow = $quantity * $material->fields['estimated_price'];
                }
            } else {
                $cells[] = ['value' => ''];
            }
        }

        if ($has_orderfollowup) {
            $material = new Material();
            if ($material->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                if ($withquantity) {
                    $cells[] = ['value' => $material->fields['unit']];
                }
                if ($withprice) {
                    $cells[] = ['value' => Html::formatNumber($material->fields['unit_price'], false, 2) . ' €'];
                    $totalrow = $quantity * $material->fields['unit_price'];
                }
            } else {
                // The legacy code pushed a single cell here even when the header had
                // declared both the unit and the unit price, shifting the whole row.
                if ($withquantity) {
                    $cells[] = ['value' => ''];
                }
                if ($withprice) {
                    $cells[] = ['value' => ''];
                }
            }
        }

        // Without quantity every object counts for one: no quantity column, and no line
        // total unless it is a price (see getBasketSummaryTable())
        if ($withquantity) {
            $cells[] = ['value' => $quantity];
        }

        if ($withprice && ($has_ordermaterial || $has_orderfollowup)) {
            $cells[] = [
                'value' => $on_quotation
                    ? __('On quotation', 'ordermaterial')
                    : Html::formatNumber($totalrow, false, 2) . ' €',
                'align' => 'right',
            ];
        } elseif ($withquantity) {
            $cells[] = ['value' => Html::formatNumber($totalrow, false, 0), 'align' => 'right'];
        }

        $grandtotal += $totalrow;

        return $cells;
    }

    public static function checkConditions($data, $metaparams)
    {
        $custom_values = isset($data['custom_values'])
            ? FieldParameter::_unserialize($data['custom_values'])
            : [];
        // With quantities, the conditions test the quantity selects
        $name = (isset($custom_values[0]) && $custom_values[0] == 1 ? 'quantity[' : 'field[') . $data['id'] . ']';
        Condition::displayTrigger($data, $metaparams, ['name' => $name, 'match' => 'prefix']);
    }

    public static function getFieldValue(
        $field
    ) {
        return $field['value'];
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $title_style,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        //        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;

        $meta_id = $field['plugin_metademands_metademands_id'];
        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'])) {
            $quantities = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'];
        }
        $materials = $field["value"];

        if (is_object($materials)) {
            $materials = json_decode(json_encode($materials), true);
        }

        $total = 0;
        $has_ordermaterial = false;
        if (Plugin::isPluginActive('ordermaterial')) {
            $ordermaterialmeta = new PluginOrdermaterialMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                $has_ordermaterial = true;
            }
        }

        // Options of the basket field, see showFieldCustomValues().
        $custom_values = [];
        $fieldmeta = new FieldParameter();
        if ($fieldmeta->getFromDBByCrit(["plugin_metademands_fields_id" => $field['id']])) {
            $custom_values = FieldParameter::_unserialize($fieldmeta->fields['custom']);
        }
        if (!is_array($custom_values)) {
            $custom_values = [];
        }
        $withquantity = ($custom_values[0] ?? 0) == 1;
        $withreference = ($custom_values[5] ?? 1) == 1;
        $withdescription = ($custom_values[6] ?? 1) == 1;

        $has_orderfollowup = false;
        $withprice = false;
        if (Plugin::isPluginActive('orderfollowup')) {
            $ordermaterialmeta = new OrderMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                $has_orderfollowup = true;
            }
            $withprice = ($custom_values[1] ?? 0) == 1;
        }

        if (is_array($materials) && count($materials) > 0) {
            $header = [];
            if ($withreference) {
                $header[] = __('Reference', 'metademands');
            }
            $header[] = __('Designation', 'metademands');
            if ($withdescription) {
                $header[] = __('Description');
            }
            if ($has_ordermaterial) {
                $header[] = __('Order type', 'ordermaterial');
            }
            // Without quantity, every object counts for one unit: the unit and quantity
            // columns would only show 1.
            if ($has_orderfollowup && $withquantity) {
                $header[] = __('Unit', 'orderfollowup');
            }
            if ($withquantity) {
                $header[] = __('Quantity', 'metademands');
            }
            if ($has_ordermaterial) {
                $header[] = __('Estimated unit price', 'ordermaterial');
            }
            if ($has_orderfollowup && $withprice) {
                $header[] = __('Unit price (HT)', 'orderfollowup');
                $header[] = __('Total (HT)', 'orderfollowup');
            }
            $rows = [];
            foreach ($header as $text) {
                $rows[0][] = ['text' => $text, 'heading' => true, 'table_only' => true];
            }

            foreach ($materials as $mat_id => $q) {
                $material = new Basketobject();
                $material->getFromDB($mat_id);

                if ($withquantity == false) {
                    $quantity = 1;
                } else {
                    $quantity = 0;
                }

                if (isset($quantities[$field['id']][$mat_id])) {
                    $quantity = $quantities[$field['id']][$mat_id];
                }
                if ($withquantity == true && $quantity == 0) {
                    continue;
                }

                $row = [];
                if ($withreference) {
                    $row[] = ['text' => (string) $material->fields['reference']];
                }
                $row[] = ['text' => (string) $material->getName()];
                if ($withdescription) {
                    $row[] = ['text' => (string) $material->fields['description']];
                }

                $catalog_material = null;
                if ($has_ordermaterial) {
                    $ordermaterial = new PluginOrdermaterialMaterial();
                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                        $catalog_material = $ordermaterial;
                        $row[] = ['text' => $ordermaterial->fields['is_specific'] ? __('On quotation', 'ordermaterial') : __('On catalog', 'ordermaterial')];
                    }
                }

                $followup_material = null;
                if ($has_orderfollowup) {
                    $ordermaterial = new Material();
                    if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                        $followup_material = $ordermaterial;
                    }
                    // Blank when no price is set for this object, one cell per header
                    if ($withquantity) {
                        $row[] = ['text' => $followup_material !== null ? (string) $followup_material->fields['unit'] : ''];
                    }
                }

                if ($withquantity) {
                    $row[] = ['text' => (string) $quantity];
                }

                $totalrow = $quantity;
                if ($catalog_material !== null) {
                    $row[] = ['text' => Html::formatNumber($catalog_material->fields['estimated_price'], false, 2) . " €"];
                    $totalrow = $quantity * $catalog_material->fields['estimated_price'];
                }
                if ($followup_material !== null) {
                    if ($withprice) {
                        $row[] = ['text' => Html::formatNumber($followup_material->fields['unit_price'], false, 2) . " €"];
                    }
                    // With prices, a reference at 0 € costs 0 €: it no longer counts as its quantity
                    if ($withprice || $followup_material->fields['unit_price'] != 0) {
                        $totalrow = $quantity * $followup_material->fields['unit_price'];
                    } else {
                        $totalrow = $quantity;
                    }
                    if ($withprice) {
                        $row[] = ['text' => Html::formatNumber($totalrow, false, 2) . " €"];
                    }
                } elseif ($has_orderfollowup && $withprice) {
                    // No price set for this object: blank price and total, nothing added
                    $totalrow = 0;
                    $row[] = ['text' => ''];
                    $row[] = ['text' => ''];
                }

                $rows[] = $row;
                $total += $totalrow;
            }

            // The label spans every column but the last one, which holds the total: the
            // former literal column counts did not follow the optional columns.
            $colspan = count($header) - 1;
            $total_row = [];
            if (!Plugin::isPluginActive('orderfollowup')) {
                $total_row[] = ['text' => __('Total', 'metademands'), 'heading' => true, 'colspan' => $colspan, 'table_only' => true];
            } elseif ($has_orderfollowup) {
                $total_label = $withprice ? __('Grand total (HT)', 'orderfollowup') : __('Total', 'metademands');
                $total_row[] = ['text' => $total_label, 'heading' => true, 'colspan' => $colspan, 'table_only' => true];
            }
            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                $total_final = (string) $total;
            } else {
                $total_final = Html::formatNumber($total, false, 2);
            }
            if ($has_ordermaterial && isset($custom_values[1]) && $custom_values[1] == 1) {
                $total_final .= " €";
            }
            if ($has_orderfollowup && isset($custom_values[1]) && $custom_values[1] == 1) {
                $total_final .= " €";
            }
            $total_row[] = ['text' => $total_final];
            // Without quantity nor price, the total would only count the objects listed
            if ($withquantity || $has_ordermaterial || ($has_orderfollowup && $withprice)) {
                $rows[] = $total_row;
            }

            $result[$field['rank']]['content'] .= Field::renderContentRows(
                (bool) $formatAsTable,
                (string) $title_style,
                'border: 1px solid black;',
                $rows,
            );
        }

        return $result;
    }

    public static function displayFieldPDF($elt, $fields, $label)
    {
        $value = "";

        if (isset($_SESSION['plugin_metademands'][$elt['plugin_metademands_metademands_id']]['quantities'])) {
            $quantities = $_SESSION['plugin_metademands'][$elt['plugin_metademands_metademands_id']]['quantities'];
        }

        $materials = $fields[$elt['id']] ?? [];

        if (Plugin::isPluginActive('orderfollowup')) {
            $values = [];
            $ordermaterialmeta = new OrderMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(
                ['plugin_metademands_metademands_id' => $elt['plugin_metademands_metademands_id']],
            )) {
                $order = new Order();
                $orders = $order->find(['tickets_id' => $fields['tickets_id'] ?? 0]);

                // "With unit price (HT)" option of the basket field (custom index 1).
                // When disabled, the unit price / total / grand total columns must not
                // appear in the PDF (index 0 is the "with quantity" option).
                // Indexes 0, 5 and 6 (quantity, reference, description) drop their
                // columns the same way; reference and description default to enabled.
                $custom_values = [];
                $fieldparam = new FieldParameter();
                if ($fieldparam->getFromDBByCrit(['plugin_metademands_fields_id' => $elt['id']])) {
                    $custom_values = FieldParameter::_unserialize($fieldparam->fields['custom']);
                }
                if (!is_array($custom_values)) {
                    $custom_values = [];
                }
                $with_price = ($custom_values[1] ?? 0) == 1;
                $with_quantity = ($custom_values[0] ?? 0) == 1;
                $with_reference = ($custom_values[5] ?? 1) == 1;
                $with_description = ($custom_values[6] ?? 1) == 1;

                foreach ($orders as $id => $item) {
                    $material = new Basketobject();
                    $material->getFromDB($item['plugin_metademands_basketobjects_id']);

                    // MetademandPdf extends \TCPDF, which is UTF-8 native. Feeding it
                    // ISO-8859-1 (what Toolbox::decodeFromUtf8() produces) drops every
                    // accented character — that is why the "Référence"/"Désignation"/
                    // "Quantité"/"Unité" column titles and the "Unité" cell vanished from
                    // the basket table. Keep the strings UTF-8: no decode on the PDF path.
                    if ($with_reference) {
                        $values[$id][__('Reference', 'metademands')] = $material->fields['reference'];
                    }
                    $values[$id][__('Designation', 'metademands')] = $material->getName();
                    if ($with_description) {
                        $values[$id][__('Description')] = $material->fields['description'];
                    }
                    // Without quantity, every object counts for one: no column showing 1
                    if ($with_quantity) {
                        $values[$id][__('Quantity', 'metademands')] = $item['quantity'];
                    }

                    $ordermaterial = new Material();
                    if ($ordermaterial->getFromDBByCrit(
                        ['plugin_metademands_basketobjects_id' => $item['plugin_metademands_basketobjects_id']],
                    )) {
                        if ($with_quantity) {
                            $values[$id][__('Unit', 'orderfollowup')] = $ordermaterial->fields['unit'];
                        }

                        if ($with_price) {
                            $values[$id][__('Unit price (HT)', 'orderfollowup')]
                                = Html::formatNumber($ordermaterial->fields['unit_price'], false, 2);

                            $total = $ordermaterial->fields['unit_price'] * $item['quantity'];
                            $values[$id][__('Total (HT)', 'orderfollowup')] = Html::formatNumber(
                                $total,
                                false,
                                2,
                            );
                        }
                    } else {
                        // No price set for this object: blank cells keep the columns aligned
                        if ($with_quantity) {
                            $values[$id][__('Unit', 'orderfollowup')] = '';
                        }
                        if ($with_price) {
                            $values[$id][__('Unit price (HT)', 'orderfollowup')] = '';
                            $values[$id][__('Total (HT)', 'orderfollowup')] = '';
                        }
                    }
                }

                return $values;
            }
        }

        // Fallback: plain material listing. Reached when orderfollowup is inactive, or when
        // it is active but this basket is not linked to an order metademand. Without it the
        // orderfollowup branch above would fall through and return null, leaving the PDF
        // basket empty for every non-order basket while orderfollowup is installed.
        if (is_array($materials)) {
            foreach ($materials as $id => $mat_id) {
                $material = new Basketobject();
                $material->getFromDB($id);

                $value .= $material->getName();
                $quantity = 0;
                if (isset($quantities[$elt['id']][$id])) {
                    $quantity = $quantities[$elt['id']][$id];
                    if ($quantity > 0) {
                        $value .= " - " . __('Quantity', 'metademands') . " : " . $quantity;
                    }
                }
                $value .= "\n";
            }
        }
        // No decodeFromUtf8 here: the value goes to TCPDF (UTF-8 native).

        return $value;
    }
}
