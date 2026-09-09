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

use GlpiPlugin\Metademands\BasketobjecttypeTranslation;

Session::checkRight('plugin_metademands', UPDATE);

$translation = new BasketobjecttypeTranslation();
if (isset($_POST['add'])) {
    // Same as metademandtranslation.form.php: the global right bit is not scoped by entity, so the
    // control has to be carried by the row - or by the parent, for a creation.
    $translation->check(-1, CREATE, $_POST);
    $translation->add($_POST);
} elseif (isset($_POST['update'])) {
    $translation->check((int) $_POST['id'], UPDATE);
    $translation->update($_POST);
} elseif (isset($_POST['purge'])) {
    $translation->check((int) $_POST['id'], PURGE);
    $translation->delete($_POST, 1);
}
Html::back();
