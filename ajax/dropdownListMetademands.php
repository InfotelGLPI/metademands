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

use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["step"])) {
   switch ($_POST["step"]) {
      case 'metademands':
         $metademands = new Metademand();
         $wizard = new Wizard();

         // NOTE: listMetademands() builds a fixed $DB->request() query and does not honor any
         // 'condition' parameter, so no raw SQL fragment is built here (avoids a latent SQL
         // injection vector). The 'family' filter is currently not applied by this listing.
         $data = $metademands->listMetademands(false, []);
         $data[0] = \Dropdown::EMPTY_VALUE;
         ksort($data);
         \Dropdown::showFromArray('metademands_id', $data, ['width' => 150]);
         break;
   }
}
