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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * PluginMetademandsSignature Class
 *
 **/
class PluginMetademandsSignature extends CommonDBTM
{

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    static function getTypeName($nb = 0)
    {
        return __('Signature', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        $name = $namefield . "[" . $data['id'] . "]";

        $required = 0;
        if ($data['is_mandatory'] == 1) {
            $required = 1;
        }

        $metademands_id = $data['plugin_metademands_metademands_id'];
        $id = $data['id'];

        $msg_add = "<i class=\"fas fa-check-circle fa-1x\" style=\"color:forestgreen\"></i> ".__('Your signature has been uploaded', 'metademands');
        $msg_remove = "<i class=\"fas fa-times-circle fa-1x\" style=\"color:darkred\"></i> ".__('Your signature has been deleted', 'metademands');
        $msg_failadd = "<i class=\"fas fa-times-circle fa-1x\" style=\"color:darkred\"></i> ".__('There was a problem on upload your signature', 'metademands');
        $msg_failremove = "<i class=\"fas fa-times-circle fa-1x\" style=\"color:darkred\"></i> ".__('There was a problem on delete your signature', 'metademands');
        $msg_mandatory = "<i class=\"fas fa-times-circle fa-1x\" style=\"color:darkred\"></i> ".__('This field is mandatory', 'metademands');

        $field = "<div class='wrapper'>";
        $field .= "<canvas id='signature-pad' class='signature-pad' width=400 height=100></canvas>";
        $field .= "</div>";
        $field .= "<br><div>";
        $field .= "<button id='savesign' form='' class='btn btn-primary'>" . __(
                'Add your signature',
                'metademands'
            ) . "</button> ";
        $field .= "<button id='clearsign' form='' class='btn btn-primary'>" . __('Clear', 'metademands') . "</button>";
        $field .= "</div>";

        $js = Html::script(PLUGIN_METADEMANDS_DIR_NOFULL . "/lib/signature/js/signature_pad.umd.min.js");
        $css = Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/lib/signature/css/signature_pad.umd.css");

        $field .= $js;
        $field .= $css;

        $field .= "<script type='text/javascript'>
                        var signaturePad = new SignaturePad(document.getElementById('signature-pad'), {
                            backgroundColor: 'rgba(255, 255, 255, 0)',
                            penColor: 'rgb(0, 0, 0)'
                        });
                        var saveButton = document.getElementById('savesign');
                        var cancelButton = document.getElementById('clearsign');
                        var meta_id = $metademands_id;
                        var msg_add = '$msg_add';
                        var msg_remove = '$msg_remove';
                        var msg_failadd = '$msg_failadd';
                        var msg_failremove = '$msg_failremove';
                        var msg_mandatory = '$msg_mandatory';
                        var is_mandatory = $required;
                        let hasDrawn = false;
                        
                        if (is_mandatory == true) {
                            sessionStorage.setItem('mandatory_sign', $id);
                        }
                        
                        saveButton.addEventListener('click', function (event) {
                            
                            var datasign = '';
                            if (!signaturePad.isEmpty()) {
                                var datasign = signaturePad.toDataURL('image/png');
                                hasDrawn = true;
                            }
                            if (hasDrawn == false && is_mandatory == true) {
                                $('.result').html(msg_mandatory);
                            }
                            
                            if (hasDrawn) {
                                $.ajax({
                                       url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addsignature.php',
                                       type: 'POST',
                                       datatype: 'html',
                                       data : { datasign : datasign, metademands_id : meta_id},
                                       success: function (response) {
                                            $('.result').html(msg_add);
                                            $('#hiddenId').attr('value', response);
                                            sessionStorage.removeItem('mandatory_sign', $id);
                                        },
                                       error: function() {
                                            $('.result').html(msg_failadd);
                                       }
                                    });
                            }
                        });
                        
                        cancelButton.addEventListener('click', function (event) {
                            signaturePad.clear();
                            hasDrawn = false;
                            var datasign = $('#hiddenId').val();
                            $.ajax({
                                   url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/removesignature.php',
                                   type: 'POST',
                                   datatype: 'html',
                                   data : { metademands_id : meta_id, datasign : datasign},
                                   success: function (response) {
                                        $('.result').html(msg_remove);
                                        $('#hiddenId').attr('value', '');
                                        if (is_mandatory == true) {
                                            sessionStorage.setItem('mandatory_sign', $id);
                                        }
                                    },
                                   error: function() {
                                        $('.result').html(msg_failremove);
                                   }
                                });
                        });
                    </script>";

        $field .= "<br><span class='result'></span>";
        $field .= "<input type='hidden' id='hiddenId' name='$name' value=''>";
        echo $field;
    }


    static function showFieldCustomValues($params)
    {

    }

    static function showFieldParameters($params)
    {


    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {

    }

    static function showValueToCheck($item, $params)
    {

    }


    /**
     * @param array $value
     * @param array $fields
     * @return bool
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {

    }

    static function isCheckValueOK($value, $check_value)
    {

    }

    static function showParamsValueToCheck($params)
    {


    }

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function taskScript($data)
    {


    }

    static function fieldsHiddenScript($data)
    {


    }

    public static function blocksHiddenScript($data)
    {

    }

    public static function getFieldValue($field)
    {
        $picture_url = Toolbox::getPictureUrl($field['value']);

        return "<img src='$picture_url'>";
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $colspan = $is_order ? 6 : 1;
        if ($field['value'] != 0) {
            $result[$field['rank']]['display'] = true;
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
