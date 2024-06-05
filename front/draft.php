<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

if (Plugin::isPluginActive('servicecatalog') && Session::getCurrentInterface() != 'central') {
    PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    echo "<a class='btn btn-sm btn-primary mb-3 fs-4' href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/draftcreation.php'>" . __(
            "New draft",
            'metademands'
        ) . "</a>";

    Search::show(PluginMetademandsDraft::getType());

    if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
