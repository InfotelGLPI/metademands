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
use Html;
use Plugin;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Orderfollowup\Config;
use Toolbox;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Freetablefield as MetaFreetablefield;

/**
 * Freetable Class
 *
 **/
class Freetable extends CommonDBTM
{
    public static string $rightname = 'plugin_metademands';

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
            // Escaped by the template rather than here, so the style fragment is not
            // pre-escaped and then emitted raw.
            $background_color = "background-color:" . Metademand::toThemedBackground($meta->fields['background_color']) . ";";
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

        // Header columns: label, mandatory flag and comment (tooltip) of each column.
        $columns = [];
        foreach ($addfields as $k => $addfield) {
            $columns[] = [
                'label'     => $addfield,
                'mandatory' => in_array($k, $is_mandatory),
                'comment'   => (string) ($commentfields[$addfield] ?? ''),
            ];
        }

        $orderfollowup_is_active = 0;
        if (Plugin::isPluginActive('orderfollowup')) {
            $orderfollowup_is_active = 1;
        }

        $lastid = 0;
        if (is_array($values) && count($values) > 0) {
            ksort($values);
            $lastvalues = end($values);
            $lastid = $lastvalues['id'];
        }

        // Parameters of public/scripts/metademands_freelines.js, exposed as
        // window.metademandfreelinesparams<rand> by public/scripts/wizard_form.js
        // (initFreetable) from the data-md-freetable-params attribute.
        $freetable_params = [
            'existLine' => __('You can\'t create a new line when there is an existing one', 'metademands'),
            'rand' => (string) $rand,
            'root' => PLUGIN_METADEMANDS_WEBDIR,
            'encoded_fields' => $addfields,
            'mandatory_encoded_fields' => $is_mandatory,
            'types_encoded_fields' => $types,
            'dropdown_values_encoded_fields' => $dropdown_values,
            'orderfollowupisactive' => $orderfollowup_is_active,
            'size' => $size,
            'empty_value' => \Dropdown::EMPTY_VALUE,
            'plugin_metademands_metademands_id' => (int) $plugin_metademands_metademands_id,
            'lastid' => (int) $lastid,
            'text' => MetaFreetablefield::TYPE_TEXT,
            'select' => MetaFreetablefield::TYPE_SELECT,
            'number' => MetaFreetablefield::TYPE_NUMBER,
            'readonly' => MetaFreetablefield::TYPE_READONLY,
            'date' => MetaFreetablefield::TYPE_DATE,
            'time' => MetaFreetablefield::TYPE_TIME,
        ];

        // Build data rows for the Twig template.
        $rows = [];
        if (is_array($values) && count($values) > 0) {
            $kindmap = [
                MetaFreetablefield::TYPE_TEXT   => 'text',
                MetaFreetablefield::TYPE_SELECT => 'select',
                MetaFreetablefield::TYPE_NUMBER => 'number',
                MetaFreetablefield::TYPE_DATE   => 'date',
                MetaFreetablefield::TYPE_TIME   => 'time',
            ];
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

                $cells = [];
                foreach ($addfields as $k => $addfield) {
                    if (isset($l[$k]) && isset($kindmap[$types[$k]])) {
                        $cell = [
                            'kind'  => $kindmap[$types[$k]],
                            'id'    => $k . '_' . $idline,
                            'name'  => $k,
                            'value' => (string) $l[$k],
                            'size'  => $size,
                        ];
                        if ($types[$k] == MetaFreetablefield::TYPE_SELECT) {
                            $options = [];
                            foreach ($dropdown_values[$k] as $key => $dropdown_value) {
                                $options[] = [
                                    'dv'          => (string) $dropdown_value,
                                    'is_selected' => $key == $l[$k],
                                ];
                            }
                            $cell['options'] = $options;
                        }
                        $cells[] = $cell;
                    }
                }

                $has_total = false;
                $linetotal = '';
                if (Plugin::isPluginActive('orderfollowup')) {
                    $has_total = true;
                    $linetotal = number_format($quantity * $unit_price, 2, '.', ' ');
                }

                $rows[] = [
                    'idline'    => $idline,
                    'cells'     => $cells,
                    'has_total' => $has_total,
                    'linetotal' => $linetotal,
                ];
            }
        }

        $has_orderfollowup = Plugin::isPluginActive('orderfollowup');
        // Grand total computed by public/scripts/wizard_form.js when the basket is
        // validated, from the data-md-freetable-* attributes of the button.
        $grandtotal = [];
        if ($has_orderfollowup) {
            $conf = new Config();
            $conf->getFromDB(1);
            $tva = $conf->fields['use_tva'] ?? "20";
            $grandtotal = [
                'tva' => $tva / 100,
                'label' => __('Grand total (TTC)', 'orderfollowup'),
                'label_ht' => __('Grand total (HT)', 'orderfollowup') . " " . __('(if VAT 20%)', 'orderfollowup'),
            ];
        }

        echo $field;
        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_freetable.html.twig', [
            'rand'              => $rand,
            'background_color'  => $background_color,
            'columns'           => $columns,
            'params'            => $freetable_params,
            'autoadd'           => $nb_values === 0,
            'rows'              => $rows,
            'has_orderfollowup' => $has_orderfollowup,
            'grandtotal'        => $grandtotal,
            'validate_label'    => __('Validate the basket', 'metademands'),
        ]);
    }

    public static function showFreetableFields($params)
    {
        $custom_values = $params['custom_values'];

        $nbfields     = 0;
        $field_custom = new MetaFreetablefield();
        if ($customs = $field_custom->find(
            ["plugin_metademands_fields_id" => $params['plugin_metademands_fields_id']],
            "rank",
        )) {
            if (count($customs) > 0) {
                $nbfields = count($customs);
            }
        }

        $fields_id = $params['plugin_metademands_fields_id'] ?? 0;
        $maxrank   = 0;
        $entries   = [];

        if (is_array($custom_values) && !empty($custom_values)) {
            foreach ($custom_values as $key => $value) {
                $entries[] = [
                    'key'             => $key,
                    'rank'            => $value['rank'],
                    'type'            => $value['type'],
                    'internal_name'   => $value['internal_name'],
                    'name'            => $value['name'],
                    'comment'         => $value['comment'],
                    'dropdown_values' => $value['dropdown_values'],
                    'is_mandatory'    => $value['is_mandatory'],
                ];

                $maxrank = $value['rank'];
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/freetable_fields.html.twig', [
            'entries'           => $entries,
            'fields_id'         => $fields_id,
            'has_fields_id'     => isset($params['plugin_metademands_fields_id']),
            'type_object'       => $params['type'] ?? 'freetable',
            'target'            => MetaFreetablefield::getFormURL(),
            'reorder_url'       => PLUGIN_METADEMANDS_WEBDIR . '/ajax/reorder.php',
            'reorder_params'    => json_encode([
                'field_id' => $params['plugin_metademands_fields_id'] ?? '',
                'type'     => $params['type'] ?? 'freetable',
            ]),
            'type_choices'      => MetaFreetablefield::getTypeFields(),
            'max_rank'          => $maxrank,
            'root_doc'          => PLUGIN_METADEMANDS_WEBDIR,
            'show_init'         => $nbfields < 6,
            'type_text'         => MetaFreetablefield::TYPE_TEXT,
            'type_select'       => MetaFreetablefield::TYPE_SELECT,
            'type_number'       => MetaFreetablefield::TYPE_NUMBER,
            'type_date'         => MetaFreetablefield::TYPE_DATE,
            'type_time'         => MetaFreetablefield::TYPE_TIME,
        ]);
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

    /**
     * Build the free table for the PDF export.
     *
     * Returns the column titles and the data rows separately so the PDF stays aligned:
     *  - 'header' holds every defined column label, ordered by rank (same authoritative
     *    source as the on-screen HTML rendering in displayFieldItems);
     *  - 'rows'   holds one entry per submitted line, each cell aligned to that column
     *    order (missing/empty cells become '').
     *
     * The previous implementation derived the header by scraping the submitted row data,
     * so an empty cell in a row produced non-contiguous keys (array_unique keeps original
     * keys) and the column titles fell out of the PDF table. Sourcing the header from the
     * column definitions makes it independent of the submitted values.
     *
     * @return array{header: string[], rows: array<int, string[]>}
     */
    public static function displayFieldPDF($elt, $fields, $label)
    {
        // Authoritative column list (internal_name => label), ordered by rank.
        $columns = [];
        $field_custom = new MetaFreetablefield();
        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $elt['id']], "rank")) {
            foreach ($customs as $custom) {
                $translated_col = Field::displayField($elt['id'], 'freetablecol' . $custom['rank']);
                // Keep the label UTF-8: MetademandPdf extends \TCPDF (UTF-8 native).
                // decodeFromUtf8() would turn it into ISO-8859-1 and drop the accents,
                // which is exactly what made the accented column titles disappear.
                $columns[$custom['internal_name']] = $translated_col !== '' ? $translated_col : $custom['name'];
            }
        }

        if (count($columns) === 0) {
            return ['header' => [], 'rows' => []];
        }

        $rows = [];
        $values_elt = $fields[$elt['id']] ?? [];
        if (is_array($values_elt)) {
            foreach ($values_elt as $value_elt) {
                if (!is_array($value_elt)) {
                    continue;
                }
                // Align every cell to the column order; keep empty cells as '' so the row
                // width always matches the header width.
                $row = [];
                foreach ($columns as $internal_name => $col_label) {
                    $row[] = $value_elt[$internal_name] ?? '';
                }
                $rows[] = $row;
            }
        }

        return ['header' => array_values($columns), 'rows' => $rows];
    }

    /**
     * Plain text of a free table cell for the ticket content. The value comes straight from
     * the requester through the session and is stored raw, so it goes through the same chain
     * as the other free input fields (see Email::getFieldValue()); the template escapes it.
     *
     * @param mixed $value
     * @param mixed $type  MetaFreetablefield::TYPE_* of the column
     */
    private static function getCellText($value, $type): string
    {
        if ($type == MetaFreetablefield::TYPE_DATE) {
            return (string) Html::convDate((string) $value);
        }

        return RichText::getTextFromHtml(RichText::getSafeHtml((string) $value));
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
        //        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'])) {
        //            $quantities = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['quantities'];
        //        }
        $materials = $field["value"];

        //        if (is_object($materials)) {
        //            $materials = json_decode(json_encode($materials), true);
        //        }
        $rows = [];
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
        $total = 0;

        if (isset($_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['freetables'][$field['id']])) {
            $freetables = $_SESSION['plugin_metademands'][$field['plugin_metademands_metademands_id']]['freetables'][$field['id']];

            if (is_array($freetables) && count($freetables) > 0) {
                $rows[] = [['text' => (string) $label, 'title' => true, 'colspan' => $colspan_title]];

                $header = [];
                foreach ($addfields as $addfield) {
                    $header[] = ['text' => (string) $addfield, 'heading' => true, 'colspan' => $colspan, 'table_only' => true];
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $header[] = ['text' => __('Total (TTC)', 'orderfollowup'), 'heading' => true, 'table_only' => true];
                }
                $rows[] = $header;

                foreach ($freetables as $fi) {
                    $row = [];
                    foreach ($addfields as $k => $addfield) {
                        $row[] = ['text' => self::getCellText($fi[$k] ?? '', $types[$k] ?? null), 'colspan' => $colspan];
                    }
                    if (Plugin::isPluginActive('orderfollowup')) {
                        $totalrow = floatval($fi['quantity']) * floatval($fi['unit_price']);
                        $row[] = ['text' => Html::formatNumber($totalrow, false, 2) . " €"];
                        $total += $totalrow;
                    }
                    $rows[] = $row;
                }
            }
        }

        if (Plugin::isPluginActive('orderfollowup')) {
            $conf = new Config();
            $conf->getFromDB(1);
            $tva = $conf->fields['use_tva'] ?? "20";
            $totalHT = $total / (1 + ($tva / 100));
            $rows[] = [
                ['text' => __('Grand total (TTC)', 'orderfollowup'), 'heading' => true, 'colspan' => 10],
                ['text' => Html::formatNumber($total, false, 2) . " €"],
            ];
            $rows[] = [
                ['text' => __('Grand total (HT)', 'orderfollowup') . " " . __('(if VAT 20%)', 'orderfollowup'), 'heading' => true, 'colspan' => 10],
                ['text' => Html::formatNumber($totalHT, false, 2) . " €"],
            ];
        }

        $result[$field['rank']]['content'] .= Field::renderContentRows(
            (bool) $formatAsTable,
            (string) $title_style,
            'border: 1px solid #CCC;',
            $rows,
        );

        return $result;
    }
}
