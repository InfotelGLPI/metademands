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

/**
 * Class PluginMetademandsMenu
 */
class PluginMetademandsMenu extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    /**
     * @return translated
     */
    public static function getMenuName()
    {
        return _n('Meta-Demand', 'Meta-Demands', 2, 'metademands');
    }

    /**
     * @return array
     */
    public static function getMenuContent()
    {
        $menu                    = [];
        $menu['title']           = self::getMenuName();
//        $menu['links']['lists']  = "";
        if (PluginMetademandsMetademand::canCreate()) {
            $menu['page']            = PluginMetademandsMetademand::getSearchURL(false);
            $menu['links']['search'] = PluginMetademandsMetademand::getSearchURL(false);
            $image                 = "<i class='ti ti-share' title='" . __('Create a metademand', 'metademands') . "'></i>&nbsp;".__('Create a metademand', 'metademands');
            $menu['links'][$image] = PluginMetademandsWizard::getFormURL(false);

        } else if(Session::haveRight('plugin_metademands_createmeta', READ)) {
            $menu['page']            = PluginMetademandsWizard::getFormURL(false);
            $image                 = "<i class='ti ti-share' title='" . __('Create a metademand', 'metademands') . "'></i>&nbsp;".__('Create a metademand', 'metademands');
            $menu['links'][$image] = PluginMetademandsWizard::getFormURL(false);
        }

        if (PluginMetademandsMetademand::canCreate()) {
            $menu['links']['add'] = PLUGIN_METADEMANDS_WEBDIR_NOFULL.'/front/setup.templates.php?add=1';
        }
        if (Session::haveRight("config", UPDATE)) {
            //Entry icon in breadcrumb
            $menu['links']['config'] = PluginMetademandsConfig::getFormURL(false);
        }

        if (PluginMetademandsMetademand::canCreate()) {
            $menu['links']['template'] = PLUGIN_METADEMANDS_WEBDIR_NOFULL.'/front/setup.templates.php?add=0';
            $image                 = "<i class='ti ti-upload' title='" . __('Import metademands', 'metademands') . "'></i>&nbsp;" . __('Import metademands', 'metademands');
            $menu['links'][$image] = PluginMetademandsMetademand::getFormURL(false) . "?import_form=1";
        }

        if (PluginMetademandsMetademand::canCreate()) {
            $image                 = "<i class='ti ti-edit' title='" . __('Continue metademand', 'metademands') . "'></i>&nbsp;" . __('Continue metademand', 'metademands');
            $menu['links'][$image] = PluginMetademandsStepform::getSearchURL(false);
        }

        $menu['icon'] = self::getIcon();

        return $menu;
    }

    public static function getIcon()
    {
        return "ti ti-share";
    }

    public static function removeRightsFromSession()
    {
        if (isset($_SESSION['glpimenu']['helpdesk']['types']['PluginMetademandsMenu'])) {
            unset($_SESSION['glpimenu']['helpdesk']['types']['PluginMetademandsMenu']);
        }
        if (isset($_SESSION['glpimenu']['helpdesk']['content']['pluginmetademandsmenu'])) {
            unset($_SESSION['glpimenu']['helpdesk']['content']['pluginmetademandsmenu']);
        }
    }
}
