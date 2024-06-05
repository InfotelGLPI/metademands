<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

use Glpi\Event;

if (Plugin::isPluginActive('servicecatalog') && Session::getCurrentInterface() != 'central') {

    PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    $draft_id = 0;

    if (isset($_GET['id'])) {
        $draft_id = $_GET['id'];
    }

    if ($draft_id > 0) {

        $datas = PluginMetademandsDraft::loadDatasDraft($draft_id);
        PluginMetademandsDraft::showDraft($datas);

    } else {
        echo __(
            'No draft available for this form',
            'metademands'
        );
    }

    if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
