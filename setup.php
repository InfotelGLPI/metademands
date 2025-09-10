<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Form\MetademandProvider;
use function Safe\define;
use Glpi\Plugin\Hooks;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Form\ServiceCatalog\HomeSearchManager;

define('PLUGIN_METADEMANDS_VERSION', '3.5.0');

global $CFG_GLPI;


if (!defined("PLUGIN_METADEMANDS_DIR")) {
    define("PLUGIN_METADEMANDS_DIR", Plugin::getPhpDir("metademands"));
//    define("PLUGIN_METADEMANDS_WEBDIR", Plugin::getPhpDir("metademands", false));
    $root = $CFG_GLPI['root_doc'] . '/plugins/metademands';
    define("PLUGIN_METADEMANDS_WEBDIR", $root);
}

include_once PLUGIN_METADEMANDS_DIR . "/vendor/autoload.php";

// Init the hooks of the plugins -Needed
function plugin_init_metademands()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    // Register custom service catalog content provider
    $service_catalog_manager = ServiceCatalogManager::getInstance();
    $service_catalog_manager->registerPluginProvider(new MetademandProvider());
    // Register custom home page search provider
    $home_manager = HomeSearchManager::getInstance();
    $home_manager->registerPluginProvider(new MetademandProvider());

    include_once(PLUGIN_METADEMANDS_DIR . "/inc/autoload.php");
    $autoloader = new PluginMetademandsAutoloader();
    $autoloader->register();

    $PLUGIN_HOOKS['csrf_compliant']['metademands'] = true;
    $PLUGIN_HOOKS['change_profile']['metademands'] = ['PluginMetademandsProfile', 'initProfile'];
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['metademands'] = ['scripts/metademands.js'];
    //    $PLUGIN_HOOKS["javascript"]['metademands'] = [PLUGIN_METADEMANDS_WEBDIR . "/scripts/metademands.js"];
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['metademands'] = ['css/metademands.css'];
    //    $PLUGIN_HOOKS['add_css']['metademands'] = ['css/range.scss'];
    // add minidashboard
    $PLUGIN_HOOKS['dashboard_cards']['metademands'] = 'plugin_metademands_hook_dashboard_cards';

    $PLUGIN_HOOKS['use_massive_action']['metademands'] = 1;
    $_SESSION["glpi_plugin_metademands_loaded"] = 0;

    if (Session::getLoginUserID()) {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['metademands'][] = 'lib/fuse.js';
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['metademands'][] = 'lib/fuzzysearch.js.php';
        //        $PLUGIN_HOOKS["javascript"]['metademands'] = [PLUGIN_METADEMANDS_WEBDIR . "/lib/fuse.js"];
        //        $PLUGIN_HOOKS["javascript"]['metademands'] = [PLUGIN_METADEMANDS_WEBDIR . "/lib/fuzzysearch.js.php"];

        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['metademands'][] = "lib/redips/redips-drag.min.js";
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['metademands'][] = "scripts/plugin_metademands_drag-field-row.js";
        }

        Plugin::registerClass('PluginMetademandsMetademand', ['addtabon' => 'Ticket']);
        Plugin::registerClass('PluginMetademandsForm', ['addtabon' => ['Ticket', 'Problem', 'Change', 'User']]);
        Plugin::registerClass('PluginMetademandsProfile', ['addtabon' => 'Profile']);
        Plugin::registerClass('PluginMetademandsMetademand_Resource', ['addtabon' => 'PluginResourcesContractType']);

        Plugin::registerClass(
            'PluginMetademandsBasketobjectTranslation',
            ['addtabon' => ['PluginMetademandsBasketobject']]
        );
        Plugin::registerClass(
            'PluginMetademandsBasketobjecttypeTranslation',
            ['addtabon' => ['PluginMetademandsBasketobjecttype']]
        );

        Plugin::registerClass(
            'PluginMetademandsInterticketfollowup',
            ['notificationtemplates_types' => true]
        );
        Plugin::registerClass(
            'PluginMetademandsStepform',
            ['notificationtemplates_types' => true]
        );
        $PLUGIN_HOOKS['item_show']['metademands'] = [
            'PluginResourcesResource'
                => ['PluginMetademandsMetademand_Resource', 'redirectFormForResource'],
        ];
        $PLUGIN_HOOKS['item_empty']['metademands'] = [
            'Ticket'
                => ['PluginMetademandsTicket', 'emptyTicket'],
        ];

        $PLUGIN_HOOKS['pre_item_form']['metademands'] = [PluginMetademandsTicket::class, 'emptyform'];

        $PLUGIN_HOOKS['pre_item_purge']['metademands'] = [
            'Profile'
                => ['PluginMetademandsProfile', 'purgeProfiles'],
            'TicketTemplateMandatoryField'
                => ['PluginMetademandsTicketField', 'post_delete_mandatoryField'],
            'TicketTemplatePredefinedField'
                => ['PluginMetademandsTicketField', 'post_delete_predefinedField'],
        ];

        $PLUGIN_HOOKS['pre_item_purge']['metademands'] = [
            'Ticket' => 'plugin_metademands_item_purge',
        ];

        $PLUGIN_HOOKS['item_update']['metademands'] = [
            'Ticket'
                => ['PluginMetademandsTicket', 'post_update_ticket'],
            'ITILCategory'
                => ['PluginMetademandsTicketField', 'update_category_mandatoryFields'],
            'ITILCategory'
                => ['PluginMetademandsTicketField', 'update_category_predefinedFields'],
        ];

        $PLUGIN_HOOKS['pre_item_update']['metademands'] = [
            'Ticket'
                => ['PluginMetademandsTicket', 'pre_update_ticket'],
        ];

        $PLUGIN_HOOKS['item_add']['metademands'] = [
            'TicketTemplateMandatoryField'
                => ['PluginMetademandsTicketField', 'post_add_mandatoryField'],
            'TicketTemplatePredefinedField'
                => ['PluginMetademandsTicketField', 'post_add_predefinedField'],
            'ITILCategory'
                => ['PluginMetademandsTicketField', 'update_category_mandatoryFields'],
            'ITILCategory'
                => ['PluginMetademandsTicketField', 'update_category_predefinedFields'],
            'Ticket'
                => ['PluginMetademandsTicket', 'post_add_ticket'],
        ];

        $PLUGIN_HOOKS['pre_item_add']['metademands'] = [
            'Ticket'
                => ['PluginMetademandsTicket', 'pre_add_ticket'],
        ];

        $PLUGIN_HOOKS['item_transfer']['metademands'] = 'plugin_item_transfer_metademands';

        if (Session::haveRight("plugin_metademands", READ)
            || Session::haveRight('plugin_metademands_createmeta', READ)) {
            $PLUGIN_HOOKS['menu_toadd']['metademands'] = [
                'helpdesk' => 'PluginMetademandsMenu',
                'management' => 'PluginMetademandsBasketobject',
            ];
        }

        if (Session::haveRight("plugin_metademands", READ)
            && !Plugin::isPluginActive('servicecatalog')
            && !Session::haveRight("plugin_metademands_in_menu", READ)) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['metademands'] = PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php';
            $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['metademands'] = PluginMetademandsMetademand::getIcon();
        }

        if (!isset($_SESSION["plugin_metademands_on_login_loaded"])
            || (isset($_SESSION["plugin_metademands_on_login_loaded"])
            && $_SESSION["plugin_metademands_on_login_loaded"] == 0)) {
            if (Session::getCurrentInterface() == "helpdesk"
                && Session::haveRight('plugin_metademands_on_login', READ)) {
                $_SESSION["plugin_metademands_on_login_loaded"] = 1;
                //                Html::redirect(PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php');


                $dest = PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php';
                $toadd = '';
                $dest = addslashes($dest);

                echo "<script type='text/javascript'>
                            NomNav = navigator.appName;
                            if (NomNav=='Konqueror') {
                               window.location='" . $dest . $toadd . "';
                            } else {
                               window.location='" . $dest . "';
                            }
                         </script>";
                exit();

            }
        }

        if (isset($_SESSION["plugin_metademands_on_login_loaded"])
            && $_SESSION["plugin_metademands_on_login_loaded"] == 1
            && isset($_SESSION['glpiactiveprofile']['interface'])
                && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
            if (str_contains($_SERVER['REQUEST_URI'], "create_ticket")
                || str_contains($_SERVER['REQUEST_URI'], "Helpdesk")
                || str_contains($_SERVER['REQUEST_URI'], "ServiceCatalog")) {
                $dest = PLUGIN_METADEMANDS_WEBDIR . '/front/wizard.form.php';
                $toadd = '';
                $dest = addslashes($dest);

                echo "<script type='text/javascript'>
                            NomNav = navigator.appName;
                            if (NomNav=='Konqueror') {
                               window.location='" . $dest . $toadd . "';
                            } else {
                               window.location='" . $dest . "';
                            }
                         </script>";
                exit();
            }
        }
        // END TEST Redirect

        if (Session::haveRight("config", UPDATE)) {
            $PLUGIN_HOOKS['config_page']['metademands'] = 'front/config.form.php';
        }

        // Template
        $PLUGIN_HOOKS['tickettemplate']['metademands'] = ['PluginMetademandsTicket', 'getAllowedFields'];

        // Rule
        $PLUGIN_HOOKS['use_rules']['metademands'] = ['RuleTicket'];

        // Notifications
        $PLUGIN_HOOKS['item_get_datas']['metademands'] = [
            'NotificationTargetTicket'
                => ['PluginMetademandsTicket', 'addNotificationDatas'],
        ];

        if (Plugin::isPluginActive('servicecatalog')) {
            $PLUGIN_HOOKS['servicecatalog']['metademands'] = ['PluginMetademandsServicecatalog'];
        }

        $PLUGIN_HOOKS['plugin_datainjection_populate']['metademands'] = 'plugin_datainjection_populate_basketobjects';

        Plugin::registerClass('PluginMetademandsExport', ['addtabon' => 'Glpi\Form\Form']);
    }

    // Import webservice
    //   $PLUGIN_HOOKS['webservices']['metademands']                   = 'plugin_metademands_registerMethods';
    $PLUGIN_HOOKS['timeline_actions']['metademands'] = [
        'PluginMetademandsMetademandValidation',
        'showActionsForm',
    ];

    //Add another actions into answer
    $PLUGIN_HOOKS['timeline_answer_actions']['metademands'] = [
        'PluginMetademandsInterticketfollowup',
        'addToTimeline',
    ];
    $PLUGIN_HOOKS['show_in_timeline']['metademands'] = [
        'PluginMetademandsInterticketfollowup',
        'getlistItems',
    ];
}

/**
 * Get the name and the version of the plugin - Needed
 *e
 * @return array
 */
function plugin_version_metademands()
{
    return [
        'name' => _n('Meta-Demand', 'Meta-Demands', 2, 'metademands'),
        'version' => PLUGIN_METADEMANDS_VERSION,
        'author' => "<a href='http://blogglpi.infotel.com'>Infotel</a>",
        'license' => 'GPLv2+',
        'homepage' => 'https://github.com/InfotelGLPI/metademands',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ],
    ];
}

/**
 * @return bool
 */
function plugin_metademands_check_prerequisites()
{
    if (!is_readable(__DIR__ . '/vendor/autoload.php')
        || !is_file(__DIR__ . '/vendor/autoload.php')) {
        echo "Run composer install --no-dev in the plugin directory<br>";
        return false;
    }

    return true;
}
