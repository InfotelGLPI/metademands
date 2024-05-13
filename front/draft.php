<?php

include('../../../inc/includes.php');

//Session::checkCentralAccess();

$orderfollowup_draft = new PluginOrderfollowupDraft();

Html::Header($orderfollowup_draft::getTypeName(),"", "Tools", $orderfollowup_draft::getType());

$params = [
    'start' => 0,      // start with first item (index 0)
    'criteria' => [
        [
            'field' => 4,        // field index in search options
            'searchtype' => 'equals',  // type of search
            'value' => Session::getLoginUserID(),         // value to search
        ],
    ],
];

Search::showList(PluginMetademandsDraft::getType(),$params);

Html::Footer();