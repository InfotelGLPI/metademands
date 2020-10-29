<?php

/*
 -------------------------------------------------------------------------
 Servicecatalog plugin for GLPI
 Copyright (C) 2003-2019 by the Servicecatalog Development Team.

 https://forge.indepnet.net/projects/servicecatalog
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Servicecatalog.

 Servicecatalog is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Servicecatalog is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Servicecatalog. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

$translation = new PluginMetademandsFieldTranslation();
if (isset($_POST['add'])) {
   $translation->add($_POST);
} else if (isset($_POST['update'])) {
   $translation->update($_POST);
} else if (isset($_POST['purge'])) {
   $translation->delete($_POST, 1);
}
Html::back();