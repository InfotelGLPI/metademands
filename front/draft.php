<?php

use GlpiPlugin\Servicecatalog\Main;
use GlpiPlugin\Metademands\Draft;

Session::checkLoginUser();

if (Plugin::isPluginActive('servicecatalog') && Session::getCurrentInterface() != 'central') {

    Main::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    echo "<a class='btn btn-sm btn-primary mb-3 fs-4' href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/draftcreation.php'>" . __(
            "New draft",
            'metademands'
        ) . "</a>";

    Search::show(Draft::class);

    if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
