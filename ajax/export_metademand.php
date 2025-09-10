<?php
/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2024 by the Metademands Development Team.

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

use Glpi\Exception\Http\BadRequestHttpException;

include('../../../inc/includes.php');

if (isset($_POST["action"])
    && isset($_POST["metademands"])
    && is_array($_POST["metademands"])
    && Session::haveRight("plugin_metademands", CREATE)) {
    $old_memory = ini_set("memory_limit", "-1");
    $old_execution = ini_set("max_execution_time", "0");

    $files = [];
    foreach ($_POST["metademands"] as $id) {

        if ($_POST["action"] == "exportXML") {
            $file = PluginMetademandsExport::exportAsXMLForMetademands($id);
        } else {
            $file = PluginMetademandsExport::exportAsJSONForFormcreator($id);
        }

        $splitter = explode("/", $file, 2);

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            $files[] = $send;
        } else {
            ini_set("memory_limit", $old_memory);
            ini_set("max_execution_time", $old_execution);
            throw new BadRequestHttpException(__('Unauthorized access to this file'));
        }
    }

    $zip = new ZipArchive;
    $filename = '/metademands/export_' . date('Y-m-d') . '.zip';
    $fullZip = GLPI_PLUGIN_DOC_DIR . $filename;
    if ($zip->open($fullZip, ZipArchive::CREATE)) {
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        foreach ($files as $file) {
            unlink($file);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=export_' . date('Y-m-d') . '.zip');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullZip));

        readfile($fullZip);
        unlink($fullZip);

        ini_set("memory_limit", $old_memory);
        ini_set("max_execution_time", $old_execution);
    } else {
        Session::addMessageAfterRedirect(
            __('Error when creating export archive', 'metademands'),
            false,
            ERROR
        );
        ini_set("memory_limit", $old_memory);
        ini_set("max_execution_time", $old_execution);
        Html::back();
    }
} else {
    Html::displayRightError();
}
