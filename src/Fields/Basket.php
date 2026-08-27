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
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Orderfollowup\Order;
use Html;
use Plugin;
use GlpiPlugin\Metademands\Basketobject;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Metademands\Wizard;
use GlpiPlugin\Orderfollowup\Material;
use GlpiPlugin\Orderfollowup\Metademand as OrderMetademand;
use PluginOrdermaterialMaterial;
use PluginOrdermaterialMetademand;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

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
            $background_color = "background-color:" . htmlspecialchars($metademand->fields['background_color'], ENT_QUOTES) . ";";
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

        $criteria = [
            'FROM' => [Basketobject::getTable()],
            'SELECT' => [Basketobject::getTable() => '*'],
            'ORDER' => ['name', 'description'],
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

        if (Plugin::isPluginActive('orderfollowup')) {
            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                $where = ['unit_price' => ['>', 0]];
            }

            if (count($where)) {
                $criteria['LEFT JOIN'][Material::getTable()] = [
                    'ON' => [
                        Material::getTable() => 'plugin_metademands_basketobjects_id',
                        Basketobject::getTable() => 'id',
                    ],
                ];
            }
        }

        if (count($where)) {
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $where);
        }

        $materials = $DB->request($criteria);
        $nb = count($materials);

        // Capture-and-render: the tabular/conditional logic and the ordermaterial /
        // orderfollowup integration stay in PHP; the widget (showNumber), the inline
        // total-row script (Ajax::updateItemJsCode), the checkbox input and the
        // sanitized description (getSafeHtml) are captured as raw strings and injected
        // via |raw at their exact positions. Reference / Designation are passed as raw
        // text so the template auto-escapes them (defense-in-depth). background_color is
        // already htmlspecialchars'd (style attribute), header labels and placeholders
        // are trusted translation strings: both emitted raw for byte-identity.
        $headers = [];
        $headers[] = ['label' => __('Reference', 'metademands'), 'style' => $background_color];
        $headers[] = ['label' => __('Designation', 'metademands'), 'style' => $background_color];
        $headers[] = ['label' => __('Description'), 'style' => $background_color];

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
                $headers[] = ['label' => __('Unit', 'orderfollowup'), 'style' => $background_color];

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

                $cells[] = ['t' => (string) $material['reference']];
                $cells[] = ['t' => (string) $material['name']];
                $cells[] = ['h' => RichText::getSafeHtml($material['description'])];

                if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            if ($ordermaterial->fields['is_specific'] == 1) {
                                $cells[] = ['h' => __('On quotation', 'ordermaterial')];
                            } else {
                                $cells[] = [
                                    'h' => Html::formatNumber(
                                        $ordermaterial->fields['estimated_price'],
                                        false,
                                        2,
                                    ) . " €",
                                ];
                            }
                        } else {
                            $cells[] = ['h' => ''];
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
                            $cells[] = ['h' => (string) $ordermaterial->fields['unit']];

                            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                                $cells[] = [
                                    'h' => Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €",
                                ];
                            }
                        } else {
                            $cells[] = ['h' => ''];
                            $cells[] = ['h' => ''];
                        }
                    }
                }

                $functiontotal = "plugin_metademands_load_totalrow" . $key;

                $rand = mt_rand();
                $name_field = "dropdown_quantity[" . $data['id'] . "][" . $key . "]";

                $opt = [
                    'min' => 0,
                    'max' => 1000,
                    'step' => 1,
                    'display' => false,
                    'rand' => $rand,
                    'on_change' => $functiontotal . '()',
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

                $qty_html = \Dropdown::showNumber("quantity[" . $data['id'] . "][" . $key . "]", $opt);

                $check_hidden = $namefield . "[" . $data['id'] . "]";
                $name_hidden = $namefield . "[" . $data['id'] . "][" . $key . "]";
                $qty_html .= "<script type='text/javascript'>";
                $qty_html .= "function plugin_metademands_load_totalrow$key(){";
                $params = [
                    'action' => 'loadTotalrow',
                    'quantity' => '__VALUE__',
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
                $qty_html .= Ajax::updateItemJsCode(
                    'plugin_metademands_totalrow' . $rand_totalrow,
                    PLUGIN_METADEMANDS_WEBDIR . '/ajax/totalrow.php',
                    $params,
                    $name_field . $rand,
                    false,
                );

                //                $params_total = ['action' => 'loadGrandTotal'];
                //                $field .= Ajax::updateItemJsCode('plugin_ordermaterial_grandtotal',
                //                    PLUGIN_ORDERMATERIAL_WEBDIR . '/ajax/totalrow.php',
                //                    $params_total, $name_field . $rand, false);
                $qty_html .= "}";

                $qty_html .= "</script>";

                $cells[] = ['h' => $qty_html];

                $cells[] = [
                    'h' => '',
                    'attrs' => " style='text-align: right;' id='plugin_metademands_totalrow$rand_totalrow'",
                ];

                $rows[] = $cells;
            }
        } else {
            foreach ($materials as $material) {
                $key = $material['id'];
                $cells = [];

                $cells[] = ['t' => (string) $material['reference']];
                $cells[] = ['t' => (string) $material['name']];
                $cells[] = ['h' => RichText::getSafeHtml($material['description'])];

                if (Plugin::isPluginActive('ordermaterial') && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id']],
                    )) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            if ($ordermaterial->fields['is_specific'] == 1) {
                                $cells[] = ['h' => __('On quotation', 'ordermaterial')];
                            } else {
                                $cells[] = [
                                    'h' => Html::formatNumber(
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
                        $ordermaterial = new Material();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $key])) {
                            $cells[] = ['h' => (string) $ordermaterial->fields['unit']];

                            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                                $cells[] = [
                                    'h' => Html::formatNumber($ordermaterial->fields['unit_price'], false, 2) . " €",
                                ];
                            }
                        }
                    }
                }

                $checked = '';
                $required = "";
                //                if ($data['is_mandatory'] == 1) {
                //                    $required = "required=required";
                //                }
                $value_check = $key;
                if (isset($value) && is_array($value)) {
                    foreach ($value as $val) {
                        if ($val == $key) {
                            $checked = "checked";
                        }
                    }
                }
                $checkbox_html = "<input $required class='form-check-input' type='checkbox'
                check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]'
                key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$value_check' $checked>";

                $cells[] = ['h' => $checkbox_html];

                $rows[] = $cells;
            }
        }

        $search_script = "";
        if ($nb > 1) {
            // basketSearchInit() lives in metademands.js, loaded in the footer via the
            // ADD_JAVASCRIPT hook. This inline script runs at parse time, before the footer
            // script exists, so defer the call until the function is defined.
            $search_script .= "<script>";
            $search_script .= "(function(){var run=function(){basketSearchInit($search_id);};";
            $search_script .= "if(typeof basketSearchInit==='function'){run();}";
            $search_script .= "else{document.addEventListener('DOMContentLoaded',run);}})();";
            $search_script .= "</script>";
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_basket.html.twig',
            [
                'background_style' => $background_color,
                'headers' => $headers,
                'nb' => $nb,
                'search_id' => $search_id,
                'ph_ref' => __('Search..', 'metademands'),
                'ph_name' => __('Search for names..', 'metademands'),
                'ph_desc' => __('Search for description..', 'metademands'),
                'rows' => $rows,
                'search_script' => $search_script,
            ],
        );
    }

    public static function showFieldCustomValues($params)
    {
        $quantity = $params['custom_values'][0] ?? 0;
        $price = $params['custom_values'][1] ?? 0;

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('With quantity', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('custom[0]', $quantity);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

        if (Plugin::isPluginActive('ordermaterial')
            || Plugin::isPluginActive('orderfollowup')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('With unit price (HT)', 'metademands');
            echo "</td>";
            echo "<td>";
            \Dropdown::showYesNo('custom[1]', $price);
            echo "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        }

        if (Plugin::isPluginActive('ordermaterial')) {
            $is_specific = $params['custom_values'][2] ?? 0;
            $is_accessory = $params['custom_values'][3] ?? 0;

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('On quotation', 'ordermaterial');
            echo "</td>";
            echo "<td>";
            \Dropdown::showYesNo('custom[2]', $is_specific);
            echo "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Accessory', 'ordermaterial');
            echo "</td>";
            echo "<td>";
            \Dropdown::showYesNo('custom[3]', $is_accessory);
            echo "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo Html::submit("", [
            'name' => 'update',
            'class' => 'btn btn-primary',
            'icon'  => 'ti ti-device-floppy']);
        echo "</td>";
        echo "</tr>";
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td colspan='2'>";
        echo __('Value to check', 'metademands');
        echo " ( " . \Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
        echo "</td>";
        echo "<td class = 'dropdown-valuetocheck'>";

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

        echo "</td>";

        echo "<script type = \"text/javascript\">
                 $('td.dropdown-valuetocheck select').on('change', function() {
                 let formOption = [
                     " . $params['ID'] . ",
                         $(this).val(),
                         $('select[name=\"plugin_metademands_tasks_id\"]').val(),
                         $('select[name=\"fields_link\"]').val(),
                         $('select[name=\"hidden_link\"]').val(),
                         $('select[name=\"hidden_block\"]').val(),
                         JSON.stringify($('select[name=\"childs_blocks[][]\"]').val()),
                         $('select[name=\"users_id_validate\"]').val(),
                         $('select[name=\"checkbox_id\"]').val(),
                         0,
                         0
                  ];

                     reloadviewOption(formOption);
                 });";

        echo " </script>";

        echo FieldOption::showLinkHtml($item->getID(), $params);
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

    public static function showParamsValueToCheck($params)
    {
        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_value_to_check.html.twig', [
            'value' => \Dropdown::getDropdownName('glpi_plugin_metademands_basketobjects', $params['check_value']),
        ]);
    }

    public static function fieldsMandatoryScript($data)
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
            $pre_onchange = "console.log('fieldsHiddenScript-basket $id');";
        }
        $withquantity = false;
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize(
            $data['custom_values'],
        ) : [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            $withquantity = true;
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
            }

            if ($withquantity == false) {
                $onchange = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            } else {
                $name = "quantity[" . $data["id"] . "]";

                $onchange = "$('[name^=\"$name\"]').change(function() {";
            }

            $onchange .= "var tohide = {};";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    if ($withquantity == false) {
                        $onchange .= " if (this.checked){";
                        //                                        foreach ($hidden_link as $key => $fields) {
                        $onchange .= " if ($(this).val() == $idc || $idc == -1) { ";
                    } else {
                        $onchange .= "if ($(this).val() > 0 ) { ";
                    }
                    $onchange .= "if ($fields_link in tohide) {
                             } else {
                                tohide[$fields_link] = true;
                             }
                             tohide[$fields_link] = false;
                          ";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $fields_link;
                    }

                    $onchange .= "$.each(tohide, function( key, value ) {
                                if (value == true) {
                                   var id = '#metademands_wizard_red'+ key;
                                   $(id).html('');
                                   sessionStorage.setItem('hiddenlink$name', key);
                                    " . FieldOption::resetMandatoryFieldsByField($name) . "
                                    $('[name =\"field['+key+']\"]').removeAttr('required');
                                    $('[name =\"field['+key+'-2]\"]').removeAttr('required');
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
                            });";

                    if ($withquantity == false) {
                        $onchange .= "} else {";

                        $onchange .= "if($(this).val() == $idc){
                                if($fields_link in tohide){

                                }else{
                                   tohide[$fields_link] = true;
                                }
                                $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                        $onchange .= "if($(value).val() == $idc || $idc == -1 ){
                                       tohide[$fields_link] = false;
                                    }";
                        $onchange .= "});";

                        $onchange .= "}";

                        $onchange .= "$.each( tohide, function( key, value ) {
                                if (value == true) {
                                   var id = '#metademands_wizard_red'+ key;
                                   $(id).html('');
                                   sessionStorage.setItem('hiddenlink$name', key);
                                   " . FieldOption::resetMandatoryFieldsByField($name) . "
                                   $('[name =\"field['+key+']\"]').removeAttr('required');
                                   $('[name =\"field['+key+'-2]\"]').removeAttr('required');
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
                             });";
                        $onchange .= "}
                        }";
                    } else {
                        $onchange .= "} else {";

                        $onchange .= "
//                                      var id = '#metademands_wizard_red'+ key;
//                                      $(id).html('');
//                                      sessionStorage.setItem('hiddenlink$name', key);
                                      " . FieldOption::resetMandatoryFieldsByField($name) . "
                                      $('[id-field =\"field" . $fields_link . "\"]').removeAttr('required');";

                        $onchange .= "}";
                    }
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
            $script = "console.log('taskScript-basket $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $script2 .= "$('[name^=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
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

            $withquantity = false;
            $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize(
                $data['custom_values'],
            ) : [];
            if (isset($custom_values[0]) && $custom_values[0] == 1) {
                $withquantity = true;
            }

            if ($withquantity == false) {
                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            } else {
                $name = "quantity[" . $data["id"] . "]";

                $script .= "$('[name^=\"$name\"]').change(function() {";
            }

            $script .= "var tohide = {};";
            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    if ($withquantity == false) {
                        $script .= " if (this.checked){";
                        //                                        foreach ($hidden_link as $key => $fields) {
                        $script .= " if ($(this).val() == $idc || $idc == -1) { ";
                    } else {
                        $script .= "if ($(this).val() > 0 ) { ";
                    }
                    $script .= "if ($tasks_id in tohide) {
                         } else {
                            tohide[$tasks_id] = true;
                         }
                         tohide[$tasks_id] = false;
                      ";

                    $script .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
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
                    $script .= "} else {
                $.ajax({
                                 url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                 data: { tasks_id: $tasks_id,
                              used: 0 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$title'
                                       }
                                    },
                            });
                         }

            ";
                    if ($withquantity == false) {
                        $script .= "}";
                    }
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
            $pre_onchange = "console.log('fieldsHiddenScript-basket $id');";
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

        $withquantity = false;
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize(
            $data['custom_values'],
        ) : [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            $withquantity = true;
        }

        if (count($check_values) > 0) {
            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                    $('[id-field =\"field" . $hidden_link . "-2\"]').hide();";
                }
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val(" . json_encode((string) $data['value'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ").trigger('change');";
            }

            if ($withquantity == false) {
                $onchange = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            } else {
                $name = "quantity[" . $data["id"] . "]";

                $onchange = "$('[name^=\"$name\"]').change(function() {";
            }

            $onchange .= "var tohide = {};";
            $display = 0;

            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    if ($withquantity == false) {
                        $onchange .= " if (this.checked){";
                        //                                        foreach ($hidden_link as $key => $fields) {
                        $onchange .= " if ($(this).val() == $idc || $idc == -1) { ";
                    } else {
                        $onchange .= "if ($(this).val() > 0 ) { ";
                    }
                    $onchange .= "if ($hidden_link in tohide) {
                         } else {
                            tohide[$hidden_link] = true;
                         }
                         tohide[$hidden_link] = false;
                      ";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_link;
                    }

                    //checkbox
                    $onchange .= "$.each(tohide, function( key, value ) {
                            if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            $('[id-field =\"field'+key+'-2\"]').hide();
                               sessionStorage.setItem('hiddenlink$name', key);
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
                        });";

                    if ($withquantity == false) {
                        $onchange .= "} else {";

                        $onchange .= "if($(this).val() == $idc){
                            if($hidden_link in tohide){

                            }else{
                               tohide[$hidden_link] = true;
                            }
                            $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                        $onchange .= "if($(value).val() == $idc || $idc == -1 ){
                                   tohide[$hidden_link] = false;
                                }";
                        $onchange .= "});";

                        $onchange .= "}";

                        $onchange .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                               $('[id-field =\"field'+key+'\"]').hide();
                               $('[id-field =\"field'+key+'-2\"]').hide();
                               sessionStorage.setItem('hiddenlink$name', key);
                               " . FieldOption::resetMandatoryFieldsByFieldForHidden($name) . "
                               $('[name =\"field['+key+']\"]').removeAttr('required');
                               $('[name =\"field['+key+'-2]\"]').removeAttr('required');
                            } else {
                               $('[id-field =\"field'+key+'\"]').show();
                               $('[id-field =\"field'+key+'-2\"]').show();
                            }
                         });";

                        $onchange .= "}
                }";
                    } else {
                        $onchange .= "} else {";

                        $onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();
                        $('[id-field =\"field" . $hidden_link . "-2\"]').hide();";

                        $onchange .= "}";
                    }
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

    public static function blocksHiddenScript($data)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $withquantity = false;
        $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize(
            $data['custom_values'],
        ) : [];
        if (isset($custom_values[0]) && $custom_values[0] == 1) {
            $withquantity = true;
        }

        if (count($check_values) > 0) {
            if ($withquantity == false) {
                $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            } else {
                $name = "quantity[" . $data["id"] . "]";

                $script = "$('[name^=\"$name\"]').change(function() {";
            }

            $script2 = "";
            $script .= "var tohide = {};";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_block'] as $hidden_block) {
                    if ($withquantity == false) {
                        $script .= " if (this.checked){ ";

                        $script .= "if($(this).val() == $idc || $idc == -1 ){ ";
                    } else {
                        $script .= "if($(this).val() > 0 ){ ";
                    }
                    $script .= " if ($hidden_block in tohide) {
                             } else {
                                tohide[$hidden_block] = true;
                             }
                             tohide[$hidden_block] = false;
                          }";

                    $script2 .= "if (document.getElementById('ablock" . $hidden_block . "'))
                document.getElementById('ablock" . $hidden_block . "').style.display = 'none';
                $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
                $('[bloc-id =\"subbloc" . $hidden_block . "\"]').hide();";
                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_block;
                    }

                    $script .= "$.each( tohide, function( key, value ) {
                            if(value == true){
                             $('[bloc-id =\"bloc'+key+'\"]').hide();
                             $('[bloc-id =\"subbloc'+key+'\"]').hide();
                             var id = 'ablock'+ key;
                             if (document.getElementById(id))
                             document.getElementById(id).style.display = 'none';
                             $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                switch(this.type) {
                                       case 'password':
                                       case 'text':
                                       case 'textarea':
                                       case 'file':
                                       case 'date':
                                       case 'number':
                                        case 'range':
                                       case 'tel':
                                       case 'email':
                                       case 'url':
                                           jQuery(this).val('');
                                           break;
                                       case 'select-one':
                                       case 'select-multiple':
                                           jQuery(this).val('0').trigger('change');
                                           jQuery(this).val('0');
                                           break;
                                       case 'checkbox':
                                       case 'radio':
                                           this.checked = false;
                                           break;
                                   }
                               });
                            } else {
                             var id = 'ablock'+ key;
                            if (document.getElementById(id))
                            document.getElementById(id).style.display = 'block';
                            $('[bloc-id =\"bloc'+key+'\"]').show();

                            }

                         });";

                    if ($withquantity == false) {
                        $script .= " } else { ";
                        //                                        foreach ($hidden_block as $key => $fields) {
                        $script .= "if ($(this).val() == $idc) {
                                 if ($hidden_block in tohide) {

                                 } else {
                                    tohide[$hidden_block] = true;
                                 }
                                 $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                        $script .= "if ($(value).val() == $idc || $idc == -1 ) {
                                   tohide[$hidden_block] = false;
                                }";
                        $script .= " });
                        }
                        ";
                        $script .= " }";
                    }
                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_block;
                    }

                    $script .= "$.each( tohide, function( key, value ) {
                            if(value == true){
                                 var id = 'ablock'+ key;
                                if (document.getElementById(id))
                                document.getElementById(id).style.display = 'none';
                                 $('[bloc-id =\"bloc'+key+'\"]').hide();
                                 $('[bloc-id =\"subbloc'+key+'\"]').hide();
                                 $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                    switch(this.type) {
                                           case 'password':
                                           case 'text':
                                           case 'textarea':
                                           case 'file':
                                           case 'date':
                                           case 'number':
                                            case 'range':
                                           case 'tel':
                                           case 'email':
                                           case 'url':
                                               jQuery(this).val('');
                                               break;
                                           case 'select-one':
                                           case 'select-multiple':
                                               jQuery(this).val('0').trigger('change');
                                               jQuery(this).val('0');
                                               break;
                                           case 'checkbox':
                                           case 'radio':
                                                if(this.checked == true) {
                                                    this.click();
                                                    this.checked = false;
                                                    break;
                                                }
                                       }
                                   });
                            } else {
                                 var id = 'ablock'+ key;
                                 if (document.getElementById(id))
                                 document.getElementById(id).style.display = 'block';
                                $('[bloc-id =\"bloc'+key+'\"]').show();
                                $('[bloc-id =\"subbloc'+key+'\"]').show();
                            }
                        });
                        ";
                }
            }
            $script .= "});";

            if ($display > 0) {
                $script2 .= "$('[bloc-id =\"bloc" . $display . "\"]').show();
                            $('[bloc-id =\"subbloc" . $display . "\"]').show();";
            }

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    /**
     * @param $idline
     * @param $values
     * @param $fields
     */
    public static function retrieveDatasByType($id, $value)
    {
        //        foreach ($fields as $k => $v) {

        $field = new Field();
        if ($field->getFromDB($id)) {
            //hide blocks
            //            if ($field->fields['type'] == 'informations' || $field->fields['type'] == 'title-block' || $field->fields['type'] == 'title') {
            //                continue;
            //            }

            if (!empty($value) && isset($field->fields['type']) && $field->fields['type'] != "basket") {
                //            echo "<tr class='tab_bg_1'>";

                echo "<td>";

                if (empty($label = Field::displayField($field->fields['id'], 'name'))) {
                    $label = $field->fields['name'];
                    echo htmlspecialchars((string) $label);
                }

                if ($field->fields['type'] == "date_interval") {
                    if (empty($label2 = Field::displayField($field->fields['id'], 'label2'))) {
                        $label2 = $field->fields['label2'];
                    }
                    echo "<br><br><br>" . Toolbox::stripTags($label2);
                }

                echo "</td>";

                echo "<td>";
                $lang = $_SESSION['glpilanguage'];

                $values['value'] = $value;
                $values['item'] = $field->fields['item'];

                //            if ($field->fields['type'] == "date_interval" || $field->fields['type'] == "datetime_interval") {
                //                if (isset($value['value2'])) {
                //                    $v['value'] = $value['value2'];
                //                }
                //
                //            }
                $params = Field::getAllParamsFromField($field);
                $values = array_merge($values, $params);

                switch ($field->fields['type']) {
                    case 'dropdown':
                        echo Dropdown::getFieldValue($values);
                        break;
                    case 'dropdown_object':
                        echo Dropdownobject::getFieldValue($values);
                        break;
                    case 'dropdown_ldap' :
                        echo Ldapdropdown::getFieldValue($values);
                        break;
                    case 'dropdown_meta':
                        echo Dropdownmeta::getFieldValue($values, $lang);
                        break;
                    case 'dropdown_multiple':
                        echo Dropdownmultiple::getFieldValue($values, $lang);
                        break;
                    case 'link':
                        echo Link::getFieldValue($values);
                        break;
                    case 'textarea':
                        echo Textarea::getFieldValue($values);
                        break;
                    case 'text':
                        echo Text::getFieldValue($values);
                        break;
                    case 'tel':
                        echo Tel::getFieldValue($values);
                        break;
                    case 'email':
                        echo Email::getFieldValue($values);
                        break;
                    case 'url':
                        echo Url::getFieldValue($values);
                        break;
                    case 'checkbox':
                        //                        $values['custom_values'] = $fieldmeta->fields['custom'];
                        //                        $values['id'] = $id;
                        echo Checkbox::getFieldValue($values, $lang);
                        break;
                    case 'radio':
                        //                        $values['custom_values'] = $fieldmeta->fields['custom'];
                        //                        $values['id'] = $id;
                        echo Radio::getFieldValue($values, $label, $lang);
                        break;
                    case 'date':
                        echo Date::getFieldValue($values);
                        break;
                    case 'time':
                        echo Time::getFieldValue($values);
                        break;
                    case 'datetime':
                        echo Datetime::getFieldValue($values);
                        break;
                    case 'date_interval':
                        echo Dateinterval::getFieldValue($values);
                        break;
                    case 'datetime_interval':
                        echo Datetimeinterval::getFieldValue($values);
                        break;
                    case 'number':
                        echo Number::getFieldValue($values);
                        break;
                    case 'range':
                        echo Range::getFieldValue($values);
                        break;
                    case 'yesno':
                        echo Yesno::getFieldValue($values);
                        break;
                    default:
                        echo htmlspecialchars((string) $value);
                        break;
                }
                echo "</td>";
            }
        }
    }

    public static function displayBasketSummary($fields)
    {
        //        if (Plugin::isPluginActive('orderfollowup')) {
        //
        //            if (isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
        //                $freeinputs = $_SESSION['plugin_orderfollowup']['freeinputs'];
        //                foreach ($freeinputs as $freeinput) {
        //                    $fields['freeinputs'][] = $freeinput;
        //                }
        //            }
        //        }

        $materials = $fields["field"] ?? [];
        $quantities = $fields["quantity"] ?? [];
        $content = "";

        $count = 0;
        $columns = 2;

        if (is_array($materials) && count($materials) > 0) {
            //            $meta = new Metademand();
            //            if ($meta->getFromDB($fields['metademands_id'])) {
            //                $title_color = "#000";
            //                if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
            //                    $title_color = $meta->fields['title_color'];
            //                }
            //
            //                $color = Wizard::hex2rgba($title_color, "0.03");
            //                $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 15px;'";
            //                echo "<div class='card-header d-flex justify-content-between align-items-center md-color' $style_background>";// alert alert-light
            //
            //                echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
            //                echo __('Demand details', 'metademands');
            //                echo "</h2>";
            //
            //                echo "</div>";
            //            }

            echo "<table class='tab_cadre_fixe'>";
            foreach ($materials as $idline => $fieldlines) {
                $count++;
                if ($count > $columns) {
                    echo "<tr class='tab_bg_1'>";
                }
                self::retrieveDatasByType($idline, $fieldlines);
                if ($count > $columns) {
                    echo "</tr>";
                    $count = 0;
                }
            }
            echo "</table>";
        }

        $meta = new Metademand();
        if ($meta->getFromDB($fields['metademands_id'])) {
            $title_color = "#000";
            if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
                $title_color = $meta->fields['title_color'];
            }

            //            $color = Wizard::hex2rgba($title_color, "0.03");
            //            $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 10px;padding: 20px;'";
            //            echo "<div class='card-header d-flex justify-content-between align-items-center md-color' $style_background>";// alert alert-light
            //
            //            echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
            //            echo __('Basket summary', 'metademands');
            //            echo "</h2>";
            //
            //            echo "</div>";

            echo "<div class='row'>";
            echo "<div class=\"card mx-1 my-2 flex-grow-1\">";
            echo "<div class='col-12 align-self-center'>";

            echo "<section class='card-body' style='width: 100%;'>";
            echo "<h2 class='card-title mb-2 text-break' style='color: $title_color;'>";
            echo __('Basket summary', 'metademands');
            echo "</h2>";
            echo "</section>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        if (is_array($materials) && count($materials) > 0) {
            $content .= "<table class='tab_cadre_fixe'>";
            $content .= "<tr class='tab_bg_1'>";
            $content .= "<th style='border: 1px solid black;'>" . __('Reference', 'metademands') . "</th>";
            $content .= "<th style='border: 1px solid black;'>" . __('Designation', 'metademands') . "</th>";
            $content .= "<th style='border: 1px solid black;'>" . __('Description') . "</th>";

            $withprice    = false;
            $withquantity = false;
            if (Plugin::isPluginActive('orderfollowup')) {
                foreach ($materials as $id => $material) {
                    $field = new Field();
                    $field->getFromDB($id);

                    $params = Field::getAllParamsFromField($field);
                    $fields = array_merge($fields, $params);

                    if (isset($fields['custom_values'])) {
                        $custom_values = FieldParameter::_unserialize($fields['custom_values']);
                        if (isset($custom_values[0]) && $custom_values[0] == 1) {
                            $withquantity = true;
                        }

                        if (isset($custom_values[1]) && $custom_values[1] == 1) {
                            $withprice = true;
                        }
                    }
                }
            }

            if (Plugin::isPluginActive('ordermaterial')) {
                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                )) {
                    $content .= "<th style='border: 1px solid black;'>" . __(
                        'Estimated unit price',
                        'ordermaterial',
                    ) . "</th>";
                }
            }
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new OrderMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                )) {
                    $content .= "<th style='border: 1px solid black;'>" . __('Unit', 'orderfollowup') . "</th>";
                    if ($withprice) {
                        $content .= "<th style='border: 1px solid black;'>" . __(
                            'Unit price (HT)',
                            'orderfollowup',
                        ) . "</th>";
                    }
                }
            }

            $content .= "<th style='border: 1px solid black;'>" . __('Quantity', 'metademands') . "</th>";

            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new OrderMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                )) {
                    if ($withprice) {
                        $content .= "<th style='border: 1px solid black;text-align: right;'>" . __(
                            'Total (HT)',
                            'orderfollowup',
                        ) . "</th>";
                    } else {
                        $content .= "<th style='border: 1px solid black;text-align: right;'>" . __(
                            'Total',
                            'metademands',
                        ) . "</th>";
                    }
                } else {
                    $content .= "<th style='border: 1px solid black;text-align: right;'>" . __(
                        'Total',
                        'metademands',
                    ) . "</th>";
                }
            } else {
                $content .= "<th style='border: 1px solid black;text-align: right;'>" . __(
                    'Total',
                    'metademands',
                ) . "</th>";
            }

            $content .= "</tr>";

            $grandtotal = 0;
            $withprice = false;
            $withquantity = false;

            foreach ($materials as $id => $material) {
                $field = new Field();
                $field->getFromDB($id);

                $params = Field::getAllParamsFromField($field);
                $fields = array_merge($fields, $params);

                if (isset($field->fields['custom_values'])) {
                    $custom_values = FieldParameter::_unserialize($fields['custom_values']);
                    if (isset($custom_values[0]) && $custom_values[0] == 1) {
                        $withquantity = true;
                    }

                    if (isset($custom_values[1]) && $custom_values[1] == 1) {
                        $withprice = true;
                    }
                }
                if (is_array($material)) {
                    foreach ($material as $mat_id) {
                        $basket_obj = new Basketobject();
                        if ($basket_obj->getFromDB($mat_id)) {
                            if ($withquantity == false) {
                                $quantity = 1;
                            } else {
                                $quantity = 0;
                            }

                            if (isset($quantities[$id][$mat_id])) {
                                $quantity = $quantities[$id][$mat_id];
                            }

                            if ($withquantity == true && $quantity == 0) {
                                continue;
                            }

                            $content .= "<tr class='tab_bg_1'>";

                            $content .= "<td style='border: 1px solid black;'>";
                            $content .= $basket_obj->fields['reference'];
                            $content .= "</td>";

                            $content .= "<td style='border: 1px solid black;'>";
                            $content .= $basket_obj->getName();
                            $content .= "</td>";

                            $content .= "<td style='border: 1px solid black;'>";
                            $content .= $basket_obj->fields['description'];
                            $content .= "</td>";

                            if (Plugin::isPluginActive('ordermaterial')) {
                                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(
                                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                                )) {
                                    $ordermaterial = new PluginOrdermaterialMaterial();
                                    if ($ordermaterial->getFromDBByCrit(
                                        ['plugin_metademands_basketobjects_id' => $mat_id],
                                    )) {
                                        if ($ordermaterial->fields['is_specific'] == 1) {
                                            $content .= "<td style='border: 1px solid black;'>";
                                            $content .= __('On quotation', 'ordermaterial');
                                            $content .= "</td>";
                                        } else {
                                            $content .= "<td style='border: 1px solid black;'>";
                                            if ($withprice) {
                                                $content .= Html::formatNumber(
                                                    $ordermaterial->fields['estimated_price'],
                                                    false,
                                                    2,
                                                ) . " €";
                                            }
                                            $content .= "</td>";
                                        }
                                    } else {
                                        $content .= "<td style='border: 1px solid black;'>";
                                        $content .= "</td>";
                                    }
                                }
                            }

                            if (Plugin::isPluginActive('orderfollowup')) {
                                $ordermaterialmeta = new OrderMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(
                                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                                )) {
                                    $ordermaterial = new Material();
                                    if ($ordermaterial->getFromDBByCrit(
                                        ['plugin_metademands_basketobjects_id' => $mat_id],
                                    )) {
                                        $content .= "<td style='border: 1px solid black;'>";
                                        $content .= $ordermaterial->fields['unit'];
                                        $content .= "</td>";

                                        if ($withprice) {
                                            $content .= "<td style='border: 1px solid black;'>";
                                            $content .= Html::formatNumber(
                                                $ordermaterial->fields['unit_price'],
                                                false,
                                                2,
                                            ) . " €";
                                            $content .= "</td>";
                                        }
                                    } else {
                                        $content .= "<td style='border: 1px solid black;'>";
                                        $content .= "</td>";
                                    }
                                }
                            }

                            $content .= "<td style='border: 1px solid black;'>";
                            $content .= $quantity;
                            $content .= "</td>";

                            $content .= "<td style='border: 1px solid black;text-align: right;'>";

                            $totalrow = $quantity;
                            if (Plugin::isPluginActive('ordermaterial')) {
                                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(
                                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                                )) {
                                    $ordermaterial = new PluginOrdermaterialMaterial();
                                    if ($ordermaterial->getFromDBByCrit(
                                        ['plugin_metademands_basketobjects_id' => $mat_id],
                                    ) && $withprice) {
                                        $totalrow = $quantity * $ordermaterial->fields['estimated_price'];
                                    }
                                }
                            }
                            if (Plugin::isPluginActive('orderfollowup')) {
                                $ordermaterialmeta = new OrderMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(
                                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                                )) {
                                    $ordermaterial = new Material();
                                    if ($ordermaterial->getFromDBByCrit(
                                        ['plugin_metademands_basketobjects_id' => $mat_id],
                                    ) && $withprice) {
                                        $totalrow = $quantity * $ordermaterial->fields['unit_price'];
                                    }
                                }
                            }

                            if (Plugin::isPluginActive('ordermaterial') && $withprice) {
                                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(
                                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                                )) {
                                    $ordermaterial = new PluginOrdermaterialMaterial();
                                    if ($ordermaterial->getFromDBByCrit(
                                        ['plugin_metademands_basketobjects_id' => $mat_id],
                                    )) {
                                        if ($ordermaterial->fields['is_specific'] == 1) {
                                            $content .= __('On quotation', 'ordermaterial');
                                        } else {
                                            $content .= Html::formatNumber($totalrow, false, 2);
                                            $content .= " €";
                                        }
                                    }
                                }
                            } elseif (Plugin::isPluginActive('orderfollowup') && $withprice) {
                                $ordermaterialmeta = new OrderMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(
                                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                                )) {
                                    $ordermaterial = new Material();
                                    if ($ordermaterial->getFromDBByCrit(
                                        ['plugin_metademands_basketobjects_id' => $mat_id],
                                    )) {
                                        $content .= Html::formatNumber($totalrow, false, 2);
                                        $content .= " €";
                                    }
                                }
                            } else {
                                $content .= Html::formatNumber($totalrow, false, 0);
                            }

                            $grandtotal += $totalrow;
                            $content .= "</td>";

                            $content .= "</tr>";
                        }
                    }
                }
            }
            if (Plugin::isPluginActive('ordermaterial')) {
                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                ) && $withprice) {
                    $content .= "<tr class='tab_bg_1'>";
                    $content .= "<th style='border: 1px solid black;' colspan='3'>" . __(
                        'Grand total',
                        'ordermaterial',
                    ) . "</th>";
                    $content .= "<th style='border: 1px solid black;text-align: right;'>" . Html::formatNumber(
                        $grandtotal,
                        false,
                        2,
                    ) . " €</th>";
                    $content .= "</tr>";
                    $content .= "<tr class='tab_bg_1'>";
                    $content .= "<td colspan='4' style='border: 1px solid black;'></td>";
                    $content .= "<td colspan='4'>" . __(
                        '* The prices are estimates and do not act as an estimate',
                        'ordermaterial',
                    ) . "</td>";
                    $content .= "</tr>";
                }
            }
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new OrderMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $fields['metademands_id']],
                ) && $withprice) {
                    $content .= "<tr class='tab_bg_1'>";
                    $content .= "<th style='border: 1px solid black;' colspan='6'>" . __(
                        'Grand total (HT)',
                        'orderfollowup',
                    ) . "</th>";
                    $content .= "<th style='border: 1px solid black;text-align: right;'>" . Html::formatNumber(
                        $grandtotal,
                        false,
                        2,
                    ) . " €</th>";
                    $content .= "</tr>";
                }
            }
            $content .= "</table>";

            //            if ($grandtotal > 0) {
            $content .= "<br><div>";
            $config = Config::getInstance();
            if ($config['use_draft']) {
                //button create draft
                $content .= Draft::createDraftInput(Draft::BASKET_MODE);
            }
            $content .= "<span style='float:right'>";
            //            $title = _sx('button', 'Send order', 'metademands');
            $title = _sx('button', 'Save & Post', 'metademands');
            $current_ticket = $fields["current_ticket_id"] = $fields["tickets_id"];
            $content .= Html::submit($title, [
                'name' => 'send_order',
                'icon' => 'ti ti-shopping-bag',
                'form' => '',
                'id' => 'submitOrder',
                'class' => 'btn btn-success right',
            ]);
            $content .= "</span></div>";

            $paramUrl = "";
            $meta_validated = false;
            if ($current_ticket > 0 && !$meta_validated) {
                $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
            }
            $post = json_encode($fields);
            $meta_id = $fields['metademands_id'];
            $metademands = new Metademand();
            if ($metademands->getFromDB($meta_id)) {
                $name = addslashes($metademands->fields['name']) . "_" . $_SESSION['glpi_currenttime'] . "_" . $_SESSION['glpiID'];
                $content .= "<script>
                          $('#submitOrder').click(function() {
                             var meta_id = $meta_id;
//                             arrayDatas = $post;
                             arrayDatas = $('#wizard_form').serializeArray();
                             arrayDatas.push({name: 'save_form', value: true});
                             arrayDatas.push({name: 'step', value: 2});
                             arrayDatas.push({name: 'form_name', value: '$name'});
                             $.ajax({
                                   url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
                                   type: 'POST',
                                   dataType: 'html',
                                   data: arrayDatas,
                                   success: function (response) {
                                      if(response != 1){
                                          $.ajax({
                                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                                                type: 'POST',
                                                data: arrayDatas,
                                                success: function (response) {
                                                   if(response != 1){
                                                        window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=create_metademands';
                                                   } else {
                                                        location.reload();
                                                   }
                                                },
                                                error: function (xhr, status, error) {
                                                   console.log(xhr);
                                                   console.log(status);
                                                   console.log(error);
                                                }
                                             });
                                      } else {
                                           location.reload();
                                       }
                                   },
                                   error: function (xhr, status, error) {
                                      console.log(xhr);
                                      console.log(status);
                                      console.log(error);
                                   }
                                });
                           });
                           $('#prevBtn').hide();
                           $('.step_wizard').hide();

                        </script>";
                //            }
            }
        } else {
            $content .= "<table>";
            $content .= "<tr class='tab_bg_1'>";
            $content .= "<th colspan='4'>" . __('No items on basket', 'metademands') . "</th>";
            $content .= "</tr>";
            $content .= "</table>";
        }
        return $content;
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
            $withquantity = false;
            $custom_values = isset($data['custom_values']) ? FieldParameter::_unserialize(
                $data['custom_values'],
            ) : [];
            if (isset($custom_values[0]) && $custom_values[0] == 1) {
                $withquantity = true;
            }

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

            if ($withquantity == false) {
                $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            } else {
                $name = "quantity[" . $data["id"] . "]";

                $onchange .= "$('[name^=\"$name\"]').change(function() {";
            }
            $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $onchange . '});',
            );
        }
    }

    public static function getFieldValue(
        $field
    ) {
        return $field['value'];
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
        //        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;

        $meta_id = $field['plugin_metademands_metademands_id'];
        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'])) {
            $quantities = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'];
        }
        $style_td = "style = \'border: 1px solid black; \'";

        $materials = $field["value"];

        if (is_object($materials)) {
            $materials = json_decode(json_encode($materials), true);
        }

        $total = 0;
        $nb = 4;
        if (Plugin::isPluginActive('ordermaterial')) {
            $ordermaterialmeta = new PluginOrdermaterialMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                $nb = 6;
            }
        }
        if (Plugin::isPluginActive('orderfollowup')) {
            $ordermaterialmeta = new OrderMetademand();
            if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                $nb = 6;
            }
            $withprice = false;
            foreach ($materials as $id => $mat_id) {
                $fieldmeta = new FieldParameter();
                $fieldmeta->getFromDBByCrit(["plugin_metademands_fields_id" => $field['id']]);
                $custom_values = FieldParameter::_unserialize($fieldmeta->fields['custom']);

                if ($custom_values[1] == 1) {
                    $withprice = true;
                    $nb = 7;
                } else {
                    $nb = 5;
                }
            }
        }

        if (is_array($materials) && count($materials) > 0) {
            if ($formatAsTable) {
                //                $result .= "<table $style_td>";
                $result[$field['rank']]['content'] .= "<tr>";

                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Reference', 'metademands') . "</th>";

                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Designation', 'metademands') . "</th>";

                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Description') . "</th>";

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __(
                            'Order type',
                            'ordermaterial',
                        ) . "</th>";
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __('Unit', 'orderfollowup') . "</th>";
                    }
                }

                $result[$field['rank']]['content'] .= "<th $style_td>" . __('Quantity', 'metademands') . "</th>";

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __(
                            'Estimated unit price',
                            'ordermaterial',
                        ) . "</th>";
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(
                        ['plugin_metademands_metademands_id' => $meta_id],
                    ) && $withprice) {
                        $result[$field['rank']]['content'] .= "<th $style_td>" . __(
                            'Unit price (HT)',
                            'orderfollowup',
                        ) . "</th>";
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        if ($withprice) {
                            $result[$field['rank']]['content'] .= "<th $style_td>" . __(
                                'Total (HT)',
                                'orderfollowup',
                            ) . "</th>";
                        }
                    }
                }

                $result[$field['rank']]['content'] .= "</tr>";
            }

            foreach ($materials as $mat_id => $q) {
                $totalrow = 0;

                $material = new Basketobject();
                $material->getFromDB($mat_id);

                $field['value'] = $material->getName();

                $fieldmeta = new FieldParameter();
                $fieldmeta->getFromDBByCrit(["plugin_metademands_fields_id" => $field['id']]);
                $withquantity = false;
                $custom_values = FieldParameter::_unserialize($fieldmeta->fields['custom']);
                if ($custom_values[0] == 1) {
                    $withquantity = true;
                }

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

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<tr>";
                    $result[$field['rank']]['content'] .= "<td $style_td>";
                }
                $result[$field['rank']]['content'] .= htmlspecialchars((string) $material->fields['reference'], ENT_QUOTES, 'UTF-8');

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td $style_td>";
                }
                $result[$field['rank']]['content'] .= htmlspecialchars((string) $field['value'], ENT_QUOTES, 'UTF-8');
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td $style_td>";
                }
                $result[$field['rank']]['content'] .= htmlspecialchars((string) $material->fields['description'], ENT_QUOTES, 'UTF-8');

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($ordermaterial->fields['is_specific']) {
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "<td $style_td>";
                                }
                                $result[$field['rank']]['content'] .= __('On quotation', 'ordermaterial');
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "</td $style_td>";
                                }
                            } else {
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "<td $style_td>";
                                }
                                $result[$field['rank']]['content'] .= __('On catalog', 'ordermaterial');
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "</td>";
                                }
                            }
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new Material();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "<td $style_td>";
                            }
                            $result[$field['rank']]['content'] .= $ordermaterial->fields['unit'];

                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td>";
                            }
                        }
                    }
                }

                if ($quantity > 0) {
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_td>";
                    }
                    $result[$field['rank']]['content'] .= $quantity;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }
                } else {
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_td>";
                    }
                    $result[$field['rank']]['content'] .= "1";
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td>";
                    }
                }

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "<td $style_td>";
                            }
                            $result[$field['rank']]['content'] .= Html::formatNumber(
                                $ordermaterial->fields['estimated_price'],
                                false,
                                2,
                            ) . " €";

                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td>";
                            }
                        }
                    }
                }

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new Material();
                        if ($ordermaterial->getFromDBByCrit(
                            ['plugin_metademands_basketobjects_id' => $mat_id],
                        ) && $withprice) {
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "<td $style_td>";
                            }
                            $result[$field['rank']]['content'] .= Html::formatNumber(
                                $ordermaterial->fields['unit_price'],
                                false,
                                2,
                            ) . " €";

                            //                            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                            //                                $result[$field['rank']]['content']  .= " €";
                            //                            }

                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td>";
                            }
                        }
                    }
                }

                $totalrow = $quantity;

                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new PluginOrdermaterialMaterial();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            $totalrow = $quantity * $ordermaterial->fields['estimated_price'];
                        }
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        $ordermaterial = new Material();
                        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_basketobjects_id' => $mat_id])) {
                            if ($ordermaterial->fields['unit_price'] == 0) {
                                $totalrow = $quantity;
                            } else {
                                $totalrow = $quantity * $ordermaterial->fields['unit_price'];
                            }
                            if ($withprice) {
                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "<td $style_td>";
                                }
                                $result[$field['rank']]['content'] .= Html::formatNumber($totalrow, false, 2) . " €";

                                if ($formatAsTable) {
                                    $result[$field['rank']]['content'] .= "</td>";
                                }
                            }
                        }
                    }
                }

                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</tr>";
                }

                $total += $totalrow;
            }

            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<tr>";
                $colspan = $nb - 1;

                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new OrderMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])) {
                        if ($withprice) {
                            $result[$field['rank']]['content'] .= "<th $style_td colspan='$colspan'>" . __(
                                'Grand total (HT)',
                                'orderfollowup',
                            ) . "</th>";
                        } else {
                            $result[$field['rank']]['content'] .= "<th $style_td colspan='$colspan'>" . __(
                                'Total',
                                'metademands',
                            ) . "</th>";
                        }
                    }
                } else {
                    $result[$field['rank']]['content'] .= "<th $style_td colspan='$colspan'>" . __(
                        'Total',
                        'metademands',
                    ) . "</th>";
                }
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_td>";
            }
            if (isset($custom_values[1]) && $custom_values[1] == 1) {
                $total_final = $total;
            } else {
                $total_final = Html::formatNumber($total, false, 2);
            }
            if (Plugin::isPluginActive('ordermaterial')) {
                $ordermaterialmeta = new PluginOrdermaterialMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])
                    && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $total_final .= " €";
                }
            }
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new OrderMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id])
                    && isset($custom_values[1]) && $custom_values[1] == 1) {
                    $total_final .= " €";
                }
            }
            $result[$field['rank']]['content'] .= $total_final;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
                $result[$field['rank']]['content'] .= "</tr>";
            }
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
                $with_price = false;
                $fieldparam = new FieldParameter();
                if ($fieldparam->getFromDBByCrit(['plugin_metademands_fields_id' => $elt['id']])) {
                    $custom_values = FieldParameter::_unserialize($fieldparam->fields['custom']);
                    $with_price = isset($custom_values[1]) && $custom_values[1] == 1;
                }

                foreach ($orders as $id => $item) {
                    $material = new Basketobject();
                    $material->getFromDB($item['plugin_metademands_basketobjects_id']);

                    // MetademandPdf extends \TCPDF, which is UTF-8 native. Feeding it
                    // ISO-8859-1 (what Toolbox::decodeFromUtf8() produces) drops every
                    // accented character — that is why the "Référence"/"Désignation"/
                    // "Quantité"/"Unité" column titles and the "Unité" cell vanished from
                    // the basket table. Keep the strings UTF-8: no decode on the PDF path.
                    $values[$id][__('Reference', 'metademands')] = $material->fields['reference'];
                    $values[$id][__('Designation', 'metademands')] = $material->getName();
                    $values[$id][__('Description')] = $material->fields['description'];
                    $values[$id][__('Quantity', 'metademands')] = $item['quantity'];

                    $ordermaterial = new Material();
                    if ($ordermaterial->getFromDBByCrit(
                        ['plugin_metademands_basketobjects_id' => $item['plugin_metademands_basketobjects_id']],
                    )) {
                        $values[$id][__('Unit', 'orderfollowup')] = $ordermaterial->fields['unit'];

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
