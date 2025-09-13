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

use GlpiPlugin\Metademands\Basketline;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Stepform;
use GlpiPlugin\Servicecatalog\Main;
use GlpiPlugin\Metademands\Wizard;
use GlpiPlugin\Metademands\Config;

include('../../../inc/includes.php');
Session::checkLoginUser();

global $CFG_GLPI;

$wizard = new Wizard();
$metademands = new Metademand();
$fields = new Field();

if (empty($_POST['metademands_id'])) {
    $_POST['metademands_id'] = 0;
}

if (empty($_GET['metademands_id'])) {
    $_GET['metademands_id'] = 0;
}

if (!isset($_GET['meta_type'])) {
    $_GET['meta_type'] = 0;
}

if (empty($_GET['tickets_id'])) {
    $_GET['tickets_id'] = 0;
}

if (empty($_GET['ancestor_tickets_id'])) {
    $_GET['ancestor_tickets_id'] = 0;
}

if (isset($_GET['ancestor_tickets_id'])) {
    $ancestor_tickets_id = $_GET['ancestor_tickets_id'];
}

if (empty($_GET['resources_id'])) {
    $_GET['resources_id'] = 0;
    if (isset($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'])
        && !empty($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'])) {
        $_GET['resources_id'] = $_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'];
    } elseif (isset($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields'])) {
        foreach ($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields'] as $fieldKey => $field) {
            if (!is_array($field) && is_int($field)) {
                $metademandsField = new Field();
                $metademandsField->getFromDB($fieldKey);
                if ($metademandsField->getField('item') == 'PluginResourcesResource') {
                    $_GET['resources_id'] = $field;
                    $_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'] = $field;
                }
            }
        }
    }
} else {
    $_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'] = $_GET['resources_id'];
}

if (empty($_GET['resources_step'])) {
    $_GET['resources_step'] = '';
}

if (!empty($_POST['step'])) {
    $_GET['step'] = $_POST['step'];
}

if (empty($_GET['step'])) {
    $_GET['step'] = Metademand::STEP_INIT;
}

$config = new Config();
$config->getFromDB(1);

//unactivate because   ../index.php?redirect=GlpiPlugin\Metademands\Wizard_X is broken
//if (Session::getCurrentInterface() != 'central'
//    && Plugin::isPluginActive('servicecatalog')
//    && ($_GET['step'] == Metademand::STEP_INIT || $_GET['step'] == Metademand::STEP_LIST)
//    && $config->getField('display_buttonlist_servicecatalog') == 0
//    && Session::haveRight("plugin_servicecatalog", READ)) {
//
//    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
//
//}

// Url Redirect case
if (isset($_GET['id'])) {
    $_GET['metademands_id'] = $_GET['id'];
    $_GET['step'] = Metademand::STEP_SHOW;
    $_GET['tickets_id'] = "0";
}

if (isset($_GET['metademands_id'])) {
    if ($metademands->getFromDB($_GET['metademands_id'])) {
        if (!Session::haveAccessToEntity($metademands->fields['entities_id'], $metademands->fields['is_recursive'])) {
            $message = __('This metademand cannot be used with this entity', 'metademands');
            Session::addMessageAfterRedirect($message, false, ERROR);


            if (Session::getCurrentInterface() != 'central'
                && Plugin::isPluginActive('servicecatalog')) {
                Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
            } else {
                Html::redirect($wizard->getFormURL() . "?step=" . Metademand::STEP_INIT);
            }
        }
    }
}


if (isset($_POST['add_to_basket'])) {
    $KO = false;
    $step = Metademand::STEP_SHOW;

    $checks = [];
    $content = [];
    $data = $fields->find([
        'plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
//        'is_basket' => 1
    ]);


    //Clean $post & $data & $_POST
    $dataOld = $data;
    $post = $_POST['field'];
    // Double appel for prevent order fields
    FieldOption::unsetHidden($data, $post);
    FieldOption::unsetHidden($dataOld, $post);
    $_POST['field'] = $post;


    foreach ($data as $id => $value) {
        if ($value['type'] == 'radio') {
            if (!isset($_POST['field'][$id])) {
                $_POST['field'][$id] = null;
            }
        }
        if ($value['type'] == 'checkbox') {
            if (!isset($_POST['field'][$id])) {
                $_POST['field'][$id] = 0;
            }
        }
        if ($value['type'] == 'informations'
            || $value['type'] == 'title') {
            if (!isset($_POST['field'][$id])) {
                $_POST['field'][$id] = 0;
            }
        }
        if ($value['item'] == 'ITILCategory_Metademands') {
            $_POST['field'][$id] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'][$id] = $_POST['field'][$id];
        }

        $checks[] = Wizard::checkvalues($value, $id, $_POST, 'field');
    }
    foreach ($checks as $check) {
        if ($check['result'] == true) {
            $KO = true;
        }
        $content = array_merge($content, $check['content']);
    }

    if ($KO === false && count($content) > 0) {
        $basketline = new Basketline();
        $basketline->addToBasket($content, $_POST['form_metademands_id']);
    } else {
        Session::addMessageAfterRedirect(__("There is a problem with the basket", "metademands"), false, ERROR);
    }
    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $step);
} elseif (isset($_POST['update_basket_line'])) {
    $line = $_POST['update_basket_line'];
    if (isset($_POST['field_basket_' . $line])) {
        $KO = false;

        $checks = [];
        $content = [];
        $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['metademands_id']]);

        foreach ($data as $id => $value) {
            if ($value['type'] == 'radio') {
                if (!isset($_POST['field_basket_' . $line][$id])) {
                    $_POST['field_basket_' . $line][$id] = null;
                }
            }
            if ($value['type'] == 'checkbox') {
                if (!isset($_POST['field_basket_' . $line][$id])) {
                    $_POST['field_basket_' . $line][$id] = "";
                }
            }
            //            if ($value['type'] == 'informations'
            //                || $value['type'] == 'title') {
            //               if (!isset($_POST['field_basket_' . $line][$id])) {
            //                  $_POST['field_basket_' . $line][$id] = "";
            //               }
            //            }
            if ($value['item'] == 'ITILCategory_Metademands') {
                $_POST['field_basket_' . $line][$id] = $_POST['basket_plugin_servicecatalog_itilcategories_id'] ?? 0;
                $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'][$id] = $_POST['field_basket_' . $line][$id];
            }
            $fieldname = 'field_basket_' . $line;

            $checks[] = Wizard::checkvalues($value, $id, $_POST, $fieldname, true);
        }
    }
    foreach ($checks as $check) {
        if ($check['result'] == true) {
            $KO = true;
        }
        //not used
        $content = array_merge($content, $check['content']);
    }

    if ($KO === false) {
        $basketline = new Basketline();
        $basketline->updateFromBasket($_POST, $line);
    }

    Html::redirect(
        $wizard->getFormURL(
        ) . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . Metademand::STEP_SHOW
    );
} elseif (isset($_POST['delete_basket_line'])) {
    $basketline = new Basketline();
    $basketline->deleteFromBasket($_POST);

    Html::redirect(
        $wizard->getFormURL(
        ) . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . Metademand::STEP_SHOW
    );
} elseif (isset($_POST['delete_basket_file'])) {
    $basketline = new Basketline();
    $basketline->deleteFileFromBasket($_POST);

    Html::redirect(
        $wizard->getFormURL(
        ) . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . Metademand::STEP_SHOW
    );
} elseif (isset($_POST['clear_basket'])) {
    $basketline = new Basketline();
    $basketline->deleteByCriteria([
        'plugin_metademands_metademands_id' => $_POST['metademands_id'],
        'users_id' => Session::getLoginUserID()
    ]);

    Html::redirect(
        $wizard->getFormURL(
        ) . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . Metademand::STEP_SHOW
    );
} elseif (isset($_POST['clean_form'])) {
    unset($_SESSION['plugin_metademands']);
    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $_POST['step']);
} else {
    $name = __('Create a metademand', 'metademands');
    if ($metademands->getFromDB($_GET['metademands_id'])) {
        $name = Wizard::getMetademandTypeName(
            $metademands->fields['object_to_create'],
            $metademands->fields['type']
        );
    }

    //Default wizard
    if (Session::getCurrentInterface() == 'central') {
        Html::header($name, '', "helpdesk", Menu::class, "wizard");
    } else {
        if (Plugin::isPluginActive('servicecatalog')
            && Session::haveRight("plugin_servicecatalog", READ)) {
            Main::showDefaultHeaderHelpdesk($name);
        } else {
            Html::helpHeader($name);
        }
    }

    if (isset($_SESSION['metademands_hide'])) {
        unset($_SESSION['metademands_hide']);
    }
    if (isset($_SESSION['metademands_child_meta'])) {
        unset($_SESSION['metademands_child_meta']);
    }
    $itilcategories_id = 0;
    if (isset($_GET['itilcategories_id']) && $_GET['itilcategories_id'] > 0) {
        $itilcategories_id = $_GET['itilcategories_id'];
    }
    //      if (!isset($_GET['itilcategories_id']) && isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
    //         $itilcategories_id = $_SESSION['servicecatalog']['sc_itilcategories_id'];
    //      }


    $options = [
        'step' => $_GET['step'],
        'metademands_id' => $_GET['metademands_id'],
        'preview' => false,
        'tickets_id' => $_GET['tickets_id'],
        'ancestor_tickets_id' => $_GET['ancestor_tickets_id'],
        'resources_id' => $_GET['resources_id'],
        'resources_step' => $_GET['resources_step'],
        'itilcategories_id' => $itilcategories_id,
        'defaultvalues' =>  $_GET['field'] ?? [],
    ];

    if (isset($_GET['see_form']) && $_GET['see_form'] > 0) {
        $options['seeform'] = true;
    }
    if (isset($_GET['current_ticket_id']) && $_GET['current_ticket_id'] > 0) {
        $options['current_ticket_id'] = $_GET['current_ticket_id'];
    }
    if (isset($_GET['meta_validated'])) {
        if ($_GET['meta_validated'] > 0) {
            $options['meta_validated'] = true;
        } else {
            $options['meta_validated'] = false;
        }
    }
    if (isset($_GET['meta_type'])) {
        $options['meta_type'] = $_GET['meta_type'];
    }
    if (!isset($_GET['step'])) {
        $options['step'] = Metademand::STEP_INIT;
    }

    Stepform::showWaitingWarning();

    $wizard->showWizard($options);

    if (Session::getCurrentInterface() != 'central'
        && Plugin::isPluginActive('servicecatalog')
        && Session::haveRight("plugin_servicecatalog", READ)) {
        Main::showNavBarFooter('metademands');
    }


    if (Session::getCurrentInterface() == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
