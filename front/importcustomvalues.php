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

if (isset($_POST['importreplacecsv']) && isset($_POST['plugin_metademands_fields_id'])) {
    $csvMimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain'
    );


    if (!empty($_FILES['importFrm']['name']) && in_array($_FILES['importFrm']['type'], $csvMimes)) {
        if (($handle = fopen($_FILES['importFrm']['tmp_name'], "r")) !== false) {
            $rank = 0;
            $fieldcustom = new PluginMetademandsFieldCustomvalue();
            $fieldcustom->deleteByCriteria(['plugin_metademands_fields_id' => $_POST['plugin_metademands_fields_id']]);
            while (($data = fgetcsv($handle, 1000, $_SESSION["glpicsv_delimiter"])) !== false) {
                $input['name'] = Toolbox::addslashes_deep($data[0]);
                $input['is_default'] = $data[1];
                $input['comment'] = Toolbox::addslashes_deep($data[2]);
                $input['plugin_metademands_fields_id'] = $_POST['plugin_metademands_fields_id'];
                $input['rank'] = $rank;
                $rank++;
                $fieldcustom->add($input);
            }
            Session::addMessageAfterRedirect(__('Data imported successfully', 'metademands'), false, INFO);
            fclose($handle);
        } else {
            Session::addMessageAfterRedirect(__('Impossible to read the CSV file', 'metademands'), false, ERROR);
            return false;
        }
    } else {
        Session::addMessageAfterRedirect(__('Please upload a valid CSV file', 'metademands'), false, ERROR);
    }

    Html::back();
}
