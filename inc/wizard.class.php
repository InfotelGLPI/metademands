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

use Glpi\RichText\RichText;

/**
 * Class PluginMetademandsWizard
 */
class PluginMetademandsWizard extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    /**
     * __contruct function where initialize some variables
     *
     * @global array $CFG_GLPI
     */
    public function __construct()
    {
//        $this->table = "glpi_plugin_metademands_metademands";
    }

    /**
     * Return the table used to store this object
     *
     * @param string $classname Force class (to avoid late_binding on inheritance)
     *
     * @return string
     **/
    public static function getTable($classname = null)
    {
        return CommonDBTM::getTable("PluginMetademandsMetademand");
    }

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Wizard overview', 'metademands');
    }

    /**
     * @return bool|int
     */
    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    /**
     * @return bool
     */
    public function canUpdateRequester()
    {
        return Session::haveRight('plugin_metademands_requester', 1);
    }


    /**
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["id" => $item->getID()]
                    )
                );
            }
            return self::getTypeName();
        }
        return '';
    }

    /**
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == 'PluginMetademandsMetademand') {
            $field->showForm(0, ["item" => $item]);
        }
        return true;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showForm($ID, $options = [])
    {
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }
        Html::requireJs('tinymce');

        $metademand = new PluginMetademandsMetademand();

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            $item = $options['item'];
            $canedit = $metademand->can($item->fields['id'], UPDATE);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
            $this->fields['color'] = '#000';
        }

        $wizard = new PluginMetademandsWizard();
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th class='tab_bg_1'>" . PluginMetademandsWizard::getTypeName() . "</th></tr>";
        $meta = new PluginMetademandsMetademand();
//        if ($meta->getFromDB($item->fields['id'])) {
//            if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
//                $background_color = $meta->fields['background_color'];
//            }
//        }
        echo "<tr><td>";
        $options = ['step' => PluginMetademandsMetademand::STEP_SHOW,
            'metademands_id' => $item->getID(),
            'preview' => true];
        $wizard->showWizard($options);
        echo "</td></tr>";
        echo "</table>";
        return true;
    }

    /**
     * @param \User $user
     */
    public static function showUserInformations(User $user)
    {
        $infos = getUserName($user->getID(), 2);
        echo $infos['comment'];

        $cond['is_requester'] = 1;
        $groups = PluginMetademandsField::getUserGroup(
            $_SESSION['glpiactiveentities'],
            $user->getID(),
            $cond,
            false
        );
        $style = '';
        if (!empty($user->fields['picture'])) {
            $style = 'tooltip_group_text';
        }

        if (count($groups) > 0) {
            echo "<div class='$style'>";
            echo "<b>" . _n('Group', 'Groups', count($groups)) . "</b> :<br>";
            foreach ($groups as $group) {
                echo Dropdown::getDropdownName("glpi_groups", $group) . "<br>";
            }
            echo "</div>";
        }
    }

    /**
     * @param $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showWizard($options)
    {
        $parameters = ['step' => PluginMetademandsMetademand::STEP_INIT,
            'metademands_id' => 0,
            'preview' => false,
            'seeform' => false,
            'tickets_id' => 0,
            'current_ticket_id' => 0,
            'meta_validated' => 1,
            'resources_id' => 0,
            'resources_step' => '',
            'meta_type' => '',
            'block_id' => 0,
            'itilcategories_id' => 0];

        // if given parameters, override defaults
        foreach ($options as $key => $value) {
            if (isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }
        $_SESSION['servicecatalog']['sc_itilcategories_id'] = $parameters['itilcategories_id'];
        // Retrieve session values
        if (isset($_SESSION['plugin_metademands']['fields']['tickets_id'])) {
            $parameters['tickets_id'] = $_SESSION['plugin_metademands']['fields']['tickets_id'];
        }
        if (isset($_SESSION['plugin_metademands']['fields']['resources_id'])) {
            $parameters['resources_id'] = $_SESSION['plugin_metademands']['fields']['resources_id'];
        }
        if (isset($_SESSION['plugin_metademands']['fields']['resources_step'])) {
            $parameters['resources_step'] = $_SESSION['plugin_metademands']['fields']['resources_step'];
        }
        Html::requireJs("metademands");
        //      echo Html::script(PLUGIN_METADEMANDS_DIR_NOFULL . "/lib/bootstrap/4.5.3/js/bootstrap.bundle.min.js");
        //      echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/style_bootstrap_main.css");
        //      echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/style_bootstrap_ticket.css");

        echo "<div id ='content'>";
        $background_color = "";
        $meta = new PluginMetademandsMetademand();
        $maintenance_mode = 0;
        $use_as_step = 0;
        if ($meta->getFromDB($parameters['metademands_id'])) {
            if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                $background_color = $meta->fields['background_color'];
            }
            $maintenance_mode = $meta->fields['maintenance_mode'];
            $_SESSION['servicecatalog']['sc_itilcategories_id'] = $meta->fields['itilcategories_id'];
            $use_as_step = $meta->fields['step_by_step_mode'];
        }

        if ($maintenance_mode == 1 && !$parameters['preview']) {
            echo "<h3>";
            echo "<div class='alert alert-important alert-warning center'>";
            echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:orange'></i>&nbsp;";
            echo __('This form is in maintenance mode', 'metademands') . "<br>";
            echo __('Please come back later', 'metademands') . "</div></h3>";
        } else {
//            if (!$parameters['preview']) {
            echo "<div class='bt-container-fluid asset metademands_wizard_rank'> ";
//            }
            if ($parameters['step'] > PluginMetademandsMetademand::STEP_LIST) {
                // Wizard title
                if ($meta->getFromDB($parameters['metademands_id'])
                    && Plugin::isPluginActive('servicecatalog')
                    && Session::getCurrentInterface() != 'central'
                    && $parameters['itilcategories_id'] > 0) {

                    $treename = PluginServicecatalogCategory::getTreeCategoryFriendlyName($meta->fields['type'], $parameters['itilcategories_id'], 6);
                    $name = $treename['name'];
                    $treescript = json_decode($treename['script']);

                    echo "<script>$(document).ready(function() {
                          $('#title_cat').show();
                             $('#categories_title').show();
                             document.getElementById('title_cat').innerHTML = \"$name\";
                             let newScript = document.createElement('script');
                             newScript.type = 'text/javascript';
                             let scriptContent = document.createTextNode( $treescript );
                             newScript.appendChild( scriptContent ); //add the text node to the newly created div.
                             document.body.appendChild( newScript ); //add the text node to the newly created div.
                        });</script>";

                    echo "<span id='categories_title' style='display: none'>";
                    $style = "";
                    $config = new PluginServicecatalogConfig();
                    if ($config->getLayout() == PluginServicecatalogConfig::BOOTSTRAPPED
                        || $config->getLayout() == PluginServicecatalogConfig::BOOTSTRAPPED_COLOR) {
                        $style = 'style="border: 1px solid transparent;border-radius: 1px;margin: 0px;"';
                    }
                    echo "<div class='alert alert-secondary' role='alert' $style>";
                    echo "<span id='title_cat'>";
                    echo "</span>";
                    echo "</div>";
                    echo "</span>";
                    echo "</h5>";
                }
            }

            echo "<div id='meta-form' class='bt-block'> ";

            echo "<form novalidate name='wizard_form' id ='wizard_form'
                        method='post'
                        action= '" . Toolbox::getItemTypeFormURL(__CLASS__) . "'
                        enctype='multipart/form-data'
                        class='metademands_img'> ";

            // Case of simple ticket convertion
            echo Html::hidden('tickets_id', ['value' => $parameters['tickets_id']]);
            echo Html::hidden('resources_id', ['value' => $parameters['resources_id']]);
            echo Html::hidden('resources_step', ['value' => $parameters['resources_step']]);
            echo Html::hidden('block_id', ['value' => $parameters['block_id']]);

            $icon = '';

            if ($parameters['step'] == PluginMetademandsMetademand::STEP_INIT) {
                // Wizard title
                echo "<div class=\"row\">";
                echo "<div class=\"col-md-12\">";
                echo "<h4><div class='alert alert-light' role='alert'>";
                $icon = "fa-share-alt";
                if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                    $icon = $meta->fields['icon'];
                }
                echo "<i class='fa-2x fas $icon'></i>&nbsp;";
                echo __('What you want to do ?', 'metademands');
                echo "</div></h4></div></div>";
            } elseif ($parameters['step'] == PluginMetademandsMetademand::STEP_LIST) {
                // Wizard title
                echo "<div class=\"row\">";
                echo "<div class=\"col-md-12\">";
                echo "<h4><div class='alert alert-light' role='alert'>";
                $icon = "fa-share-alt";
                if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                    $icon = $meta->fields['icon'];
                }
                echo "<i class='fa-2x fas $icon'></i>&nbsp;";
                echo __('Form choice', 'metademands');
                echo "</div></h4></div></div>";
            } elseif ($parameters['step'] > PluginMetademandsMetademand::STEP_LIST) {
                // Wizard title
                echo "<div class=\"row\">";

                echo "<div class=\"col-md-12 md-title\">";
                echo "<div style='background-color: #FFF'>";
                $title_color = "#000";
                if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
                    $title_color = $meta->fields['title_color'];
                }

                $color = self::hex2rgba($title_color, "0.03");
                $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;margin-bottom: 10px;'";
                echo "<div class='card-header d-flex justify-content-between align-items-center md-color' $style_background>";// alert alert-light
//                echo "<div class='left' style='display: inline;float: left;'>";
//
//                if ($parameters['preview'] || $parameters['seeform']) {
//                    $use_as_step = 0;
//                }
//                if (
////                    Session::getCurrentInterface() != 'central'
////                    &&
//                    $use_as_step == 0) {
//                    $title = "<i class='fas fa-chevron-left fa-2x' title=\"".__('Previous')."\" data-hasqtip='0' aria-hidden='true'></i>&nbsp;";
////                    $title .= __('Previous');
//                    echo Html::submit($title, ['name' => 'previous', 'class' => 'btn btn-link']);
//                }
//                echo "</div>";
//                echo "<div class='center' style='display: inline;text-align: center;font-size: 16px;'>";
                $meta = new PluginMetademandsMetademand();
                if ($meta->getFromDB($parameters['metademands_id'])) {
                    if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                        $icon = $meta->fields['icon'];
                    }
                }

                echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
                echo "<i class='fa-2x fas $icon' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>&nbsp;";
                if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
                    echo $meta->getName();
                } else {
                    echo $n;
                }
                if (isset($parameters['itilcategories_id'])
                    && isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
                    $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
                    if (is_array($cats) && count($cats) > 1) {
                        $itilCategory = new ITILCategory();
                        if ($itilCategory->getFromDB($parameters['itilcategories_id'])) {
                            echo " - " . $itilCategory->fields['completename'];
                        }
                    }
                }


                //         echo Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $parameters['metademands_id']);
                if (Session::getCurrentInterface() == 'central'
                    && Session::haveRight('plugin_metademands', UPDATE)
                    && !$parameters['seeform']) {
                    echo "&nbsp;<a href='" . Toolbox::getItemTypeFormURL('PluginMetademandsMetademand') . "?id=" . $parameters['metademands_id'] . "'>
                        <i class='fas fa-wrench'></i></a>";
                }
                echo "</h2>";
//                echo "</div>";
                $config = PluginMetademandsConfig::getInstance();
                //            if (!$parameters['preview']
                //                && !$parameters['seeform']
                //                && $config['use_draft']) {
                //               echo "<span class='mydraft'>";
                //               $count_drafts = PluginMetademandsDraft::countDraftsForUserMetademand(Session::getLoginUserID(), $parameters['metademands_id']);
                //               if ($count_drafts > 0) {
                //                  echo "<span class='mydraft-text'>";
                //                  echo sprintf(_n('You have %d draft', 'You have %d drafts', $count_drafts, 'metademands'),
                //                               $count_drafts);
                //                  echo "</span>&nbsp;";
                //               }
                //
                //               echo "&nbsp;<i class='fas fa-2x mydraft-fa fa-cloud-download-alt pointer' title='" . _sx('button', 'Your drafts', 'metademands') . "'
                //                data-hasqtip='0' aria-hidden='true' onclick='$(\"#divdrafts\").toggle();' ></i>";
                //               echo "</span>";
                //            }
                if (!$parameters['preview'] && !$parameters['seeform']) {
                    echo "<div class='mydraft right' style='display: inline;float: right;'>";
                    echo "&nbsp;<i class='fas fa-2x mydraft-fa fa-align-justify pointer' title='" . _sx('button', 'Your forms', 'metademands') . "' 
                data-hasqtip='0' aria-hidden='true' onclick='$(\"#divnavforms\").toggle();' ></i>";
                    echo "</span>";
                }
                echo "</h4>";

                echo "</div></div>";
                if (!$parameters['seeform']) {
                    echo "<div id='divnavforms' class=\"input-draft card bg-light mb-3\" style='display:none;color: #000!important;position:absolute;right:0;z-index: 1000;'>";
                    echo "<ul class='nav nav-tabs' id= 'myTab' role = 'tablist'>";
                    echo "<li class='nav-item' role='presentation'>";
                    echo "<button class='nav-link active' id='divformmodels-tab' data-bs-toggle='tab' 
    data-bs-target='#divformmodels' type='button' role='tab' aria-controls='divformmodels' aria-selected='true'>";
                    echo __("Your models", 'metademands');
                    echo "</button>";
                    echo "</li>";
                    echo "<li class='nav-item' role='presentation'>";
                    echo "<button class='nav-link' id='divforms-tab' data-bs-toggle='tab' 
    data-bs-target='#divforms' type='button' role='tab' aria-controls='divforms' aria-selected='true'>";
                    echo __("Your created forms", 'metademands');
                    echo "</button>";
                    echo "</li>";

                    if ($config['use_draft']) {
                        echo "<li class='nav-item' role='presentation'>";
                        echo "<button class='nav-link' id='divdrafts-tab' data-bs-toggle='tab' 
    data-bs-target='#divdrafts' type='button' role='tab' aria-controls='divdrafts' aria-selected='true'>";
                        echo __("Your drafts", 'metademands');
                        echo "</button>";
                        echo "</li>";
                    }
                    echo "</ul>";

                    echo "<div class='tab-content' id='myTabContent'>";

                    echo "<div id='divformmodels' class='tab-pane fade show active' role='tabpanel' aria-labelledby='divformmodels-tab'>";
                    echo PluginMetademandsForm::showFormsForUserMetademand(Session::getLoginUserID(), $parameters['metademands_id'], true);
                    echo "</div>";

                    echo "<div id='divforms' class='tab-pane fade' role='tabpanel' aria-labelledby='divforms-tab'>";
                    echo PluginMetademandsForm::showFormsForUserMetademand(Session::getLoginUserID(), $parameters['metademands_id'], false);
                    echo "</div>";

                    if ($config['use_draft']) {
                        //
                        echo "<div id='divdrafts' class='tab-pane fade' role='tabpanel' aria-labelledby='divdrafts-tab'>";
                        echo PluginMetademandsDraft::showDraftsForUserMetademand(Session::getLoginUserID(), $parameters['metademands_id']);
                        echo "</div>";
                    }
                }

                echo "</div>";
                echo "</div>";
                echo "</div>";
                if ($meta->getFromDB($parameters['metademands_id'])
                    && !empty($meta->fields['comment'])) {
                    if (empty($comment = PluginMetademandsMetademand::displayField($meta->getID(), 'comment'))) {
                        $comment = $meta->fields['comment'];
                    }
                    echo "<div class='center' style='background: #EEE;padding: 10px;'><i>" . nl2br($comment) . "</i></div>";
                }
                if ($meta->getFromDB($parameters['metademands_id'])
                    && Plugin::isPluginActive('servicecatalog')) {
                    $configsc = new PluginServicecatalogConfig();
                    if ($configsc->seeCategoryDetails()) {
                        $itilcategories_id = 0;
                        $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
                        if (is_array($cats) && count($cats) == 1) {
                            foreach ($cats as $cat) {
                                $itilcategories_id = $cat;
                            }
                        }
                        $type = $meta->fields['type'];
                        $helpdesk_category = new PluginServicecatalogCategory();
                        if ($itilcategories_id > 0 && $helpdesk_category->getFromDBByCategory($itilcategories_id)
                            && ($helpdesk_category->fields['comment'] != null
                                || $helpdesk_category->fields['service_detail'] != null
                                || $helpdesk_category->fields['service_users'] != null
                                || $helpdesk_category->fields['service_ttr'] != null
                                || $helpdesk_category->fields['service_use'] != null
                                || $helpdesk_category->fields['service_supervision'] != null
                                || $helpdesk_category->fields['service_rules'] != null)) {
                            //                     echo "<div class='alert alert-important alert-info d-flex alert-dismissible' role='alert'>";
                            //                     echo "<a href='#' class='close' data-bs-dismiss='alert' aria-label='close'>&times;</a>";
                            //                     echo ($helpdesk_category->fields['comment'] != null) ?
                            //                        "
                            //                    <span class='titlespeech'>" . __('Description') . "</span><br>" .
                            //                        Glpi\tRichText\RichText::getSafeHtml($helpdesk_category->fields['comment']) . "" : "";
                            //                     echo ($helpdesk_category->fields['service_detail'] != null) ?
                            //                        "
                            //                    <span class='titlespeech'>" . __('How can i use it', 'servicecatalog') . "</span><br><br>" .
                            //                        Glpi\RichText\RichText::getSafeHtml($helpdesk_category->fields['service_detail']) . "" : "";
                            //                     echo ($helpdesk_category->fields['service_users'] != null) ?
                            //                        "
                            //                    <span class='titlespeech'>" . __('Who can benefit from this service?', 'servicecatalog') . "</span><br><br>" .
                            //                        Glpi\RichText\RichText::getSafeHtml($helpdesk_category->fields['service_users']) . "" : "";
                            //                     echo ($helpdesk_category->fields['service_ttr'] != null) ?
                            //                        "
                            //                    <span class='titlespeech'>" . __('Lead time', 'servicecatalog') . "</span><br><br>" .
                            //                        Glpi\RichText\RichText::getSafeHtml($helpdesk_category->fields['service_ttr']) . "" : "";
                            //                     echo ($helpdesk_category->fields['service_use'] != null) ?
                            //                        "
                            //                    <span class='titlespeech'>" . __('How to obtain the software in case of request?', 'servicecatalog') . "</span><br><br>" .
                            //                        Glpi\RichText\RichText::getSafeHtml($helpdesk_category->fields['service_use']) . "" : "";
                            //                     echo ($helpdesk_category->fields['service_supervision'] != null) ?
                            //                        "
                            //                        <span class='titlespeech'>" . __('Availability of service', 'servicecatalog') . "</span><br><br>" .
                            //                        Glpi\RichText\RichText::getSafeHtml($helpdesk_category->fields['service_supervision']) . "" : "";
                            //                     echo ($helpdesk_category->fields['service_rules'] != null) ?
                            //                        "
                            //                    <span class='titlespeech'>" . __('What are the rules to follow ?', 'servicecatalog') . "</span><br><br>" .
                            //                        Glpi\RichText\RichText::getSafeHtml($helpdesk_category->fields['service_rules']) . "" : "";
                            //
                            //                     echo "</div>";
                            echo "<div class='alert alert-light' style='margin-bottom: 10px;'>";
                            echo "<button form='' class='btn btn-primary' href='#' data-bs-toggle='modal' data-bs-target='#categorydetails$itilcategories_id' title=\"" . __('More informations', 'servicecatalog') . "\"> ";
                            echo __('More informations of this category ? click here', 'servicecatalog');
                            echo "</button>";
                            echo "</div>";
                            echo Ajax::createIframeModalWindow(
                                'categorydetails' . $itilcategories_id,
                                PLUGIN_SERVICECATALOG_WEBDIR . "/front/categorydetail.form.php?type=" . $type . "&category_id=" . $itilcategories_id,
                                ['title' => __('More informations', 'servicecatalog'),
                                    'display' => false,
                                    'width' => 1050,
                                    'height' => 500]
                            );
                        }
                    }
                }

                // Display user informations
                $userid = Session::getLoginUserID();
                // If ticket exists we get its first requester
                if ($parameters['tickets_id']) {
                    $users_id_requester = PluginMetademandsTicket::getUsedActors($parameters['tickets_id'], CommonITILActor::REQUESTER, 'users_id');
                    if (count($users_id_requester)) {
                        $userid = $users_id_requester[0];
                    }
                }

                // Retrieve session values
                if (isset($_SESSION['plugin_metademands']['fields']['_users_id_requester'])) {
                    $userid = $_SESSION['plugin_metademands']['fields']['_users_id_requester'];
                }

                $user = new User();
                $user->getFromDB($userid);

                $canuse = PluginMetademandsGroup::isUserHaveRight($parameters['metademands_id']);
                if ($parameters['preview'] == 1) {
                    $canuse = 1;
                }
                // Rights management
                if (Session::getCurrentInterface() == 'central'
                    && !empty($parameters['tickets_id'])
                    && !Session::haveRight('ticket', UPDATE)) {
                    self::showMessage(__("You don't have the right to update tickets", 'metademands'), true);
                    return false;
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                } elseif (!$canuse) {
                    self::showMessage(__("You don't have the right to create meta-demand", 'metademands'), true);
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    return false;
                }
                echo Html::hidden('_users_id_requester', ['value' => $userid]);
            }
            $options['resources_id'] = $parameters['resources_id'];
            $options['itilcategories_id'] = $parameters['itilcategories_id'];

            self::showWizardSteps($parameters['step'], $parameters['metademands_id'], $parameters['preview'], $options, $parameters['seeform'], $parameters['current_ticket_id'], $parameters['meta_validated']);
            Html::closeForm();
            echo "</div>";
            if (!$parameters['preview']) {
                echo "</div>";
            }
        }
        echo "</div>";
    }

    /**
     * @param       $step
     * @param int $metademands_id
     * @param bool $preview
     * @param array $options
     *
     * @throws \GlpitestSQLError
     */
    public static function showWizardSteps($step, $metademands_id = 0, $preview = false, $options = [], $seeform = false, $current_ticket = 0, $meta_validated = 1)
    {
        if ($preview == false) {
            echo "<div id='ajax_loader' class=\"ajax_loader\">";
            echo "</div>";

            echo Html::scriptBlock(
                "$(window).load(function() {
             $('#ajax_loader').hide();
          });"
            );
        }
        if ($step === PluginMetademandsMetademand::STEP_CREATE) {
            $values = isset($_SESSION['plugin_metademands']) ? $_SESSION['plugin_metademands'] : [];
            self::createMetademands($metademands_id, $values, $options);
        } elseif ($step == 0) {
            self::listMetademandTypes();
        } else {
            switch ($step) {
                case PluginMetademandsMetademand::STEP_LIST:

                    if (isset($options['meta_type'])) {
                        $_SESSION['plugin_metademands']['type'] = $options['meta_type'];
                        self::listMetademands($options['meta_type']);
                    } else {
                        echo __('No existing forms founded', 'metademands');
                    }

                    unset($_SESSION['plugin_metademands']);
                    unset($_SESSION['servicecatalog']['sc_itilcategories_id']);
                    break;

                default:
                    self::showMetademands($metademands_id, $step, $current_ticket, $meta_validated, $preview, $options, $seeform);
                    break;
            }
            echo Html::hidden('step', ['value' => $step]);
        }
    }

    /**
     * @param $file_data
     */
    //   function uploadFiles($file_data) {
    //
    //      echo "<div class=\"row\">";
    //      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
    //      echo "<form name='wizard_form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' enctype='multipart/form-data'>";
    //      echo "<h1>";
    //      echo __('Add documents on the demand', 'metademands');
    //      echo "</h1>";
    //
    //      $ticket = new Ticket();
    //      $ticket->getFromDB($file_data['tickets_id']);
    //
    //      $docadded = $ticket->addFiles($file_data['tickets_id'], 0);
    //      if (count($docadded) > 0) {
    //         foreach ($docadded as $name) {
    //            echo __('Added document', 'metademands') . " $name";
    //         }
    //      }
    //      echo "</div>";
    //      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
    //      echo "<input type='submit' class='submit' name='return' value='" . _sx('button', 'Finish', 'metademands') . "'>";
    //      echo "</div>";
    //
    //      Html::closeForm();
    //      echo "</div>";
    //      echo "</div>";
    //   }

    /**
     * @param $step
     */
    //   static function chooseType($step) {
    //
    //      echo "<div class=\"row\">";
    //      echo "<div class=\"bt-feature col-md-12 metademands_wizard_border\">";
    //      echo "<h4 class=\"bt-title-divider\"><span>";
    //      echo sprintf(__('Step %d - Ticket type choice', 'metademands'), $step);
    //      echo "</span></h4>";
    //      echo "</div>";
    //      echo "</div>";
    //
    //      echo "<div class=\"row\">";
    //      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
    //      // Type
    //      echo '<b>' . __('Type') . '</b>';
    //      echo "</div>";
    //      echo "<div class=\"bt-feature bt-col-sm-6 bt-col-md-6\">";
    //      $types    = PluginMetademandsTask::getTaskTypes();
    //      $types[0] = Dropdown::EMPTY_VALUE;
    //      ksort($types);
    //      Dropdown::showFromArray('type', $types, ['width' => 150]);
    //      echo "</div>";
    //      echo "</div>";
    //
    //      echo "<div class=\"row\">";
    //      echo "<div class=\"bt-feature col-md-12 right\">";
    //      echo "<input type='submit' class='submit' name='next' value='" . __('Next') . "'>";
    //      echo "</div>";
    //      echo "</div>";
    //   }

    /**
     * @param string $limit
     *
     * @param int $type
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public static function selectMetademands($all = false, $limit = "", $type = Ticket::DEMAND_TYPE)
    {
        global $DB;

        if ($type == Ticket::INCIDENT_TYPE || $type == Ticket::DEMAND_TYPE) {
            $crit = "`type` = '$type'";
            if ($all == true) {
                $crit = "`type` IS NOT NULL ";
            }
        } else {
            $crit = "`object_to_create` = '$type'";
            if ($all == true) {
                $crit = "`object_to_create` IS NOT NULL ";
            }
        }


        $dbu = new DbUtils();
        $query = "SELECT `id`,`name`
                   FROM `glpi_plugin_metademands_metademands`
                   WHERE (is_order = 1  OR `itilcategories_id` <> '')
                   AND $crit  
                        AND `id` NOT IN (SELECT `plugin_metademands_metademands_id` FROM `glpi_plugin_metademands_metademands_resources`) "
            . $dbu->getEntitiesRestrictRequest(" AND ", 'glpi_plugin_metademands_metademands', '', $_SESSION['glpiactive_entity'], true);

        //Type can be deleted
        $meta = new PluginMetademandsMetademand();
        if ($meta->maybeDeleted()) {
            $query .= " AND `is_deleted` = '0' ";
        }
        if ($meta->maybeTemplate()) {
            $query .= " AND `is_template` = '0' ";
        }

        $query .= "AND `is_active` = 1 ORDER BY `name` $limit";

        $metademands = [];
        $result = $DB->query($query);
        if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
                $canuse = PluginMetademandsGroup::isUserHaveRight($data['id']);
                $canuse_step = PluginMetademandsStep::isUserHaveRight($data['id']);
                if ($canuse && $canuse_step) {
                    if (empty($name = PluginMetademandsMetademand::displayField($data['id'], 'name'))) {
                        $name = $data['name'];
                    }
                    $metademands[$data['id']] = $name;
                }
            }
        }
        return $metademands;
    }

    /**
     * @throws \GlpitestSQLError
     */
    public static function listMetademandTypes()
    {
        global $CFG_GLPI;

        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.css.php");

        $config = PluginMetademandsConfig::getInstance();

        $meta = new PluginMetademandsMetademand();

        $data = [];

        $metademands_incidents = self::selectMetademands(false, "", Ticket::INCIDENT_TYPE);
        if (count($metademands_incidents) > 0) {
            $data[Ticket::INCIDENT_TYPE] = __('Report an incident', 'metademands');
        }

        $metademands_requests = self::selectMetademands(false, "", Ticket::DEMAND_TYPE);
        if (count($metademands_requests) > 0) {
            $data[Ticket::DEMAND_TYPE] = __('Make a request', 'metademands');
        }

        $metademands_problems = self::selectMetademands(false, "", "Problem");
        if (count($metademands_problems) > 0) {
            $data['Problem'] = __('Report a problem', 'metademands');
        }
        $metademands_changes = self::selectMetademands(false, "", "Change");
        if (count($metademands_changes) > 0) {
            $data['Change'] = __('Make a change request', 'metademands');
        }
        if (count($data) > 0) {
            foreach ($data as $type => $typename) {

                echo "<a class='bt-buttons' href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=" . PluginMetademandsMetademand::STEP_LIST . "&meta_type=$type'>";
                echo '<div class="btnsc-normal" >';
                $fasize = "fa-6x";
                echo "<div class='center'>";
                $icon = "fa-share-alt";
                echo "<i class='bt-interface fa-menu-md fas $icon $fasize'></i>";//$style
                echo "</div>";
                echo "<br><p>";
                echo $typename;
                echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                echo "</span></em>";
                echo "</p></div></a>";
            }
        } else {
            echo "<div class='alert alert-important alert-info center'>";
            echo __("No existing forms founded", 'metademands');
            echo "</div>";
        }

    }

    /**
     * @throws \GlpitestSQLError
     */
    public static function listMetademands($type)
    {
        global $CFG_GLPI;

        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.css.php");

        $config = PluginMetademandsConfig::getInstance();

        $meta = new PluginMetademandsMetademand();
        if ($config['display_type'] == 1) {

            $metademands = self::selectMetademands(false, "", $type);
            if (count($metademands) > 0) {
                echo "<div id='listmeta'>";
                $title = __("Find a form", "metademands");
                echo "<div tabindex='-1' id='mt-fuzzysearch'>";
                echo "<div class='modal-content'>";
                echo "<div class='modal-body' style='padding: 10px;'>";
                echo "<input type='text' class='mt-home-trigger-fuzzy form-control' placeholder='" . $title . "'>";
                echo "<input type='hidden' name='meta_type' id='meta_type' value='" . $type . "'/>";
                echo "<ul class='results list-group mt-2' style='background: #FFF;'></ul>";
                echo "</div>";
                echo "</div>";
                echo "</div>";

                foreach ($metademands as $id => $name) {
                    $meta = new PluginMetademandsMetademand();
                    if ($meta->getFromDB($id)) {
                        echo "<a class='bt-buttons' href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=2'>";
                        echo '<div class="btnsc-normal" >';
                        $fasize = "fa-4x";
                        echo "<div class='center'>";
                        $icon = "fa-share-alt";
                        if (!empty($meta->fields['icon'])) {
                            $icon = $meta->fields['icon'];
                        }
                        echo "<i class='bt-interface fa-menu-md fas $icon $fasize' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>";//$style
                        echo "</div>";

                        echo "<br><p>";
                        if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
                            echo $meta->getName();
                        } else {
                            echo $n;
                        }

                        if (empty($comm = PluginMetademandsMetademand::displayField($meta->getID(), 'comment')) && !empty($meta->fields['comment'])) {
                            echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                            echo $meta->fields['comment'];
                            echo "</span></em>";
                        } elseif (!empty($comm = PluginMetademandsMetademand::displayField($meta->getID(), 'comment'))) {
                            echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                            echo $comm;
                            echo "</span></em>";
                        }

                        if ($config['use_draft']) {
                            $count_drafts = PluginMetademandsDraft::countDraftsForUserMetademand(Session::getLoginUserID(), $id);
                            if ($count_drafts > 0) {
                                echo "<br><em><span class='mydraft-comment'>";
                                echo sprintf(
                                    _n('You have %d draft', 'You have %d drafts', $count_drafts, 'metademands'),
                                    $count_drafts
                                );
                                echo "</span>";
                            }
                        }

                        echo "</p></div></a>";
                    }
                }
                echo "</div>";
            }
        } else {


            echo "<div id='listmeta' class=\"bt-row\">";
            echo "<div class=\"bt-feature bt-col-sm-12 bt-col-md-12 \">";
            // METADEMAND list
            $options['display_emptychoice'] = true;
            $options['type'] = $type;
            $data = $meta->listMetademands(false, $options);
            Dropdown::showFromArray('metademands_id', $data, ['width' => 250]);
            echo "</div>";
            echo "</div>";

            echo "<br/>";
            echo "<div class=\"bt-row\">";
            echo "<div class=\"bt-feature bt-col-sm-12 bt-col-md-12 right\">";
            echo Html::submit(__('Next'), ['name' => 'next', 'class' => 'btn btn-primary']);
            echo "</div>";

            echo "</div>";
        }
    }

    /**
     * @param       $metademands_id
     * @param       $step
     * @param bool $preview
     *
     * @param array $options
     *
     * @throws \GlpitestSQLError
     */
    public static function showMetademands($metademands_id, $step, $current_ticket, $meta_validated, $preview = false, $options = [], $seeform = false)
    {
        global $CFG_GLPI;

        $parameters = ['itilcategories_id' => 0];

        // if given parameters, override defaults
        foreach ($options as $key => $value) {
            if (isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        $metademands = new PluginMetademandsMetademand();
        $metademands_data = $metademands->constructMetademands($metademands_id);
        $metademands->getFromDB($metademands_id);

        echo "<div class='md-wizard'>";
        //      echo "<div width='100%'>";
        //Delete metademand wich need to be hide from $metademands_data
        if (isset($_SESSION['metademands_hide'])) {
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    if (in_array($form_metademands_id, $_SESSION['metademands_hide'])) {
                        unset($metademands_data[$form_step]);
                    }
                }
            }
            //Reorder array
            $metademands_data = array_values($metademands_data);
            array_unshift($metademands_data, "", "");
            unset($metademands_data[0]);
            unset($metademands_data[1]);
        }

        if (count($metademands_data)) {
            if ($step - 1 > count($metademands_data) && !$preview) {
                self::showWizardSteps(PluginMetademandsMetademand::STEP_CREATE, $metademands_id, $preview, $seeform, $current_ticket, $meta_validated);
            } else {
                echo "</div>";

                foreach ($metademands_data as $form_step => $data) {
                    if ($form_step == $step) {
                        foreach ($data as $form_metademands_id => $line) {
                            if ($metademands->fields['is_order'] == 1) {
                                if (!$preview && countElementsInTable(
                                        "glpi_plugin_metademands_basketlines",
                                        ["plugin_metademands_metademands_id" => $metademands->fields['id'],
                                            "users_id" => Session::getLoginUserID()]
                                    )) {
                                    echo "<div class='height-div left-div'>";
                                }
                            }
                            if (!isset($_POST['form_metademands_id']) ||
                                (isset($_POST['form_metademands_id']) && $form_metademands_id != $_POST['form_metademands_id'])) {
                                if (!isset($_SESSION['metademands_hide'][$form_metademands_id])) {
                                    self::constructForm($metademands_id, $metademands_data, $step, $line['form'], $preview, $parameters['itilcategories_id'], $seeform, $current_ticket, $meta_validated);
                                } else {
                                    $step++;
                                }
                            } else {
                                self::constructForm($metademands_id, $metademands_data, $step, $line['form'], $preview, $parameters['itilcategories_id'], $seeform, $current_ticket, $meta_validated);
                            }
                            unset($_SESSION['plugin_metademands']['fields']);
                            if ($metademands->fields['is_order'] == 1) {
                                if (!$preview && countElementsInTable(
                                        "glpi_plugin_metademands_basketlines",
                                        ["plugin_metademands_metademands_id" => $metademands->fields['id'],
                                            "users_id" => Session::getLoginUserID()]
                                    )) {
                                    echo "<div style='text-align: center; margin-top: 20px; margin-bottom : 20px;' class=\"bt-feature col-md-12\">";

                                    $title = "<i class='fas fa-plus' data-hasqtip='0' aria-hidden='true'></i>&nbsp;";
                                    $title .= _sx('button', 'Add to basket', 'metademands');
                                    echo Html::submit($title, ['name' => 'add_to_basket',
                                        'id' => 'add_to_basket',
                                        'class' => 'btn btn-primary btn-sm']);

                                    echo "</div>";

                                    echo "</div>";
                                }

                                PluginMetademandsBasketline::constructBasket($metademands_id, $line['form'], $preview);
                            }
                            echo Html::hidden('form_metademands_id', ['value' => $form_metademands_id]);
                        }
                    }
                }
                $use_as_step = 0;
                if ($preview || $seeform) {
                    $use_as_step = 0;
                }
                if (!$preview && (!$seeform
                        || (isset($options['resources_id'])
                            && $options['resources_id'] > 0)
                        || ($current_ticket > 0
                            && ((!$meta_validated
                                    && $metademands->fields['can_update'] == true) ||
                                ($meta_validated
                                    && $metademands->fields['can_clone'] == true))
                            && Session::haveRight('plugin_metademands_updatemeta', READ)))

                ) {
                    echo "<div class=\"middle-div bt-container-fluid\">";
                    echo "<div class=\"bt-feature col-md-12 \">";
                    echo "</div>";
                    echo "</div>";

                    echo "<div class=\"row\" style='width: 100%;'>";

                    echo "<div class=\"bt-feature col-md-12 \">";
                    if ($current_ticket > 0 && !$meta_validated) {
                        Html::hidden('current_ticket_id', ['value' => $current_ticket]);
                    }
                    echo Html::hidden('metademands_id', ['value' => $metademands_id]);
                    echo Html::hidden('update_fields', ['value' => 1]);
                    //verify if have sons metademand
                    if ($step - 1 >= count($metademands_data)) {
                        echo Html::hidden('create_metademands', ['value' => 1]);
                        echo "<a href='#' class='metademand_middle_button' onclick='window.print();return false;'>";
                        echo "<i class='fas fa-2x fa-print' style='color:#e3e0e0;'></i>";
                        echo "</a>";

                        if ($metademands->fields['is_order'] == 1) {
                            if (!countElementsInTable(
                                "glpi_plugin_metademands_basketlines",
                                ["plugin_metademands_metademands_id" => $metademands->fields['id'],
                                    "users_id" => Session::getLoginUserID()]
                            )) {
                                $title = "<i class='fas fa-plus'></i>&nbsp;";
                                $title .= _sx('button', 'Add to basket', 'metademands');
                                echo Html::submit($title, ['name' => 'add_to_basket',
                                    'id' => 'add_to_basket',
                                    'class' => 'metademand_next_button btn btn-primary']);
                            } else {
                                echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
                                echo "</div>";
                                $title = "<i class='fas fa-save'></i>&nbsp;";
                                $title .= _sx('button', 'Validate your basket', 'metademands');
                                echo Html::submit($title, ['name' => 'next_button',
                                    'form' => '',
                                    'id' => 'submitjob',
                                    'class' => 'metademand_next_button btn btn-success']);
                                $ID = $metademands->fields['id'];
                                echo "<script>
                          $('#submitjob').click(function() {
                             var meta_id = {$ID};
                             if(typeof tinyMCE !== 'undefined'){
                                tinyMCE.triggerSave();
                             }
                             jQuery('.resume_builder_input').trigger('change');
                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                             $('#ajax_loader').show();
                             $.ajax({
                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                                   type: 'POST',
                                   data: $('form').serializeArray(),
                                   success: function(response){
                                       $('#ajax_loader').hide();
                                       if (response == 1) {
                                          document.location.reload();
                                       } else {
                                          window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=create_metademands';
                                       }
                                    },
                                   error: function(xhr, status, error) {
                                      console.log(xhr);
                                      console.log(status);
                                      console.log(error);
                                    } 
                                });
                          });
                        </script>";
                            }
                        } else {
                            //                     if ($use_as_step == 0) {
                            //                        echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
                            //                        echo "</div>";
                            //
                            //                        $title = "<i class='fas fa-save' data-hasqtip='0' aria-hidden='true'></i>&nbsp;";
                            //                        $title .= _sx('button', 'Save & Post', 'metademands');
                            //                        echo Html::submit($title, ['name'  => 'next_button',
                            //                                                   'form'  => '',
                            //                                                   'title' => _sx('button', 'Save & Post', 'metademands'),
                            //                                                   'id'    => 'submitjob',
                            //                                                   'class' => 'btn btn-success metademand_next_button']);
                            //
                            //                        $ID   = $metademands->fields['id'];
                            //                        $name = Toolbox::addslashes_deep($metademands->fields['name']) . "_" . $_SESSION['glpi_currenttime'] . "_" . $_SESSION['glpiID'];
                            //                        echo "<script>
                            //                       $('#submitjob').click(function() {
                            //                          var meta_id = {$ID};
                            //                          if(typeof tinyMCE !== 'undefined'){
                            //                                tinyMCE.triggerSave();
                            //                             }
                            //                          jQuery('.resume_builder_input').trigger('change');
                            //                          $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                            //                          $('#ajax_loader').show();
                            //                          arrayDatas = $('form').serializeArray();
                            //                          arrayDatas.push({name: \"save_form\", value: true});
                            //                          arrayDatas.push({name: \"form_name\", value: '$name'});
                            //                          $.ajax({
                            //                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
                            //                                type: 'POST',
                            //                                data: arrayDatas,
                            //                                success: function(response){
                            //                                 },
                            //                                error: function(xhr, status, error) {
                            //                                   console.log(xhr);
                            //                                   console.log(status);
                            //                                   console.log(error);
                            //                                 }
                            //                             });
                            //                          $.ajax({
                            //                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                            //                                type: 'POST',
                            //                                data: $('form').serializeArray(),
                            //                                success: function(response){
                            //                                    $('#ajax_loader').hide();
                            //                                    if (response == 1) {
                            //                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=2';
                            //                                    } else {
                            //                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=create_metademands';
                            //                                    }
                            //                                 },
                            //                                error: function(xhr, status, error) {
                            //                                   console.log(xhr);
                            //                                   console.log(status);
                            //                                   console.log(error);
                            //                                 }
                            //                             });
                            //                       });
                            //                     </script>";
                            //                     }
                        }
                    } else {
                        echo "<br>";
                        $title = "<i class='fas fa-chevron-right' data-hasqtip='0' aria-hidden='true'></i>&nbsp;";
                        $title .= __('Next');
                        echo Html::submit($title, ['name' => 'next', 'class' => 'btn btn-primary metademand_next_button']);
                    }
                    echo "</div>";
                    echo "</div>";
                }
                //            if ($metademands->getField('is_order')) {
                //               PluginMetademandsBasketline::constructBasket($metademands_id, $line['form'], $preview);
                //            }
            }
        } else {
            echo "</div>";
            echo "<div class='center first-bloc'>";
            echo "<div class=\"row\">";
            echo "<div class=\"bt-feature col-md-12 \">";
            echo __('No item to display');
            echo "</div></div>";
            echo "<div class=\"row\">";
            echo "<div class=\"bt-feature col-md-12 \">";
            echo Html::submit(__('Previous'), ['name' => 'previous', 'class' => 'btn btn-primary']);
            echo Html::hidden('previous_metademands_id', ['value' => $metademands_id]);
            echo "</td>";
            echo "</tr>";
            echo "</div></div>";
        }
    }

    /**
     * @param array $line
     * @param       $metademands_data
     * @param bool $preview
     * @param int $itilcategories_id
     */
    public static function constructForm($metademands_id, $metademands_data, $step, $line = [], $preview = false, $itilcategories_id = 0, $seeform = false, $current_ticket = 0, $meta_validated = 1)
    {
        global $CFG_GLPI;
        $url = $CFG_GLPI['root_doc'] . PLUGIN_METADEMANDS_DIR_NOFULL . "/front/wizard.form.php";
        $user_id = Session::getLoginUserID();
        if (isset($_SESSION['plugin_metademands'][$user_id]['redirect_wizard'])) {
            unset($_SESSION['plugin_metademands'][$user_id]);
            Html::redirect($url);
        }
        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($metademands_id);

        $paramUrl = "";
        if ($current_ticket > 0 && !$meta_validated) {
            $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
        }

        $ranks = [];
        $block_current_id_stepform = $_SESSION['plugin_metademands']['block_id'] ?? 99999999;
        if ($block_current_id_stepform != 99999999) {
            if (!PluginMetademandsStep::canSeeBlock($metademands_id, $block_current_id_stepform) && $preview == false) {
                Session::addMessageAfterRedirect(__('You do not have access to the form', 'metademands'));
                Html::redirect($url);
            }
        }
        $lineForStepByStep = [];
        $data_form = [];
        $values_saved = $_SESSION['plugin_metademands']['fields'] ?? [];
        foreach ($line as $fields) {
            $ranks[] = $fields["rank"];
            if ($metademands->fields['step_by_step_mode'] == 1 && $fields['rank'] < $block_current_id_stepform) {
                $lineForStepByStep[$fields['id']] = $fields;
                if (isset($values_saved[$fields['id']])) {
                    $data_form[$fields['id']] = $values_saved[$fields['id']];
                }
            }
        }
        $allranks = array_unique($ranks);

        $allfields = [];

        $use_as_step = 0;
        //TODO v3.3
        if ($metademands->fields['step_by_step_mode'] == 1) {
            $use_as_step = 1;
        }
        if ($preview || $seeform) {
            $use_as_step = 0;
        }


        $hidden_blocks = [];
        $all_hidden_blocks = [];

        foreach ($allranks as $rank) {
            foreach ($line as $fields) {
                if ($rank == $fields["rank"]) {
                    $allfields[$rank][] = $fields;
                    if ($use_as_step == 1) {

//                        if (isset($fields['options'])) {
//                            $check_values = $fields['options'];
//                            if (is_array($check_values)) {
//                                if (count($check_values) > 0) {
//                                    foreach ($check_values as $idc => $check_value) {
//                                        if (isset($check_value['hidden_block'])) {
//                                            $allhidden = $check_value['hidden_block'];
//                                            if ($allhidden > 0) {
//                                                $hidden_blocks[$rank][] = $allhidden;
//                                                $all_hidden_blocks[] = $allhidden;
//                                                $hidden_blocks[$rank] = array_filter($hidden_blocks[$rank]);
//                                            }
//                                        }
//                                    }
//                                }
//                            }
//                        }
                    }
                }
            }
        }

        $all_hidden_blocks = array_unique($all_hidden_blocks);

        $count = 0;
        $columns = 2;
        $cpt = 0;

        if (count($line)) {
            if ($use_as_step == 0) {
                echo "<div class='tab-nostep'>";
                $cpt = 1;
            }
            echo Html::scriptBlock('$(document).keypress(function(e){
                            if (e.which == 13){
                                var target = $(e.target);
                                if(!target.is("textarea")) {
                                     $("#submitjob").click();
                                     $("#nextBtn").click();
                                }
                                
                            }
                });');
            $block_current_id_stepform = $_SESSION['plugin_metademands']['block_id'] ?? 99999999;

            foreach ($allfields as $blocks => $line) {
                if ($use_as_step == 1 && $metademands->fields['is_order'] == 0) {
                    if (!in_array($blocks, $all_hidden_blocks)) {
                        //                  continue;

                        echo "<div class='tab-step'>";
                        $cpt++;
                    } else {
                        //                  echo "<div class='tab-sc-child-" . $blocks . "'>";
                    }
                }
                $style = 'padding: 0.5rem 0.5rem;';
                $style_left_right = 'padding: 0.5rem 0.5rem;';
                $keys = array_keys($line);
                $keyIndexes = array_flip($keys);

                $rank = $line[$keys[0]]['rank'];

                $style = "";

                // Color
                if ($preview) {
                    $color = PluginMetademandsField::setColor($rank);
                    $style = 'padding-top:5px;
                      padding-bottom:10px;
                      border-top :3px solid #' . $color . ';
                      border-left :3px solid #' . $color . ';
                      border-bottom :3px solid #' . $color . ';
                      border-right :3px solid #' . $color;
                    echo '<style type="text/css">
                       .preview-md-';
                    echo $rank;
                    echo ':before {
                         content: attr(data-title);
                         background: #';
                    echo $color . ";";
                    echo 'position: absolute;
                               padding: 0 20px;
                               color: #fff;
                               right: 0;
                               top: 0;
                               z-index:1000;
                           }
                          </style>';
                }
                if (isset($metademands->fields['background_color'])
                    && !empty($metademands->fields['background_color'])) {
                    $background_color = $metademands->fields['background_color'];
                    $style .= ";background-color:" . $background_color . ";";
                }

                echo "<div bloc-id='bloc" . $rank . "' style='$style' class='card tab-sc-child-" . $rank . "'>";

                if ($line[$keys[0]]['type'] == 'title-block') {

                    $color = self::hex2rgba($line[$keys[0]]['color'], "0.03");
                    $style_background = "style='background-color: $color!important;border-color: " . $line[$keys[0]]['color'] . "!important;border-radius: 0;'";

                    if ($preview) {
                        echo "<div class=\"card-header preview-md preview-md-$rank\" $style_background data-title='" . $rank . "' >";
                    } else {
                        echo "<div class='card-header' $style_background>";
                    }
                    echo "<h2 class=\"card-title\"><span style='color:" . $line[$keys[0]]['color'] . ";font-weight: normal;'>";

                    if (empty($label = PluginMetademandsField::displayField($line[$keys[0]]['id'], 'name'))) {
                        $label = $line[$keys[0]]['name'];
                    }

                    echo $label;
                    $config_link = "";
                    if (Session::getCurrentInterface() == 'central' && $preview) {
                        $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL('PluginMetademandsField') . "?id=" . $line[$keys[0]]['id'] . "'>";
                        $config_link .= "<i class='fas fa-wrench'></i></a>";
                    }
                    echo $config_link;
                    if (isset($line[$keys[0]]['label2']) && !empty($line[$keys[0]]['label2'])) {
                        echo "&nbsp;";
                        if (empty($label2 = PluginMetademandsField::displayField($line[$keys[0]]['id'], 'label2'))) {
                            $label2 = $line[$keys[0]]['label2'];
                        }
                        Html::showToolTip(
                            Glpi\RichText\RichText::getSafeHtml($label2),
                            ['awesome-class' => 'fa-info-circle']
                        );
                    }
                    echo "<i id='up" . $rank . "' class='fa-1x fas fa-chevron-up pointer' style='right:40px;position: absolute;color:" . $line[$keys[0]]['color'] . ";'></i>";
                    $rand = mt_rand();
                    echo Html::scriptBlock("
                     var myelement$rand = '#up" . $rank . "';
                     var bloc$rand = 'bloc" . $rank . "';
                     $(myelement$rand).click(function() {     
                         if($('[bloc-hideid =' + bloc$rand + ']:visible').length) {
                             $('[bloc-hideid =' + bloc$rand + ']').hide();
                             $(myelement$rand).toggleClass('fa-chevron-up fa-chevron-down');
                         } else {
                             $('[bloc-hideid =' + bloc$rand + ']').show();
                             $(myelement$rand).toggleClass('fa-chevron-down fa-chevron-up');
                         }
                     });");
                    echo "</span></h2>";
                    echo "</div>";
                    if (!empty($line[$keys[0]]['comment'])) {
                        if (empty($comment = PluginMetademandsField::displayField($line[$keys[0]]['id'], 'comment'))) {
                            $comment = $line[$keys[0]]['comment'];
                        }
                        $comment = htmlspecialchars_decode(stripslashes($comment));
                        echo "<div class='card-body'><i>" . $comment . "</i></div>";
                    }
                }
                if ($preview) {
                    echo "<div class='card-body' bloc-hideid='bloc" . $rank . "'>";
                } else {
                    echo "<div class='card-body' bloc-hideid='bloc" . $rank . "'>";
                }
                if ($preview) {
                    echo "<div class=\"row preview-md preview-md-$rank\" data-title='" . $rank . "'>";
                } else {
                    echo "<div class=\"row\" style='$style'>";
                }
                foreach ($line as $key => $data) {
                    $config_link = "";
                    if (Session::getCurrentInterface() == 'central' && $preview) {
                        $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL('PluginMetademandsField') . "?id=" . $data['id'] . "'>";
                        $config_link .= "<i class='fas fa-wrench'></i></a>";
                    }
                    // Manage ranks
                    if (isset($keyIndexes[$key])
                        && isset($keys[$keyIndexes[$key] - 1])
                        && $data['rank'] != $line[$keys[$keyIndexes[$key] - 1]]['rank']) {
                        //End bloc-hideid
                        echo "</div>";

                        echo "</div>";
                        echo "</div>";
                        echo "<div bloc-id='bloc" . $data["rank"] . "'>";

                        // Title block field
                        if ($data['type'] == 'title-block') {
                            if ($preview) {
                                $rank = $data["rank"];
                                $color = PluginMetademandsField::setColor($rank);
                                $style = 'padding-top:5px;
                                          padding-bottom:10px;
                                          border-top :3px solid #' . $color . ';
                                          border-left :3px solid #' . $color . ';
                                          border-right :3px solid #' . $color;
                                echo '<style type="text/css">
                                        .preview-md-';
                                echo $rank;
                                echo ':before {
                                                 content: attr(data-title);
                                                 background: #';
                                echo $color . ";";
                                echo 'position: absolute;
                                       padding: 0 20px;
                                       color: #fff;
                                       right: 0;
                                       top: 0;
                                   }
                                  </style>';
                                echo "<div class=\"row preview-md preview-md-$rank\" data-title='" . $rank . "' style='$style'>";
                            } else {
                                echo "<div>";
                            }
                            echo "<br><h4 class=\"alert alert-light\"><span style='color:" . $data['color'] . ";'>";

                            if (empty($label = PluginMetademandsField::displayField($data['id'], 'name'))) {
                                $label = $data['name'];
                            }

                            echo $label;
                            echo $config_link;
                            if (isset($data['label2']) && !empty($data['label2'])) {
                                echo "&nbsp;";
                                if (empty($label2 = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                                    $label2 = $data['label2'];
                                }
                                Html::showToolTip(
                                    Glpi\RichText\RichText::getSafeHtml($label2),
                                    ['awesome-class' => 'fa-info-circle']
                                );
                            }
                            echo "<i id='up" . $data["rank"] . "' class='fa-1x fas fa-chevron-up pointer' style='right:40px;position: absolute;color:" . $data['color'] . ";'></i>";
                            $rand = mt_rand();
                            echo Html::scriptBlock("
                                 var myelement$rand = '#up" . $data["rank"] . "';
                                 var bloc$rand = 'bloc" . $data["rank"] . "';
                                 $(myelement$rand).click(function() {     
                                     if($('[bloc-hideid =' + bloc$rand + ']:visible').length) {
                                         $('[bloc-hideid =' + bloc$rand + ']').hide();
                                         $(myelement$rand).toggleClass('fa-chevron-up fa-chevron-down');
                                     } else {
                                         $('[bloc-hideid =' + bloc$rand + ']').show();
                                         $(myelement$rand).toggleClass('fa-chevron-down fa-chevron-up');
                                     }
                                 });");
                            echo "</span></h4>";
                            if (!empty($data['comment'])) {
                                if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
                                    $comment = $data['comment'];
                                }
                                $comment = htmlspecialchars_decode(stripslashes($comment));
                                echo "<label><i>" . $comment . "</i></label>";
                            }

                            echo "</div>";
                            // Other fields
                        }

                        echo "<div bloc-hideid='bloc" . $data["rank"] . "'>";

                        if ($preview) {
                            $rank = $data['rank'];
                            $color = PluginMetademandsField::setColor($data['rank']);
                            echo '<style type="text/css">
                           .preview-md-';
                            echo $rank;
                            echo ':before {
                             content: attr(data-title);
                             background: #';
                            echo $color . ";";
                            echo 'position: absolute;
                                   padding: 0 20px;
                                   color: #fff;
                                   right: 0;
                                   top: 0;
                               }
                              </style>';
                            $style = 'padding-top:5px;
                            padding-bottom:10px;
                            border-top :3px solid #' . $color . ';
                            border-left :3px solid #' . $color . ';
                            border-right :3px solid #' . $color;
                            echo "<div class=\"row preview-md preview-md-$rank\" data-title='" . $rank . "' style='$style'>";
                        } else {
                            $background_color = "";
                            if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                                $background_color = $meta->fields['background_color'];
                            }
                            echo "<div class=\"row class1\" style='background-color: " . $background_color . ";padding: 0.5rem 0.5rem;'>";
                        }

                        $count = 0;
                    }

                    // If values are saved in session we retrieve it
                    if (isset($_SESSION['plugin_metademands']['fields'])) {
                        foreach ($_SESSION['plugin_metademands']['fields'] as $id => $value) {
                            if (strval($data['id']) === strval($id)) {
                                $data['value'] = $value;
                            } elseif ($data['id'] . '-2' === $id) {
                                $data['value-2'] = $value;
                            }
                        }
                    }

                    // Title field
                    if ($data['type'] == 'title') {
                        $color = self::hex2rgba($data['color'], "0.03");
                        $style_background = "style='background-color: $color!important;border-color:" . $data['color'] . "!important;border-radius: 0;'";
                        echo "<div id-field='field" . $data["id"] . "' class='card-header' $style_background>";
                        echo "<br><h2 class='card-title'><span style='color:" . $data['color'] . ";font-weight: normal;'>";

                        if (empty($label = PluginMetademandsField::displayField($data['id'], 'name'))) {
                            $label = $data['name'];
                        }

                        echo $label;
                        echo $config_link;
                        if (isset($data['label2']) && !empty($data['label2'])) {
                            echo "&nbsp;";
                            if (empty($label2 = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                                $label2 = $data['label2'];
                            }
                            Html::showToolTip(
                                Glpi\RichText\RichText::getSafeHtml($label2),
                                ['awesome-class' => 'fa-info-circle']
                            );
                        }
                        echo "</span></h2>";
                        echo "</div>";
                        if (!empty($data['comment'])) {
                            if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
                                $comment = $data['comment'];
                            }
                            $comment = htmlspecialchars_decode(stripslashes($comment));
                            echo "<div class='card-body'><i>" . $comment . "</i></div>";
                        }

                        $count = $count + $columns;

                        // Other fields
                    } elseif ($data['type'] != 'title-block') {
                        $style = "";
                        $class = "";
                        if ($data['row_display'] == 1 && $data['type'] == "link") {
                            $class = "center";
                        }
                        //Add possibility to hide field
                        if ($data['type'] == 'dropdown_meta'
                            && $data['item'] == "ITILCategory_Metademands"
                            && Session::getCurrentInterface() != 'central') {
                            $class .= " itilmeta";
                            //xaca to delete ??
                            //echo Html::hidden('_tickettemplates_id', ['value' => $tt->fields['id']]);
                        }
                        if ($data['type'] == 'informations') {
                            $color = $data['color'];
                            $style = "";
                            //                  $class = "metademands_wizard_informations";
                            $class = "alert d-flex alert-warning alert-dismissible fade show";  //alert-important alert-warning alert-dismissible
                        } else {
                            $class = "form-group ";
                        }
                        $bottomclass = "";
                        if ($data['type'] != 'informations') {
                            $bottomclass = "md-bottom";
                        }
                        if ($data['row_display'] == 1) {
                            echo "<div id-field='field" . $data["id"] . "' $style class=\"$bottomclass $class\">";
                            if ($data['type'] == 'informations') {
                                echo "<a href='#' class='close' data-bs-dismiss='alert' aria-label='close'>&times;</a>";
                            }
                            $count++;
                        } else {
                            echo "<div id-field='field" . $data["id"] . "' $style class=\"col-md-5 $bottomclass $class\">";
                            if ($data['type'] == 'informations') {
                                echo "<a href='#' class='close' data-bs-dismiss='alert' aria-label='close'>&times;</a>";
                            }
                        }
                        //see fields
                        PluginMetademandsField::getFieldType($metademands_data, $data, $preview, $config_link, $itilcategories_id);

                        // Label 2 (date interval)
                        if (!empty($data['label2'])
                            && $data['type'] != 'link') {
                            $required = "";
                            $required_icon = "";
                            if ($data['is_mandatory']) {
                                $required = "required style='color:red'";
                                $required_icon = " * ";
                            }

                            if ($data['type'] == 'datetime_interval' || $data['type'] == 'date_interval') {
                                echo "</div><div class=\"form-group col-md-5 md-bottom\">";
                            } else {
                                $class = "";
                                echo "<div class=\"form-group\" style='padding: 10px 15px;'>";
                            }
                            if (empty($label2 = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                                $label2 = htmlspecialchars_decode(stripslashes($data['label2']));
                            }
                            $style = "";
                            if ($data['type'] != 'informations') {
                                $style = "style='background: #EEE;padding: 10px'";
                            }

                            if ($data['type'] != 'datetime_interval' && $data['type'] != 'date_interval') {
                                echo "<br><label class='col-form-label' $style>" . Glpi\RichText\RichText::getSafeHtml($label2) . "</label>";
                            } else {
                                echo "<label $required for='field[" . $data['id'] . "-2]' class='col-form-label metademand-label'>" . RichText::getTextFromHtml($label2) . $required_icon . "</label>";
                            }
                            $value2 = '';
                            if (isset($data['value-2'])) {
                                $value2 = $data['value-2'];
                            }

                            echo "<span style='width: 50%!important;display: -webkit-box;'>";
                            switch ($data['type']) {
                                case 'date_interval':
                                    Html::showDateField("field[" . $data['id'] . "-2]", ['value' => $value2, 'required' => ($data['is_mandatory'] ? "required" : "")]);
                                    $count++; // If date interval : pass to next line
                                    break;
                                case 'datetime_interval':
                                    Html::showDateTimeField("field[" . $data['id'] . "-2]", ['value' => $value2, 'required' => ($data['is_mandatory'] ? "required" : "")]);
                                    $count++; // If date interval : pass to next line
                                    break;
                            }
                            echo "</span>";
                            if ($data['type'] != 'datetime_interval' && $data['type'] != 'date_interval') {
                                echo "</div>";
                            }
                        }
                        echo "</div>";
                    }

//                    if ($data['type'] == 'title-block') {
//                        $count--;
//                    }
                    // If next field is date interval : pass to next line
//                    if (isset($keyIndexes[$key])
//                        && isset($keys[$keyIndexes[$key] + 1])
//                        && ($line[$keys[$keyIndexes[$key] + 1]]['type'] == 'datetime_interval' || $line[$keys[$keyIndexes[$key] + 1]]['type'] == 'date_interval')) {
//                        $count++;
//                    }

//                    $count++;

                    // Next row
                    if ($count > $columns) {
                        if ($preview) {
                            $color = PluginMetademandsField::setColor($data['rank']);
                            $style_left_right = 'padding-bottom:10px;
                                       border-left :3px solid #' . $color . ';
                                       border-right :3px solid #' . $color;
                        }

                        echo "</div>";

                        $background_color = "";
                        if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                            $background_color = $meta->fields['background_color'];
                        }
                        if ($preview) {
                            echo "<div class=\"row class2\" style='background-color: " . $background_color . ";'>";
                        } else {
                            echo "<div class=\"row class2\" style='background-color: " . $background_color . ";$style_left_right'>";
                        }

                        $count = 0;
                    }
                }

                echo "</div>";
                echo "</div>";
                echo "</div>";

//                if ($preview) {
//                    $color = PluginMetademandsField::setColor($line[$keys[count($keys) - 1]]['rank']);
//                    echo "<div class=\"row\" style='border-bottom: 3px solid #" . $color . ";' >";
//                    echo "</div>";
//                }

                // Fields linked


                foreach ($line as $data) {
                    PluginMetademandsFieldOption::fieldsLinkScript($data);

                    PluginMetademandsFieldOption::fieldsHiddenScript($data);
//
                    PluginMetademandsFieldOption::blocksHiddenScript($data);
//
                    PluginMetademandsFieldOption::checkboxScript($data);
                }

                if ($use_as_step == 1 && $metademands->fields['is_order'] == 0) {
                    if (!in_array($blocks, $all_hidden_blocks)) {
                        echo "</div>";
                    }
                }
            }
            if ($use_as_step == 0) {
                echo "</div>";
            }

            if ($metademands->fields['is_order'] == 0
                && !$preview
                && (!$seeform
                    || (isset($options['resources_id'])
                        && $options['resources_id'] > 0)
                    || ($current_ticket > 0
                        && ((!$meta_validated
                                && $metademands->fields['can_update'] == true) ||
                            ($meta_validated
                                && $metademands->fields['can_clone'] == true))
                        && Session::haveRight('plugin_metademands_updatemeta', READ)))
                && $step - 1 >= count($metademands_data)) {
                echo "<br>";
                echo "<div class=\"form-sc-group\">";
                echo "<div class='center'>";
                //               if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
                //                  echo Html::hidden('_tickettemplates_id', ['value' => $tt->fields['id']]);
                //                  echo Html::hidden('_predefined_fields', ['value' => Toolbox::prepareArrayForInput($predefined_fields)]);
                //               }
                //               if ($config->getBypassCategories() == 0) {
                //                  echo Html::hidden('itilcategories_id', ['value' => $category_id]);
                //               }
                //               echo Html::hidden('type', ['value' => $type]);
                //               echo Html::hidden('add', ['value' => 1]);
                echo "<div style='overflow:auto;'>";

                echo "<button type='button' id='prevBtn' class='btn btn-primary ticket-button' onclick='nextPrev(-1)'>";
                echo "<i class='ti ti-chevron-left'></i>&nbsp;" . __('Previous', 'metademands') . "</button>";
                //TODO v3.3
                //                if ($metademands->fields['step_by_step_mode'] == 1
                //                ) {
                //                    $msg = PluginMetademandsStep::getMsgForNextBlock($metademands->getID(), 1);
                //                    if ($msg) {
                //                        echo "<div class='alert alert-info center'>";
                //                        echo $msg;
                //                        echo "</div>";
                //                    }
                //                }
                echo "&nbsp;<button type='button' id='nextBtn' class='btn btn-primary ticket-button'  onclick='nextPrev(1)'>";
                echo __('Next', 'metademands') . "&nbsp;<i class='ti ti-chevron-right'></i></button>";

                //TODO v3.3
                if ($use_as_step == 1) {
                    echo "<div id='nextMsg' class='alert alert-info center'>";
                    echo "</div>";
                }
                echo "</span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
//                echo "<br>";
                //            }


                //Circles which indicates the steps of the form:
                echo "<div style='text-align:center;margin-top:20px;'>";
                //            $cpt--;

                if ($cpt > 1) {
                    for ($j = 1; $j <= $cpt; $j++) {
                        echo "<span class='step_wizard'></span>";
                    }
                } else {
                    echo "<span class='step_wizard' style='display: none'></span>";
                }


                echo "</div>";


                $nexttitle = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
                $title = _sx('button', 'Save & Post', 'metademands');
                $submittitle = "<i class=\"fas fa-save\"></i>&nbsp;" . $title;
                $submitmsg = "";
                $nextsteptitle = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
                $title = _sx('button', 'Save & send to another user / group', 'metademands');
                $submitsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . $title;
                $submitstepmsg = "";


                echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
                echo "</div>";

                $ID = $metademands->fields['id'];
                $name = Toolbox::addslashes_deep($metademands->fields['name']) . "_" . $_SESSION['glpi_currenttime'] . "_" . $_SESSION['glpiID'];

                $json_hidden_blocks = json_encode($hidden_blocks);
                $alert = __('Thanks to fill mandatory fields', 'metademands');
                $group_user = new Group_User();
                $groups_users = $group_user->find(['users_id' => Session::getLoginUserID()]);
                $groups = [];
                foreach ($groups_users as $gu) {
                    $groups[] = $gu['groups_id'];
                }
                $list_blocks = [];
                $list_blocks2 = [];
                if ($use_as_step == 1) {
                    $step = new PluginMetademandsStep();
                    $steps = [];
                    if (!empty($groups)) {
                        $steps = $step->find(['plugin_metademands_metademands_id' => $ID,
                            'groups_id' => $groups]);

                        foreach ($steps as $s) {
                            $list_blocks[] = $s['block_id'];
                        }
                    }
                    $step2 = new PluginMetademandsStep();
                    if ($steps2 = $step2->find(['plugin_metademands_metademands_id' => $ID])) {
                        foreach ($steps2 as $s) {
                            $list_blocks2[] = $s['block_id'];
                        }
                    }
                    $field = new PluginMetademandsField();
                    $fields = $field->find(["plugin_metademands_metademands_id" => $ID]);
                    $blocks = [];

                    foreach ($fields as $f) {
                        $blocks[] = $f["rank"];
                    }
//                    if (count($list_blocks2) > 0) {
                    foreach ($blocks as $block) {
                        if (!in_array($block, $list_blocks2)) {
                            $list_blocks[] = $block;
                        }
                    }
//                    }

                    if (!isset($steps) || (is_array($steps) && count($steps) == 0)) {
                    }
                    if (count($steps) == 0) {
                        $submitsteptitle = $submittitle;
                    }
                    $list_blocks = array_unique($list_blocks);
                }

                if (!empty($data_form)) {
                    $modal_html = '';
                    $parent_fields = $metademands->formatFields($lineForStepByStep, $metademands_id, [$metademands_id => $data_form], []);
                    $form = new PluginMetademandsStepform();
                    if ($form->getFromDBByCrit(['id' => $_SESSION['plugin_metademands']['plugin_metademands_stepforms_id']])) {
                        $previousUser = new User();
                        if ($previousUser->getFromDBByCrit(['id' => $form->fields['users_id']])) {
                            $lbl = __('Previous user', 'metademands');
                            $modal_html .= "
                        <table class='tab_cadre_fixe' style='width: 100%;'>
                            <tr class='even'>
                                <td class='title'> $lbl : " . $previousUser->fields['realname'] . " " . $previousUser->fields['firstname'] . "</td>
                            </tr>
                        </table>";
                        }
                    }
                        $modal_html .= Glpi\RichText\RichText::getSafeHtml($parent_fields['content']);
                        $title = __('Previous data edited', 'metademands');
                        $setting_dialog = json_encode($modal_html);
                        echo Html::scriptBlock("$(function() {
                                                        glpi_html_dialog({
                                                             title: '$title',
                                                             body: {$setting_dialog},
                                                             dialogclass: 'modal-lg',
                                                        });
                                                    });");


                    if (isset($_SESSION['plugin_metademands']['hidden_blocks'])) {
                        if (is_array($_SESSION['plugin_metademands']['hidden_blocks'])) {
                            $hidden_blocks = $_SESSION['plugin_metademands']['hidden_blocks'];
                            $script = "";
                            foreach ($hidden_blocks as $hidden_block) {
                                $script .= "$('div[bloc-id=\"bloc$hidden_block\"]').hide();";
                            }
                            echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
                        }
                    }
                }


                if (isset($_SESSION['plugin_metademands']['plugin_metademands_stepforms_id'])) {
                    echo Html::hidden('plugin_metademands_stepforms_id', ['value' => $_SESSION['plugin_metademands']['plugin_metademands_stepforms_id']]);
                }

                //Html::scriptBlock('var step = sessionStorage.currentStep; return step;');
                $block_id = $_SESSION['plugin_metademands']['block_id'] ?? 0;

                //TODO v3.3
//                if ($metademands->fields['step_by_step_mode'] == 1
//                ) {
//                    $submitmsg = $submitstepmsg =  __('Your form will be redirected to another group of people who will complete the following information.', 'metademands');
//
//                    $msg = PluginMetademandsStep::getMsgForNextBlock($metademands->getID(), $block_id);
//                    if ($msg) {
//                        $submitmsg = $msg;
//                        $submitstepmsg = $msg;
//                    }
//                }

                echo "<span id = 'modalgroupspan'>";
                echo "</span>";

                $stepConfig = new PluginMetademandsConfigstep();
                $res = $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id]);
                $modal = true;
                $updateStepform = 0;
                if (!$stepConfig->fields['link_user_block']
                    && !$stepConfig->fields['multiple_link_groups_blocks']) {
                    $modal = false;
                } else if($block_current_id_stepform != 99999999){
                    $canSeeNextBlock = PluginMetademandsStep::canSeeBlock($metademands_id, $block_current_id_stepform + 1);
                    if(!$canSeeNextBlock) {
                        $modal = true;
                        $updateStepform = 1;
                    }
                }
                echo "<script>
                  var nexttitle = '$nexttitle';
                  var submittitle = '$submittitle';
                  var submitmsg = '$submitmsg'; 
                  var use_as_step = '$use_as_step';
                  var nextsteptitle = '$nextsteptitle';
                  var submitsteptitle = '$submitsteptitle';
                  var submitstepmsg = '$submitstepmsg';
                  var hiddenblocs = {$json_hidden_blocks};
                  var msg = '$alert';
                  var firstnumTab = 0;
                  var currentTab = 0; // Current tab is set to be the first tab (0)
                  findFirstTab($block_id);
                  showTab(currentTab, nexttitle, submittitle, submitmsg); // Display the current tab
                  
                  function showTab(n,create = false, submittitle, submitmsg) {
                     // This function will display the specified tab of the form...
                     //document.getElementById('nextMsg').style.display = 'none';
                     if (use_as_step == 1) {
                        var x = document.getElementsByClassName('tab-step');
                     } else {
                        var x = document.getElementsByClassName('tab-nostep');
                     }
                   
                     x[n].style.display = 'block';
                     //... and fix the Previous/Next buttons:
                     if (n == firstnumTab) {
                        document.getElementById('prevBtn').style.display = 'none';
                     } else {
                        document.getElementById('prevBtn').style.display = 'inline';
                     }

                     document.getElementById('nextBtn').innerHTML = nexttitle;
                     if (n == (x.length - 1) || create == true) {
                        document.getElementById('nextBtn').innerHTML = submittitle;
//                        document.getElementById('nextMsg').style.display = 'block';
//                        document.getElementById('nextMsg').innerHTML = submitmsg;
                     }
                     
                     //... and run a function that will display the correct step indicator:
                     if (use_as_step == 1) {
                        fixStepIndicator(n);
                     }
                      //sessionStorage.setItem('blocks', JSON.stringify(loaded_blocks));
                       //var storedBlocks = JSON.parse(sessionStorage.getItem('loaded_blocks'));
                       //console.log(storedBlocks);
                     //hide hidden blocks
                  //                     if (n == 0) {
                  //                        var output = Object.entries(hiddenblocs).map(([key, value]) => ({key,value}));
                  //                        for(var j = 0; j < output.length; j++) {
                  //                           var values = output[j].value;
                  //                          for(var v = 0; v < values.length; v++) {
                  //                             var y = document.getElementsByClassName('tab-sc-child-' + values[v]);
                  //                             if (y[n] !== undefined) {
                  //                                 y[n].style.display = 'none';
                  //                             }
                  //                          }
                  //                        }
                  //                     }
                  }
                  function findFirstTab(block_id) {
                      if (use_as_step == 1) {
                        var x = document.getElementsByClassName('tab-step');
                     } else {
                        var x = document.getElementsByClassName('tab-nostep');
                     }
                    
                      if(block_id > 0) {
                          bloc = x[currentTab].firstChild.getAttribute('bloc-id');
                          id_bloc = parseInt(bloc.replace('bloc',''));
                          while (block_id != id_bloc) {
                             currentTab = currentTab+1;
                             bloc = x[currentTab].firstChild.getAttribute('bloc-id');
                             id_bloc = parseInt(bloc.replace('bloc',''));
                          }
                          firstnumTab = currentTab;
                      }
                  }
                  function nextPrev(n) {
                      var modal = '$modal';
                     // This function will figure out which tab to display
                     if (use_as_step == 1) {
                        var x = document.getElementsByClassName('tab-step');
                     } else {
                        var x = document.getElementsByClassName('tab-nostep');
                     }
                     // Exit the function if any field in the current tab is invalid:
                     if (n == 1 && !validateForm()) return false;
                  
                     // Increase or decrease the current tab by 1:
                     nextTab = currentTab + n;
                     // Hide the current tab:
                     x[currentTab].style.display = 'none';
                  
                     // Increase or decrease the current tab by 1:
                     currentTab = currentTab + n;
                    
                     create = false;
                     createNow = false;
                     if (use_as_step == 1) {
                       
                         var finded = false;
                        
                         while (finded == false) {  

                            if(true) {
                               
                                if(x[currentTab] == undefined || x[currentTab].firstChild == undefined) {
                                     createNow = true;
                                     finded = true;
                                } else {
                                    if(x[currentTab].firstChild.style.display != 'none' ) {
                                         finded = true;
                                         nextTab = currentTab + n;
                                         while (nextTab >= firstnumTab && nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
                                             nextTab = nextTab + n;
                                         }
                                         if(nextTab >= x.length) {
                                             create = true;
                                         }
                                     } else {
                                         currentTab = currentTab + n;
                                     }
                                }
                            } else {
                                 finded = true;
                            }
                         }
                     }
                     // if you have reached the end of the form...
                     if (currentTab >= x.length || createNow) {
                  
                        document.getElementById('nextBtn').style.display = 'none';
                        // ... the form gets submitted:
                        var meta_id = {$ID};
                        if (typeof tinyMCE !== 'undefined') {
                           tinyMCE.triggerSave();
                        }
                        jQuery('.resume_builder_input').trigger('change');
                        $('select[id$=\"_to\"] option').each(function () {
                           $(this).prop('selected', true);
                        });
                        $('#ajax_loader').show();
                        arrayDatas = $('form').serializeArray();
                        arrayDatas.push({name: 'save_form', value: true});
                        arrayDatas.push({name: 'step', value: 2});
                        arrayDatas.push({name: 'form_name', value: '$name'});
                        
                        $.ajax({
                           url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php',
                           type: 'POST',
                           data: $('form').serializeArray(),
                           success: function (response) {
                              $('#ajax_loader').hide();
                              if (response == 1) {
                                 window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=2';
                              } else {
                                 $.ajax({
                                    url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
                                    type: 'POST',
                                    data: arrayDatas,
                                    success: function (response) {
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?" . $paramUrl . "metademands_id=' + meta_id + '&step=create_metademands';
                                    },
                                    error: function (xhr, status, error) {
                                       console.log(xhr);
                                       console.log(status);
                                       console.log(error);
                                    }
                                 });
                              }
                           },
                           error: function (xhr, status, error) {
                              console.log(xhr);
                              console.log(status);
                              console.log(error);
                           }
                        });
                  
                        return false;
                     }
                     var listBlock = [" . implode(",", $list_blocks) . "];
                     bloc = x[currentTab].firstChild.getAttribute('bloc-id');
                     id_bloc = parseInt(bloc.replace('bloc',''));
 
                     if(!listBlock.includes(id_bloc)) { 
                        var meta_id = {$ID};
                        if (typeof tinyMCE !== 'undefined') {
                           tinyMCE.triggerSave();
                        }
                        var updatestepform = '$updateStepform';                       
                        jQuery('.resume_builder_input').trigger('change');
                        $('select[id$=\"_to\"] option').each(function () {
                           $(this).prop('selected', true);
                        });
                        arrayDatas = $('form').serializeArray();
                        arrayDatas.push({name: 'block_id', value: id_bloc});  
                        arrayDatas.push({name: 'action', value: 'nextUser'});  
                        arrayDatas.push({name: 'update_stepform', value: updatestepform});  
                        if(modal == true) {
                            showModal(arrayDatas);
                        } else {
                            nextUser(arrayDatas);
                        }                      

                     } else {
                        showTab(currentTab,create, submittitle, submitmsg);
                     }
                     // Otherwise, display the correct tab:
                  }
                  
                  function nextUser(arrayDatas) {
                     $.ajax(
                            {
                                type: 'POST',
                                url: '" . $CFG_GLPI['root_doc'] . PLUGIN_METADEMANDS_DIR_NOFULL . "/ajax/nextUser.php" . "',
                                data: arrayDatas,
                                dataType: 'JSON',
                                success: function(ret) {
                                
                                    if(ret == 0) {
                                        location.href = '" . $CFG_GLPI['root_doc'] . PLUGIN_METADEMANDS_DIR_NOFULL . "/front/wizard.form.php" . "';
                                    } else {
                                        window.location.reload();
                                    }
                                },
                                error: function (xhr, status, error) {
                                       console.log(xhr);
                                       console.log(status);
                                       console.log(error);
                                    }
                            }
                        );
                  }
                  
                  function showModal(arrayDatas) {
                        $.ajax(
                            {
                                type : 'POST',
                                url :'" . $CFG_GLPI['root_doc'] . PLUGIN_METADEMANDS_DIR_NOFULL . "/ajax/showModal.php',
                                data : arrayDatas,
                                dataType : 'JSON',
                                success: function(response) {
                                try {

                                        // For modern browsers except IE:
                                        var event = new Event('show.bs.modal');
                                        
                                        } catch(err) {
                                        
                                        // If IE 11 (or 10 or 9...?) do it this way:
                                        
                                        // Create the event.
                                        var event = document.createEvent('Event');
                                        
                                }
//                                console.log(response);
//                                $('#modalgroupspan').html(response.html);
                                $('#modalgroupspan').html(response);
                                $.globalEval(response.js);
                                $('#modalgroup').modal('show');
                                document.dispatchEvent(event);
                                },
                                error : function (xhr, status, error) {
                                       console.log(xhr);
                                       console.log(status);
                                       console.log(error);
                                    }
                                
                            });
                                         
                  }
                 
                  
                  function validateForm() {
                     // This function deals with validation of the form fields
                     var x, y, i, valid = true, ko = 0, radioexists = 0, lengthr = 0;
                  
                     if (use_as_step == 1) {
                        var x = document.getElementsByClassName('tab-step');
                     } else {
                        var x = document.getElementsByClassName('tab-nostep');
                     }
                     y = x[currentTab].getElementsByTagName('input');
                     z = x[currentTab].getElementsByTagName('select');
                     w = x[currentTab].getElementsByTagName('textarea');
                  
                     // A loop that checks every input field in the current tab:
                     for (i = 0; i < y.length; i++) {
                  
                        // If a field is empty...
                        fieldname = y[i].name;
                        fieldtype = y[i].type;
                        fieldmandatory = y[i].required;
                        if (fieldname != '_uploader_filename[]'
                           && fieldname != '_uploader_content[]'
                           && fieldtype != 'file'
                            && fieldtype != 'informations'
                           //                                    && fieldtype != 'hidden'
                           && fieldmandatory == true) {
//                            console.log(fieldtype);
                           var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                  
                           if (res != 'none' && y[i].value == '') {
                              $('[name=\"' + fieldname + '\"]').addClass('invalid');
                              $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                              $('[for=\"' + fieldname + '\"]').css('color', 'red');
                              //hack for date
                              $('[name=\"' + fieldname + '\"]').next('input').addClass('invalid');
                              $('[name=\"' + fieldname + '\"]').next('input').attr('required', 'required');
                              ko++;
                           } else {
                              $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                              $('[name=\"' + fieldname + '\"]').removeAttr('required');
                              //hack for date
                              $('[name=\"' + fieldname + '\"]').next('input').removeClass('invalid');
                              $('[name=\"' + fieldname + '\"]').next('input').removeAttr('required');
                              $('[for=\"' + fieldname + '\"]').css('color', 'unset');
                           }
                  
                  //                           if (y[i].value == '') {
                  //                              // add an 'invalid' class to the field:
                  //                              y[i].classList.add('invalid');
                  //                              // and set the current valid status to false
                  //                              ko++;
                  //                           } else {
                  //                              y[i].classList.remove('invalid');
                  //                           }
                        }
                        if (y[i].type == 'radio' && fieldmandatory == true) {
                           if (y[i].checked) lengthr++;
                           var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                           if (res != 'none' && lengthr == 0) {
                              $('[name=\"' + fieldname + '\"]').addClass('invalid');
                              $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                              $('[for=\"' + fieldname + '\"]').css('color', 'red');
                              ko++;
                           } else {
                              $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                              $('[name=\"' + fieldname + '\"]').removeAttr('required');
                              $('[for=\"' + fieldname + '\"]').css('color', 'unset');
                              ko--;
                           }
                        }
                        if (y[i].type == 'checkbox' && fieldmandatory == true) {
                           var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                           if (res != 'none') {
                              result = fieldname.substring(0, fieldname.length - 3);
                              var boxes =  $('[check*=\"' + result + '\"]:checked');
                              if (boxes.length > 0) {
                                 $('[check*=\"' + result + '\"]').removeClass('invalid');
                                 $('[check*=\"' + result + '\"]').removeAttr('required');
                              } else {
                                 $('[check*=\"' + result + '\"]').addClass('invalid');
                                 $('[check*=\"' + result + '\"]').attr('required', 'required');
                                 ko++;
                              }
                           }
                        }
                     }
                  
                     //for textarea
                     if (w.length > 0) {
                        for (i = 0; i < w.length; i++) {
                           fieldmandatory = w[i].required;
                           // If a field is empty...
                           if (w[i].value == '' && fieldmandatory == true) {
                              var fieldname = w[i].name;
                              var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                              if (res != 'none') {
                                 $('[name=\"' + fieldname + '\"]').addClass('invalid');
                                 $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                                 $('[for=\"' + fieldname + '\"]').css('color', 'red');
                                 ko++;
                              } else {
                                 $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                                 $('[name=\"' + fieldname + '\"]').removeAttr('required');
                                 $('[for=\"' + fieldname + '\"]').css('color', 'unset');
                              }
                           } else {
                              w[i].classList.remove('invalid');
                           }
                        }
                     }
                     //for select
                     if (z.length > 0) {
                        for (i = 0; i < z.length; i++) {
                           fieldmandatory = z[i].required;
                           // If a field is empty...
                           isnumber = z[i].getAttribute('isnumber');
//                           console.log(isnumber);
                           if (z[i].value == 0 && isnumber == null && fieldmandatory == true) {
                              // add an 'invalid' class to the field:
                              var fieldname = z[i].name;
                              var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                              if (res != 'none') {
                                 $('[name=\"' + fieldname + '\"]').addClass('invalid');
                                 $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                                 $('[for=\"' + fieldname + '\"]').css('color', 'red');
                                 ko++;
                              } else {
                                 $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                                 $('[name=\"' + fieldname + '\"]').removeAttr('required');
                                 $('[for=\"' + fieldname + '\"]').css('color', 'unset');
                              }
                  
                           } else {
                              z[i].classList.remove('invalid');
                           }
                        }
                     }
                     if (ko > 0) {
//                                       console.log(ko)
                        valid = false;
                        alert(msg);
                     }
                  
                     return valid; // return the valid status
                  }
                  
                  function fixStepIndicator(n) {
                     // This function removes the 'active' class of all steps...
                     var i, x = document.getElementsByClassName('step_wizard');
                     for (i = 0; i < x.length; i++) {
                        x[i].className = x[i].className.replace(' active', '');
                     }
                     //... and adds the 'active' class on the current step:
                     x[n].className += ' active';
                     
                     if (use_as_step == 1) {
                        var tabx = document.getElementsByClassName('tab-step');
                     } else {
                        var tabx = document.getElementsByClassName('tab-nostep');
                     }
//                     console.log(tabx);
                     bloc = tabx[n].firstChild.getAttribute('bloc-id');
                     id_bloc = parseInt(bloc.replace('bloc',''));
//                     console.log(id_bloc);
                     $(document).ready(function () {
                       $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/getNextMessage.php',
                                type: 'POST',
                                data:
                                  {
                                    '_glpi_csrf_token':'" . Session::getNewCSRFToken() . "',
                                    plugin_metademands_metademands_id: $metademands_id,
                                    block_id: id_bloc
                                  },
                                success: function(response){
                                  if (response.length == 0) {
                                      document.getElementById('nextMsg').style.display = 'none';
                                  } else {
                                      document.getElementById('nextBtn').innerHTML = nextsteptitle;
                                      document.getElementById('nextMsg').style.display = 'block';
                                      document.getElementById('nextMsg').innerHTML = response;
                                      sessionStorage.setItem('currentStep', id_bloc);
                                  }
                                 },
                                error: function(xhr, status, error) {
                                   console.log(xhr);
                                   console.log(status);
                                   console.log(error);
                                 } 
                             });
                     });
                  }
                  
                  function fixButtonIndicator() {
                     // This function removes the 'active' class of all steps...
                     if (use_as_step == 1) {
                        var x = document.getElementsByClassName('tab-step');
                     } else {
                        var x = document.getElementsByClassName('tab-nostep');
                     }
                   
                     create = false;
                     if (use_as_step == 1) {
                         nextTab = currentTab + 1;
                         while (nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
                             nextTab = nextTab + 1;
                         }
                   
                          var listBlock = [" . implode(",", $list_blocks) . "];
                        
                          if(x[nextTab] != undefined) {
                               bloc = x[nextTab].firstChild.getAttribute('bloc-id');
                               id_bloc = parseInt(bloc.replace('bloc',''));
                                if(!listBlock.includes(id_bloc)) {
                                    create = true; 
                                }
                          }

                         if(nextTab >= x.length) {
                             create = true;
                         }
                         
                         if(create) {
                            document.getElementById('nextBtn').innerHTML = submitsteptitle;
//                            document.getElementById('nextMsg').style.display = 'block';
//                            document.getElementById('nextMsg').innerHTML = submitstepmsg;
                         } else {
                            document.getElementById('nextBtn').innerHTML = nextsteptitle;
                         }

                     }
                  }
               </script>";
            } else {
                echo "<script>
                
                function fixButtonIndicator() {
                }
                </script>";
            }
        } else {
            echo "<div class='center'><b>" . __('No item to display') . "</b></div>";
        }
    }


    /**
     * @param       $metademands_id
     * @param       $values
     * @param array $options
     *
     * @throws \GlpitestSQLError
     */
    public static function createMetademands($metademands_id, $values, $options = [])
    {
        global $CFG_GLPI;

        $self = new self();
        $metademands = new PluginMetademandsMetademand();
        if ($metademands->getFromDB($metademands_id)) {
            if ($metademands->fields['is_order'] == 1
                && isset($values['basket'])) {
                $basketclass = new PluginMetademandsBasketline();
                if ($metademands->fields['create_one_ticket'] == 0) {
                    //create one ticket for each basket
                    foreach ($values['basket'] as $k => $basket) {
                        $datas = [];
                        $datas['basket'] = $basket;

                        if (isset($values['fields']['_filename'])) {
                            unset($values['fields']['_filename']);
                        }
                        if (isset($values['fields']['_prefix_filename'])) {
                            unset($values['fields']['_prefix_filename']);
                        }
                        if (isset($values['fields']['_tag_filename'])) {
                            unset($values['fields']['_tag_filename']);
                        }
                        $filename = [];
                        $prefixname = [];
                        $tagname = [];
                        foreach ($basket as $key => $val) {
                            $line = $k + 1;

                            $check = $basketclass->getFromDBByCrit(["plugin_metademands_metademands_id" => $metademands_id,
                                'plugin_metademands_fields_id' => $key,
                                'line' => $line,
                                'users_id' => Session::getLoginUserID(),
                                'name' => "upload"
                            ]);
                            if ($check) {
                                if (!empty($val)) {
                                    $files = json_decode($val, 1);
                                    foreach ($files as $file) {
                                        $filename[] = $file['_filename'];
                                        $prefixname[] = $file['_prefix_filename'];
                                        $tagname[] = $file['_tag_filename'];
                                    }
                                }
                            }
                        }

                        $values['fields']['_filename'] = $filename;
                        $values['fields']['_prefix_filename'] = $prefixname;
                        $values['fields']['_tag_filename'] = $tagname;

                        $datas['fields'] = $values['fields'];

                        $result = $metademands->addObjects($metademands_id, $datas, $options);
//                    PluginMetademandsStepform::deleteAfterCreate($stepformID);
                        Session::addMessageAfterRedirect($result['message']);
                    }
                    $basketclass->deleteByCriteria(['plugin_metademands_metademands_id' => $metademands_id,
                        'users_id' => Session::getLoginUserID()]);
                } else {
                    //create one ticket for all basket
                    if (isset($values['fields']['_filename'])) {
                        unset($values['fields']['_filename']);
                    }
                    if (isset($values['fields']['_prefix_filename'])) {
                        unset($values['fields']['_prefix_filename']);
                    }
                    if (isset($values['fields']['_tag_filename'])) {
                        unset($values['fields']['_tag_filename']);
                    }
                    $filename = [];
                    $prefixname = [];
                    $tagname = [];
                    foreach ($values['basket'] as $k => $basket) {
                        foreach ($basket as $key => $val) {
                            $line = $k + 1;
                            $check = $basketclass->getFromDBByCrit([
                                "plugin_metademands_metademands_id" => $metademands_id,
                                'plugin_metademands_fields_id' => $key,
                                'line' => $line,
                                'users_id' => Session::getLoginUserID(),
                                'name' => "upload"
                            ]);
                            if ($check) {
                                if (!empty($val)) {
                                    $files = json_decode($val, 1);
                                    foreach ($files as $file) {
                                        $filename[] = $file['_filename'];
                                        $prefixname[] = $file['_prefix_filename'];
                                        $tagname[] = $file['_tag_filename'];
                                    }
                                }
                            }
                        }
                    }
                    $values['fields']['_filename'] = $filename;
                    $values['fields']['_prefix_filename'] = $prefixname;
                    $values['fields']['_tag_filename'] = $tagname;

                    $basketclass->deleteByCriteria(['plugin_metademands_metademands_id' => $metademands_id,
                        'users_id' => Session::getLoginUserID()]);

                    $result = $metademands->addObjects($metademands_id, $values, $options);
                    Session::addMessageAfterRedirect($result['message']);
                }
            } else {
                //not in basket
                $result = $metademands->addObjects($metademands_id, $values, $options);
                if (isset($values['plugin_metademands_stepforms_id'])) {
                    //Add all form contributors as ticket requester
                    $ticketUser = new Ticket_User();
                    $stepformActor = new PluginMetademandsStepform_Actor();
                    $stepformActors = $stepformActor->find(['plugin_metademands_stepforms_id' => $values['plugin_metademands_stepforms_id']]);
                    foreach ($stepformActors as $actor) {
                        $ticketUser->add([
                            'tickets_id' => $result['id'],
                            'users_id' => $actor['users_id'],
                            'type' => CommonITILActor::REQUESTER
                        ]);
                    }
                    PluginMetademandsStepform::deleteAfterCreate($values['plugin_metademands_stepforms_id']);
                }

                Session::addMessageAfterRedirect($result['message']);
            }
        }
        unset($_SESSION['plugin_metademands']);

        if (!empty($options['resources_id'])) {
            Html::redirect(PLUGIN_RESOURCES_WEBDIR . "/front/wizard.form.php");
        } else {
            if (Plugin::isPluginActive('servicecatalog')
                && Session::haveRight("plugin_servicecatalog", READ)) {
                if (PluginServicecatalogConfig::getConfig()->getMultiEntityRedirection()) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php?changeactiveentity");
                } else {
                    $type = $metademands->fields['type'];
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/choosecategory.form.php?type=$type&level=1");
                }


            } elseif (Session::haveRight("plugin_metademands", READ)) {
                Html::redirect($self->getFormURL() . "?step=" . $step = PluginMetademandsMetademand::STEP_INIT);
            } else {
                Html::back();
            }
        }
    }

    /**
     * @param      $message
     * @param bool $error
     */
    public static function showMessage($message, $error = false)
    {
        $class = $error ? "style='color:red'" : "";

        echo "<br><div class='box'>";
        echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'>";
        echo "</div></div></div>";
        echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter center'>";
        echo "<h3 $class>" . $message . "</h3>";
        echo "</div></div></div>";
        echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'>";
        echo "</div></div></div>";
        echo "</div>";
    }

    /**
     * @param       $value
     * @param       $id
     * @param       $post
     * @param       $fieldname
     * @param false $on_basket
     *
     * @return array
     */
    public static function checkvalues($value, $id, $post, $fieldname, $on_basket = false)
    {
        $KO = false;
        $content = [];
        if (($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval') && !isset($value['second_date_ok'])) {
            $value['second_date_ok'] = true;
            $value['id'] = $id . '-2';
            $value['name'] = $value['label2'];
            $data[$id . '-2'] = $value;
        }

        if (isset($post[$fieldname][$id])
            && $value['type'] != 'checkbox'
            && $value['type'] != 'radio'
            && $value['type'] != 'title'
            && $value['type'] != 'title-block'
            && $value['type'] != 'informations'
            && $value['item'] != 'ITILCategory_Metademands'
            && $value['type'] != 'upload') {
            if (!self::checkMandatoryFields(
                $fieldname,
                $value,
                ['id' => $id,
                    'value' => $post[$fieldname][$id]],
                $post
            )) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands']['fields'][$id] = $post[$fieldname][$id];
                if (isset($post[$fieldname][$id . "-2"]) &&
                    ($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval')
                    && $value['second_date_ok']) {
                    $_SESSION['plugin_metademands']['fields'][$id . "-2"] = $post[$fieldname][$id . "-2"];
                }
            }
        } elseif ($value['item'] == 'ITILCategory_Metademands') {
            if (!self::checkMandatoryFields(
                $fieldname,
                $value,
                ['id' => $id,
                    'value' => $post[$fieldname][$id]],
                $post
            )) {
                $KO = true;
            } else {
                $content[$id]['plugin_metademands_fields_id'] = $id;
                if ($on_basket == false) {
                    $content[$id]['value'] = $post['field_plugin_servicecatalog_itilcategories_id'];
                } else {
                    $content[$id]['value'] = $post['basket_plugin_servicecatalog_itilcategories_id'];
                }

                $content[$id]['value2'] = "";
                $content[$id]['item'] = $value['item'];
                $content[$id]['type'] = $value['type'];
                $_SESSION['plugin_metademands']['fields'][$id] = $post[$fieldname][$id];
            }
        } elseif ($value['type'] == 'checkbox') {
            if (!self::checkMandatoryFields($fieldname, $value, ['id' => $id, 'value' => $post[$fieldname][$id]], $post)) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands']['fields'][$id] = $post[$fieldname][$id];
            }
        } elseif ($value['type'] == 'radio') {
            if (!self::checkMandatoryFields($fieldname, $value, ['id' => $id, 'value' => $post[$fieldname][$id]], $post)) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands']['fields'][$id] = $post[$fieldname][$id];
            }
        } elseif ($value['type'] == 'upload') {
            if ($value['is_basket'] == 1
                && isset($post[$fieldname][$id]) && !empty($post[$fieldname][$id])) {
                $files = json_decode($post[$fieldname][$id], 1);
                foreach ($files as $file) {
                    $post['_filename'][] = $file['_filename'];
                    $post['_prefix_filename'][] = $file['_prefix_filename'];
                    $post['_tag_filename'][] = $file['_tag_filename'];
                }
            }
            if (!self::checkMandatoryFields($fieldname, $value, ['id' => $id, 'value' => 1], $post)) {
                $KO = true;
            } else {
                //not in basket mode
                if (isset($post['_filename'])) {
                    foreach ($post['_filename'] as $key => $filename) {
                        $_SESSION['plugin_metademands']['fields']['files'][$post['form_metademands_id']]['_prefix_filename'][] = $post['_prefix_filename'][$key];
                        $_SESSION['plugin_metademands']['fields']['files'][$post['form_metademands_id']]['_tag_filename'][] = $post['_tag_filename'][$key];
                        $_SESSION['plugin_metademands']['fields']['files'][$post['form_metademands_id']]['_filename'][] = $post['_filename'][$key];
                    }
                }
            }
        } elseif ($value['type'] == 'dropdown_multiple') {
            if (!isset($post[$fieldname][$id])) {
                if (!self::checkMandatoryFields(
                    $fieldname,
                    $value,
                    ['id' => $id,
                        'value' => []],
                    $post
                )) {
                    $KO = true;
                    $_SESSION['plugin_metademands']['fields'][$id] = [];
                } else {
                    $_SESSION['plugin_metademands']['fields'][$id] = [];
                }
            } else {
                $_SESSION['plugin_metademands']['fields'][$id] = $post[$fieldname][$id];
            }
        }
        //INFO : not used for update basket
        if ($value['item'] != 'ITILCategory_Metademands'
            && $KO === false
            && isset($post[$fieldname][$id])) {
            $content[$id]['plugin_metademands_fields_id'] = $id;
            if ($value['type'] != "upload") {
                $content[$id]['value'] = (is_array($post[$fieldname][$id])) ? PluginMetademandsField::_serialize($post[$fieldname][$id]) : $post[$fieldname][$id];
            }
            $content[$id]['value2'] = (isset($post[$fieldname][$id . "-2"])) ? $post[$fieldname][$id . "-2"] : "";
            $content[$id]['item'] = $value['item'];
            $content[$id]['type'] = $value['type'];

            if (isset($post['_filename']) && $value['type'] == "upload") {
                $files = [];
                foreach ($post['_filename'] as $key => $filename) {
                    $files[$key]['_prefix_filename'] = $post['_prefix_filename'][$key];
                    $files[$key]['_tag_filename'] = $post['_tag_filename'][$key];
                    $files[$key]['_filename'] = $post['_filename'][$key];
                }
                $content[$id]['value'] = json_encode($files);
            }
        }

        return ['result' => $KO, 'content' => $content];
    }

    /**
     * @param array $value
     * @param array $fields
     * @param       $fieldname
     * @param array $post
     *
     * @return bool
     */
    public static function checkMandatoryFields($fieldname, $value = [], $fields = [], $post = [])
    {

        //Don't check hidden fields of hidden blocks
        $hidden_blocks = $_SESSION['plugin_metademands']['hidden_blocks'] ?? [];
        $dbu = new DbUtils();
//        Toolbox::logInfo($hidden_blocks);
        foreach ($hidden_blocks as $hidden_block) {
            $crit["rank"] = $hidden_block;
            $crit["plugin_metademands_metademands_id"] = $post["metademands_id"];
            $meta_fields = $dbu->getAllDataFromTable("glpi_plugin_metademands_fields", $crit);
            $hiddenfields = [];
            foreach ($meta_fields as $meta_field) {
                $hiddenfields[] = $meta_field['id'];
            }
            if (is_array($hiddenfields) && in_array($fields['id'], $hiddenfields)) {
                return true;
            }
        }

        //TODO To Translate ?
        $checkKo = [];
        $checkKoDateInterval = [];
        $checkNbDoc = [];
        $checkRegex = [];
        $msg = [];
        $msg2 = [];
        $msg3 = [];
        $all_fields = $post[$fieldname];

        if ($value['type'] != 'parent_field') {
            // Check fields empty
            if ($value['is_mandatory']
                && empty($fields['value'])
                && $value['type'] != 'radio'
                && $value['type'] != 'checkbox'
                && $value['type'] != 'number'
                && $value['type'] != 'informations'
                && $value['type'] != 'upload') {
                $msg[] = $value['name'];
                $checkKo[] = 1;
            }

            // Check linked field mandatory
            if (!empty($value['fields_link'])
                && !empty($value['check_value'])
                && PluginMetademandsTicket_Field::isCheckValueOK($fields['value'], $value['check_value'], $value['type'])
                && (empty($all_fields[$value['fields_link']]) || $all_fields[$value['fields_link']] == 'NULL')
            ) {
                $field = new PluginMetademandsField();
                $fields_links = $value['fields_link'];

                if (is_array($fields_links)) {
                    foreach ($fields_links as $k => $fields_link) {
                        if ($fields_link > 0) {
                            if ($field->getFromDB($fields_link)) {
                                $msg[] = $field->fields['name'] . ' ' . $field->fields['label2'];
                                $checkKo[] = 1;
                            }
                        }
                    }
                }
            }
            //radio
            if ($value['type'] == 'radio'
                && $value['is_mandatory']) {
                if ($fields['value'] == null) {
                    $msg[] = $value['name'];
                    $checkKo[] = 1;
                }
            }

            //number
            if ($value['type'] == 'number'
                && $value['is_mandatory']) {
                if ($fields['value'] == null) {
                    $msg[] = $value['name'];
                    $checkKo[] = 1;
                }
            }

            //checkbox
            if ($value['type'] == 'checkbox'
                && $value['is_mandatory']) {
                if ($fields['value'] == null) {
                    $msg[] = $value['name'];
                    $checkKo[] = 1;
                }
            }

            // Check date
            if ($value['type'] == "date"
                || $value['type'] == "datetime"
                || $value['type'] == "date_interval"
                || $value['type'] == "datetime_interval") {
                // date Null
                if ($value['is_mandatory']
                    && $fields['value'] == 'NULL') {
                    $msg[] = $value['name'];
                    $checkKo[] = 1;
                }
                // date not < today
                if ($fields['value'] != 'NULL'
                    && !empty($fields['value'])
                    && !empty($value['check_value'])
                    && !(strtotime($fields['value']) >= strtotime(date('Y-m-d')))) {
                    $msg[] = sprintf(__("Date %s cannot be less than today's date", 'metademands'), $value['name']);
                    $checkKo[] = 1;
                }
            }

            // Check date interval is right
            if (($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval')
                && isset($all_fields[$fields['id'] . '-2'])) {
                if (strtotime($fields['value']) > strtotime($all_fields[$fields['id'] . '-2'])) {
                    $msg[] = sprintf(__('Date %1$s cannot be greater than date %2$s', 'metademands'), $value['name'], $value['label2']);
                    $checkKoDateInterval[] = 1;
                }
            }

            // Check File upload field
            if ($value['type'] == "upload"
                && $value['is_mandatory']) {
                if (isset($post['_filename'])) {
                    if (empty($post['_filename'][0])) {
                        $msg[] = $value['name'];
                        $checkKo[] = 1;
                    }
                } else {
                    $msg[] = $value['name'];
                    $checkKo[] = 1;
                }
            }
            // Check File upload field
            if ($value['type'] == "upload"
                && !empty($value["max_upload"])
                && isset($post['_filename'])) {
                if ($value["max_upload"] < count($post['_filename'])) {
                    $msg2[] = $value['name'];
                    $checkNbDoc[] = 1;
                }
            }

            // Check text with regex
            if ($value['type'] == "text"
                && !empty($value["regex"])) {
                if ((!empty($fields['value']) && $value['is_mandatory'] == 0) || $value['is_mandatory'] == 1) {
                    if (!preg_match(($value['regex']), $fields['value'])) {
                        $msg3[] = $value['name'];
                        $checkRegex[] = 1;
                    }
                }
            }
        }

        if (in_array(1, $checkKo)
            || in_array(1, $checkKoDateInterval)) {
            Session::addMessageAfterRedirect(sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)), false, ERROR);
            return false;
        }
        if (in_array(1, $checkNbDoc)) {
            Session::addMessageAfterRedirect(sprintf(__("Too much documents are upload, max %s. Please correct: %s", "metademands"), $value["max_upload"], implode(', ', $msg2)), false, ERROR);
            return false;
        }
        if (in_array(1, $checkRegex)) {
            Session::addMessageAfterRedirect(sprintf(__("Field do not correspond to the expected format. Please correct: %s", "metademands"), implode(', ', $msg3)), false, ERROR);
            return false;
        }

        return true;
    }

    /**
     * @param       $name
     * @param       $data
     * @param array $options
     */
    public function showDropdownFromArray($name, $data, $options = [])
    {
        $params['on_change'] = '';
        $params['no_empty'] = 0;
        $params['value'] = '';
        $params['tree'] = false;
        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }
        //print_r($params['value']);
        echo "<select class='form-select' id='" . $name . "' name='" . $name . "' onchange='" . $params['on_change'] . "'>";
        if (!$params['no_empty']) {
            echo "<option value='0'>-----</option>";
        }
        foreach ($data as $id => $values) {
            $level = 0;
            $class = "";
            $raquo = "";

            if ($params['tree']) {
                $level = $values['level'];
                $class = " class='tree' ";
                $raquo = "&raquo;";

                if ($level == 1) {
                    $class = " class='treeroot'";
                    $raquo = "";
                }
            }

            echo "<option value='" . $id . "' $class " . ($params['value'] == $id ? 'selected' : '') . " >" . str_repeat("&nbsp;&nbsp;&nbsp;", $level) . $raquo;

            if ($params['tree']) {
                echo $values['name'];
            } else {
                echo $values;
            }
            echo "</option>";
        }
        echo "</select>";
    }

    /**
     * Used for check if hide child metademands
     *
     * @param $check_value
     * @param $plugin_metademands_tasks_id
     * @param $metademandtasks_tasks_id
     * @param $id
     * @param $value
     */
    public function checkValueOk($check_value, $plugin_metademands_tasks_id, $metademandtasks_tasks_id, $id, $value, $post)
    {
        if (isset($post[$id])
            && $check_value != null
            && in_array($plugin_metademands_tasks_id, $metademandtasks_tasks_id)) {
            if (!PluginMetademandsTicket_Field::isCheckValueOK($post[$id], $check_value, $value['type'])) {
                $metademandToHide = array_keys($metademandtasks_tasks_id, $plugin_metademands_tasks_id);
                $_SESSION['metademands_hide'][$metademandToHide[0]] = $metademandToHide[0];
                unset($_SESSION['son_meta'][$metademandToHide[0]]);
            }
        }
    }


    //* Function to convert Hex colors to RGBA
    public static function hex2rgba($color, $opacity = false)
    {

        $defaultColor = 'rgb(0,0,0)';

        // Return default color if no color provided
        if (empty($color)) {
            return $defaultColor;
        }

        // Ignore "#" if provided
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        // Check if color has 6 or 3 characters, get values
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $defaultColor;
        }

        // Convert hex values to rgb values
        $rgb = array_map('hexdec', $hex);

        // Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = 1.0;
            }
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        // Return rgb(a) color string
        return $output;

    }
}
