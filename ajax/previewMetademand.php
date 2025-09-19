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

use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_metademands", UPDATE);

$metademands_id = $_REQUEST['metademands_id'];
$step = Metademand::STEP_SHOW;
$current_ticket = 0;
$meta_validated = 0;
$preview = 1;
$options = [];
$seeform = 0;

Wizard::showMetademands(
    $metademands_id,
    $step,
    $current_ticket,
    $meta_validated,
    $preview,
    $options,
    $seeform,
    $_REQUEST['block']
);
