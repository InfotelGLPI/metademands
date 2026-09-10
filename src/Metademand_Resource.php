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
use CommonGLPI;
use DBConnection;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Resources\Config;
use GlpiPlugin\Resources\ContractType;
use GlpiPlugin\Resources\Resource;
use GlpiPlugin\Resources\Service;
use Html;
use Migration;
use Session;
use Toolbox;
use UserCategory;

/**
 * Class Metademand_Resource
 */
class Metademand_Resource extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Link with a metademand', 'Link with metademands', $nb, 'metademands');
    }

    /**
     * @return bool|int
     */
    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
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
                        `plugin_resources_contracttypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `entities_id` (`entities_id`),
                        KEY `plugin_resources_contracttypes_id` (`plugin_resources_contracttypes_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.3.0
        if (!isIndex($table, "entities_id")) {
            $migration->addKey($table, "entities_id");
        }
        if (!isIndex($table, "plugin_resources_contracttypes_id")) {
            $migration->addKey($table, "plugin_resources_contracttypes_id");
        }
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
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
     * @param int        $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item->getType() == ContractType::class) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        self::getTypeName(),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["plugin_resources_contracttypes_id" => $item->getID()],
                        ),
                    );
                }
                return self::createTabEntry(self::getTypeName());
            }
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-share";
    }

    /**
     * Display content for each users
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $metademand_resource = new self();

        if ($item->getType() == ContractType::class) {
            $metademand_resource->showPluginForResource($item);
        }
        return true;
    }


    /**
     * @param $resourceContractType
     *
     * @return bool
     */
    public function showPluginForResource($resourceContractType)
    {

        if (!$this->canView()) {
            return false;
        }
        if (!$this->canCreate()) {
            return false;
        }

        $used_data = [];
        $data      = $this->getDataForResourceContractType($resourceContractType->fields['id'], ['entities_id' => $_SESSION['glpiactiveentities']]);
        if ($data) {
            foreach ($data as $field) {
                $used_data[] = $field['plugin_metademands_metademands_id'];
            }
        }
        $canedit = $this->canCreate();
        if ($canedit) {
            ob_start();
            Dropdown::show(Metademand::class, ['name'   => 'plugin_metademands_metademands_id',
                'used'   => $used_data,
                'entity' => $_SESSION['glpiactive_entity']]);
            $metademand_dropdown = ob_get_clean();

            TemplateRenderer::getInstance()->display('@metademands/forms/metademand_resource_form.html.twig', [
                'form_action'          => Toolbox::getItemTypeFormURL(Metademand_Resource::class),
                'resource_type_name'   => self::getTypeName(1),
                'metademand_type_name' => Metademand::getTypeName(1),
                'metademand_dropdown'  => $metademand_dropdown,
                'submit_html'          => Html::submit(_sx('button', 'Add'), ['name' => 'update', 'class' => 'btn btn-primary']),
                'hidden_entities_id'   => Html::hidden('entities_id', ['value' => $_SESSION['glpiactive_entity']]),
                'hidden_contracttype'  => Html::hidden('plugin_resources_contracttypes_id', ['value' => $resourceContractType->fields['id']]),
            ]);
        }

        $this->listItems($data, $canedit);
    }

    /**
     * @param $fields
     * @param $canedit
     */
    private function listItems($fields, $canedit)
    {
        if (empty($fields)) {
            return;
        }

        $entries = [];
        foreach ($fields as $field) {
            $entries[] = [
                'id'              => $field['id'],
                'metademand_name' => Dropdown::getDropdownName(
                    'glpi_plugin_metademands_metademands',
                    $field['plugin_metademands_metademands_id'],
                ),
                'entity_name'     => Dropdown::getDropdownName('glpi_entities', $field['entities_id']),
            ];
        }

        TemplateRenderer::getInstance()->display('@metademands/forms/metademand_resource_list.html.twig', [
            'itemtype'       => self::class,
            'mass_container' => 'massResources' . mt_rand(),
            'canedit'        => $canedit,
            'entries'        => $entries,
        ]);
    }

    /**
     * @param Resource $resources
     */
    public static function redirectFormForResource(Resource $resources)
    {

        $metademand_resource = new self();
        $resources_step      = $resources->fields['resources_step'];

        if (isset($resources->fields["plugin_resources_resources_id"])
            && !empty($resources->fields["plugin_resources_resources_id"])) {
            $resources->getFromDB($resources->fields["plugin_resources_resources_id"]);
        }

        if (!empty($resources->fields["plugin_resources_contracttypes_id"])
            && $resources->fields["is_template"] != 1) {
            $data = $metademand_resource->getDataForResourceContractType($resources->fields['plugin_resources_contracttypes_id'], ['entities_id' => $_SESSION['glpiactive_entity']]);
            $data = array_shift($data);
            if (!empty($data["plugin_metademands_metademands_id"])) {
                Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $data["plugin_metademands_metademands_id"] . "&resources_id=" . $resources->fields['id'] . "&resources_step=" . $resources_step . "&step=2");
            }
        }
    }

    /**
     * @param        $resourceContractType_id
     * @param array  $condition
     *
     * @return array
     */
    public function getDataForResourceContractType($resourceContractType_id, $condition = [])
    {
        $cond = ['plugin_resources_contracttypes_id' => $resourceContractType_id]
                + $condition;
        $data = $this->find($cond);
        return $data;
    }

    public static function getTableResource($options)
    {
        $resource = new Resource();
        // The identifier reaches us straight from the caller's options, so refuse a resource the
        // session may not read instead of recomposing its data into the fragment. An unknown or
        // out-of-scope identifier simply contributes nothing.
        if (!$resource->getFromDB($options['resources_id'] ?? 0)) {
            return '';
        }
        if (!Session::haveAccessToEntity(
            $resource->fields['entities_id'] ?? 0,
            $resource->fields['is_recursive'] ?? 0,
        )) {
            return '';
        }

        $contractype = new ContractType();
        $contractype->getFromDB($resource->fields['plugin_resources_contracttypes_id']);
        $config = new Config();
        if ($config->useServiceDepartmentAD()) {
            $userCat = new UserCategory();
            $userCat->getFromDB($resource->fields['plugin_resources_services_id']);
            $service = $userCat->getField('name');
        } else {
            $service = new Service();
            $service->getFromDB($resource->fields['plugin_resources_services_id']);
            $service = $service->getField('name');
        }

        $secondary_services = [];
        if ($config->useSecondaryService() && $config->useServiceDepartmentAD()) {
            foreach ((array) json_decode((string) $resource->fields['secondary_services']) as $srvID) {
                $userCat = new UserCategory();
                $userCat->getFromDB($srvID);
                $secondary_services[] = $userCat->getField('name');
            }
        }

        // Rendered through Twig: every column below is stored raw since GLPI 10 and this
        // fragment used to be concatenated by hand, so a resource name or comment holding
        // markup was injected verbatim into whatever page displayed it.
        return TemplateRenderer::getInstance()->render('@metademands/resource_table.html.twig', [
            'show_table'         => !($options['hideTable'] ?? false),
            'title'              => trim($resource->fields['name'] . ' ' . $resource->fields['firstname']),
            'firstname'          => $resource->fields['firstname'],
            'name'               => $resource->fields['name'],
            'contract_type'      => $contractype->getField('name'),
            'service'            => $service,
            'secondary_services' => $secondary_services,
            'date_begin'         => Html::convDate($resource->fields['date_begin']),
            'date_end'           => Html::convDate($resource->fields['date_end']),
            'manager_name'       => getUserName($resource->fields['users_id'], 0, true),
            'comment'            => $resource->fields['comment'],
        ]);
    }

}
