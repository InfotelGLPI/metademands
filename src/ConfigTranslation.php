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

use CommonGLPI;
use Override;
use Session;

/**
 * Translations of the texts of the configuration shown to the requesters: the comments of
 * the types of meta-demands on the home of the wizard (Config::listTranslationsHandlers()).
 */
class ConfigTranslation extends AbstractMetademandTranslation
{
    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if (!($item instanceof Config)) {
            return '';
        }

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = countDistinctElementsInTable(
                static::getTable(),
                'language',
                ['items_id' => $item->getID(), 'itemtype' => $item->getType()],
            );
        }

        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
    }

    #[Override]
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if (!($item instanceof Config)) {
            return false;
        }

        static::renderTranslationsTab($item);
        return true;
    }

    #[Override]
    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        $criteria = ['itemtype' => [Config::class]];
        return [crc32(serialize($criteria)) => $criteria];
    }

    /**
     * Text of the configuration in the language of the session, '' without translation.
     *
     * @param string $key column of the configuration
     */
    public static function getTranslatedValue(string $key): string
    {
        $translation = new self();
        if (
            $translation->getFromDBByCrit([
                'items_id' => 1,
                'itemtype' => Config::class,
                'key'      => $key,
                'language' => $_SESSION['glpilanguage'] ?? '',
            ])
        ) {
            return (string) ($translation->getTranslation() ?? '');
        }

        return '';
    }
}
