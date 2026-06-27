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
 the Free Software Foundation; either version 3 of the License, or
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

use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\ItemTranslation;
use Migration;
use Override;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

abstract class AbstractMetademandTranslation extends ItemTranslation
{
    public static $rightname = 'plugin_metademands';

    public static function getTypeName($nb = 0): string
    {
        return _n('Translation', 'Translations', $nb);
    }

    public static function getIcon(): string
    {
        return 'ti ti-language';
    }

    #[Override]
    public static function getTable($classname = null): string
    {
        if (is_a($classname ?? static::class, ItemTranslation::class, true)) {
            return parent::getTable(ItemTranslation::class);
        }
        return parent::getTable($classname);
    }

    /**
     * Returns translations for an item grouped by language.
     * @return array<string, array<string, ItemTranslation>>
     */
    public static function getTranslationsByLanguage(CommonDBTM $item): array
    {
        $by_lang = [];
        foreach (static::getTranslationsForItem($item) as $tr) {
            $by_lang[$tr->fields['language']][$tr->fields['key']] = $tr;
        }
        return $by_lang;
    }

    /**
     * Renders the translation tab via Twig.
     */
    protected static function renderTranslationsTab(CommonDBTM $item): void
    {
        if (!($item instanceof ProvideTranslationsInterface)) {
            return;
        }

        $translations = static::getTranslationsByLanguage($item);

        $handlers = [];
        foreach ($item->listTranslationsHandlers() as $group) {
            foreach ($group as $handler) {
                $handlers[$handler->getKey()] = $handler;
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/item_translations.html.twig', [
            'item'                => $item,
            'translation_class'   => static::class,
            'translations'        => $translations,
            'handlers'            => $handlers,
            'available_languages' => array_diff_key(Dropdown::getLanguages(), $translations),
            'canedit'             => $item->can($item->getID(), UPDATE),
            'ajax_url'            => PLUGIN_METADEMANDS_WEBDIR . '/ajax/translations.php',
        ]);
    }

    /**
     * Migrates records from an old plugin table into glpi_itemtranslations_itemtranslations.
     * Old schema: id, items_id, itemtype, language, field, value
     */
    protected static function migrateFromLegacyTable(string $old_table, Migration $migration): void
    {
        global $DB;

        if (!$DB->tableExists($old_table)) {
            return;
        }

        $new_table = static::getTable();
        $iterator  = $DB->request(['FROM' => $old_table]);

        foreach ($iterator as $row) {
            if (empty($row['field']) || empty($row['language'])) {
                continue;
            }

            $existing = (new static())->find([
                'items_id' => $row['items_id'],
                'itemtype' => $row['itemtype'],
                'key'      => $row['field'],
                'language' => $row['language'],
            ]);

            if (!empty($existing)) {
                continue;
            }

            $tr = new static();
            $tr->add([
                'items_id'     => $row['items_id'],
                'itemtype'     => $row['itemtype'],
                'key'          => $row['field'],
                'language'     => $row['language'],
                'translations' => ['one' => $row['value'] ?? ''],
            ]);
        }

        $migration->dropTable($old_table);
    }

    public static function install(Migration $migration): void
    {
        // The core table glpi_itemtranslations_itemtranslations is created by GLPI core.
        // Concrete classes call migrateFromLegacyTable() if upgrading from the old system.
    }

    /**
     * Fix GLPI core bug: getTranslationsToReview() hardcodes FormTranslation.
     * We override it to use static::class instead.
     */
    public function getTranslationsToReview(): int
    {
        $translations_handlers = $this->getTranslationsHandlersForStats();
        $translations_to_review = 0;

        array_walk_recursive(
            $translations_handlers,
            function ($handler) use (&$translations_to_review) {
                $translation = new static();
                if (
                    $translation->getFromDBByCrit([
                        static::$items_id => $handler->getItem()->getID(),
                        static::$itemtype => $handler->getItem()->getType(),
                        'language'        => $this->fields['language'],
                        'key'             => $handler->getKey(),
                        'hash'            => ['!=', md5($handler->getValue() ?? '')],
                    ])
                ) {
                    if ($translation->isPossiblyObsolete()) {
                        $translations_to_review++;
                    }
                }
            }
        );

        return $translations_to_review;
    }

    public static function uninstall(): void
    {
        global $DB;

        $criteria = static::getSystemSQLCriteria();
        if (!empty($criteria)) {
            $where = array_values($criteria)[0];
            $DB->delete(static::getTable(), $where);
        }
    }
}
