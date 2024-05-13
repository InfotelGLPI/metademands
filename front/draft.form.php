<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

use Glpi\Event;

if (!isset($_GET["id"])) {
    $_GET["id"] = 0;
}

Html::Header(PluginMetademandsDraft::getTypeName(),"", "Tools", PluginMetademandsDraft::getType());


$wizard = new PluginMetademandsWizard();

$menus = ["tools", PluginMetademandsDraft::getType()];

$options = ['step' => 2,
    'metademands_id' => 7,
    'preview' => false,
    'tickets_id' => '',
    'ancestor_tickets_id' => '',
    'resources_id' => '',
    'resources_step' => '',
    'itilcategories_id' => 0];


//$wizard->showWizard($options);

$datas = PluginOrderfollowupDraft::loadDatasDraft($_REQUEST['id']);

PluginOrderfollowupDraft::showDraft($datas);

//PluginMetademandsDraft::displayFullPageForItem($_REQUEST['id'] ?? 0, $menus, $_REQUEST);

Html::helpFooter();