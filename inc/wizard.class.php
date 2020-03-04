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

/**
 * Class PluginMetademandsWizard
 */
class PluginMetademandsWizard extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    **/
   static function getTypeName($nb = 0) {
      return __('Wizard overview', 'metademands');
   }

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   /**
    * @return bool
    */
   function canUpdateRequester() {
      return Session::haveRight('plugin_metademands_requester', 1);
   }

   /**
    * @param \User $user
    * @param       $tickets_id
    */
   function showUserInformations(User $user, $tickets_id) {

      echo "<div class=\"form-row\">";

      // If profile have no rights on requester update : display connected user info
      if (!$this->canUpdateRequester() || !empty($tickets_id)) {
         echo "<div class=\"bt-feature bt-col-sm-4 bt-col-md- \">";
         echo __('Name') . "&nbsp;";
         echo $user->getField('realname');
         echo " / ";
         echo __('First name') . "&nbsp;";
         echo $user->getField('firstname');
         echo " / ";
      }

      echo __('Login') . "&nbsp;";
      echo $user->getField('name');
      echo " / ";
      echo __('Phone') . "&nbsp;";
      echo $user->getField('phone');
      echo "</div>";

      echo "</div>";
   }

   /**
    * @param string $step
    * @param int    $metademands_id
    * @param bool   $preview
    * @param int    $tickets_id
    * @param int    $resources_id
    * @param string $resources_step
    *
    * @throws \GlpitestSQLError
    */
   function showWizard($step = 'initWizard', $metademands_id = 0, $preview = false, $tickets_id = 0, $resources_id = 0, $resources_step = '') {
      global $CFG_GLPI;

      $config = PluginMetademandsConfig::getInstance();

      // Retrieve session values
      if (isset($_SESSION['plugin_metademands']['fields']['tickets_id'])) {
         $tickets_id = $_SESSION['plugin_metademands']['fields']['tickets_id'];
      }
      if (isset($_SESSION['plugin_metademands']['fields']['resources_id'])) {
         $resources_id = $_SESSION['plugin_metademands']['fields']['resources_id'];
      }
      if (isset($_SESSION['plugin_metademands']['fields']['resources_step'])) {
         $resources_step = $_SESSION['plugin_metademands']['fields']['resources_step'];
      }
      Html::requireJs("metademands");
      echo Html::css("/plugins/metademands/css/style_bootstrap_main.css");
      echo Html::css("/plugins/metademands/css/style_bootstrap_ticket.css");
      echo Html::css("/lib/font-awesome/css/all.min.css");
      echo Html::script("/plugins/metademands/lib/bootstrap/3.2.0/js/bootstrap.min.js");
      echo "<div id ='content'>";
      if (!$preview) {
         echo "<div class='bt-container metademands_wizard_rank' > ";
      }
      $style = "";
      if ($preview) {
         $style = "style='width: 1000px;'";
      }
      echo "<div class='bt-block bt-features' $style> ";

      echo "<form name    = 'wizard_form'
                  method  = 'post'
                  action  = '" . Toolbox::getItemTypeFormURL(__CLASS__) . "'
                  enctype = 'multipart/form-data' 
                  class = 'metademands_img'> ";

      // Case of simple ticket convertion
      echo "<input type = 'hidden' value = '" . $tickets_id . "' name = 'tickets_id' > ";
      // Resources id
      echo "<input type = 'hidden' value = '" . $resources_id . "' name = 'resources_id' > ";
      // Resources step
      echo "<input type = 'hidden' value = '" . $resources_step . "' name = 'resources_step' > ";

      if ($step == 1) {
         // Wizard title
         echo "<div class=\"form-row\">";
         echo "<div class=\"bt-feature col-md-12 \" style='border-bottom: #CCC;border-bottom-style: solid;'>";
         echo "<h4 class=\"bt-title-divider\">";
         $icon = "fa-share-alt";
         $meta = new PluginMetademandsMetademand();
         if ($meta->getFromDB($metademands_id)) {
            if (!empty($meta->fields['icon'])) {
               $icon = $meta->fields['icon'];
            }
         }
         echo "<i class='fa-2x fas $icon'></i>&nbsp;";
         echo __('Demand choice', 'metademands');
         echo "</h4></div></div>";

      } else if ($step >= 2) {
         // Wizard title
         echo "<div class=\"form-row\">";
         echo "<div class=\"bt-feature col-md-12 \" style='border-bottom: #CCC;border-bottom-style: solid;'>";
         echo "<h4 class=\"bt-title-divider\">";
         $meta = new PluginMetademandsMetademand();
         if ($meta->getFromDB($metademands_id)) {
            if (!empty($meta->fields['icon'])) {
               $icon = $meta->fields['icon'];
            }
         }
         echo "<i class='fa-2x fas $icon'></i>&nbsp;";
         echo Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metademands_id);
         echo "</h4>";
         if ($meta->getFromDB($metademands_id)) {
            echo "<label><i>" . nl2br($meta->fields['comment']) . "</i></label>";
         }
         echo "</div></div>";

         // Display user informations
         $userid = Session::getLoginUserID();
         // If ticket exists we get its first requester
         if ($tickets_id) {
            $users_id_requester = PluginMetademandsTicket::getUsedActors($tickets_id, CommonITILActor::REQUESTER, 'users_id');
            if (count($users_id_requester)) {
               $userid = $users_id_requester[0];
            }
         }

         // Retrieve session values
         if (isset($_SESSION['plugin_metademands']['fields']['_users_id_requester'])) {
            $userid = $_SESSION['plugin_metademands']['fields']['_users_id_requester'];
         }

         $user = new User();
         $user->getFromDB($userid);

         // Rights management
         if (!empty($tickets_id) && !Session::haveRight('ticket', UPDATE)) {
            $this->showMessage(__("You don't have the right to update tickets", 'metademands'), true);
            return false;
            echo "</div>";
            echo "</div>";
            echo "</div>";
         } else if (!self::canCreate() &&
                    !PluginMetademandsGroup::isUserHaveRight($metademands_id)
         ) {
            $this->showMessage(__("You don't have the right to create meta-demand", 'metademands'), true);
            echo "</div>";
            echo "</div>";
            echo "</div>";
            return false;
         }

         if ($config['show_requester_informations']) {
            echo "<div class=\"form-row\">";
            echo "<div class=\"bt-feature col-md-12 \" style='border-bottom: #CCC;border-bottom-style: solid;'>";
            echo "<h4 class=\"bt-title-divider\">";
            echo __('General informations', 'metademands');
            echo "</h4></div>";

            // If profile have right on requester update
            if ($this->canUpdateRequester() && empty($tickets_id)) {
               $rand = mt_rand();

               echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
               echo __('Requester') . '&nbsp;:&nbsp;';
               User::dropdown(['name'      => "_users_id_requester",
                               'value'     => $userid,
                               'right'     => 'all',
                               'rand'      => $rand,
                               'on_change' => "showRequester$rand()"]);
               echo "<script type='text/javascript' >\n";
               echo "function showRequester$rand() {\n";
               $params = ['value'      => '__VALUE__',
                          'old_value'  => $userid,
                          'tickets_id' => $tickets_id];
               Ajax::updateItemJsCode("show_users_id_requester",
                                      $CFG_GLPI["root_doc"] . "/plugins/metademands/ajax/dropdownWizardUser.php",
                                      $params,
                                      "dropdown__users_id_requester$rand");
               echo "}";
               echo "</script>\n";
               echo "</div>";
            } else {
               echo "<input type='hidden' value='" . $userid . "' name='_users_id_requester'>";
            }
            echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
            echo "<span id='show_users_id_requester'>";
            $this->showUserInformations($user, $tickets_id);
            echo "</span>";
            echo "</div>";
            echo "</div><br/>";
         } else {
            echo "<input type='hidden' value='" . $userid . "' name='_users_id_requester'>";
         }
      }

      $options['resources_id'] = $resources_id;
      $this->showWizardSteps($step, $metademands_id, $preview, $options);
      Html::closeForm();
      echo "</div>";
      if (!$preview) {
         echo "</div>";
      }
      echo "</div>";
   }

   /**
    * @param       $step
    * @param int   $metademands_id
    * @param bool  $preview
    * @param array $options
    *
    * @throws \GlpitestSQLError
    */
   function showWizardSteps($step, $metademands_id = 0, $preview = false, $options = []) {

      switch ($step) {
         case 'initWizard':
            $this->chooseType($step);
            unset($_SESSION['plugin_metademands']);
            break;

         case 1:
            $this->listMetademands($step);
            unset($_SESSION['plugin_metademands']);
            break;

         case 'add_metademands':
            $values = isset($_SESSION['plugin_metademands']) ? $_SESSION['plugin_metademands'] : [];
            $this->addMetademands($metademands_id, $values, $options);
            break;

         default:
            $this->showMetademands($metademands_id, $step, $preview);
            break;

      }
      echo "<input type='hidden' name='step' value='" . $step . "'>";
   }

   /**
    * @param $file_data
    */
   function uploadFiles($file_data) {

      echo "<div class=\"form-row\">";
      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
      echo "<form name='wizard_form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' enctype='multipart/form-data'>";
      echo "<h1>";
      echo __('Add documents on the demand', 'metademands');
      echo "</h1>";

      $ticket = new Ticket();
      $ticket->getFromDB($file_data['tickets_id']);

      $docadded = $ticket->addFiles($file_data['tickets_id'], 0);
      if (count($docadded) > 0) {
         foreach ($docadded as $name) {
            echo __('Added document', 'metademands') . " $name";
         }
      }
      echo "</div>";
      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
      echo "<input type='submit' class='submit' name='return' value='" . _sx('button', 'Finish', 'metademands') . "'>";
      echo "</div>";

      Html::closeForm();
      echo "</div>";
      echo "</div>";
   }

   /**
    * @param $step
    */
   function chooseType($step) {

      echo "<div class=\"form-row\">";
      echo "<div class=\"bt-feature col-md-12 \" style='border-bottom: #CCC;border-bottom-style: solid;'>";
      echo "<h4 class=\"bt-title-divider\">";
      echo sprintf(__('Step %d - Ticket type choice', 'metademands'), $step);
      echo "</h4>";
      echo "</div>";
      echo "</div>";

      echo "<div class=\"form-row\">";
      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
      // Type
      echo '<b>' . __('Type') . '</b>';
      echo "</div>";
      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
      $types    = PluginMetademandsTask::getTaskTypes();
      $types[0] = Dropdown::EMPTY_VALUE;
      ksort($types);
      Dropdown::showFromArray('type', $types, ['width' => 150]);
      echo "</div>";
      echo "</div>";

      echo "<div class=\"form-row\">";
      echo "<div class=\"bt-feature col-md-12 right\">";
      echo "<input type='submit' class='submit' name='next' value='" . __('Next') . "'>";
      echo "</div>";
      echo "</div>";
   }

   /**
    * @return array
    * @throws \GlpitestSQLError
    */
   static function selectMetademands() {
      global $DB;


      $dbu         = new DbUtils();
      $query       = "SELECT `id`,`name`
                   FROM `glpi_plugin_metademands_metademands`
                   WHERE `glpi_plugin_metademands_metademands`.`itilcategories_id` > 0
                        AND `id` NOT IN (SELECT `plugin_metademands_metademands_id` FROM `glpi_plugin_metademands_metademands_resources`) "
                     . $dbu->getEntitiesRestrictRequest(" AND ", 'glpi_plugin_metademands_metademands', '', '', true);
      $query       .= "AND is_active ORDER BY `name`";
      $metademands = [];
      $result      = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (PluginMetademandsGroup::isUserHaveRight($data['id'])) {
               $metademands[$data['id']] = $data['name'];
            }

         }
      }
      return $metademands;
   }

   /**
    * @param $step
    *
    * @throws \GlpitestSQLError
    */
   function listMetademands($step) {
      global $CFG_GLPI;

      echo Html::css("/plugins/metademands/css/wizard.php");

      $metademands = self::selectMetademands();
      $config = new PluginMetademandsConfig();
      $config->getFromDB(1);
      $meta = new PluginMetademandsMetademand();
      $dbu = new DbUtils();
      if ($config->getField('display_type') == 1) {
         foreach ($metademands as $id => $name) {
            if ($meta->getFromDB($id)) {
               echo '<div class="btnsc-normal" >';
               echo "<a class='bt-buttons' href='" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
               $fasize = "fa-6x";
               echo "<div class='center'>";
               $icon = "fa-share-alt";
               if (!empty($meta->fields['icon'])) {
                  $icon = $meta->fields['icon'];
               }
               echo "<i class='bt-interface fa-menu-md fas $icon $fasize'></i>";//$style
               echo "</div>";

               echo "</a>";
               echo "<a class='bt-buttons' style='display: block;width: 100%; height: 100%;' href='" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
               echo "<p>";
               echo Html::resume_text($meta->getName(), 30);
               echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
               echo $meta->fields['comment'];
               echo "</span></em>";
               echo "</p></a></div>";
            }
         }
      }else{
         echo "<div class=\"bt-row\">";
         $config = PluginMetademandsConfig::getInstance();
         if ($config['enable_families']) {

            echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6 \">";
            // FAMILY
            echo __('Family', 'metademands') . "&nbsp;";
            // Get metademand families
            $data_categories = $dbu->getAllDataFromTable('glpi_itilcategories', ['`level`' => 1]);
            if (count($data_categories)) {
               $data_categories = array_keys($data_categories);
            }

            $itilfamilies_id = [Dropdown::EMPTY_VALUE];

            foreach ($meta->listMetademandsCategories() as $value) {
               $ancestors_id = $dbu->getAncestorsOf('glpi_itilcategories', $value);
               $value        = array_shift($ancestors_id);
               if (in_array($value, $data_categories)) {
                  $itilfamilies_id[$value] = Dropdown::getDropdownName('glpi_itilcategories', $value);
               }
            }
            asort($itilfamilies_id);
            $rand   = Dropdown::showFromArray('itilfamilies_id', $itilfamilies_id, ['width' => 150]);
            $params = ['family' => '__VALUE__',
               'step'   => 'metademands'];

            Ajax::updateItemOnSelectEvent("dropdown_itilfamilies_id$rand", "show_metademands_by_family",
               $CFG_GLPI["root_doc"] . "/plugins/metademands/ajax/dropdownListMetademands.php",
               $params);
            echo "</div>";
            echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6 \">";
         } else {
            echo "<div class=\"bt-feature bt-col-sm-12 bt-col-md-12 \">";
         }
         // METADEMAND list
         echo __('Request') . "&nbsp;";
         echo "<span id='show_metademands_by_family'>";

         $data[0] = Dropdown::EMPTY_VALUE;
         $data    = $meta->listMetademands(false, [], $data);

         Dropdown::showFromArray('metademands_id', $data, ['width' => 250]);
         echo "</span>";
         echo "</div>";
         echo "</div>";

         echo "<br/>";
         echo "<div class=\"bt-row\">";
         echo "<div class=\"bt-feature bt-col-sm-12 bt-col-md-12 right\">";
         echo "<input type='submit' class='submit' name='next' value='" . __('Next') . "'>";
         echo "</div>";
         echo "</div>";
      }
      if (count($metademands) == 0) {
         echo '<div class="bt-feature bt-col-sm-5 bt-col-md-2">';
         echo '<h5 class="bt-title">';
         echo '<span class="de-em">' . __('No advanced request found', 'metademands') . '</span></h5></a>';
         echo '</div>';
         echo '</div>';
      }
   }

   /**
    * @param      $metademands_id
    * @param      $step
    * @param bool $preview
    *
    * @throws \GlpitestSQLError
    */
   function showMetademands($metademands_id, $step, $preview = false) {

      $metademands      = new PluginMetademandsMetademand();
      $metademands_data = $metademands->showMetademands($metademands_id);

      $no_form = false;

      echo "<div class='md-wizard'>";
      if (count($metademands_data)) {
         if ($step - 1 > count($metademands_data) && !$preview) {
            $this->showWizardSteps('add_metademands', $metademands_id, $preview);

         } else {
            foreach ($metademands_data as $form_step => $data) {
               if ($form_step == $step) {
                  foreach ($data as $form_metademands_id => $line) {
                     $no_form = false;

                     $this->constructForm($line['form'], $preview, $metademands_data);

                     echo "<input type='hidden' name='form_metademands_id' value='" . $form_metademands_id . "'>";
                  }
               }
            }
            echo "</div>";
            if (!$preview) {
               echo "<br/>";
               echo "<div class=\"form-row\">";
               echo "<div class=\"bt-feature col-md-12 \">";
               echo "<input type='hidden' name='metademands_id' value='" . $metademands_id . "'>";
               echo "<input type='hidden' name='update_fields'>";
               if ($step - 1 >= count($metademands_data)) {
                  echo "<input type='hidden' name='add_metademands'>";
                  echo "<input type='submit' class='submit metademand_next_button' name='next' value='" . _sx('button', 'Post') . "'>";
               } else {
                  echo "<input type='submit' class='metademand_next_button submit' name='next' value='" . __('Next') . "'>";
               }
               echo "<input type='submit' class='metademand_previous_button submit' name='previous' value='" . __('Previous') . "'>";
               echo "</div></div>";
            }
         }
      } else {
         echo "</div>";
         echo "<div class='center first-bloc'>";
         echo "<div class=\"form-row\">";
         echo "<div class=\"bt-feature col-md-12 \">";
         echo __('No item to display');
         echo "</div></div>";
         echo "<div class=\"form-row\">";
         echo "<div class=\"bt-feature col-md-12 \">";
         echo "<input type='submit' class='submit' name='previous' value='" . __('Previous') . "'>";
         echo "<input type='hidden' name='previous_metademands_id' value='" . $metademands_id . "'>";
         echo "</td>";
         echo "</tr>";
         echo "</div></div>";
      }
   }

   /**
    * @param array $line
    * @param bool  $preview
    * @param       $metademands_data
    */
   function constructForm(array $line, $preview = false, $metademands_data) {
      global $CFG_GLPI;

      $count   = 0;
      $columns = 2;

      if (count($line)) {
         $style            = '';
         $style_left_right = '';
         $keys             = array_keys($line);
         $keyIndexes       = array_flip($keys);

         // Color
         if ($preview) {
            $style = 'padding-top:5px; 
                      border-top :3px solid #' . PluginMetademandsField::setColor($line[$keys[0]]['rank']) . ';
                      border-left :3px solid #' . PluginMetademandsField::setColor($line[$keys[0]]['rank']) . ';
                      border-right :3px solid #' . PluginMetademandsField::setColor($line[$keys[0]]['rank']);
         }

         echo "<div class=\"form-row\" style='$style'>";
         foreach ($line as $key => $data) {
            $config_link = "";
            if ($preview) {
               $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL('PluginMetademandsField') . "?id=" . $data['id'] . "'><i class='fas fa-wrench'></i></a>";
            }
            // Manage ranks
            if (isset($keyIndexes[$key])
                && isset($keys[$keyIndexes[$key] - 1])
                && $data['rank'] != $line[$keys[$keyIndexes[$key] - 1]]['rank']) {
               echo "</div>";
               if ($preview) {
                  echo "<div class=\"form-row\" style='border-bottom: 3px solid #" . PluginMetademandsField::setColor($data['rank'] - 1) . ";' >";
                  echo "</div>";

                  $style = 'padding-top:5px; 
                            border-top :3px solid #' . PluginMetademandsField::setColor($data['rank']) . ';
                            border-left :3px solid #' . PluginMetademandsField::setColor($data['rank']) . ';
                            border-right :3px solid #' . PluginMetademandsField::setColor($data['rank']);
               }
               echo "<div class='bt-feature col-md-12' 
                          style='border-bottom: #EBEBEB;border-bottom-style: dashed;border-width:1px;
                                 '>";
               echo "</div>";
               echo "&nbsp;";
               echo "<div class=\"form-row\" style='$style'>";
               $count = 0;
            }

            // If values are saved in session we retrieve it
            if (isset($_SESSION['plugin_metademands']['fields'])) {

               foreach ($_SESSION['plugin_metademands']['fields'] as $id => $value) {
                  if ($data['id'] == $id) {
                     $data['value'] = $value;
                  } else if ($data['id'] . '-2' == $id) {
                     $data['value-2'] = $value;
                  }
               }
            }

            // Title field
            if ($data['type'] == 'title') {
               echo "<div class=\"bt-feature col-md-12 \" style='border-bottom: #CCC;border-bottom-style: solid;'>";
               echo "<h4 class=\"bt-title-divider\" style='color:" . $data['color'] . ";'>";

               echo $data['label'];
               echo $config_link;
               if (isset($data['label2']) && !empty($data['label2'])) {
                  echo "&nbsp;";
                  Html::showToolTip($data['label2'],
                                    ['awesome-class' => 'fa-info-circle']);
               }
               echo "</h4>";
               if (!empty($data['comment'])) {
                  echo "<label><i>" . $data['comment'] . "</i></label>";
               }

               echo "</div>";
               $count = $count + $columns;

               // Other fields
            } else {
               if ($data['row_display'] == 1) {
                  echo "<div class=\"form-group col-md-11\">";
               } else {
                  echo "<div class=\"form-group col-md-5\">";
               }

               self::getFieldType($data, $metademands_data, $preview, $config_link);
               echo "</div>";

               // Label 2 (date interval)
               if (!empty($data['label2']) && $data['type'] != 'link') {
                  $required = "";
                  if ($data['is_mandatory']) {
                     $required = "required";
                  }
                  echo "<div class=\"form-group col-md-5\">";
                  echo "<label $required for='field[" . $data['id'] . "-2]' class='col-form-label col-form-label-sm'>" . $data['label2'] . "</label>";
                  $value2 = '';
                  if (isset($data['value-2'])) {
                     $value2 = $data['value-2'];
                  }

                  switch ($data['type']) {
                     case 'datetime_interval':
                        Html::showDateField("field[" . $data['id'] . "-2]", ['value' => $value2]);
                        $count++; // If date interval : pass to next line
                        break;
                  }
                  echo "</div>";
               }
            }

            // If next field is date interval : pass to next line
            if (isset($keyIndexes[$key])
                && isset($keys[$keyIndexes[$key] + 1])
                && $line[$keys[$keyIndexes[$key] + 1]]['type'] == 'datetime_interval') {
               $count++;

            }

            $count++;

            // Next row
            $style_left_right = "";
            if ($count >= $columns) {
               if ($preview) {
                  $style_left_right = 'border-left :3px solid #' . PluginMetademandsField::setColor($data['rank']) . ';
                                       border-right :3px solid #' . PluginMetademandsField::setColor($data['rank']);
               }

               echo "</div>";
               echo "<div class=\"form-row\" style='$style_left_right'>";
               $count = 0;
            }

         }
         echo "</div>";
         if ($preview) {
            echo "<div class=\"form-row\" style='border-bottom: 3px solid #" . PluginMetademandsField::setColor($line[$keys[count($keys) - 1]]['rank']) . ";' >";
            echo "</div>";
         }

         // Fields linked
         foreach ($line as $data) {
            if (!empty($data['fields_link'])) {
               $script = "var metademandWizard = $(document).metademandWizard();";
               $script .= "metademandWizard.metademand_setMandatoryField('metademands_wizard_red" . $data['fields_link'] . "', 'field[" . $data['id'] . "]', '" . $data['check_value'] . "');";
               echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
            }
         }

      } else {
         echo "<div class='center'><b>" . __('No item to display') . "</b></div>";
      }
   }

   /**
    * @param $data
    * @param $metademands_data
    */
   function getFieldType($data, $metademands_data, $preview = false, $config_link = "") {
      global $CFG_GLPI;

      $value = '';
      if (isset($data['value'])) {
         $value = $data['value'];
      }
      $required = "";
      if ($data['is_mandatory'] && $data['type'] != 'parent_field') {
         $required = "required";
      }

      $upload = "";
      if ($data['type'] == "upload") {
         $upload = " (" . Document::getMaxUploadSize() . ")";
      }
      if ($data['is_mandatory']) {
         $required = "red";
      }

      echo "<label for='field[" . $data['id'] . "]' class='$required col-form-label col-form-label-sm'>";
      echo $data['label'] . " $upload";
      if ($preview) {
         echo $config_link;
      }
      echo "</label>";
      echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $data['id'] . "'>";
      if ($data['is_mandatory'] && $data['type'] != 'parent_field') {
         echo "*";
      }
      echo "</span>";
      echo "&nbsp;";
      // Input
      switch ($data['type']) {
         case 'dropdown_multiple' :
            if (!empty($data['custom_values'])) {
               $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
               $defaults              = PluginMetademandsField::_unserialize($data['default_values']);
               $default_values        = [];
               if ($defaults) {
                  foreach ($defaults as $k => $v) {
                     if ($v != null) {
                        $default_values[] = $k;
                     }
                  }
               }
               ksort($data['custom_values']);
               $value = is_array($value) ? $value : $default_values;
               Dropdown::showFromArray("field[" . $data['id'] . "]", $data['custom_values'],
                                       ['values' => $value, 'width' => '250px', 'multiple' => true
                                       ]);
            }
            break;
         case 'dropdown':
            if (!empty($data['custom_values']) && $data['item'] == 'other') {
               $data['custom_values']    = PluginMetademandsField::_unserialize($data['custom_values']);
               $data['custom_values'][0] = Dropdown::EMPTY_VALUE;
               ksort($data['custom_values']);
               Dropdown::showFromArray("field[" . $data['id'] . "]", $data['custom_values'],
                                       ['value' => $value, 'width' => '200px',
                                       ]);
            } else {
               switch ($data['item']) {
                  case 'user':
                     User::dropdown(['name'   => "field[" . $data['id'] . "]",
                                     'entity' => $_SESSION['glpiactiveentities'],
                                     'right'  => 'all',
                                     'value'  => $value]);
                     break;
                  case 'usertitle':
                     $titlerand = mt_rand();
                     UserTitle::dropdown(['name' => "field[" . $data['id'] . "]", 'rand' => $titlerand]);
                     break;
                  case 'usercategory':
                     $catrand = mt_rand();
                     UserCategory::dropdown(['name' => "field[" . $data['id'] . "]", 'rand' => $catrand]);
                     break;
                  case 'PluginResourcesResource':
                     PluginResourcesResource::dropdown(['name'   => "field[" . $data['id'] . "]",
                                                        'entity' => $_SESSION['glpiactiveentities'],
                                                        'value'  => $value]);
                     break;
                  case 'PluginMetademandsITILApplication' :
                     $opt = ['value'  => $value,
                             'entity' => $_SESSION['glpiactiveentities'],
                             'name'   => "field[" . $data['id'] . "]"];
                     PluginMetademandsITILApplication::dropdown($opt);
                     break;
                  case 'PluginMetademandsITILEnvironment' :
                     $opt = ['value'  => $value,
                             'entity' => $_SESSION['glpiactiveentities'],
                             'name'   => "field[" . $data['id'] . "]"];
                     PluginMetademandsITILEnvironment::dropdown($opt);
                     break;
                  default:
                     Dropdown::show($data['item'], ['name'     => "field[" . $data['id'] . "]",
                                                    'entity'   => $_SESSION['glpiactiveentities'],
                                                    'value'    => $value,
                                                    'readonly' => true]);
                     break;
               }
            }
            break;
         case 'text':
            echo "<input type='text' name='field[" . $data['id'] . "]' value='" . $value . "' class='form-control form-control-sm' id='field[" . $data['id'] . "]' placeholder=\"" . $data['comment'] . "\">";
            break;

         case 'link':
            if (!empty($data['custom_values'])) {
               $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
               switch ($data['custom_values'][0]) {
                  case 'button' :
                     $btnLabel = __('Link');
                     if (!empty($data['label2'])) {
                        $btnLabel = $data['label2'];
                     }
                     echo "<input type='submit' class='submit' value ='$btnLabel' target='_blank' onclick=\"window.open('" . $data['custom_values'][1] . "','_blank');return false\">";

                     break;
                  case 'link_a' :
                     echo "<a target='_blank' href ='" . $data['custom_values'][1] . "'>" . $data['custom_values'][1] . "</a>";
                     break;
               }
            }
            //            echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $data['custom_values'] . "'>";

            break;
         case 'checkbox':
            if (!empty($data['custom_values'])) {
               $data['custom_values']  = PluginMetademandsField::_unserialize($data['custom_values']);
               $data['comment_values'] = PluginMetademandsField::_unserialize($data['comment_values']);
               $defaults               = PluginMetademandsField::_unserialize($data['default_values']);
               if (!empty($value)) {
                  $value = PluginMetademandsField::_unserialize($value);
               }
               $nbr    = 0;
               $inline = "";
               if ($data['row_display'] == 1) {
                  $inline = 'custom-control-inline';
               }
               foreach ($data['custom_values'] as $key => $label) {
                  echo "<div class='custom-control custom-checkbox $inline'>";
                  $checked = "";
                  if (isset($value[$key])) {
                     $checked = isset($value[$key]) ? 'checked' : '';
                  } elseif (isset($defaults[$key])) {
                     $checked = isset($defaults[$key]) ? 'checked' : '';
                  }
                  echo "<input class='custom-control-input' type='checkbox' name='field[" . $data['id'] . "][" . $key . "]' id='field[" . $data['id'] . "][" . $key . "]' value='checkbox' $checked>";
                  $nbr++;
                  echo "&nbsp;<label class='custom-control-label' for='field[" . $data['id'] . "][" . $key . "]'>$label</label>";
                  if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                     echo "&nbsp;<span style='vertical-align: bottom;'>";
                     Html::showToolTip($data['comment_values'][$key],
                                       ['awesome-class' => 'fa-info-circle']);
                     echo "</span>";
                  }
                  echo "</div>";
               }
            } else {
               $checked = $value ? 'checked' : '';
               echo "<input class='custom-control-input' type='checkbox' name='field[" . $data['id'] . "]' value='checkbox' $checked>";
            }
            break;

         case 'radio':
            if (!empty($data['custom_values'])) {
               $data['custom_values']  = PluginMetademandsField::_unserialize($data['custom_values']);
               $data['comment_values'] = PluginMetademandsField::_unserialize($data['comment_values']);
               $defaults               = PluginMetademandsField::_unserialize($data['default_values']);
               if (!empty($value)) {
                  $value = PluginMetademandsField::_unserialize($value);
               }
               $nbr    = 0;
               $inline = "";
               if ($data['row_display'] == 1) {
                  $inline = 'custom-control-inline';
               }
               foreach ($data['custom_values'] as $key => $label) {
                  echo "<div class='custom-control custom-radio $inline'>";

                  $checked = "";
                  if (!empty($value) && $value == $key) {
                     $checked = $value == $key ? 'checked' : '';
                  } elseif (isset($defaults[$key])) {
                     $checked = isset($defaults[$key]) ? 'checked' : '';
                  }
                  echo "<input class='custom-control-input' type='radio' name='radio[" . $data['id'] . "]' id='radio[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                  $nbr++;
                  echo "&nbsp;<label class='custom-control-label' for='radio[" . $data['id'] . "][" . $key . "]'>$label</label>";
                  if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                     echo "&nbsp;<span style='vertical-align: bottom;'>";
                     Html::showToolTip($data['comment_values'][$key],
                                       ['awesome-class' => 'fa-info-circle']);
                     echo "</span>";
                  }
                  echo "</div>";
               }
            }
            break;
         case
         'textarea':
            echo "<textarea class='form-control' rows='3' placeholder=\"" . $data['comment'] . "\" name='field[" . $data['id'] . "]' id='field[" . $data['id'] . "]' $required>" . $value . "</textarea>";
            break;
         case 'datetime_interval':
         case 'datetime':
            Html::showDateField("field[" . $data['id'] . "]", ['value' => $value]);
            break;
         case 'yesno':
            $option[1] = __('No');
            $option[2] = __('Yes');
            $value     = $data['custom_values'];
            Dropdown::showFromArray("field[" . $data['id'] . "]", $option, ['value' => $value]);
            break;
         case 'upload':
            Html::file(['filecontainer' => 'fileupload_info_ticket',
                        'editor_id'     => '',
                        'showtitle'     => false,
                        'multiple'      => true]);
            break;

         case 'parent_field':
            foreach ($metademands_data as $metademands_data_steps) {
               foreach ($metademands_data_steps as $line_data) {
                  foreach ($line_data['form'] as $field_id => $field) {
                     if ($field_id == $data['parent_field_id']) {

                        $value_parent_field = '';
                        if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id']])) {
                           $value_parent_field = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id']];
                        }

                        switch ($field['type']) {
                           case 'dropdown_multiple':
                              if (!empty($field['custom_values'])) {
                                 $value_parent_field = $field['custom_values'][$value_parent_field];
                              }
                              break;
                           case 'dropdown':
                              if (!empty($field['custom_values']) && $field['item'] == 'other') {
                                 $value_parent_field = $field['custom_values'][$value_parent_field];
                              } else {
                                 switch ($field['item']) {
                                    case 'user':
                                       echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                       $user = new User();
                                       $user->getFromDB($value_parent_field);
                                       $value_parent_field = $user->getName();
                                       break;
                                    default:
                                       $dbu = new DbUtils();
                                       echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                       $value_parent_field = Dropdown::getDropdownName($dbu->getTableForItemType($field['item']),
                                                                                       $value_parent_field);
                                       break;
                                 }
                              }
                              break;
                           case 'checkbox':
                              if (!empty($field['custom_values'])) {
                                 $field['custom_values'] = PluginMetademandsField::_unserialize($field['custom_values']);
                                 $checkboxes             = PluginMetademandsField::_unserialize($value_parent_field);

                                 $custom_checkbox = [];
                                 foreach ($field['custom_values'] as $key => $label) {
                                    $checked = isset($checkboxes[$key]) ? 1 : 0;
                                    if ($checked) {
                                       $custom_checkbox[] .= $label;
                                       echo "<input type='hidden' name='field[" . $data['id'] . "][" . $key . "]' value='checkbox'>";

                                    }
                                 }
                                 $value_parent_field = implode('<br>', $custom_checkbox);
                              }
                              break;

                           case 'radio' :
                              if (!empty($field['custom_values'])) {
                                 $field['custom_values'] = PluginMetademandsField::_unserialize($field['custom_values']);
                                 foreach ($field['custom_values'] as $key => $label) {
                                    if ($value_parent_field == $key) {
                                       echo "<input type='hidden' name='radio[" . $data['id'] . "]' value='$key' >";
                                       $value_parent_field = $label;
                                       break;
                                    }

                                 }
                              }
                              break;

                           case 'datetime':
                              echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                              $value_parent_field = Html::convDate($value_parent_field);

                              break;

                           case 'datetime_interval':
                              echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                              if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"])) {
                                 $value_parent_field2 = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"];
                                 echo "<input type='hidden' name='field[" . $data['id'] . "-2]' value='" . $value_parent_field2 . "'>";
                              } else {
                                 $value_parent_field2 = 0;
                              }
                              $value_parent_field = Html::convDate($value_parent_field) . " - " . Html::convDate($value_parent_field2);
                              break;
                           case 'yesno' :
                              echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                              $value_parent_field = Dropdown::getYesNo($value_parent_field);
                              break;

                           default :
                              echo "<input type='hidden' name='field[" . $data['id'] . "]' value='" . $value_parent_field . "'>";

                        }
                        echo $value_parent_field;
                        break;
                     }
                  }
               }
            }
            break;
      }
   }

   /**
    * @param       $metademands_id
    * @param       $values
    * @param array $options
    *
    * @throws \GlpitestSQLError
    */
   function addMetademands($metademands_id, $values, $options = []) {
      global $CFG_GLPI;

      $metademands = new PluginMetademandsMetademand();
      $result      = $metademands->addMetademands($metademands_id, $values);
      Session::addMessageAfterRedirect($result['message']);
      unset($_SESSION['plugin_metademands']);

      if (!empty($options['resources_id'])) {
         Html::redirect($CFG_GLPI["root_doc"] . "/plugins/resources/front/wizard.form.php");
      } else {
         Html::back();
      }
   }

   /**
    * @param      $message
    * @param bool $error
    */
   function showMessage($message, $error = false) {
      $class = $error ? "class='red'" : "";

      echo "<br><div class='box'>";
      echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'>";
      echo "</div></div></div>";
      echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter center'>";
      echo "<h3 $class>" . $message . "</h3>";
      echo "</div></div></div>";
      echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'>";
      echo "</div></div></div>";
      echo "</div>";
   }


   /**
    * @param array $value
    * @param array $fields
    * @param array $all_fields
    *
    * @return bool
    */
   static function checkMandatoryFields(array $value, array $fields, array $all_fields = []) {

      $checkKo             = [];
      $checkKoDateInterval = [];
      $msg                 = [];

      if ($value['type'] != 'parent_field') {
         // Check fields empty
         if ($value['is_mandatory'] && empty($fields['value'])) {
            $msg[]     = $value['label'];
            $checkKo[] = 1;
         }

         // Check linked field mandatory
         if (!empty($value['fields_link'])
             && !empty($value['check_value'])
             && PluginMetademandsTicket_Field::isCheckValueOK($fields['value'], $value['check_value'], $value['type'])
             && (empty($all_fields[$value['fields_link']]) || $all_fields[$value['fields_link']] == 'NULL')
         ) {

            $field = new PluginMetademandsField();
            $field->getFromDB($value['fields_link']);
            $msg[]     = $field->fields['label'] . ' ' . $field->fields['label2'];
            $checkKo[] = 1;
         }

         // Check date
         if ($value['type'] == "date" || $value['type'] == "datetime" || $value['type'] == "datetime_interval") {
            // date Null
            if ($value['is_mandatory'] && $fields['value'] == 'NULL') {
               $msg[]     = $value['label'];
               $checkKo[] = 1;
            }
            // date not < today
            if ($fields['value'] != 'NULL'
                && !empty($fields['value'])
                && !empty($value['check_value'])
                && !(strtotime($fields['value']) >= strtotime(date('Y-m-d')))) {
               $msg[]     = sprintf(__("Date %s cannot be less than today's date", 'metademands'), $value['label']);
               $checkKo[] = 1;
            }
         }

         // Check date interval is right
         if ($value['type'] == "datetime_interval" && isset($all_fields[$fields['id'] . '-2'])) {
            if (strtotime($fields['value']) > strtotime($all_fields[$fields['id'] . '-2'])) {
               $msg[]                 = sprintf(__('Date %1$s cannot be greater than date %2$s', 'metademands'), $value['label'], $value['label2']);
               $checkKoDateInterval[] = 1;
            }
         }

         // Check File upload field
         if ($value['type'] == "upload" && $value['is_mandatory']) {
            if (isset($_FILES['filename']['tmp_name'])) {
               if (empty($_FILES['filename']['tmp_name'][0])) {
                  $msg[]     = $value['label'];
                  $checkKo[] = 1;
               }
            }
         }

      }
      if (in_array(1, $checkKo) || in_array(1, $checkKoDateInterval)) {
         Session::addMessageAfterRedirect(__('Mandatory field') . " : " . implode(', ', $msg), false, ERROR);
         return false;
      }

      return true;
   }

   /**
    * @param       $name
    * @param       $data
    * @param array $options
    */
   function showDropdownFromArray($name, $data, $options = []) {
      $params['on_change'] = '';
      $params['no_empty']  = 0;
      $params['value']     = '';
      $params['tree']      = false;
      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }
      //print_r($params['value']);
      echo "<select id='" . $name . "' name='" . $name . "' onchange='" . $params['on_change'] . "'>";
      if (!$params['no_empty']) {
         echo "<option value='0'>-----</option>";
      }
      foreach ($data as $id => $values) {

         $level = 0;
         $class = "";
         $raquo = "";

         if ($params['tree']) {
            $level = $values['level'];
            $class = " class='tree' ";
            $raquo = "&raquo;";

            if ($level == 1) {
               $class = " class='treeroot'";
               $raquo = "";
            }
         }
         $selected = "";

         echo "<option value='" . $id . "' $class " . ($params['value'] == $id ? 'selected' : '') . " >" . str_repeat("&nbsp;&nbsp;&nbsp;", $level) . $raquo;

         if ($params['tree']) {
            echo $values['name'];
         } else {
            echo $values;
         }
         echo "</option>";
      }
      echo "</select>";
   }

}