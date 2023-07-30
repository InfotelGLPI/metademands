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
 * PluginMetademandsUpload Class
 *
 **/
class PluginMetademandsUpload extends CommonDBTM
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
        return __('Add a document');
    }

    static function showWizardField($data, $namefield, $value, $on_basket, $idline)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $arrayFiles = json_decode($value, true);
        $field      = "";
        $nb         = 0;
        $container = 'fileupload_info_ticket'.$namefield . $data['id'];
        if (is_array($arrayFiles)) {
            if (count($arrayFiles) > 0) {
                foreach ($arrayFiles as $k => $file) {
                    $field .= str_replace($file['_prefix_filename'], "", $file['_filename']);
                    $wiz = new PluginMetademandsWizard();
                    $field .= "&nbsp;";
                    //own showSimpleForm for return (not echo)
                    $field .= PluginMetademandsField::showSimpleForm(
                        $wiz->getFormURL(),
                        'delete_basket_file',
                        _x('button', 'Delete permanently'),
                        ['id' => $k,
                            'metademands_id' => $data['plugin_metademands_metademands_id'],
                            'plugin_metademands_fields_id' => $data['id'],
                            'idline' => $idline
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
                            'editor_id' => '',
                            'showtitle' => false,
                            'multiple' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? true : false)]);
                    } else {
                        $field .= Html::file([
                            'filecontainer' => $container,
                            'editor_id' => '',
                            'showtitle' => false,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? true : false)
                        ]);
                    }
                }
            } else {
                if ($data["max_upload"] > 1) {
                    $field .= Html::file([
                        'filecontainer' => $container,
                        'editor_id'     => '',
                        'showtitle'     => false,
                        'multiple'      => true,
                        'display'       => false,
                        'required' => ($data['is_mandatory'] ? true : false)]);
                } else {
                    $field .= Html::file([
                        'filecontainer' => $container,
                        'editor_id'     => '',
                        'showtitle'     => false,
                        'display'       => false,
                        'required' => ($data['is_mandatory'] ? true : false)
                    ]);
                }
            }
        } else {
            if ($data["max_upload"] > 1) {
                $field .= Html::file([
                    'filecontainer' => $container,
                    'editor_id'     => '',
                    'showtitle'     => false,
                    'multiple'      => true,
                    'display'       => false,
                    'required' => ($data['is_mandatory'] ? true : false)]);
            } else {
                $field .= Html::file([
                    'filecontainer' => $container,
                    'editor_id'     => '',
                    'showtitle'     => false,
                    'display'       => false,
                    'required' => ($data['is_mandatory'] ? true : false)
                ]);
            }
        }
        $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$value'>";

        echo $field;
    }

    static function showFieldCustomValues($values, $key, $params)
    {

    }

    /**
     * @param array $value
     * @param array $fields
     * @return bool
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

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function fieldsHiddenScript($data)
    {

    }

    public static function blocksHiddenScript($data)
    {
        
    }

}
