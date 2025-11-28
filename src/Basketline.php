<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use DBConnection;
use Html;
use Migration;
use Session;
use Toolbox;

/**
 * Class Basketline
 */
class Basketline extends CommonDBTM
{

    static $rightname = 'plugin_metademands';


    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `users_id`                          int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `plugin_metademands_fields_id`      int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `line`                              int          NOT NULL                   DEFAULT '0',
                        `name`                              varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `value`                             text COLLATE utf8mb4_unicode_ci,
                        `value2`                            text COLLATE utf8mb4_unicode_ci,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `unicity` (`plugin_metademands_metademands_id`, `plugin_metademands_fields_id`, `line`, `name`,
                                              `users_id`),
                        KEY `users_id` (`users_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        $migration->dropKey($table, 'unicity');
        $migration->addKey($table, ['plugin_metademands_metademands_id','plugin_metademands_fields_id','line','name','users_id'], 'unicity');

        //version 3.3.0
        if (!isIndex($table, "users_id")) {
            $migration->addKey($table, "users_id");
        }
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
        }
        if (!isIndex($table, "plugin_metademands_fields_id")) {
            $migration->addKey($table, "plugin_metademands_fields_id");
        }
    }


    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }


    /**
     * @param array $line
     * @param bool $preview
     * @param       $metademands_id
     */
    static function displayBasketSummary($metademands_id, $line = [], $post = [])
    {

        if (count($line) > 0) {

            $basketline = new self();
            if ($basketlinesFind = $basketline->find(['plugin_metademands_metademands_id' => $metademands_id,
                'users_id' => Session::getLoginUserID()])) {

                $title_color = "#000";
                $meta = new Metademand();
                if ($meta->getFromDB($metademands_id)) {
                    if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
                        $title_color = $meta->fields['title_color'];
                    }
                }

//                $color = Wizard::hex2rgba($title_color, "0.03");
//                $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 10px;'";
//                echo "<div class='card-header' $style_background>";
//
//                echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
//                echo __('Your basket', 'metademands');
//

//                echo "</h2>";
//                echo "</div>";

                echo "<div class='row'>";
                echo "<div class=\"card mx-1 my-2 flex-grow-1\">";
                echo "<div class='col-12 align-self-center'>";

                echo "<section class='card-body' style='width: 100%;'>";

                echo "<h2 class='card-title mb-2 text-break' style='color: $title_color;'>";
                echo __('Your basket', 'metademands');
                echo "</h2>";

                echo "<div class='mydraft right' style='position: absolute;top: 0;margin-top: 10px;;right: 0;'>";

                $target = Toolbox::getItemTypeFormURL(Wizard::class);
                Html::showSimpleForm(
                    $target,
                    'clear_basket',
                    _sx('button', 'Clear the basket', 'metademands'),
                    [
                        'metademands_id' => $metademands_id,
                    ],
                    'ti-trash',
                    "class='btn btn-primary'"
                );

                echo "</div>";
                echo Html::hidden('metademands_id', ['value' => $metademands_id]);
                echo Html::hidden('form_metademands_id', ['value' => $metademands_id]);
                echo "</section>";
                echo "</div>";
                echo "</div>";
                echo "</div>";

                $basketLines = [];
                foreach ($basketlinesFind as $basketLine) {
                    $basketLines[$basketLine['line']][] = $basketLine;
                }

                foreach ($basketLines as $idline => $fieldlines) {
//                    echo "<table class='tab_cadre_fixehov' style='border: 3px #CCC solid;'>";
                    self::retrieveDatasByType($metademands_id, $idline, $fieldlines, $line);
//                    echo "</table>";
                }

                echo "<div class='row'>";
                echo "<div class=\"bt-feature col-md-12 \">";

                $target = Toolbox::getItemTypeFormURL(Wizard::class);
                Html::showSimpleForm(
                    $target,
                    'clean_form',
                    __('Previous'),
                    [
                        'metademands_id' => $metademands_id,
                        'step' => Metademand::STEP_SHOW,
                    ],
                    '',
                    "class='btn btn-primary'"
                );

                echo "<span style='float:right'>";
//                $title = _sx('button', 'Send order', 'metademands');
                $title = _sx('button', 'Save & Post', 'metademands');
                $current_ticket = $post["current_ticket_id"] = $post["tickets_id"];
                echo Html::submit($title, ['name' => 'send_order',
                    'form' => '',
                    'icon' => 'ti ti-shopping-bag',
                    'id' => 'submitOrder',
                    'class' => 'btn btn-success right']);
                echo "</span>";
                echo "</div></div>";
                $paramUrl = "";
                $meta_validated = false;
                if ($current_ticket > 0 && !$meta_validated) {
                    $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
                }
                $meta_id = $post['metademands_id'];
                $post = json_encode($post);
                echo "<script>
                          $('#submitOrder').click(function() {
                             var meta_id = $meta_id;
                             $.ajax({
                               url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
                               type: 'POST',
                               data: $post,
                               success: function (response) {
                                  $.ajax({
                                            url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                                            type: 'POST',
                                            data: $post,
                                            success: function (response) {
                                               window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=create_metademands';
                                            },
                                            error: function (xhr, status, error) {
                                               console.log(xhr);
                                               console.log(status);
                                               console.log(error);
                                            }
                                         });
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
            }
        }
    }

    /**
     * @param $idline
     * @param $values
     * @param $fields
     */
    public static function retrieveDatasByType($metademands_id, $idline, $values, $fields)
    {

        $target = Toolbox::getItemTypeFormURL(Wizard::class);
        echo "<form method='post' action=\"$target\">";
        echo "<table class='tab_cadre_fixehov' style='border: 3px #CCC solid;'>";
        echo Html::hidden('metademands_id', ['value' => $metademands_id]);
        echo Html::hidden('form_metademands_id', ['value' => $metademands_id]);


        foreach ($fields as $k => $v) {

            $field = new Field();
            if ($field->getFromDB($v["id"])) {
                $params = Field::getAllParamsFromField($field);
                $v = array_merge($v, $params);
            }

            //hide blocks
            if ($v['type'] == 'informations' || $v['type'] == 'title-block' || $v['type'] == 'title') {
                continue;
            }


            if (isset($v['is_basket']) && $v['is_basket'] == 0
                && isset($v['is_order']) && $v['is_order'] == 0) {
                continue;
            }

            echo "<tr class='tab_bg_1' id-field='field_basket_".$idline . $v["id"] . "'>";

            echo "<td>";

            if (empty($label = Field::displayField($v['id'], 'name'))) {
                $label = $v['name'];
                echo $label;
            }

            if ($v['type'] == "date_interval") {
                if (empty($label2 = Field::displayField($v['id'], 'label2'))) {
                    $label2 = $v['label2'];
                }
                echo "<br><br><br>" . Toolbox::stripTags($label2);
            }

            echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $v['id'] . "'>";
            if ($v['is_mandatory'] && $v['type'] != 'parent_field') {
                echo "*";
            }
            echo "</span>";

            echo "</td>";

            echo "<td>";

            foreach ($values as $key => $value) {

                if ($v['id'] == $value['plugin_metademands_fields_id']) {

                    $v['value'] = '';
                    if (isset($value['value'])) {
                        $v['value'] = $value['value'];
                    }

                    echo Field::getFieldInput([], $v, true, 0, $idline, false, "");
                    if ($v['type'] == "date_interval" || $v['type'] == "datetime_interval") {
                        if (isset($value['value2'])) {
                            $v['value'] = $value['value2'];
                        }
                        $v['id'] = $v['id'] . "-2";
                        echo Field::getFieldInput([], $v, true, 0, $idline, false, "");
                    }
                }
            }
            echo "</td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>";

        echo "<button type='submit' class='submit btn btn-primary' name='update_basket_line' value='$idline' title='"
            . _sx('button', 'Update this line', 'metademands') . "'>";
        echo "<i class='ti ti-device-floppy' data-hasqtip='0' aria-hidden='true'></i>";
        echo "</button>";

        echo "</td>";
        echo "<td class='center'>";
        $target = Toolbox::getItemTypeFormURL(Wizard::class);
        Html::showSimpleForm(
            $target,
            'delete_basket_line',
            _sx('button', 'Delete this line', 'metademands'),
            [
                'metademands_id' => $metademands_id,
                'delete_basket_line' => $idline,
            ],
            'ti-trash',
            "class='btn btn-danger'"
        );

        echo "</td>";
        echo "</tr></table>";
        Html::closeForm();
    }


    /**
     * @param $content
     * @param $plugin_metademands_metademands_id
     *
     * @throws \GlpitestSQLError
     */
    function addToBasket($content, $plugin_metademands_metademands_id)
    {
        global $DB;

        $line = 0;

        $criteria = [
            'SELECT' => ['MAX' => 'line AS line'],
            'FROM' => $this->getTable(),
            'WHERE' => [
                'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
                'users_id' => Session::getLoginUserID(),
            ],
        ];
        $iterator = $DB->request($criteria);

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                if (isset($data['line'])) {
                    $line = $data['line'] + 1;
                }
            }
        }

        foreach ($content as $values) {

            if ($values['item'] == "informations") {
                continue;
            }
            //TODO drop if empty datas ??
            $name = $values['item'];

            if ($values['type'] != "dropdown_object"
                && $values['type'] != "dropdown"
                && $values['type'] != "dropdown_meta"
                && ($values['item'] != null && strpos($values['item'], 'plugin_') === false)) {
                $name = $values['type'];
            }

            $this->add(['name' => $name,
                'value' => isset($values['value']) ? $values['value'] : NULL,
                'value2' => $values['value2'],
                'line' => $line,
                'plugin_metademands_fields_id' => $values['plugin_metademands_fields_id'],
                'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
                'users_id' => Session::getLoginUserID()]);

        }
    }

    /**
     * @param $input
     * @param $line
     */
    function updateFromBasket($input, $line)
    {


        $new_files = [];
        unset($input['field']);

        if (isset($input['_filename']) && !empty($input['_filename'])) {
            foreach ($input['_filename'] as $key => $filename) {
                $new_files[$key]['_prefix_filename'] = $input['_prefix_filename'][$key];
                $new_files[$key]['_tag_filename'] = $input['_tag_filename'][$key];
                $new_files[$key]['_filename'] = $input['_filename'][$key];
            }
        }
        if (isset($input['field_basket_' . $line])) {
            foreach ($input['field_basket_' . $line] as $fields_id => $value) {

                //get id from form_metademands_id & $id
                $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['form_metademands_id'],
                    'plugin_metademands_fields_id' => $fields_id,
                    'line' => $input['update_basket_line']]);

                $value2 = "";
                if ($this->fields['name'] != "ITILCategory_Metademands") {
                    if ($this->fields['name'] == "upload") {

                        $old_files = [];
                        if (isset($this->fields['value']) && !empty($this->fields['value'])) {
                            $old_files = json_decode($this->fields['value'], 1);
                        }
                        if (is_array($new_files) && count($new_files) > 0
                            && is_array($old_files) && count($old_files) > 0) {
                            $files = array_merge($old_files, $new_files);
                            $newvalue = json_encode($files);
                        } else {
                            $newvalue = json_encode($new_files);
                        }

                    } else {
                        $newvalue = is_array($value) ? FieldParameter::_serialize($value) : $value;
                    }

                    if (!str_ends_with($fields_id, "-2")) {
                        $this->update(['plugin_metademands_fields_id' => $fields_id,
                            'value' => $newvalue,
                            'id' => $this->fields['id']]);
                    }
                    //date-interval
                    if (str_ends_with($fields_id, "-2")) {
                        $value2 = $value;
                        $fields_id = rtrim($fields_id, '-2');
                        $this->update(['plugin_metademands_fields_id' => $fields_id,
                            'value2' => $value2,
                            'id' => $this->fields['id']]);
                    }
                }
            }
        }

        if (isset($input['basket_plugin_servicecatalog_itilcategories_id'])) {

            $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['form_metademands_id'],
                'name' => "ITILCategory_Metademands",
                'line' => $input['update_basket_line']]);

            $this->update(['value' => $input['basket_plugin_servicecatalog_itilcategories_id'],
                'id' => $this->fields['id']]);
        }


        Session::addMessageAfterRedirect(__("The line has been updated", "metademands"), false, INFO);
    }

    /**
     * @param $input
     */
    function deleteFromBasket($input)
    {

        $this->deleteByCriteria(['line' => $input['delete_basket_line'],
            'users_id' => Session::getLoginUserID()]);
        Session::addMessageAfterRedirect(__("The line has been deleted", "metademands"), false, INFO);
    }

    /**
     * @param $input
     */
    function deleteFileFromBasket($input)
    {

        $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['metademands_id'],
            'plugin_metademands_fields_id' => $input['plugin_metademands_fields_id'],
            'line' => $input['idline']]);

        $files = json_decode($this->fields['value'], 1);
        unset($files[$input['id']]);
        $files = json_encode($files);
        $this->update(['plugin_metademands_fields_id' => $input['plugin_metademands_fields_id'],
            'value' => $files,
            'id' => $this->fields['id']]);
    }
}
