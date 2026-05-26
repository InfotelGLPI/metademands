<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use CommonDropdown;
use DBConnection;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Migration;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class Basketobjecttype
 */
class Basketobjecttype extends CommonDropdown implements ProvideTranslationsInterface
{

    static $rightname = "dropdown";
   var $can_be_translated = true;

   /**
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return _n('Object type','Object types', $nb,'metademands');
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
                        `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
                         PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    public function listTranslationsHandlers(): array
    {
        $key = sprintf('%s_%d', static::getType(), $this->getID());

        return [
            $key => [
                new TranslationHandler(
                    item: $this,
                    key: 'name',
                    name: __('Name'),
                    value: $this->fields['name'],
                ),
            ],
        ];
    }
}
