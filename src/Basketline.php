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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Migration;
use Session;
use Toolbox;

/**
 * Class Basketline
 */
class Basketline extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';


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
                        UNIQUE KEY `unicity` (`plugin_metademands_metademands_id`,`plugin_metademands_fields_id`,`line`,`name`,`users_id`),
                        KEY `users_id` (`users_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

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
    public static function displayBasketSummary($metademands_id, $line = [], $post = [])
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

                $target = Toolbox::getItemTypeFormURL(Wizard::class);

                $clear_basket_html = Html::getSimpleForm(
                    $target,
                    'clear_basket',
                    _sx('button', 'Clear the basket', 'metademands'),
                    [
                        'metademands_id' => $metademands_id,
                    ],
                    'ti-trash',
                    "class='btn btn-primary'",
                );

                $basketLines = [];
                foreach ($basketlinesFind as $basketLine) {
                    $basketLines[$basketLine['line']][] = $basketLine;
                }

                ob_start();
                foreach ($basketLines as $idline => $fieldlines) {
                    self::retrieveDatasByType($metademands_id, $idline, $fieldlines, $line);
                }
                $lines_html = ob_get_clean();

                $previous_html = Html::getSimpleForm(
                    $target,
                    'clean_form',
                    __('Previous'),
                    [
                        'metademands_id' => $metademands_id,
                        'step' => Metademand::STEP_SHOW,
                    ],
                    '',
                    "class='btn btn-primary'",
                );

                //                $title = _sx('button', 'Send order', 'metademands');
                $title = _sx('button', 'Save & Post', 'metademands');
                $current_ticket = $post["current_ticket_id"] = $post["tickets_id"];
                $submit_order_html = Html::submit($title, ['name' => 'send_order',
                    'form' => '',
                    'icon' => 'ti ti-shopping-bag',
                    'id' => 'submitOrder',
                    'class' => 'btn btn-success right']);

                $paramUrl = "";
                $meta_validated = false;
                if ($current_ticket > 0 && !$meta_validated) {
                    $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
                }
                $meta_id = $post['metademands_id'];
                $post_json = json_encode($post, JSON_HEX_TAG | JSON_HEX_AMP);

                echo TemplateRenderer::getInstance()->render('@metademands/forms/basketline_summary.html.twig', [
                    'title_color'       => $title_color,
                    'clear_basket_html' => $clear_basket_html,
                    'hidden_meta'       => Html::hidden('metademands_id', ['value' => $metademands_id]),
                    'hidden_form_meta'  => Html::hidden('form_metademands_id', ['value' => $metademands_id]),
                    'lines_html'        => $lines_html,
                    'previous_html'     => $previous_html,
                    'submit_order_html' => $submit_order_html,
                    'meta_id'           => $meta_id,
                    'webdir'            => PLUGIN_METADEMANDS_WEBDIR,
                    'post_json'         => $post_json,
                    'param_url'         => $paramUrl,
                ]);
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

        $rows = [];

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

            // Row identifiers must be captured before the inner loop mutates $v['id'].
            $id_field = 'field_basket_' . $idline . $v["id"];
            $field_id = $v['id'];

            // Faithful behavior: label is only rendered when there is no field translation.
            $label_text = '';
            if (empty($label = Field::displayField($v['id'], 'name'))) {
                $label = $v['name'];
                $label_text = $label;
            }

            $label2_text = null;
            if ($v['type'] == "date_interval") {
                if (empty($label2 = Field::displayField($v['id'], 'label2'))) {
                    $label2 = $v['label2'];
                }
                $label2_text = Toolbox::stripTags($label2);
            }

            $mandatory = ($v['is_mandatory'] && $v['type'] != 'parent_field');

            ob_start();
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
            $input_html = ob_get_clean();

            $rows[] = [
                'id_field'    => $id_field,
                'field_id'    => $field_id,
                'label_text'  => $label_text,
                'label2_text' => $label2_text,
                'mandatory'   => $mandatory,
                'input_html'  => $input_html,
            ];
        }

        $delete_html = Html::getSimpleForm(
            $target,
            'delete_basket_line',
            _sx('button', 'Delete this line', 'metademands'),
            [
                'metademands_id' => $metademands_id,
                'delete_basket_line' => $idline,
            ],
            'ti-trash',
            "class='btn btn-danger'",
        );

        echo TemplateRenderer::getInstance()->render('@metademands/forms/basketline_line.html.twig', [
            'target'           => $target,
            'hidden_meta'      => Html::hidden('metademands_id', ['value' => $metademands_id]),
            'hidden_form_meta' => Html::hidden('form_metademands_id', ['value' => $metademands_id]),
            'rows'             => $rows,
            'idline'           => $idline,
            'delete_html'      => $delete_html,
            'close_form_html'  => Html::closeForm(false),
        ]);
    }


    /**
     * @param $content
     * @param $plugin_metademands_metademands_id
     *
     * @throws \GlpitestSQLError
     */
    public function addToBasket($content, $plugin_metademands_metademands_id)
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
                'value' => isset($values['value']) ? $values['value'] : null,
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
    public function updateFromBasket($input, $line)
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
                // Scope to the current user so a self-service user cannot overwrite another user's basket line (IDOR).
                if (
                    !$this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['form_metademands_id'],
                        'plugin_metademands_fields_id' => $fields_id,
                        'line' => $input['update_basket_line'],
                        'users_id' => Session::getLoginUserID()])
                ) {
                    continue;
                }

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

            // Same IDOR guard: only update the ITIL category line owned by the current user.
            if (
                $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['form_metademands_id'],
                    'name' => "ITILCategory_Metademands",
                    'line' => $input['update_basket_line'],
                    'users_id' => Session::getLoginUserID()])
            ) {
                $this->update(['value' => $input['basket_plugin_servicecatalog_itilcategories_id'],
                    'id' => $this->fields['id']]);
            }
        }


        Session::addMessageAfterRedirect(__("The line has been updated", "metademands"), false, INFO);
    }

    /**
     * @param $input
     */
    public function deleteFromBasket($input)
    {

        $this->deleteByCriteria(['line' => $input['delete_basket_line'],
            'users_id' => Session::getLoginUserID()]);
        Session::addMessageAfterRedirect(__("The line has been deleted", "metademands"), false, INFO);
    }

    /**
     * @param $input
     */
    public function deleteFileFromBasket($input)
    {

        // Scope the lookup to the current user: basket line numbers are small shared integers,
        // so without users_id a self-service user could delete another user's attached file (IDOR).
        if (
            !$this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['metademands_id'],
                'plugin_metademands_fields_id' => $input['plugin_metademands_fields_id'],
                'line' => $input['idline'],
                'users_id' => Session::getLoginUserID()])
        ) {
            return;
        }

        $files = json_decode($this->fields['value'], 1);
        unset($files[$input['id']]);
        $files = json_encode($files);
        $this->update(['plugin_metademands_fields_id' => $input['plugin_metademands_fields_id'],
            'value' => $files,
            'id' => $this->fields['id']]);
    }
}
