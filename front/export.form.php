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

if (Session::haveRight("plugin_metademands", CREATE)) {

    if (isset($_POST["exportFormcreatorXML"])) {

        $file = PluginMetademandsExport::exportAsXMLFromFormcreator($_POST["plugin_formcreator_forms_id"]);

        $splitter = explode("/", $file, 2);
        $expires_headers = false;

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            Toolbox::sendFile($send, $splitter[1], null, $expires_headers);
            unlink($send);
        } else {
            Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
        }

    } else if (isset($_POST["exportMetademandsXML"])) {

        $file = PluginMetademandsExport::exportAsXMLForMetademands($_POST["plugin_metademands_metademands_id"]);
        $splitter = explode("/", $file, 2);
        $expires_headers = false;

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            Toolbox::sendFile($send, $splitter[1], null, $expires_headers);
            unlink($send);

        } else {
            Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
        }
    } else if (isset($_POST["exportMetademandsJSON"])) {

        $file = PluginMetademandsExport::exportAsJSONForFormcreator($_POST["plugin_metademands_metademands_id"]);
        $splitter = explode("/", $file, 2);
        $expires_headers = false;

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            Toolbox::sendFile($send, $splitter[1], 'json', $expires_headers);
            unlink($send);

        } else {
            Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
        }

    }  elseif (isset($_GET["import_form"])) {

        Html::header(PluginMetademandsMetademand::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");
        PluginMetademandsExport::showImportForm();
        Html::footer();

    } elseif (isset($_POST["import_file"])) {
        $id = PluginMetademandsExport::importXml();
        if ($id) {
            $meta = new PluginMetademandsMetademand();
            Html::redirect($meta->getFormURL() . "?id=" . $id);
        } else {
            Html::back();
        }
    }
} else {
    Html::displayRightError();
}

