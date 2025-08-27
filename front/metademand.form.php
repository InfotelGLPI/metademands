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

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$meta = new PluginMetademandsMetademand();

if (isset($_POST['apply_rule'])) {
    $meta->check($_POST['id'], UPDATE);
    $meta->update($_POST);
    Html::back();
}

// override itil_categories_id if checkbox all was checked
if (isset($_POST['itilcategories_id_all']) && $_POST['itilcategories_id_all'] == 1) {
    $_POST['itilcategories_id'] = array_keys(PluginMetademandsMetademand::getAvailableItilCategories($_POST['id']));
    unset($_POST['itilcategories_id_all']);
}

// allow a metademand to have all its categories removed
if (!isset($_POST['itilcategories_id'])) {
    $_POST['itilcategories_id'] = [];
}

if (isset($_POST["add"])) {
    $meta->check(-1, CREATE, $_POST);
    $newID = $meta->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($meta->getFormURL() . "?id=" . $newID);
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $meta->check($_POST['id'], DELETE);
    $meta->delete($_POST);
    $meta->redirectToList();
} elseif (isset($_POST["restore"])) {
    $meta->check($_POST['id'], PURGE);
    $meta->restore($_POST);
    $meta->redirectToList();
} elseif (isset($_POST["purge"])) {
    $meta->check($_POST['id'], PURGE);
    $meta->delete($_POST, 1);
    $meta->redirectToList();
} elseif (isset($_POST["update"])) {
    $meta->check($_POST['id'], UPDATE);
    $meta->update($_POST);
    Html::back();
} else {
    $meta->checkGlobal(READ);

    Html::header(PluginMetademandsMetademand::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");

    $meta->display(['id' => $_GET["id"], 'withtemplate' => $_GET["withtemplate"]]);

    echo Html::scriptBlock("
    function cacherSubblocs() {
        \$('[bloc-id^=\"subbloc\"]').each(function() {
            var subblocValue = \$(this).attr('bloc-id');
            var number = subblocValue.replace('subbloc', '');
            var blocHideIdValue = 'bloc' + number;
            \$('[bloc-hideid=\"' + blocHideIdValue + '\"]').parent().hide();
        });
    }

    \$(document).ready(function() {
        cacherSubblocs();

        const observer = new MutationObserver(function(mutationsList) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    cacherSubblocs();
                }
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
");

    Html::footer();
}
