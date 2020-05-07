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


/**
 * Class PluginMetademandsServicecatalog
 */
class PluginMetademandsServicecatalog extends CommonGLPI {

   static $rightname = 'plugin_metademands';

   var $dohistory = false;

   /**
    * @return bool
    * @throws \GlpitestSQLError
    */
   static function canUse() {
      $metademands = PluginMetademandsWizard::selectMetademands();
      return (Session::haveRight(self::$rightname, READ) && (count($metademands) > 0));
   }

   /**
    * @return string
    */
   static function getMenuTitle() {
      return "<span class=\"de-em\">" . __('Create a', 'servicecatalog')  ." ". __('advanced request', 'metademands'). " </span>";
   }

   /**
    * @return string
    */
   static function getMenuLink() {
      global $CFG_GLPI;

      return $CFG_GLPI['root_doc'] . "/plugins/servicecatalog/front/main.form.php?choose_category&type=metademands";
   }

   /**
    * @return string
    */
   static function getMenuLogo() {

      return "fas fa-share-alt";
   }


   /**
    * @return string
    * @throws \GlpitestSQLError
    */
   static function getMenuComment() {

      $list        = "";
      $metademands = PluginMetademandsWizard::selectMetademands();

      foreach ($metademands as $id => $name) {
         $list .= $name . '<br>';
      }
      return $list;
   }

   /**
    * @return string
    */
   static function getLinkList() {
      return __('Select the advanced request', 'metademands');
   }

   /**
    * @param $type
    * @param $category_id
    *
    * @return string or bool
    */
   static function getLinkURL($type, $category_id) {
      global $CFG_GLPI;

      $dbu   = new DbUtils();
      $metas = $dbu->getAllDataFromTable('glpi_plugin_metademands_metademands',
                                         ["`itilcategories_id`" => $category_id,
                                          "`is_active`"         => 1,
                                          "`type`"              => $type]);

      if (!empty($metas)) {
         $meta = reset($metas);
         //Redirect if not linked to a resource contract type
         if (!$dbu->countElementsInTable("glpi_plugin_metademands_metademands_resources",
                                         ["plugin_metademands_metademands_id" => $meta["id"]])) {

            return $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $meta["id"] . "&tickets_id=0&step=2";

         }
      }
      return false;
   }

   static function getList() {
      global $CFG_GLPI;

      $metademands = PluginMetademandsWizard::selectMetademands();
      $plugin      = new Plugin();
      if ($plugin->isActivated("servicecatalog") && ($plugin->getInfo('servicecatalog')["version"] >= "1.6.0")) {
         //echo '<div class="btnsc-normal fa-back" id="click">';


         //$fasize = "fa-5x";
         //$margin = "fas-sc";
         //$config = new PluginServicecatalogConfig();
         //if ($config->getCatSize() == 'verysmall') {
         //   $margin = "fas-sc-small";
         //}

         //echo "<i class=\"fas $margin fa-chevron-circle-up $fasize\"></i>";
         //echo "<br><br>";
         //echo "<span class=\"label_back bottom_title\">";

         //         echo __('Back');

         //         echo "</span>";
         echo "<script>$(document).ready(function() {
                 $('#click').click(function() {
                      window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/servicecatalog/front/main.form.php';
                 });
            });</script>";

         //echo "</div>";
         //      echo '<li>';
         //      echo "<a class='bt-back' title='" . __('Back') . "' href='" . $CFG_GLPI['root_doc'] . "/plugins/servicecatalog/front/main.form.php'></a>";
         //      echo '</li>';

         foreach ($metademands as $id => $name) {

            $meta = new PluginMetademandsMetademand();
            if ($meta->getFromDB($id)) {
               echo '<div class="favsc-normal visitedchildbg favsc-link-normal sc-widget" >';
               echo "<a class='bt-buttons' href='" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
               $fasize = "fa-6x";
               echo "<div class='center'>";
               $icon = "fa-share-alt";
               if (!empty($meta->fields['icon'])) {
                  $icon = $meta->fields['icon'];
               }
               echo "<i class='bt-interface fa-menu-sc fas $icon $fasize'></i>";//$style
               echo "</div>";

               echo "</a>";
               echo "<a class='bt-buttons center' href='" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
               echo "<p>";
               echo $meta->getName();
               echo "<br>";

               echo "<em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
               if (!empty($meta->fields['comment'])) {
                  echo $meta->fields['comment'];
               }
               echo "</span></em>";

               echo "</p></a></div>";
            }
         }
      } else {
         echo '<li>';
         echo "<a class='bt-back' title='" . __('Back') . "' href='" . $CFG_GLPI['root_doc'] . "/plugins/servicecatalog/front/main.form.php'></a>";
         echo '</li>';

         foreach ($metademands as $id => $name) {

            $meta = new PluginMetademandsMetademand();
            if ($meta->getFromDB($id)) {
               echo '<li>';
               echo "<a class='bt-list-advancedrequest' href='" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
               echo "</a>";
               echo "<a class='bt-buttons' style='display: block;width: 100%; height: 100%;' href='" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
               echo "<p>";
               echo Html::resume_text($meta->getName(), 30);
               echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
               echo $meta->fields['comment'];
               echo "</span></em>";
               echo "</p></a></li>";
            }
         }
      }


      if (count($metademands) == 0) {
         echo '<div class="bt-feature bt-col-sm-5 bt-col-md-2">';
         echo '<h5 class="bt-title">';
         echo '<span class="de-em">' . __('No advanced request found', 'metademands') . '</span></h5></a>';
         echo '</div>';
         echo '</div>';
      }
   }
}
