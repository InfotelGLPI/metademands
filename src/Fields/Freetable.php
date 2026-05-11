<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

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

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Glpi\RichText\RichText;
use Html;
use Plugin;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Orderfollowup\Config;
use Toolbox;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Freetablefield as MetaFreetablefield;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Freetable Class
 *
 **/
class Freetable extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

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
        return __('Free table', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        $field = "";

        $plugin_metademands_metademands_id = $data['plugin_metademands_metademands_id'];
        $meta = new Metademand();
        $meta->getFromDB($plugin_metademands_metademands_id);
        $background_color = "";
        if (isset($meta->fields['background_color'])
            && $meta->fields['background_color'] != "") {
            $safe_bg = htmlspecialchars($meta->fields['background_color'], ENT_QUOTES);
            $background_color = "background-color:" . $safe_bg . ";";
        }
        $plugin_metademands_fields_id = $data['id'];

        if (!isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields'][$data['id']])) {
            unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables']);
        }
        $nb = 0;
        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables'][$data['id']])) {
            $nb = count($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['freetables'][$data['id']]);
        }
        $values = [];

        $idline = 0;

        if (isset($data['value'])
            && is_array($data['value'])) {
            $values = $data['value'];
        }
        $nb_values = count($values);
        $colspan = '4';

        $style_th = "style='text-align: left;$background_color'";
        $field .= Html::hidden('is_freetable_mandatory[' . $data['id'] . ']', ['value' => $data['is_mandatory']]);
        $colspanfields = 0;
        $addfields = [];
        $commentfields = [];
        $size = 30;
        $field_custom = new MetaFreetablefield();
        $is_mandatory = [];
        $types = [];
        $dropdown_values = [];
        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
            if (count($customs) > 0) {
                foreach ($customs as $custom) {
                    $translated_col = Field::displayField($data['id'], 'freetablecol' . $custom['rank']);
                    $addfields[$custom['internal_name']] = $translated_col !== '' ? $translated_col : $custom['name'];
                    $commentfields[$custom['internal_name']] = $custom['comment'];
                    if ($custom['is_mandatory'] == 1) {
                        $is_mandatory[] = $custom['internal_name'];
                    }
                    $types[$custom['internal_name']] = $custom['type'];

                    $dropdown_values_array = [];
                    $dropdown_values_array[0] = \Dropdown::EMPTY_VALUE;
                    if (!empty($custom['dropdown_values'])) {
                        $explode = explode(",", $custom['dropdown_values']);
                        foreach ($explode as $val) {
                            $dropdown_values_array[] = Toolbox::cleanNewLines($val);
                        }
                    }

                    if ($custom['type'] == MetaFreetablefield::TYPE_SELECT) {
                        $dropdown_values[$custom['internal_name']] = $dropdown_values_array;
                    }
                }
                $colspanfields = count($customs);
                if (count($customs) > 3) {
                    $size = 20;
                }
                if (count($customs) > 4) {
                    $size = 10;
                }
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $addfields['total'] = __('Total (TTC)', 'orderfollowup');
            $commentfields['total'] = '';
            $types['total'] = MetaFreetablefield::TYPE_READONLY;
            $size = 17;
        }

        $rand = $data['id'];

        $field .= "<table class='tab_cadre' width='100%' id ='freetable_table$rand' style='overflow: auto;width:100%;$background_color'>";//display: block;
        $field .= "<tr class='tab_bg_1'>";
        foreach ($addfields as $k => $addfield) {
            $field .= "<th $style_th>";
            $field .= $addfield;
            if (in_array($k, $is_mandatory)) {
                $field .= "<span style='color : red'> *</span>";
            }
            if (isset($commentfields[$addfield]) && !empty($commentfields[$addfield])) {
                $field .= Html::showToolTip(
                    $commentfields[$addfield],
                    ['display' => false, 'awesome-class' => 'ti ti-info-circle']
                );
            }
            $field .= "</th>";
        }

        $encoded_fields = json_encode($addfields);
        $mandatory_encoded_fields = json_encode($is_mandatory);
        $empty_value = \Dropdown::EMPTY_VALUE;
        $types_encoded_fields = json_encode($types);
        $dropdown_values_encoded_fields = json_encode($dropdown_values);
        $root = PLUGIN_METADEMANDS_WEBDIR;

        $orderfollowup_is_active = 0;
        if (Plugin::isPluginActive('orderfollowup')) {
            $orderfollowup_is_active = 1;
        }

        $existLine = json_encode(__('You can\'t create a new line when there is an existing one', 'metademands'));

        $lastid = 0;
        if (is_array($values) && count($values) > 0) {
            ksort($values);
            $lastvalues = end($values);
            $lastid = $lastvalues['id'];
        }
        $texttype = MetaFreetablefield::TYPE_TEXT;
        $selecttype = MetaFreetablefield::TYPE_SELECT;
        $numbertype = MetaFreetablefield::TYPE_NUMBER;
        $readonlytype = MetaFreetablefield::TYPE_READONLY;
        $datetype = MetaFreetablefield::TYPE_DATE;
        $timetype = MetaFreetablefield::TYPE_TIME;

        $field .= "<script>
                    $(document).ready(function (){
                        window.metademandfreelinesparams$rand = {};
                        metademandfreelinesparams$rand.existLine = $existLine;
                        metademandfreelinesparams$rand.rand = '$rand';
                        metademandfreelinesparams$rand.root = '$root';
                        metademandfreelinesparams$rand.encoded_fields = $encoded_fields;
                        metademandfreelinesparams$rand.mandatory_encoded_fields = $mandatory_encoded_fields;
                        metademandfreelinesparams$rand.types_encoded_fields = $types_encoded_fields;
                        metademandfreelinesparams$rand.dropdown_values_encoded_fields = $dropdown_values_encoded_fields;
                        metademandfreelinesparams$rand.orderfollowupisactive = $orderfollowup_is_active;
                        metademandfreelinesparams$rand.size = $size;
                        metademandfreelinesparams$rand.empty_value = '$empty_value';
                        metademandfreelinesparams$rand.plugin_metademands_metademands_id = $plugin_metademands_metademands_id;
                        metademandfreelinesparams$rand.lastid = $lastid;
                        metademandfreelinesparams$rand.text = $texttype;
                        metademandfreelinesparams$rand.select = $selecttype;
                        metademandfreelinesparams$rand.number = $numbertype;
                        metademandfreelinesparams$rand.readonly = $readonlytype;
                        metademandfreelinesparams$rand.date = $datetype;
                        metademandfreelinesparams$rand.time = $timetype;
                        if ($nb_values === 0) {
                            addLine(window.metademandfreelinesparams$rand);
                        }
                    });

               </script>";

        $field .= "<th style='text-align: right;$background_color' colspan='4' onclick='addLine(window.metademandfreelinesparams$rand)'><i class='ti ti-plus btn btn-info'></i></th>";
        $field .= "</tr>";

        if (is_array($values) && count($values) > 0) {
            foreach ($values as $value) {

                $idline = $value['id'];
                $l = [
                    'id' => $idline,
                ];

                foreach ($addfields as $k => $addfield) {
                    if (isset($value[$k])) {
                        $l[$k] = $value[$k];
                    }
                }

                $quantity   = 0;
                $unit_price = 0;
                if (Plugin::isPluginActive('orderfollowup')) {
                    $quantity   = floatval($l['quantity'] ?? 0);
                    $unit_price = floatval($l['unit_price'] ?? 0);
                }

                $field .= "<tr name=\"data\" id=\"line_" . $rand . "_$idline\" disabled>";

                foreach ($addfields as $k => $addfield) {
                    if (isset($l[$k])) {
                        $escaped_val = htmlspecialchars((string) $l[$k], ENT_QUOTES);
                        $escaped_k   = htmlspecialchars($k, ENT_QUOTES);
                        $escaped_id  = htmlspecialchars($k . '_' . $idline, ENT_QUOTES);
                        if ($types[$k] == MetaFreetablefield::TYPE_TEXT) {
                            $field .= "<td><input id=\"$escaped_id\" name=\"$escaped_k\" type=\"text\" value=\"$escaped_val\" size=\"$size\" disabled></td>";
                        } elseif ($types[$k] == MetaFreetablefield::TYPE_SELECT) {
                            $field .= "<td><select id=\"$escaped_id\" name=\"$escaped_k\">";
                            foreach ($dropdown_values[$k] as $key => $dropdown_value) {
                                $selected = ($key == $l[$k]) ? "selected" : "";
                                $esc_dv   = htmlspecialchars((string) $dropdown_value, ENT_QUOTES);
                                $field .= "<option $selected value=\"$esc_dv\">$esc_dv</option>";
                            }
                            $field .= "</select></td>";
                        } elseif ($types[$k] == MetaFreetablefield::TYPE_NUMBER) {
                            $field .= "<td><input add=-1 id=\"$escaped_id\" name=\"$escaped_k\" type=\"number\" min=\"0\" value=\"$escaped_val\" style=\"width: 7em;\" disabled></td>";
                        } elseif ($types[$k] == MetaFreetablefield::TYPE_DATE) {
                            $field .= "<td><input add=-1 id=\"$escaped_id\" name=\"$escaped_k\" type=\"date\" value=\"$escaped_val\" disabled></td>";
                        } elseif ($types[$k] == MetaFreetablefield::TYPE_TIME) {
                            $field .= "<td><input add=-1 id=\"$escaped_id\" name=\"$escaped_k\" type=\"time\" value=\"$escaped_val\" disabled></td>";
                        }
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $linetotal = number_format($quantity * $unit_price, 2, '.', ' ');
                    $field .= "<td id=\"linetotal_$idline\">$linetotal €</td>";
                }

                $field .= "<td><button onclick =\"editLine($idline, $rand, window.metademandfreelinesparams$rand)\"class =\"btn btn-info\" type = \"button\" name =\"edit_item\"><i class =\"ti ti-pencil\"></i></button></td>";
                $field .= "<td><button onclick =\"removeLine($idline, $rand, window.metademandfreelinesparams$rand)\"class =\"btn btn-danger\" type = \"button\" name =\"delete_item\"><i class =\"ti ti-trash\"></i></button></td>";
                $field .= "</tr>";

            }
        }

        $field .= "</table>";

        if (Plugin::isPluginActive('orderfollowup')) {
            $stylereadonly = "style= \'white-space: nowrap;text-align: right;background-color: #ffffff;\'";
            $conf = new Config();
            $conf->getFromDB(1);
            $tva = $conf->fields['use_tva'] ?? "20";
            $tva_calc = $tva / 100;
            $grandtotal = __('Grand total (TTC)', 'orderfollowup');
            $grandtotalHT = __('Grand total (HT)', 'orderfollowup') . " " . __('(if VAT 20%)', 'orderfollowup');
            $field .= "<script>
                    function saveInput_{$rand}() {
                        var grandtotal = 0;
                        var grandtotalht = 0;
                        var tva = $tva_calc;
                        $('#freetable_table{$rand} tr[id^=line_{$rand}_]').each(function () {
                             grandtotal += $(this).find('[id^=unit_price_]').val() * $(this).find('[id^=quantity_]').val();
                        });
                        grandtotalht = grandtotal / (1 + tva);

                        $('#freetable_table{$rand} tr[id^=line_{$rand}_]').css('background-color', '#f7f7f7');
                        let tr_grandtotal = document.getElementById('grandtotal_{$rand}');
                        if (tr_grandtotal === null) {
                             $('#freetable_table{$rand} tr[id^=line_{$rand}_]:last').after('<tr id=\"grandtotal_{$rand}\">' +
                         '<th colspan=\"6\" style= \'background-color: #ffffff;\' > $grandtotal </th><th $stylereadonly id=\"amount_grandtotal_{$rand}\" >' + grandtotal.toFixed(2) + ' €</th></tr>' +
                          '<tr id=\"grandtotalht_{$rand}\">' +
                         '<th colspan=\"6\" style= \'background-color: #ffffff;\' > $grandtotalHT </th><th $stylereadonly id=\"amount_grandtotalht_{$rand}\" >' + grandtotalht.toFixed(2) + ' €</th></tr>');
                        } else {
                           $('#amount_grandtotal_{$rand}').text(grandtotal.toFixed(2) + ' €');
                           $('#amount_grandtotalht_{$rand}').text(grandtotalht.toFixed(2) + ' €');
                        }
                        $('#nextBtn').show();
                    }
               </script>";

            $field .= "<table class='tab_cadre' width='100%' style='overflow: auto;width:100%;$background_color'>";
            $field .= "<tr class='tab_bg_1'>";
            $field .= "<td colspan='8' style ='text-align:center;'>";
            $field .= "<button onclick='saveInput_{$rand}()' type = 'button' id='add_freeinputs_{$rand}' class='btn btn-primary' style='display: none;'>";
            $field .= "<span>" . __('Validate the basket', 'orderfollowup') . "</span>";
            $field .= "</button>";
            $field .= "</td>";
            $field .= "</tr>";
            $field .= "</table>";
        }


        echo $field;
    }

    public static function showFreetableFields($params)
    {
        $custom_values = $params['custom_values'];

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $maxrank = 0;

        $nbfields = 0;
        $field_custom = new MetaFreetablefield();
        if ($customs = $field_custom->find(
            ["plugin_metademands_fields_id" => $params['plugin_metademands_fields_id']],
            "rank"
        )) {
            if (count($customs) > 0) {
                $nbfields = count($customs);
            }
        }

        if (is_array($custom_values) && !empty($custom_values)) {
            echo "<div id='drag'>";
            $target = MetaFreetablefield::getFormURL();
            echo "<form method='post' action=\"$target\">";
            echo "<table class='tab_cadre_fixe'>";
            foreach ($custom_values as $key => $value) {
                echo "<tr class='tab_bg_1'>";

                echo "<td class='rowhandler control center'>";
                echo __('Rank', 'metademands') . " " . $value['rank'] . " ";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden(
                        'fields_id',
                        ['value' => $params["plugin_metademands_fields_id"], 'id' => 'fields_id']
                    );
                    echo Html::hidden('type_object', ['value' => $params["type"], 'id' => 'type_object']);
                }
                echo "</td>";

                echo "<td class='rowhandler control left'>";
                echo "<span id='internal_name_values$key'>";
                echo " " . __('Internal name', 'metademands') . " ";
                echo Html::input('internal_name[' . $key . ']', ['value' => $value['internal_name'], 'size' => 20]);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control left'>";
                echo "<span id='type_values$key'>";
                echo " " . __('Type', 'metademands') . "<br>";
                \Dropdown::showFromArray(
                    'type[' . $key . ']',
                    MetaFreetablefield::getTypeFields(),
                    ['value' => $value['type'], 'size' => 20]
                );
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control left'>";
                echo "<span id='custom_values$key'>";
                echo " " . __('Display name', 'metademands') . " ";
                echo Html::input('name[' . $key . ']', ['value' => $value['name'], 'size' => 20]);
                echo "</span>";
                echo "</td>";

                if ($value['type'] == MetaFreetablefield::TYPE_TEXT) {
                    echo "<td class='rowhandler control left'>";
                    echo "<span id='comment_values$key'>";
                    echo __('Comment') . " ";
                    echo Html::input('comment[' . $key . ']', ['value' => $value['comment'], 'size' => 20]);
                    echo "</span>";
                    echo Html::hidden('dropdown_values[' . $key . ']', ['value' => []]);
                    echo "</td>";
                } elseif ($value['type'] == MetaFreetablefield::TYPE_SELECT) {
                    echo "<td class='rowhandler control left'>";
                    echo "<span id='dropdown_values$key'>";
                    echo " " . __('Dropdown values', 'metademands') . " ";
                    $label = __('One value by line, separated by comma', 'metademands');
                    Html::showToolTip(
                        RichText::getSafeHtml($label),
                        ['awesome-class' => 'ti ti-info-circle']
                    );
                    Html::textarea([
                        'name' => 'dropdown_values[' . $key . ']',
                        'value' => $value['dropdown_values'],
                        'rows' => 3,
                        'cols' => 5,
                    ]);
                    echo "</span>";
                    echo Html::hidden('comment[' . $key . ']', ['value' => ""]);
                    echo "</td>";
                } elseif ($value['type'] == MetaFreetablefield::TYPE_NUMBER
                    || $value['type'] == MetaFreetablefield::TYPE_DATE
                    || $value['type'] == MetaFreetablefield::TYPE_TIME) {
                    echo "<td class='rowhandler control left'>";
                    echo Html::hidden('comment[' . $key . ']', ['value' => ""]);
                    echo Html::hidden('dropdown_values[' . $key . ']', ['value' => []]);
                    echo "</td>";
                }

                echo "<td class='rowhandler control left'>";
                echo "<span id='is_mandatory_values$key'>";
                echo __('Mandatory', 'metademands') . "<br>";
                \Dropdown::showYesNo('is_mandatory[' . $key . ']', $value['is_mandatory']);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<i class=\"ti ti-grip-horizontal grip-rule\"></i>";
                echo "</div>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo Html::hidden('id[' . $key . ']', ['value' => $key]);
                echo Html::submit("", [
                    'name' => 'update',
                    'class' => 'btn btn-primary',
                    'icon' => 'ti ti-device-floppy',
                ]);
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                Html::showSimpleForm(
                    $target,
                    'delete',
                    _x('button', 'Delete permanently'),
                    [
                        'freetablefield_id' => $key,
                        'rank' => $value['rank'],
                        'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                    ],
                    'ti-circle-x',
                    "class='btn btn-primary'"
                );
                echo "</td>";

                echo "</tr>";

                $maxrank = $value['rank'];
            }
            echo Html::hidden('plugin_metademands_fields_id', ['value' => $params['plugin_metademands_fields_id']]);
            echo "</table>";
            Html::closeForm();
            echo "</div>";
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_freetableredipsInit()});');

            if ($nbfields < 6) {
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='4' align='left' id='show_custom_fields'>";
                MetaFreetablefield::initCustomValue(
                    $maxrank,
                    $params["plugin_metademands_fields_id"]
                );
                echo "</td>";
                echo "</tr>";
            }
        } else {
            if ($nbfields < 6) {
                echo "<tr class='tab_bg_1'>";
                echo "<td align='right'  id='show_custom_fields'>";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
                }
                MetaFreetablefield::initCustomValue(-1, $params["plugin_metademands_fields_id"]);
                echo "</td>";
                echo "</tr>";
            }
        }
        echo "</td>";
        echo "</tr>";
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

        if ($value['is_mandatory'] && empty($fields['value'])) {
            $msg     = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        return $field['value'];
    }

    public static function displayFieldPDF($elt, $fields, $label)
    {
        $values = [];

        $values_elt = $fields[$elt['id']] ?? [];

        if (is_array($values_elt) && count($values_elt) > 0) {
            foreach ($values_elt as $k => $value_elt) {
                foreach ($value_elt as $internal_name => $value) {
                    $field_custom = new MetaFreetablefield();
                    if ($customs = $field_custom->find([
                        "internal_name" => $internal_name,
                        "plugin_metademands_fields_id" => $elt['id'],
                    ])) {
                        if (count($customs) > 0) {
                            foreach ($customs as $id => $custom) {
                                $translated_col = Field::displayField($elt['id'], 'freetablecol' . $custom['rank']);
                                $col_label = $translated_col !== '' ? $translated_col : $custom['name'];
                                $values[$elt['id']][$k][Toolbox::decodeFromUtf8($col_label)] = $value;
                            }
                        }
                        //TODO
                        //                        if (Plugin::isPluginActive('orderfollowup')) {
                        //                            $total = $item['unitprice'] * $item['quantity'];
                        //                            $values[$id][Toolbox::decodeFromUtf8(
                        //                                __('Total (TTC)', 'orderfollowup')
                        //                            )] = Html::formatNumber($total, false, 2);
                        //                        }
                    }
                }
            }
        }

        return $values;
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
        //        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'])) {
        //            $quantities = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'];
        //        }
        $style_td = "style = \'border: 1px solid #CCC; \'";

        $materials = $field["value"];

        //        if (is_object($materials)) {
        //            $materials = json_decode(json_encode($materials), true);
        //        }
        $content = "";
        $result[$field['rank']]['display'] = true;
        //        $total = 0;
        $addfields = [];
        $dropdown_values = [];
        $types = [];
        $colspan_title = $is_order ? 12 : 2;
        $field_custom = new MetaFreetablefield();
        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $field['id']], "rank")) {
            if (count($customs) > 0) {
                foreach ($customs as $custom) {
                    $translated_col = Field::displayField($field['id'], 'freetablecol' . $custom['rank'], $lang);
                    $addfields[$custom['internal_name']] = $translated_col !== '' ? $translated_col : $custom['name'];
                    $types[$custom['internal_name']] = $custom['type'];
                    if ($custom['type'] == MetaFreetablefield::TYPE_SELECT) {
                        $dropdown_values[$custom['internal_name']] = explode(",", $custom['dropdown_values']);
                    }
                }
            }
        }
        $nb = is_array($customs) ? count($customs) : 0;

        $colspan = 1;
        if ($nb == 1) {
            $colspan = 12;
        }
        if ($nb == 2) {
            $colspan = 6;
        }
        if ($nb == 3) {
            $colspan = 4;
        }
        if ($nb == 4) {
            $colspan = 3;
        }
        if ($nb == 5) {
            $colspan = 2;
        }
        if ($nb == 6) {
            $colspan = 2;
        }
        if (Plugin::isPluginActive('orderfollowup')) {
            $total = 0;
        }

        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['freetables'][$field['id']])) {
            $freetables = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['freetables'][$field['id']];

            if (is_array($freetables) && count($freetables) > 0) {
                if ($formatAsTable) {
                    $content .= "<tr>";
                    $content .= "<td $style_title colspan='$colspan_title'>";
                }
                $content .= $label;
                if ($formatAsTable) {
                    $content .= "</td>";
                    $content .= "</tr>";
                }
                if ($formatAsTable) {
                    $content .= "<tr>";
                    foreach ($addfields as $k => $addfield) {
                        $content .= "<th $style_td colspan='$colspan'>" . $addfield . "</th>";
                    }
                    if (Plugin::isPluginActive('orderfollowup')) {
                        $content .= "<th $style_td>" . __('Total (TTC)', 'orderfollowup') . "</th>";
                    }
                    $content .= "</tr>";
                }

                foreach ($freetables as $fi) {
                    if ($formatAsTable) {
                        $content .= "<tr>";
                    }

                    foreach ($addfields as $k => $addfield) {
                        if ($formatAsTable) {
                            $content .= "<td $style_td colspan='$colspan'>";
                        }

                        if (($types[$k] ?? null) == MetaFreetablefield::TYPE_SELECT) {
                            $content .= $fi[$k] ?? '';
                        } elseif (($types[$k] ?? null) == MetaFreetablefield::TYPE_DATE) {
                            $content .= Html::convDate($fi[$k]);
                        } elseif (($types[$k] ?? null) == MetaFreetablefield::TYPE_TIME) {
                            $content .= $fi[$k] ?? '';
                        } else {
                            $content .= $fi[$k] ?? '';
                        }

                        if ($formatAsTable) {
                            $content .= "</td>";
                        }
                    }
                    if (Plugin::isPluginActive('orderfollowup')) {
                        if ($formatAsTable) {
                            $content .= "<td $style_td>";
                        }
                        $totalrow = floatval($fi['quantity']) * floatval($fi['unit_price']);
                        $content .= Html::formatNumber($totalrow, false, 2) . " €";
                        if ($formatAsTable) {
                            $content .= "</td>";
                        }
                        $total += $totalrow;
                    }
                    if ($formatAsTable) {
                        $content .= "</tr>";
                    }
                }
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $grandtotal = __('Grand total (TTC)', 'orderfollowup');
            $grandtotalHT = __('Grand total (HT)', 'orderfollowup') . " " . __('(if VAT 20%)', 'orderfollowup');
            $content .= "<tr>";
            $content .= "<th $style_td colspan='10'>" . $grandtotal . "</th>";
            $content .= "<td $style_td>" . Html::formatNumber($total, false, 2) . " €</td></tr>";
            $content .= "<tr>";
            $content .= "<th $style_td colspan='10'>" . $grandtotalHT . "</th>";
            $conf = new Config();
            $conf->getFromDB(1);
            $tva = $conf->fields['use_tva'] ?? "20";
            $totalHT = $total / (1 + ($tva / 100));
            $content .= "<td $style_td>" . Html::formatNumber($totalHT, false, 2) . " €</td></tr>";
        }

        $result[$field['rank']]['content'] .= $content;

        return $result;
    }
}
