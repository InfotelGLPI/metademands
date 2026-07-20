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

use GlpiPlugin\Metademands\Configstep;

Session::checkLoginUser();

Session::checkRight('plugin_metademands', UPDATE);

$stepConfig = new Configstep();

if (isset($_POST['update_configstep']) && isset($_POST['plugin_metademands_metademands_id'])) {
    $res = $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]);
    $input = $_POST;
    if ($res) {
        $input['id'] = $stepConfig->fields['id'];
        $stepConfig->update($input);
    } else {
        $stepConfig->add($input);
    }
    Html::back();

}
