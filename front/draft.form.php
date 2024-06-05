<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

use Glpi\Event;

if (Plugin::isPluginActive('servicecatalog') && Session::getCurrentInterface() != 'central') {

    PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    //Protection in case, we don't find draft id
    if (isset($_POST['metademands_id'])) {
        $draft_id = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_drafts_id'];
        $_SESSION['last_draft'] = $draft_id;
    } elseif (isset($_REQUEST['id'])) {
        $draft_id = $_REQUEST['id'];
        $_SESSION['last_draft'] = $draft_id;
    } else {
        $draft_id = $_SESSION['last_draft'];
    }

    $datas = PluginMetademandsDraft::loadDatasDraft($draft_id);
    PluginMetademandsDraft::showDraft($datas);

    if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
