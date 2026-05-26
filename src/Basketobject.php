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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Basketobject
 */
class Basketobject extends CommonDBTM implements ProvideTranslationsInterface
{

    public $dohistory = true;
    static $rightname = "plugin_metademands";

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
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_basketobjecttypes_id` (`plugin_metademands_basketobjecttypes_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
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
    function prepareInputForAdd($input)
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
        return $input;
    }


    /**
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return __('Reference catalog', 'metademands');
    }


    /**
     * @return array
     */
    function rawSearchOptions()
    {

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2)
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
            'datatype' => 'dropdown'
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
    static function getMenuContent()
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
    static function getIcon()
    {
        return "ti ti-shopping-bag";
    }


    /**
     * @param array $options
     *
     * @return array
     */
    function defineTabs($options = [])
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
    function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);

        ob_start();
        \Dropdown::show(Basketobjecttype::class, [
            'name'  => 'plugin_metademands_basketobjecttypes_id',
            'value' => $this->fields['plugin_metademands_basketobjecttypes_id'],
        ]);
        $type_dropdown_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/basketobject_form.html.twig', [
            'action'             => Toolbox::getItemTypeFormURL(Basketobject::class),
            'item_id'            => $this->fields['id'] ?? 0,
            'is_new'             => $ID <= 0,
            'name'               => $this->fields['name'] ?? '',
            'description'        => $this->fields['description'] ?? '',
            'reference'          => $this->fields['reference'] ?? '',
            'type_name'          => Basketobjecttype::getTypeName(),
            'type_dropdown_html' => $type_dropdown_html,
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
            value: $this->fields['name'],
        );

        $handlers[$key][] = new TranslationHandler(
            item: $this,
            key: 'description',
            name: __('Description'),
            value: $this->fields['description'],
            is_rich_text: false,
        );

        return $handlers;
    }
}
