<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
  Copyright (C) 2018-2026 by the Metademands Development Team.
 -------------------------------------------------------------------------
 LICENSE
 This file is part of Metademands.
 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use CommonGLPI;
use Glpi\ItemTranslation\ItemTranslation;
use Migration;
use Override;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class FieldTranslation extends AbstractMetademandTranslation
{
    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if (!($item instanceof Field)) {
            return '';
        }

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = countDistinctElementsInTable(
                static::getTable(),
                'language',
                ['items_id' => $item->getID(), 'itemtype' => $item->getType()]
            );
        }

        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
    }

    #[Override]
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if (!($item instanceof Field)) {
            return false;
        }

        static::renderTranslationsTab($item);
        return true;
    }

    #[Override]
    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        $criteria = ['itemtype' => [Field::class]];
        return [crc32(serialize($criteria)) => $criteria];
    }

    #[Override]
    public static function install(Migration $migration): void
    {
        static::migrateFromLegacyTable('glpi_plugin_metademands_fieldtranslations', $migration);
    }
}
