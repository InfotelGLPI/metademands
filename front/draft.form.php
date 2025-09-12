<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

use GlpiPlugin\Servicecatalog\Main;

if (Plugin::isPluginActive('servicecatalog')
    && Session::getCurrentInterface() != 'central') {

    Main::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

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
