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

//For migration if needed
//include(PLUGIN_METADEMANDS_DIR . "/install/migrateFieldsOptions.php");
//migrateFieldsOptions();

use Glpi\Exception\Http\AccessDeniedHttpException;

if (Plugin::isPluginActive("metademands")) {
    if (Session::haveRight("plugin_metademands", UPDATE)) {
        Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/config.form.php");
    } else {
        throw new AccessDeniedHttpException();
    }
} else {
    Html::header(__('Setup'), '', "config", "plugin");
    echo "<div class='alert alert-important alert-warning d-flex'>";
    echo "<b>" . __('Please activate the plugin', 'metademands') . "</b></div>";
    Html::footer();
}
