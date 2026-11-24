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

use ChangeTemplate;
use CommonDBChild;
use CommonDBTM;
use CommonTreeDropdown;
use DBConnection;
use DbUtils;
use GlpiPlugin\Metademands\Fields\Time;
use Plugin;
use Html;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
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

/**
 * Class TicketField
 */
class TicketField extends CommonDBChild
{
    public static string $itemtype = Metademand::class;
    public static string $items_id = 'plugin_metademands_metademands_id';

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

    public static string $rightname = 'plugin_metademands';

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
                || in_array($field['type'], ['informations', 'forminformation'])
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
        global $CFG_GLPI;

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

        $rand = mt_rand();
        $template_link = '';

        // The template of the metademand, the same whoever looks at it (issue #299): the
        // one of its first category having one, otherwise the one of its entity
        $tt = self::getMetademandTemplate($meta) ?? $tt;
        if (!$tt->isNewItem()) {
            $template_link = $tt->getLink();
        }

        $viewticketchild_id = 'viewticketchild' . $item->fields['id'] . $rand;
        echo TemplateRenderer::getInstance()->render('@metademands/ticketfield_sync.html.twig', [
            'canedit' => $canedit,
            'form_action' => Toolbox::getItemTypeFormURL(__CLASS__),
            'template_link' => $template_link,
            'template_class' => $tt::class,
            'templates_id' => $tt->isNewItem() ? 0 : $tt->getID(),
            'item_fields' => $item->fields,
            'tags_url' => PLUGIN_METADEMANDS_WEBDIR . "/front/tags.php?metademands_id=" . $item->fields['id'],
            'viewticketchild_id' => $viewticketchild_id,
            // A field added by hand, as a destination of a core form: the field form, empty
            'add_url' => $CFG_GLPI['root_doc'] . '/ajax/viewsubitem.php',
            'add_params' => [
                'type' => __CLASS__,
                'parenttype' => Metademand::class,
                Metademand::getForeignKeyField() => $item->fields['id'],
                'id' => -1,
            ],
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

        $is_new = $ID <= 0;
        if (!$is_new) {
            $this->check($ID, READ);
        } else {
            // A field added by hand to the metademand of the tab
            $parent_id = isset($options['parent']) && $options['parent'] instanceof Metademand
                ? $options['parent']->getID()
                : 0;
            $parent_input = ['plugin_metademands_metademands_id' => $parent_id];
            $this->check(-1, CREATE, $parent_input);
            $this->getEmpty();
        }

        $metademands_id = (int) ($this->fields["plugin_metademands_metademands_id"] ?? 0);
        if ($metademands_id === 0
            && isset($options['parent'])
            && $options['parent'] instanceof Metademand) {
            $metademands_id = $options['parent']->getID();
            $this->fields["plugin_metademands_metademands_id"] = $metademands_id;
        }

        $meta = new Metademand();
        $meta->getFromDB($metademands_id);
        $object = $meta->fields['object_to_create'];
        $searchOption = Search::getOptions($object);
        $field_name = $searchOption[$this->fields['num']]['name'] ?? '';
        if ($is_new) {
            $this->fields['entities_id']  = $meta->fields['entities_id'];
            $this->fields['is_recursive'] = $meta->fields['is_recursive'];
        }

        $used_fields = $this->getPredefinedFields($metademands_id, true);
        $itemtype_used = $used_fields['itemtype'] ?? '';

        // The form header and buttons are printed by the template, around the field row.
        TemplateRenderer::getInstance()->display('@metademands/ticketfield_form.html.twig', [
            'modal_id'       => 'modal_ticketfield_' . $ID . '_' . mt_rand(),
            'item'           => $this,
            'field_name'     => $field_name,
            'entities_id'    => $this->fields["entities_id"],
            'is_recursive'   => $this->fields["is_recursive"],
            'metademands_id' => $metademands_id,
            'num'            => $this->fields["num"],
            // New field: the fields that may be added, the value following the choice
            'field_choices'  => $is_new ? self::getAvailableFields($meta) : null,
            'rand'           => mt_rand(),
            'value_url'      => PLUGIN_METADEMANDS_WEBDIR . "/ajax/dropdownMassiveActionField.php",
            'value_params'   => [
                'id_field'       => $this->fields["num"],
                'value'          => $this->fields["value"],
                'name'           => 'value',
                'itemtype'       => $object,
                'datatype'       => "text",
                'itemtype_used'  => $itemtype_used,
                'relative_dates' => 1,
            ],
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

        // No comments: their tooltips are inline scripts, which the template's |safe_html drops
        $display_options = [
            'comments' => false,
            'html' => true,
        ];

        $has_rows = count($ticketfield_data) && count($fields);
        // No backslash: the massive action bar looks the checkboxes up with a jQuery
        // selector on this id, where a namespace separator reads as an escape
        $container = 'mass' . str_replace('\\', '', __CLASS__) . $rand;

        $rows = [];

        if ($has_rows) {
            Session::initNavigateListItems($this->getType(), self::getTypeName(2));

            $fieldnames = $tt->getAllowedFields(true);
            foreach ($ticketfield_data as $id => $value) {
                if (in_array($searchOption[$value['num']]['linkfield'], self::$used_fields)
                    || in_array($value['num'], self::$used_fields)) {
                    continue;
                }
                Session::addToNavigateListItems($this->getType(), $id);


                $display_datas = [$searchOption[$value['num']]['field'] => $value['value']];

                $rows[] = [
                    'id' => $id,
                    // Loaded into the edit pane by public/scripts/wizard_form.js.
                    'edit_params' => [
                        'type' => __CLASS__,
                        'parenttype' => Metademand::class,
                        Metademand::getForeignKeyField() => $meta_id,
                        'id' => $id,
                    ],
                    'label' => $fields[$value['num']],
                    'is_mandatory' => $tt->isMandatoryField($fieldnames[$value['num']]),
                    'value_html' => $obj->getValueToDisplay(
                        $searchOption[$value['num']],
                        $display_datas,
                        $display_options,
                    ),
                ];
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/ticketfield_list.html.twig', [
            'has_rows' => $has_rows,
            'canedit' => $canedit,
            'title' => self::getTypeName(2),
            'itemtype' => __CLASS__,
            'container' => $container,
            'edit_target' => 'viewticketchild' . $meta_id . $rand,
            'edit_url' => $CFG_GLPI['root_doc'] . '/ajax/viewsubitem.php',
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
     * Synchronise the fields of the object to create by a metademand with its template
     * (getMetademandTemplate()), or with the one chosen in the "Parent ticket fields" tab.
     *
     * @param array $input        the metademand
     * @param int   $templates_id template chosen instead of the one of the metademand
     *
     * @return bool
     */
    public static function updateMandatoryTicketFields($input, int $templates_id = 0)
    {
        if (isset($input['itilcategories_id']) && isset($input['entities_id']) && isset($input['id'])) {
            $meta = new Metademand();
            $meta->getFromDB($input['id']);
            $type = $meta->getField('type');
            // Add mandatory ticket fields
            self::addTemplateFields($input['id'], $input['itilcategories_id'], $type, $input['entities_id'], 'mandatory', $templates_id);
            // Add predefined ticket fields
            self::addTemplateFields(
                $input['id'],
                $input['itilcategories_id'],
                $type,
                $input['entities_id'],
                'predefined',
                $templates_id,
            );
        }

        return true;
    }

    /**
     * Replay the ticket template fields of every meta-demand attached to a category.
     *
     * The mandatory and the predefined fields used to be registered as two separate
     * callbacks on the same 'ITILCategory' key of $PLUGIN_HOOKS, so the second
     * assignment silently dropped the first one and only one of the two ever ran.
     *
     * @param \ITILCategory $itilcategory
     */
    public static function update_category_fields(ITILCategory $itilcategory)
    {
        $categid = 0;
        if (isset($itilcategory->fields['id'])) {
            $categid = $itilcategory->fields['id'];
        }

        // The categories of a metademand are a JSON list: the former search on the bare id
        // never matched it
        $metademands = new Metademand();
        $metademands_data = $metademands->find(['itilcategories_id' => ['LIKE', '%' . (int) $categid . '%']]);
        foreach ($metademands_data as $id => $value) {
            $categories = json_decode((string) $value['itilcategories_id'], true);
            if (!is_array($categories) || !in_array((int) $categid, array_map('intval', $categories), true)) {
                continue;
            }
            self::addTemplateFields($id, $categid, $value['type'], $value['entities_id']);
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

        // Metademands creating tickets only: the id of a template of tickets says nothing of
        // the template of a change or a problem
        $metademands_data = $metademands->find(['object_to_create' => \Ticket::class]);
        foreach ($metademands_data as $id => $value) {
            // Search for the metademand template, the same whoever changes the template
            $meta = new Metademand();
            if ($meta->getFromDB($id) && ($meta_tt = self::getMetademandTemplate($meta))) {
                $fieldsname = $meta_tt->getAllowedFields(true);

                // Template of metademand found
                if ($meta_tt->fields['id'] == $ttp->fields['tickettemplates_id']
                    && isset($fieldsname[$ttp->fields['num']])) {
                    if (!in_array($fieldsname[$ttp->fields['num']], self::$used_fields)
                        && $ttp->fields['num'] != -2) {
                        $used = false;
                        $from_template = false;
                        $fields_data = $ticketField->find(['plugin_metademands_metademands_id' => $id]);
                        foreach ($fields_data as $fields_value) {
                            if ($fields_value['num'] == $ttp->fields['num']) {
                                $used = $fields_value['id'];
                                $from_template = (int) $fields_value['is_deletable'] === 0;
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
                            case '_tasktemplates_id':
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
                            } elseif ($from_template) {
                                // A field added by hand keeps its value
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

        // As addFieldsFromTemplate(): the metademands creating tickets, their own template
        $metademands_data = $metademands->find(['object_to_create' => \Ticket::class]);
        foreach ($metademands_data as $id => $value) {
            $meta = new Metademand();
            if (!$meta->getFromDB($id) || !($tt = self::getMetademandTemplate($meta))) {
                continue;
            }

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
                    // Only the field the template brought: one added by hand stays
                    $ticketField->deleteByCriteria(
                        ['num' => $ttp->fields['num'], 'plugin_metademands_metademands_id' => $id, 'is_deletable' => 0],
                    );
                }
            }
        }
    }

    /**
     * Whether a field of the object to create may be predefined for a metademand: a field of
     * its templates, but those the plugin fills itself (used_fields), and once only (the task
     * templates excepted, of which a ticket may have several).
     */
    public static function isFieldAvailable(Metademand $meta, int $num): bool
    {
        $tt = self::getMetademandTemplate($meta);
        if ($tt === null || $num === -2) {
            return false;
        }
        $name = $tt->getAllowedFields(true, true)[$num] ?? null;
        if (!is_string($name)
            || in_array($name, self::$used_fields, true)
            || in_array($num, self::$used_fields, true)) {
            return false;
        }

        return $name === '_tasktemplates_id'
            || countElementsInTable(self::getTable(), [
                'plugin_metademands_metademands_id' => $meta->getID(),
                'num'                               => $num,
            ]) === 0;
    }

    /**
     * Fields of the object to create that may be added by hand to a metademand
     * (isFieldAvailable()), as the destinations of a core form set them: title, urgency,
     * impact, priority, request source, location, assigned group or technician...
     *
     * @return array<int, string> search option => label
     */
    public static function getAvailableFields(Metademand $meta): array
    {
        $tt = self::getMetademandTemplate($meta);
        if ($tt === null) {
            return [];
        }

        $available = [];
        foreach ($tt->getAllowedFieldsNames(true, true) as $num => $label) {
            if (self::isFieldAvailable($meta, (int) $num)) {
                $available[(int) $num] = $label;
            }
        }
        asort($available);

        return $available;
    }

    /**
     * Set a field of the object to create of a metademand from a copy or an import: the field
     * already there (synchronised with the template when the metademand was added) takes the
     * value, otherwise it is added; a field that may not be predefined is left out silently.
     *
     * @param array<string, mixed> $input row of the source, without its id
     */
    public static function copyField(int $metademands_id, array $input): bool
    {
        $meta = new Metademand();
        $num  = (int) ($input['num'] ?? 0);
        if (!$meta->getFromDB($metademands_id)) {
            return false;
        }

        $ticketfield = new self();
        $tt = self::getMetademandTemplate($meta);
        $is_multiple = $tt !== null && ($tt->getAllowedFields(true, true)[$num] ?? null) === '_tasktemplates_id';
        if (!$is_multiple
            && $ticketfield->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id, 'num' => $num])) {
            return $ticketfield->update([
                'id'           => $ticketfield->getID(),
                'value'        => $input['value'] ?? '',
                'is_mandatory' => (int) ($input['is_mandatory'] ?? $ticketfield->fields['is_mandatory']),
                'is_deletable' => (int) ($input['is_deletable'] ?? $ticketfield->fields['is_deletable']),
            ]);
        }
        if (!self::isFieldAvailable($meta, $num)) {
            return false;
        }

        return (bool) $ticketfield->add(['plugin_metademands_metademands_id' => $metademands_id] + $input);
    }

    public function prepareInputForAdd($input)
    {
        // Added by hand, by the synchronisation with a template or by an import: a field of the
        // object to create, once
        $meta = new Metademand();
        if (!$meta->getFromDB((int) ($input['plugin_metademands_metademands_id'] ?? 0))
            || !self::isFieldAvailable($meta, (int) ($input['num'] ?? 0))) {
            Session::addMessageAfterRedirect(
                __('This field cannot be predefined, or it already is', 'metademands'),
                false,
                ERROR,
            );
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * Id of the template the fields of the object to create by a metademand are synchronised
     * with, the same whoever asks: the one of the first of its categories having one for its
     * type, otherwise the one of its entity; 0 when there is none.
     *
     * getITILTemplateToUse() was given the categories as their JSON list, which it read as no
     * category, and then took the template of the profile of the current user (issue #299).
     */
    public static function getMetademandTemplateId(Metademand $meta): int
    {
        $object = (string) ($meta->fields['object_to_create'] ?? '');
        $item   = getItemForItemtype($object);
        if (!$item instanceof \CommonITILObject) {
            return 0;
        }

        // The template of a category depends on the type of a ticket only
        $type = (int) ($meta->fields['type'] ?? 0);
        if (!$item instanceof \Ticket || in_array($type, [\Ticket::INCIDENT_TYPE, \Ticket::DEMAND_TYPE], true)) {
            $field      = $item->getTemplateFieldName($item instanceof \Ticket ? $type : true);
            $categories = json_decode((string) ($meta->fields['itilcategories_id'] ?? ''), true);
            $categories = is_array($categories) ? $categories : [$meta->fields['itilcategories_id'] ?? 0];
            $category   = new ITILCategory();
            foreach ($categories as $categories_id) {
                if ((int) $categories_id > 0
                    && $category->getFromDB((int) $categories_id)
                    && (int) ($category->fields[$field] ?? 0) > 0) {
                    return (int) $category->fields[$field];
                }
            }
        }

        return max(0, (int) \Entity::getUsedConfig(
            strtolower($object) . 'templates_strategy',
            (int) $meta->fields['entities_id'],
            strtolower($object) . 'templates_id',
            0,
        ));
    }

    /**
     * Template the fields of the object to create by a metademand are synchronised with
     * (getMetademandTemplateId()), or the one chosen instead; an empty template, without
     * mandatory nor predefined fields, when there is none.
     *
     * @return \ITILTemplate|null null when the object to create has no templates
     */
    public static function getMetademandTemplate(Metademand $meta, int $templates_id = 0): ?\ITILTemplate
    {
        $item = getItemForItemtype((string) ($meta->fields['object_to_create'] ?? ''));
        if (!$item instanceof \CommonITILObject) {
            return null;
        }
        $tt = getItemForItemtype($item::getTemplateClass());
        if (!$tt instanceof \ITILTemplate) {
            return null;
        }

        $templates_id = $templates_id > 0 ? $templates_id : self::getMetademandTemplateId($meta);
        if ($templates_id <= 0 || !$tt->getFromDBWithData($templates_id, true)) {
            $tt->getEmpty();
        }

        return $tt;
    }

    /**
     * Add the mandatory or predefined fields of the template of the metademand
     * (getMetademandTemplate()), or of the one chosen, to its fields of the object to create.
     * A field added by hand (is_deletable) keeps its value.
     *
     * @param        $metademands_id
     * @param        $categid       not used any more: the template is the one of the metademand
     * @param        $type          not used any more
     * @param        $entity        not used any more: the entity of the metademand
     * @param string $templatetype  'mandatory' or 'predefined'
     * @param int    $templates_id  template chosen instead of the one of the metademand
     */
    public static function addTemplateFields($metademands_id, $categid, $type, $entity, $templatetype = 'mandatory', int $templates_id = 0)
    {
        $meta = new Metademand();
        if (!$meta->getFromDB($metademands_id)) {
            return;
        }
        $entity = $meta->fields['entities_id'];

        $ticketField = new self();
        $fields_data = $ticketField->find(['plugin_metademands_metademands_id' => $metademands_id]);

        if ($tt = self::getMetademandTemplate($meta, $templates_id)) {
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
                            $from_template = false;
                            foreach ($fields_data as $fields_value) {
                                if ($fields_value['num'] == $num) {
                                    $used = $fields_value['id'];
                                    $from_template = (int) $fields_value['is_deletable'] === 0;
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
                                case '_tasktemplates_id':
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
                                } elseif ($from_template && !empty($default_value)) {
                                    // A field added by hand keeps its value
                                    $ticketField->update(['id' => $used, 'value' => $default_value]);
                                }
                            } else {
                                // The task templates of the template, replaced; those added by
                                // hand are kept
                                $ticketField->deleteByCriteria(
                                    ['num' => $num, 'plugin_metademands_metademands_id' => $metademands_id, 'is_deletable' => 0],
                                );
                                foreach ($tt->predefined[$key] ?? [] as $val) {
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
     * GLPI object a value of a field of the object to create points at (user, group, location…),
     * from the search option of that field: its itemtype, null for a value written as is (title,
     * urgency, dates…).
     *
     * @param string $object object to create by the metademand (Ticket, Change, Problem)
     *
     * @return class-string<CommonDBTM>|null
     */
    public static function getValueItemtype(string $object, int $num): ?string
    {
        if (!is_a($object, CommonDBTM::class, true)) {
            return null;
        }
        $table = Search::getOptions($object)[$num]['table'] ?? '';
        if (!is_string($table) || $table === '' || $table === $object::getTable()) {
            return null;
        }
        $itemtype = getItemTypeForTable($table);

        return is_a($itemtype, CommonDBTM::class, true) ? $itemtype : null;
    }

    /**
     * Column an object pointed at by a value is found again by on another GLPI, where its id
     * differs: the login of a user, the complete name of a tree, the name otherwise.
     *
     * @param class-string<CommonDBTM> $itemtype
     */
    private static function getValueNameField(string $itemtype): string
    {
        if (is_a($itemtype, CommonTreeDropdown::class, true)) {
            return 'completename';
        }

        return 'name';
    }

    /**
     * Itemtype and name of the object a value points at, exported with it so that the import
     * finds that object again on another GLPI ([] for a value written as is).
     *
     * @param array<string, mixed> $ticketfield row of the table
     *
     * @return array{value_itemtype?: string, value_name?: string}
     */
    public static function getExportedValueItem(string $object, array $ticketfield): array
    {
        $itemtype = self::getValueItemtype($object, (int) ($ticketfield['num'] ?? 0));
        $value    = $ticketfield['value'] ?? '';
        if ($itemtype === null || !is_numeric($value) || (int) $value <= 0) {
            return [];
        }
        $item = getItemForItemtype($itemtype);
        $name_field = self::getValueNameField($itemtype);
        if (!$item || !$item->getFromDB((int) $value) || (string) ($item->fields[$name_field] ?? '') === '') {
            return [];
        }

        return [
            'value_itemtype' => $itemtype,
            'value_name'     => (string) $item->fields[$name_field],
        ];
    }

    /**
     * Id on this GLPI of the object an imported value points at, found by its name among the
     * objects visible from the entity; 0 when there is none, or several.
     *
     * @param class-string<CommonDBTM> $itemtype
     */
    public static function findValueItem(string $itemtype, string $name, int $entities_id): int
    {
        $item = getItemForItemtype($itemtype);
        if (!$item || $name === '') {
            return 0;
        }
        $criteria = [self::getValueNameField($itemtype) => $name];
        if ($item->isEntityAssign()) {
            $criteria += getEntitiesRestrictCriteria($item::getTable(), '', $entities_id, true);
        }

        return $item->getFromDBByCrit($criteria) ? (int) $item->getID() : 0;
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


    /**
     * Print the widget of a massive-action field (ajax/massiveaction_field.html.twig), picked
     * from the search option: a core helper, or the MassiveActionsFieldsDisplay hook of a plugin.
     *
     * @param class-string<\CommonDBTM> $itemtype
     * @param array<string, mixed>     $search search option of the field
     * @param array<string, mixed>     $input  request parameters (value, itemtype_used, relative_dates…)
     */
    public static function showMassiveActionFieldWidget(string $itemtype, array $search, array $input): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $dbu = new DbUtils();

        if ($search["table"] == $dbu->getTableForItemType($itemtype)) { // field type
            switch ($search["table"] . "." . $search["linkfield"]) {
                case "glpi_tickets.status":
                    \Ticket::dropdownStatus(['name'  => $search["linkfield"],
                        'value' => ($input['value'] ?? '')]);
                    break;

                case "glpi_tickets.items_id":
                    // The endpoint rejected any other itemtype before rendering; this only keeps the
                    // method from listing an arbitrary dropdown when called without that check.
                    if (
                        !empty($input['itemtype_used'])
                        && in_array($input['itemtype_used'], $CFG_GLPI['ticket_types'], true)
                    ) {
                        Dropdown::show($input['itemtype_used'], ['name' => $search["linkfield"], 'value' => ($input['value'] ?? '')]);
                    }
                    break;

                case "glpi_tickets.type":
                    \Ticket::dropdownType($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                    break;

                case "glpi_tickets.priority":
                    \Ticket::dropdownPriority(['name' => $search["linkfield"], 'value' => ($input['value'] ?? '')]);
                    break;

                case "glpi_tickets.impact":
                    \Ticket::dropdownImpact(['name' => $search["linkfield"], 'value' => ($input['value'] ?? '')]);
                    break;

                case "glpi_tickets.urgency":
                    \Ticket::dropdownUrgency(['name' => $search["linkfield"], 'value' => ($input['value'] ?? '')]);
                    break;

                case "glpi_tickets.global_validation":
                    \TicketValidation::dropdownStatus($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                    break;
                default:
                    // Specific plugin Type case
                    $plugdisplay = false;
                    if ($plug = isPluginItemType($itemtype)) {
                        $plugdisplay = Plugin::doOneHook(
                            $plug['plugin'],
                            'MassiveActionsFieldsDisplay',
                            ['itemtype' => $itemtype,
                                'options'  => $search],
                        );
                    }
                    $already_display = false;

                    if (isset($search['datatype'])) {
                        switch ($search['datatype']) {
                            case "date":
                                Html::showDateField($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                                $already_display = true;
                                break;
                            case "time":
                                Time::showTimeField($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                                $already_display = true;
                                break;
                            case "datetime":
                                if (!isset($input['relative_dates']) || !$input['relative_dates']) {
                                    Html::showDateTimeField($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                                    $already_display = true;
                                } else { // For ticket template
                                    Html::showGenericDateTimeSearch(
                                        $search["linkfield"],
                                        ($input['value'] ?? ''),
                                        ['with_time'          => true,
                                            'with_future'
                                                                 => (isset($search['maybefuture'])
                                                                     && $search['maybefuture']),
                                            'with_days'          => false,
                                            'with_specific_date' => false],
                                    );

                                    $already_display = true;
                                }
                                break;

                                //                  case "itemtypename" :
                                //                     if (isset($search['itemtype_list'])) {
                                //                        Dropdown::dropdownTypes($search["linkfield"], ($input['value'] ?? ''), $CFG_GLPI[$search['itemtype_list']]);
                                //                        $already_display = true;
                                //                     }
                                //                     break;

                            case "bool":
                                Dropdown::showYesNo($search["linkfield"], ($input['value'] ?? ''));
                                $already_display = true;
                                break;

                            case "timestamp":
                                Dropdown::showTimeStamp($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                                $already_display = true;
                                break;

                            case "text":
                                Html::textarea(['name'              => $search["linkfield"],
                                    'cols'              => '45',
                                    'rows'              => '5',
                                    'value'             => stripslashes(($input['value'] ?? '')),
                                    'enable_richtext'   => true,
                                    'enable_fileupload' => false]);
                                $already_display = true;
                                break;
                        }
                    }

                    if (!$plugdisplay && !$already_display) {
                        echo Html::input($search["linkfield"], ['value' => stripslashes(($input['value'] ?? '')), 'size' => 40]);
                    }
            }
        } else {
            switch ($search["table"]) {
                case "glpi_users": // users
                    switch ($search["linkfield"]) {
                        //                case "users_id_assign" :
                        //                   User::dropdown(array('name'   => $search["linkfield"],
                        //                                        'right'  => 'own_ticket',
                        //                                        'entity' => $_SESSION["glpiactive_entity"]));
                        //                   break;

                        case "users_id_tech":
                            User::dropdown(['name'   => $search["linkfield"],
                                'value'  => ($input['value'] ?? ''),
                                'right'  => 'own_ticket',
                                'entity' => $_SESSION["glpiactive_entity"]]);
                            break;

                        default:
                            User::dropdown(['name'   => $search["linkfield"],
                                'value'  => ($input['value'] ?? ''),
                                'entity' => $_SESSION["glpiactive_entity"],
                                'right'  => 'all']);
                    }
                    break;

                case "glpi_softwareversions":
                    switch ($search["linkfield"]) {
                        case "softwareversions_id_use":
                        case "softwareversions_id_buy":
                            $_POST['softwares_id'] = $input['extra_softwares_id'];
                            $_POST['myname']       = $search['linkfield'];
                            $inc = $CFG_GLPI["root_doc"] . '/ajax/dropdownInstallVersion.php';
                            if (file_exists($inc)) {
                                include($inc);
                            }
                            break;
                    }
                    break;

                default: // dropdown case
                    $plugdisplay = false;
                    // Specific plugin Type case
                    if (($plug = isPluginItemType($itemtype))
                    // Specific for plugin which add link to core object
                    || ($plug = isPluginItemType($dbu->getItemTypeForTable($search['table'])))) {
                        $plugdisplay = Plugin::doOneHook(
                            $plug['plugin'],
                            'MassiveActionsFieldsDisplay',
                            ['itemtype' => $itemtype,
                                'options'  => $search],
                        );
                    }
                    $already_display = false;

                    if (isset($search['datatype'])) {
                        switch ($search['datatype']) {
                            case "date":
                                Html::showDateField($search["linkfield"], ['value' => $input['value'] ?? '']);
                                $already_display = true;
                                break;
                            case "time":
                                Time::showTimeField($search["linkfield"], ['value' => $input['value'] ?? '']);
                                $already_display = true;
                                break;
                            case "datetime":
                                Html::showDateTimeField($search["linkfield"], ['value' => ($input['value'] ?? '')]);
                                $already_display = true;
                                break;

                            case "bool":
                                Dropdown::showYesNo($search["linkfield"], ($input['value'] ?? ''));
                                $already_display = true;
                                break;

                            case "text":
                                Html::textarea(['name'             => $search["linkfield"],
                                    'value'           => htmlspecialchars($input['value'] ?? '', ENT_QUOTES, 'UTF-8'),
                                    'cols'            => 45,
                                    'rows'            => 5,
                                    'enable_richtext' => true]);
                                $already_display = true;
                                break;
                        }
                    }

                    if (!$plugdisplay && !$already_display) {
                        $cond = (isset($search['condition']) ? $search['condition'] : []);
                        Dropdown::show(
                            $dbu->getItemTypeForTable($search["table"]),
                            ['name'      => $search["linkfield"],
                                'value'     => ($input['value'] ?? ''),
                                'entity'    => $_SESSION['glpiactiveentities'],
                                'condition' => $cond],
                        );
                    }
            }
        }
    }

    /**
     * Whether the widget of showMassiveActionFieldWidget() is a date or time picker, laid out
     * in the legacy two-cell table.
     *
     * @param array<string, mixed> $search
     * @param array<string, mixed> $input
     */
    public static function massiveActionFieldUsesTable(string $itemtype, array $search, array $input): bool
    {
        $datatype = $search['datatype'] ?? '';

        if ($search['table'] == (new DbUtils())->getTableForItemType($itemtype)) {
            $ticket_fields = ['status', 'items_id', 'type', 'priority', 'impact', 'urgency', 'global_validation'];
            if ($search['table'] === 'glpi_tickets' && in_array($search['linkfield'], $ticket_fields, true)) {
                return false;
            }

            return in_array($datatype, ['date', 'time'], true)
                || ($datatype === 'datetime' && empty($input['relative_dates']));
        }

        if (in_array($search['table'], ['glpi_users', 'glpi_softwareversions'], true)) {
            return false;
        }

        return in_array($datatype, ['date', 'time', 'datetime'], true);
    }
}
