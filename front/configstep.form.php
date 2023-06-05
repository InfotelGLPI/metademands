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

include('../../../inc/includes.php');
Session::checkLoginUser();

$stepConfig = new PluginMetademandsConfigStep();

if (isset($_POST['update_configstep']) && isset($_POST['plugin_metademands_metademands_id'])) {
    $res = $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]);
    $input = [
        'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'],
        'link_user_block' => $_POST['link_user_block'],
        'multiple_link_groups_blocks' => $_POST['multiple_link_groups_blocks'],
        'add_user_as_requester' => $_POST['add_user_as_requester']
    ];
    if ($res) {
        $input['id'] = $stepConfig->fields['id'];
        $stepConfig->update($input);
    } else {
        $stepConfig->add($input);
    }
    Html::back();

}