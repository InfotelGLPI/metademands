<?php

use GlpiPlugin\Metademands\Draft;

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

        $datas = Draft::loadDatasDraft($draft_id);
        Draft::showDraft($datas);

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
