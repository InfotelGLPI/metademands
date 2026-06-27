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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;

if (Session::haveRight("plugin_metademands", CREATE)) {

    if (isset($_POST["exportFormGLPIXML"])) {

        $file = Export::exportAsXMLFromGLPI($_POST["forms_id"]);

        $splitter = explode("/", $file, 2);
        $expires_headers = false;

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            return Toolbox::getFileAsResponse($send, $splitter[1], 'xml', $expires_headers);
        } else {
            throw new AccessDeniedHttpException();
        }

    } elseif (isset($_POST["exportMetademandsXML"])) {

        $file = Export::exportAsXMLForMetademands($_POST["plugin_metademands_metademands_id"]);
        $splitter = explode("/", $file, 2);
        $expires_headers = false;

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            return Toolbox::getFileAsResponse($send, $splitter[1], 'xml', $expires_headers);
        } else {
            throw new AccessDeniedHttpException();
        }
    } elseif (isset($_POST["exportMetademandsJSON"])) {

        $file = Export::exportAsJSONForGLPIForm($_POST["plugin_metademands_metademands_id"]);
        $splitter = explode("/", $file, 2);
        $expires_headers = false;

        if ($splitter[0] == "_plugins") {
            $send = GLPI_PLUGIN_DOC_DIR . '/' . $splitter[1];
        }

        if ($send && file_exists($send)) {
            return Toolbox::getFileAsResponse($send, $splitter[1], 'json', $expires_headers);
        } else {
            throw new AccessDeniedHttpException();
        }

    } elseif (isset($_GET["import_form"])) {

        Html::header(Metademand::getTypeName(2), '', "helpdesk", Menu::class);
        Export::showImportForm();
        Html::footer();

    } elseif (isset($_POST["import_file"])) {
        $id = Export::importXml();
        if ($id) {
            $meta = new Metademand();
            Html::redirect($meta->getFormURL() . "?id=" . $id);
        } else {
            Html::back();
        }
    }
} else {
    throw new AccessDeniedHttpException();
}
