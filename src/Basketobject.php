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

use CommonDBTM;
use DBConnection;
use GlpiPlugin\Orderfollowup\Material;
use Glpi\Application\View\TemplateRenderer;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Html;
use Migration;
use Plugin;
use Session;
use Toolbox;
use PluginOrdermaterialMaterial;

/**
 * Class Basketobject
 */
class Basketobject extends CommonDBTM implements ProvideTranslationsInterface
{
    public bool $dohistory = true;
    public static string $rightname = "plugin_metademands";

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
                        `name`                                    varchar(255) collate utf8mb4_unicode_ci default NULL,
                        `description`                             longtext,
                        `reference`                               varchar(255) collate utf8mb4_unicode_ci,
                        `plugin_metademands_basketobjecttypes_id` int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `rank`                                    int                    NOT NULL                   DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_basketobjecttypes_id` (`plugin_metademands_basketobjecttypes_id`),
                        KEY `rank` (`rank`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.7.2
        // Display order of the references in the basket (Fields\Basket::showField())
        if (!$DB->fieldExists($table, 'rank')) {
            $migration->addField($table, 'rank', "int NOT NULL DEFAULT '0'");
            $migration->addKey($table, 'rank');
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        $material = new Basketobject();
        if ($material->getFromDBByCrit(['reference' => $input['reference']])) {
            Session::addMessageAfterRedirect(__('This reference already exists', 'metademands'), false, ERROR);
            return false;
        }

        if (empty($input['name'])) {
            Session::addMessageAfterRedirect(__("The designation is mandatory", "metademands"), false, ERROR);
            return false;
        }

        if (empty($input['reference'])) {
            Session::addMessageAfterRedirect(__("Reference is mandatory", "metademands"), false, ERROR);
            return false;
        }

        if (!isset($input['plugin_metademands_basketobjecttypes_id'])
            || $input['plugin_metademands_basketobjecttypes_id'] == 0) {
            Session::addMessageAfterRedirect(__("The object type is mandatory", "metademands"), false, ERROR);
            return false;
        }
        return self::normalizeRank($input);
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForUpdate($input)
    {
        return self::normalizeRank($input);
    }

    /**
     * References of a type in their display order, as the basket lists them
     * (Fields\Basket::showWizardField()).
     *
     * @return array<int, array> rows by id
     */
    public static function getOrderedOfType(int $types_id): array
    {
        return (new self())->find(
            ['plugin_metademands_basketobjecttypes_id' => $types_id],
            ['rank', 'name', 'description'],
        );
    }

    /**
     * Number the references of a type in the order given, from 1: the order of the
     * basket field options (Fields\Basket::showFieldCustomValues()).
     *
     * @param int   $types_id type the references belong to
     * @param int[] $ids      references, in their new order
     *
     * @return bool false when one of them may not be updated or is not of this type
     */
    public static function reorder(int $types_id, array $ids): bool
    {
        $of_type = self::getOrderedOfType($types_id);

        $ok   = true;
        $rank = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            $reference = new self();
            if (!isset($of_type[$id]) || !$reference->can($id, UPDATE)) {
                $ok = false;
                continue;
            }
            $rank++;
            if ((int) $reference->fields['rank'] !== $rank) {
                $ok = $reference->update(['id' => $id, 'rank' => $rank]) && $ok;
            }
        }

        return $ok;
    }

    /**
     * The display order is a positive integer: an empty or invalid value puts the
     * reference first, with the others not ordered yet.
     */
    private static function normalizeRank(array $input): array
    {
        if (array_key_exists('rank', $input)) {
            $input['rank'] = max(0, (int) $input['rank']);
        }

        return $input;
    }


    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Reference catalog', 'metademands');
    }


    /**
     * @return array
     */
    public function rawSearchOptions()
    {

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Designation', 'metademands'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => 3,
            'table' => $this->getTable(),
            'field' => 'description',
            'name' => __('Description'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id' => 4,
            'table' => $this->getTable(),
            'field' => 'reference',
            'name' => __('Reference', 'metademands'),
            'datatype' => 'text',
        ];


        $tab[] = [
            'id' => 7,
            'table' => Basketobjecttype::getTable(),
            'field' => 'name',
            'name' => Basketobjecttype::getTypeName(),
            'datatype' => 'dropdown',
        ];

        // Editable from the list (massive action): reorder several references at once
        $tab[] = [
            'id' => 8,
            'table' => $this->getTable(),
            'field' => 'rank',
            'name' => __('Display order', 'metademands'),
            'datatype' => 'number',
            'min' => 0,
            'max' => 9999,
            'step' => 1,
        ];

        if (Plugin::isPluginActive("ordermaterial")) {
            $tab = array_merge($tab, PluginOrdermaterialMaterial::rawSearchOptionsToAdd());
        }
        if (Plugin::isPluginActive("orderfollowup")) {
            $tab = array_merge($tab, Material::rawSearchOptionsToAdd());
        }
        return $tab;
    }


    /**
     * get menu content
     *
     * @return array array for menu
     **@since version 0.85
     *
     */
    public static function getMenuContent()
    {
        $menu = [];

        $menu['title'] = self::getMenuName();
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);
        if (self::canCreate()) {
            $menu['links']['add'] = self::getFormURL(false);
        }
        $menu['icon'] = self::getIcon();
        return $menu;
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return "ti ti-shopping-bag";
    }


    /**
     * @param array $options
     *
     * @return array
     */
    public function defineTabs($options = [])
    {

        $ong = parent::defineTabs($options);
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }


    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     */
    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);

        TemplateRenderer::getInstance()->display('@metademands/basketobject_form.html.twig', [
            'action'             => Toolbox::getItemTypeFormURL(Basketobject::class),
            'item_id'            => $this->fields['id'] ?? 0,
            'is_new'             => $ID <= 0,
            'name'               => $this->fields['name'] ?? '',
            'description'        => $this->fields['description'] ?? '',
            'reference'          => $this->fields['reference'] ?? '',
            'rank'               => (int) ($this->fields['rank'] ?? 0),
            'type_name'          => Basketobjecttype::getTypeName(),
            'type_itemtype'      => Basketobjecttype::class,
            'types_id'           => $this->fields['plugin_metademands_basketobjecttypes_id'],
            // For the POST_ITEM_FORM hook of the other plugins (see the template)
            'item'               => $this,
            'params'             => $options,
        ]);

        return true;
    }

    public function listTranslationsHandlers(): array
    {
        $key = sprintf('%s_%d', static::getType(), $this->getID());
        $handlers = [];

        $handlers[$key][] = new TranslationHandler(
            item: $this,
            key: 'name',
            name: __('Designation', 'metademands'),
            value: $this->fields['name'] ?? '',
        );

        $handlers[$key][] = new TranslationHandler(
            item: $this,
            key: 'description',
            name: __('Description'),
            value: $this->fields['description'] ?? '',
            is_rich_text: false,
        );

        return $handlers;
    }
}
