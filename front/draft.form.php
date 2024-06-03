<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

use Glpi\Event;

//Protection in case, we don't find draft id
if (isset($_POST['metademands_id'])) {
    $draft_id = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_drafts_id'];
    $_SESSION['last_draft'] = $draft_id;
}elseif(isset($_REQUEST['id'])) {
    $draft_id = $_REQUEST['id'];
    $_SESSION['last_draft'] = $draft_id;
}else{
    $draft_id =  $_SESSION['last_draft'];
}

if (Session::getCurrentInterface() == 'central') {
    Html::Header(PluginOrderfollowupDraft::getTypeName(),"", "Tools", PluginOrderfollowupDraft::getType());
}

else {
    if (Plugin::isPluginActive('servicecatalog')) {
        PluginServicecatalogMain::showDefaultHeaderHelpdesk(PluginOrderfollowupDraft::getTypeName(2));
    } else {
        Html::helpHeader(PluginOrderfollowupDraft::getTypeName(2));
    }
}


$datas = PluginOrderfollowupDraft::loadDatasDraft($draft_id);
PluginOrderfollowupDraft::showDraft($datas);

Html::helpFooter();