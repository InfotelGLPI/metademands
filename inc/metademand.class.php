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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


include_once('metademandpdf.class.php');

/**
 * Class PluginMetademandsMetademand
 */
class PluginMetademandsMetademand extends CommonDBTM
{
    const LOG_ADD = 1;
    const LOG_UPDATE = 2;
    const LOG_DELETE = 3;
    const SLA_TODO = 1;
    const SLA_LATE = 2;
    const SLA_FINISHED = 3;
    const SLA_PLANNED = 4;
    const SLA_NOTCREATED = 5;

    public static $PARENT_PREFIX = '';
    public static $SON_PREFIX = '';
    public static $rightname = 'plugin_metademands';

    const STEP_INIT = 0;
    const STEP_LIST = 1;
    const STEP_SHOW = 2;

    const STEP_CREATE = "create_metademands";

    const TODO = 1; // todo
    const DONE = 2; // done
    const FAIL = 3; // Failed


    public $dohistory = true;
    private $config;

    public function __construct()
    {
        $config = PluginMetademandsConfig::getInstance();
        $this->config = $config;
        self::$PARENT_PREFIX = $config['parent_ticket_tag'] . ' ';
        self::$SON_PREFIX = $config['son_ticket_tag'] . ' ';
    }

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
        return _n('Meta-Demand', 'Meta-Demands', $nb, 'metademands');
    }

    public static function getIcon()
    {
        return "ti ti-share";
    }

    /**
     * @return bool|int
     */
    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    /**
     * @return bool|mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Display tab for each tickets
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $dbu = new DbUtils();
        if ($dbu->countElementsInTable("glpi_plugin_metademands_tickets_metademands", ["tickets_id" => $item->fields['id']])
            || $dbu->countElementsInTable("glpi_plugin_metademands_tickets_tasks", ["tickets_id" => $item->fields['id']])) {
            if (!$withtemplate
                && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                if ($item->getType() == 'Ticket' && $this->canView()) {
                    $ticket_metademand = new PluginMetademandsTicket_Metademand();
                    $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $item->fields['id']]);
                    $tickets_found = [];
                    // If ticket is Parent : Check if all sons ticket are closed
                    if (count($ticket_metademand_data)) {
                        $ticket_metademand_data = reset($ticket_metademand_data);
                        $tickets_found = PluginMetademandsTicket::getSonTickets(
                            $item->fields['id'],
                            $ticket_metademand_data['plugin_metademands_metademands_id']
                        );
                        $total = 0;
                        foreach ($tickets_found as $ticket_found) {
                            if (isset($ticket_found['parent_tickets_id'])
                                && $ticket_found['tickets_id'] == 0) {
                                continue;
                            }
                            $total++;
                        }
                        $name = _n('Child ticket', 'Child tickets', 2, 'metademands');
                    } else {
                        $ticket_task = new PluginMetademandsTicket_Task();
                        $ticket_task_data = $ticket_task->find(['tickets_id' => $item->fields['id']]);

                        if (count($ticket_task_data)) {
                            $tickets_found = PluginMetademandsTicket::getAncestorTickets(
                                $item->fields['id'],
                                true
                            );
                        }
                        $total = count($tickets_found);
                        $name = self::getTypeName($total);
                    }

                    return self::createTabEntry(
                        $name,
                        $total
                    );
                }
            } else {
                if ($item->getType() == 'Ticket' && $this->canView()) {
                    $name = __('Demand Progression', 'metademands');
                    return self::createTabEntry(
                        $name,
                        1
                    );
                }
            }
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
     * @throws \GlpitestSQLError
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $metademands = new self();

        switch ($item->getType()) {
            case 'Ticket':
                if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $form = new PluginMetademandsForm();
                    $form->showFormsForItilObject($item);
                    $metademands->showPluginForTicket($item);
                    $metademands->showProgressionForm($item);
                } else {
                    $metademands->showProgressionForm($item);
                }
                break;
        }

        return true;
    }

    /**
     * Display tab for each metademands
     *
     * @param array $options
     *
     * @return array
     */
    public function defineTabs($options = [])
    {
        $ong = [];

        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginMetademandsField', $ong, $options);
        $this->addStandardTab('PluginMetademandsWizard', $ong, $options);
        if ($this->getField('step_by_step_mode') == 1) {
            $this->addStandardTab('PluginMetademandsStep', $ong, $options);
            $this->addStandardTab('PluginMetademandsConfigstep', $ong, $options);
        }
        //TODO Change / problem ?
        if ($this->getField('object_to_create') == 'Ticket') {
            $this->addStandardTab('PluginMetademandsTicketField', $ong, $options);
        }
        $this->addStandardTab('PluginMetademandsMetademandTranslation', $ong, $options);
        $this->addStandardTab('PluginMetademandsTask', $ong, $options);
        $this->addStandardTab('PluginMetademandsGroup', $ong, $options);
        if (Session::getCurrentInterface() == 'central') {
            $this->addStandardTab('Log', $ong, $options);
        }
        //TODO Change / problem ?
        if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            if ($this->getField('object_to_create') == 'Ticket') {
                $this->addStandardTab('PluginMetademandsTicket_Metademand', $ong, $options);
                $this->addStandardTab('PluginMetademandsStepform', $ong, $options);
            }
        }
        return $ong;
    }

    /**
     * @param        $object
     * @param string $type
     *
     * @return bool|string
     */
    public static function redirectForm($object, $type = 'show')
    {
        global $CFG_GLPI;

        $conf = new PluginMetademandsConfig();
        $config = $conf->getInstance();
        if ($config['simpleticket_to_metademand']) {
            if (($type == 'show' && $object->fields["id"] == 0)
                || ($type == 'update' && $object->fields["id"] > 0)) {
                if (!empty($object->input["itilcategories_id"])) {
                    $dbu = new DbUtils();
                    $metademand = new self();
                    $metas = $metademand->find(['is_active' => 1,
                        'is_deleted' => 0,
                        'is_template' => 0,
                        'type' => $object->input["type"]
                    ]);
                    $cats = [];

                    foreach ($metas as $meta) {
                        $categories = [];
                        if (isset($meta['itilcategories_id'])) {
                            if (is_array(json_decode($meta['itilcategories_id'], true))) {
                                $categories = $meta['itilcategories_id'];
                            } else {
                                $array = [$meta['itilcategories_id']];
                                $categories = json_encode($array);
                            }
                        }
                        $cats[$meta['id']] = json_decode($categories);
                    }

                    $meta_concerned = 0;
                    foreach ($cats as $meta => $meta_cats) {
                        if (in_array($object->input['itilcategories_id'], $meta_cats)) {
                            $meta_concerned = $meta;
                        }
                    }

                    if ($meta_concerned) {
                        //$meta = reset($metas);
                        // Redirect if not linked to a resource contract type
                        if (!$dbu->countElementsInTable(
                            "glpi_plugin_metademands_metademands_resources",
                            ["plugin_metademands_metademands_id" => $meta_concerned]
                        )) {
                            unset($_SESSION['plugin_metademands']);
                            return PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?itilcategories_id=" .
                                $object->input['itilcategories_id'] . "&metademands_id=" . $meta_concerned .
                                "&tickets_id=" . $object->fields["id"] . "&step=" . self::STEP_SHOW;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function post_getEmpty()
    {
        $this->fields["background_color"] = '#ffffff';
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        global $DB;
        $cat_already_store = false;
        if (isset($input['itilcategories_id']) && !empty($input['itilcategories_id'])) {
            //retrieve all multiple cats from all metademands
            $iterator_cats = $DB->request(['SELECT' => ['id', 'itilcategories_id'],
                'FROM' => $this->getTable(),
                'WHERE' => ['is_deleted' => 0, 'is_template' => 0, 'type' => $input['type']]]);

            $cats = $input['itilcategories_id'];
            foreach ($iterator_cats as $data) {
                if (is_array(json_decode($data['itilcategories_id'])) && is_array($cats)) {
                    $cat_already_store = !empty(array_intersect($cats, json_decode($data['itilcategories_id'])));
                }
                if ($cat_already_store) {
                    $error = __('The category is related to a demand. Thank you to select another', 'metademands');
                    Session::addMessageAfterRedirect($error, false, ERROR);
                    return false;
                }
                $iterator_cats->next();
            }
        }

        if (!$cat_already_store) {
            if (isset($input['itilcategories_id'])) {
                if ($input['itilcategories_id'] != null) {
                    $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
                } else {
                    $input['itilcategories_id'] = '';
                }
            } else {
                $input['itilcategories_id'] = '';
            }
        }

        if (empty($input['object_to_create'])) {
            Session::addMessageAfterRedirect(__('Object to create is mandatory', 'metademands'), false, ERROR);
            return false;
        }

        if (isset($input['object_to_create'])
            && ($input['object_to_create'] == 'Problem' || $input['object_to_create'] == 'Change')) {
            $input['type'] = 0;
            $input['force_create_tasks'] = 1;
        }

        $template = new Self();
        if (isset($this->input['id_template'])) {
            if ($template->getFromDBByCrit(['id' => $this->input['id_template'],
                'is_template' => 1])) {
                $input["metademands_oldID"] = $this->input['id_template'];
                unset($input['id']);
                unset($input['withtemplate']);
            }
        }

        return $input;
    }


    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForUpdate($input)
    {
        global $DB;
        $cat_already_store = false;


        if (isset($input['itilcategories_id']) && count($input['itilcategories_id']) > 0) {
            //retrieve all multiple cats from all metademands
            if ($input['object_to_create'] == 'Problem' || $input['object_to_create'] == 'Change') {
                $input['type'] = 0;
            }

            $iterator_cats = $DB->request(['SELECT' => ['id', 'itilcategories_id'],
                'FROM' => $this->getTable(),
                'WHERE' => ['is_deleted' => 0, 'is_template' => 0, 'type' => $input['type']]]);
            $iterator_meta_existing_cats = $DB->request(['SELECT' => 'itilcategories_id',
                'FROM' => $this->getTable(),
                'WHERE' => ['id' => $input['id'], 'is_deleted' => 0, 'is_template' => 0, 'type' => $input['type']]]);
            $cats = [];
            $number_cats_meta = count($iterator_meta_existing_cats);
            if ($number_cats_meta) {
                foreach ($iterator_meta_existing_cats as $data) {
                    $cats = json_decode($data['itilcategories_id']);
                    $iterator_meta_existing_cats->next();
                }
                if (!isset($cats) || $cats == null) {
                    $cats = [];
                }
            }

            if (count($input['itilcategories_id']) >= count($cats)) {
                foreach ($input['itilcategories_id'] as $post_cats) {
                    if (in_array($post_cats, $cats)) {
                        unset($cats[array_search($post_cats, $cats)]);
                    } else {
                        $cats[] = $post_cats;
                    }
                }
                foreach ($iterator_cats as $data) {
                    if (is_array(json_decode($data['itilcategories_id'])) && $input['id'] != $data['id']) {
                        $cat_already_store = !empty(array_intersect($cats, json_decode($data['itilcategories_id'])));
                    }
                    if ($cat_already_store) {
                        $error = __('The category is related to a demand. Thank you to select another', 'metademands');
                        Session::addMessageAfterRedirect($error, false, ERROR);
                        return false;
                    }
                    $iterator_cats->next();
                }
                if (!$cat_already_store) {
                    $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
                }
            } else {
                $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
            }
        }

        if (isset($input['is_order']) && $input['is_order'] == 1) {
            $metademands_data = $this->constructMetademands($this->getID());
            $metademands_data = array_values($metademands_data);
            if (isset($metademands_data['tasks'])
                && is_array($metademands_data['tasks'])
                && count($metademands_data['tasks']) > 0) {
                $error = __('There are sub-metademands or this is a sub-metademand. This metademand cannot be in basket mode', 'metademands');
                Session::addMessageAfterRedirect($error, false, ERROR);
                return false;
            }
        }

        if (empty($input['object_to_create']) && empty($this->fields['object_to_create'])) {
            Session::addMessageAfterRedirect(__('Object to create is mandatory', 'metademands'), false, ERROR);
            return false;
        }

        if (isset($input['object_to_create'])
            && ($input['object_to_create'] == 'Problem' || $input['object_to_create'] == 'Change')) {
            $input['type'] = 0;
            $input['force_create_tasks'] = 1;
        }

        return $input;
    }

    public function post_addItem()
    {
        parent::post_addItem();

        if (!isset($this->input['id']) || empty($this->input['id'])) {
            $this->input['id'] = $this->fields['id'];
        }
        if (!isset($this->input["metademands_oldID"])) {
            PluginMetademandsTicketField::updateMandatoryTicketFields($this->input);
        }

        $confStep = new PluginMetademandsConfigstep();

        $confStep->add(['plugin_metademands_metademands_id' => $this->fields['id']]);

        if (isset($this->input["metademands_oldID"])) {

            // ADD fields
            $fields = PluginMetademandsField::getItemsAssociatedTo("PluginMetademandsMetademand", $this->input["metademands_oldID"]);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $override_input['name'] = $field->fields["name"];
                    $override_input['link_to_user'] = 0;
                    $override_input['plugin_metademands_fields_id'] = 0;
                    $override_input['plugin_metademands_tasks_id'] = 0;
                    $field->clone($override_input);
                }
            }

            // ADD tasks
            $tasks = PluginMetademandsTask::getItemsAssociatedTo("PluginMetademandsMetademand", $this->input["metademands_oldID"]);
            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $override_input['name'] = $task->fields["name"];
                    $override_input['plugin_metademands_tasks_id'] = 0;
                    $idtask = $task->clone($override_input);

                    $fields_task = PluginMetademandsTicketTask::getItemsAssociatedTo("PluginMetademandsTask", $task->fields["id"]);
                    if (!empty($fields_task)) {
                        $override_input['plugin_metademands_tasks_id'] = $idtask;
                        $fields_task[0]->clone($override_input);
                    }
                }
            }
            if ($this->input['object_to_create'] == 'Ticket') {
                // ADD ticket fields
                $ticketfields = PluginMetademandsTicketField::getItemsAssociatedTo("PluginMetademandsMetademand", $this->input["metademands_oldID"]);
                if (!empty($ticketfields)) {
                    foreach ($ticketfields as $ticketfield) {
                        $override_input['plugin_metademands_metademands_id'] = $this->getID();
                        $ticketfield->clone($override_input);
                    }
                }
            }

            // ADD groups
            $groups = PluginMetademandsGroup::getItemsAssociatedTo("PluginMetademandsMetademand", $this->input["metademands_oldID"]);
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $group->clone($override_input);
                }
            }
            // ADD steps
            $steps = PluginMetademandsStep::getItemsAssociatedTo("PluginMetademandsMetademand", $this->input["metademands_oldID"]);
            if (!empty($steps)) {
                foreach ($steps as $step) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $step->clone($override_input);
                }
            }
        }
    }

    /**
     * @param int $history
     */
    public function post_updateItem($history = 1)
    {
        parent::post_updateItem($history);

        if (isset($this->updates['is_order']) && $this->input['is_order'] == 1) {
            $fields = new PluginMetademandsField();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $this->getID()]);
            if (count($fields_data) > 0) {
                foreach ($fields_data as $field) {
                    $fields->update(['is_basket' => 1, 'id' => $field['id']]);
                }
            }
        }
        PluginMetademandsTicketField::updateMandatoryTicketFields($this->input);
    }

    /**
     * @param $metademands_id
     *
     * @return string
     */
    public function getURL($metademands_id)
    {
        global $CFG_GLPI;
        if (!empty($metademands_id)) {
            return urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=PluginMetademandsWizard_" . $metademands_id);
        }
    }

    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2)
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'comment',
            'name' => __('Comments'),
            'datatype' => 'text'
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'is_active',
            'name' => __('Active'),
            'datatype' => 'bool',
        ];

        //      $tab[] = [
        //         'id'       => '4',
        //         'table'    => $this->getTable(),
        //         'field'    => 'icon',
        //         'name'     => __('Icon'),
        //         'datatype' => 'text',
        //      ];

        $tab[] = [
            'id' => '5',
            'table' => $this->getTable(),
            'field' => 'is_order',
            'name' => __('Use as basket', 'metademands'),
            'datatype' => 'bool'
        ];

        $tab[] = [
            'id' => '6',
            'table' => $this->getTable(),
            'field' => 'create_one_ticket',
            'name' => __('Create one ticket for all lines of the basket', 'metademands'),
            'datatype' => 'bool'
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'type',
            'name' => __('Type'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'object_to_create',
            'name' => __('Object to create', 'metademands'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '9',
            'table' => $this->getTable(),
            'field' => 'maintenance_mode',
            'name' => __('Maintenance mode'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '10',
            'table' => $this->getTable(),
            'field' => 'title_color',
            'name' => __('Title color', 'metademands'),
            'searchtype' => 'equals',
            'datatype' => 'color'
        ];

        $tab[] = [
            'id' => '11',
            'table' => $this->getTable(),
            'field' => 'background_color',
            'name' => __('Background color', 'metademands'),
            'searchtype' => 'equals',
            'datatype' => 'color'
        ];

        $tab[] = [
            'id' => '12',
            'table' => $this->getTable(),
            'field' => 'can_update',
            'name' => __('Allow form modification before validation', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '13',
            'table' => $this->getTable(),
            'field' => 'can_clone',
            'name' => __('Allow form modification after validation', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '92',
            'table' => $this->getTable(),
            'field' => 'itilcategories_id',
            'name' => __('Category'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number'
        ];

        $tab[] = [
            'id' => '80',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown'
        ];

        $tab[] = [
            'id' => '86',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __('Child entities'),
            'datatype' => 'bool'
        ];

        return $tab;
    }


    /**
     * @param string $field
     * @param array|string $values
     * @param array $options
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'itilcategories_id':
                if (is_array(json_decode($values[$field], true))) {
                    $categories = json_decode($values[$field], true);
                } else {
                    $categories = [$values[$field]];
                }
                $display = "";
                if (count($categories) > 0) {
                    foreach ($categories as $category) {
                        $display .= Dropdown::getDropdownName("glpi_itilcategories", $category) . "<br>";
                    }
                }
                return $display;
                break;
            case 'type':
                return Ticket::getTicketTypeName($values[$field]);
                break;

            case 'object_to_create':
                return self::getObjectTypeName($values[$field]);
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
//            case 'itilcategories_id':
//                $opt = ['name' => $name,
//                    'value' => $values[$field],
//                    'display' => false];
//                return ITILCategory::dropdown($opt);
            case 'type':
                $options['value'] = $values[$field];
                return Ticket::dropdownType($name, $options);
            case 'object_to_create':
                return Dropdown::showFromArray($name, self::getObjectTypes(), $options);
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * @param $value
     *
     * @return string
     */
    private static function getObjectTypeName($value)
    {
        switch ($value) {
            case 'Ticket':
                return __('Ticket');
            case 'Change':
                return __('Change');
            case 'Problem':
                return __('Problem');
            default:
                // Return $value if not define
                return Dropdown::EMPTY_VALUE;
        }
    }

    /**
     * @return array
     */
    private static function getObjectTypes()
    {
        return [
            null => Dropdown::EMPTY_VALUE,
            'Ticket' => __('Ticket'),
            'Change' => __('Change'),
            'Problem' => __('Problem'),
        ];
    }

    public function showForm($ID, $options = [])
    {

        $options['formoptions'] = "data-track-changes=false";
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $is_template = isset($options['withtemplate']) && (int)$options['withtemplate'] === 1;
        $from_template = isset($options['withtemplate']) && (int)$options['withtemplate'] === 2;

        if ($is_template & !$this->isNewItem()) {
            // Show template name after creation (creation is already handled by
            // showFormHeader which add the template name in a special header
            // only displayed on creation)
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Template name') . "</td>";
            echo "<td>";
            echo Html::input('template_name', [
                'value' => $this->fields['template_name']
            ]);
            echo "</td>";
            echo "<td colspan='2'>&nbsp;</td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo Html::hidden('withtemplate', ['value' => $options['withtemplate']]);
        echo Html::hidden('id_template', ['value' => $ID]);
        if ($this->fields['maintenance_mode'] == 1) {
            echo "<h3>";
            echo "<div class='alert alert-warning center'>";
            echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:orange'></i>&nbsp;";
            echo __('This form is in maintenance mode', 'metademands') . "</div></h3>";
        }

        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name'], 'size' => 40]);
        echo "</td>";

        echo "<td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("is_active", $this->fields['is_active']);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Allow form modification before validation', 'metademands') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("can_update", $this->fields['can_update']);
        echo "</td>";
        echo "<td>" . __('Allow form modification after validation', 'metademands') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("can_clone", $this->fields['can_clone']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>";

        echo __('Step-by-step mode', 'metademands');
        echo "</td>";
        echo "<td>";

        Dropdown::showYesNo("step_by_step_mode", $this->fields['step_by_step_mode']);
        echo "</td>";

        echo "<td>" . __('Maintenance mode') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("maintenance_mode", $this->fields['maintenance_mode']);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Object to create', 'metademands') . "&nbsp;<span style='color:red;'>*</span></td>";
        echo "<td>";
        if ($ID == 0 || empty($ID)) {
            $objects = self::getObjectTypes();
            $idDropdown = Dropdown::showFromArray('object_to_create', $objects, ['value' => $this->fields['object_to_create']]);
            Ajax::updateItemOnEvent(
                "dropdown_object_to_create" . $idDropdown,
                "define_object",
                PLUGIN_METADEMANDS_WEBDIR . "/ajax/type_object.php",
                ['object_to_create' => '__VALUE__']
            );
        } else {
            echo self::getObjectTypeName($this->fields['object_to_create']);
            echo Html::hidden('object_to_create', ['value' => $this->fields['object_to_create']]);
        }
        echo "</td>";
        echo "<td colspan='2'>";

        echo "<span id='define_object'>";
        echo "</span>";

        echo "</td>";
        echo "</tr>";

        if ($ID > 0) {
            echo "<tr class='tab_bg_1'>";

            if ($this->fields['object_to_create'] == 'Ticket') {
                echo "<td>" . _n('Type', 'Types', 1) . "</td>";
                echo "<td>";
                $opt = [
                    'value' => $this->fields['type'],
                ];
                $rand = Ticket::dropdownType('type', $opt);

                $params = ['type' => '__VALUE__',
                    'entity_restrict' => $this->fields['entities_id'],
                    'value' => $this->fields['itilcategories_id'],
                    'currenttype' => $this->fields['type']];

                Ajax::updateItemOnSelectEvent(
                    "dropdown_type$rand",
                    "show_category_by_type",
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/dropdownITILCategories.php",
                    $params
                );
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
            }

            echo "<td>" . __('Category') . "</td>";
            echo "<td>";

            if ($this->fields['type']) {
                switch ($this->fields['type']) {
                    case Ticket::INCIDENT_TYPE:
                        $criteria['is_incident'] = 1;
                        break;

                    case Ticket::DEMAND_TYPE:
                        $criteria['is_request'] = 1;
                        break;
                }
            } else {
                $criteria = ['is_incident' => 1];
            }

            if ($this->fields['object_to_create'] == 'Problem') {
                $criteria = ['is_problem' => 1];
            } elseif ($this->fields['object_to_create'] == 'Change') {
                $criteria = ['is_change' => 1];
            }

            $criteria += getEntitiesRestrictCriteria(
                \ITILCategory::getTable(),
                'entities_id',
                $_SESSION['glpiactiveentities'],
                true
            );

            $dbu = new DbUtils();

            $crit["is_deleted"] = 0;
            $crit["is_template"] = 0;
            $crit += ['NOT' => [
                'id' => $ID
            ]];
            $cats = $dbu->getAllDataFromTable(self::getTable(), $crit);

            $used = [];
            foreach ($cats as $item) {
                $tempcats = json_decode($item['itilcategories_id'], true);
                if (is_array($tempcats)) {
                    foreach ($tempcats as $tempcat) {
                        $used [] = $tempcat;
                    }
                }
            }

//            $ticketcats = $dbu->getAllDataFromTable(PluginMetademandsTicketTask::getTable());
//            foreach ($ticketcats as $item) {
//                if ($item['itilcategories_id'] > 0) {
//                    $used [] = $item['itilcategories_id'];
//                }
//            }
            $used = array_unique($used);
            if (count($used) > 0) {
                $criteria += ['NOT' => [
                    'id' => $used
                ]];
            }
            $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $criteria);
            $temp = [];
            foreach ($result as $item) {
                $temp[$item['id']] = $item['completename'];
            }

            $categories = [];
            if (isset($this->fields['itilcategories_id'])) {
                if (is_array($this->fields['itilcategories_id'])) {
                    $categories = json_encode($this->fields['itilcategories_id']);
                } elseif (is_array(json_decode($this->fields['itilcategories_id'], true))) {
                    $categories = $this->fields['itilcategories_id'];
                } else {
                    $array = [$this->fields['itilcategories_id']];
                    $categories = json_encode($array);
                }
            }
            $values = $this->fields['itilcategories_id'] ? json_decode($categories) : [];


            echo "<span id='show_category_by_type'>";
            Dropdown::showFromArray(
                'itilcategories_id',
                $temp,
                ['values' => $values,
                    'width' => '100%',
                    'multiple' => true,
                    'entity' => $_SESSION['glpiactiveentities']]
            );
            echo "</span>";
            echo "</td>";
            echo "</tr>";
        }


        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('URL') . "</td><td>";
        echo $this->getURL($ID);
        echo "</td>";

        echo "<td rowspan='2'>" . __('Comments') . "</td>";
        echo "<td rowspan='2'>";
        Html::textarea(['name' => 'comment',
            'value' => $this->fields["comment"],
            'cols' => 50,
            'rows' => 10,
            'enable_richtext' => false,
            'enable_fileupload' => false]);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Icon') . "</td><td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon',
            [$this->fields['icon'] => $this->fields['icon']],
            [
                'id' => $icon_selector_id,
                'selected' => $this->fields['icon'],
                'style' => 'width:175px;'
            ]
        );

        echo Html::script('js/Forms/FaIconSelector.js');
        echo Html::scriptBlock(
            <<<JAVASCRIPT
         $(
            function() {
               var icon_selector = new GLPI.Forms.FaIconSelector(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
            }
         );
JAVASCRIPT
        );
        //      $opt = [
        //         'value'     => isset($this->fields['icon']) ? $this->fields['icon'] : '',
        //         'maxlength' => 50,
        //         'size'      => 50,
        //      ];
        //      echo Html::input('icon', $opt);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Use as basket', 'metademands') . "</td><td>";
        Dropdown::showYesNo("is_order", $this->fields['is_order']);
        echo "</td>";

        if ($this->fields['is_order'] == 1) {
            echo "<td>" . __('Create one ticket for all lines of the basket', 'metademands') . "</td><td>";
            Dropdown::showYesNo("create_one_ticket", $this->fields['create_one_ticket']);
            echo "<br>";
            echo "<span class='alert alert-warning d-flex'>";
            echo __('You cannot use this parameter if there is more than one category', 'metademands');
            echo "</span>";
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        if ($this->fields['object_to_create'] != 'Change') {
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Need validation to create subticket', 'metademands') . "</td><td>";
            Dropdown::showYesNo("validation_subticket", $this->fields['validation_subticket']);
            echo "</td>";
            echo "<td>";
            echo __('Hide the "No" and empty values of fields in the tickets', 'metademands');
            echo "</td><td>";
            Dropdown::showYesNo("hide_no_field", $this->fields['hide_no_field']);
            echo "</td>";
            echo "</tr>";
        }
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Background color', 'metademands') . "</td><td>";
        Html::showColorField('background_color', ['value' => $this->fields["background_color"]]);
        echo "</td>";
        echo "<td>" . __('Title color', 'metademands') . "</td><td>";
        Html::showColorField('title_color', ['value' => $this->fields["title_color"]]);
        echo "</td>";
        echo "</tr>";

        if ($ID > 0) {
            if ($this->fields['object_to_create'] == 'Ticket') {
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . __('Create tasks (not child tickets)', 'metademands') . "</td>";
                echo "<td>";
                Dropdown::showYesNo("force_create_tasks", $this->fields['force_create_tasks']);
                echo "</td>";
                echo "<td colspan='2'></td>";
                echo "</tr>";
            } else {
                echo Html::hidden('force_create_tasks', ['value' => 1]);
            }
        }

        $options['addbuttons'] = ['export' => __('Export', 'metademands')];

        $this->showFormButtons($options);

        return true;
    }


    /**
     * @param $metademands_id
     */
    public function showDuplication($metademands_id)
    {
        echo "<h3><div class='alert alert-warning' role='alert'>";
        echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:orange'></i>&nbsp;";
        echo __('Tasks tree cannot be changed as unresolved related tickets exist or activate maintenance mode', 'metademands');

        echo "<br><br><form name='task_form' id='task_form' method='post' 
               action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        echo Html::submit(_sx('button', 'Duplicate'), ['name' => 'execute', 'class' => 'btn btn-primary']);
        echo Html::hidden('_method', ['value' => 'Duplicate']);
        echo Html::hidden('metademands_id', ['value' => $metademands_id]);
        echo Html::hidden('redirect', ['value' => 1]);

        Html::closeForm();
        echo "</div>";
        echo "</h3>";
    }

    /**
     * @param       $ID
     * @param array $field
     */
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        $this->getFromDB($ID);

        switch ($field['name']) {
            case 'url':
                echo $this->getURL($this->fields['id']);
                break;
            case 'itilcategories_id':
                echo Html::hidden('type', ['value' => $this->fields['type']]);
                switch ($this->fields['type']) {
                    case Ticket::INCIDENT_TYPE:
                        $criteria = ['is_incident' => 1];
                        break;
                    case Ticket::DEMAND_TYPE:
                        $criteria = ['is_request' => 1];
                        break;
                    default:
                        $criteria = [];
                        break;
                }
                $criteria += getEntitiesRestrictCriteria(
                    \ITILCategory::getTable(),
                    'entities_id',
                    $_SESSION['glpiactiveentities'],
                    true
                );

                $dbu = new DbUtils();

                $crit["is_deleted"] = 0;
                $crit["is_template"] = 0;
                $crit += ['NOT' => [
                    'id' => $ID
                ]];
                $cats = $dbu->getAllDataFromTable(self::getTable(), $crit);

                $used = [];
                foreach ($cats as $item) {
                    $tempcats = json_decode($item['itilcategories_id'], true);
                    if (is_array($tempcats)) {
                        foreach ($tempcats as $tempcat) {
                            $used [] = $tempcat;
                        }
                    }
                }

                $ticketcats = $dbu->getAllDataFromTable(PluginMetademandsTicketTask::getTable());
                foreach ($ticketcats as $item) {
                    if ($item['itilcategories_id'] > 0) {
                        $used [] = $item['itilcategories_id'];
                    }
                }
                if (count($used) > 0) {
                    $used = array_unique($used);
                    $criteria += ['NOT' => [
                        'id' => $used
                    ]];
                }
                $dbu = new DbUtils();
                $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $criteria);
                $temp = [];
                foreach ($result as $item) {
                    $temp[$item['id']] = $item['completename'];
                }
                $categories = [];
                if (isset($this->fields['itilcategories_id'])) {
                    if (is_array(json_decode($this->fields['itilcategories_id'], true))) {
                        $categories = $this->fields['itilcategories_id'];
                    } else {
                        $array = [$this->fields['itilcategories_id']];
                        $categories = json_encode($array);
                    }
                }
                $values = $this->fields['itilcategories_id'] ? json_decode($categories) : [];

                Dropdown::showFromArray(
                    'itilcategories_id',
                    $temp,
                    ['values' => $values,
                        'width' => '100%',
                        'multiple' => true,
                        'entity' => $_SESSION['glpiactiveentities']]
                );
                break;
            case 'tickettemplates_id':
                $opt['condition'] = [];
                $opt['value'] = $this->fields['tickettemplates_id'];
                $opt['entity'] = $_SESSION['glpiactiveentities'];
                TicketTemplate::dropdown($opt);
                break;
            case 'icon':
                $icon_selector_id = 'icon_' . mt_rand();
                echo Html::select(
                    'icon',
                    [$this->fields['icon'] => $this->fields['icon']],
                    [
                        'id' => $icon_selector_id,
                        'selected' => $this->fields['icon'],
                        'style' => 'width:175px;'
                    ]
                );

                echo Html::script('js/Forms/FaIconSelector.js');
                echo Html::scriptBlock(
                    <<<JAVASCRIPT
         $(
            function() {
               var icon_selector = new GLPI.Forms.FaIconSelector(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
            }
         );
JAVASCRIPT
                );
                break;
        }
    }

    /**
     * Add Logs
     *
     * @param $input
     * @param $logtype
     *
     * @return void
     */
    public static function addLog($input, $logtype)
    {
        $new_value = $_SESSION["glpiname"] . " ";
        if ($logtype == self::LOG_ADD) {
            $new_value .= __('field add on demand', 'metademands') . " : ";
        } elseif ($logtype == self::LOG_UPDATE) {
            $new_value .= __('field update on demand', 'metademands') . " : ";
        } elseif ($logtype == self::LOG_DELETE) {
            $new_value .= __('field delete on demand', 'metademands') . " : ";
        }

        $metademand = new self();
        $metademand->getFromDB($input['plugin_metademands_metademands_id']);

        $field = new PluginMetademandsField();
        $field->getFromDB($input['id']);

        $new_value .= $metademand->getName() . " - " . $field->getName();

        self::addHistory($input['plugin_metademands_metademands_id'], __CLASS__, "", $new_value);
        self::addHistory($input['id'], "PluginMetademandsField", "", $new_value);
    }

    /**
     * Add an history
     *
     * @param        $ID
     * @param        $type
     * @param string $old_value
     * @param string $new_value
     *
     * @return void
     */
    public static function addHistory($ID, $type, $old_value = '', $new_value = '')
    {
        $changes[0] = 0;
        $changes[1] = $old_value;
        $changes[2] = $new_value;
        Log::history($ID, $type, $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);
    }

    /**
     * methodAddMetademands : Add metademand from WEBSERVICE plugin
     *
     * @param type $params
     * @param type $protocol
     *
     * @return type
     * @throws \GlpitestSQLError
     * @global type $DB
     *
     */
    //   static function methodAddMetademands($params, $protocol) {
    //
    //      if (isset($params['help'])) {
    //         return ['help'           => 'bool,optional',
    //                 'metademands_id' => 'int,mandatory',
    //                 'values'         => 'array,optional'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      if (!isset($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
    //      }
    //
    //      if (isset($params['metademands_id']) && !is_numeric($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'metademands_id');
    //      }
    //
    //      $metademands = new self();
    //
    //      if (!$metademands->can(-1, UPDATE) && !PluginMetademandsGroup::isUserHaveRight($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
    //      }
    //
    //      $meta_data = [];
    //
    //      if (isset($params['values']['fields']) && count($params['values']['fields'])) {
    //         foreach ($params['values']['fields'] as $data) {
    //            $meta_data['fields'][$data['id']] = $data['values'];
    //         }
    //      }
    //      return $metademands->addObjects($params['metademands_id'], $meta_data);
    //   }

    //   /**
    //    * methodGetIntervention : Get intervention from WEBSERVICE plugin
    //    *
    //    * @param type  $params
    //    * @param type  $protocol
    //    *
    //    * @return type
    //    * @throws \GlpitestSQLError
    //    * @global type $DB
    //    *
    //    */
    //   static function methodShowMetademands($params, $protocol) {
    //
    //      if (isset($params['help'])) {
    //         return ['help'           => 'bool,optional',
    //                 'metademands_id' => 'int'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      if (!isset($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
    //      }
    //
    //      $metademands = new self();
    //
    //      if (!$metademands->canCreate() && !PluginMetademandsGroup::isUserHaveRight($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
    //      }
    //
    //      $result = $metademands->constructMetademands($params['metademands_id']);
    //
    //      $response = [];
    //      foreach ($result as $step => $values) {
    //         foreach ($values as $metademands_id => $form) {
    //            $response[] = ['metademands_id'   => $metademands_id,
    //                           'metademands_name' => Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metademands_id),
    //                           'form'             => $form['form'],
    //                           'tasks'            => $form['tasks']];
    //         }
    //      }
    //
    //      return $response;
    //   }

    //   /**
    //    * @param $params
    //    * @param $protocol
    //    *
    //    * @return array
    //    * @throws \GlpitestSQLError
    //    */
    //   static function methodListMetademands($params, $protocol) {
    //
    //      if (isset($params['help'])) {
    //         return ['help' => 'bool,optional'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      $metademands = new self();
    //      $result      = $metademands->listMetademands();
    //
    //      $response = [];
    //
    //      foreach ($result as $key => $val) {
    //         $response[] = ['id' => $key, 'value' => $val];
    //      }
    //
    //      return $response;
    //   }


    /**
     * @param bool $forceview
     * @param array $options
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public function listMetademands($forceview = false, $options = [])
    {
        global $DB;

        $dbu = new DbUtils();
        $params['condition'] = '';

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $meta_data = [];
        if (isset($options['empty_value'])) {
            $meta_data[0] = Dropdown::EMPTY_VALUE;
        }
        $type = Ticket::DEMAND_TYPE;
        if (isset($options['type'])) {
            $type = $options['type'];
        }
        if ($type == Ticket::INCIDENT_TYPE || $type == Ticket::DEMAND_TYPE) {
            $condition = "1 AND `" . $this->getTable() . "`.`type` = '$type' 
                        AND is_active = 1 
                        AND is_deleted = 0 
                        AND 'is_template' = 0 ";
        } else {
            $condition = "1 AND `" . $this->getTable() . "`.`object_to_create` = '$type' 
                        AND is_active = 1 
                        AND is_deleted = 0 
                        AND 'is_template' = 0 ";
        }

        $condition .= $dbu->getEntitiesRestrictRequest("AND", $this->getTable());

        if (!empty($params['condition'])) {
            $condition .= $params['condition'];
        }

        if (!empty($type) || $forceview) {
            $query = "SELECT `" . $this->getTable() . "`.`name`, 
                          `" . $this->getTable() . "`.`id`, 
                          `glpi_entities`.`completename` as entities_name
                   FROM " . $this->getTable() . "
                   INNER JOIN `glpi_entities`
                      ON (`" . $this->getTable() . "`.`entities_id` = `glpi_entities`.`id`)
                   WHERE $condition
                   ORDER BY `" . $this->getTable() . "`.`name`";

            $result = $DB->query($query);
            if ($DB->numrows($result)) {
                while ($data = $DB->fetchAssoc($result)) {
                    if ($this->canCreate() || PluginMetademandsGroup::isUserHaveRight($data['id'])) {
                        if (!$dbu->countElementsInTable(
                            "glpi_plugin_metademands_metademands_resources",
                            ["plugin_metademands_metademands_id" => $data['id']]
                        )) {
                            if (empty($name = PluginMetademandsMetademand::displayField($data['id'], 'name'))) {
                                $name = $data['name'];
                            }
                            $meta_data[$data['id']] = $name . ' (' . $data['entities_name'] . ')';
                        }
                    }
                }
            }
        }

        return $meta_data;
    }

    /**
     * @param       $metademands_id
     * @param array $forms
     * @param int $step
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public function constructMetademands($metademands_id, $forms = [], $step = self::STEP_SHOW)
    {
        global $DB;

        $metademands = new self();
        $metademands->getFromDB($metademands_id);

        $hidden = false;
        if (isset($_SESSION['metademands_hide'])) {
            $hidden = in_array($metademands_id, $_SESSION['metademands_hide']);
        }

        if (!empty($metademands_id) && !$hidden) {
            // get normal form data
            $field = new PluginMetademandsField();
            $form_data = $field->find(
                ['plugin_metademands_metademands_id' => $metademands_id],
                ['rank', 'order']
            );


            // Construct array
            $forms[$step][$metademands_id]['form'] = [];
            $forms[$step][$metademands_id]['tasks'] = [];

            if (count($form_data)) {
                //TODO add array options
                foreach ($form_data as $idf => $form_data_fields) {

                    $fieldopt = new PluginMetademandsFieldOption();
                    if($opts = $fieldopt->find(["plugin_metademands_fields_id" => $idf])) {

                        foreach ($opts as $opt) {
                            $check_value = $opt["check_value"];
                            if ($idf > 0 && $fieldopt->getFromDBByCrit(["plugin_metademands_fields_id" => $idf, "check_value" => $check_value])) {
                                $form_data[$idf]["options"][$check_value]['plugin_metademands_tasks_id'] = $fieldopt->fields['plugin_metademands_tasks_id'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['fields_link'] = $fieldopt->fields['fields_link'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['hidden_link'] = $fieldopt->fields['hidden_link'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['hidden_block'] = $fieldopt->fields['hidden_block'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['users_id_validate'] = $fieldopt->fields['users_id_validate'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['childs_blocks'] = $fieldopt->fields['childs_blocks'];
                                $form_data[$idf]["options"][$check_value]['checkbox_value'] = $fieldopt->fields['checkbox_value'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['checkbox_id'] = $fieldopt->fields['checkbox_id'] ?? 0;
                                $form_data[$idf]["options"][$check_value]['parent_field_id'] = $fieldopt->fields['parent_field_id'] ?? 0;
                            }
                        }
                    }

                }

                $forms[$step][$metademands_id]['form'] = $form_data;
            }

            // Task only for demands
            if (isset($metademands->fields['type'])) {
                if (isset($metademands->fields['force_create_tasks'])
                    && $metademands->fields['force_create_tasks'] > 0) {
                    $tasks = new PluginMetademandsTask();
                    $tasks_data = $tasks->getTasks(
                        $metademands_id,
                        ['condition' => ['glpi_plugin_metademands_tasks.type' => PluginMetademandsTask::TASK_TYPE]]
                    );

                    $forms[$step][$metademands_id]['tasks'] = $tasks_data;
                } else {
                    $tasks = new PluginMetademandsTask();
                    $tasks_data = $tasks->getTasks(
                        $metademands_id,
                        ['condition' => ['glpi_plugin_metademands_tasks.type' => PluginMetademandsTask::TICKET_TYPE]]
                    );

                    $forms[$step][$metademands_id]['tasks'] = $tasks_data;
                }
            }

            // Check if task are metademands, if some found : recursive call
            if (isset($metademands->fields['type'])) {
                $query = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` AS link_metademands_id
                        FROM `glpi_plugin_metademands_tasks`
                        RIGHT JOIN `glpi_plugin_metademands_metademandtasks`
                          ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` = `glpi_plugin_metademands_tasks`.`id`)
                        WHERE `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` = " . $metademands_id;
                $result = $DB->query($query);
                if ($DB->numrows($result)) {
                    while ($data = $DB->fetchAssoc($result)) {
                        $step++;
                        $forms = $this->constructMetademands($data['link_metademands_id'], $forms, $step);
                    }
                }
            }
        }
        return $forms;
    }

    /**
     * @param $ticket
     * @param $metademands_id
     *
     * @throws \GlpitestSQLError
     */
    public function convertMetademandToTicket($ticket, $metademands_id)
    {
        $tickets_id = $ticket->input["id"];
        $oldlanguage = $_SESSION['glpilanguage'];
        $ticket_task = new PluginMetademandsTicket_Task();
        $ticket_metademand = new PluginMetademandsTicket_Metademand();
        $ticket_field = new PluginMetademandsTicket_Field();
        $ticket_ticket = new Ticket_Ticket();


        // Try to convert name
        $ticket->input["name"] = addslashes(str_replace(self::$PARENT_PREFIX .
            Dropdown::getDropdownName($this->getTable(), $metademands_id) . '&nbsp;:&nbsp;', '', $ticket->fields["name"]));
        if ($ticket->input["name"] == $ticket->fields["name"]) {
            $ticket->input["name"] = addslashes(str_replace(self::$PARENT_PREFIX, '', $ticket->fields["name"]));
        }

        // Delete metademand linked to the ticket
        $ticket_metademand->deleteByCriteria(['tickets_id' => $tickets_id]);
        $ticket_field->deleteByCriteria(['tickets_id' => $tickets_id]);
        $ticket_ticket->deleteByCriteria(['tickets_id_1' => $tickets_id]);

        // For each sons tickets linked to metademand
        $tickets_found = PluginMetademandsTicket::getSonTickets($tickets_id, $metademands_id, [], true);
        foreach ($tickets_found as $value) {
            // If son is a metademand : recursive call
            if (isset($value['metademands_id'])) {
                $son_metademands_ticket = new Ticket();
                $son_metademands_ticket->getFromDB($value['tickets_id']);
                //TODO To translate ?
                $son_metademands_ticket->input = $son_metademands_ticket->fields;
                $this->convertMetademandToTicket($son_metademands_ticket, $value['metademands_id']);
                $son_metademands_ticket->fields["name"] = addslashes(str_replace(self::$PARENT_PREFIX, '', $ticket->input["name"]));
                $son_metademands_ticket->updateInDB(['name']);
            } elseif (!empty($value['tickets_id'])) {
                // Try to convert name
                $son_ticket = new Ticket();
                $son_ticket->getFromDB($value['tickets_id']);
                //TODO To translate ?
                $son_ticket->fields["name"] = addslashes(str_replace(self::$SON_PREFIX, '', $son_ticket->fields["name"]));
                $son_ticket->updateInDB(['name']);

                // Delete links
                $ticket_task->deleteByCriteria(['tickets_id' => $value['tickets_id']]);
                $ticket_metademand->deleteByCriteria(['tickets_id' => $value['tickets_id']]);
                $ticket_field->deleteByCriteria(['tickets_id' => $value['tickets_id']]);
                $ticket_ticket->deleteByCriteria(['tickets_id_1' => $value['tickets_id']]);
            }
        }
    }

    /**
     * @param       $metademands_id
     * @param       $values
     * @param array $options
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public function addObjects($metademands_id, $values, $options = [])
    {
        global $PLUGIN_HOOKS;

//        Toolbox::logInfo($metademands_id);
//        Toolbox::logInfo($values);
//        Toolbox::logInfo($options);

        $tasklevel = 1;

        $metademands_data = $this->constructMetademands($metademands_id);
        $this->getFromDB($metademands_id);

        if (!$this->fields['object_to_create']
            || !getItemForItemtype($this->fields['object_to_create'])) {
            return false;
        }
        $object_class = $this->fields['object_to_create'];
        $object = new $object_class();

        $ticket_metademand = new PluginMetademandsTicket_Metademand();
        $ticket_field = new PluginMetademandsTicket_Field();
        $ticket_ticket = new Ticket_Ticket();
        $KO = [];
        $ancestor_tickets_id = 0;
        $ticket_exists_array = [];
        $config = $this->getConfig();

        $itilcategory = 0;
        if (isset($values['field_plugin_servicecatalog_itilcategories_id'])) {
            $itilcategory = $values['field_plugin_servicecatalog_itilcategories_id'];
        }

        if (count($metademands_data)) {
            foreach ($metademands_data as $form_step => $data) {
                $docitem = null;
                foreach ($data as $form_metademands_id => $line) {
                    if ($object_class == 'Ticket') {
                        $noChild = false;
                        if ($ancestor_tickets_id > 0) {
                            // Skip ticket creation if not allowed by metademand form
                            $metademandtasks_tasks_ids = PluginMetademandsMetademandTask::getMetademandTask_TaskId($form_metademands_id);
                            //                  foreach ($metademandtasks_tasks_ids as $metademandtasks_tasks_id) {
                            if (!PluginMetademandsTicket_Field::checkTicketCreation($metademandtasks_tasks_ids, $ancestor_tickets_id)) {
                                $noChild = true;
                            }
                            //                  }
                        } else {
                            $values['fields']['tickets_id'] = 0;
                        }
                        if ($noChild) {
                            continue;
                        }
                    }
                    $metademand = new self();
                    $metademand->getFromDB($form_metademands_id);

                    // Create parent ticket
                    // Get form fields
//                    $parent_fields['content'] = '';

                    if ($metademand->fields['is_order'] == 0) {
                        if (count($line['form'])
                            && isset($values['fields'])) {
                            $forms_id = 0;
                            if (isset($_SESSION['plugin_metademands'][$form_metademands_id]['form_to_compare'])) {
                                $forms_id = $_SESSION['plugin_metademands'][$form_metademands_id]['form_to_compare'];
                            } elseif (isset($values['plugin_metademands_forms_id'])) {
                                $forms_id = $values['plugin_metademands_forms_id'];
                            }
                            if ($config['show_form_changes'] && $forms_id > 0) {
                                foreach ($values['fields'] as $idField => $valueField) {
                                    $diffRemove = "";
                                    $oldFormValues = new PluginMetademandsForm_Value();
                                    if ($oldFormValues->getFromDBByCrit(['plugin_metademands_forms_id' => $forms_id,
                                        'plugin_metademands_fields_id' => $idField])) {
                                        $jsonDecode = json_decode($oldFormValues->getField('value'), true);
                                        if (is_array($jsonDecode)) {
                                            if (empty($valueField)) {
                                                $valueField = [];
                                            }
                                            $diffAdd = array_diff($valueField, $jsonDecode);
                                            $diffRemove = array_diff($jsonDecode, $valueField);
                                        } elseif (is_array($oldFormValues->getField('value'))) {
                                            if (empty($valueField)) {
                                                $valueField = [];
                                            }
                                            $diffRemove = array_diff($oldFormValues->getField('value'), $valueField);
                                            $diffAdd = array_diff($valueField, $oldFormValues->getField('value'));
                                        } elseif ($oldFormValues->getField('value') != $valueField) {
                                            $values['fields'][$idField . '#orange'] = $valueField;
                                        }
                                        if ($oldFormValues->getField('value') == $valueField ||
                                            (isset($diffRemove) && empty($diffRemove) && empty($diffAdd))) {
                                            unset($values['fields'][$idField]);
                                        } else {
                                            if (isset($diffRemove) && !empty($diffRemove)) {
                                                if (!empty($diffAdd)) {
                                                    $values['fields'][$idField . '#green'] = $diffAdd;
                                                }
                                                $values['fields'][$idField . '#red'] = $diffRemove;
                                            } elseif (!isset($values['fields'][$idField . '#orange'])) {
                                                $values['fields'][$idField . '#green'] = $valueField;
                                            }
                                        }
                                    }
                                }
                            }
                            unset($_SESSION['plugin_metademands'][$form_metademands_id]['form_to_compare']);
                            $values_form[0] = $values['fields'];
                            $parent_fields = $this->formatFields($line['form'], $metademands_id, $values_form, $options);
                            $parent_fields['content'] = Html::cleanPostForTextArea($parent_fields['content']);
                        }
                    } elseif ($metademand->fields['is_order'] == 1) {
                        if ($metademand->fields['create_one_ticket'] == 0) {
                            //create one ticket for each basket
                            $values_form[0] = isset($values['basket']) ? $values['basket'] : [];
                            foreach ($values_form[0] as $id => $value) {
                                if (isset($line['form'][$id]['item'])
                                    && $line['form'][$id]['item'] == "ITILCategory_Metademands") {
                                    $itilcategory = $value;
                                }
                            }
                        } else {
                            //create one ticket for all basket
                            $values_form = isset($values['basket']) ? $values['basket'] : [];
                            foreach ($values_form as $id => $value) {
                                if (isset($line['form'][$id]['item'])
                                    && $line['form'][$id]['item'] == "ITILCategory_Metademands") {
                                    $itilcategory = $value;
                                }
                            }
                        }

                        $parent_fields = $this->formatFields($line['form'], $metademands_id, $values_form, $options);
                        $parent_fields['content'] = Html::cleanPostForTextArea($parent_fields['content']);
                    }

                    foreach ($values['fields'] as $id => $datav) {
                        $metademands_fields = new PluginMetademandsField();
                        if (strpos($id, '-2')) {
                            $id = str_replace("-2", "", $id);
                        }
                        if ($metademands_fields->getFromDB($id)) {
                            switch ($metademands_fields->fields['item']) {
                                case 'ITILCategory_Metademands':
                                    $parent_fields['itilcategories_id'] = $datav;
                                    if ($itilcategory > 0) {
                                        $parent_fields['itilcategories_id'] = $itilcategory;
                                    }
                                    break;
                            }

                            if (isset($metademands_fields->fields['users_id_validate'])
                                && !empty($metademands_fields->fields['users_id_validate'])) {
                                if (isset($metademands_fields->fields['check_value']) && is_array($datav)) {
                                    $checkeValue = json_decode($metademands_fields->fields['check_value'], 1);
                                    $usersValidate = json_decode($metademands_fields->fields['users_id_validate'], 1);
                                    foreach ($checkeValue as $key => $checkVal) {
                                        if (in_array($checkVal, $datav)) {
                                            $add_validation = '0';
                                            $validatortype = 'user';
                                            $users_id_validate[] = $usersValidate[$key];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (empty($n = PluginMetademandsMetademand::displayField($form_metademands_id, 'name'))) {
                        $n = Dropdown::getDropdownName($this->getTable(), $form_metademands_id);
                    }

                    $parent_fields['name'] = self::$PARENT_PREFIX .
                        $n;
                    if ($object_class == 'Ticket') {
                        $parent_fields['type'] = $this->fields['type'];
                        // Existing tickets id field
                        $parent_fields['id'] = $values['fields']['tickets_id'];
                    }
                    $parent_fields['entities_id'] = $_SESSION['glpiactive_entity'];

                    $parent_fields['status'] = CommonITILObject::INCOMING;

                    // Resources id
                    if (!empty($options['resources_id'])) {
                        $parent_fields['items_id'] = ['PluginResourcesResource' => [$options['resources_id']]];
                    }

                    // Requester user field
                    //TODO Add options ?
                    if (isset($values['fields']['_users_id_requester'])) {
                        $parent_fields['_users_id_requester'] = $values['fields']['_users_id_requester'];
                        if ($values['fields']['_users_id_requester'] != Session::getLoginUserID()) {
                            $parent_fields['_users_id_observer'] = Session::getLoginUserID();
                        }
                    }
                    // Add requester if empty
                    $parent_fields['_users_id_requester'] = isset($parent_fields['_users_id_requester']) ? $parent_fields['_users_id_requester'] : "";
                    if (empty($parent_fields['_users_id_requester'])) {
                        $parent_fields['_users_id_requester'] = Session::getLoginUserID();
                    }

//                    $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $parent_fields['entities_id'], '', 1);
//                    $parent_fields['_users_id_requester_notif'] = ['use_notification' => $default_use_notif,
//                        'alternative_email' => ''];


                    // Get predefined ticket fields
                    //TODO Add check if metademand fields linked to a ticket field with used_by_ticket ?
                    $parent_ticketfields = [];
                    if ($object_class == 'Ticket') {
                        //TODO Change / problem ?
                        $parent_ticketfields = $this->formatTicketFields($form_metademands_id, $itilcategory, $values, $parent_fields['_users_id_requester']);
                    }

                    $list_fields = $line['form'];
                    $searchOption = Search::getOptions($object_class);
                    foreach ($list_fields as $id => $fields_values) {
                        if ($fields_values['used_by_ticket'] > 0) {
                            foreach ($values_form as $k => $v) {
                                if (isset($v[$id])) {
                                    $name = $searchOption[$fields_values['used_by_ticket']]['linkfield'];
                                    if ($fields_values['used_by_ticket'] == 4) {
                                        $name = "_users_id_requester";
                                    }
                                    if ($fields_values['used_by_ticket'] == 71) {
                                        $name = "_groups_id_requester";
                                    }
                                    if ($fields_values['used_by_ticket'] == 66) {
                                        $name = "_users_id_observer";
                                    }
                                    if ($fields_values['used_by_ticket'] == 65) {
                                        $name = "_groups_id_observer";
                                    }

                                    $parent_fields[$name] = $v[$id];
                                    $parent_ticketfields[$name] = $v[$id];
                                    if ($fields_values['used_by_ticket'] == 59) {
                                        $parent_fields["_add_validation"] = '0';
                                        $parent_ticketfields["_add_validation"] = '0';
                                        $parent_fields["validatortype"] = 'user';
                                        $parent_ticketfields["validatortype"] = 'user';
                                        $parent_fields["users_id_validate"] = [$v[$id]];
                                        $parent_ticketfields["users_id_validate"] = [$v[$id]];
                                    }
                                    if ($fields_values['used_by_ticket'] == 13) {
                                        if ($fields_values['type'] == "dropdown_meta"
                                            && $fields_values["item"] == "mydevices") {
                                            $item = explode('_', $v[$id]);
                                            $parent_fields["items_id"] = [$item[0] => [$item[1]]];
                                        }
                                        if ($fields_values['type'] == "dropdown_object"
                                            && Ticket::isPossibleToAssignType($fields_values["item"])) {
                                            $parent_fields["items_id"] = [$fields_values["item"] => [$v[$id]]];
                                        }
                                        if ($fields_values['type'] == "dropdown_multiple"
//                                            && Ticket::isPossibleToAssignType("Appliance")
                                            && $fields_values["item"] == "Appliance") {
                                            foreach ($v[$id] as $k => $items_id) {
                                                $parent_fields["items_id"] = ['Appliance' => [$items_id]];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // If requester is different of connected user : Force his requester group on ticket
                    //TODO Add options ?
                    //               if (isset($parent_fields['_users_id_requester'])
                    //                   && $parent_fields['_users_id_requester'] != Session::getLoginUserID()) {
                    //                  $query  = "SELECT `glpi_groups`.`id` AS _groups_id_requester
                    //                           FROM `glpi_groups_users`
                    //                           LEFT JOIN `glpi_groups`
                    //                             ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                    //                           WHERE `glpi_groups_users`.`users_id` = " . $parent_fields['_users_id_requester'] . "
                    //                           AND `glpi_groups`.`is_requester` = 1
                    //                           LIMIT 1";
                    //                  $result = $DB->query($query);
                    //                  if ($DB->numrows($result)) {
                    //                     $groups_id_requester                   = $DB->result($result, 0, '_groups_id_requester');
                    //                     $parent_fields['_groups_id_requester'] = $groups_id_requester;
                    //                  }
                    //               }
                    // Affect requester group to son metademand
                    //               if ($form_metademands_id != $metademands_id) {
                    //                  $groups_id_assign = PluginMetademandsTicket::getUsedActors($ancestor_tickets_id,
                    //                                                                             CommonITILActor::ASSIGN,
                    //                                                                             'groups_id');
                    //                  if (count($groups_id_assign)) {
                    //                     $parent_fields['_groups_id_requester'] = $groups_id_assign[0];
                    //                  }
                    //               }
                    //END TODO Add options

                    if (isset($users_id_validate)) {
                        $parent_fields["_add_validation"] = $add_validation;
                        $parent_ticketfields["_add_validation"] = $add_validation;
                        $parent_fields["validatortype"] = $validatortype;
                        $parent_ticketfields["validatortype"] = $validatortype;
                        if (isset($parent_fields["users_id_validate"])) {
                            $parent_fields["users_id_validate"] = array_merge($parent_fields["users_id_validate"], $users_id_validate);
                            $parent_ticketfields["users_id_validate"] = array_merge($parent_ticketfields["users_id_validate"], $users_id_validate);
                        } else {
                            $parent_fields["users_id_validate"] = $users_id_validate;
                            $parent_ticketfields["users_id_validate"] = $users_id_validate;
                        }
                    }

                    // Case of simple ticket convertion
                    // Ticket does not exist : ADD
                    $ticket_exists = false;

                    if (empty($parent_fields['id'])) {
                        unset($parent_fields['id']);

                        if ($object_class == 'Ticket') {
                            $input = $this->mergeFields($parent_fields, $parent_ticketfields);
                        } else {
                            $input = $parent_fields;
                        }

                        if ($metademand->fields['is_order'] == 0) {
                            if (isset($values['fields']['files'][$form_metademands_id]['_filename'])) {
                                $input['_filename'] = $values['fields']['files'][$form_metademands_id]['_filename'];
                            }
                            if (isset($values['fields']['files'][$form_metademands_id]['_prefix_filename'])) {
                                $input['_prefix_filename'] = $values['fields']['files'][$form_metademands_id]['_prefix_filename'];
                            }
                            if (isset($values['fields']['files'][$form_metademands_id]['_tag_filename'])) {
                                $input['_tag_filename'] = $values['fields']['files'][$form_metademands_id]['_tag_filename'];
                            }
                        } else {
                            if (isset($values['fields']['files'][$form_metademands_id]['_filename'])) {
                                $input['_filename'] = $values['fields']['_filename'];
                            }
                            if (isset($values['fields']['files'][$form_metademands_id]['_prefix_filename'])) {
                                $input['_prefix_filename'] = $values['fields']['_prefix_filename'];
                            }
                            if (isset($values['fields']['files'][$form_metademands_id]['_tag_filename'])) {
                                $input['_tag_filename'] = $values['fields']['_tag_filename'];
                            }
                        }

                        if ($itilcategory > 0) {
                            $input['itilcategories_id'] = $itilcategory;
                        } else {
                            $cats = json_decode($this->fields['itilcategories_id'], true);
                            if (is_array($cats) && count($cats) == 1) {
                                foreach ($cats as $cat) {
                                    $input['itilcategories_id'] = $cat;
                                }
                            }
                        }
                        $inputFieldMain = [];
                        if (Plugin::isPluginActive('fields')) {
                            $pluginfield = new PluginMetademandsPluginfields();
                            $pluginfields = $pluginfield->find(['plugin_metademands_metademands_id' => $form_metademands_id]);
                            foreach ($pluginfields as $plfield) {
                                $fields_field = new PluginFieldsField();
                                $fields_container = new PluginFieldsContainer();
                                if ($fields_field->getFromDB($plfield['plugin_fields_fields_id'])) {
                                    if ($fields_container->getFromDB($fields_field->fields['plugin_fields_containers_id'])) {
                                        if ($fields_container->fields['type'] == 'dom') {
                                            if (isset($values['fields'][$plfield['plugin_metademands_fields_id']])) {
                                                if ($fields_field->fields['type'] == 'dropdown') {
                                                    if ($values['fields'][$plfield['plugin_metademands_fields_id']] > 0) {
                                                        $input["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                        $inputFieldMain["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                    }
                                                } elseif ($fields_field->fields['type'] == 'yesno') {
                                                    $input[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']] - 1;
                                                    $inputFieldMain[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']] - 1;
                                                } else {
                                                    $input[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                    $inputFieldMain[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($input['items_id']['PluginResourcesResource'])) {
                            $resource = new PluginResourcesResource();
                            foreach ($input['items_id']['PluginResourcesResource'] as $resource_id) {
                                if ($resource->getFromDB($resource_id)) {
                                    $input['name'] .= " " . $resource->fields['name'] . " " . $resource->fields['firstname'];
                                }
                            }
                        }
                        if ($input['name'] == 0 || $input['name'] == "0" || empty($input['name'])) {
                            $input['name'] = Dropdown::getDropdownName($this->getTable(), $form_metademands_id);
                        }
                        $input['name'] = Glpi\RichText\RichText::getTextFromHtml($input['name']);
                        $input = Toolbox::addslashes_deep($input);


                        //ADD TICKET
                        if (isset($options['current_ticket_id'])
                            && $options['current_ticket_id'] > 0
                            && !$options['meta_validated']) {
                            $inputUpdate['id'] = $options['current_ticket_id'];
                            $inputUpdate['content'] = $input['content'];
                            $inputUpdate['name'] = $input['name'];
                            $parent_tickets_id = $inputUpdate['id'];
                            $object->update($inputUpdate);
                            $object->getFromDB($inputUpdate['id']);
                            $ticket_exists_array[] = 1;
                        } else {
                            $parent_tickets_id = $object->add($input);
                        }


                        //delete drafts
                        if (isset($_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_drafts_id'])) {
                            $draft = new PluginMetademandsDraft();
                            $draft->deleteByCriteria(['id' => $_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_drafts_id']]);
                        }
                        //Link object to forms_id
                        if (isset($_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_forms_id'])) {
                            $form = new PluginMetademandsForm();
                            $form->update(['id' => $_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_forms_id'],
                                'items_id' => $parent_tickets_id,
                                'itemtype' => $object_class]);
                        }
                        $inputField = [];
                        if (Plugin::isPluginActive('fields')) {
                            $inputField = [];
                            $pluginfield = new PluginMetademandsPluginfields();
                            $pluginfields = $pluginfield->find(['plugin_metademands_metademands_id' => $form_metademands_id]);
                            foreach ($pluginfields as $plfield) {
                                $fields_field = new PluginFieldsField();
                                $fields_container = new PluginFieldsContainer();
                                if ($fields_field->getFromDB($plfield['plugin_fields_fields_id'])) {
                                    if ($fields_container->getFromDB($fields_field->fields['plugin_fields_containers_id'])) {
                                        if ($fields_container->fields['type'] == 'tab') {
                                            if (isset($values['fields'][$plfield['plugin_metademands_fields_id']])) {
                                                if ($fields_field->fields['type'] == 'dropdown') {
                                                    if ($values['fields'][$plfield['plugin_metademands_fields_id']] > 0) {
                                                        $inputField[$fields_field->fields['plugin_fields_containers_id']]["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                    }
                                                } elseif ($fields_field->fields['type'] == 'yesno') {
                                                    $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']] - 1;
                                                } else {
                                                    $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            foreach ($inputField as $containers_id => $vals) {
                                $container = new PluginFieldsContainer;
                                $vals['plugin_fields_containers_id'] = $containers_id;
                                $vals['itemtype'] = $object_class;
                                $vals['items_id'] = $parent_tickets_id;
                                $container->updateFieldsValues($vals, $object_class, false);
                            }
                        }
                        //Hook to do action after ticket creation with metademands
                        if (isset($PLUGIN_HOOKS['metademands'])) {
                            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                                $p = [];
                                $options["tickets_id"] = $parent_tickets_id;
                                $p["options"] = $options;
                                $p["values"] = $values;
                                $p["line"] = $line;

                                $new_res = PluginMetademandsMetademand::getPluginAfterCreateTicket($plug, $p);
                            }
                        }

                        if ($docitem == null && $config['create_pdf']) {
                            //document PDF Generation
                            //TODO TO Tranlate
                            if (empty($n = PluginMetademandsMetademand::displayField($this->getID(), 'name'))) {
                                $n = $this->getName();
                            }

                            if (empty($comm = PluginMetademandsMetademand::displayField($this->getID(), 'comment'))) {
                                $comm = $this->getField("comment");
                            }
                            $docPdf = new PluginMetaDemandsMetaDemandPdf($n, $comm);
                            if ($metademand->fields['is_order'] == 0) {
                                $values_form['0'] = isset($values) ? $values : [];
                                $docPdf->drawPdf($line['form'], $values_form, false);
                            } elseif ($metademand->fields['is_order'] == 1) {
                                if ($metademand->fields['create_one_ticket'] == 0) {
                                    //create one ticket for each basket
                                    $values_form['0'] = isset($values) ? $values : [];
                                } else {
                                    //create one ticket for all basket
                                    $baskets = [];
                                    $values['basket'] = isset($values['basket']) ? $values['basket'] : [];
                                    foreach ($values['basket'] as $k => $v) {
                                        $baskets[$k]['basket'] = $v;
                                    }

                                    $values_form = $baskets;
                                }
                                $docPdf->drawPdf($line['form'], $values_form, true);
                            }
                            $docPdf->Close();
                            //TODO TO Tranlate
                            $name = PluginMetaDemandsMetaDemandPdf::cleanTitle($n);
                            $docitem = $docPdf->addDocument($name, $object_class, $object->getID(), $_SESSION['glpiactive_entity']);
                        }

                        // Ticket already exists
                    } else {
                        if ($object_class == 'Ticket') {
                            $parent_tickets_id = $parent_fields['id'];
                            $object->getFromDB($parent_tickets_id);
                            $parent_fields['content'] = $object->fields['content']
                                . "<br>" . $parent_fields['content'];
                            $parent_fields['name'] = Html::cleanPostForTextArea($parent_fields['name'])
                                . '&nbsp;:&nbsp;' . Html::cleanPostForTextArea($object->fields['name']);
                            $ticket_exists_array[] = 1;
                            $ticket_exists = true;
                            $values['fields']['tickets_id'] = 0;
                        }
                    }

                    //Prevent create subtickets
                    //               $tasks = [];
                    //               foreach ($values['fields'] as $key => $field) {
                    //                  $fieldDbtm = new PluginMetademandsField();
                    //                  if ($fieldDbtm->getFromDB($key)) {
                    //
                    //                     $check_value = $fieldDbtm->fields['check_value'];
                    //                     $type        = $fieldDbtm->fields['type'];
                    //                     $test    = PluginMetademandsTicket_Field::isCheckValueOK($field, $check_value, $type);
                    //                     $check[] = ($test == false) ? 0 : 1;
                    //                     if (in_array(0, $check)) {
                    //                        $tasks[] .= $fieldDbtm->fields['plugin_metademands_tasks_id'];
                    //                     }
                    //                  }
                    //               }
                    //
                    //               foreach ($tasks as $k => $task) {
                    //                  unset($line['tasks'][$task]);
                    //               }

                    if ($parent_tickets_id) {
                        // Create link for metademand task with ancestor metademand
                        if ($form_metademands_id == $metademands_id) {
                            $ancestor_tickets_id = $parent_tickets_id;
                        }

                        if ($object_class == 'Ticket') {
                            // Metademands - ticket relation
                            //TODO Change / problem ?
                            if (!$ticket_metademand->getFromDBByCrit(['tickets_id' => $parent_tickets_id,
                                'parent_tickets_id' => $ancestor_tickets_id,
                                'plugin_metademands_metademands_id' => $form_metademands_id,
                            ])) {
                                $ticket_metademand->add(['tickets_id' => $parent_tickets_id,
                                    'parent_tickets_id' => $ancestor_tickets_id,
                                    'plugin_metademands_metademands_id' => $form_metademands_id,
                                    'status' => PluginMetademandsTicket_Metademand::RUNNING]);
                            }

                            // Save all form values of the ticket
                            if (count($line['form']) && isset($values['fields'])) {
                                //TODO Change / problem ?
                                $ticket_field->deleteByCriteria(['tickets_id' => $parent_tickets_id]);
                                $ticket_field->setTicketFieldsValues($line['form'], $values['fields'], $parent_tickets_id);
                            }

                            if (!empty($ancestor_tickets_id) && $object_class == 'Ticket') {
                                // Add son link to parent
                                $ticket_ticket->add(['tickets_id_1' => $parent_tickets_id,
                                    'tickets_id_2' => $ancestor_tickets_id,
                                    'link' => Ticket_Ticket::SON_OF]);
                                $ancestor_tickets_id = $parent_tickets_id;
                            }
                        }
                        //create tasks (for problem / change)
                        if ($object_class == 'Problem' || $object_class == 'Change') {
                            $meta_tasks = $line['tasks'];
                            if (is_array($meta_tasks)) {
                                foreach ($meta_tasks as $meta_task) {
                                    if (PluginMetademandsTicket_Field::checkTicketCreation($meta_task['tasks_id'], $parent_tickets_id)) {
                                        $input = [];
                                        if ($object_class == 'Problem') {
                                            $task = new ProblemTask();
                                            $input['problems_id'] = $parent_tickets_id;
                                        } else {
                                            $task = new ChangeTask();
                                            $input['changes_id'] = $parent_tickets_id;
                                        }
                                        $input['content'] = Toolbox::addslashes_deep($meta_task['tickettasks_name']) . " " . Toolbox::addslashes_deep($meta_task['content']);
                                        $input['groups_id_tech'] = $meta_task["groups_id_assign"];
                                        $input['users_id_tech'] = $meta_task["users_id_assign"];
                                        $task->add($input);
                                    }
                                }
                            }
                        }
                        // Create sons tickets
                        if ($object_class == 'Ticket') {
                            if (isset($line['tasks'])
                                && is_array($line['tasks'])
                                && count($line['tasks'])) {
                                //                     $line['tasks'] = $this->checkTaskAllowed($metademands_id, $values, $line['tasks']);

                                if ($this->fields["validation_subticket"] == 0) {
                                    $ticket2 = new Ticket();
                                    $ticket2->getFromDB($parent_tickets_id);
                                    $parent_fields["requesttypes_id"] = $ticket2->fields['requesttypes_id'];
                                    foreach ($line['tasks'] as $key => $l) {
                                        //replace #id# in title with the value
                                        do {
                                            $match = $this->getBetween($l['tickettasks_name'], '[', ']');
                                            if (empty($match)) {
                                                $explodeTitle = [];
                                                $explodeTitle = explode("#", $l['tickettasks_name']);
                                                foreach ($explodeTitle as $title) {
                                                    if (isset($values['fields'][$title])) {
                                                        $field = new PluginMetademandsField();
                                                        $field->getFromDB($title);
                                                        $fields = $field->fields;
                                                        $fields['value'] = '';

                                                        $fields['value'] = $values['fields'][$title];

                                                        $fields['value2'] = '';
                                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                                        }
                                                        $result = [];
                                                        $result['content'] = "";
                                                        $result[$fields['rank']]['content'] = "";
                                                        $result[$fields['rank']]['display'] = false;
                                                        $parent_fields_id = 0;
                                                        $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                                    } else {
                                                        $explodeTitle2 = explode(".", $title);

                                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                                            $field_object = new PluginMetademandsField();
                                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                                    $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $line['tasks'][$key]['tickettasks_name']);
                                                                }
                                                            }
                                                        }
                                                        $users_id = $parent_fields['_users_id_requester'];
                                                        $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($title, $users_id, $title, $line['tasks'][$key]['tickettasks_name'], true);
                                                    }
                                                }
                                            } else {
                                                $explodeVal = [];
                                                $explodeVal = explode("|", $match);
                                                $find = false;
                                                $val_to_replace = "";
                                                foreach ($explodeVal as $str) {
                                                    $explodeTitle = explode("#", $str);
                                                    foreach ($explodeTitle as $title) {
                                                        if (isset($values['fields'][$title])) {
                                                            $field = new PluginMetademandsField();
                                                            $field->getFromDB($title);
                                                            $fields = $field->fields;
                                                            $fields['value'] = '';

                                                            $fields['value'] = $values['fields'][$title];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                            $str = str_replace("#" . $title . "#", $value, $str);
                                                            if (!is_null($value) && !empty($value)) {
                                                                $find = true;
                                                            }
                                                        } else {
                                                            $explodeTitle2 = explode(".", $title);

                                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                $field_object = new PluginMetademandsField();
                                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                                        $str = self::getContentForUser($explodeTitle2[1], $users_id, $title, $str);
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $str = self::getContentForUser($title, $users_id, $title, $str, true);
                                                        }
                                                    }
                                                    if ($find == true) {
                                                        break;
                                                    }
                                                }

                                                if (str_contains($match, "#")) {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['tickettasks_name']);
                                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", $str, $l['tickettasks_name']);
                                                } else {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['tickettasks_name']);
                                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['tickettasks_name']);
                                                }
                                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                            }
                                        } while (!empty($match));

                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("<@", "[", $line['tasks'][$key]['tickettasks_name']);
                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("@>", "]", $line['tasks'][$key]['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                                        $explodeTitle = explode("#", $l['tickettasks_name']);
                                        foreach ($explodeTitle as $title) {
                                            if (isset($values['fields'][$title])) {
                                                $field = new PluginMetademandsField();
                                                $field->getFromDB($title);
                                                $fields = $field->fields;
                                                $fields['value'] = '';

                                                $fields['value'] = $values['fields'][$title];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval'
                                                        || $fields['type'] == 'datetime_interval')
                                                    && isset($values['fields'][$title . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                }
                                                $result = [];
                                                $result['content'] = "";
                                                $result[$fields['rank']]['content'] = "";
                                                $result[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                            } else {
                                                $explodeTitle2 = explode(".", $title);

                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                    $field_object = new PluginMetademandsField();
                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                            $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $line['tasks'][$key]['tickettasks_name']);
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($title, $users_id, $title, $line['tasks'][$key]['tickettasks_name'], true);
                                            }
                                        }


                                        //replace #id# in content with the value
                                        do {
                                            $match = $this->getBetween($l['content'], '[', ']');
                                            if (empty($match) && $l['content'] != null) {
                                                $explodeContent = explode("#", $l['content']);
                                                foreach ($explodeContent as $content) {
                                                    if (isset($values['fields'][$content])) {
                                                        $field = new PluginMetademandsField();
                                                        $field->getFromDB($content);
                                                        $fields = $field->fields;
                                                        $fields['value'] = '';

                                                        $fields['value'] = $values['fields'][$content];

                                                        $fields['value2'] = '';
                                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                                        }
                                                        $result = [];
                                                        $result['content'] = "";
                                                        $result[$fields['rank']]['content'] = "";
                                                        $result[$fields['rank']]['display'] = false;
                                                        $parent_fields_id = 0;
                                                        $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                        if ($fields['type'] == "textarea") {
                                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                $value = str_replace("\\n", '","', $value);
                                                            }
                                                        }
                                                        $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                                    } else {
                                                        $explodeContent2 = explode(".", $content);

                                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                                            $field_object = new PluginMetademandsField();
                                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                                    $line['tasks'][$key]['content'] = self::getContentForUser($explodeContent2[1], $users_id, $content, $line['tasks'][$key]['content']);
                                                                }
                                                            }
                                                        }
                                                        $users_id = $parent_fields['_users_id_requester'];
                                                        $line['tasks'][$key]['content'] = self::getContentForUser($content, $users_id, $content, $line['tasks'][$key]['content'], true);
                                                    }
                                                }
                                            } else {
                                                $explodeVal = [];
                                                $explodeVal = explode("|", $match);
                                                $find = false;
                                                $val_to_replace = "";
                                                foreach ($explodeVal as $str) {
                                                    $explodeContent = explode("#", $str);
                                                    foreach ($explodeContent as $content) {
                                                        if (isset($values['fields'][$content])) {
                                                            $field = new PluginMetademandsField();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;
                                                            $fields['value'] = '';

                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }
                                                            $str = str_replace("#" . $content . "#", $value, $str);
                                                            if (!is_null($value) && !empty($value)) {
                                                                $find = true;
                                                            }
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new PluginMetademandsField();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $str = self::getContentForUser($explodeContent2[1], $users_id, $content, $str);
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $str = self::getContentForUser($content, $users_id, $content, $str, true);
                                                        }
                                                    }
                                                    if ($find == true) {
                                                        break;
                                                    }
                                                }
                                                //                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                                if (str_contains($match, "#")) {
                                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                                    $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                                                } else {
                                                    if ($line['tasks'][$key]['content'] != null) {
                                                        $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['content']);
                                                    }
                                                    if ($l['content'] != null) {
                                                        $l['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['content']);
                                                    }
                                                }
                                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                            }
                                        } while (!empty($match));

                                        if ($line['tasks'][$key]['content'] != null) {
                                            $line['tasks'][$key]['content'] = str_replace("<@", "[", $line['tasks'][$key]['content']);
                                            $line['tasks'][$key]['content'] = str_replace("@>", "]", $line['tasks'][$key]['content']);
                                        }
                                        if ($l['content'] != null) {
                                            $l['content'] = str_replace("<@", "[", $l['content']);
                                            $l['content'] = str_replace("@>", "]", $l['content']);
                                        }
                                        if ($l['content'] != null) {
                                            $explodeContent = explode("#", $l['content']);
                                            foreach ($explodeContent as $content) {
                                                if (isset($values['fields'][$content])) {
                                                    $field = new PluginMetademandsField();
                                                    $field->getFromDB($content);
                                                    $fields = $field->fields;
                                                    $fields['value'] = '';

                                                    $fields['value'] = $values['fields'][$content];

                                                    $fields['value2'] = '';
                                                    if (($fields['type'] == 'date_interval'
                                                            || $fields['type'] == 'datetime_interval')
                                                        && isset($values['fields'][$content . '-2'])) {
                                                        $fields['value2'] = $values['fields'][$content . '-2'];
                                                    }
                                                    $result = [];
                                                    $result['content'] = "";
                                                    $result[$fields['rank']]['content'] = "";
                                                    $result[$fields['rank']]['display'] = false;
                                                    $parent_fields_id = 0;
                                                    $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                    if ($fields['type'] == "textarea") {
                                                        if ($line['tasks'][$key]["formatastable"] == 0) {
                                                            $value = str_replace("\\n", '","', $value);
                                                        }
                                                    }
                                                    $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                                } else {
                                                    $explodeContent2 = explode(".", $content);

                                                    if (isset($values['fields'][$explodeContent2[0]])) {
                                                        $field_object = new PluginMetademandsField();
                                                        if ($field_object->getFromDB($explodeContent2[0])) {
                                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                $users_id = $values['fields'][$explodeContent2[0]];
                                                                $line['tasks'][$key]['content'] = self::getContentForUser($explodeContent2[1], $users_id, $content, $line['tasks'][$key]['content']);
                                                            }
                                                        }
                                                    }
                                                    $users_id = $parent_fields['_users_id_requester'];
                                                    $line['tasks'][$key]['content'] = self::getContentForUser($content, $users_id, $content, $line['tasks'][$key]['content'], true);
                                                }
                                            }
                                        }
                                    }

                                    if ($metademand->fields['force_create_tasks'] == 0) {
                                        if (!$this->createSonsTickets(
                                            $parent_tickets_id,
                                            $this->mergeFields(
                                                $parent_fields,
                                                $parent_ticketfields
                                            ),
                                            $parent_tickets_id,
                                            $line['tasks'],
                                            $tasklevel,
                                            $inputField,
                                            $inputFieldMain
                                        )) {
                                            $KO[] = 1;
                                        }
                                    } else {
                                        $meta_tasks = $line['tasks'];
                                        if (is_array($meta_tasks)) {
                                            foreach ($meta_tasks as $meta_task) {
                                                if (PluginMetademandsTicket_Field::checkTicketCreation($meta_task['tasks_id'], $parent_tickets_id)) {
                                                    $ticket_task = new TicketTask();
                                                    $input = [];
                                                    $input['content'] = Toolbox::addslashes_deep($meta_task['tickettasks_name']) . " " . Toolbox::addslashes_deep($meta_task['content']);
                                                    $input['tickets_id'] = $parent_tickets_id;
                                                    $input['groups_id_tech'] = $meta_task["groups_id_assign"];
                                                    $input['users_id_tech'] = $meta_task["users_id_assign"];
                                                    if (!$ticket_task->add($input)) {
                                                        $KO[] = 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $metaValid = new PluginMetademandsMetademandValidation();
                                    $paramIn["tickets_id"] = $parent_tickets_id;
                                    $paramIn["plugin_metademands_metademands_id"] = $metademands_id;
                                    $paramIn["users_id"] = 0;
                                    $paramIn["validate"] = PluginMetademandsMetademandValidation::TO_VALIDATE;
                                    $paramIn["date"] = date("Y-m-d H:i:s");

                                    foreach ($line['tasks'] as $key => $l) {
                                        //replace #id# in title with the value
                                        do {
                                            if (isset($resource_id)) {
                                                $resource = new PluginResourcesResource();
                                                if ($resource->getFromDB($resource_id)) {
                                                    $line['tasks'][$key]['tickettasks_name'] .= " - " . $resource->getField('name') . " " . $resource->getField('firstname');
                                                }
                                                $line['tasks'][$key]['items_id'] = ['PluginResourcesResource' => [$resource_id]];
                                            }
                                            $match = $this->getBetween($l['tickettasks_name'], '[', ']');
                                            if (empty($match)) {
                                                $explodeTitle = [];
                                                $explodeTitle = explode("#", $l['tickettasks_name']);
                                                foreach ($explodeTitle as $title) {
                                                    if (isset($values['fields'][$title])) {
                                                        $field = new PluginMetademandsField();
                                                        $field->getFromDB($title);
                                                        $fields = $field->fields;
                                                        $fields['value'] = '';

                                                        $fields['value'] = $values['fields'][$title];

                                                        $fields['value2'] = '';
                                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                                        }
                                                        $result = [];
                                                        $result['content'] = "";
                                                        $result[$fields['rank']]['content'] = "";
                                                        $result[$fields['rank']]['display'] = false;
                                                        $parent_fields_id = 0;
                                                        $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                        if ($value != null) {
                                                            $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                                        }

                                                    } else {
                                                        $explodeTitle2 = explode(".", $title);

                                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                                            $field_object = new PluginMetademandsField();
                                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                                    $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $line['tasks'][$key]['tickettasks_name']);
                                                                }
                                                            }
                                                        }
                                                        $users_id = $parent_fields['_users_id_requester'];
                                                        $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($title, $users_id, $title, $line['tasks'][$key]['tickettasks_name'], true);
                                                    }
                                                }
                                            } else {
                                                $explodeVal = [];
                                                $explodeVal = explode("|", $match);
                                                $find = false;
                                                $val_to_replace = "";
                                                foreach ($explodeVal as $str) {
                                                    $explodeTitle = explode("#", $str);
                                                    foreach ($explodeTitle as $title) {
                                                        if (isset($values['fields'][$title])) {
                                                            $field = new PluginMetademandsField();
                                                            $field->getFromDB($title);
                                                            $fields = $field->fields;
                                                            $fields['value'] = '';

                                                            $fields['value'] = $values['fields'][$title];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                            $str = str_replace("#" . $title . "#", $value, $str);
                                                            if (!is_null($value) && !empty($value)) {
                                                                $find = true;
                                                            }
                                                        } else {
                                                            $explodeTitle2 = explode(".", $title);

                                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                $field_object = new PluginMetademandsField();
                                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                                        $str = self::getContentForUser($explodeTitle2[1], $users_id, $title, $str);
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $str = self::getContentForUser($title, $users_id, $title, $str, true);
                                                        }
                                                    }
                                                    if ($find == true) {
                                                        break;
                                                    }
                                                }

                                                if (str_contains($match, "#")) {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['tickettasks_name']);
                                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", $str, $l['tickettasks_name']);
                                                } else {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['tickettasks_name']);
                                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['tickettasks_name']);
                                                }
                                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                            }
                                        } while (!empty($match));

                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("<@", "[", $line['tasks'][$key]['tickettasks_name']);
                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("@>", "]", $line['tasks'][$key]['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                                        $explodeTitle = explode("#", $l['tickettasks_name']);
                                        foreach ($explodeTitle as $title) {
                                            if (isset($values['fields'][$title])) {
                                                $field = new PluginMetademandsField();
                                                $field->getFromDB($title);
                                                $fields = $field->fields;
                                                $fields['value'] = '';

                                                $fields['value'] = $values['fields'][$title];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval'
                                                        || $fields['type'] == 'datetime_interval')
                                                    && isset($values['fields'][$title . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                }
                                                $result = [];
                                                $result['content'] = "";
                                                $result[$fields['rank']]['content'] = "";
                                                $result[$fields['rank']]['display'] = false;

                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                if ($value != null) {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                                }
                                            } else {
                                                $explodeTitle2 = explode(".", $title);

                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                    $field_object = new PluginMetademandsField();
                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                            $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $line['tasks'][$key]['tickettasks_name']);
                                                        }
                                                    }
                                                }

                                                $users_id = $parent_fields['_users_id_requester'];
                                                $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser($title, $users_id, $title, $line['tasks'][$key]['tickettasks_name'], true);
                                            }
                                        }

                                        //replace #id# in content with the value
                                        do {
                                            $match = $this->getBetween($l['content'], '[', ']');
                                            if (empty($match)) {
                                                if ($l['content'] != null) {
                                                    $explodeContent = explode("#", $l['content']);
                                                    foreach ($explodeContent as $content) {
                                                        if (isset($values['fields'][$content])) {
                                                            $field = new PluginMetademandsField();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;
                                                            $fields['value'] = '';

                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }
                                                            $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new PluginMetademandsField();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $line['tasks'][$key]['content'] = self::getContentForUser($explodeContent2[1], $users_id, $content, $line['tasks'][$key]['content']);
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $line['tasks'][$key]['content'] = self::getContentForUser($content, $users_id, $content, $line['tasks'][$key]['content'], true);
                                                        }
                                                    }
                                                }
                                            } else {
                                                $explodeVal = [];
                                                $explodeVal = explode("|", $match);
                                                $find = false;
                                                $val_to_replace = "";
                                                foreach ($explodeVal as $str) {
                                                    $explodeContent = explode("#", $str);
                                                    foreach ($explodeContent as $content) {
                                                        if (isset($values['fields'][$content])) {
                                                            $field = new PluginMetademandsField();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;
                                                            $fields['value'] = '';

                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }

                                                            $str = str_replace("#" . $content . "#", $value, $str);
                                                            if (!is_null($value) && !empty($value)) {
                                                                $find = true;
                                                            }
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new PluginMetademandsField();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $str = self::getContentForUser($explodeContent2[1], $users_id, $content, $str);
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $str = self::getContentForUser($content, $users_id, $content, $str, true);
                                                        }
                                                    }
                                                    if ($find == true) {
                                                        break;
                                                    }
                                                }

                                                if (str_contains($match, "#")) {
                                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                                    $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                                                } else {
                                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['content']);
                                                    $l['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['content']);
                                                }
                                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                            }
                                        } while (!empty($match));

                                        $line['tasks'][$key]['content'] = str_replace("<@", "[", $line['tasks'][$key]['content']);
                                        $line['tasks'][$key]['content'] = str_replace("@>", "]", $line['tasks'][$key]['content']);
                                        $l['content'] = str_replace("<@", "[", $l['content']);
                                        $l['content'] = str_replace("@>", "]", $l['content']);

                                        $explodeContent = explode("#", $l['content']);
                                        foreach ($explodeContent as $content) {
                                            if (isset($values['fields'][$content])) {
                                                $field = new PluginMetademandsField();
                                                $field->getFromDB($content);
                                                $fields = $field->fields;
                                                $fields['value'] = '';

                                                $fields['value'] = $values['fields'][$content];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval'
                                                        || $fields['type'] == 'datetime_interval')
                                                    && isset($values['fields'][$content . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$content . '-2'];
                                                }
                                                $result = [];
                                                $result['content'] = "";
                                                $result[$fields['rank']]['content'] = "";
                                                $result[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                                if ($fields['type'] == "textarea") {
                                                    if ($line['tasks'][$key]["formatastable"] == 0) {
                                                        $value = str_replace("\\n", '","', $value);
                                                    }
                                                }
                                                $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                            } else {
                                                $explodeContent2 = explode(".", $content);

                                                if (isset($values['fields'][$explodeContent2[0]])) {
                                                    $field_object = new PluginMetademandsField();
                                                    if ($field_object->getFromDB($explodeContent2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                            $users_id = $values['fields'][$explodeContent2[0]];
                                                            $line['tasks'][$key]['content'] = self::getContentForUser($explodeContent2[1], $users_id, $content, $line['tasks'][$key]['content']);
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $line['tasks'][$key]['content'] = self::getContentForUser($content, $users_id, $content, $line['tasks'][$key]['content'], true);
                                            }
                                        }
                                    }

                                    $tasks = $line['tasks'];
                                    foreach ($tasks as $key => $val) {
                                        if (PluginMetademandsTicket_Field::checkTicketCreation($val['tasks_id'], $parent_tickets_id)) {
                                            $tasks[$key]['tickettasks_name'] = addslashes(urlencode($val['tickettasks_name']));
                                            if (isset($input['items_id']['PluginResourcesResource'])) {
                                                if ($resource->getFromDB($resource_id)) {
                                                    $tasks[$key]['tickettasks_name'] .= " " . $resource->fields['name'] . " " . $resource->fields['firstname'];
                                                    $tasks[$key]['items_id'] = ['PluginResourcesResource' => [$resource_id]];
                                                }
                                            }
                                            if ($val['tasks_completename'] != null) {
                                                $tasks[$key]['tasks_completename'] = addslashes(urlencode($val['tasks_completename']));
                                            }
                                            $tasks[$key]['content'] = addslashes(urlencode($val['content']));
                                            $tasks[$key]['block_use'] = json_decode($val["block_use"], true);
                                        } else {
                                            unset($tasks[$key]);
                                        }
                                    }

                                    $paramIn["tickets_to_create"] = json_encode($tasks);
                                    if ($metaValid->getFromDBByCrit(['tickets_id' => $paramIn["tickets_id"]])) {
                                        $paramIn['id'] = $metaValid->getID();
                                        $metaValid->update($paramIn);
                                    } else {
                                        $metaValid->add($paramIn);
                                    }
                                }
                            } else {
                                if ($this->fields["validation_subticket"] == 1) {
                                    $metaValid = new PluginMetademandsMetademandValidation();
                                    $paramIn["tickets_id"] = $parent_tickets_id;
                                    $paramIn["plugin_metademands_metademands_id"] = $metademands_id;
                                    $paramIn["users_id"] = 0;
                                    $paramIn["validate"] = PluginMetademandsMetademandValidation::TO_VALIDATE_WITHOUTTASK;
                                    $paramIn["date"] = date("Y-m-d H:i:s");

                                    $paramIn["tickets_to_create"] = "";
                                    if ($metaValid->getFromDBByCrit(['tickets_id' => $paramIn["tickets_id"]])) {
                                        $paramIn['id'] = $metaValid->getID();
                                        $metaValid->update($paramIn);
                                    } else {
                                        $metaValid->add($paramIn);
                                    }
                                }
                            }

                            // Case of simple ticket convertion
                            if ($ticket_exists) {
                                if (isset($parent_ticketfields['_users_id_observer'])
                                    && !empty($parent_ticketfields['_users_id_observer'])) {
                                    $parent_ticketfields['_itil_observer'] = ['users_id' => $parent_ticketfields['_users_id_observer'],
                                        '_type' => 'user'];
                                }
                                if (isset($parent_ticketfields['_groups_id_observer'])
                                    && !empty($parent_ticketfields['_groups_id_observer'])) {
                                    $parent_ticketfields['_itil_observer'] = ['groups_id' => $parent_ticketfields['_groups_id_observer'],
                                        '_type' => 'group'];
                                }
                                if (isset($parent_ticketfields['_users_id_assign'])
                                    && !empty($parent_ticketfields['_users_id_assign'])) {
                                    $parent_ticketfields['_itil_assign'] = ['users_id' => $parent_ticketfields['_users_id_assign'],
                                        '_type' => 'user'];
                                }
                                if (isset($parent_ticketfields['_groups_id_assign'])
                                    && !empty($parent_ticketfields['_groups_id_assign'])) {
                                    $parent_ticketfields['_itil_assign'] = ['groups_id' => $parent_ticketfields['_groups_id_assign'],
                                        '_type' => 'group'];
                                }

                                $object->update($this->mergeFields($parent_fields, $parent_ticketfields));
                            }
                        }
                    } else {
                        $KO[] = 1;
                    }
                }
            }
        }

        // Message return
        $parent_metademands_name = Toolbox::stripslashes_deep($object->fields['name']);
        if (count($KO)) {
            $message = __('Demand add failed', 'metademands');
        } else {
            if (isset($_SESSION['plugin_metademands'])) {
                unset($_SESSION['plugin_metademands']);
            }
            if ($object_class == 'Ticket') {
                if (!in_array(1, $ticket_exists_array)) {
                    $message = sprintf(__('Demand "%s" added with success', 'metademands'),
                        "<a href='".Ticket::getFormURL()."?id=".$parent_tickets_id."'>".$parent_metademands_name."</a>");
                } else {
                    $message = sprintf(
                        __('%s %d successfully updated', 'metademands'),
                        $object::getTypeName(1),
                        $object->getID(),
                    );
                }
            } else {
                $message = sprintf(
                    __('%s %d successfully created'),
                    $object::getTypeName(1),
                    $object->getID(),
                );
            }
        }

        return ['message' => $message, 'id' => $ancestor_tickets_id];
    }


    /**
     * @param $parent_fields
     * @param $parent_ticketfields
     *
     * @return mixed
     */
    private function mergeFields($parent_fields, $parent_ticketfields)
    {
        foreach ($parent_ticketfields as $key => $val) {
            switch ($key) {
                //            case 'name' :
                //               $parent_fields[$key] .= ' ' . $val;
                //               break;c
                //            case 'content' :
                //               $parent_fields[$key] .= '\r\n' . $val;
                //               break;
                default:
                    $parent_fields[$key] = $val;
                    break;
            }
        }

        return $parent_fields;
    }

    /**
     * @param array $parent_fields
     * @param       $metademands_id
     * @param       $values_form
     * @param array $options
     *
     * @return array
     */
    public function formatFields(array $parent_fields, $metademands_id, $values_form, $options = [])
    {
        $config_data = PluginMetademandsConfig::getInstance();
        $langTech = $config_data['languageTech'];
        $result = [];
        $result['content'] = "";
        $parent_fields_id = 0;
        $colors = [];


        foreach ($values_form as $k => $values) {
            if (is_array($values) && $config_data['show_form_changes']) {
                foreach ($values as $key => $val) {
                    if (strpos($key, '#') > 0) {
                        $newKey = substr($key, 0, strpos($key, '#'));
                        $colors[$key] = $val;//substr($key,strpos($key,'#')+1);
                        unset($values_form[$k][$newKey]);
                    }
                }
            }
            if (empty($name = PluginMetademandsMetademand::displayField($metademands_id, 'name', $langTech))) {
                $name = Dropdown::getDropdownName($this->getTable(), $metademands_id);
            }
            if (!isset($options['formatastable']) || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                $result['content'] .= "<table class='tab_cadre' style='width: 100%;background:none;'>"; // class='mticket'
//                 $result['content'] .= "<tr><th colspan='2'>" . $name . "</th></tr>";
            }

            if (!empty($options['resources_id'])) {
                $resourceMeta = new PluginMetademandsMetademand_Resource();
                $result['content'] .= $resourceMeta::getTableResource($options);
            }
            //      $result['content'] .= "</table>";
            $resultTemp = [];
            $nb = 0;
            foreach ($parent_fields as $fields_id => $field) {
                if (!isset($resultTemp[$field['rank']])) {
                    $resultTemp[$field['rank']]['content'] = "";
                    $resultTemp[$field['rank']]['display'] = false;
                }
                $field['value'] = '';
                if (isset($values[$fields_id])) {
                    $field['value'] = $values[$fields_id];
                }
                $field['value2'] = '';
                if (($field['type'] == 'date_interval'
                        || $field['type'] == 'datetime_interval') && isset($values[$fields_id . '-2'])) {
                    $field['value2'] = $values[$fields_id . '-2'];
                }

                $self = new self();
                $self->getFromDB($metademands_id);
                if ($self->getField('hide_no_field') == 1) {
                    if ($field['type'] == 'radio' && $field['value'] === "") {
                        continue;
                    }
                    if ($field['type'] == 'number' && $field['value'] == "0") {
                        continue;
                    }
                    if ($field['type'] == 'checkbox' && ($field['value'] == "" || $field['value'] == "0")) {
                        continue;
                    }
                    if ($field['type'] == 'yesno' && $field['value'] != "2") {
                        continue;
                    }
                    if ($field['type'] == 'dropdown_meta' && $field['value'] == "0") {
                        continue;
                    }
                }

                if ($field['type'] == "dropdown_meta"
                    && $field['item'] == "PluginResourcesResource") {
                    $result['items_id'] = ['PluginResourcesResource' => [$field['value']]];
                }

                if (!isset($options['formatastable'])
                    || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                    if ($nb % 2 == 0) {
                        $resultTemp[$field['rank']]['content'] .= "<tr class='even'>";
                    } else {
                        $resultTemp[$field['rank']]['content'] .= "<tr class='odd'>";
                    }
                }
                $nb++;
                $formatAsTable = $options['formatastable'] ?? true;

                if (isset($colors) && !empty($colors)) {
                    $i = 0;
                    foreach ($colors as $key => $val) {
                        $newKey = substr($key, 0, strpos($key, '#'));
                        if ($field['id'] == $newKey) {
                            if ($i > 0) {
                                $resultTemp[$field['rank']]['content'] .= "<tr>";
                            }
                            $i++;
                            $field['value'] = $val;
                            $color = substr($key, strpos($key, '#') + 1);
                            self::getContentWithField($parent_fields, $newKey, $field, $resultTemp, $parent_fields_id, false, $formatAsTable, $langTech, $color);
                            unset($colors[$key]);
                            if (!isset($options['formatastable'])
                                || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                                $resultTemp[$field['rank']]['content'] .= "</tr>";
                            }
                        }
                    }
                } else {
                    self::getContentWithField($parent_fields, $fields_id, $field, $resultTemp, $parent_fields_id, false, $formatAsTable, $langTech);

                    if (!isset($options['formatastable'])
                        || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                        $resultTemp[$field['rank']]['content'] .= "</tr>";
                    }
                }
            }
            foreach ($resultTemp as $blockId => $tab) {
                if ($tab['display'] == true) {
                    $result['content'] .= $tab['content'];
                }
            }
            if (!isset($options['formatastable'])
                || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                $result['content'] .= "</table>";
            }
        }
        return $result;
    }

    /**
     * Format fields to display on ticket content
     *
     * @param $parent_fields
     * @param $fields_id
     * @param $field
     * @param $result
     * @param $parent_fields_id
     * @param $return_value
     */
    public static function getContentWithField($parent_fields, $fields_id, $field, &$result, &$parent_fields_id, $return_value = false, $formatAsTable = true, $lang = '', $color = '')
    {
        global $PLUGIN_HOOKS;

        $style_title = "class='title'";
        if ($color != "") {
            $style_title .= " style='color:$color'";
        }
        //      $style_title = "style='background-color: #cccccc;'";

        if (empty($label = PluginMetademandsField::displayField($field['id'], 'name', $lang))) {
            $label = Toolbox::stripslashes_deep($field['name']);
        }

        if ((!empty($field['value']) || $field['value'] == "0")
            && $field['value'] != 'NULL'
            || $field['type'] == 'title'
            || $field['type'] == 'title-block'
            || $field['type'] == 'radio') {


            //use plugin fields types
            if (isset($PLUGIN_HOOKS['metademands'])) {
                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                    $new_fields = PluginMetademandsField::getPluginFieldItemsType($plug);
                    if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                        if (in_array($field['type'], array_keys($new_fields))) {
                            $field['type'] = $new_fields[$field['type']];
                        }
                    }
                }
            }

            switch ($field['type']) {

                case 'title-block':
                    PluginMetademandsTitleblock::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    break;
                case 'title':
                    PluginMetademandsTitle::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    break;
                case 'dropdown':
                    if ($field['value'] != 0) {
                        if ($return_value == true) {
                            return PluginMetademandsDropdown::getFieldValue($field);
                        } else {
                            PluginMetademandsDropdown::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                        }
                    }
                    break;
                case 'dropdown_object':
                    if ($field['value'] != 0) {
                        if ($return_value == true) {
                            return PluginMetademandsDropdownobject::getFieldValue($field);
                        } else {
                            PluginMetademandsDropdownobject::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                        }
                    }
                    break;
                case 'dropdown_meta':
                    if ($return_value == true) {
                        return PluginMetademandsDropdownmeta::getFieldValue($field, $lang);
                    } else {
                        PluginMetademandsDropdownmeta::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'dropdown_multiple':
                    if ($return_value == true) {
                        return PluginMetademandsDropdownmultiple::getFieldValue($field, $lang);
                    } else {
                        PluginMetademandsDropdownmultiple::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'link':
                    if ($return_value == true) {
                        return PluginMetademandsLink::getFieldValue($field);
                    } else {
                        PluginMetademandsLink::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }

                    break;
                case 'textarea':
                    if ($return_value == true) {
                        return PluginMetademandsTextarea::getFieldValue($field);
                    } else {
                        PluginMetademandsTextarea::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'text':
                    if ($return_value == true) {
                        return PluginMetademandsText::getFieldValue($field);
                    } else {
                        PluginMetademandsText::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'checkbox':
                    if ($return_value == true) {
                        return PluginMetademandsCheckbox::getFieldValue($field, $lang);
                    } else {
                        PluginMetademandsCheckbox::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }

                    break;
                case 'radio':
                    if ($return_value == true) {
                        return PluginMetademandsRadio::getFieldValue($field, $label, $lang);
                    } else {
                        PluginMetademandsRadio::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'date':
                    if ($return_value == true) {
                        return PluginMetademandsDate::getFieldValue($field);
                    } else {
                        PluginMetademandsDate::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'datetime':
                    if ($return_value == true) {
                        return PluginMetademandsDatetime::getFieldValue($field);
                    } else {
                        PluginMetademandsDatetime::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'date_interval':
                    if ($return_value == true) {
                        return PluginMetademandsDateinterval::getFieldValue($field);
                    } else {
                        PluginMetademandsDateinterval::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'datetime_interval':
                    if ($return_value == true) {
                        return PluginMetademandsDatetimeinterval::getFieldValue($field);
                    } else {
                        PluginMetademandsDatetimeinterval::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'number':
                    if ($return_value == true) {
                        return PluginMetademandsNumber::getFieldValue($field);
                    } else {
                        PluginMetademandsNumber::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;
                case 'yesno':
                    if ($return_value == true) {
                        return PluginMetademandsYesno::getFieldValue($field);
                    } else {
                        PluginMetademandsYesno::displayFieldItems($result, $formatAsTable, $style_title, $label, $field, $return_value, $lang);
                    }
                    break;

                case 'parent_field':
                    $metademand_field = new PluginMetademandsField();
                    if (isset($field['parent_field_id']) && $metademand_field->getFromDB($field['parent_field_id'])) {
                        $parent_field = $field;
                        $custom_values = PluginMetademandsField::_unserialize($metademand_field->fields['custom_values']);
                        foreach ($custom_values as $k => $val) {
                            if (!empty($ret = PluginMetademandsField::displayField($field["parent_field_id"], "custom" . $k, $lang))) {
                                $custom_values[$k] = $ret;
                            }
                        }
                        $parent_field['custom_values'] = $custom_values;
                        $parent_field['type'] = $metademand_field->fields['type'];
                        $parent_field['item'] = $metademand_field->fields['item'];

                        self::getContentWithField($parent_fields, $fields_id, $parent_field, $result, $parent_fields_id, false, false, $lang);
                    }

                    break;
                default:

                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            if ($return_value == true) {
                                return $field['value'];
                            } else {
                                $result[$field['rank']]['display'] = true;
                                $content = self::displayPluginFieldItems($plug, $formatAsTable, $style_title, $label, $field);
                                $result[$field['rank']]['content'] .= $content;

                            }
                        }
                    }

                    break;
            }
        }
        $parent_fields_id = $fields_id;
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
       static function displayPluginFieldItems($plug, $formatAsTable, $style_title, $label, $field) {
          global $PLUGIN_HOOKS;

          $dbu = new DbUtils();
          if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
             $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

             foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                   continue;
                }
                $form[$pluginclass] = [];
                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'displayFieldItems'])) {
                   return $item->displayFieldItems($formatAsTable, $style_title, $label, $field);
                }
             }
          }
       }

    /**
     * @param $metademands_id
     * @param $itilcategory
     * @param $values
     * @param $users_id_requester
     *
     * @return array
     */
    public function formatTicketFields($metademands_id, $itilcategory, $values, $users_id_requester)
    {
        $inputs = [];
        $ticket_field = new PluginMetademandsTicketField();
        $parent_ticketfields = $ticket_field->find(['plugin_metademands_metademands_id' => $metademands_id]);

        $ticket = new Ticket();
        $meta = new PluginMetademandsMetademand();
        $meta->getFromDB($metademands_id);
        $tt = $ticket->getITILTemplateToUse(0, $meta->fields["type"], $itilcategory, $meta->fields['entities_id']);

        if (count($parent_ticketfields)) {
            $allowed_fields = $tt->getAllowedFields(true, true);
            foreach ($parent_ticketfields as $value) {
                if (isset($allowed_fields[$value['num']])
                    && (!in_array($allowed_fields[$value['num']], PluginMetademandsTicketField::$used_fields))) {
                    $value['item'] = $allowed_fields[$value['num']];
                    if ($value['item'] == 'name') {
                        do {
                            $match = $this->getBetween($value['value'], '[', ']');
                            if (empty($match)) {
                                $explodeTitle = [];
                                $explodeTitle = explode("#", $value['value']);
                                foreach ($explodeTitle as $title) {
                                    if (isset($values['fields'][$title])) {
                                        $field = new PluginMetademandsField();
                                        $field->getFromDB($title);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$title];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                            && isset($values['fields'][$title . '-2'])) {
                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                        }
                                        $result = [];
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $v = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        if ($v != null) {
                                            $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        }
                                    } else {
                                        $explodeTitle2 = explode(".", $title);

                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                            $field_object = new PluginMetademandsField();
                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                    $value['value'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $value['value']);
                                                }
                                            }
                                        }

                                        $users_id = $users_id_requester;
                                        switch ($title) {
                                            case "requester.login":
                                                $user = new User();
                                                $user->getFromDB($users_id);
                                                $v = $user->fields['name'];
                                                $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                                break;
                                            case "requester.name":
                                                $user = new User();
                                                $user->getFromDB($users_id);
                                                $v = $user->fields['realname'];
                                                $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                                break;
                                            case "requester.firstname":
                                                $user = new User();
                                                $user->getFromDB($users_id);
                                                $v = $user->fields['firstname'];
                                                $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                                break;
                                            case "requester.email":
                                                $user = new UserEmail();
                                                $user->getFromDBByCrit(['users_id' => $users_id, 'is_default' => 1]);
                                                $v = $user->fields['email'];
                                                $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                                break;
                                        }
                                    }
                                }
                            } else {
                                $explodeVal = [];
                                $explodeVal = explode("|", $match);
                                $find = false;
                                $val_to_replace = "";
                                foreach ($explodeVal as $str) {
                                    $explodeTitle = explode("#", $str);
                                    foreach ($explodeTitle as $title) {
                                        if (isset($values['fields'][$title])) {
                                            $field = new PluginMetademandsField();
                                            $field->getFromDB($title);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$title];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                                && isset($values['fields'][$title . '-2'])) {
                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                            }
                                            $result = [];
                                            $result[$fields['rank']]['content'] = "";
                                            $result[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $v = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                            $str = str_replace("#" . $title . "#", $v, $str);
                                            if (!is_null($v) && !empty($v)) {
                                                $find = true;
                                            }
                                        } else {
                                            $users_id = $users_id_requester;
                                            switch ($title) {
                                                case "requester.login":
                                                    $user = new User();
                                                    $user->getFromDB($users_id);
                                                    $v = $user->fields['name'];
                                                    $str = str_replace("#" . $title . "#", $v, $str);
                                                    break;
                                                case "requester.name":
                                                    $user = new User();
                                                    $user->getFromDB($users_id);
                                                    $v = $user->fields['realname'];
                                                    $str = str_replace("#" . $title . "#", $v, $str);
                                                    break;
                                                case "requester.firstname":
                                                    $user = new User();
                                                    $user->getFromDB($users_id);
                                                    $v = $user->fields['firstname'];
                                                    $str = str_replace("#" . $title . "#", $v, $str);
                                                    break;
                                                case "requester.email":
                                                    $user = new UserEmail();
                                                    $user->getFromDBByCrit(['users_id' => $users_id, 'is_default' => 1]);
                                                    $v = $user->fields['email'];
                                                    $str = str_replace("#" . $title . "#", $v, $str);
                                                    break;
                                            }
                                        }
                                    }
                                    if ($find == true) {
                                        break;
                                    }
                                }
                                if (str_contains($match, "#")) {
                                    $value['value'] = str_replace("[" . $match . "]", $str, $value['value']);
                                } else {
                                    $value['value'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $value['value']);
                                }
                            }
                        } while (!empty($match));

                        $value['value'] = str_replace("<@", "[", $value['value']);
                        $value['value'] = str_replace("@>", "]", $value['value']);
                        $explodeTitle = [];
                        $explodeTitle = explode("#", $value['value']);
                        foreach ($explodeTitle as $title) {
                            if (isset($values['fields'][$title])) {
                                $field = new PluginMetademandsField();
                                $field->getFromDB($title);
                                $fields = $field->fields;
                                $fields['value'] = '';

                                $fields['value'] = $values['fields'][$title];

                                $fields['value2'] = '';
                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                    && isset($values['fields'][$title . '-2'])) {
                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                }
                                $result = [];
                                $result[$fields['rank']]['content'] = "";
                                $result[$fields['rank']]['display'] = false;
                                $parent_fields_id = 0;
                                $v = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                if ($v != null) {
                                    $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                }
                            } else {
                                $users_id = $users_id_requester;
                                switch ($title) {
                                    case "requester.login":
                                        $user = new User();
                                        $user->getFromDB($users_id);
                                        $v = $user->fields['name'];
                                        $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        break;
                                    case "requester.name":
                                        $user = new User();
                                        $user->getFromDB($users_id);
                                        $v = $user->fields['realname'];
                                        $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        break;
                                    case "requester.firstname":
                                        $user = new User();
                                        $user->getFromDB($users_id);
                                        $v = $user->fields['firstname'];
                                        $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        break;
                                    case "requester.email":
                                        $user = new UserEmail();
                                        $user->getFromDBByCrit(['users_id' => $users_id, 'is_default' => 1]);
                                        $v = $user->fields['email'];
                                        $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        break;
                                }
                            }
                        }


                        $inputs[$value['item']] = self::$PARENT_PREFIX . $value['value'];
                    } else {
                        $inputs[$value['item']] = json_decode($value['value'], true);
                    }
                }
            }
        }
        return $inputs;
    }

    /**
     * @param array $tickettasks_data
     * @param       $parent_tickets_id
     * @param int $tasklevel
     * @param       $parent_fields
     * @param       $ancestor_tickets_id
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function createSonsTickets($parent_tickets_id, $parent_fields, $ancestor_tickets_id, $tickettasks_data = [], $tasklevel = 1, $inputField = [], $inputFieldMain = [])
    {
        $ticket_ticket = new Ticket_Ticket();
        $ticket_task = new PluginMetademandsTicket_Task();
        $task = new PluginMetademandsTask();
        $ticket = new Ticket();
        $KO = [];
        $ticketParent = new Ticket();
        $ticketParent->getFromDB($parent_tickets_id);

        foreach ($tickettasks_data as $son_ticket_data) {
            if ($son_ticket_data['level'] == $tasklevel) {
                if (isset($_SESSION['metademands_hide'])
                    && in_array($son_ticket_data['tickettasks_id'], $_SESSION['metademands_hide'])) {
                    continue;
                }
                // Skip ticket creation if not allowed by metademand form
                if (!PluginMetademandsTicket_Field::checkTicketCreation($son_ticket_data['tasks_id'], $ancestor_tickets_id)) {
                    continue;
                }
             
                $tt = $ticket->getITILTemplateToUse(0, $ticketParent->fields['type'], $son_ticket_data['itilcategories_id'], $ticketParent->fields['entities_id']);
                $predifined_fields = $tt->predefined;
                $son_ticket_data = array_merge($son_ticket_data,$predifined_fields);
             
                // Field format for ticket
                foreach ($son_ticket_data as $field => $value) {
                    if (strstr($field, 'groups_id_')
                        || strstr($field, 'users_id_')) {
                        $son_ticket_data['_' . $field] = $son_ticket_data[$field];
                    }
                }
                foreach ($parent_fields as $field => $value) {
                    if (strstr($field, 'groups_id_')
                        || strstr($field, 'users_id_')) {
                        $parent_fields['_' . $field] = $parent_fields[$field];
                    }
                }

                if (!isset($this->fields['id'])) {
                    $ticket_meta = new PluginMetademandsTicket_Metademand();
                    $ticket_meta->getFromDBByCrit(['tickets_id' => $ancestor_tickets_id]);
                    $this->getFromDB($ticket_meta->fields['plugin_metademands_metademands_id']);
                }

                $values_form = [];
                $ticket_field = new PluginMetademandsTicket_Field();
                $fields = $ticket_field->find(['tickets_id' => $ancestor_tickets_id]);
                foreach ($fields as $f) {
                    $values_form[$f['plugin_metademands_fields_id']] = json_decode($f['value']);
                    if ($values_form[$f['plugin_metademands_fields_id']] === null) {
                        $values_form[$f['plugin_metademands_fields_id']] = $f['value'];
                    }
                    if (!empty($f['value2'])) {
                        $values_form[$f['plugin_metademands_fields_id'] . '-2'] = json_decode($f['value2']);
                        if ($values_form[$f['plugin_metademands_fields_id'] . '-2'] === null) {
                            $values_form[$f['plugin_metademands_fields_id'] . '-2'] = $f['value2'];
                        }
                    }
                }
                $metademands_data = $this->constructMetademands($this->getID());

                if (count($metademands_data)) {
                    foreach ($metademands_data as $form_step => $data) {
                        foreach ($data as $form_metademands_id => $line) {
                            $list_fields = $line['form'];
                            $searchOption = Search::getOptions('Ticket');
                            if ($task->getFromDB($son_ticket_data['tasks_id'])) {
                                if (isset($task->fields['useBlock']) && $task->fields['useBlock'] == 1) {
                                    $blocks = json_decode($task->fields["block_use"], true);
                                    if (!empty($blocks)) {
                                        foreach ($line['form'] as $i => $l) {
                                            if (!in_array($l['rank'], $blocks)) {
                                                unset($line['form'][$i]);
                                                unset($values_form[$i]);
                                            }
                                        }
                                        $parent_fields_content = $this->formatFields($line['form'], $this->getID(), [$values_form], ['formatastable' => $task->fields['formatastable']]);
                                        $parent_fields_content['content'] = Html::cleanPostForTextArea($parent_fields_content['content']);
                                    } else {
                                        $parent_fields_content['content'] = $parent_fields['content'];
                                    }
                                }
                                foreach ($list_fields as $id => $fields_values) {
                                    if ($fields_values['used_by_ticket'] > 0 && $fields_values['used_by_child'] == 1) {
                                        if (isset($values_form[$id])) {
                                            $name = $searchOption[$fields_values['used_by_ticket']]['linkfield'];
                                            if ($fields_values['used_by_ticket'] == 4) {
                                                $name = "_users_id_requester";
                                            }
                                            if ($fields_values['used_by_ticket'] == 71) {
                                                $name = "_groups_id_requester";
                                            }
                                            if ($fields_values['used_by_ticket'] == 66) {
                                                $name = "_users_id_observer";
                                            }
                                            if ($fields_values['used_by_ticket'] == 65) {
                                                $name = "_groups_id_observer";
                                            }
                                            $son_ticket_data[$name] = $values_form[$id];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }


                // Add son ticket
//                $son_ticket_data['_disablenotif']      = true;
                $son_ticket_data['name'] = self::$SON_PREFIX . $son_ticket_data['tickettasks_name'];
                $son_ticket_data['name'] = trim($son_ticket_data['name']);
                $son_ticket_data['name'] = Glpi\RichText\RichText::getTextFromHtml($son_ticket_data['name']);
                $son_ticket_data['type'] = $parent_fields['type'];
                $son_ticket_data['entities_id'] = $parent_fields['entities_id'];
                $son_ticket_data['users_id_recipient'] = isset($parent_fields['users_id_recipient']) ? $parent_fields['users_id_recipient'] : 0;
                //Must use used_by_child parameter if can
                if (!$son_ticket_data['_users_id_requester']) {
                    $son_ticket_data['_users_id_requester'] = isset($parent_fields['_users_id_requester']) ? $parent_fields['_users_id_requester'] : 0;
                }
                $son_ticket_data['requesttypes_id'] = $parent_fields['requesttypes_id'];
                $son_ticket_data['_auto_import'] = 1;
                $son_ticket_data['status'] = Ticket::INCOMING;
                if (isset($parent_fields['urgency'])) {
                    $son_ticket_data['urgency'] = $parent_fields['urgency'];
                }
                if (isset($parent_fields['impact'])) {
                    $son_ticket_data['impact'] = $parent_fields['impact'];
                }
                if (isset($parent_fields['priority'])) {
                    $son_ticket_data['priority'] = $parent_fields['priority'];
                }

                $content = '';
                $config = new PluginMetademandsConfig();
                $config->getFromDB(1);

                if (!empty($son_ticket_data['content'])) {
                    if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                        $content = "<table class='tab_cadre_fixe' style='width: 100%;'>";
                        $content .= "<tr><th colspan='2'>" . __('Child Ticket', 'metademands') .
                            "</th></tr><tr><td colspan='2'>";
                    }

                    $content .= Glpi\RichText\RichText::getSafeHtml($son_ticket_data['content']);

                    if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                        $content .= "</td></tr></table><br>";
                    }
                }

                if ($config->getField('childs_parent_content') == 1
                    && $task->fields['formatastable'] == true) {
                    if (!empty($parent_fields_content['content'])) {
                        //if (!strstr($parent_fields['content'], __('Parent ticket', 'metademands'))) {
                        $content .= "<table class='tab_cadre_fixe' style='width: 100%;'><tr><th colspan='2'>";
                        $content .= _n('Parent tickets', 'Parent tickets', 1, 'metademands') .
                            "</th></tr><tr><td colspan='2'>" . Glpi\RichText\RichText::getSafeHtml($parent_fields_content['content']);
                        //if (!strstr($parent_fields['content'], __('Parent ticket', 'metademands'))) {
                        $content .= "</td></tr></table><br>";
                        //}
                    }
                }

                $son_ticket_data['content'] = $content;
                if (isset($parent_fields['_groups_id_assign'])) {
                    $son_ticket_data['_groups_id_requester'] = $parent_fields['_groups_id_assign'];
                }
                $son_ticket_data = $this->mergeFields($son_ticket_data, $inputFieldMain);

                if ($son_tickets_id = $ticket->add(Toolbox::addslashes_deep($son_ticket_data))) {
                    if (Plugin::isPluginActive('fields')) {
                        foreach ($inputField as $containers_id => $vals) {
                            $container = new PluginFieldsContainer;
                            $vals['plugin_fields_containers_id'] = $containers_id;
                            $vals['itemtype'] = "Ticket";
                            $vals['items_id'] = $son_tickets_id;
                            $container->updateFieldsValues($vals, "Ticket", false);
                        }
                    }
                    // Add son link to parent
                    $ticket_ticket->add(['tickets_id_1' => $parent_tickets_id,
                        'tickets_id_2' => $son_tickets_id,
                        'link' => Ticket_Ticket::PARENT_OF]);

                    // task - ticket relation
                    $ticket_task->add(['tickets_id' => $son_tickets_id,
                        'parent_tickets_id' => $parent_tickets_id,
                        'level' => $son_ticket_data['level'],
                        'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id']]);
                } else {
                    $KO[] = 1;
                }
            } else {
                if (isset($_SESSION['metademands_hide'])
                    && in_array($son_ticket_data['tickettasks_id'], $_SESSION['metademands_hide'])) {
                    continue;
                }
                // task - ticket relation for next tickets
                if (!PluginMetademandsTicket_Field::checkTicketCreation($son_ticket_data['tasks_id'], $parent_tickets_id)) {
                    continue;
                }
                $ticket_task->add(['tickets_id' => 0,
                    'parent_tickets_id' => $parent_tickets_id,
                    'level' => $son_ticket_data['level'],
                    'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id']]);
            }
        }

        if (count($KO)) {
            return false;
        }

        return true;
    }

    /**
     * @param $tickets_data
     *
     * @throws \GlpitestSQLError
     */
    public function addSonTickets($tickets_data, $ticket_metademand)
    {
        global $DB;

        $ticket_task = new PluginMetademandsTicket_Task();
        $ticket = new Ticket();
        $groups_tickets = new Group_Ticket();
        $users_tickets = new Ticket_User();

        // We can add task if one is not already present for ticket
        $search_ticket = $ticket_task->find(['parent_tickets_id' => $tickets_data['id']]);
        if (!count($search_ticket)) {
            $task = new PluginMetademandsTask();
            $query = "SELECT `glpi_plugin_metademands_tickettasks`.*,
                             `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id`,
                             `glpi_plugin_metademands_tasks`.`id` AS tasks_id,
                             `glpi_plugin_metademands_tickets_tasks`.`level` AS parent_level
                        FROM `glpi_plugin_metademands_tickettasks`
                        LEFT JOIN `glpi_plugin_metademands_tasks`
                           ON (`glpi_plugin_metademands_tasks`.`id` = `glpi_plugin_metademands_tickettasks`.`plugin_metademands_tasks_id`)
                        LEFT JOIN `glpi_plugin_metademands_tickets_tasks`
                           ON (`glpi_plugin_metademands_tasks`.`id` = `glpi_plugin_metademands_tickets_tasks`.`plugin_metademands_tasks_id`)
                        WHERE `glpi_plugin_metademands_tickets_tasks`.`tickets_id` = " . $tickets_data['id'];
            $result = $DB->query($query);

            if ($DB->numrows($result)) {
                $values = [];
                $ticket_field = new PluginMetademandsTicket_Field();
                $ticket_id = PluginMetademandsTicket_Task::getFirstTicket($tickets_data['id']);
                $fields = $ticket_field->find(['tickets_id' => $ticket_id]);
                foreach ($fields as $f) {
                    $values['fields'][$f['plugin_metademands_fields_id']] = json_decode($f['value']);
                    if ($values['fields'][$f['plugin_metademands_fields_id']] === null) {
                        $values['fields'][$f['plugin_metademands_fields_id']] = $f['value'];
                    }

                    $f['plugin_metademands_fields_id'];
                }
                while ($data = $DB->fetchAssoc($result)) {
                    // If child task exists : son ticket creation
                    $child_tasks_data = $task->getChildrenForLevel($data['tasks_id'], $data['parent_level'] + 1);

                    if ($child_tasks_data) {
                        foreach ($child_tasks_data as $child_tasks_id) {
                            $tasks_data = $task->getTasks(
                                $data['plugin_metademands_metademands_id'],
                                ['condition' => ['glpi_plugin_metademands_tasks.id' => $child_tasks_id]]
                            );

                            // Get parent ticket data
                            $ticket->getFromDB($tickets_data['id']);

                            // Find parent metademand tickets_id and get its _groups_id_assign
                            $tickets_found = PluginMetademandsTicket::getAncestorTickets($tickets_data['id'], true);
                            $parent_groups_tickets_data = $groups_tickets->find(['tickets_id' => $tickets_found[0]['tickets_id'],
                                'type' => CommonITILActor::ASSIGN]);

                            if (count($parent_groups_tickets_data)) {
                                $parent_groups_tickets_data = reset($parent_groups_tickets_data);
                                $ticket->fields['_groups_id_assign'] = $parent_groups_tickets_data['groups_id'];
                            }
                            $parent_groups_tickets_data = $users_tickets->find(['tickets_id' => $tickets_found[0]['tickets_id'],
                                'type' => CommonITILActor::ASSIGN]);
                            $requesters = $users_tickets->find(['tickets_id' => $tickets_found[0]['tickets_id'],
                                'type' => CommonITILActor::REQUESTER]);
                            if (!empty($requesters)) {
                                $requester = array_shift($requesters);
                                $parent_fields['_users_id_requester'] = $requester['users_id'];
                            } else {
                                $parent_fields['_users_id_requester'] = Session::getLoginUserID();
                            }

                            if (count($parent_groups_tickets_data)) {
                                $parent_groups_tickets_data = reset($parent_groups_tickets_data);
                                $ticket->fields['_users_id_assign'] = $parent_groups_tickets_data['users_id'];
                            }

                            $l = $tasks_data[$child_tasks_id];
                            do {
                                $match = $this->getBetween($l['tickettasks_name'], '[', ']');
                                if (empty($match)) {
                                    $explodeTitle = [];
                                    $explodeTitle = explode("#", $l['tickettasks_name']);
                                    foreach ($explodeTitle as $title) {
                                        if (isset($values['fields'][$title])) {
                                            $field = new PluginMetademandsField();
                                            $field->getFromDB($title);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$title];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                            }
                                            $resultData = [];
                                            $resultData['content'] = "";
                                            $resultData[$fields['rank']]['content'] = "";
                                            $resultData[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = self::getContentWithField([], 0, $fields, $resultData, $parent_fields_id, true);
                                            $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $tasks_data[$child_tasks_id]['tickettasks_name']);
                                        } else {
                                            $explodeTitle2 = explode(".", $title);

                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                $field_object = new PluginMetademandsField();
                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                        $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $tasks_data[$child_tasks_id]['tickettasks_name']);
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester']; // TODO
                                            //                                 $users_id = Session::getLoginUserID(); // TODO
                                            $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser($title, $users_id, $title, $tasks_data[$child_tasks_id]['tickettasks_name'], true);
                                        }
                                    }
                                } else {
                                    $explodeVal = [];
                                    $explodeVal = explode("|", $match);
                                    $find = false;
                                    $val_to_replace = "";
                                    foreach ($explodeVal as $str) {
                                        $explodeTitle = explode("#", $str);
                                        foreach ($explodeTitle as $title) {
                                            if (isset($values['fields'][$title])) {
                                                $field = new PluginMetademandsField();
                                                $field->getFromDB($title);
                                                $fields = $field->fields;
                                                $fields['value'] = '';

                                                $fields['value'] = $values['fields'][$title];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                }
                                                $resultData = [];
                                                $resultData['content'] = "";
                                                $resultData[$fields['rank']]['content'] = "";
                                                $resultData[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField([], 0, $fields, $resultData, $parent_fields_id, true);
                                                $str = str_replace("#" . $title . "#", $value, $str);
                                                if (!is_null($value) && !empty($value)) {
                                                    $find = true;
                                                }
                                            } else {
                                                $explodeTitle2 = explode(".", $title);

                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                    $field_object = new PluginMetademandsField();
                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                            $str = self::getContentForUser($explodeTitle2[1], $users_id, $title, $str);
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $str = self::getContentForUser($explodeTitle2[1], $users_id, $title, $str);
                                            }
                                        }
                                        if ($find == true) {
                                            break;
                                        }
                                    }

                                    if (str_contains($match, "#")) {
                                        $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace("[" . $match . "]", $str, $tasks_data[$child_tasks_id]['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("[" . $match . "]", $str, $l['tickettasks_name']);
                                    } else {
                                        $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $tasks_data[$child_tasks_id]['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['tickettasks_name']);
                                    }
                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                }
                            } while (!empty($match));

                            $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace("<@", "[", $tasks_data[$child_tasks_id]['tickettasks_name']);
                            $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace("@>", "]", $tasks_data[$child_tasks_id]['tickettasks_name']);
                            $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                            $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                            $explodeTitle = explode("#", $l['tickettasks_name']);
                            foreach ($explodeTitle as $title) {
                                if (isset($values['fields'][$title])) {
                                    $field = new PluginMetademandsField();
                                    $field->getFromDB($title);
                                    $fields = $field->fields;
                                    $fields['value'] = '';

                                    $fields['value'] = $values['fields'][$title];

                                    $fields['value2'] = '';
                                    if (($fields['type'] == 'date_interval'
                                            || $fields['type'] == 'datetime_interval')
                                        && isset($values['fields'][$title . '-2'])) {
                                        $fields['value2'] = $values['fields'][$title . '-2'];
                                    }
                                    $resultData = [];
                                    $resultData['content'] = "";
                                    $resultData[$fields['rank']]['content'] = "";
                                    $resultData[$fields['rank']]['display'] = false;
                                    $parent_fields_id = 0;
                                    $value = self::getContentWithField([], 0, $fields, $resultData, $parent_fields_id, true);
                                    $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $tasks_data[$child_tasks_id]['tickettasks_name']);
                                } else {
                                    $explodeTitle2 = explode(".", $title);

                                    if (isset($values['fields'][$explodeTitle2[0]])) {
                                        $field_object = new PluginMetademandsField();
                                        if ($field_object->getFromDB($explodeTitle2[0])) {
                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                $users_id = $values['fields'][$explodeTitle2[0]];
                                                $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser($explodeTitle2[1], $users_id, $title, $tasks_data[$child_tasks_id]['tickettasks_name']);
                                            }
                                        }
                                    }
                                    $users_id = $parent_fields['_users_id_requester'];
                                    $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser($title, $users_id, $title, $tasks_data[$child_tasks_id]['tickettasks_name'], true);
                                }
                            }

                            //replace #id# in content with the value
                            do {
                                $match = $this->getBetween($l['content'], '[', ']');
                                if (empty($match)) {
                                    $explodeContent = explode("#", $l['content']);
                                    foreach ($explodeContent as $content) {
                                        if (isset($values['fields'][$content])) {
                                            $field = new PluginMetademandsField();
                                            $field->getFromDB($content);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$content];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                            }
                                            $resultData = [];
                                            $resultData['content'] = "";
                                            $resultData[$fields['rank']]['content'] = "";
                                            $resultData[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = self::getContentWithField([], 0, $fields, $resultData, $parent_fields_id, true);
                                            $tasks_data[$child_tasks_id]['content'] = str_replace("#" . $content . "#", $value, $tasks_data[$child_tasks_id]['content']);
                                        } else {
                                            $explodeContent2 = explode(".", $content);

                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                $field_object = new PluginMetademandsField();
                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                        $tasks_data[$child_tasks_id]['content'] = self::getContentForUser($explodeContent2[1], $users_id, $content, $tasks_data[$child_tasks_id]['content']);
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester'];
                                            $tasks_data[$child_tasks_id]['content'] = self::getContentForUser($content, $users_id, $content, $tasks_data[$child_tasks_id]['content'], true);
                                        }
                                    }
                                } else {
                                    $explodeVal = [];
                                    $explodeVal = explode("|", $match);
                                    $find = false;
                                    $val_to_replace = "";
                                    foreach ($explodeVal as $str) {
                                        $explodeContent = explode("#", $str);
                                        foreach ($explodeContent as $content) {
                                            if (isset($values['fields'][$content])) {
                                                $field = new PluginMetademandsField();
                                                $field->getFromDB($content);
                                                $fields = $field->fields;
                                                $fields['value'] = '';

                                                $fields['value'] = $values['fields'][$content];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$content . '-2'];
                                                }
                                                $resultData = [];
                                                $resultData['content'] = "";
                                                $resultData[$fields['rank']]['content'] = "";
                                                $resultData[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField([], 0, $fields, $resultData, $parent_fields_id, true);
                                                $str = str_replace("#" . $content . "#", $value, $str);
                                                if (!is_null($value) && !empty($value)) {
                                                    $find = true;
                                                }
                                            } else {
                                                $explodeContent2 = explode(".", $content);

                                                if (isset($values['fields'][$explodeContent2[0]])) {
                                                    $field_object = new PluginMetademandsField();
                                                    if ($field_object->getFromDB($explodeContent2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                            $users_id = $values['fields'][$explodeContent2[0]];
                                                            $str = self::getContentForUser($explodeContent2[1], $users_id, $content, $str);
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $str = self::getContentForUser($content, $users_id, $content, $str, true);
                                            }
                                        }
                                        if ($find == true) {
                                            break;
                                        }
                                    }
                                    //                                    $tasks_data[$child_tasks_id]['content'] = str_replace("[" . $match . "]", $str, $tasks_data[$child_tasks_id]['content']);
                                    if (str_contains($match, "#")) {
                                        $tasks_data[$child_tasks_id]['content'] = str_replace("[" . $match . "]", $str, $tasks_data[$child_tasks_id]['content']);
                                        $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                                    } else {
                                        $tasks_data[$child_tasks_id]['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $tasks_data[$child_tasks_id]['content']);
                                        $l['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['content']);
                                    }
                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                }
                            } while (!empty($match));

                            $tasks_data[$child_tasks_id]['content'] = str_replace("<@", "[", $tasks_data[$child_tasks_id]['content']);
                            $tasks_data[$child_tasks_id]['content'] = str_replace("@>", "]", $tasks_data[$child_tasks_id]['content']);
                            $l['content'] = str_replace("<@", "[", $l['content']);
                            $l['content'] = str_replace("@>", "]", $l['content']);

                            $explodeContent = explode("#", $l['content']);
                            foreach ($explodeContent as $content) {
                                if (isset($values['fields'][$content])) {
                                    $field = new PluginMetademandsField();
                                    $field->getFromDB($content);
                                    $fields = $field->fields;
                                    $fields['value'] = '';

                                    $fields['value'] = $values['fields'][$content];

                                    $fields['value2'] = '';
                                    if (($fields['type'] == 'date_interval'
                                            || $fields['type'] == 'datetime_interval')
                                        && isset($values['fields'][$content . '-2'])) {
                                        $fields['value2'] = $values['fields'][$content . '-2'];
                                    }
                                    $resultData = [];
                                    $resultData['content'] = "";
                                    $resultData[$fields['rank']]['content'] = "";
                                    $resultData[$fields['rank']]['display'] = false;
                                    $parent_fields_id = 0;
                                    $value = self::getContentWithField([], 0, $fields, $resultData, $parent_fields_id, true);
                                    $tasks_data[$child_tasks_id]['content'] = str_replace("#" . $content . "#", $value, $tasks_data[$child_tasks_id]['content']);
                                } else {
                                    $explodeContent2 = explode(".", $content);

                                    if (isset($values['fields'][$explodeContent2[0]])) {
                                        $field_object = new PluginMetademandsField();
                                        if ($field_object->getFromDB($explodeContent2[0])) {
                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                $users_id = $values['fields'][$explodeContent2[0]];
                                                $tasks_data[$child_tasks_id]['content'] = self::getContentForUser($explodeContent2[1], $users_id, $content, $tasks_data[$child_tasks_id]['content']);
                                            }
                                        }
                                    }
                                    $users_id = $parent_fields['_users_id_requester'];
                                    $tasks_data[$child_tasks_id]['content'] = self::getContentForUser($content, $users_id, $content, $tasks_data[$child_tasks_id]['content'], true);
                                }
                            }

                            $this->createSonsTickets($tickets_data['id'], $ticket->fields, $tickets_found[0]['tickets_id'], $tasks_data, $data['parent_level'] + 1);
                        }
                    }
                }
            } else {
                if (count($ticket_metademand->fields) > 0) {
                    $ticket_metademand->update(['id' => $ticket_metademand->getID(),
                        'status' => PluginMetademandsTicket_Metademand::CLOSED]);
                }
            }
        }
    }

    /**
     * @param $ticket
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showPluginForTicket($ticket)
    {
        if (!$this->canView()) {
            return false;
        }
        $tovalidate = 0;
        $metaValidation = new PluginMetademandsMetademandValidation();
        if ($metaValidation->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])
            && ($metaValidation->fields['validate'] == PluginMetademandsMetademandValidation::TO_VALIDATE
                || $metaValidation->fields['validate'] == PluginMetademandsMetademandValidation::TO_VALIDATE_WITHOUTTASK)
            && Session::haveRight('plugin_metademands', READ)
            && Session::getCurrentInterface() == 'central') {
            $tovalidate = 1;

            echo "<div class='alert center'>";
            echo __('Metademand need a validation', 'metademands');
            echo "<br>";
            echo __('Do you want to validate her?', 'metademands');
            $style = "btn-orange";
            echo "<br>";
            echo "<br>";
            echo "<a class='btn primary mb-2 answer-action $style' data-bs-toggle='modal' data-bs-target='#metavalidation'>"
                . "<i class='fas fa-thumbs-up'></i>&nbsp;" . __('Metademand validation', 'metademands') . "</a>";

            echo Ajax::createIframeModalWindow(
                'metavalidation',
                PLUGIN_METADEMANDS_WEBDIR . '/front/metademandvalidation.form.php?tickets_id=' . $ticket->fields['id'],
                ['title' => __('Metademand validation', 'metademands'),
                    'display' => false,
                    'width' => 200,
                    'height' => 400,
                    'reloadonclose' => true]
            );

            echo "</div>";


            $sons = json_decode($metaValidation->fields['tickets_to_create'], true);
            if (is_array($sons)) {
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='left b' colspan='4'>" . __('List of tickets / tasks which be created after validation', 'metademands') . "</th>";
                echo "</tr>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='center b'>" . __('Name') . "</th>";
                echo "<th class='center b'>" . __('Type') . "</th>";
                echo "<th class='center b'>" . __('Category') . "</th>";
                echo "<th class='center b'>" . __('Assigned to') . "</th>";
                echo "</tr>";
                foreach ($sons as $son) {
                    if (PluginMetademandsTicket_Field::checkTicketCreation($son['tasks_id'], $ticket->fields['id'])) {
                        echo "<tr class='tab_bg_1'>";
                        if ($son['type'] == PluginMetademandsTask::TICKET_TYPE || $son['type'] == PluginMetademandsTask::TASK_TYPE) {
                            $color_class = '';
                        } else {
                            $color_class = "class='metademand_metademandtasks'";
                        }

                        echo "<td $color_class>" . urldecode($son['tickettasks_name']) . "</td>";

                        // Type
                        echo "<td $color_class>" . PluginMetademandsTask::getTaskTypeName($son['type']) . "</td>";

                        $cat = "";
                        if ($son['type'] == PluginMetademandsTask::TICKET_TYPE
                            && isset($son['itilcategories_id'])
                            && $son['itilcategories_id'] > 0) {
                            $cat = Dropdown::getDropdownName("glpi_itilcategories", $son['itilcategories_id']);
                        }
                        echo "<td $color_class>";
                        echo $cat;
                        echo "</td>";

                        //assign
                        $techdata = "";
                        if ($son['type'] == PluginMetademandsTask::TICKET_TYPE || $son['type'] == PluginMetademandsTask::TASK_TYPE) {
                            if (isset($son['users_id_assign'])
                                && $son['users_id_assign'] > 0) {
                                $techdata .= getUserName($son['users_id_assign']);
                                $techdata .= "<br>";
                            }
                            if (isset($son['groups_id_assign'])
                                && $son['groups_id_assign'] > 0) {
                                $techdata .= Dropdown::getDropdownName("glpi_groups", $son['groups_id_assign']);
                            }
                        }
                        echo "<td $color_class>";
                        echo $techdata;
                        echo "</td>";

                        echo "</tr>";
                    }
                }
                echo "</table>";
            }
        }

        $ticket_metademand = new PluginMetademandsTicket_Metademand();
        $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $ticket->fields['id']]);
        $tickets_found = [];
        // If ticket is Parent : Check if all sons ticket are closed
        if (count($ticket_metademand_data)) {
            $ticket_metademand_data = reset($ticket_metademand_data);
            $tickets_found = PluginMetademandsTicket::getSonTickets(
                $ticket->fields['id'],
                $ticket_metademand_data['plugin_metademands_metademands_id']
            );
        } else {
            $ticket_task = new PluginMetademandsTicket_Task();
            $ticket_task_data = $ticket_task->find(['tickets_id' => $ticket->fields['id']]);

            if (count($ticket_task_data)) {
                $tickets_found = PluginMetademandsTicket::getAncestorTickets($ticket->fields['id'], true);
            }
        }
        $tickets_existant = [];
        $tickets_next = [];

        if ($tovalidate == 0) {
            if (count($tickets_found)) {
                echo "<div align='center'><table class='tab_cadre_fixe'>";
                echo "<tr><th colspan='6'>" . __('Demand followup', 'metademands') . "</th></tr>";
                echo "</table></div>";

                foreach ($tickets_found as $tickets) {
                    if (!empty($tickets['tickets_id'])) {
                        $tickets_existant[] = $tickets;
                    } else {
                        $tickets_next[] = $tickets;
                    }
                }

                if (count($tickets_existant)) {
                    echo "<div align='center'><table class='tab_cadre_fixe'>";
                    echo "<tr class='center'>";
                    echo "<td colspan='6'><h3>" . __('Existent tickets', 'metademands') . "</h3></td></tr>";

                    echo "<tr>";
                    echo "<th>" . __('Ticket') . "</th>";
                    echo "<th>" . __('Opening date') . "</th>";
                    echo "<th>" . __('Assigned to') . "</th>";
                    echo "<th>" . __('Status') . "</th>";
                    echo "<th>" . __('Due date', 'metademands') . "</th>";
                    echo "<th>" . __('Status') . " " . __('SLA') . "</th></tr>";

                    $status = [Ticket::SOLVED, Ticket::CLOSED];

                    foreach ($tickets_existant as $values) {
                        $color_class = '';
                        // Get ticket values if it exists
                        $ticket->getFromDB($values['tickets_id']);

                        // SLA State
                        $sla_state = Dropdown::EMPTY_VALUE;
                        $is_late = false;
                        switch ($this->checkSlaState($values)) {
                            case self::SLA_FINISHED:
                                $sla_state = __('Task completed.');
                                break;
                            case self::SLA_LATE:
                                $is_late = true;
                                $color_class = "metademand_metademandfollowup_red";
                                $sla_state = __('Late');
                                break;
                            case self::SLA_PLANNED:
                                $sla_state = __('Processing');
                                break;
                            case self::SLA_TODO:
                                $sla_state = __('To do');
                                $color_class = "metademand_metademandfollowup_yellow";
                                break;
                        }

                        echo "<tr class='tab_bg_1'>";
                        echo "<td>";
                        // Name
                        if ($values['type'] == PluginMetademandsTask::TICKET_TYPE) {
                            if ($values['level'] > 1) {
                                $width = (20 * $values['level']);
                                echo "<div style='margin-left:" . $width . "px' class='metademands_tree'></div>";
                            }
                        }

                        if (!empty($values['tickets_id'])) {
                            echo "<a href='" . Toolbox::getItemTypeFormURL('Ticket') .
                                "?id=" . $ticket->fields['id'] . "&glpi_tab=Ticket$" . 'main' . "'>" . $ticket->fields['name'] . "</a>";
                        } else {
                            echo self::$SON_PREFIX . $values['tasks_name'];
                        }

                        echo "</td>";

                        //date
                        echo "<td>";
                        echo Html::convDateTime($ticket->fields['date']);
                        echo "</td>";

                        //group
                        $techdata = '';
                        if ($ticket->countUsers(CommonITILActor::ASSIGN)) {
                            foreach ($ticket->getUsers(CommonITILActor::ASSIGN) as $u) {
                                $k = $u['users_id'];
                                if ($k) {
                                    $techdata .= getUserName($k);
                                }

                                if ($ticket->countUsers(CommonITILActor::ASSIGN) > 1) {
                                    $techdata .= "<br>";
                                }
                            }
                            $techdata .= "<br>";
                        }

                        if ($ticket->countGroups(CommonITILActor::ASSIGN)) {
                            foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $u) {
                                $k = $u['groups_id'];
                                if ($k) {
                                    $techdata .= Dropdown::getDropdownName("glpi_groups", $k);
                                }

                                if ($ticket->countGroups(CommonITILActor::ASSIGN) > 1) {
                                    $techdata .= "<br>";
                                }
                            }
                        }
                        echo "<td>";
                        echo $techdata;
                        echo "</td>";

                        //status
                        echo "<td class='center'>";
                        if (in_array($ticket->fields['status'], $status)) {
                            echo "<i class='fas fa-check-circle fa-2x' style='color:forestgreen'></i> ";
                        }

                        if (!in_array($ticket->fields['status'], $status)) {
                            echo "<i class='fas fa-cog fa-2x' style='color:orange'></i> ";
                        }
                        echo Ticket::getStatus($ticket->fields['status']);
                        echo "</td>";

                        //due date
                        echo "<td class='$color_class'>";
                        if ($is_late && !in_array($ticket->fields['status'], $status)) {
                            echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:darkred'></i> ";
                        }
                        echo Html::convDateTime($ticket->fields['time_to_resolve']);
                        echo "</td>";

                        //sla state
                        echo "<td>";
                        echo $sla_state;
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</table></div>";
                }

                if (count($tickets_next)) {
                    $color_class = "metademand_metademandfollowup_grey";
                    echo "<div align='center'><table class='tab_cadre_fixe'>";
                    echo "<tr class='center'>";
                    echo "<td colspan='6'><h3>" . __('Next tickets', 'metademands') . "</h3></td></tr>";

                    echo "<tr>";
                    echo "<th>" . __('Ticket') . "</th>";
                    echo "<th>" . __('Opening date') . "</th>";
                    echo "<th>" . __('Assigned to') . "</th>";
                    echo "<th>" . __('Status') . "</th>";
                    echo "<th>" . __('Due date', 'metademands') . "</th>";
                    echo "<th>" . __('Status') . " " . __('SLA') . "</th></tr>";

                    foreach ($tickets_next as $values) {
                        if (isset($values['parent_tickets_id']) && $values['parent_tickets_id'] > 0) {
                            continue;
                        }

                        $ticket->getEmpty();

                        // SLA State
                        $sla_state = Dropdown::EMPTY_VALUE;

                        echo "<tr class='tab_bg_1'>";
                        echo "<td class='$color_class'>";
                        // Name
                        if ($values['type'] == PluginMetademandsTask::TICKET_TYPE) {
                            if ($values['level'] > 1) {
                                $width = (20 * $values['level']);
                                echo "<div style='margin-left:" . $width . "px' class='metademands_tree'></div>";
                            }
                        }

                        if (!empty($values['tickets_id'])) {
                            echo "<a href='" . Toolbox::getItemTypeFormURL('Ticket') .
                                "?id=" . $ticket->fields['id'] . "'>" . $ticket->fields['name'] . "</a>";
                        } else {
                            echo self::$SON_PREFIX . $values['tasks_name'];
                        }

                        echo "</td>";

                        //date
                        echo "<td class='$color_class'>";
                        echo Html::convDateTime($ticket->fields['date']);
                        echo "</td>";

                        //group
                        $techdata = '';
                        if ($ticket->countUsers(CommonITILActor::ASSIGN)) {
                            foreach ($ticket->getUsers(CommonITILActor::ASSIGN) as $u) {
                                $k = $u['users_id'];
                                if ($k) {
                                    $techdata .= getUserName($k);
                                }

                                if ($ticket->countUsers(CommonITILActor::ASSIGN) > 1) {
                                    $techdata .= "<br>";
                                }
                            }
                            $techdata .= "<br>";
                        }

                        if ($ticket->countGroups(CommonITILActor::ASSIGN)) {
                            foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $u) {
                                $k = $u['groups_id'];
                                if ($k) {
                                    $techdata .= Dropdown::getDropdownName("glpi_groups", $k);
                                }

                                if ($ticket->countGroups(CommonITILActor::ASSIGN) > 1) {
                                    $techdata .= "<br>";
                                }
                            }
                        }
                        echo "<td class='$color_class'>";
                        echo "</td>";

                        //status
                        echo "<td class='$color_class center'>";
                        echo "<i class='fas fa-hourglass-half fa-2x'></i> ";
                        echo __('Coming', 'metademands');

                        echo "</td>";

                        //due date
                        echo "<td class='$color_class'>";
                        echo Html::convDateTime($ticket->fields['time_to_resolve']);
                        echo "</td>";

                        //sla state
                        echo "<td class='$color_class'>";
                        echo $sla_state;
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</table></div>";
                }
            } else {
                echo "<div class='alert alert-important alert-info center'>";
                echo __('There is no childs tickets', 'metademands');
                echo "</div>";
            }
        }
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function executeDuplicate($options = [])
    {
        global $CFG_GLPI;

        if (isset($options['metademands_id'])) {
            $metademands_id = $options['metademands_id'];

            $fields = new PluginMetademandsField();
            $fieldoptions = new PluginMetademandsFieldOption();
            $ticketfields = new PluginMetademandsTicketField();
            $tasks = new PluginMetademandsTask();
            $groups = new PluginMetademandsGroup();
            $tickettasks = new PluginMetademandsTicketTask();
            $metademandtasks = new PluginMetademandsMetademandTask();

            // Add the new metademand
            $this->getFromDB($metademands_id);
            unset($this->fields['id']);
            unset($this->fields['itilcategories_id']);

            //TODO To translate ?
            $this->fields['comment'] = addslashes($this->fields['comment']);
            $this->fields['name'] = addslashes($this->fields['name']);

            if ($new_metademands_id = $this->add($this->fields)) {
                $translationMeta = new PluginMetademandsMetademandTranslation();
                $translationsMeta = $translationMeta->find(['itemtype' => "PluginMetademandsMetademand", "items_id" => $metademands_id]);
                foreach ($translationsMeta as $tr) {
                    $translationMeta->getFromDB($tr['id']);
                    $translationMeta->clone(["items_id" => $new_metademands_id]);
                }
                $metademands_data = $this->constructMetademands($metademands_id);


                if (count($metademands_data)) {
                    $associated_fields = [];
                    $associated_tasks = [];
                    foreach ($metademands_data as $form_step => $data) {
                        foreach ($data as $form_metademands_id => $line) {
                            if (count($line['form'])) {
                                if ($form_metademands_id == $metademands_id) {
                                    // Add metademand fields
                                    foreach ($line['form'] as $values) {
                                        $id = $values['id'];
                                        unset($values['id']);
                                        $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                                        $values['name'] = addslashes($values['name']);
                                        $values['label2'] = addslashes($values['label2']);
                                        $values['comment'] = addslashes($values['comment']);

                                        $newID = $fields->add($values);
                                        $associated_fields[$id] = $newID;
                                        $associated_fields[$newID] = $id;
                                        $translation = new PluginMetademandsFieldTranslation();
                                        $translations = $translation->find(['itemtype' => "PluginMetademandsField", "items_id" => $id]);
                                        foreach ($translations as $tr) {
                                            $translation->getFromDB($tr['id']);
                                            $translation->clone(["items_id" => $newID]);
                                        }
                                    }

                                    // Add metademand group
                                    $groups_data = $groups->find(['plugin_metademands_metademands_id' => $metademands_id]);
                                    if (count($groups_data)) {
                                        foreach ($groups_data as $values) {
                                            unset($values['id']);
                                            $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                                            $groups->add($values);
                                        }
                                    }
                                }
                            }

                            // Add tasks
                            if (count($line['tasks']) && $form_metademands_id == $metademands_id) {
                                $parent_tasks = [];
                                foreach ($line['tasks'] as $values) {
                                    $tasks->getFromDB($values['tasks_id']);
                                    if (array_key_exists($values['parent_task'], $parent_tasks)) {
                                        $tasks->fields['plugin_metademands_tasks_id'] = $parent_tasks[$values['parent_task']];
                                    }
                                    $tasks->fields['plugin_metademands_metademands_id'] = $new_metademands_id;
                                    $tasks->fields['sons_cache'] = '';
                                    $tasks->fields['ancestors_cache'] = '';
                                    if (isset($tasks->fields['name'])) {
                                        $tasks->fields['name'] = addslashes($tasks->fields['name']);
                                    }
                                    if (isset($tasks->fields['completename'])) {
                                        $tasks->fields['completename'] = addslashes($tasks->fields['completename']);
                                    }
                                    if (isset($tasks->fields['comment'])) {
                                        $tasks->fields['comment'] = addslashes($tasks->fields['comment']);
                                    }

                                    unset($tasks->fields['id']);

                                    $new_tasks_id = $tasks->add($tasks->fields);
                                    $associated_tasks[$values['tasks_id']] = $new_tasks_id;
                                    $parent_tasks[$values['tasks_id']] = $new_tasks_id;

                                    // Ticket tasks
                                    if ($values['type'] == PluginMetademandsTask::TICKET_TYPE) {
                                        $tickettasks_data = $tickettasks->find(['plugin_metademands_tasks_id' => $values['tasks_id']]);
                                        if (count($tickettasks_data)) {
                                            foreach ($tickettasks_data as $values) {
                                                unset($values['id']);
                                                $values['plugin_metademands_tasks_id'] = $new_tasks_id;
                                                $values['content'] = addslashes($values['content']);
                                                $tickettasks->add($values);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $associated_fields[0] = 0;
                $associated_tasks[0] = 0;
                // duplicate metademand task
                $tasks_data = $tasks->find(['plugin_metademands_metademands_id' => $metademands_id,
                    'type' => PluginMetademandsTask::METADEMAND_TYPE]);
                if (count($tasks_data)) {
                    foreach ($tasks_data as $values) {
                        $metademandtasks_data = $metademandtasks->find(['plugin_metademands_tasks_id' => $values['id']]);
                        $id = $values['id'];
                        unset($values['id']);
                        $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                        $new_tasks_id = $tasks->add($values);
                        $associated_tasks[$id] = $new_tasks_id;
                        if (count($metademandtasks_data)) {
                            foreach ($metademandtasks_data as $data) {
                                $metademandtasks->add(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                                    'plugin_metademands_tasks_id' => $new_tasks_id]);
                            }
                        }
                    }
                }

                $newFields = $fields->find(['plugin_metademands_metademands_id' => $new_metademands_id]);
                foreach ($newFields as $newField) {

                    $old_field_id = $associated_fields[$newField["id"]];
                    $oldOptions = $fieldoptions->find(['plugin_metademands_fields_id' => $old_field_id]);
                    foreach ($oldOptions as $oldOption) {
                        $input['plugin_metademands_tasks_id'] = $associated_tasks[$oldOption['plugin_metademands_tasks_id']];
                        $input['check_value'] = $oldOption['check_value'];
                        $input['fields_link'] = $associated_fields[$oldOption['fields_link']];
                        $input['hidden_link'] = $associated_fields[$oldOption['hidden_link']];
                        $input['hidden_block'] = $oldOption['hidden_block'];
                        $input['users_id_validate'] = $oldOption['users_id_validate'];
                        $input['childs_blocks'] = $oldOption['childs_blocks'];
                        $input['checkbox_value'] = $oldOption['checkbox_value'];
                        $input['checkbox_id'] = $oldOption['checkbox_id'];
                        $input['plugin_metademands_fields_id'] = $newField['id'];
                        $fieldoptions->add($input);
                    }
                }
                // Add ticket fields
                $ticketfields_data = $ticketfields->find(['plugin_metademands_metademands_id' => $metademands_id]);
                if (count($ticketfields_data)) {
                    foreach ($ticketfields_data as $values) {
                        unset($values['id']);
                        $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                        $values['value'] = addslashes($values['value']);
                        $ticketfields->add($values);
                    }
                }

                // Redirect on finish
                if (isset($options['redirect'])) {
                    Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/metademand.form.php?id=" . $new_metademands_id);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * @param $values
     *
     * @return int
     */
    public function checkSlaState($values)
    {
        $ticket = new Ticket();
        $status = [Ticket::SOLVED, Ticket::CLOSED];

        $notcreated = false;
        // Get ticket values if it exists
        if (!empty($values['tickets_id'])) {
            $ticket->getFromDB($values['tickets_id']);
        } else {
            $notcreated = true;
            $ticket->getEmpty();
        }

        // SLA State
        if (!$notcreated) {
            if ((!empty($ticket->fields['time_to_resolve'])
                    && ($ticket->fields['solvedate'] > $ticket->fields['time_to_resolve'])
                    || (!empty($ticket->fields['time_to_resolve']) && (strtotime($ticket->fields['time_to_resolve']) < time())))
                && !in_array($ticket->fields['status'], $status)
            ) {
                $sla_state = self::SLA_LATE;
            } else {
                if (!in_array($ticket->fields['status'], $status)
                    && $ticket->fields['time_to_resolve'] != null
                    && $ticket->fields['date'] != null) {
                    $total_time = (strtotime($ticket->fields['time_to_resolve']) - strtotime($ticket->fields['date']));
                    $current_time = $total_time - (strtotime($ticket->fields['time_to_resolve']) - time());

                    if ($total_time > 0) {
                        $time_percent = $current_time * 100 / $total_time;
                    } else {
                        $time_percent = 100;
                    }

                    if (!empty($ticket->fields['time_to_resolve']) && $time_percent > 75) {
                        $sla_state = self::SLA_TODO;
                    } else {
                        $sla_state = self::SLA_PLANNED;
                    }
                } else {
                    $sla_state = self::SLA_NOTCREATED;
                }
            }
        } else {
            $sla_state = self::SLA_NOTCREATED;
        }

        return $sla_state;
    }

    /**
     * Get the specific massive actions
     *
     * @param null $checkitem link item to check right   (default NULL)
     *
     * @return array array of massive actions
     * *@since version 0.84
     */
    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);
        if ($isadmin) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate'] = _sx('button', 'Duplicate');
        }

        return $actions;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     *
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'duplicate':
                echo "&nbsp;" .
                    Html::submit(__('Validate'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM $item
     * @param array $ids
     *
     * @return void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM    $item,
        array         $ids
    )
    {
        switch ($ma->getAction()) {
            case 'duplicate':
                if (__CLASS__ == $item->getType()) {
                    foreach ($ids as $key) {
                        if ($item->can($key, UPDATE)) {
                            if ($item->executeDuplicate(['metademands_id' => $key])) {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                            }
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * @return array
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        $forbidden[] = 'merge';
        $forbidden[] = 'clone';
        return $forbidden;
    }


    public function displayHeader()
    {
        Html::header(__('Configure demands', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand", "metademand");
    }

    /**
     * Action after ticket creation with metademands
     *
     * @param $plug
     */
    public static function getPluginAfterCreateTicket($plug, $params)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            if (Plugin::isPluginActive($plug)) {
                $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

                foreach ($pluginclasses as $pluginclass) {
                    if (!class_exists($pluginclass)) {
                        continue;
                    }
                    $form[$pluginclass] = [];
                    $item = $dbu->getItemForItemtype($pluginclass);
                    if ($item && is_callable([$item, 'afterCreateTicket'])) {
                        return $item->afterCreateTicket($params);
                    }
                }
            }
        }
    }

    /**
     * Returns the translation of the field
     *
     * @param        $id
     * @param type $field
     * @param string $lang
     *
     * @return type
     * @global type $DB
     */
    public static function displayField($id, $field, $lang = '')
    {
        global $DB;

        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_metademands_metademandtranslations',
            'WHERE' => [
                'itemtype' => self::getType(),
                'items_id' => $id,
                'field' => $field,
                'language' => $_SESSION['glpilanguage']
            ]]);

        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                'FROM' => 'glpi_plugin_metademands_metademandtranslations',
                'WHERE' => [
                    'itemtype' => self::getType(),
                    'items_id' => $id,
                    'field' => $field,
                    'language' => $lang
                ]]);
        }


        if (count($iterator)) {
            foreach ($iterator as $data) {
                $res = $data['value'];
            }
        } else {
            //            $res = Dropdown::getDropdownName('glpi_plugin_metademands_metademandtranslations',$id);
        }
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            if (count($iterator2)) {
                foreach ($iterator2 as $data2) {
                    $res .= ' / ' . $data2['value'];
                    $iterator2->next();
                }
            }
        }
        return $res;
    }

//    public function checkTaskAllowed($metademands_id, $values, $tasks)
//    {
//        $in = [];
//        $out = [];
//        $field = new PluginMetademandsField();
//        $fields = $field->find(["plugin_metademands_metademands_id" => $metademands_id]);
//        foreach ($fields as $f) {
//            $check_values = PluginMetademandsField::_unserialize($f['check_value']);
//            $tasks_fields = PluginMetademandsField::_unserialize($f['plugin_metademands_tasks_id']);
//            if (is_array($check_values)) {
//                foreach ($check_values as $id => $check) {
//                    if ($check != "0") {
//                        switch ($f['type']) {
//                        }
//                        if (isset($values["fields"][$f['id']])) {
//                            if (is_array($values["fields"][$f['id']])) {
//                                if (in_array($check, $values["fields"][$f['id']])) {
//                                    $in[] = $tasks_fields[$id];
//                                } else {
//                                    $out[] = $tasks_fields[$id];
//                                }
//                            } else {
//                                if ($check == $values["fields"][$f['id']]) {
//                                    $in[] = $tasks_fields[$id];
//                                } else {
//                                    $out[] = $tasks_fields[$id];
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//        foreach ($out as $o) {
//            if (!in_array($o, $in)) {
//                unset($tasks[$o]);
//            }
//        }
//        return $tasks;
//    }


    public static function getRunningMetademands(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $dbu = new DbUtils();

        $default_params = [
            'label' => __("Running metademands", 'metademands'),
            'icon' => PluginMetademandsMenu::getIcon(),
            'apply_filters' => [],
        ];

        $get_running_parents_tickets_meta =
            "SELECT COUNT(`glpi_plugin_metademands_tickets_metademands`.`id`) as 'total_running' FROM `glpi_plugin_metademands_tickets_metademands`
                        LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` =  `glpi_plugin_metademands_tickets_metademands`.`tickets_id` WHERE
                            `glpi_tickets`.`is_deleted` = 0 AND `glpi_plugin_metademands_tickets_metademands`.`status` =  
                                    " . PluginMetademandsTicket_Metademand::RUNNING . " " .
            $dbu->getEntitiesRestrictRequest('AND', 'glpi_tickets');


        $total_running_parents_meta = $DB->query($get_running_parents_tickets_meta);

        $total_running = 0;
        while ($row = $DB->fetchArray($total_running_parents_meta)) {
            $total_running = $row['total_running'];
        }


        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 9500, // status
                    'searchtype' => 'equals',
                    'value' => PluginMetademandsTicket_Metademand::RUNNING
                ]
            ],
            'reset' => 'reset'
        ];

        $url = Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);


        return [
            'number' => $total_running,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }

    public static function getRunningMetademandsAndMygroups(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $dbu = new DbUtils();

        $default_params = [
            'label' => __("Running metademands with tickets of my groups", "metademands"),
            'icon' => PluginMetademandsMenu::getIcon(),
            'apply_filters' => [],
        ];

        $get_running_parents_tickets_meta =
            "SELECT COUNT(DISTINCT(`glpi_plugin_metademands_tickets_metademands`.`id`)) as 'total_running' FROM `glpi_tickets`
                        LEFT JOIN `glpi_plugin_metademands_tickets_metademands` ON `glpi_tickets`.`id` =  `glpi_plugin_metademands_tickets_metademands`.`tickets_id`
                         LEFT JOIN `glpi_plugin_metademands_tickets_tasks`  ON (`glpi_tickets`.`id` = `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` )
                         LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_metademands 
                             ON (`glpi_plugin_metademands_tickets_tasks`.`tickets_id` = `glpi_groups_tickets_metademands`.`tickets_id` AND `glpi_groups_tickets_metademands`.`type` = '" . CommonITILActor::ASSIGN . "') 
                         LEFT JOIN `glpi_groups` AS glpi_groups_metademands ON (`glpi_groups_tickets_metademands`.`groups_id` = `glpi_groups_metademands`.`id` ) WHERE
                            `glpi_tickets`.`is_deleted` = 0 AND `glpi_plugin_metademands_tickets_metademands`.`status` =  
                                    " . PluginMetademandsTicket_Metademand::RUNNING . " AND (`glpi_groups_metademands`.`id` IN ('" . implode("','", $_SESSION['glpigroups']) . "'))  " .
            $dbu->getEntitiesRestrictRequest('AND', 'glpi_tickets');

        $total_running_parents_meta = $DB->query($get_running_parents_tickets_meta);

        $total_running = 0;
        while ($row = $DB->fetchArray($total_running_parents_meta)) {
            $total_running = $row['total_running'];
        }


        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 9500, // metademand status
                    'searchtype' => 'equals',
                    'value' => PluginMetademandsTicket_Metademand::RUNNING
                ],
                [
                    'link' => 'AND',
                    'field' => 9502, // group
                    'searchtype' => 'equals',
                    'value' => "mygroups"
                ],
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => "notold"
                ]
            ],
            'reset' => 'reset'
        ];

        $url = Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);


        return [
            'number' => $total_running,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }


    public static function getMetademandsToBeClosed(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $dbu = new DbUtils();

        $default_params = [
            'label' => __("Metademands to be closed", 'metademands'),
            'icon' => PluginMetademandsMenu::getIcon(),
            'apply_filters' => [],
        ];

        $get_closed_parents_tickets_meta =
            "SELECT COUNT(`glpi_plugin_metademands_tickets_metademands`.`id`) as 'total_to_closed' FROM `glpi_plugin_metademands_tickets_metademands`
                        LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` =  `glpi_plugin_metademands_tickets_metademands`.`tickets_id` WHERE
                            `glpi_tickets`.`is_deleted` = 0 AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "','" . Ticket::SOLVED . "') AND `glpi_plugin_metademands_tickets_metademands`.`status` =  
                                    " . PluginMetademandsTicket_Metademand::TO_CLOSED . " " .
            $dbu->getEntitiesRestrictRequest('AND', 'glpi_tickets');


        $results_closed_parents = $DB->query($get_closed_parents_tickets_meta);

        $total_closed = 0;
        while ($row = $DB->fetchArray($results_closed_parents)) {
            $total_closed = $row['total_to_closed'];
        }


        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 9500, // status
                    'searchtype' => 'equals',
                    'value' => PluginMetademandsTicket_Metademand::TO_CLOSED
                ],
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => "notold"
                ]
            ],
            'reset' => 'reset'
        ];

        $url = Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);

        return [
            'number' => $total_closed,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }

    public static function getMetademandsToBeValidated(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $dbu = new DbUtils();

        $default_params = [
            'label' => __("Metademands to be validated", 'metademands'),
            'icon' => PluginMetademandsMenu::getIcon(),
            'apply_filters' => [],
        ];

        $get_to_validated_meta =
            "SELECT COUNT(`glpi_plugin_metademands_metademandvalidations`.`id`) as 'total_to_validated' 
          FROM `glpi_plugin_metademands_metademandvalidations`
         LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` =  `glpi_plugin_metademands_metademandvalidations`.`tickets_id` 
         WHERE  `glpi_tickets`.`is_deleted` = 0 AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "','" . Ticket::SOLVED . "')
           AND `glpi_plugin_metademands_metademandvalidations`.`validate` IN (" . PluginMetademandsMetademandValidation::TO_VALIDATE . "," . PluginMetademandsMetademandValidation::TO_VALIDATE_WITHOUTTASK . ")" .
            $dbu->getEntitiesRestrictRequest('AND', 'glpi_tickets');


        $results_meta_to_validated = $DB->query($get_to_validated_meta);

        $total_to_validated = 0;
        while ($row = $DB->fetchArray($results_meta_to_validated)) {
            $total_to_validated = $row['total_to_validated'];
        }


        $s_criteria = [
            'criteria' => [
                0 => [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => "notold"
                ],
                [
                    'link' => 'AND',
                    'criteria' => [
                        [
                            'link' => 'AND',
                            'field' => 9501, // validation status
                            'searchtype' => 'equals',
                            'value' => PluginMetademandsMetademandValidation::TO_VALIDATE
                        ],
                        [
                            'link' => 'OR',
                            'field' => 9501, // validation status
                            'searchtype' => 'equals',
                            'value' => PluginMetademandsMetademandValidation::TO_VALIDATE_WITHOUTTASK
                        ]
                    ]
                ],
            ],
            'reset' => 'reset'
        ];

        $url = Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);

        return [
            'number' => $total_to_validated,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }


    /**
     * Actions done when item is deleted from the database
     *
     * @return void
     **/
    public function cleanDBonPurge()
    {
        $temp = new PluginMetademandsMetademandTask();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsField();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsTicketField();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsTicket_Metademand();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsMetademandTask();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsGroup();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsMetademand_Resource();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsBasketline();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new PluginMetademandsMetademandValidation();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);
    }

    /**
     * @param $id
     **/
    public static function showAvailableTags($id)
    {
        $self = new self();
        $tags = $self->getTags($id);

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th>" . __('Tag') . "</th>
                <th>" . __('Label') . "</th>
            </tr>";
        foreach ($tags as $tag => $values) {
            echo "<tr>
                  <td>#" . $tag . "#</td>
                  <td>" . $values . "</td>
               </tr>";
        }
        echo "</table></div>";
    }


    /** Display Tags available for the metademand $id
     *
     * @param $id
     **/
    public function getTags($id)
    {
        $fields = $this->find(['id' => $id]);
        $res = [];
        foreach ($fields as $field) {
            $res[$field['id']] = $field['name'];
        }

        return $res;
    }

    public function exportAsXML()
    {
        $fields = $this->fields;
        $metatranslation = new PluginMetademandsMetademandTranslation();
        $translations = $metatranslation->find(['items_id' => $this->getID(),
            'itemtype' => PluginMetademandsMetademand::getType()]);
        foreach ($translations as $id => $translation) {
            $fields['translations']['meta_translation' . $id] = $translation;
        }
        $metafield = new PluginMetademandsField();
        $metafieldoption = new PluginMetademandsFieldOption();
        $metafields = $metafield->find(['plugin_metademands_metademands_id' => $this->getID()]);
        $fields['metafields'] = [];
        $fields['metafieldoptions'] = [];
        foreach ($metafields as $id => $metafield) {
            $fields['metafields']['field' . $id] = $metafield;

            $metafieldoptions = $metafieldoption->find(['plugin_metademands_fields_id' => $metafield["id"]]);

            foreach ($metafieldoptions as $idoptions => $metafieldopt) {
                $fields['metafieldoptions']['fieldoptions' . $idoptions] = $metafieldopt;
            }
        }

        $fieldtranslation = new PluginMetademandsFieldTranslation();
        foreach ($fields['metafields'] as $id => $f) {
            $translationsfield = $fieldtranslation->find(['items_id' => $f['id'],
                'itemtype' => PluginMetademandsField::getType()]);
            foreach ($translationsfield as $k => $v) {
                $fields['metafields'][$id]['fieldtranslations']['translation'] = $v;
            }
        }
        $resourceMeta = new PluginMetademandsMetademand_Resource();
        $resourceMeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->getID()]);
        $fields['resource'] = $resourceMeta->fields;
        $meta_Task = new PluginMetademandsTask();
        $tasks = $meta_Task->find(['plugin_metademands_metademands_id' => $this->getID()]);
        $fields['tasks'] = [];
        foreach ($tasks as $id => $task) {
            $fields['tasks']['task' . $id] = $task;
        }
        $metaTask = new PluginMetademandsMetademandTask();
        $metatasks = $metaTask->find(['plugin_metademands_metademands_id' => $this->getID()]);
        foreach ($metatasks as $id => $task) {
            $fields['metatasks']['metatask' . $id] = $task;
        }
        $ticketTask = new PluginMetademandsTicketTask();

        foreach ($fields['tasks'] as $id => $task) {
            $ticketTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']]);
            $fields['tasks'][$id]['tickettask'] = $ticketTask->fields;
        }

        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><metademand></metademand>");

        $this->toXml($xml, $fields);

        $name = "/metademands/" . $this->getField('name') . ".xml";

        $xml->saveXML(GLPI_PLUGIN_DOC_DIR . $name);

        return "_plugins" . $name;
    }

    public function toXml(SimpleXMLElement &$parent, array &$data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $parent->addChild($key);
                $this->toXml($child, $value);
            } else {
                // if the key is an integer, it needs text with it to actually work.

                if ($key != 0 && $key == (int)$key) {
                    $key = "key_$key";
                }

                //            if($key == 'name' || $key == 'completename' || $key == 'comments' || $key == 'label2')
                if ($value != NULL) {
                    $value = htmlspecialchars($value, ENT_NOQUOTES);
                    $parent->addChild($key, $value);
                }

            }
        }
    }

    public function importXml()
    {
        if (isset($_FILES['meta_file'])) {
            if (!count($_FILES['meta_file'])
                || empty($_FILES['meta_file']['name'])
                || !is_file($_FILES['meta_file']['tmp_name'])
            ) {
                switch ($_FILES['meta_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        Session::addMessageAfterRedirect(
                            __('File too large to be added.'),
                            false,
                            ERROR
                        );
                        return false;
                        break;

                    case UPLOAD_ERR_NO_FILE:
                        Session::addMessageAfterRedirect(__('No file specified', 'metademands'), false, ERROR);
                        return false;
                        break;
                }
            } else {
                $tmp = explode(".", $_FILES['meta_file']['name']);
                $extension = array_pop($tmp);
                if (Toolbox::getMime($_FILES['meta_file']['tmp_name'], 'text') && $extension == "xml") {
                    // Unlink old picture (clean on changing format)
                    $filename = "tmpfileMeta";
                    $picture_path = GLPI_PLUGIN_DOC_DIR . "/metademands/{$filename}.$extension";
                    Document::renameForce($_FILES['meta_file']['tmp_name'], $picture_path);
                    $file = $picture_path;
                } else {
                    Session::addMessageAfterRedirect(
                        __('The file is not an XML file', 'metademands'),
                        false,
                        ERROR
                    );
                    return false;
                }
            }
        }


        //      $xml   = simplexml_load_file(GLPI_PLUGIN_DOC_DIR . '/test.xml');
        $xml = simplexml_load_file($file);
        $json = json_encode($xml);
        $datas = json_decode($json, true);

        $metademand = new PluginMetademandsMetademand();
        $oldId = $datas['id'];
        unset($datas['id']);
        unset($datas['date_creation']);
        unset($datas['date_mod']);
        unset($datas['itilcategories_id']);
        $datas['entities_id'] = $_SESSION['glpiactive_entity'];

        $mapTableField = [];
        $mapTableFieldReverse = [];


        $fields = [];
        if (isset($datas['metafields'])) {
            $fields = $datas['metafields'];
        }

        $fieldoptions = [];
        if (isset($datas['metafieldoptions'])) {
            $fieldoptions = $datas['metafieldoptions'];
        }

        $tasks = [];
        if (isset($datas['tasks'])) {
            $tasks = $datas['tasks'];
        }

        $resource = [];
        if (isset($datas['resources'])) {
            $resource = $datas['resources'];
        }

        $metatasks = [];
        if (isset($datas['metatasks'])) {
            $metatasks = $datas['metatasks'];
        }

        $translations = [];
        if (isset($datas['translations'])) {
            $translations = $datas['translations'];
        }


        foreach ($datas as $key => $data) {
            if (is_array($data) && empty($data)) {
                $datas[$key] = '';
            } elseif (!is_array($data)) {
                $datas[$key] = Html::entity_decode_deep($data);
            }
        }
        $datas = Toolbox::addslashes_deep($datas);
        $newIDMeta = $metademand->add($datas);
        //      $translations = [];
        foreach ($fields as $k => $field) {
            foreach ($field as $key => $f) {
                $fields[$k][$key] = Html::entity_decode_deep($f);

                if ($key == "custom_values") {
                    $fields[$k][$key] = PluginMetademandsField::_unserialize($f);
                    $fields[$k][$key] = PluginMetademandsField::_serialize($fields[$k][$key]);
                    if (is_null($fields[$k][$key])) {
                        $fields[$k][$key] = "[]";
                    }
                } elseif ($key == "comment_values") {
                    $fields[$k][$key] = PluginMetademandsField::_unserialize($f);
                    $fields[$k][$key] = PluginMetademandsField::_serialize($fields[$k][$key]);
                    if (is_null($fields[$k][$key])) {
                        $fields[$k][$key] = "[]";
                    }
                } elseif ($key == "default_values") {
                    $fields[$k][$key] = PluginMetademandsField::_unserialize($f);
                    $fields[$k][$key] = PluginMetademandsField::_serialize($fields[$k][$key]);
                    if (is_null($fields[$k][$key])) {
                        $fields[$k][$key] = "[]";
                    }
                } elseif ($key == "informations_to_display") {
                    $fields[$k][$key] = PluginMetademandsField::_unserialize($f);
                    $fields[$k][$key] = PluginMetademandsField::_serialize($fields[$k][$key]);
                    if (is_null($fields[$k][$key])) {
                        $fields[$k][$key] = "[]";
                    }
                } elseif ($key == "fieldtranslations") {
                    $fieldstranslations = $f;
                } else {
                    if (is_array($f) && empty($f)) {
                        $fields[$k][$key] = '';
                    }
                }
            }

            $oldIDField = $fields[$k]["id"];
            unset($fields[$k]["id"]);
            $fields[$k]['entities_id'] = $_SESSION['glpiactive_entity'];
            $fields[$k] = Toolbox::addslashes_deep($fields[$k]);
            $fields[$k]["plugin_metademands_metademands_id"] = $newIDMeta;
            $fields[$k]["date_creation"] = $_SESSION['glpi_currenttime'];
            $fields[$k]["date_mod"] = $_SESSION['glpi_currenttime'];

            $metaField = new PluginMetademandsField();
            $newIDField = $metaField->add($fields[$k]);


            $mapTableField[$oldIDField] = $newIDField;
            $mapTableFieldReverse[$newIDField] = $oldIDField;
            if (isset($fieldstranslations)) {
                foreach ($fieldstranslations as $fieldstranslation) {
                    unset($fieldstranslation['id']);
                    $fieldstranslation['value'] = Html::entity_decode_deep(Toolbox::addslashes_deep($fieldstranslation['value']));
                    $fieldstranslation['field'] = Html::entity_decode_deep(Toolbox::addslashes_deep($fieldstranslation['field']));
                    $fieldstranslation['items_id'] = $newIDField;

                    $trans = new PluginMetademandsFieldTranslation();
                    $trans->add($fieldstranslation);
                }
            }

            //TODO Change fields id for link_to_user fields
        }
        $mapTableTask = [];
        $mapTableTaskReverse = [];

        foreach ($tasks as $k => $task) {
            $oldIDTask = $task['id'];
            unset($task['id']);
            unset($task['ancestors_cache']);
            unset($task['sons_cache']);
            $task = Toolbox::addslashes_deep($task);
            $tickettask = $task['tickettask'];
            foreach ($task as $key => $val) {
                if (is_array($val)) {
                    $task[$key] = "";
                } else {
                    $task[$key] = Html::entity_decode_deep($val);
                }
            }
            $task['entities_id'] = $_SESSION['glpiactive_entity'];

            $task['plugin_metademands_metademands_id'] = $newIDMeta;
            $meta_task = new PluginMetademandsTask();
            $newIDTask = $meta_task->add($task);

            $mapTableTask[$oldIDTask] = $newIDTask;
            $mapTableTaskReverse[$newIDTask] = $oldIDTask;


            if (is_array($tickettask) && !empty($tickettask)) {
                unset($tickettask['id']);
                foreach ($tickettask as $key => $val) {
                    if (is_array($val) && empty($val)) {
                        $tickettask[$key] = '';
                    } elseif (!is_array($val)) {
                        $tickettask[$key] = Html::entity_decode_deep($val);
                    }
                }
                $tickettask['plugin_metademands_tasks_id'] = $newIDTask;
                $tickettaskP = new PluginMetademandsTicketTask();
                $tickettaskP->add($tickettask);
            }
        }


        //Add new options & update fields
        $fieldMetaopt = new PluginMetademandsFieldOption();
//        Toolbox::logInfo($fieldoptions);
        foreach ($fieldoptions as $new => $old) {

//            $fieldMeta->getFromDBByCrit(["plugin_metademands_fileds_id" => $new]);

            $check_value = $old["check_value"]??0;
            $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"]??0;
            $plugin_metademands_tasks_id = $old["plugin_metademands_tasks_id"]??0;
            $fields_link = $old["fields_link"]??0;
            $hidden_link = $old["hidden_link"]??0;
            $hidden_block = $old["hidden_block"]??0;
            $users_id_validate = $old["users_id_validate"]??0;
            $childs_blocks = $old["childs_blocks"]??[];
            $checkbox_value = $old["checkbox_value"]??0;
            $checkbox_id = $old["checkbox_id"]??0;
//            $parent_field_id = $old["parent_field_id"]??0;
//
            $toUpdate = [];
            if ($check_value != 0) {
                $toUpdate["check_value"] = $check_value;
            }
            if ($plugin_metademands_tasks_id != 0 && isset($mapTableTask[$plugin_metademands_tasks_id])) {
                $toUpdate["plugin_metademands_tasks_id"] = $mapTableTask[$plugin_metademands_tasks_id];
            }
            if ($fields_link != 0 && isset($mapTableField[$fields_link])) {
                $toUpdate["fields_link"] = $mapTableField[$fields_link];
            }
            if ($hidden_link != 0 && isset($mapTableField[$hidden_link])) {
                $toUpdate["hidden_link"] = $mapTableField[$hidden_link];
            }
            if ($hidden_block != 0) {
                $toUpdate["hidden_block"] = $hidden_block;
            }
            if ($users_id_validate != 0) {
                $toUpdate["users_id_validate"] = $users_id_validate;
            }
            if ($childs_blocks) {
                $toUpdate["childs_blocks"] = $childs_blocks;
            }
            if ($checkbox_value != 0) {
                $toUpdate["checkbox_value"] = $checkbox_value;
            }
            if ($checkbox_id != 0) {
                $toUpdate["checkbox_id"] = $checkbox_id;
            }
//            if ($parent_field_id != 0 && isset($mapTableField[$parent_field_id])) {
//                $toUpdate["parent_field_id"] = $mapTableField[$parent_field_id];
//            }
//
            if ($plugin_metademands_fields_id != 0
                && isset($mapTableField[$plugin_metademands_fields_id])) {
                $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
            }
//            Toolbox::logInfo($toUpdate);
            $fieldMetaopt->add($toUpdate);
        }

        foreach ($mapTableTaskReverse as $new => $old) {
            $meta_task = new PluginMetademandsTask();
            $meta_task->getFromDB($new);
            $toUpdate = [];
            $toUpdate['id'] = $new;
            if (isset($mapTableTask[$meta_task->fields["plugin_metademands_tasks_id"]])) {
                $toUpdate["plugin_metademands_tasks_id"] = $mapTableTask[$meta_task->fields["plugin_metademands_tasks_id"]];
            }
            $meta_task->update($toUpdate);
        }

        if (!empty($resource)) {
            $resource['plugin_metademands_metademands_id'] = $newIDMeta;
            $resource_meta = new PluginMetademandsMetademand_Resource();
            $resource_meta->add($resource);
        }


        if (!empty($metatasks)) {
            foreach ($metatasks as $key => $metatask) {
                $meta_metatask = new PluginMetademandsMetademandTask();
                $metat = [];
                $metat['plugin_metademands_metademands_id'] = $newIDMeta;
                $metat['plugin_metademands_tasks_id'] = $mapTableTask[$metatask['plugin_metademands_tasks_id']];
                $meta_metatask->add($metat);
            }
        }


        if (!empty($translations)) {
            foreach ($translations as $key => $trans) {
                $meta_translation = new PluginMetademandsMetademandTranslation();
                $trans['value'] = Html::entity_decode_deep(Toolbox::addslashes_deep($trans['value']));
                $trans['field'] = Html::entity_decode_deep($trans['field']);
                unset($trans['id']);
                $trans['items_id'] = $newIDMeta;

                $meta_translation->add($trans);
            }
        }
        unlink($file);

        return $newIDMeta;
    }

    public function showImportForm()
    {
        echo "<div align='center'>";
        echo "<form name='import_file_form' id='import_file_form' method='post'
            action='" . self::getFormURL() . "' enctype='multipart/form-data'>";
        echo " <table class='tab_cadre' width='30%' cellpadding='5'>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __("Metademand file to import", 'metademands');
        echo "</td>";
        echo "<td>";
        //      echo Html::file(['name'=>'meta_file', 'accept' => 'text/*']);
        echo "<input class='form-control' type='file' name='meta_file' accept='text/*'>";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td  class='center' colspan='2'>";
        echo Html::submit(__('Import', 'metademands'), ['name' => 'import_file', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    public function getBetween($string, $start = "", $end = "")
    {
        if ($string != null && str_contains($string, $start)) { // required if $start not exist in $string
            $startCharCount = strpos($string, $start) + strlen($start);
            $firstSubStr = substr($string, $startCharCount, strlen($string));
            $endCharCount = strpos($firstSubStr, $end);
            if ($endCharCount == 0) {
                $endCharCount = strlen($firstSubStr);
            }
            return substr($firstSubStr, 0, $endCharCount);
        } else {
            return '';
        }
    }

    /**
     * @param       $field
     * @param       $users_id
     * @param       $title
     * @param       $line
     * @param false $bypass
     *
     * @return array|string|string[]
     */
    public static function getContentForUser($field, $users_id, $title, $line, $bypass = false)
    {
        if ($bypass === true && is_numeric($title)) {
            return str_replace("#" . $title . "#", "", $line);
        }
        switch ($field) {
            case "login":
            case "requester.login":
                $user = new User();
                $user->getFromDB($users_id);
                $value = $user->fields['name'];
                return str_replace("#" . $title . "#", $value, $line);
                break;
            case "name":
            case "requester.name":
                $user = new User();
                $user->getFromDB($users_id);
                $value = $user->fields['realname'];
                return str_replace("#" . $title . "#", $value, $line);
                break;
            case "firstname":
            case "requester.firstname":
                $user = new User();
                $user->getFromDB($users_id);
                $value = $user->fields['firstname'];
                return str_replace("#" . $title . "#", $value, $line);
                break;
            case "email":
            case "requester.email":
                $user = new UserEmail();
                $user->getFromDBByCrit(['users_id' => $users_id, 'is_default' => 1]);
                $value = $user->fields['email'];
                return str_replace("#" . $title . "#", $value, $line);
                break;
        }
        return $line;
    }

    /**
     * @param $state
     *
     * @return string
     */
    public static function getStateItem($state)
    {
        switch ($state) {
            case self::TODO:
                return "<span><i class=\"fas fa-3x fa-hourglass-half\"></i></span>";
                break;
            case self::DONE:
                return "<span><i class=\"fas fa-3x fa-check\"></i></span>";
                break;
            case self::FAIL:
                return "<span><i class=\"fas fa-3x fa-times\"></i></span>";
                break;
        }
    }

    public function showProgressionForm($item)
    {
        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/timeline_user.css");

        echo "<table class='tab_cadre_fixe' id='mainformtable'>";
        echo "<tr class='tab_bg_1 center'>";
        echo "<th>";
        echo __('Progression of your demand', 'metademands');
        echo "</th>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";

        echo "<section id='timeline'>";

        //begin ticket
        echo "<article>";
        echo "<div class='inner'>";
        echo "<span class='bulle bulleMarge'>";
        echo "<span style='margin-left: 5px;'><i class='fas fa-3x fa-play'></i></span>";
        echo "</span>";
        echo "<h2 class='dateColor'>" . __("Creation date");
        echo "<i class='fas fa-calendar' style='float: right;'></i></h2>";
        echo "<p>" . Html::convDateTime($item->fields["date"]) . "</p>";
        echo "</div>";
        echo "</article>";

        $ticket_metademand = new PluginMetademandsTicket_Metademand();
        $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $item->fields['id']]);
        $tickets_found = [];
        // If ticket is Parent : Check if all sons ticket are closed
        if (count($ticket_metademand_data)) {
            $ticket_metademand_data = reset($ticket_metademand_data);
            $tickets_found = PluginMetademandsTicket::getSonTickets(
                $item->fields['id'],
                $ticket_metademand_data['plugin_metademands_metademands_id']
            );
        } else {
            //         $ticket_task      = new PluginMetademandsTicket_Task();
            //         $ticket_task_data = $ticket_task->find(['tickets_id' => $item->fields['id']]);
            //
            //         if (count($ticket_task_data)) {
            //            $tickets_found = PluginMetademandsTicket::getAncestorTickets($item->fields['id'], true);
            //         }
        }
        $tickets_existant = [];

        if (count($tickets_found)) {
            foreach ($tickets_found as $tickets) {
                if (!empty($tickets['tickets_id'])) {
                    $tickets_existant[] = $tickets;
                } else {
                    $tickets_next[] = $tickets;
                }
            }
            if (count($tickets_existant)) {
                $ticket = new Ticket();
                foreach ($tickets_existant as $values) {
                    // Get ticket values if it exists
                    $ticket->getFromDB($values['tickets_id']);
                    $class = "";
                    $fa = "fa-tasks";
                    $state = self::TODO;
                    if (in_array($ticket->fields['status'], $ticket->getSolvedStatusArray())) {
                        $state = self::DONE;
                    }
                    $class_state = "";


                    if (Plugin::isPluginActive("servicecatalog")) {
                        $fa = PluginServicecatalogCategory::getUsedConfig("inherit_config", $ticket->fields['itilcategories_id'], 'icon');
                        $color = PluginServicecatalogCategory::getUsedConfig("inherit_config", $ticket->fields['itilcategories_id'], "background_color");
                        $class = "background-color: $color;box-shadow: 0 0 0 7px $color !important;";
                        $class_state = "box-shadow: 0 0 0 7px $color !important;";
                    }

                    echo "<article>";
                    echo "<div class='inner'>";
                    echo "<span class='bulle bulleMarge bulleDefault' style='$class_state'>";
                    echo self::getStateItem($state);
                    echo "</span>";
                    echo "<h2 style='$class'><i class='fas $fa' style='float: right;'></i>" . $ticket->getLink() . "</h2>";

                    $statusicon = CommonITILObject::getStatusClass($ticket->fields['status']);

                    $dateEnd = (!empty($ticket->fields["solvedate"])) ? __('Done on', 'metademands') . " " . Html::convDateTime($ticket->fields["solvedate"]) : __("In progress", 'metademands');
                    echo "<p>";
                    echo "<i class='" . $statusicon . "'></i>&nbsp;";
                    echo $dateEnd;
                    echo "</p>";
                    echo "<p></p>";
                    echo "</div>";
                    echo "</article>";
                }
            }
        }

        //end ticket
        $dateEnd = (!empty($item->fields["solvedate"])) ? Html::convDateTime($item->fields["solvedate"]) : __("Not yet completed", 'metademands');
        $class_end = (!empty($item->fields["solvedate"])) ? "bulleDone" : "";
        $fa_end = (!empty($item->fields["solvedate"])) ? "fa-check" : "fa-hourglass-half";
        echo "<article>";
        echo "<div class='inner'>";
        echo "<span class='bulle bulleMarge $class_end'>";
        echo "<span><i class=\"fas fa-3x $fa_end\"></i></span>";
        echo "</span>";

        echo "<h2 class='dateColor'>" . __("End date") . "<i class='fas fa-calendar' style='float: right;'></i></h2>";
        echo " <p>" . $dateEnd . "</p>";
        echo "</div>";
        echo "</article>";

        echo "</section>";

        echo "</td>";
        echo "</tr>";
        echo "</table>";
    }

    /**
     * Manage events from js/fuzzysearch.js
     *
     * @param string $action action to switch (should be actually 'getHtml' or 'getList')
     *
     * @return string
     * @since 9.2
     *
     */
    public static function fuzzySearch($action = '', $type = Ticket::DEMAND_TYPE)
    {
        $title = __("Find a form", "metademands");

        switch ($action) {
            case 'getHtml':
                $placeholder = $title;
                $html = <<<HTML
               <div class="" tabindex="-1" id="mt-fuzzysearch">
                  <div class="">
                     <div class="modal-content">
                        <div class="modal-body" style="padding: 10px;">
                           <input type="text" class="mt-home-trigger-fuzzy form-control" placeholder="{$placeholder}">
                           <input type="hidden" name="meta_type" id="meta_type" value="$type"/>
                           <ul class="results list-group mt-2" style="background: #FFF;"></ul>
                        </div>
                     </div>
                  </div>
               </div>

HTML;
                return $html;
                break;

            default:
                $metas = [];
                $metademands = PluginMetademandsWizard::selectMetademands(false, "", $type);

                foreach ($metademands as $id => $values) {
                    $meta = new PluginMetademandsMetademand();
                    if ($meta->getFromDB($id)) {
                        $icon = "fa-share-alt";
                        if (!empty($meta->fields['icon'])) {
                            $icon = $meta->fields['icon'];
                        }
                        if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
                            $name = $meta->getName();
                        } else {
                            $name = $n;
                        }

                        $metas[] = [
                            'title' => $name,
                            'icon' => $icon,
                            'url' => PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=2",
                        ];
                    }
                }

                // return the entries to ajax call
                return json_encode($metas);
                break;
        }
    }

    /**
     * @param     $target
     * @param int $add
     */
    function listOfTemplates($target, $add = 0)
    {
        $dbu = new DbUtils();

        $restrict = ["is_template" => 1] +
            $dbu->getEntitiesRestrictCriteria($this->getTable(), '', '', $this->maybeRecursive()) +
            ["ORDER" => "name"];

        $templates = $dbu->getAllDataFromTable($this->getTable(), $restrict);

        if (Session::isMultiEntitiesMode()) {
            $colsup = 1;
        } else {
            $colsup = 0;
        }

        echo "<div align='center'><table class='tab_cadre'>";
        if ($add) {
            echo "<tr><th colspan='" . (2 + $colsup) . "'>" . __('Choose a template') . " - " . self::getTypeName(2) . "</th>";
        } else {
            echo "<tr><th colspan='" . (2 + $colsup) . "'>" . __('Templates') . " - " . self::getTypeName(2) . "</th>";
        }

        echo "</tr>";
        if ($add) {

            echo "<tr>";
            echo "<td colspan='" . (2 + $colsup) . "' class='center tab_bg_1'>";
            echo "<a href=\"$target?id=-1&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;" . __('Blank Template') . "&nbsp;&nbsp;&nbsp;</a></td>";
            echo "</tr>";
        }

        foreach ($templates as $template) {

            $templname = $template["template_name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($template["template_name"])) {
                $templname .= "(" . $template["id"] . ")";
            }

            echo "<tr>";
            echo "<td class='center tab_bg_1'>";
            if (!$add) {
                echo "<a href=\"$target?id=" . $template["id"] . "&amp;withtemplate=1\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

                if (Session::isMultiEntitiesMode()) {
                    echo "<td class='center tab_bg_2'>";
                    echo Dropdown::getDropdownName("glpi_entities", $template['entities_id']);
                    echo "</td>";
                }
                echo "<td class='center tab_bg_2'>";
                Html::showSimpleForm($target,
                    'purge',
                    _x('button', 'Delete permanently'),
                    ['id' => $template["id"], 'withtemplate' => 1]);
                echo "</td>";

            } else {
                echo "<a href=\"$target?id=" . $template["id"] . "&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

                if (Session::isMultiEntitiesMode()) {
                    echo "<td class='center tab_bg_2'>";
                    echo Dropdown::getDropdownName("glpi_entities", $template['entities_id']);
                    echo "</td>";
                }
            }
            echo "</tr>";
        }
        if (!$add) {
            echo "<tr>";
            echo "<td colspan='" . (2 + $colsup) . "' class='tab_bg_2 center'>";
            echo "<b><a href=\"$target?withtemplate=1\">" . __('Add a template...') . "</a></b>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }
}
