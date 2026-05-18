<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
  Copyright (C) 2018-2026 by the Metademands Development Team.

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
use Html;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Signature Class
 *
 **/
class Signature extends CommonDBTM
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
        return __('Signature', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        $field_id       = $data['id'];
        $name           = $namefield . "[" . $field_id . "]";
        $is_mandatory   = ($data['is_mandatory'] == 1) ? 1 : 0;
        $metademands_id = (int) $data['plugin_metademands_metademands_id'];

        // Unique HTML IDs per field to support multiple Signature fields on the same form
        $canvas_id = "signature-pad-$field_id";
        $save_id   = "savesign-$field_id";
        $clear_id  = "clearsign-$field_id";
        $hidden_id = "hiddenId-$field_id";
        $result_id = "result-$field_id";

        // json_encode safely escapes quotes and special chars in translated strings
        $msg_add        = json_encode("<i class=\"ti ti-circle-check fa-1x\" style=\"color:forestgreen\"></i> " . __('Your signature has been uploaded', 'metademands'));
        $msg_remove     = json_encode("<i class=\"ti ti-circle-x fa-1x\" style=\"color:darkred\"></i> " . __('Your signature has been deleted', 'metademands'));
        $msg_failadd    = json_encode("<i class=\"ti ti-circle-x fa-1x\" style=\"color:darkred\"></i> " . __('There was a problem on upload your signature', 'metademands'));
        $msg_failremove = json_encode("<i class=\"ti ti-circle-x fa-1x\" style=\"color:darkred\"></i> " . __('There was a problem on delete your signature', 'metademands'));
        $msg_mandatory  = json_encode("<i class=\"ti ti-circle-x fa-1x\" style=\"color:darkred\"></i> " . __('This field is mandatory', 'metademands'));

        $field  = "<div class='wrapper'>";
        $field .= "<canvas id='$canvas_id' class='signature-pad' width=400 height=100></canvas>";
        $field .= "</div>";

        // Show existing signature as preview when editing a saved value
        if (!empty($value)) {
            $picture_url = Toolbox::getPictureUrl($value);
            $field .= "<div id='existing-sign-$field_id'>";
            $field .= "<img src='" . htmlspecialchars($picture_url, ENT_QUOTES) . "' style='max-height:100px;border:1px solid #ccc;'>";
            $field .= "</div>";
        }

        $field .= "<br><div>";
        // type='button' prevents accidental form submission on click
        $field .= "<button type='button' id='$save_id' class='btn btn-primary'>" . __('Add your signature', 'metademands') . "</button> ";
        $field .= "<button type='button' id='$clear_id' class='btn btn-primary'>" . __('Clear', 'metademands') . "</button>";
        $field .= "</div>";

        $existing_value = htmlspecialchars($value ?? '', ENT_QUOTES);

        // Span et input placés avant le <script> pour garantir leur présence dans le DOM à l'exécution
        $field .= "<br><span class='result' id='$result_id'></span>";
        $field .= "<input type='hidden' id='$hidden_id' name='$name' value='$existing_value'>";

        $field .= Html::script(PLUGIN_METADEMANDS_WEBDIR . "/lib/signature/js/signature_pad.umd.min.js");
        $field .= Html::css(PLUGIN_METADEMANDS_WEBDIR . "/lib/signature/css/signature_pad.umd.css");

        // IIFE to scope all variables — supports multiple Signature fields per page
        $field .= "<script type='text/javascript'>
        (function () {
            var signaturePad = new SignaturePad(document.getElementById('$canvas_id'), {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)'
            });
            var saveButton   = document.getElementById('$save_id');
            var clearButton  = document.getElementById('$clear_id');
            var resultEl     = document.getElementById('$result_id');
            var hiddenInput  = document.getElementById('$hidden_id');
            var meta_id      = $metademands_id;
            var is_mandatory = $is_mandatory;
            var field_id     = $field_id;
            var msg_add        = $msg_add;
            var msg_remove     = $msg_remove;
            var msg_failadd    = $msg_failadd;
            var msg_failremove = $msg_failremove;
            var msg_mandatory  = $msg_mandatory;
            var hasDrawn = false;

            if (is_mandatory) {
                sessionStorage.setItem('mandatory_sign_' + field_id, field_id);
            }

            saveButton.addEventListener('click', function () {
                let datasign = '';
                if (!signaturePad.isEmpty()) {
                    datasign = signaturePad.toDataURL('image/png');
                    hasDrawn = true;
                }
                if (!hasDrawn && is_mandatory) {
                    resultEl.innerHTML = msg_mandatory;
                    return;
                }
                if (hasDrawn) {
                    $.ajax({
                        url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addsignature.php',
                        type: 'POST',
                        dataType: 'html',
                        data: { datasign: datasign, metademands_id: meta_id },
                        success: function (response) {
                            resultEl.innerHTML = msg_add;
                            hiddenInput.value = response;
                            sessionStorage.removeItem('mandatory_sign_' + field_id);
                        },
                        error: function () {
                            resultEl.innerHTML = msg_failadd;
                        }
                    });
                }
            });

            clearButton.addEventListener('click', function () {
                signaturePad.clear();
                hasDrawn = false;
                let datasign = hiddenInput.value;
                $.ajax({
                    url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/removesignature.php',
                    type: 'POST',
                    dataType: 'html',
                    data: { metademands_id: meta_id, datasign: datasign },
                    success: function () {
                        resultEl.innerHTML = msg_remove;
                        hiddenInput.value = '';
                        if (is_mandatory) {
                            sessionStorage.setItem('mandatory_sign_' + field_id, field_id);
                        }
                    },
                    error: function () {
                        resultEl.innerHTML = msg_failremove;
                    }
                });
            });
        })();
        </script>";

        echo $field;
    }


    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params) {}

    public static function getParamsValueToCheck($fieldoption, $item, $params) {}

    public static function showValueToCheck($item, $params) {}


    /**
     * @param array $value
     * @param array $fields
     * @return array
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

    public static function showParamsValueToCheck($params) {}

    public static function fieldsMandatoryScript($data) {}

    public static function taskScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        $picture_url = Toolbox::getPictureUrl($field['value']);
        return "<img src='" . htmlspecialchars($picture_url, ENT_QUOTES) . "'>";
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
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        }

        return $result;
    }

}
