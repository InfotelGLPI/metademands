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
use GlpiPlugin\Metademands\Step;

if (strpos($_SERVER['PHP_SELF'], "getNextMessage.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
} elseif (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Session::checkLoginUser();

$metademands = new Metademand();
$metademands->getFromDB((int) $_POST['plugin_metademands_metademands_id']);
$block_id = (int) $_POST['block_id'];

if ($metademands->fields['step_by_step_mode'] == 1
) {
    $submitmsg = $submitstepmsg =  __('Your form will be redirected to another group of people who will complete the following information.', 'metademands');

    $msg = Step::getMsgForNextBlock($metademands->getID(), $block_id);
    if ($msg) {
        $submitmsg = $msg;
        $submitstepmsg = $msg;
        // The step message is a plain-text field (see step_form.html.twig, enable_richtext=false)
        // configured by a form manager and shown to end users: escape it to prevent stored XSS.
        echo htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8');
    } else {
        echo "";
    }
}
