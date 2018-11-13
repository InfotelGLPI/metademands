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
 * Update from 2.1.4 to 2.2.5
 *
 * @return bool for success (will die for most error)
 * */
function update214to225() {
   global $DB;

   $migration = new Migration(225);

   // Config
   $migration->addField('glpi_plugin_metademands_configs', 'enable_families', 'bool', ['value' => '0']);
   // Add out production
   $migration->addField('glpi_plugin_metademands_itilenvironments', 'is_outproduction', 'bool', ['value' => '0']);
   // Add criticity
   $migration->addField('glpi_plugin_metademands_itilapplications', 'is_critical', 'integer', ['value' => '0']);
   $migration->executeMigration();

   $dbu = new DbUtils();
   // Application
   if ($DB->tableExists('glpi_itilapplications')) {
      $datas = $dbu->getAllDataFromTable("glpi_itilapplications", "`name` != 'Aucune'");
      foreach ($datas as $data) {
         $DB->queryOrDie("INSERT INTO `glpi_plugin_metademands_itilapplications`
                        (`id`, `name`, `entities_id`, `is_recursive`, `comment`, `is_critical`) 
                        VALUES (".$data['id'].",'".addslashes($data['name'])."',".$data['entities_id'].",".$data['is_recursive'].",'".addslashes($data['comment'])."',".$data['is_critical'].")", "ITILApplication migration");
      }

      // Link with tickets
      $datas = $dbu->getAllDataFromTable("glpi_tickets", "`itilapplications_id` != 0");
      foreach ($datas as $data) {
         $DB->queryOrDie("INSERT INTO `glpi_plugin_metademands_tickets_itilapplications`(`tickets_id`, `plugin_metademands_itilapplications_id`) VALUES (".$data['id'].",".$data['itilapplications_id'].")", "ITILApplication migration");
      }
   }

   // Environment
   if ($DB->tableExists('glpi_itilenvironments')) {
      $datas = $dbu->getAllDataFromTable("glpi_itilenvironments", "`name` != 'Sans objet'");
      foreach ($datas as $data) {
         $DB->queryOrDie("INSERT INTO `glpi_plugin_metademands_itilenvironments`(`id`, `name`, `entities_id`, `is_recursive`, `comment`, `is_outproduction`) VALUES (".$data['id'].",'".addslashes($data['name'])."',".$data['entities_id'].",".$data['is_recursive'].",'".addslashes($data['comment'])."',".$data['is_outproduction'].")", "ITILEnvironment migration");
      }

      // Link with tickets
      $datas = $dbu->getAllDataFromTable("glpi_tickets", "`itilenvironments_id` != 0");
      foreach ($datas as $data) {
         $DB->queryOrDie("INSERT INTO `glpi_plugin_metademands_tickets_itilenvironments`(`tickets_id`, `plugin_metademands_itilenvironments_id`) VALUES (".$data['id'].",".$data['itilenvironments_id'].")", "ITILEnvironment migration");
      }
   }

   $migration->changeField('glpi_plugin_metademands_tickettasks', 'itilapplications_id', 'plugin_metademands_itilapplications_id', 'int');
   $migration->changeField('glpi_plugin_metademands_tickettasks', 'itilenvironments_id', 'plugin_metademands_itilenvironments_id', 'int');

   // Update template search option num
   $DB->queryOrDie("UPDATE `glpi_tickettemplatepredefinedfields` SET num = 8050 WHERE num = 81");// Application
   $DB->queryOrDie("UPDATE `glpi_tickettemplatemandatoryfields` SET num = 8050 WHERE num = 81");// Application
   $DB->queryOrDie("UPDATE `glpi_tickettemplatehiddenfields` SET num = 8050 WHERE num = 81");// Application
   $DB->queryOrDie("UPDATE `glpi_tickettemplatepredefinedfields` SET num = 9050 WHERE num = 192");// Environment
   $DB->queryOrDie("UPDATE `glpi_tickettemplatemandatoryfields` SET num = 9050 WHERE num = 192");// Environment
   $DB->queryOrDie("UPDATE `glpi_tickettemplatehiddenfields` SET num = 9050 WHERE num = 192");// Environment
   $DB->queryOrDie("UPDATE `glpi_plugin_metademands_ticketfields` SET num = 9050 WHERE num = 192");// Metademand environment
   $DB->queryOrDie("UPDATE `glpi_plugin_metademands_ticketfields` SET num = 8050 WHERE num = 81");// Metademand application

   // Update rule criteria
   $DB->queryOrDie("UPDATE `glpi_rulecriterias` SET `criteria` = 'plugin_metademands_itilapplications_id' WHERE `criteria` = 'itilapplications_id'");
   $DB->queryOrDie("UPDATE `glpi_rulecriterias` SET `criteria` = 'plugin_metademands_itilenvironments_id' WHERE `criteria` = 'itilenvironments_id'");

   // Update rule action
   $DB->queryOrDie("UPDATE `glpi_ruleactions` SET `field` = 'plugin_metademands_itilapplications_id' WHERE `field` = 'itilapplications_id'");
   $DB->queryOrDie("UPDATE `glpi_ruleactions` SET `field` = 'plugin_metademands_itilenvironments_id' WHERE `field` = 'itilenvironments_id'");

   $migration->executeMigration();

   return true;
}

