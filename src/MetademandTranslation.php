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
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\ItemTranslation\ItemTranslation;
use Migration;
use Override;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class MetademandTranslation extends AbstractMetademandTranslation
{
    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if (!($item instanceof Metademand)) {
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
        if (!($item instanceof Metademand)) {
            return false;
        }

        $translations_by_language = static::getTranslationsForMetademand($item);
        $available_languages      = array_diff_key(Dropdown::getLanguages(), $translations_by_language);

        TemplateRenderer::getInstance()->display('@metademands/item_translations.html.twig', [
            'item'                => $item,
            'translation_class'   => static::class,
            'translations'        => $translations_by_language,
            'available_languages' => $available_languages,
            'canedit'             => $item->can($item->getID(), UPDATE),
            'ajax_url'            => PLUGIN_METADEMANDS_WEBDIR . '/ajax/translations.php',
        ]);

        return true;
    }

    /**
     * Returns a map language => MetademandTranslation (metademand-level record only).
     * Stats methods on these objects use Metademand::listTranslationsHandlers()
     * which already covers all Fields, so the percentages are accurate.
     *
     * @return array<string, MetademandTranslation>
     */
    public static function getTranslationsForMetademand(Metademand $metademand): array
    {
        $by_lang = [];

        // Metademand-level records drive the language list and stats
        foreach (static::getTranslationsForItem($metademand) as $tr) {
            if ($tr instanceof MetademandTranslation) {
                $by_lang[$tr->fields['language']] = $tr;
            }
        }

        return $by_lang;
    }

    #[Override]
    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        // Covers both Metademand-level and Field-level translations
        $criteria = ['itemtype' => [Metademand::class, Field::class]];
        return [crc32(serialize($criteria)) => $criteria];
    }

    #[Override]
    public static function install(Migration $migration): void
    {
        static::migrateFromLegacyTable('glpi_plugin_metademands_metademandtranslations', $migration);
    }
}
