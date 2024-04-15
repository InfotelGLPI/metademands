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

/**
 * Class PluginMetademandsBasketline
 */
class PluginMetademandsBasketline extends CommonDBTM
{

    static $rightname = 'plugin_metademands';

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
                $meta = new PluginMetademandsMetademand();
                if ($meta->getFromDB($metademands_id)) {
                    if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
                        $title_color = $meta->fields['title_color'];
                    }
                }

                $color = PluginMetademandsWizard::hex2rgba($title_color, "0.03");
                $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 10px;'";
                echo "<div class='card-header' $style_background>";

                echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
                echo __('Your basket', 'metademands');

                echo "<div class='mydraft right' style='display: inline;float: right;'>";
                echo "&nbsp;<button type='submit' class='pointer btn btn-light' name='clear_basket' title='"
                    . _sx('button', 'Clear the basket', 'metademands') . "'>";
                echo "<i class='fas fa-trash' data-hasqtip='0' aria-hidden='true'></i>";
                echo "</button>";
                echo "</div>";
                echo Html::hidden('metademands_id', ['value' => $metademands_id]);
                echo Html::hidden('form_metademands_id', ['value' => $metademands_id]);
                echo "</h2>";
                echo "</div>";

                $basketLines = [];
                foreach ($basketlinesFind as $basketLine) {
                    $basketLines[$basketLine['line']][] = $basketLine;
                }
                foreach ($basketLines as $idline => $fieldlines) {
                    echo "<table class='tab_cadre_fixehov' style='border: 3px #CCC solid;'>";
                    self::retrieveDatasByType($idline, $fieldlines, $line);
                    echo "</table>";
                }

                echo "<div class=\"row\">";
                echo "<div class=\"bt-feature col-md-12 \">";
                echo Html::submit(__('Previous'), ['name' => 'clean_form', 'class' => 'btn btn-primary']);


                echo "<span style='float:right'>";
                $title = "<i class='fas fa-shopping-basket'></i> " . _sx('button', 'Send order', 'metademands');

                $current_ticket = $post["current_ticket_id"] = $post["tickets_id"];
                echo Html::submit($title, ['name' => 'send_order',
                    'form' => '',
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
    public static function retrieveDatasByType($idline, $values, $fields)
    {

        foreach ($fields as $k => $v) {

            $field = new PluginMetademandsField();
            if ($field->getFromDB($v["id"])) {
                $params = PluginMetademandsField::getAllParamsFromField($field);
                $v = array_merge($v, $params);
            }

            //hide blocks
            if ($v['type'] == 'informations' || $v['type'] == 'title-block' || $v['type'] == 'title') {
                continue;
            }


            if (isset($v['is_basket']) && $v['is_basket'] == 0) {
                continue;
            }

            echo "<tr class='tab_bg_1'>";

            echo "<td>";

            if (empty($label = PluginMetademandsField::displayField($v['id'], 'name'))) {
                $label = $v['name'];
                echo $label;
            }

            if ($v['type'] == "date_interval") {
                if (empty($label2 = PluginMetademandsField::displayField($v['id'], 'label2'))) {
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

                    echo PluginMetademandsField::getFieldInput([], $v, true, 0, $idline, false, "");
                    if ($v['type'] == "date_interval" || $v['type'] == "datetime_interval") {
                        if (isset($value['value2'])) {
                            $v['value'] = $value['value2'];
                        }
                        $v['id'] = $v['id'] . "-2";
                        echo PluginMetademandsField::getFieldInput([], $v, true, 0, $idline, false, "");
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
        echo "<i class='fas fa-save' data-hasqtip='0' aria-hidden='true'></i>";
        echo "</button>";
        echo "</td>";
        echo "<td class='center'>";
        echo "<button type='submit' class='submit btn btn-danger' name='delete_basket_line' value='$idline' title='"
            . _sx('button', 'Delete this line', 'metademands') . "'>";
        echo "<i class='fas fa-trash' data-hasqtip='0' aria-hidden='true'></i>";
        echo "</button>";
        echo "</td>";
        echo "</tr>";
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

        $query = "SELECT MAX(`line`)
                FROM `" . $this->getTable() . "`
                WHERE `plugin_metademands_metademands_id` = $plugin_metademands_metademands_id 
                AND `users_id` = " . Session::getLoginUserID() . "";
        $result = $DB->query($query);

        $line = $DB->result($result, 0, 0) + 1;

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
                    $newvalue = is_array($value) ? PluginMetademandsFieldParameter::_serialize($value) : $value;
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
