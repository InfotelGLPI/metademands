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

use Ajax;
use ChangeTemplate;
use CommonDBChild;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Html;
use ITILCategory;
use Migration;
use ProblemTemplate;
use Search;
use Session;
use CommonGLPI;
use TicketTemplate;
use TicketTemplateMandatoryField;
use TicketTemplatePredefinedField;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class TicketField
 */
class TicketField extends CommonDBChild
{
    public static $itemtype = Metademand::class;
    public static $items_id = 'plugin_metademands_metademands_id';

    //4 => requester
    //71 => requester group
    public static $used_fields = [
        'content',
        'itilcategories_id',
        'type',
        'status',
        'time_to_resolve',
        'itemtype',
        'items_id',
        '_groups_id_requester',
        '_users_id_requester',
        'slas_id',
        4,
        71,
    ];

    public static $rightname = 'plugin_metademands';

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Ticket field', 'Ticket fields', $nb, 'metademands');
    }

    public static function getIcon()
    {
        return "ti ti-filter-down";
    }

    /**
     * @return bool|int
     */
    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

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
                        `num`                               int                             DEFAULT NULL,
                        `value`                             text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `entities_id`                       int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `is_recursive`                      int          NOT NULL           DEFAULT '0',
                        `is_mandatory`                      int          NOT NULL           DEFAULT '0',
                        `is_deletable`                      int          NOT NULL           DEFAULT '1',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL           DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `entities_id` (`entities_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.3.0
        if (!isIndex($table, "entities_id")) {
            $migration->addKey($table, "entities_id");
        }
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * Display tab for each users
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == Metademand::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(2),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()],
                    ),
                );
            }
            return self::getTypeName(2);
        }
        return '';
    }

    /**
     * Display content for each users
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == Metademand::class) {
            $field->showFromMetademand($item);
        }
        return true;
    }

    /**
     * @param $id
     **/
    public static function showAvailableTags($id)
    {
        $self = new self();
        $tags = $self->getTags($id);

        // Render through Twig so field names ($tag / $values, stored raw in the
        // database) are auto-escaped instead of echoed into HTML verbatim
        // (stored XSS defense).
        $rows = [];
        foreach ($tags as $tag => $values) {
            $rows[] = ['tag' => $tag, 'label' => $values];
        }

        TemplateRenderer::getInstance()->display('@metademands/available_tags.html.twig', [
            'tags' => $rows,
        ]);
    }

    /** Display fields Tags available for the metademand $id
     *
     * @param $id
     **/
    public function getTags($id)
    {
        $metafield = new Field();
        $fields = $metafield->find(['plugin_metademands_metademands_id' => $id]);
        $res = [];
        foreach ($fields as $field) {
            if ($field['type'] == 'title'
                || $field['type'] == 'title-block'
                || $field['type'] == 'informations'
                || $field['type'] == 'upload'
                || $field['type'] == 'basket'
                || $field['type'] == 'signature') {
                continue;
            }
            $res[$field['id']] = $field['name'];
            if ($field['type'] == 'dropdown_object' && $field['item'] == User::getType()) {
                $res[$field['id'] . ".login"] = $field['name'] . " : " . __('Login');
                $res[$field['id'] . ".name"] = $field['name'] . " : " . __('Name');
                $res[$field['id'] . ".firstname"] = $field['name'] . " : " . __('First name');
                $res[$field['id'] . ".email"] = $field['name'] . " : " . _n('Email', 'Emails', 1);
            }
        }

        return $res;
    }

    /**
     * Print the field form
     *
     * @param $item
     *
     * @return bool (display)
     * @throws \GlpitestSQLError
     */
    public function showFromMetademand($item)
    {
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }

        $meta = new Metademand();
        $canedit = $meta->can($item->fields['id'], UPDATE);

        $meta->getFromDB($item->fields['id']);
        $object = $meta->fields['object_to_create'];

        if ($meta->fields['object_to_create'] == 'Ticket') {
            $tt = new TicketTemplate();
        } elseif ($meta->fields['object_to_create'] == 'Problem') {
            $tt = new ProblemTemplate();
        } elseif ($meta->fields['object_to_create'] == 'Change') {
            $tt = new ChangeTemplate();
        } else {
            $tt = new ChangeTemplate();
        }

        $ticketfield_data = $this->find(['plugin_metademands_metademands_id' => $item->fields['id']]);
        $searchOption = Search::getOptions($object);

        $used_fields = $this->getPredefinedFields($item->fields['id'], true);
        $fields = $tt->getAllowedFieldsNames(true, isset($used_fields['itemtype']));

        $cats = json_decode($item->fields['itilcategories_id'], true);

        if (is_array($cats)) {
            $cats = reset($cats);
        }

        $rand = mt_rand();
        $template_link = '';
        $submit_html = '';
        $hidden_html = '';
        $tags_modal_html = '';
        $close_form_html = '';

        if ($canedit) {
            $ticket = new $object();
            // Beware: $tt is replaced here by the template actually in use, and that is the
            // instance forwarded to listFields() below. A user without UPDATE therefore gets
            // the mandatory marks of an empty template, which is the legacy behaviour.
            $tt = $ticket->getITILTemplateToUse(0, $meta->fields["type"], $cats, $item->fields['entities_id']);
            $template_link = $tt->getLink();

            $submit_html = Html::submit(
                __('Synchronise with ticket template', 'metademands'),
                ['name' => 'template_sync', 'class' => 'btn btn-primary'],
            );
            foreach ($item->fields as $name => $value) {
                $hidden_html .= Html::hidden($name, ['value' => $value]);
            }
            $tags_modal_html = Ajax::createIframeModalWindow(
                'tags',
                PLUGIN_METADEMANDS_WEBDIR . "/front/tags.php?metademands_id=" . $item->fields['id'],
                [
                    'title' => __('Show list of available tags'),
                    'display' => false,
                ],
            );
            $close_form_html = Html::closeForm(false);
        }

        echo TemplateRenderer::getInstance()->render('@metademands/ticketfield_sync.html.twig', [
            'canedit' => $canedit,
            'form_action' => Toolbox::getItemTypeFormURL(__CLASS__),
            'template_link' => $template_link,
            'submit_html' => $submit_html,
            'hidden_html' => $hidden_html,
            'tags_modal_html' => $tags_modal_html,
            'close_form_html' => $close_form_html,
            'viewticketchild_id' => 'viewticketchild' . $item->fields['id'] . $rand,
        ]);

        $this->listFields($object, $ticketfield_data, $fields, $searchOption, $canedit, $tt, $item->fields['id'], $rand);
    }

    /**
     * Print the field form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return bool (display)
     * @throws \GlpitestSQLError
     */
    public function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            $this->check(-1, UPDATE);
            $this->getEmpty();
        }

        $meta = new Metademand();
        $meta->getFromDB($this->fields["plugin_metademands_metademands_id"]);
        $object = $meta->fields['object_to_create'];
        $searchOption = Search::getOptions($object);
        $field_name = $searchOption[$this->fields['num']]['name'] ?? '';

        $used_fields = $this->getPredefinedFields($this->fields["plugin_metademands_metademands_id"], true);
        $itemtype_used = $used_fields['itemtype'] ?? '';

        ob_start();
        $this->showFormHeader(['colspan' => 2]);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo htmlspecialchars($field_name);
        echo Html::hidden('entities_id', ['value' => $this->fields["entities_id"]]);
        echo Html::hidden('is_recursive', ['value' => $this->fields["is_recursive"]]);
        echo "</td>";
        echo "<td>" . __('Value') . "</td>";
        echo "<td>";
        echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";
        Ajax::updateItem(
            "show_massiveaction_field",
            PLUGIN_METADEMANDS_WEBDIR . "/ajax/dropdownMassiveActionField.php",
            [
                'id_field'       => $this->fields["num"],
                'value'          => $this->fields["value"],
                'name'           => 'value',
                'itemtype'       => $object,
                'datatype'       => "text",
                'itemtype_used'  => $itemtype_used,
                'relative_dates' => 1,
            ],
        );
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons(['colspan' => 2, 'candel' => $this->fields["is_deletable"]]);
        $form_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/ticketfield_form.html.twig', [
            'modal_id'   => 'modal_ticketfield_' . $ID . '_' . mt_rand(),
            'field_name' => $field_name,
            'form_html'  => $form_html,
        ]);

        return true;
    }

    /**
     * @param $ticketfield_data
     * @param $fields
     * @param $searchOption
     * @param $canedit
     * @param $tt
     */
    private function listFields($object, $ticketfield_data, $fields, $searchOption, $canedit, $tt, $meta_id = 0, $rand = null)
    {
        global $CFG_GLPI;

        if ($rand === null) {
            $rand = mt_rand();
        }

        $obj = new $object();

        $display_options = [
            'comments' => true,
            'html' => true,
        ];

        $has_rows = count($ticketfield_data) && count($fields);
        $container = 'mass' . __CLASS__ . $rand;

        $open_form_html = '';
        $ma_top_html = '';
        $ma_bottom_html = '';
        $close_form_html = '';
        $check_all_html = '';
        $scripts_html = '';
        $rows = [];

        if ($has_rows) {
            $massiveactionparams = ['item' => __CLASS__,
                'container' => $container,
                'display' => false,
            ];
            if ($canedit) {
                $open_form_html = Html::getOpenMassiveActionsForm($container);
                $ma_top_html = Html::showMassiveActions($massiveactionparams);
                $check_all_html = Html::getCheckAllAsCheckbox($container);
            }

            Session::initNavigateListItems($this->getType(), self::getTypeName(2));

            $fieldnames = $tt->getAllowedFields(true);
            foreach ($ticketfield_data as $id => $value) {
                if (in_array($searchOption[$value['num']]['linkfield'], self::$used_fields)
                    || in_array($value['num'], self::$used_fields)) {
                    continue;
                }
                Session::addToNavigateListItems($this->getType(), $id);

                $edit_function = 'viewEditTicketField' . $id . $rand;
                $update_js = Ajax::updateItemJsCode(
                    "viewticketchild" . $meta_id . $rand,
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    ['type' => __CLASS__,
                        'parenttype' => Metademand::class,
                        Metademand::getForeignKeyField() => $meta_id,
                        'id' => $id,
                    ],
                    "",
                    false,
                );
                $scripts_html .= "<script type='text/javascript'>\n"
                    . "function " . $edit_function . "() {\n"
                    . $update_js . ";\n}\n"
                    . "</script>\n";

                $display_datas = [$searchOption[$value['num']]['field'] => $value['value']];

                $rows[] = [
                    'checkbox_html' => $canedit ? Html::getMassiveActionCheckBox(__CLASS__, $id) : '',
                    'edit_function' => $edit_function,
                    'label' => $fields[$value['num']],
                    'mandatory_mark' => $tt->getMandatoryMark($fieldnames[$value['num']]),
                    'value_html' => $obj->getValueToDisplay(
                        $searchOption[$value['num']],
                        $display_datas,
                        $display_options,
                    ),
                ];
            }

            if ($canedit) {
                // Built after the rows on purpose: showMassiveActions() empties
                // $_SESSION['glpimassiveactionselected'] when it is not the top one, and the
                // row checkboxes read that selection to restore their checked state.
                $massiveactionparams['ontop'] = false;
                $ma_bottom_html = Html::showMassiveActions($massiveactionparams);
                $close_form_html = Html::closeForm(false);
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/ticketfield_list.html.twig', [
            'has_rows' => $has_rows,
            'canedit' => $canedit,
            'title' => self::getTypeName(2),
            'open_form_html' => $open_form_html,
            'ma_top_html' => $ma_top_html,
            'ma_bottom_html' => $ma_bottom_html,
            'close_form_html' => $close_form_html,
            'check_all_html' => $check_all_html,
            'scripts_html' => $scripts_html,
            'rows' => $rows,
        ]);
    }

    /**
     * Get predefined fields for a template
     *
     * @param $ID
     * @param $withtypeandcategory bool with type and category
     *
     * @return array array of predefined fields
     **@throws \GlpitestSQLError
     * @since version 0.83
     *
     */
    public function getPredefinedFields($ID, $withtypeandcategory = false)
    {
        global $DB;

        $meta = new Metademand();
        $meta->getFromDB($ID);

        if ($meta->fields['object_to_create'] == 'Ticket') {
            $tt = new TicketTemplate();
        } elseif ($meta->fields['object_to_create'] == 'Problem') {
            $tt = new ProblemTemplate();
        } elseif ($meta->fields['object_to_create'] == 'Change') {
            $tt = new ChangeTemplate();
        } else {
            $tt = new ChangeTemplate();
        }

        $sql = $DB->request([
            'SELECT'    => '*',
            'FROM'      => $this->getTable(),
            'WHERE'     => [
                self::$items_id  => $ID,
            ],
            'ORDERBY'   => 'id',
        ]);

        $allowed_fields = $tt->getAllowedFields($withtypeandcategory, true);
        $fields = [];
        if (count($sql) > 0) {
            foreach ($sql as $rule) {
                if (isset($allowed_fields[$rule['num']])) {
                    $fields[$allowed_fields[$rule['num']]] = $rule['value'];
                }
            }
        }

        return $fields;
    }

    /**
     * @param $field_id
     * @param $name
     * @param $value
     */
    //   static function getSpecificTicketFields($field_id, $name, $value) {
    //
    //      $ticket = new \Ticket();
    //
    //      switch ($name) {
    //         case '_users_id_requester':
    //            $params = ['name'  => 'ticketfield[' . $field_id . ']',
    //                       'value' => $value,
    //                       'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER)];
    //
    //            User::dropdown($params);
    //            break;
    //         case '_groups_id_requester':
    //            Dropdown::show('Group', ['name'      => 'ticketfield[' . $field_id . ']',
    //                                     'value'     => $value,
    //                                     'entity'    => $_SESSION['glpiactive_entity'],
    //                                     'condition' => ['is_watcher' => 1]]);
    //            break;
    //         case '_users_id_observer':
    //            $params = ['name'  => 'ticketfield[' . $field_id . ']',
    //                       'value' => $value,
    //                       'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER)];
    //
    //            User::dropdown($params);
    //            break;
    //         case '_groups_id_observer':
    //            Dropdown::show('Group', ['name'      => 'ticketfield[' . $field_id . ']',
    //                                     'value'     => $value,
    //                                     'entity'    => $_SESSION['glpiactive_entity'],
    //                                     'condition' => ['is_requester' => 1]]);
    //            break;
    //         case '_users_id_assign':
    //            $params = ['name'  => 'ticketfield[' . $field_id . ']',
    //                       'value' => $value,
    //                       'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN)];
    //
    //            User::dropdown($params);
    //            break;
    //         case '_groups_id_assign':
    //            Dropdown::show('Group', ['name'      => 'ticketfield[' . $field_id . ']',
    //                                     'value'     => $value,
    //                                     'entity'    => $_SESSION['glpiactive_entity'],
    //                                     'condition' => ['is_assign' => 1]]);
    //            break;
    //         case 'status':
    //            $opt = ['name'  => 'ticketfield[' . $field_id . ']',
    //                    'value' => $value];
    //            Ticket::dropdownStatus($opt);
    //            break;
    //         case 'itemtype':
    //            $dev_user_id  = 0;
    //            $dev_itemtype = 0;
    //            $dev_items_id = $value;
    //            Ticket::dropdownAllDevices('ticketfield[' . $field_id . ']', $dev_itemtype, $dev_items_id,
    //                                       1, $dev_user_id, $_SESSION['glpiactive_entity']);
    //            break;
    //         case 'actiontime':
    //            Dropdown::showTimeStamp('ticketfield[' . $field_id . ']', ['addfirstminutes' => true,
    //                                                                       'value'           => $value]);
    //            break;
    //         case 'requesttypes_id';
    //            Dropdown::show('RequestType', ['name' => 'ticketfield[' . $field_id . ']', 'value' => $value]);
    //            break;
    //      }
    //
    //   }

    /**
     * @param $input
     *
     * @return bool
     */
    public static function updateMandatoryTicketFields($input)
    {
        if (isset($input['itilcategories_id']) && isset($input['entities_id']) && isset($input['id'])) {
            $meta = new Metademand();
            $meta->getFromDB($input['id']);
            $type = $meta->getField('type');
            // Add mandatory ticket fields
            self::addTemplateFields($input['id'], $input['itilcategories_id'], $type, $input['entities_id']);
            // Add predefined ticket fields
            self::addTemplateFields(
                $input['id'],
                $input['itilcategories_id'],
                $type,
                $input['entities_id'],
                'predefined',
            );
        }

        return true;
    }

    /**
     * @param \ITILCategory $itilcategory
     */
    public static function update_category_mandatoryFields(ITILCategory $itilcategory)
    {
        $categid = 0;
        if (isset($itilcategory->fields['id'])) {
            $categid = $itilcategory->fields['id'];
        }

        $metademands = new Metademand();
        $metademands_data = $metademands->find([
            'entities_id' => $_SESSION['glpiactive_entity'],
            'itilcategories_id' => $categid,
        ]);
        foreach ($metademands_data as $id => $value) {
            self::addTemplateFields($id, $categid, $value['type'], $value['entities_id']);
        }
    }

    /**
     * @param \ITILCategory $itilcategory
     */
    public static function update_category_predefinedFields(ITILCategory $itilcategory)
    {
        $categid = 0;
        if (isset($itilcategory->fields['id'])) {
            $categid = $itilcategory->fields['id'];
        }
        $metademands = new Metademand();
        $metademands_data = $metademands->find([
            'entities_id' => $_SESSION['glpiactive_entity'],
            'itilcategories_id' => $categid,
        ]);
        foreach ($metademands_data as $id => $value) {
            self::addTemplateFields($id, $categid, $value['type'], $value['entities_id'], 'predefined');
        }
    }

    /**
     * @param \TicketTemplateMandatoryField $ttp
     */
    public static function post_add_mandatoryField(TicketTemplateMandatoryField $ttp)
    {
        self::addFieldsFromTemplate($ttp);
    }

    /**
     * @param \TicketTemplatePredefinedField $ttp
     */
    public static function post_add_predefinedField(TicketTemplatePredefinedField $ttp)
    {
        self::addFieldsFromTemplate($ttp);
    }

    /**
     * @param \TicketTemplateMandatoryField $ttp
     */
    public static function post_delete_mandatoryField(TicketTemplateMandatoryField $ttp)
    {
        self::deleteFieldsFromTemplate($ttp);
    }

    /**
     * @param \TicketTemplatePredefinedField $ttp
     */
    public static function post_delete_predefinedField(TicketTemplatePredefinedField $ttp)
    {
        self::deleteFieldsFromTemplate($ttp, 'predefined');
    }

    /**
     * @param $ttp
     */
    public static function addFieldsFromTemplate($ttp)
    {
        $ticketField = new TicketField();
        $metademands = new Metademand();

        $metademands_data = $metademands->find();
        foreach ($metademands_data as $id => $value) {
            // Search for the metademand template
            $obj = $value['object_to_create'];
            if ($item = getItemForItemtype($obj)) {
                $ticket = new $obj();
                if (is_array($value['itilcategories_id'])
                    && count($value['itilcategories_id']) != 1) {
                    continue;
                }
                $meta_tt = $ticket->getITILTemplateToUse(
                    0,
                    $value['type'],
                    $value['itilcategories_id'],
                    $value['entities_id'],
                );
                $fieldsname = $meta_tt->getAllowedFields(true);

                // Template of metademand found
                if ($meta_tt->fields['id'] == $ttp->fields['tickettemplates_id']) {
                    if (!in_array($fieldsname[$ttp->fields['num']], self::$used_fields)
                        && $ttp->fields['num'] != -2) {
                        $used = false;
                        $fields_data = $ticketField->find(['plugin_metademands_metademands_id' => $id]);
                        foreach ($fields_data as $fields_value) {
                            if ($fields_value['num'] == $ttp->fields['num']) {
                                $used = $fields_value['id'];
                                break;
                            }
                        }

                        $exception = false;

                        switch ($fieldsname[$ttp->fields['num']]) {
                            case 'status':
                                $default_value = \Ticket::INCOMING;
                                break;
                            case 'priority':
                            case 'urgency':
                            case 'impact':
                                $default_value = 3;
                                break;
                            case '_tasktemplates_id' :
                                $exception = true;
                                break;
                            default:
                                $default_value = 0;
                                break;
                        }

                        if (isset($meta_tt->predefined[$fieldsname[$ttp->fields['num']]])) {
                            $default_value = $meta_tt->predefined[$fieldsname[$ttp->fields['num']]];
                        }
                        if (!$exception) {
                            if (!$used) {
                                $ticketField->add([
                                    'num' => $ttp->fields['num'],
                                    'value' => $default_value,
                                    'is_deletable' => 0,
                                    'type' => $value['type'],
                                    'is_mandatory' => 1,
                                    'entities_id' => $value['entities_id'],
                                    'plugin_metademands_metademands_id' => $id,
                                ]);
                            } else {
                                $ticketField->update(['id' => $used, 'value' => $default_value]);
                            }
                        } else {
                            $ticketField->add([
                                'value' => $ttp->fields['value'],
                                'num' => $ttp->fields['num'],
                                'is_deletable' => 0,
                                'is_mandatory' => 1,
                                'type' => $value['type'],
                                'entities_id' => $value['entities_id'],
                                'plugin_metademands_metademands_id' => $id,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param        $ttp
     * @param string $templatetype
     */
    public static function deleteFieldsFromTemplate($ttp, $templatetype = 'mandatory')
    {
        $ticketField = new TicketField();
        $metademands = new Metademand();

        $metademands_data = $metademands->find();
        foreach ($metademands_data as $id => $value) {
            $obj = $value['object_to_create'];
            $ticket = new $obj();
            $tt = $ticket->getITILTemplateToUse(0, $value['type'], $value['itilcategories_id'], $value['entities_id']);

            if ($tt->fields['id'] == $ttp->fields['tickettemplates_id']) {
                $fieldsname = $tt->getAllowedFields(true);

                $used = false;
                if ($templatetype == 'mandatory' && isset($tt->predefined[$fieldsname[$ttp->fields['num']]])) {
                    $used = true;
                }
                if ($templatetype == 'predefined' && isset($tt->mandatory[$fieldsname[$ttp->fields['num']]])) {
                    $used = true;
                }

                if (!$used) {
                    $ticketField->deleteByCriteria(
                        ['num' => $ttp->fields['num'], 'plugin_metademands_metademands_id' => $id],
                    );
                }
            }
        }
    }

    /**
     * @param        $metademands_id
     * @param        $categid
     * @param        $type
     * @param        $entity
     * @param string $templatetype
     */
    public static function addTemplateFields($metademands_id, $categid, $type, $entity, $templatetype = 'mandatory')
    {
        $meta = new Metademand();
        $meta->getFromDB($metademands_id);
        $obj = $meta->fields['object_to_create'];

        $ticketField = new self();
        $fields_data = $ticketField->find(['plugin_metademands_metademands_id' => $metademands_id]);

        if ($item = getItemForItemtype($obj)) {
            $ticket = new $obj();
            $tt = $ticket->getITILTemplateToUse(0, $type, $categid, $entity);

            $fieldnames = $tt->getAllowedFields(true);

            $fieldnames = array_flip($fieldnames);

            // Get template type to add
            $templateToAdd = $tt->mandatory;
            switch ($templatetype) {
                case 'predefined':
                    $templateToAdd = $tt->predefined;
                    break;
            }

            if (count($templateToAdd)) {
                foreach ($templateToAdd as $key => $val) {
                    if (isset($fieldnames[$key])) {
                        $num = $fieldnames[$key];
                        if (!in_array($key, self::$used_fields) && $num != -2) {
                            $used = false;
                            foreach ($fields_data as $fields_value) {
                                if ($fields_value['num'] == $num) {
                                    $used = $fields_value['id'];
                                    break;
                                }
                            }
                            $exception = false;
                            switch ($key) {
                                case 'status':
                                    $default_value = \Ticket::INCOMING;
                                    break;
                                case 'priority':
                                    $default_value = 3;
                                    break;
                                case '_tasktemplates_id' :
                                    $exception = true;
                                    break;
                                default:
                                    $default_value = 0;
                                    break;
                            }

                            if (isset($tt->predefined[$key])) {
                                $default_value = $tt->predefined[$key];
                            }
                            //               $default_value = json_encode($default_value);
                            if (!$exception) {
                                if (!$used) {
                                    $ticketField->add([
                                        'value' => $default_value,
                                        'num' => $num,
                                        'is_deletable' => 0,
                                        'is_mandatory' => 1,
                                        'entities_id' => $entity,
                                        'plugin_metademands_metademands_id' => $metademands_id,
                                    ]);
                                } else {
                                    if (!empty($default_value)) {
                                        $ticketField->update(['id' => $used, 'value' => $default_value]);
                                    }
                                }
                            } else {
                                $ticketField->deleteByCriteria(
                                    ['num' => $num, 'plugin_metademands_metademands_id' => $metademands_id],
                                );
                                foreach ($tt->predefined[$key] as $key => $val) {
                                    $ticketField->add([
                                        'value' => $val,
                                        'num' => $num,
                                        'is_deletable' => 0,
                                        'is_mandatory' => 1,
                                        'entities_id' => $entity,
                                        'plugin_metademands_metademands_id' => $metademands_id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        if (!self::canCreate()) {
            $forbidden[] = 'delete';
            $forbidden[] = 'purge';
            $forbidden[] = 'restore';
        }

        $forbidden[] = 'update';
        $forbidden[] = 'clone';
        $forbidden[] = 'add_transfer_list';
        return $forbidden;
    }

}
