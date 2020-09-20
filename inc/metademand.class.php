<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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
class PluginMetademandsMetademand extends CommonDropdown {

   const LOG_ADD        = 1;
   const LOG_UPDATE     = 2;
   const LOG_DELETE     = 3;
   const SLA_TODO       = 1;
   const SLA_LATE       = 2;
   const SLA_FINISHED   = 3;
   const SLA_PLANNED    = 4;
   const SLA_NOTCREATED = 5;

   static $PARENT_PREFIX = '';
   static $SON_PREFIX    = '';
   static $rightname     = 'plugin_metademands';

   const STEP_INIT    = 0;
   const STEP_LIST    = 1;
   const STEP_SHOW    = 2;
   const STEP_CREATE  = 3;
   const STEP_PREVIEW = 4;

   var     $dohistory = false;
   private $config;

   function __construct() {
      $config              = PluginMetademandsConfig::getInstance();
      $this->config        = $config;
      self::$PARENT_PREFIX = $config['parent_ticket_tag'] . '&nbsp;';
      self::$SON_PREFIX    = $config['son_ticket_tag'] . '&nbsp;';
   }

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {

      return _n('Meta-Demand', 'Meta-Demands', $nb, 'metademands');
   }

   /**
    * @return bool|int
    */
   static function canView() {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   /**
    * @return bool
    */
   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   /**
    * @return bool|mixed
    */
   function getConfig() {
      return $this->config;
   }

   /**
    * Display tab for each tickets
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $dbu = new DbUtils();
      if ($dbu->countElementsInTable("glpi_plugin_metademands_tickets_metademands", ["tickets_id" => $item->fields['id']]) ||
          $dbu->countElementsInTable("glpi_plugin_metademands_tickets_tasks", ["tickets_id" => $item->fields['id']])
      ) {
         if (!$withtemplate
             && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            if (($item->getType() == 'Ticket' || $item->getType() == 'PluginResourcesResource')
                && $this->canView()) {
               return self::getTypeName(1);
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
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $metademands = new self();

      switch ($item->getType()) {
         case 'Ticket':
            $metademands->showPluginForTicket($item);
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
   function defineTabs($options = []) {
      $ong = [];

      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginMetademandsField', $ong, $options);
      $this->addStandardTab('PluginMetademandsWizard', $ong, $options);
      $this->addStandardTab('PluginMetademandsTicketField', $ong, $options);
      $this->addStandardTab('PluginMetademandsTask', $ong, $options);
      $this->addStandardTab('PluginMetademandsGroup', $ong, $options);
      return $ong;
   }

   /**
    * @param \Ticket $ticket
    * @param string  $type
    *
    * @return bool|string
    */
   static function redirectForm(Ticket $ticket, $type = 'show') {
      global $CFG_GLPI;

      $conf   = new PluginMetademandsConfig();
      $config = $conf->getInstance();
      if ($config['simpleticket_to_metademand']) {
         if (($type == 'show' && $ticket->fields["id"] == 0)
             || ($type == 'update' && $ticket->fields["id"] > 0)) {
            if (!empty($ticket->input["itilcategories_id"])) {
               $dbu        = new DbUtils();
               $metademand = new PluginMetademandsMetademand();
               $metas      = $metademand->find(['is_active' => 1,
                                                'type'      => $ticket->input["type"]]);
               $cats       = [];

               foreach ($metas as $meta) {
                  $categories = [];
                  if (isset($meta['itilcategories_id'])) {
                     if (is_array(json_decode($meta['itilcategories_id'], true))) {
                        $categories = $meta['itilcategories_id'];
                     } else {
                        $array      = [$meta['itilcategories_id']];
                        $categories = json_encode($array);
                     }
                  }
                  $cats[$meta['id']] = json_decode($categories);
               }

               $meta_concerned = 0;
               foreach ($cats as $meta => $meta_cats) {
                  if (in_array($ticket->input['itilcategories_id'], $meta_cats)) {
                     $meta_concerned = $meta;
                  }
               }

               if ($meta_concerned) {
                  //$meta = reset($metas);
                  // Redirect if not linked to a resource contract type
                  if (!$dbu->countElementsInTable("glpi_plugin_metademands_metademands_resources",
                                                  ["plugin_metademands_metademands_id" => $meta_concerned])) {
                     return $CFG_GLPI["root_doc"] . "/plugins/metademands/front/wizard.form.php?itilcategories_id=" .
                            $ticket->input['itilcategories_id'] . "&metademands_id=" . $meta_concerned . "&tickets_id=" . $ticket->fields["id"] . "&step=" . self::STEP_SHOW;
                  }
               }
            }
         }
      }
      return false;
   }

   /**
    * @param array $input
    *
    * @return array|bool
    */
   function prepareInputForAdd($input) {
      global $DB;
      $cat_already_store = false;
      if (isset($input['itilcategories_id']) && !empty($input['itilcategories_id'])) {

         //retreive all multiple cats from all metademands
         $iterator_cats = $DB->request($this->getTable(), ['FIELDS' => [$this->getTable() => ['id', 'itilcategories_id']]]);
         $cats          = $_POST ['itilcategories_id'];

         while ($data = $iterator_cats->next()) {
            if (is_array(json_decode($data['itilcategories_id']))) {
               $cat_already_store = !empty(array_intersect($cats, json_decode($data['itilcategories_id'])));
            }
            if ($cat_already_store) {
               Session::addMessageAfterRedirect(__('The category is related to a demand. Thank you to select another', 'metademands'), false, ERROR);
               return false;
            }
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
      return $input;
   }


   /**
    * @param array $input
    *
    * @return array|bool
    */
   function prepareInputForUpdate($input) {
      global $DB;
      $cat_already_store = false;
      if (isset($input['itilcategories_id']) && count($input['itilcategories_id']) > 0) {

         //         $restrict = ["`itilcategories_id`" => $input['itilcategories_id'],
         //                      "NOT"                 => ["id" => $input['id']]];
         //         $dbu      = new DbUtils();
         //         $cats     = $dbu->getAllDataFromTable($this->getTable(), $restrict);

         //retreive all multiple cats from all metademands
         $iterator_cats               = $DB->request($this->getTable(), ['FIELDS' => [$this->getTable() => ['id', 'itilcategories_id']]]);
         $iterator_meta_existing_cats = $DB->request(['SELECT' => 'itilcategories_id', 'FROM' => $this->getTable(), 'WHERE' => ['id' => $_POST['id']]]);

         $number_cats_meta = count($iterator_meta_existing_cats);
         if ($number_cats_meta) {
            while ($data = $iterator_meta_existing_cats->next()) {
               $cats = json_decode($data['itilcategories_id']);
            }
            if ($cats == null) {
               $cats = [];
            }
         }

         if (count($_POST ['itilcategories_id']) >= count($cats)) {
            foreach ($_POST ['itilcategories_id'] as $post_cats) {
               if (in_array($post_cats, $cats)) {
                  unset($cats[array_search($post_cats, $cats)]);
               } else {
                  $cats[] = $post_cats;
               }
            }

            while ($data = $iterator_cats->next()) {
               if (is_array(json_decode($data['itilcategories_id'])) && $_POST['id'] != $data['id']) {
                  $cat_already_store = !empty(array_intersect($cats, json_decode($data['itilcategories_id'])));
               }
               if ($cat_already_store) {
                  Session::addMessageAfterRedirect(__('The category is related to a demand. Thank you to select another', 'metademands'), false, ERROR);
                  return false;
               }
            }
            if (!$cat_already_store) {
               $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
            }
         } else {
            $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
         }
      } else {
         $input['itilcategories_id'] = '';
      }

      return $input;
   }

   function post_addItem() {
      parent::post_addItem();

      if (!isset($this->input['id']) || empty($this->input['id'])) {
         $this->input['id'] = $this->fields['id'];
      }
      PluginMetademandsTicketField::updateMandatoryTicketFields($this->input);
   }

   /**
    * @param int $history
    */
   function post_updateItem($history = 1) {
      parent::post_updateItem($history);

      PluginMetademandsTicketField::updateMandatoryTicketFields($this->input);
   }

   /**
    * @param $metademands_id
    *
    * @return string
    */
   function getURL($metademands_id) {
      global $CFG_GLPI;
      if (!empty($metademands_id)) {
         return urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=PluginMetademandsWizard_" . $metademands_id);
      }
   }

   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'            => '92',
         'table'         => 'glpi_itilcategories',
         'field'         => 'name',
         'name'          => __('Category'),
         'datatype'      => 'specific',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'            => '93',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('URL'),
         'datatype'      => 'specific',
         'massiveaction' => false,
         'nosearch'      => true
      ];

      $tab[] = [
         'id'       => '94',
         'table'    => $this->getTable(),
         'field'    => 'is_active',
         'name'     => __('Active'),
         'datatype' => 'bool',
      ];

      return $tab;
   }

   /**
    * @return array|array[]
    */
   function getAdditionalFields() {

      $tab = [
         ['name'  => 'itilcategories_id',
          'label' => __('Category'),
          'type'  => 'specific',
          'list'  => true],
         ['name'  => 'is_active',
          'label' => __('Active'),
          'type'  => 'bool',
          'list'  => true],
         ['name'  => 'url',
          'label' => __('URL'),
          'type'  => 'specific',
          'list'  => true],
         ['name'  => 'icon',
          'label' => __('Icon'),
          'type'  => 'specific',
          'list'  => true],
         ['name'  => 'is_order',
          'label' => __('Use as basket', 'metademands'),
          'type'  => 'bool',
          'list'  => true]

      ];

      return $tab;
   }

   /**
    * @param string       $field
    * @param array|string $values
    * @param array        $options
    *
    * @return string
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'id':
            $metademand = new self();
            $metademand->getFromDB($values[$field]);
            return $metademand->getURL($metademand->fields['id']);

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * @param $metademands_id
    */
   function showDuplication($metademands_id) {

      echo "<table class='tab_glpi metademands_duplication'>";
      echo "<tr>";
      echo "<td><i class='fas fa-exclamation-triangle fa-2x' style='color:orange'></i></td>";
      echo "<td>" . __('Tasks level cannot be changed as unresolved related tickets exist', 'metademands') . "</td>";
      echo "<td width='70px'>";
      echo "<form name='task_form' id='task_form' method='post' 
               action='" . Toolbox::getItemTypeFormURL('PluginMetademandsMetademand') . "'>";
      echo "<input type='submit' name='execute' value=\"" . _sx('button', 'Duplicate') . "\"
                      class='submit'>";
      echo "<input type='hidden' name='_method' value=\"Duplicate\"
                      class='submit'>";
      echo "<input type='hidden' name='metademands_id' value=\"" . $metademands_id . "\"
                      class='submit'>";
      echo "<input type='hidden' name='redirect' value=\"1\"
                      class='submit'>";
      Html::closeForm();
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }

   /**
    * @param       $ID
    * @param array $field
    */
   function displaySpecificTypeField($ID, $field = []) {

      $this->getFromDB($ID);

      switch ($field['name']) {
         case 'url':
            echo $this->getURL($this->fields['id']);
            break;
         case 'itilcategories_id':
            echo "<input type='hidden' name='type' value='" . Ticket::DEMAND_TYPE . "'>";

            //            switch ($this->fields['type']) {
            //               case Ticket::INCIDENT_TYPE :
            //                  $criteria = ['is_incident' => 1];
            //                  break;
            //               case Ticket::DEMAND_TYPE :
            //                  $criteria = ['is_request' => 1];
            //                  break;
            //               default :
            //                  $criteria = [];
            //                  break;
            //            }
            $criteria = ['is_request' => 1];
            $criteria += getEntitiesRestrictCriteria(
               \ITILCategory::getTable(),
               'entities_id',
               $_SESSION['glpiactiveentities'],
               true
            );

            $dbu    = new DbUtils();
            $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $criteria);
            $temp   = [];
            foreach ($result as $item) {
               $temp[$item['id']] = $item['completename'];
            }
            $categories = [];
            if (isset($this->fields['itilcategories_id'])) {
               if (is_array(json_decode($this->fields['itilcategories_id'], true))) {
                  $categories = $this->fields['itilcategories_id'];
               } else {
                  $array      = [$this->fields['itilcategories_id']];
                  $categories = json_encode($array);
               }
            }
            $values = $this->fields['itilcategories_id'] ? json_decode($categories) : [];

            Dropdown::showFromArray('itilcategories_id', $temp,
                                    ['values'   => $values,
                                     'width'    => '100%',
                                     'multiple' => true,
                                     'entity'   => $_SESSION['glpiactiveentities']]);
            break;
         case 'icon':
            $opt = [
               'value'     => isset($this->fields['icon']) ? $this->fields['icon'] : '',
               'maxlength' => 250,
               'size'      => 80,
            ];
            echo Html::input('icon', $opt);
            echo "<br>" . __('Example', 'metademands') . " : fas fa-share-alt";
            if (isset($this->fields['icon'])
                && !empty($this->fields['icon'])) {
               $icon = $this->fields['icon'];
               echo "<br><br><i class='fas-sc sc-fa-color $icon fa-3x' ></i>";
            }
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
   static function addLog($input, $logtype) {

      $new_value = $_SESSION["glpiname"] . " ";
      if ($logtype == self::LOG_ADD) {
         $new_value .= __('field add on demand', 'metademands') . " : ";
      } else if ($logtype == self::LOG_UPDATE) {
         $new_value .= __('field update on demand', 'metademands') . " : ";
      } else if ($logtype == self::LOG_DELETE) {
         $new_value .= __('field delete on demand', 'metademands') . " : ";
      }

      $metademand = new self();
      $metademand->getFromDB($input['plugin_metademands_metademands_id']);

      $field = new PluginMetademandsField();
      $field->getFromDB($input['id']);

      $new_value .= $metademand->getName() . " - " . $field->getName();

      self::addHistory($input['plugin_metademands_metademands_id'], "PluginMetademandsMetademand", "", $new_value);
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
   static function addHistory($ID, $type, $old_value = '', $new_value = '') {
      $changes[0] = 0;
      $changes[1] = $old_value;
      $changes[2] = $new_value;
      Log::history($ID, $type, $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);
   }

   /**
    * methodAddMetademands : Add metademand from WEBSERVICE plugin
    *
    * @param type  $params
    * @param type  $protocol
    *
    * @return type
    * @throws \GlpitestSQLError
    * @throws \GlpitestSQLError
    * @global type $DB
    *
    */
   static function methodAddMetademands($params, $protocol) {

      if (isset($params['help'])) {
         return ['help'           => 'bool,optional',
                 'metademands_id' => 'int,mandatory',
                 'values'         => 'array,optional'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['metademands_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
      }

      if (isset($params['metademands_id']) && !is_numeric($params['metademands_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'metademands_id');
      }

      $metademands = new self();

      if (!$metademands->can(-1, UPDATE) && !PluginMetademandsGroup::isUserHaveRight($params['metademands_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      $meta_data = [];

      if (isset($params['values']['fields']) && count($params['values']['fields'])) {
         foreach ($params['values']['fields'] as $data) {
            $meta_data['fields'][$data['id']] = $data['values'];
         }
      }

      $message = $metademands->addMetademands($params['metademands_id'], $meta_data);

      return $message;
   }

   /**
    * methodGetIntervention : Get intervention from WEBSERVICE plugin
    *
    * @param type  $params
    * @param type  $protocol
    *
    * @return type
    * @throws \GlpitestSQLError
    * @throws \GlpitestSQLError
    * @global type $DB
    *
    */
   static function methodShowMetademands($params, $protocol) {

      if (isset($params['help'])) {
         return ['help'           => 'bool,optional',
                 'metademands_id' => 'int'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['metademands_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
      }

      $metademands = new self();

      if (!$metademands->canCreate() && !PluginMetademandsGroup::isUserHaveRight($params['metademands_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      $result = $metademands->showMetademands($params['metademands_id']);

      $response = [];
      foreach ($result as $step => $values) {
         foreach ($values as $metademands_id => $form) {
            $response[] = ['metademands_id'   => $metademands_id,
                           'metademands_name' => Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metademands_id),
                           'form'             => $form['form'],
                           'tasks'            => $form['tasks']];
         }
      }

      return $response;
   }

   /**
    * @param $params
    * @param $protocol
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function methodListMetademands($params, $protocol) {

      if (isset($params['help'])) {
         return ['help' => 'bool,optional'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $metademands = new self();
      $result      = $metademands->listMetademands();

      $response = [];

      foreach ($result as $key => $val) {
         $response[] = ['id' => $key, 'value' => $val];
      }

      return $response;
   }

   /**
    * @param array $options
    *
    * @return array
    */
   function listMetademandsCategories($options = []) {
      $params['condition'] = [];

      foreach ($options as $key => $value) {
         $params[$key] = $value;
      }

      $result = [];

      $dbu       = new DbUtils();
      $condition = ['type' => Ticket::DEMAND_TYPE]
                   + $dbu->getEntitiesRestrictCriteria($this->getTable(), '', '', true);

      if (!empty($params['condition'])) {
         $condition += $params['condition'];
      }

      if (!empty($type)) {
         $listMetademands = $this->find($condition);
         foreach ($listMetademands as $key => $data) {
            if ($this->canCreate() || PluginMetademandsGroup::isUserHaveRight($data['id'])) {
               $result[$data['itilcategories_id']] = $data['itilcategories_id'];
            }
         }
      }

      return $result;
   }

   /**
    * @param bool  $forceview
    * @param array $options
    * @param array $meta_data
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   function listMetademands($forceview = false, $options = [], $meta_data = []) {
      global $DB;

      $dbu                 = new DbUtils();
      $params['condition'] = '';

      foreach ($options as $key => $value) {
         $params[$key] = $value;
      }

      $meta_data = [];
      if (isset($options['empty_value'])) {
         $meta_data[0] = Dropdown::EMPTY_VALUE;
      }
      $type = Ticket::DEMAND_TYPE;

      $condition = "1 AND `" . $this->getTable() . "`.`type` = '$type' AND is_active ";
      $condition .= $dbu->getEntitiesRestrictRequest("AND", $this->getTable(), null, null, true);

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

                  if (!$dbu->countElementsInTable("glpi_plugin_metademands_metademands_resources",
                                                  ["plugin_metademands_metademands_id" => $data['id']])) {
                     $meta_data[$data['id']] = $data['name'] . ' (' . $data['entities_name'] . ')';
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
    * @param int   $step
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   function showMetademands($metademands_id, $forms = [], $step = self::STEP_SHOW) {
      global $DB;

      $metademands = new PluginMetademandsMetademand();
      $metademands->getFromDB($metademands_id);

      if (!empty($metademands_id)) {
         // get normal form data
         $field     = new PluginMetademandsField();
         $form_data = $field->find(['plugin_metademands_metademands_id' => $metademands_id],
                                   ['rank', 'order']);

         // Construct array
         $forms[$step][$metademands_id]['form']  = [];
         $forms[$step][$metademands_id]['tasks'] = [];
         if (count($form_data)) {
            $forms[$step][$metademands_id]['form'] = $form_data;
         }
         // Task only for demands
         if (isset($metademands->fields['type'])
             && $metademands->fields['type'] == Ticket::DEMAND_TYPE) {
            $tasks                                  = new PluginMetademandsTask();
            $tasks_data = $tasks->getTasks($metademands_id,
                                          ['condition' => ['glpi_plugin_metademands_tasks.type' => PluginMetademandsTask::TICKET_TYPE]]);

            $forms[$step][$metademands_id]['tasks'] = $tasks_data;
         }

         // Check if task are metademands, if some found : recursive call
         if (isset($metademands->fields['type'])
             && $metademands->fields['type'] == Ticket::DEMAND_TYPE) {
            $query  = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` AS link_metademands_id
                        FROM `glpi_plugin_metademands_tasks`
                        RIGHT JOIN `glpi_plugin_metademands_metademandtasks`
                          ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` = `glpi_plugin_metademands_tasks`.`id`)
                        WHERE `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` = " . $metademands_id;
            $result = $DB->query($query);
            if ($DB->numrows($result)) {
               while ($data = $DB->fetchAssoc($result)) {
                  $step++;
                  $forms = $this->showMetademands($data['link_metademands_id'], $forms, $step);
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
   function convertMetademandToTicket($ticket, $metademands_id) {
      $tickets_id = $ticket->input["id"];

      $ticket_task       = new PluginMetademandsTicket_Task();
      $ticket_metademand = new PluginMetademandsTicket_Metademand();
      $ticket_field      = new PluginMetademandsTicket_Field();
      $ticket_ticket     = new Ticket_Ticket();

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
            $son_metademands_ticket->input = $son_metademands_ticket->fields;
            $this->convertMetademandToTicket($son_metademands_ticket, $value['metademands_id']);
            $son_metademands_ticket->fields["name"] = addslashes(str_replace(self::$PARENT_PREFIX, '', $ticket->input["name"]));
            $son_metademands_ticket->updateInDB(['name']);
         } else if (!empty($value['tickets_id'])) {
            // Try to convert name
            $son_ticket = new Ticket();
            $son_ticket->getFromDB($value['tickets_id']);
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
   function addMetademands($metademands_id, $values, $options = []) {
      global $DB;

      $tasklevel = 1;

      $metademands_data = $this->showMetademands($metademands_id);
      $this->getFromDB($metademands_id);

      $ticket                    = new Ticket();
      $ticket_metademand         = new PluginMetademandsTicket_Metademand();
      $ticket_field              = new PluginMetademandsTicket_Field();
      $ticket_ticket             = new Ticket_Ticket();
      $KO                        = [];
      $ancestor_tickets_id       = 0;
      $parent_tickets_id_created = [];
      $ticket_exists_array       = [];
      $config                    = $this->getConfig();

      if (count($metademands_data)) {
         foreach ($metademands_data as $form_step => $data) {
            $docitem = null;
            foreach ($data as $form_metademands_id => $line) {
               $noChild = false;
               if ($ancestor_tickets_id > 0) {
                  // Skip ticket creation if not allowed by metademand form
                  $metademandtasks_tasks_ids = PluginMetademandsMetademandTask::getMetademandTask_TaskId($form_metademands_id);
                  foreach ($metademandtasks_tasks_ids as $metademandtasks_tasks_id) {
                     if (!PluginMetademandsTicket_Field::checkTicketCreation($metademandtasks_tasks_id, $ancestor_tickets_id)) {
                        $noChild = true;
                     }
                  }
               }
               if ($noChild) {
                  continue;
               }
               $metademand = new PluginMetademandsMetademand();
               $metademand->getFromDB($form_metademands_id);

               // Create parent ticket
               // Get form fields
               $parent_fields = ['content' => ''];
               if (count($line['form']) && isset($values['fields'])) {
                  $parent_fields = $this->formatFields($line['form'], $metademands_id, $values['fields'], $options);
                  //                  $parent_fields['content'] = Html::cleanPostForTextArea($parent_fields['content']);

               }

               $parent_fields['name'] = self::$PARENT_PREFIX .
                                        Dropdown::getDropdownName($this->getTable(), $form_metademands_id);
               $parent_fields['type'] = $this->fields['type'];

               $parent_fields['entities_id'] = $_SESSION['glpiactive_entity'];

               // Existing tickets id field
               $parent_fields['id']     = $values['fields']['tickets_id'];
               $parent_fields['status'] = CommonITILObject::INCOMING;

               // Resources id
               if (!empty($options['resources_id'])) {
                  $parent_fields['items_id'] = ['PluginResourcesResource' => [$options['resources_id']]];
               }

               foreach ($values['fields'] as $id => $data) {
                  $metademands_fields = new PluginMetademandsField();
                  if ($metademands_fields->getFromDB($id)) {
                     switch ($metademands_fields->fields['item']) {
                        case 'PluginMetademandsITILEnvironment':
                           $parent_fields['plugin_metademands_itilenvironments_id'] = $data;
                           break;
                        case 'PluginMetademandsITILApplication':
                           $parent_fields['plugin_metademands_itilapplications_id'] = $data;
                           break;
                        case 'itilcategory':
                           $parent_fields['itilcategories_id'] = $data;
                           if (isset($_POST['plugin_servicecatalog_itilcategories_id']) && $_POST['plugin_servicecatalog_itilcategories_id'] > 0) {
                              $parent_fields['itilcategories_id'] = $_POST['plugin_servicecatalog_itilcategories_id'];
                           }
                           break;
                     }
                  }
               }

               // Requester user field
               if ($values['fields']['_users_id_requester']) {
                  $parent_fields['_users_id_requester'] = $values['fields']['_users_id_requester'];
               }
               // Add requester if empty
               if (empty($parent_fields['_users_id_requester'])) {
                  $parent_fields['_users_id_requester'] = Session::getLoginUserID();
               }

               $email                                      = UserEmail::getDefaultForUser($parent_fields['_users_id_requester']);
               $default_use_notif                          = Entity::getUsedConfig('is_notif_enable_default', $parent_fields['entities_id'], '', 1);
               $parent_fields['_users_id_requester_notif'] = ['use_notification'
                                                                                  => (($email == "") ? 0 : $default_use_notif),
                                                              'alternative_email' => ['']];

               // If requester is different of connected user : Force his requester group on ticket
               if (isset($parent_fields['_users_id_requester'])
                   && $parent_fields['_users_id_requester'] != Session::getLoginUserID()) {
                  $query  = "SELECT `glpi_groups`.`id` AS _groups_id_requester
                           FROM `glpi_groups_users`
                           LEFT JOIN `glpi_groups`
                             ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                           WHERE `glpi_groups_users`.`users_id` = " . $parent_fields['_users_id_requester'] . "
                           AND `glpi_groups`.`is_requester` = 1
                           LIMIT 1";
                  $result = $DB->query($query);
                  if ($DB->numrows($result)) {
                     $groups_id_requester                   = $DB->result($result, 0, '_groups_id_requester');
                     $parent_fields['_groups_id_requester'] = $groups_id_requester;
                  }
               }
               // Affect requester group to son metademand
               if ($form_metademands_id != $metademands_id) {
                  $groups_id_assign = PluginMetademandsTicket::getUsedActors($ancestor_tickets_id,
                                                                             CommonITILActor::ASSIGN,
                                                                             'groups_id');
                  if (count($groups_id_assign)) {
                     $parent_fields['_groups_id_requester'] = $groups_id_assign[0];
                  }
               }

               // Get predefined ticket fields
               $parent_ticketfields = $this->formatTicketFields($form_metademands_id);

               // Case of simple ticket convertion
               // Ticket does not exist : ADD
               $ticket_exists = false;
               if (empty($parent_fields['id'])) {
                  unset($parent_fields['id']);
                  // Add files if exist
                  if (isset($values['files'][$form_metademands_id])
                      && count($values['files'][$form_metademands_id])) {
                     foreach ($values['files'][$form_metademands_id] as $files) {
                        $toUpload                     = PluginMetademandsTicket::uploadTicketDocument(['base64' => $files['base64'],
                                                                                                       'name'   => $files['name']]);
                        $parent_fields['_filename'][] = $toUpload['tmp_name'];
                     }
                  }
                  $input = $this->mergeFields($parent_fields, $parent_ticketfields);
                  if (isset($values['fields']['_filename'])) {
                     $input['_filename'] = $values['fields']['_filename'];
                  }
                  if (isset($values['fields']['_prefix_filename'])) {
                     $input['_prefix_filename'] = $values['fields']['_prefix_filename'];
                  }

                  if (isset($_POST['plugin_servicecatalog_itilcategories_id']) && $_POST['plugin_servicecatalog_itilcategories_id'] > 0) {
                     $input['itilcategories_id'] = $_POST['plugin_servicecatalog_itilcategories_id'];
                  }

                  $input = Toolbox::addslashes_deep($input);

                  $parent_tickets_id = $ticket->add($input);
                  if ($docitem == null && $config['create_pdf']) {
                     //Génération du document PDF
                     $docPdf = new PluginMetaDemandsMetaDemandPdf($this->fields['name'],
                                                                  $this->fields['comment']);
                     $docPdf->drawPdf($metademands_data[2][$metademands_id]['form'], $values);
                     $docPdf->Close();
                     $name    = PluginMetaDemandsMetaDemandPdf::cleanTitle($metademand->fields['name']);
                     $docitem = $docPdf->addDocument($name, $ticket->getID(), $_SESSION['glpiactive_entity']);
                  }

                  // Ticket already exists
               } else {
                  $parent_tickets_id = $parent_fields['id'];
                  $ticket->getFromDB($parent_tickets_id);
                  $parent_fields['content']       = $ticket->fields['content']
                                                    . "<br>" . $parent_fields['content'];
                  $parent_fields['name']          = Html::cleanPostForTextArea($parent_fields['name'])
                                                    . '&nbsp;:&nbsp;' . Html::cleanPostForTextArea($ticket->fields['name']);
                  $ticket_exists_array[]          = 1;
                  $ticket_exists                  = true;
                  $values['fields']['tickets_id'] = 0;
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

                  $parent_tickets_id_created[] = $parent_tickets_id;

                  // Metademands - ticket relation
                  $ticket_metademand->add(['tickets_id'                        => $parent_tickets_id,
                                           'parent_tickets_id'                 => $ancestor_tickets_id,
                                           'plugin_metademands_metademands_id' => $form_metademands_id]);

                  // Save all form values of the ticket
                  if (count($line['form']) && isset($values['fields'])) {
                     $ticket_field->setTicketFieldsValues($line['form'], $values['fields'], $parent_tickets_id);
                  }

                  if (!empty($ancestor_tickets_id)) {
                     // Add son link to parent
                     $ticket_ticket->add(['tickets_id_1' => $parent_tickets_id,
                                          'tickets_id_2' => $ancestor_tickets_id,
                                          'link'         => Ticket_Ticket::SON_OF]);
                  }

                  // Create sons ticket tasks
                  if (isset($line['tasks'])
                      && is_array($line['tasks'])
                      && count($line['tasks'])) {
                     if (!$this->createSonsTickets($line['tasks'], $parent_tickets_id, $tasklevel,
                                                   $this->mergeFields($parent_fields,
                                                                      $parent_ticketfields),
                                                   $parent_tickets_id)) {
                        $KO[] = 1;
                     }
                  }

                  // Case of simple ticket convertion
                  if ($ticket_exists) {
                     if (isset($parent_ticketfields['_users_id_observer'])
                         && !empty($parent_ticketfields['_users_id_observer'])) {
                        $parent_ticketfields['_itil_observer'] = ['users_id' => $parent_ticketfields['_users_id_observer'], '_type' => 'user'];
                     }
                     if (isset($parent_ticketfields['_groups_id_observer'])
                         && !empty($parent_ticketfields['_groups_id_observer'])) {
                        $parent_ticketfields['_itil_observer'] = ['groups_id' => $parent_ticketfields['_groups_id_observer'], '_type' => 'group'];
                     }
                     if (isset($parent_ticketfields['_users_id_assign'])
                         && !empty($parent_ticketfields['_users_id_assign'])) {
                        $parent_ticketfields['_itil_assign'] = ['users_id' => $parent_ticketfields['_users_id_assign'], '_type' => 'user'];
                     }
                     if (isset($parent_ticketfields['_groups_id_assign'])
                         && !empty($parent_ticketfields['_groups_id_assign'])) {
                        $parent_ticketfields['_itil_assign'] = ['groups_id' => $parent_ticketfields['_groups_id_assign'], '_type' => 'group'];
                     }

                     $ticket->update($this->mergeFields($parent_fields, $parent_ticketfields));
                  }
               } else {
                  $KO[] = 1;
               }
            }
         }
      }

      // Message return
      $parent_metademands_name = Dropdown::getDropdownName($this->getTable(), $metademands_id);
      if (count($KO)) {
         $message = __('Demand add failed', 'metademands') . ' : ' . $parent_metademands_name;
      } else {
         if (!in_array(1, $ticket_exists_array)) {
            $message = sprintf(__('Demand "%s" added with success', 'metademands'), $parent_metademands_name);
         } else {
            $message = sprintf(__('Ticket "%s" updated to metademand with success', 'metademands'), $parent_metademands_name);
         }
      }

      return ['message' => $message, 'tickets_id' => $ancestor_tickets_id];
   }

   /**
    * @param $parent_fields
    * @param $parent_ticketfields
    *
    * @return mixed
    */
   private function mergeFields($parent_fields, $parent_ticketfields) {

      foreach ($parent_ticketfields as $key => $val) {
         switch ($key) {
            case 'name' :
               $parent_fields[$key] .= ' ' . $val;
               break;
            //            case 'content' :
            //               $parent_fields[$key] .= '\r\n' . $val;
            //               break;
            default :
               $parent_fields[$key] = $val;
               break;
         }
      }

      return $parent_fields;
   }

   /**
    * @param array $parent_fields
    * @param       $metademands_id
    * @param       $values
    *
    * @return array
    */
   private function formatFields(array $parent_fields, $metademands_id, $values, $options = []) {

      $result            = [];
      $result['content'] = "";
      $parent_fields_id  = 0;

      $name              = Dropdown::getDropdownName($this->getTable(), $metademands_id);
      $result['content'] .= "<table style='width: 100%;border-style: dashed;'>"; // class='mticket'
      $result['content'] .= "<tr><th colspan='2' style='background-color: #ccc;'>" . $name . "</th></tr>";
      if (!empty($options['resources_id'])) {
         $resource = new PluginResourcesResource();
         $resource->getFromDB($options['resources_id']);
         $result['content'] .= "<tr><th colspan='2' style='background-color: #ccc;'>" . $resource->fields['name'] . " " . $resource->fields['firstname'] . "</th></tr>";
      }
      //      $result['content'] .= "</table>";
      $nb = 0;
      foreach ($parent_fields as $fields_id => $field) {

         $field['value'] = '';
         if (isset($values[$fields_id])) {
            $field['value'] = $values[$fields_id];
         }
         $field['value2'] = '';
         if ($field['type'] == 'datetime_interval' && isset($values[$fields_id . '-2'])) {
            $field['value2'] = $values[$fields_id . '-2'];
         }
         if ($nb % 2 == 0) {
            $result['content'] .= "<tr class='even'>";
         } else {
            $result['content'] .= "<tr class='odd'>";
         }
         $nb++;

         self::getContentWithField($parent_fields, $fields_id, $field, $result, $parent_fields_id);

         $result['content'] .= "</tr>";

      }
      $result['content'] .= "</table>";
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
    */
   function getContentWithField($parent_fields, $fields_id, $field, &$result, &$parent_fields_id) {
      global $PLUGIN_HOOKS;

      $style_title = "class='title'";
      //      $style_title = "style='background-color: #cccccc;'";
      $label  = Toolbox::stripslashes_deep($field['label']);
      $label2 = Toolbox::stripslashes_deep($field['label2']);
      if ((!empty($field['value']) || $field['value'] == "0") && $field['value'] != 'NULL' || $field['type'] == 'title') {
         //         if (isset($parent_fields[$parent_fields_id]['rank'])
         //             && $field['rank'] != $parent_fields[$parent_fields_id]['rank']) {
         //            $result['content'] .= "<tr>";
         //         }

         $plugin = new Plugin();
         //use plugin fields types
         if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
               $new_fields = PluginMetademandsWizard::getPluginFieldItemsType($plug);
               if ($plugin->isActivated($plug) && is_array($new_fields)) {
                  if (in_array($field['type'], array_keys($new_fields))) {
                     $field['type'] = $new_fields[$field['type']];
                  }
               }
            }
         }

         switch ($field['type']) {
            case 'title' :
               $result['content'] .= "<th colspan='2' style='background-color: #ccc;'>" . $label . "</th>";
               break;
            case 'dropdown':
               if (!empty($field['custom_values'])
                   && $field['item'] == 'other') {
                  $field['custom_values'] = PluginMetademandsField::_unserialize($field['custom_values']);
                  if (isset($field['custom_values'][$field['value']])) {
                     $result['content'] .= "<td $style_title>" . $label . "</td><td>" . $field['custom_values'][$field['value']] . "</td>";
                  }
               } else {
                  switch ($field['item']) {
                     case 'user':
                        $result['content'] .= "<td $style_title>" . $label . "</td>";
                        $user              = new User();
                        $user->getFromDB($field['value']);
                        $result['content'] .= "<td>" . $user->getName() . "</td>";
                        break;
                     case 'usertitle':
                        $result['content'] .= "<td $style_title>" . $label . "</td>";
                        $usert             = new UserTitle();
                        $usert->getFromDB($field['value']);
                        $result['content'] .= "<td>" . $usert->getName() . "</td>";
                        break;
                     case 'usercategory':
                        $result['content'] .= "<td $style_title>" . $label . "</td>";
                        $userc             = new UserCategory();
                        $userc->getFromDB($field['value']);
                        $result['content'] .= "<td>" . $userc->getName() . "</td>";
                        break;
                     //                     case strpos($field['item'], 'PluginLdapfields'):
                     //                        $result['content'] .= "<td $style_title>" . $label . "</td>";
                     //                        $classname         = new $field['item']();
                     //                        $classname->getFromDB($field['value']);
                     //                        $result['content'] .= "<td>" . $classname->getName() . "</td>";
                     //                        break;
                     case PluginMetademandsITILEnvironment::class:
                        $result['content'] .= "<td $style_title>" . $label . "</td>";
                        $classname         = new PluginMetademandsITILEnvironment();
                        $classname->getFromDB($field['value']);
                        $result['content'] .= "<td>" . $classname->getName() . "</td>";
                        break;
                     case PluginMetademandsITILApplication::class:
                        $result['content'] .= "<td $style_title>" . $label . "</td>";
                        $classname         = new PluginMetademandsITILApplication();
                        $classname->getFromDB($field['value']);
                        $result['content'] .= "<td>" . $classname->getName() . "</td>";
                        break;
                     default:
                        $dbu               = new DbUtils();
                        $result['content'] .= "<td $style_title>" . $label . "</td><td>";
                        $result['content'] .= Dropdown::getDropdownName($dbu->getTableForItemType($field['item']),
                                                                        $field['value']);
                        $result['content'] .= "</td>";
                        break;
                  }
               }
               break;
            case 'dropdown_multiple':
               if (!empty($field['custom_values'])) {
                  $field['custom_values'] = PluginMetademandsField::_unserialize($field['custom_values']);
                  $field['value']         = PluginMetademandsField::_unserialize($field['value']);
                  $parseValue             = [];
                  foreach ($field['value'] as $value) {
                     array_push($parseValue, $field['custom_values'][$value]);
                  }
                  $result['content'] .= "<td $style_title>" . $label . "</td><td>" . implode('<br>', $parseValue) . "</td>";
               }

               break;
            case 'link':
               if (strpos($field['value'], 'http://') !== 0 && strpos($field['value'], 'https://') !== 0) {
                  $field['value'] = "http://" . $field['value'];
               }
               $result['content'] .= "<td $style_title>" . $label . "</td><td>" . '<a href="' . $field['value'] . '" data-mce-href="' . $field['value'] . '" > ' . $field['value'] . '</a></td>';
               break;
            case 'textarea':
            case 'text':
               $result['content'] .= "<td $style_title>" . $label . "</td><td>" . stripslashes($field['value']) . "</td>";
               break;
            case 'checkbox':
               if (!empty($field['custom_values'])) {
                  $field['custom_values'] = PluginMetademandsField::_unserialize($field['custom_values']);
                  if (!empty($field['value'])) {
                     $field['value'] = PluginMetademandsField::_unserialize($field['value']);
                  }
                  $custom_checkbox   = [];
                  $result['content'] .= "<td $style_title>" . $label . "</td>";
                  foreach ($field['custom_values'] as $key => $val) {
                     $checked = isset($field['value'][$key]) ? 1 : 0;
                     if ($checked) {
                        $custom_checkbox[] .= $val;
                     }
                  }
                  $result['content'] .= "<td>" . implode('<br>', $custom_checkbox) . "</td>";
               } else {
                  if ($field['value']) {
                     $result['content'] .= "<td>" . $field['value'] . "</td>";
                  }
               }
               break;
            case 'radio':
               if (!empty($field['custom_values'])) {
                  $field['custom_values'] = PluginMetademandsField::_unserialize($field['custom_values']);
                  if ($field['value'] != "") {
                     $field['value'] = PluginMetademandsField::_unserialize($field['value']);
                  }
                  $result['content'] .= "<td $style_title>" . $label . "</td>";
                  $custom_radio      = "";
                  foreach ($field['custom_values'] as $key => $val) {
                     if ($field['value'] == $key) {
                        $custom_radio = $val;
                     }
                  }
                  $result['content'] .= "<td>" . $custom_radio . "</td>";
               } else {
                  if ($field['value']) {
                     $result['content'] .= "<td>" . $label . "</td>";
                  }
               }
               break;
            case 'textarea':
               $result['content'] .= $label . ' : ' . $field['value'];
               break;
            case 'datetime':
               $result['content'] .= "<td $style_title>" . $label . "</td><td>" . Html::convDate($field['value']) . "</td>";
               break;
            case 'datetime_interval':
               $result['content'] .= "<td $style_title>" . $label . "</td><td>" . Html::convDate($field['value']) . "</td></tr>";
               $result['content'] .= "<tr class='odd'><td $style_title>" . $label2 . "</td><td>" . Html::convDate($field['value2']) . "</td>";
               break;
            case 'number':
               $result['content'] .= "<td $style_title>" . $label . "</td><td>" . $field['value'] . "</td>";
               break;
            case 'yesno':
               if ($field['value'] == 2) {
                  $val = __('Yes');
               } else {
                  $val = __('No');
               }
               $result['content'] .= "<td $style_title>" . $label . "</td><td>" . $val . "</td>";
               break;

            case 'parent_field':
               $metademand_field = new PluginMetademandsField();
               if (isset($field['parent_field_id']) && $metademand_field->getFromDB($field['parent_field_id'])) {
                  $parent_field                  = $field;
                  $parent_field['custom_values'] = $metademand_field->fields['custom_values'];
                  $parent_field['type']          = $metademand_field->fields['type'];
                  $parent_field['item']          = $metademand_field->fields['item'];

                  self::getContentWithField($parent_fields, $fields_id, $parent_field, $result, $parent_fields_id);
               }

               break;
            default:
               //plugins case
               break;
         }
         //         $result['content'] .= "<br>";
      }
      $parent_fields_id = $fields_id;
   }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
   //   static function displayPluginFieldItems($plug) {
   //      global $PLUGIN_HOOKS;
   //
   //      $dbu = new DbUtils();
   //      if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
   //         $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];
   //
   //         foreach ($pluginclasses as $pluginclass) {
   //            if (!class_exists($pluginclass)) {
   //               continue;
   //            }
   //            $form[$pluginclass] = [];
   //            $item               = $dbu->getItemForItemtype($pluginclass);
   //            if ($item && is_callable([$item, 'displayFieldItems'])) {
   //               return $item->displayFieldItems();
   //            }
   //         }
   //      }
   //   }

   /**
    * @param $metademands_id
    *
    * @return array
    */
   function formatTicketFields($metademands_id) {
      $result = [];

      $ticket_field        = new PluginMetademandsTicketField();
      $parent_ticketfields = $ticket_field->find(['plugin_metademands_metademands_id' => $metademands_id]);

      $tt = new TicketTemplate();
      if (count($parent_ticketfields)) {
         $allowed_fields = $tt->getAllowedFields(true, true);
         foreach ($parent_ticketfields as $value) {
            if (isset($allowed_fields[$value['num']]) && (!in_array($allowed_fields[$value['num']], PluginMetademandsTicketField::$used_fields))) {
               $value['item']          = $allowed_fields[$value['num']];
               $result[$value['item']] = json_decode($value['value'], true);
            }
         }
      }

      return $result;
   }

   /**
    * @param array $tickettasks_data
    * @param       $parent_tickets_id
    * @param int   $tasklevel
    * @param       $parent_fields
    * @param       $ancestor_tickets_id
    *
    * @return bool
    * @throws \GlpitestSQLError
    * @throws \GlpitestSQLError
    */
   private function createSonsTickets($tickettasks_data = [], $parent_tickets_id, $tasklevel = 1, $parent_fields, $ancestor_tickets_id) {

      $ticket_ticket = new Ticket_Ticket();
      $ticket_task   = new PluginMetademandsTicket_Task();
      $ticket        = new Ticket();
      $KO            = [];

      foreach ($tickettasks_data as $son_ticket_data) {

         if ($son_ticket_data['level'] == $tasklevel
             && PluginMetademandsTicket_Field::checkTicketCreation($son_ticket_data['tasks_id'], $ancestor_tickets_id)) {

            // Skip ticket creation if not allowed by metademand form

            // Field format for ticket
            foreach ($son_ticket_data as $field => $value) {
               if (strstr($field, 'groups_id_') || strstr($field, 'users_id_')) {
                  $son_ticket_data['_' . $field] = $son_ticket_data[$field];
               }
            }
            foreach ($parent_fields as $field => $value) {
               if (strstr($field, 'groups_id_') || strstr($field, 'users_id_')) {
                  $parent_fields['_' . $field] = $parent_fields[$field];
               }
            }

            // Add son ticket
            $son_ticket_data['_disablenotif']      = true;
            $son_ticket_data['name']               = self::$SON_PREFIX . $son_ticket_data['tickettasks_name'];
            $son_ticket_data['type']               = Ticket::DEMAND_TYPE;
            $son_ticket_data['entities_id']        = $parent_fields['entities_id'];
            $son_ticket_data['users_id_recipient'] = 0;
            $son_ticket_data['_auto_import']       = 1;
            $son_ticket_data['status']             = Ticket::INCOMING;

            $content = '';
            if (!empty($son_ticket_data['content'])) {
               $content = "<table style='width: 100%;border-style: dashed;'><tr><th colspan='2' style='background-color: #ccc;'>" . __('Child Ticket', 'metademands') .
                          "</th></tr><tr><td colspan='2'>" . $son_ticket_data['content'];
               $content .= "</td></tr></table><br>";
            }
            $config = new PluginMetademandsConfig();
            $config->getFromDB(1);
            if ($config->getField('childs_parent_content') == 1) {
               if (!empty($parent_fields['content'])) {
                  //if (!strstr($parent_fields['content'], __('Parent ticket', 'metademands'))) {
                  $content .= "<table style='width: 100%;border-style: dashed;'><tr><th colspan='2' style='background-color: #ccc;'>" . __('Parent tickets', 'metademands') .
                              "</th></tr><tr><td colspan='2'>" . $parent_fields['content'];
                  //if (!strstr($parent_fields['content'], __('Parent ticket', 'metademands'))) {
                  $content .= "</td></tr></table><br>";
                  //}
               }
            }

            //            $content = Html::cleanPostForTextArea($content);

            $son_ticket_data['content'] = $content;
            if (isset($parent_fields['_groups_id_assign'])) {
               $son_ticket_data['_groups_id_requester'] = $parent_fields['_groups_id_assign'];
            }

            if ($son_tickets_id = $ticket->add(Toolbox::addslashes_deep($son_ticket_data))) {
               // Add son link to parent
               $ticket_ticket->add(['tickets_id_1' => $parent_tickets_id,
                                    'tickets_id_2' => $son_tickets_id,
                                    'link'         => Ticket_Ticket::PARENT_OF]);

               // task - ticket relation
               $ticket_task->add(['tickets_id'                  => $son_tickets_id,
                                  'parent_tickets_id'           => $parent_tickets_id,
                                  'level'                       => $son_ticket_data['level'],
                                  'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id']]);
            } else {
               $KO[] = 1;
            }
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
   function addSonTickets($tickets_data) {
      global $DB;

      $ticket_task    = new PluginMetademandsTicket_Task();
      $ticket         = new Ticket();
      $groups_tickets = new Group_Ticket();

      // We can add task if one is not already present for ticket
      $search_ticket = $ticket_task->find(['parent_tickets_id' => $tickets_data['id']]);
      if (!count($search_ticket)) {
         $task   = new PluginMetademandsTask();
         $query  = "SELECT `glpi_plugin_metademands_tickettasks`.*,
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
            while ($data = $DB->fetchAssoc($result)) {

               // If child task exists : son ticket creation
               $child_tasks_data = $task->getChildrenForLevel($data['tasks_id'], $data['parent_level'] + 1);

               if ($child_tasks_data) {
                  foreach ($child_tasks_data as $child_tasks_id) {
                     $tasks_data = $task->getTasks($data['plugin_metademands_metademands_id'],
                                                ['condition' => ['glpi_plugin_metademands_tasks.id' => $child_tasks_id]]);

                     // Get parent ticket data
                     $ticket->getFromDB($tickets_data['id']);

                     // Find parent metademand tickets_id and get its _groups_id_assign
                     $tickets_found              = PluginMetademandsTicket::getAncestorTickets($tickets_data['id'], true);
                     $parent_groups_tickets_data = $groups_tickets->find(['tickets_id' => $tickets_found[0]['tickets_id'], 'type' => CommonITILActor::ASSIGN]);

                     if (count($parent_groups_tickets_data)) {
                        $parent_groups_tickets_data          = reset($parent_groups_tickets_data);
                        $ticket->fields['_groups_id_assign'] = $parent_groups_tickets_data['groups_id'];
                     }

                     $this->createSonsTickets($tasks_data, $tickets_data['id'], $data['parent_level'] + 1, $ticket->fields, $tickets_found[0]['tickets_id']);
                  }
               }
            }
         }
      }
   }

   /**
    * @param $ticket
    *
    * @return bool
    * @throws \GlpitestSQLError
    * @throws \GlpitestSQLError
    */
   function showPluginForTicket($ticket) {

      if (!$this->canView()) {
         return false;
      }

      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $ticket->fields['id']]);
      $tickets_found          = [];
      // If ticket is Parent : Check if all sons ticket are closed
      if (count($ticket_metademand_data)) {
         $ticket_metademand_data = reset($ticket_metademand_data);
         $tickets_found          = PluginMetademandsTicket::getSonTickets($ticket->fields['id'],
                                                                          $ticket_metademand_data['plugin_metademands_metademands_id']);
      } else {
         $ticket_task      = new PluginMetademandsTicket_Task();
         $ticket_task_data = $ticket_task->find(['tickets_id' => $ticket->fields['id']]);

         if (count($ticket_task_data)) {
            $tickets_found = PluginMetademandsTicket::getAncestorTickets($ticket->fields['id'], true);
         }
      }
      $tickets_existant = [];
      $tickets_next     = [];

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
            echo "<th>" . __('Status') . " " . __('SLT') . "</th></tr>";

            $status = [Ticket::SOLVED, Ticket::CLOSED];

            foreach ($tickets_existant as $values) {
               $notcreated  = false;
               $color_class = '';
               // Get ticket values if it exists
               $ticket->getFromDB($values['tickets_id']);

               // SLA State
               $sla_state = Dropdown::EMPTY_VALUE;
               $is_late   = false;
               switch ($this->checkSlaState($values)) {
                  case self::SLA_FINISHED:
                     $sla_state = __('Task completed.');
                     break;
                  case self::SLA_LATE:
                     $is_late     = true;
                     $color_class = "metademand_metademandfollowup_red";
                     $sla_state   = __('Late');
                     break;
                  case self::SLA_PLANNED:
                     $sla_state = __('Processing');
                     break;
                  case self::SLA_TODO:
                     $sla_state   = __('To do');
                     $color_class = "metademand_metademandfollowup_yellow";
                     break;
               }

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
               echo $techdata;
               echo "</td>";

               //status
               echo "<td class='$color_class center'>";
               if (in_array($ticket->fields['status'], $status)) {
                  echo "<i class='fas fa-check-circle fa-2x' style='color:forestgreen'></i> ";
               }
               if ($is_late && !in_array($ticket->fields['status'], $status)) {
                  echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:darkred'></i> ";
               }
               if (!in_array($ticket->fields['status'], $status)) {
                  echo "<i class='fas fa-cog fa-2x' style='color:orange'></i> ";
               }
               echo Ticket::getStatus($ticket->fields['status']);
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
            echo "<th>" . __('Status') . " " . __('SLT') . "</th></tr>";

            foreach ($tickets_next as $values) {

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
      }
   }

   /**
    * @param array $options
    *
    * @return bool
    * @throws \GlpitestSQLError
    * @throws \GlpitestSQLError
    */
   function executeDuplicate($options = []) {
      global $CFG_GLPI;

      if (isset($options['metademands_id'])) {
         $metademands_id = $options['metademands_id'];

         $fields          = new PluginMetademandsField();
         $ticketfields    = new PluginMetademandsTicketField();
         $tasks           = new PluginMetademandsTask();
         $groups          = new PluginMetademandsGroup();
         $tickettasks     = new PluginMetademandsTicketTask();
         $metademandtasks = new PluginMetademandsMetademandTask();

         // Add the new metademand
         $this->getFromDB($metademands_id);
         unset($this->fields['id']);
         unset($this->fields['itilcategories_id']);
         $this->fields['comment'] = addslashes($this->fields['comment']);
         $this->fields['name']    = addslashes($this->fields['name']);

         if ($new_metademands_id = $this->add($this->fields)) {
            $metademands_data = $this->showMetademands($metademands_id);
            if (count($metademands_data)) {
               foreach ($metademands_data as $form_step => $data) {
                  foreach ($data as $form_metademands_id => $line) {
                     if (count($line['form'])) {
                        if ($form_metademands_id == $metademands_id) {
                           // Add metademand fields
                           foreach ($line['form'] as $values) {
                              unset($values['id']);
                              $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                              $values['label']                             = addslashes($values['label']);
                              $values['label2']                            = addslashes($values['label2']);
                              $values['comment']                           = addslashes($values['comment']);

                              $fields->add($values);
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
                           $tasks->fields['sons_cache']                        = '';
                           $tasks->fields['ancestors_cache']                   = '';
                           $tasks->fields['name']                              = addslashes($tasks->fields['name']);
                           $tasks->fields['completename']                      = addslashes($tasks->fields['completename']);
                           $tasks->fields['comment']                           = addslashes($tasks->fields['comment']);
                           unset($tasks->fields['id']);
                           $new_tasks_id                      = $tasks->add($tasks->fields);
                           $parent_tasks[$values['tasks_id']] = $new_tasks_id;

                           // Ticket tasks
                           if ($values['type'] == PluginMetademandsTask::TICKET_TYPE) {
                              $tickettasks_data = $tickettasks->find(['plugin_metademands_tasks_id' => $values['tasks_id']]);
                              if (count($tickettasks_data)) {
                                 foreach ($tickettasks_data as $values) {
                                    unset($values['id']);
                                    $values['plugin_metademands_tasks_id'] = $new_tasks_id;
                                    $values['content']                     = addslashes($values['content']);
                                    $tickettasks->add($values);
                                 }
                              }
                           }
                        }
                     }
                  }
               }
            }
            // Add metademand task
            $tasks_data = $tasks->find(['plugin_metademands_metademands_id' => $metademands_id,
                                        'type'                              => PluginMetademandsTask::METADEMAND_TYPE]);
            if (count($tasks_data)) {
               foreach ($tasks_data as $values) {
                  $metademandtasks_data = $metademandtasks->find(['plugin_metademands_tasks_id' => $values['id']]);
                  unset($values['id']);
                  $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                  $new_tasks_id                                = $tasks->add($values);
                  if (count($metademandtasks_data)) {
                     foreach ($metademandtasks_data as $data) {
                        $metademandtasks->add(['plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                                               'plugin_metademands_tasks_id'       => $new_tasks_id]);
                     }
                  }
               }
            }

            // Add ticket fields
            $ticketfields_data = $ticketfields->find(['plugin_metademands_metademands_id' => $metademands_id]);
            if (count($ticketfields_data)) {
               foreach ($ticketfields_data as $values) {
                  unset($values['id']);
                  $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                  $values['value']                             = addslashes($values['value']);
                  $ticketfields->add($values);
               }
            }

            // Redirect on finish
            if (isset($options['redirect'])) {
               Html::redirect($CFG_GLPI['root_doc'] . "/plugins/metademands/front/metademand.form.php?id=" . $new_metademands_id);
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
   function checkSlaState($values) {
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
            if (!in_array($ticket->fields['status'], $status)) {
               $total_time   = (strtotime($ticket->fields['time_to_resolve']) - strtotime($ticket->fields['date']));
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
               $sla_state = self::SLA_FINISHED;
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
    * @param $checkitem link item to check right   (default NULL)
    *
    * @return array array of massive actions
    * *@since version 0.84
    */
   function getSpecificMassiveActions($checkitem = null) {
      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['PluginMetademandsMetademand' . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate'] = _sx('button', 'Duplicate');
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
   static function showMassiveActionsSubForm(MassiveAction $ma) {

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
    * @param CommonDBTM    $item
    * @param array         $ids
    *
    * @return nothing|void
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    *
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      switch ($ma->getAction()) {
         case 'duplicate' :
            if ('PluginMetademandsMetademand' == $item->getType()) {
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
   /**
    * @return array
    */
   function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();

      $forbidden[] = 'merge';

      return $forbidden;
   }

   /**
    * @return array|array[]|bool
    */
   /**
    * @return array
    */
   static function getMenuContent() {
      $plugin_page = "/plugins/metademands/front/wizard.form.php";
      $menu        = [];
      //Menu entry in helpdesk
      $menu['title']           = self::getTypeName(2);
      $menu['page']            = $plugin_page;
      $menu['links']['search'] = $plugin_page;
      if (Session::haveRightsOr("plugin_metademands", [CREATE, UPDATE])) {
         //Entry icon in breadcrumb
         $menu['links']['config'] = PluginMetademandsConfig::getFormURL(false);
         $menu['links']['add']    = '/plugins/metademands/front/wizard.form.php';
         $menu['links']['search'] = '/plugins/metademands/front/metademand.php';
      }

      // metademand creation
      $menu['options']['metademand']['title']           = __('Configure demands', 'metademands');
      $menu['options']['metademand']['page']            = '/plugins/metademands/front/metademand.php';
      $menu['options']['metademand']['links']['add']    = '/plugins/metademands/front/metademand.form.php';
      $menu['options']['metademand']['links']['search'] = '/plugins/metademands/front/metademand.php';

      // config
      $menu['options']['config']['title']           = __('Setup');
      $menu['options']['config']['page']            = '/plugins/metademands/front/metademand.php';
      $menu['options']['config']['links']['add']    = '/plugins/metademands/front/metademand.form.php';
      $menu['options']['config']['links']['search'] = '/plugins/metademands/front/metademand.php';

      $menu['icon'] = self::getIcon();

      return $menu;
   }

   /**
    * @return string
    */
   static function getIcon() {
      return "fas fa-share-alt";
   }

   function displayHeader() {
      Html::header(__('Configure demands', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand", "metademand");
   }

}