<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Metademand_Resource;

$metademand_resource = new Metademand_Resource();

if (isset($_POST["update"])) {
    // This branch inserts a row, and Metademand_Resource is a plain CommonDBTM whose entities_id
    // comes from a hidden input - so checking the input against the session certified itself.
    // Authorise the parent metademand instead, and take the entity from it.
    // One id for the control and the row stored ("12.9" was checked as 12 and stored as 13)
    $_POST["plugin_metademands_metademands_id"] = Metademand::getPostedId(
        $_POST["plugin_metademands_metademands_id"] ?? null,
    );
    $metademand = new Metademand();
    $metademand->check($_POST["plugin_metademands_metademands_id"], UPDATE);
    $_POST["entities_id"] = $metademand->getEntityID();

    $metademand_resource->add($_POST);

    Html::back();
}
