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
use Html;
use GlpiPlugin\Metademands\Wizard;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Upload Class
 *
 **/
class Upload extends CommonDBTM
{
    private $uploads = [];

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
        return __('Add attachment', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $idline)
    {
        Html::requireJs('tinymce');


        $self = new self();

        if (is_array($value)) {
            $value = "";
        }
        $randupload = mt_rand();
        $namedrop = 'dropdoc' . $randupload;

        $arrayFiles = json_decode($value, true);

        $field = "";
        $nb = 0;
        $container = 'fileupload_info_ticket' . $namefield . $data['id'];
        if (is_array($arrayFiles)) {
            if (count($arrayFiles) > 0) {
                foreach ($arrayFiles as $k => $file) {
                    $field .= str_replace($file['_prefix_filename'], "", $file['_filename']);
                    $wiz = new Wizard();
                    $field .= "&nbsp;";
                    //own showSimpleForm for return (not echo)
                    $field .= FieldCustomvalue::showSimpleForm(
                        $wiz->getFormURL(),
                        'delete_basket_file',
                        _x('button', 'Delete permanently'),
                        [
                            'id' => $k,
                            'metademands_id' => $data['plugin_metademands_metademands_id'],
                            'plugin_metademands_fields_id' => $data['id'],
                            'idline' => $idline,
                        ],
                        'fa-times-circle'
                    );
                    $field .= "<br>";
                    $nb++;
                }
                if ($data["max_upload"] > $nb) {
                    if ($data["max_upload"] > 1) {
                        $field .= Html::file([
                            'filecontainer' => $container,
                            'editor_id' => $namefield . $data['id'],
                            'showtitle' => false,
                            'multiple' => true,
                            'display' => false,
                            'dropZone' => 'dropdoc' . $randupload,
                            'required' => ($data['is_mandatory'] ? true : false),
                            'uploads' => $self->uploads,
                        ]);
                    } else {
                        $field .= Html::file([
                            'filecontainer' => $container,
                            'editor_id' => $namefield . $data['id'],
                            'showtitle' => false,
                            'display' => false,
                            'dropZone' => 'dropdoc' . $randupload,
                            'required' => ($data['is_mandatory'] ? true : false),
                            'uploads' => $self->uploads,
                        ]);
                    }
                }
            } else {
                if ($data["max_upload"] > 1) {
                    $field .= Html::file([
                        'filecontainer' => $container,
                        'editor_id' => $namefield . $data['id'],
                        'showtitle' => false,
                        'multiple' => true,
                        'display' => false,
                        'dropZone' => 'dropdoc' . $randupload,
                        'required' => ($data['is_mandatory'] ? true : false),
                        'uploads' => $self->uploads,
                    ]);
                } else {
                    $field .= Html::file([
                        'filecontainer' => $container,
                        'editor_id' => $namefield . $data['id'],
                        'showtitle' => false,
                        'display' => false,
                        'dropZone' => 'dropdoc' . $randupload,
                        'required' => ($data['is_mandatory'] ? true : false),
                        'uploads' => $self->uploads,
                    ]);
                }
            }
        } else {
            if ($data["max_upload"] > 1) {
                $field .= Html::file([
                    'filecontainer' => $container,
                    'editor_id' => $namefield . $data['id'],
                    'showtitle' => false,
                    'multiple' => true,
                    'display' => false,
                    'dropZone' => 'dropdoc' . $randupload,
                    'required' => ($data['is_mandatory'] ? true : false),
                    'uploads' => $self->uploads,
                ]);
            } else {
                $field .= Html::file([
                    'filecontainer' => $container,
                    'editor_id' => $namefield . $data['id'],
                    'showtitle' => false,
                    'display' => false,
                    'dropZone' => 'dropdoc' . $randupload,
                    'required' => ($data['is_mandatory'] ? true : false),
                    'uploads' => $self->uploads,
                ]);
            }
        }
        //        $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$value'>";
        $field .= Html::scriptBlock("$('#$namedrop').show();");
        echo $field;
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params)
    {
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Number of documents allowed', 'metademands');
        echo "</td>";
        echo "<td>";
        $data[0] = \Dropdown::EMPTY_VALUE;
        for ($i = 1; $i <= 50; $i++) {
            $data[$i] = $i;
        }
        echo \Dropdown::showFromArray("max_upload", $data, ['value' => $params['max_upload'], 'display' => false]);
        echo "</td>";
        echo "</tr>";
    }


    /**
     * @param array $value
     * @param array $fields
     * @return array
     */
    public static function checkMandatoryFields($value = [], $post = [])
    {
        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']) {
            if (isset($post['_filename'])) {
                if (empty($post['_filename'][0])) {
                    $msg = $value['name'];
                    $checkKo = 1;
                }
            } else {
                $msg = $value['name'];
                $checkKo = 1;
            }
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

}
