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

use CommonGLPI;
use DbUtils;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Class Servicecatalog
 */
class Servicecatalog extends CommonGLPI
{
    public static $rightname = 'plugin_metademands';

    public $dohistory = false;

    /**
     * @return bool
     * @throws \GlpitestSQLError
     */
    public static function canUse()
    {
        $config = new Config();
        $config->getFromDB(1);
        if ($config->getField('display_buttonlist_servicecatalog') == 0) {
            return false;
        }
        $metademands = Wizard::selectMetademands(true);
        return (Session::haveRight(self::$rightname, READ) && (count($metademands) > 0));
    }

    /**
     * @return string
     */
    public static function getMenuTitle()
    {
        $config = new Config();
        $config->getFromDB(1);
        if (!empty($config->getField('title_servicecatalog'))) {
            return $config->getField('title_servicecatalog');
        }
        return __('Create a', 'servicecatalog') . " " . __('advanced request', 'metademands');
    }

    /**
     * @return string
     */
    public static function getMenuLink()
    {
        global $CFG_GLPI;

        return PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=" . Metademand::STEP_INIT;
    }

    /**
     * @return string
     */
    public static function getNavBarLink()
    {
        global $CFG_GLPI;

        return PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=" . Metademand::STEP_INIT;
    }

    /**
     * @return string
     */
    public static function getMenuLogo()
    {
        $config = new Config();
        $config->getFromDB(1);
        if (!empty($config->getField('fa_servicecatalog'))) {
            return $config->getField('fa_servicecatalog');
        }
        return Metademand::getIcon();
    }


    /**
     * @return string
     * @throws \GlpitestSQLError
     */
    public static function getMenuComment()
    {
        $config = new Config();
        $config->getFromDB(1);
        if (!empty($config->getField('comment_servicecatalog'))) {
            return $config->getField('comment_servicecatalog');
        }

        $list        = "";
        $metademands = Wizard::selectMetademands(true, " LIMIT 3");

        foreach ($metademands as $id => $name) {
            $list .= $name . '<br>';
        }
        $list .= "(...)";
        return $list;
    }

    /**
     * @return string
     */
    public static function getLinkList()
    {
        //      return __('Select the advanced request', 'metademands');
    }

    /**
     * @param $type
     * @param $category_id
     *
     * @return string or bool
     */
    public static function getLinkURL($type, $category_id)
    {

        $dbu   = new DbUtils();
        $metas = $dbu->getAllDataFromTable(
            'glpi_plugin_metademands_metademands',
            ["`is_active`"         => 1,
                "`is_deleted`"         => 0,
                "`type`"              => $type]
        );
        $cats       = [];

        foreach ($metas as $meta) {
            $categories = [];
            if (isset($meta['itilcategories_id'])) {
                if (is_array(json_decode($meta['itilcategories_id'], true))) {
                    $categories = $meta['itilcategories_id'];
                } else {
                    $array      = [$meta['itilcategories_id']];
                    $categories = json_encode($array);
                }
            }
            $cats[$meta['id']] = json_decode($categories);
        }

        $meta_concerned = 0;
        foreach ($cats as $meta => $meta_cats) {
            if (in_array($category_id, $meta_cats)) {
                $meta_concerned = $meta;
            }
        }

        if (!empty($meta_concerned)) {
            //         $meta = reset($metas);
            //Redirect if not linked to a resource contract type
            if (!$dbu->countElementsInTable(
                "glpi_plugin_metademands_metademands_resources",
                ["plugin_metademands_metademands_id" => $meta_concerned]
            )) {
                return PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?itilcategories_id=" . $category_id . "&metademands_id=" . $meta_concerned . "&tickets_id=0&step=" . Metademand::STEP_SHOW;
            }
        }
        return false;
    }

    public static function getList() {}
}
