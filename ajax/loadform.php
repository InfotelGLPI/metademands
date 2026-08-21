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
use GlpiPlugin\Metademands\Form;
use GlpiPlugin\Metademands\Form_Value;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

$metademands = new Metademand();
$wizard      = new Wizard();
$form      = new Form();

// Only the owner may load a form, except public models (is_model && !is_private)
// which are meant to be shared. Prevents IDOR on other users' private forms.
$form_id = (int) ($_POST['plugin_metademands_forms_id'] ?? 0);
if (
    !$form->getFromDB($form_id)
    || ((int) $form->fields['users_id'] !== Session::getLoginUserID()
        && !((int) $form->fields['is_model'] === 1 && (int) $form->fields['is_private'] === 0))
) {
    throw new AccessDeniedHttpException();
}

// Correlate the requested metademands_id with the loaded form (it must be the
// form's own meta-demand) and require entity access on it, before it keys any
// $_SESSION state. Prevents polluting the session of an unrelated meta-demand
// via a mismatched metademands_id.
$metademands_id = (int) ($_POST['metademands_id'] ?? 0);
if ((int) $form->fields['plugin_metademands_metademands_id'] !== $metademands_id
    || !$metademands->getFromDB($metademands_id)
    || !Session::haveAccessToEntity(
        $metademands->fields['entities_id'],
        $metademands->fields['is_recursive']
    )) {
    throw new AccessDeniedHttpException();
}

    Form_Value::loadFormValues($_POST['metademands_id'], $_POST['plugin_metademands_forms_id']);
    $form_name = $form->getField('name');

    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'] ?? 0;
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['edit_model'] = $_POST['edit_model'] ?? 0;

    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['use_model'] = 0;
    if ($form->fields['is_model'] == 1 && $form->fields['is_private'] == 0) {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['use_model'] = 1;
    }

    // Case of simple ticket convertion
    if (isset($_POST['items_id']) && $_POST['itemtype'] == 'Ticket') {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['tickets_id'] = $_POST['items_id'];
    }
    // Resources id
    if (isset($_POST['resources_id'])) {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
        // Resources step
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];
    }

    //Category id if have category field
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id']
      = (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
          && $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
    //   $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_type']                                    = $metademands->fields['type'];
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_forms_id']                  = $_POST['plugin_metademands_forms_id'];
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_forms_name']                = $form_name;

echo 0;
