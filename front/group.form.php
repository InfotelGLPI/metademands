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

use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\GroupConfig;

Session::checkLoginUser();

$group = new Group();

if (isset($_POST["add_groups"])) {
    if (isset($_POST['groups_id'])) {
        $group->check(-1, UPDATE, $_POST);
        //add groups
        foreach ($_POST['groups_id'] as $groups_id) {
            $group->add(['groups_id' => $groups_id,
                'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]);
        }
    }
    if (isset($_POST['regex_value']) && !empty($_POST['regex_value'])) {
        $grp = new Group();
        $groups = $grp->find();
        foreach ($groups as $g) {
            $res = preg_match($_POST['regex_value'], $g['name']) == 1;
            if ($res) {
                $group->add(['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'], 'groups_id' => $g['id']]);
            }
        }
    }
    Html::back();
} else if (isset($_POST["define_visibility"])) {

    $groupconfig = new GroupConfig();
    if (!$groupconfig->getFromDBByCrit(['plugin_metademands_metademands_id'=> $_POST['plugin_metademands_metademands_id']])) {
        $groupconfig->add(['visibility' => $_POST['visibility'],
            'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]);
    } else {
        $id = $groupconfig->getID();
        $groupconfig->update(['id' => $id,
            'visibility' => $_POST['visibility'],
            'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]);
    }
    Html::back();
}
