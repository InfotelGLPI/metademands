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
use DbUtils;
use Migration;
use Session;
use CommonGLPI;
use User;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class Ticket_Task
 */
class Ticket_Task extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('Task creation', 'metademands');
   }

   /**
    * @return bool|int
    */
   static function canView(): bool
   {
      return Session::haveRight(self::$rightname, READ);
   }

   /**
    * @return bool
    */
   static function canCreate(): bool
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
                        `entities_id`                       int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_tasks_id`       int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
                        KEY `entities_id` (`entities_id`),
                        KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        $migration->dropForeignKeyContraint($table, 'glpi_plugin_metademands_tickets_tasks_ibfk_1');

        //version 3.3.0
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
        }
        if (!isIndex($table, "entities_id")) {
            $migration->addKey($table, "entities_id");
        }
        if (!isIndex($table, "plugin_metademands_tasks_id")) {
            $migration->addKey($table, "plugin_metademands_tasks_id");
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
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == Metademand::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $dbu = new DbUtils();
               return self::createTabEntry(self::getTypeName(),
                                           $dbu->countElementsInTable($this->getTable(),
                                                                      ["plugin_metademands_metademands_id" => $item->getID()]));
            }
            return self::getTypeName();
         }
      }
      return '';
   }

   /**
    * Display content for each users
    *
    * @static
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $field = new self();

      if ($item->getType() == Metademand::class) {
//         $field->showPluginFromItems($item);
      }
      return true;
   }

   /**
    * @param $field
    * @param $name (default '')
    * @param $values (default '')
    * @param $options   array
    *
    * @return string
    **@since version 0.84
    *
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      $options['toadd'] = ['mygroups' => __('My groups')];
      $options['name'] = $name;
      switch ($field) {
         case 'id':
           return \Group::dropdown($options);
            break;


         case 'plugin_metademands_tasks_id':
            unset($options['toadd']);
            $right = "own_ticket";
            $options = [ 'display' => false,
                         'name' => $name,
                        'entity'      => $_SESSION['glpiactive_entity'],
                        'right'       => $right,
                        'ldap_import' => true];
            return User::dropdown($options);
            break;

      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * display a value according to a field
    *
    * @param $field     String         name of the field
    * @param $values    String / Array with the value to display
    * @param $options   Array          of option
    *
    * @return a string
    **@since version 0.83
    *
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
//         case 'id':
//            return "okkk";
//            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * display a value according to a field
    *
    * @param $field     String         name of the field
    * @param $values    String / Array with the value to display
    * @param $options   Array          of option
    *
    * @return a string
    **@since version 0.83
    *
    */
   static function getFirstTicket($ticket_id) {

      $self = new self();
      if($self->getFromDBByCrit(['tickets_id' => $ticket_id])) {
         return self::getFirstTicket($self->fields['parent_tickets_id']);
      } else {
         return $ticket_id;
      }
   }

}
