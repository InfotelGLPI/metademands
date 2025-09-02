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
        if (Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE])
            || Session::haveRight('plugin_metademands_createmeta', READ)) {
            return true;
        }
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
            //Create item
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
        if ($meta->getFromDB($item->fields['id'])) {
            if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                $background_color = $meta->fields['background_color'];
            }
        }
        echo "<tr><td>";
        $options = [
            'step' => PluginMetademandsMetademand::STEP_SHOW,
            'metademands_id' => $item->getID(),
            'preview' => true,
        ];
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
        $infos = getUserName($user->getID(), 2, true);
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
    }

    /**
     * @param $parameters
     * @return void
     */
    public function showMetademandTitle($meta, $parameters)
    {

        echo "<div class=\"row\">";
        echo "<div class=\"col-md-12 md-title\">";
        echo "<div style='background-color: #FFF'>";

        $title_color = "#000";
        if (isset($meta->fields['title_color']) && !empty($meta->fields['title_color'])) {
            $title_color = $meta->fields['title_color'];
        }

        $color = self::hex2rgba($title_color, "0.03");
        $style_background = "style='background-color: $color!important;border-color: $title_color!important;border-radius: 0;'";
        echo "<div class='card-header d-flex justify-content-between align-items-center md-color' $style_background>";

        $meta = new PluginMetademandsMetademand();
        if ($meta->getFromDB($parameters['metademands_id'])) {
            if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                $icon = $meta->fields['icon'];
            }
        }

        echo "<h2 class='card-title' style='color: " . $title_color . ";font-weight: normal;'> ";
        if (!empty($icon)) {
            echo "<i class='fa-2x fas $icon' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>&nbsp;";
        }
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
        if ($meta->getFromDB($parameters['metademands_id'])
            && Plugin::isPluginActive('servicecatalog')) {
            $configsc = new PluginServicecatalogConfig();
            $seedetail = 1;
            if (method_exists("PluginServicecatalogConfig", "getDetailBeforeFormRedirect")) {
                $seedetail = $configsc->getDetailBeforeFormRedirect();
            }
            if ($configsc->seeCategoryDetails() && $seedetail == 0) {
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
                    && ($helpdesk_category->fields['comment_incident'] != null
                        || $helpdesk_category->fields['comment_request'] != null
                        || $helpdesk_category->fields['service_detail'] != null
                        || $helpdesk_category->fields['service_users'] != null
                        || $helpdesk_category->fields['service_ttr'] != null
                        || $helpdesk_category->fields['service_use'] != null
                        || $helpdesk_category->fields['service_supervision'] != null
                        || $helpdesk_category->fields['service_rules'] != null)) {
                    echo "&nbsp;<i class='fas fa-question-circle pointer' href='#' data-bs-toggle='modal' data-bs-target='#categorydetails$itilcategories_id' title=\"" . __(
                        'More informations',
                        'servicecatalog'
                    ) . "\"> ";
                    //                            echo __('More informations of this category ? click here', 'servicecatalog');
                    echo "</i>";
                    //                            echo "</div>";
                    echo Ajax::createIframeModalWindow(
                        'categorydetails' . $itilcategories_id,
                        PLUGIN_SERVICECATALOG_WEBDIR . "/front/categorydetail.form.php?type=" . $type . "&category_id=" . $itilcategories_id,
                        [
                            'title' => __('More informations', 'servicecatalog'),
                            'display' => false,
                            'width' => 1050,
                            'height' => 500,
                        ]
                    );
                }
            }
        }

        if (Session::getCurrentInterface() == 'central'
            && Session::haveRight('plugin_metademands', UPDATE)
            && !$parameters['seeform']) {
            echo "&nbsp;<a href='" . Toolbox::getItemTypeFormURL(
                'PluginMetademandsMetademand'
            ) . "?id=" . $parameters['metademands_id'] . "'>
                            <i class='fas fa-wrench'></i></a>";
        }
        echo "</h2>";

        self::showmodelsAndDrafts($parameters, true);
        echo "</div>";
        if (!empty($meta->fields['comment'])) {
            if (empty($comment = PluginMetademandsMetademand::displayField($meta->getID(), 'comment'))) {
                $comment = $meta->fields['comment'];
            }
            echo "<div class='center' style='background: #fbf9f9;padding: 10px;'><i>" . nl2br(
                $comment
            ) . "</i></div>";
        } else {
            echo "<span style='margin-bottom: 10px'>&nbsp;";
            echo "</span>";
        }


        echo "</div>";
        echo "</div>";
    }

    public function showmodelsAndDrafts($parameters, $with_title = 1)
    {
        $config = PluginMetademandsConfig::getInstance();

        $class = "mydraft-withtitle";
        if ($with_title == false) {
            $class = "mydraft-withouttitle";
        }
        if (!$parameters['preview'] && !$parameters['seeform']) {
            echo "<div class='$class'>";
            echo "&nbsp;<i class='fas fa-2x mydraft-fa fa-align-justify pointer' title='" . _sx(
                'button',
                'Your forms',
                'metademands'
            ) . "'
                data-hasqtip='0' aria-hidden='true' onclick='$(\"#divnavforms\").toggle();' ></i>";
            echo "</span>";
            echo "</div>";

            if ($with_title == true) {
                echo "</div>";
            }

            $margin = "";
            if ($with_title == false) {
                $margin = "margin-top: 50px;";
            }
            echo "<div id='divnavforms' class=\"input-draft card bg-light mb-3\" style='display:none;color: #000!important;position:absolute;right:0;z-index: 1000;$margin'>";
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
            echo PluginMetademandsForm::showPrivateFormsForUserMetademand(
                Session::getLoginUserID(),
                $parameters['metademands_id']
            );
            echo PluginMetademandsForm::showPublicFormsForUserMetademand(
                $parameters['metademands_id'],
            );
            echo "</div>";

            echo "<div id='divforms' class='tab-pane fade' role='tabpanel' aria-labelledby='divforms-tab'>";
            echo PluginMetademandsForm::showFormsForUserMetademand(
                Session::getLoginUserID(),
                $parameters['metademands_id']
            );
            echo "</div>";

            if ($config['use_draft']) {
                //
                echo "<div id='divdrafts' class='tab-pane fade' role='tabpanel' aria-labelledby='divdrafts-tab'>";
                echo PluginMetademandsDraft::showDraftsForUserMetademand(
                    Session::getLoginUserID(),
                    $parameters['metademands_id']
                );
                echo "</div>";
            }
            echo "</div>";
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
        $parameters = [
            'step' => PluginMetademandsMetademand::STEP_INIT,
            'metademands_id' => 0,
            'preview' => false,
            'seeform' => false,
            'tickets_id' => 0,
            'current_ticket_id' => 0,
            'meta_validated' => 1,
            'resources_id' => 0,
            'resources_step' => '',
            'ancestor_tickets_id' => 0,
            'meta_type' => '',
            'block_id' => 0,
            'itilcategories_id' => 0,
        ];

        // if given parameters, override defaults
        foreach ($parameters as $key => $value) {
            if (isset($options[$key])) {
                $parameters[$key] = $options[$key];
            }
        }
        $_SESSION['servicecatalog']['sc_itilcategories_id'] = $parameters['itilcategories_id'];
        // Retrieve session values
        //        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['tickets_id'])) {
        //            $parameters['tickets_id'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['tickets_id'];
        //        }
        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_id'])) {
            $parameters['resources_id'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_id'];
        }
        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_step'])) {
            $parameters['resources_step'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_step'];
        }
        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['ancestor_tickets_id'])) {
            $parameters['ancestor_tickets_id'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['ancestor_tickets_id'];
        }

        Html::requireJs("metademands");

        $meta = new PluginMetademandsMetademand();
        $maintenance_mode = 0;
        $title = 1;

        if ($meta->getFromDB($parameters['metademands_id'])) {
            $maintenance_mode = $meta->fields['maintenance_mode'];
            $_SESSION['servicecatalog']['sc_itilcategories_id'] = $meta->fields['itilcategories_id'];
            $title = $meta->fields['hide_title'] ? 0 : 1;
        }

        if ($parameters['step'] > PluginMetademandsMetademand::STEP_LIST && $title == 0) {
            self::showmodelsAndDrafts($parameters, false);
        }

        echo "<div id ='content'>";

        if ($maintenance_mode == 1 && !$parameters['preview']) {
            echo "<h3>";
            echo "<div class='alert alert-warning center'>";
            echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:orange'></i>&nbsp;";
            echo __('This form is in maintenance mode', 'metademands') . "<br>";
            echo __('Please come back later', 'metademands') . "</div></h3>";
        } else {
            echo "<div class='bt-container-fluid asset metademands_wizard_rank'> ";
            if ($parameters['step'] > PluginMetademandsMetademand::STEP_LIST) {
                // fil d'ariane
                if ($meta->getFromDB($parameters['metademands_id'])
                    && Plugin::isPluginActive('servicecatalog')
                    && Session::getCurrentInterface() != 'central'
                    && $parameters['itilcategories_id'] > 0) {
                    $treename = PluginServicecatalogCategory::getTreeCategoryFriendlyName(
                        $meta->fields['type'],
                        $parameters['itilcategories_id'],
                        6
                    );
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
                    $important = "";
                    $plugin = new Plugin();
                    if (Plugin::isPluginActive('servicecatalog')
                        && ($plugin->getInfo('servicecatalog')["version"] > "2.0.8")) {
                        $config = new PluginServicecatalogConfig();
                        if ($config->getLayout() == PluginServicecatalogConfig::BOOTSTRAPPED
                            || $config->getLayout() == PluginServicecatalogConfig::BOOTSTRAPPED_COLOR) {
                            $style = 'style="border: 1px solid transparent;border-radius: 1px;margin: 0px;"';
                        }
                        $force = $config->getforceBackgroundColor();
                        if ($force == 1) {
                            $important = "alert-important";
                        }
                    }
                    echo "<div class='alert alert-secondary $important' role='alert' $style>";
                    echo "<span id='title_cat'>";
                    echo "</span>";
                    echo "</div>";
                    echo "</span>";
                    echo "</h5>";

                    if (Plugin::isPluginActive('servicecatalog')) {
                        $helpdesk_category = new PluginServicecatalogCategory();
                        if ($helpdesk_category->getFromDBByCategory($parameters['itilcategories_id'])
                            && !empty($helpdesk_category->fields['display_warning'])) {
                            echo "<h5>";
                            echo "<div class='alert alert-danger' role='alert'>";
                            echo "<i class='fas fa-exclamation-circle fa-2x'></i>";
                            echo "&nbsp;" . nl2br(
                                PluginServicecatalogCategory::displayField($helpdesk_category, 'display_warning')
                            );
                            echo "</div>";
                            echo "</h5>";
                        }

                        if ($helpdesk_category->getFromDBByCategory($parameters['itilcategories_id'])
                            && !empty($helpdesk_category->fields['knowbaseitems_id'])
                            && Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
                            $know_id = $helpdesk_category->fields['knowbaseitems_id'];
                            echo "<h5>";
                            echo "<div class='alert alert-warning' role='alert'>";
                            echo "<i class='fas fa-exclamation-triangle fa-2x'></i>";
                            echo "&nbsp;";
                            echo __(
                                'Did you know that there is an FAQ article that may be able to help you?',
                                'servicecatalog'
                            );
                            echo "&nbsp;";
                            echo "<a href='" . PLUGIN_SERVICECATALOG_WEBDIR . "/front/faq.php?from_ticket=1&itilcategories_id=" . $parameters['itilcategories_id'] . "&type=" . $meta->fields['type'] . "&id=" . $know_id . "'>";
                            echo "<button form='' class='submit btn btn-info btn-sm'>
<i class='fas fa-link' data-hasqtip='0' aria-hidden='true'></i>";
                            echo "&nbsp;";
                            echo __('Click here for more informations', 'servicecatalog');
                            echo "</button>";
                            echo "</a>";
                            echo "</div>";
                            echo "</h5>";
                        }
                    }
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
            echo Html::hidden('ancestor_tickets_id', ['value' => $parameters['ancestor_tickets_id']]);

            $icon = '';

            if ($parameters['step'] == PluginMetademandsMetademand::STEP_INIT) {
                // Wizard title
                echo "<div class=\"row\">";
                echo "<div class=\"col-md-12\">";
                echo "<h3><div class='alert alert-light' role='alert'>";
                $icon = "fa-share-alt";
                if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                    $icon = $meta->fields['icon'];
                }
                echo "<i class='fa-2x fas $icon'></i>&nbsp;";
                echo __('What you want to do ?', 'metademands');
                echo "</div></h3></div></div>";

            } elseif ($parameters['step'] == PluginMetademandsMetademand::STEP_LIST) {
                // Wizard title
                echo "<div class=\"row\">";
                echo "<div class=\"col-md-12\">";
                echo "<h3><div class='alert alert-light' role='alert'>";
                $icon = "fa-share-alt";

                $config = PluginMetademandsConfig::getInstance();
                if (!empty($config['icon_incident']) && $parameters['meta_type'] == Ticket::INCIDENT_TYPE) {
                    $icon = $config['icon_incident'];
                }
                if (!empty($config['icon_request']) && $parameters['meta_type'] == Ticket::DEMAND_TYPE) {
                    $icon = $config['icon_request'];
                }
                if (!empty($config['icon_problem']) && $parameters['meta_type'] == "Problem") {
                    $icon = $config['icon_problem'];
                }
                if (!empty($config['icon_change']) && $parameters['meta_type'] == "Change") {
                    $icon = $config['icon_change'];
                }
                if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                    $icon = $meta->fields['icon'];
                }

                echo "<i class='fa-2x fas $icon'></i>&nbsp;";
                echo __('Form choice', 'metademands');
                echo "</div></h3></div></div>";

            } elseif ($parameters['step'] > PluginMetademandsMetademand::STEP_LIST) {

                if ($title == 1) {
                    self::showMetademandTitle($meta, $parameters);
                }

                if ($parameters['preview'] == 0) {
                    if (PluginMetademandsStep::checkSupervisorForUser($meta->getID()) == false) {
                        return false;
                    }
                }

                // Display user informations
                $userid = Session::getLoginUserID();
                // If ticket exists we get its first requester
                if ($parameters['tickets_id']) {
                    $users_id_requester = PluginMetademandsTicket::getUsedActors(
                        $parameters['tickets_id'],
                        CommonITILActor::REQUESTER,
                        'users_id'
                    );
                    if (count($users_id_requester)) {
                        $userid = $users_id_requester[0];
                    }
                }

                // Retrieve session values
                if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['_users_id_requester'])) {
                    $userid = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['_users_id_requester'];
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

            self::showWizardSteps(
                $parameters['step'],
                $parameters['metademands_id'],
                $parameters['preview'],
                $options,
                $parameters['seeform'],
                $parameters['current_ticket_id'],
                $parameters['meta_validated']
            );
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
    public static function showWizardSteps(
        $step,
        $metademands_id = 0,
        $preview = false,
        $options = [],
        $seeform = false,
        $current_ticket = 0,
        $meta_validated = 1
    ) {
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
            $values = $_SESSION['plugin_metademands'][$metademands_id] ?? [];
            if (count($values) > 0) {
                self::createMetademands($metademands_id, $values, $options);
            }
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
                    self::showMetademands(
                        $metademands_id,
                        $step,
                        $current_ticket,
                        $meta_validated,
                        $preview,
                        $options,
                        $seeform
                    );
                    break;
            }
            echo Html::hidden('step', ['value' => $step]);
        }
    }

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
        $query = "SELECT `id`,`name`, 'comment'
                   FROM `glpi_plugin_metademands_metademands`
                   WHERE (is_order = 1  OR `itilcategories_id` <> '')
                   AND $crit  
                        AND `id` NOT IN (SELECT `plugin_metademands_metademands_id` FROM `glpi_plugin_metademands_metademands_resources`) "
            . $dbu->getEntitiesRestrictRequest(
                " AND ",
                'glpi_plugin_metademands_metademands',
                '',
                $_SESSION['glpiactive_entity'],
                true
            );

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

    public static function getMetademandTypeName($object, $type = 0)
    {
        global $PLUGIN_HOOKS;

        switch ($object) {
            case 'Ticket':
                switch ($type) {
                    case Ticket::INCIDENT_TYPE:
                        return __('Report an incident', 'metademands');
                    case Ticket::DEMAND_TYPE:
                        return __('Make a request', 'metademands');
                }
                break;
            case 'Problem':
                return __('Report a problem', 'metademands');
            case 'Change':
                return __('Make a change request', 'metademands');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_cat = self::createPluginNewKindOfCategory($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_cat)) {
                            $objectName = $new_cat['name'];
                            return $objectName;

                        }
                    }
                }
                break;
        }

    }

    public static function countMetademandTypes()
    {
        global $PLUGIN_HOOKS;

        $data = [];

        $metademands_incidents = self::selectMetademands(false, "", Ticket::INCIDENT_TYPE);
        if (count($metademands_incidents) > 0) {
            $data[Ticket::INCIDENT_TYPE] = self::getMetademandTypeName('Ticket', Ticket::INCIDENT_TYPE);
        }

        $metademands_requests = self::selectMetademands(false, "", Ticket::DEMAND_TYPE);
        if (count($metademands_requests) > 0) {
            $data[Ticket::DEMAND_TYPE] = self::getMetademandTypeName('Ticket', Ticket::DEMAND_TYPE);
        }

        $metademands_problems = self::selectMetademands(false, "", "Problem");
        if (count($metademands_problems) > 0) {
            $data['Problem'] = self::getMetademandTypeName('Problem');
        }
        $metademands_changes = self::selectMetademands(false, "", "Change");
        if (count($metademands_changes) > 0) {
            $data['Change'] = self::getMetademandTypeName('Change');
        }
        if (isset($PLUGIN_HOOKS['metademands'])) {
            $pass = false;
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_cat = self::createPluginNewKindOfCategory($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_cat)) {

                    $objectCreate = $new_cat['type'];

                    $metademands_plugin = self::selectMetademands(false, "", $objectCreate);
                    if (count($metademands_plugin) > 0) {
                        $data[$objectCreate] = self::getMetademandTypeName($objectCreate);
                    }

                }
            }
        }
        return $data;
    }
    /**
     * @throws \GlpitestSQLError
     */
    public static function listMetademandTypes()
    {

        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.css.php");

        $data = self::countMetademandTypes();

        if (count($data) > 0) {
            if (count($data) == 1) {
                foreach ($data as $type => $typename) {
                    Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=1&meta_type=$type");
                }
            }

            foreach ($data as $type => $typename) {
                echo "<a class='bt-buttons' href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=" . PluginMetademandsMetademand::STEP_LIST . "&meta_type=$type'>";
                echo '<div class="btnsc-normal-type" >';
                $fasize = "fa-6x";
                echo "<div class='center'>";
                $config = PluginMetademandsConfig::getInstance();
                $icon = "fa-share-alt";
                if (!empty($config['icon_incident']) && $type == Ticket::INCIDENT_TYPE) {
                    $icon = $config['icon_incident'];
                }
                if (!empty($config['icon_request']) && $type == Ticket::DEMAND_TYPE) {
                    $icon = $config['icon_request'];
                }
                if (!empty($config['icon_problem']) && $type == "Problem") {
                    $icon = $config['icon_problem'];
                }
                if (!empty($config['icon_change']) && $type == "Change") {
                    $icon = $config['icon_change'];
                }
                echo "<i class='bt-interface fa-menu-md fas $icon $fasize'></i>";//$style
                echo "</div>";
                echo "<br><p style='font-weight: normal;font-size: 13px;'>";
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


    public static function showMostUsedMetademands($type)
    {
        global $DB;

        switch ($type) {
            case Ticket::INCIDENT_TYPE:
                $crit = [
                    'glpi_itilcategories.is_incident' => 1,
                    'glpi_itilcategories.entities_id' => $_SESSION["glpiactive_entity"],
                    'glpi_tickets.type' => $type,
                ];

                break;
            case Ticket::DEMAND_TYPE:
                $crit = [
                    'glpi_itilcategories.is_request' => 1,
                    'glpi_itilcategories.entities_id' => $_SESSION["glpiactive_entity"],
                    'glpi_tickets.type' => $type,
                ];
                break;
            default:
                $crit = "";
                break;
        }
        if (Session::getCurrentInterface() != "central") {
            $crit['glpi_itilcategories.is_helpdeskvisible'] = 1;
        }
        $criteria = [
            'SELECT' => [
                'glpi_plugin_metademands_metademands.name',
                'glpi_plugin_metademands_metademands.id as plugin_metademands_metademands_id',
                'COUNT' => 'glpi_tickets.id AS count',
            ],
            'FROM' => 'glpi_tickets',
            'LEFT JOIN'       => [
                'glpi_itilcategories' => [
                    'ON' => [
                        'glpi_itilcategories' => 'id',
                        'glpi_tickets'          => 'itilcategories_id',
                    ],
                ],
                'glpi_plugin_metademands_tickets_metademands' => [
                    'ON' => [
                        'glpi_plugin_metademands_tickets_metademands' => 'tickets_id',
                        'glpi_tickets'          => 'id',
                    ],
                ],
                'glpi_plugin_metademands_metademands' => [
                    'ON' => [
                        'glpi_plugin_metademands_tickets_metademands' => 'plugin_metademands_metademands_id',
                        'glpi_plugin_metademands_metademands'          => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_tickets.is_deleted'  => 0,
                'glpi_plugin_metademands_metademands.is_deleted'  => 0,
                'glpi_tickets.users_id_recipient'  => Session::getLoginUserID(),
                'NOT' => ['glpi_itilcategories.id' => 'NULL'],
            ],
            'GROUPBY'   => ['glpi_plugin_metademands_metademands.id'],
            'ORDERBY'    => 'count DESC',
            'LIMIT'    => 5,

        ];

        if (isset($crit) && !empty($crit)) {
            $criteria['WHERE'] = $criteria['WHERE'] + $crit;
        }

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets'
        );

        $iterator = $DB->request($criteria);

        if (count($iterator) > 0) {

            echo "<div style='display:flex;'>";
            foreach ($iterator as $row) {
                $meta = new PluginMetademandsMetademand();
                $meta->getFromDB($row['plugin_metademands_metademands_id']);
                $icon = "fa-share-alt";
                if (!empty($meta->fields['icon'])) {
                    $icon = $meta->fields['icon'];
                }

                echo "<a href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $row['plugin_metademands_metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW . "'>";
                echo "<div style='margin-right: 5px;'>";
                echo "<h6>";
                echo "<div class='alert alert-secondary' style='border-radius: 0;margin-right: 5px;'>";
                echo "<i class='fas " . $icon . " fa-1x'></i>";
                echo "&nbsp;";
                echo  $row['name'];
                echo "</div>";
                echo "</h6>";
                echo "</div>";
                echo "</a>";
            }
            echo "</div>";
        }
    }

    /**
     * @throws \GlpitestSQLError
     */
    public static function listMetademands($type)
    {
        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.css.php");

        $config = PluginMetademandsConfig::getInstance();

        $meta = new PluginMetademandsMetademand();
        if ($config['display_type'] == 1) {
            $metademands = self::selectMetademands(false, "", $type);
            if (count($metademands) > 1) {

                echo "<div id='listmeta'>";

                if ($config['see_top'] && ($type == Ticket::INCIDENT_TYPE || $type == Ticket::DEMAND_TYPE)) {
                    self::showMostUsedMetademands($type);
                }
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

                        $icon = "fa-share-alt";
                        $name_meta = '';
                        if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
                            $name_meta = $meta->getName();
                        } else {
                            $name_meta = $n;
                        }
                        $comment_meta = '';
                        if (empty($comm = PluginMetademandsMetademand::displayField(
                            $meta->getID(),
                            'comment'
                        )) && !empty($meta->fields['comment'])) {
                            $comment_meta = $meta->fields['comment'];
                        } elseif (!empty(
                            $comm = PluginMetademandsMetademand::displayField(
                                $meta->getID(),
                                'comment'
                            ))) {
                            $comment_meta = $comm;
                        }

                        if (!empty($config['icon_incident']) && $type == Ticket::INCIDENT_TYPE) {
                            $icon = $config['icon_incident'];
                        }
                        if (!empty($config['icon_request']) && $type == Ticket::DEMAND_TYPE) {
                            $icon = $config['icon_request'];
                        }
                        if (!empty($config['icon_problem']) && $type == "Problem") {
                            $icon = $config['icon_problem'];
                        }
                        if (!empty($config['icon_change']) && $type == "Change") {
                            $icon = $config['icon_change'];
                        }
                        if (!empty($meta->fields['icon'])) {
                            $icon = $meta->fields['icon'];
                        }

                        echo "<a class='bt-buttons' title=\"" . Glpi\RichText\RichText::getTextFromHtml($comment_meta) . "\" href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=" . PluginMetademandsMetademand::STEP_SHOW . "'>";
                        echo '<div class="btnsc-normal" >';
                        $fasize = "fa-3x";
                        echo "<div class='center'>";
                        echo "<i class='bt-interface fa-menu-md fas $icon $fasize' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>";//$style
                        echo "</div>";
                        echo "<br><p style='font-size: 14px;'>";
                        echo Html::resume_text($name_meta, 40);

                        if (!empty($comment_meta)) {
                            echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                            echo Html::resume_text($comment_meta, 50);
                            echo "</span></em>";
                        }

                        if ($config['use_draft']) {
                            $count_drafts = PluginMetademandsDraft::countDraftsForUserMetademand(
                                Session::getLoginUserID(),
                                $id
                            );
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
            } elseif (count($metademands) == 1) {
                foreach ($metademands as $id => $name) {
                    $meta = new PluginMetademandsMetademand();
                    if ($meta->getFromDB($id)) {
                        $url = PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=" . PluginMetademandsMetademand::STEP_SHOW;
                        Html::redirect($url);
                    }
                }
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
            echo Html::submit(__('Next', 'metademands'), ['name' => 'next', 'class' => 'btn btn-primary']);
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
    public static function showMetademands(
        $metademands_id,
        $step,
        $current_ticket,
        $meta_validated,
        $preview = false,
        $options = [],
        $seeform = false,
        $block = 0
    ) {
        $parameters = ['itilcategories_id' => 0];

        // if given parameters, override defaults
        foreach ($options as $key => $value) {
            if (isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        $metademands = new PluginMetademandsMetademand();
        $metademands_data = PluginMetademandsMetademand::constructMetademands($metademands_id);
        $metademands->getFromDB($metademands_id);

        echo "<div class='md-basket-wizard'>";
        echo "</div>";

        $_SESSION['metademands_hide'] = [];

        echo "<div class='md-wizard'>";

        if (count($metademands_data)) {
            $see_summary = 0;
            if (isset($metademands->fields['is_basket']) && $metademands->fields['is_basket'] == 1) {
                $see_summary = 1;
            }

            foreach ($metademands_data as $form_step => $data) {
                if ($form_step == $step) {
                    foreach ($data as $form_metademands_id => $line) {

                        $fields = $line['form'];
                        if ($block > 0) {
                            $fieldsbyblock = [];
                            foreach ($fields as $fid => $field) {
                                if ($field['rank'] == $block) {
                                    $fieldsbyblock[] = $fields[$fid];
                                }
                            }
                            $fields = $fieldsbyblock;
                        }

                        self::constructForm(
                            $metademands_id,
                            $metademands_data,
                            $step,
                            $fields,
                            $preview,
                            $parameters['itilcategories_id'],
                            $seeform,
                            $current_ticket,
                            $meta_validated
                        );

                        if ($seeform == 0) {
                            unset($_SESSION['plugin_metademands'][$metademands_id]['fields']);
                        }

                        if ($metademands->fields['is_order'] == 1) {
                            if (!$preview
                                && countElementsInTable(
                                    "glpi_plugin_metademands_basketlines",
                                    [
                                        "plugin_metademands_metademands_id" => $metademands->fields['id'],
                                        "users_id" => Session::getLoginUserID(),
                                    ]
                                )
                            ) {
                                echo "<div style='text-align: center; margin-top: 20px; margin-bottom : 20px;' class=\"bt-feature col-md-12\">";
                                $title = "<i class='fas fa-plus' data-hasqtip='0' aria-hidden='true'></i>&nbsp;";
                                $title .= _sx('button', 'Add to basket', 'metademands');
                                echo Html::submit($title, [
                                    'name' => 'add_to_basket',
                                    'id' => 'add_to_basket',
                                    'class' => 'btn btn-primary',
                                ]);

                                echo "</div>";
                            }
                        }
                        echo Html::hidden('form_metademands_id', ['value' => $form_metademands_id]);
                        echo Html::hidden('is_private', ['value' => 1]);
                    }
                }
            }
            $use_as_step = 0;
            if ($preview) {
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
                $style = "";
                if ($see_summary == 0) {
                    $style = "style='margin-top: 20px'";
                }
                echo "<div class=\"row\" style='width: 100%;'>";

                echo "<div class=\"bt-feature col-md-12\" $style >";
                if ($current_ticket > 0 && !$meta_validated) {
                    Html::hidden('current_ticket_id', ['value' => $current_ticket]);
                }
                echo Html::hidden('metademands_id', ['value' => $metademands_id]);

                echo "</div>";
                echo "</div>";
            }

            echo Html::hidden('create_metademands', ['value' => 1]);

            if (isset($options['ancestor_tickets_id'])) {
                echo Html::hidden('ancestor_tickets_id', ['value' => $options['ancestor_tickets_id']]);
            }

            if ($metademands->fields['is_order'] == 1) {
                if (!countElementsInTable(
                    "glpi_plugin_metademands_basketlines",
                    [
                        "plugin_metademands_metademands_id" => $metademands->fields['id'],
                        "users_id" => Session::getLoginUserID(),
                    ]
                )) {
                    $title = "<i class='fas fa-plus'></i>&nbsp;";
                    $title .= _sx('button', 'Add to basket', 'metademands');
                    echo Html::submit($title, [
                        'name' => 'add_to_basket',
                        'id' => 'add_to_basket',
                        'class' => 'metademand_next_button btn btn-primary',
                    ]);
                } else {
                    echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
                    echo "</div>";
                    $title = "<i class='fas fa-save'></i>&nbsp;";
                    $title .= _sx('button', 'Validate your basket', 'metademands');
                    echo Html::hidden('see_basket_summary', ['value' => 1]);
                    echo Html::submit($title, [
                        'name' => 'next_button',
                        'form' => '',
                        'id' => 'submitjob',
                        'class' => 'metademand_next_button btn btn-success',
                    ]);
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
                                   url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/createmetademands.php?metademands_id=' + meta_id + '&step=2',
                                   type: 'POST',
                                   datatype: 'html',
                                   data: $('#wizard_form').serializeArray(),
                                   success: function (response) {
                                      $('#ajax_loader').hide();
                                      $('.md-wizard').replaceWith(response);
                                   },
                                   error: function (xhr, status, error) {
                                      console.log(xhr);
                                      console.log(status);
                                      console.log(error);
                                   }
                                });
                          });
                        </script>";
                }
            }
            if (!$preview
                && $metademands->fields['step_by_step_mode']  == 0
                && $see_summary == 0) {
                echo "<br><a href='#' class='metademand_middle_button' onclick='window.print();return false;'>";
                echo "<i class='fas fa-2x fa-print' style='color:#e3e0e0;'></i>";
                echo "</a>";
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
            echo "</div></div>";
        }
        echo "</div>";
    }


    public static function getDefaultParams($metademands, $preview, $seeform, $current_ticket, $meta_validated)
    {

        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $token = Session::getNewCSRFToken();

        $title = _sx('button', 'Save & Post', 'metademands');
        $childs_meta = PluginMetademandsMetademandTask::getChildMetademandsToCreate($metademands->fields['id']);
        if (count($childs_meta) > 0) {
            $title = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
        }
        $see_summary = 0;
        if ($metademands->fields['is_basket'] == 1) {
            $title = _sx('button', 'See basket summary & send it', 'metademands');
            $see_summary = 1;
        }
        $submittitle = "<i class=\"fas fa-save\"></i>&nbsp;" . $title;

        $block_id = $_SESSION['plugin_metademands'][$metademands->fields['id']]['block_id'] ?? 0;

        $block_current_id_stepform = $_SESSION['plugin_metademands'][$metademands->fields['id']]['block_id'] ?? 99999999;

        $listStepBlocks = [];

        $use_as_step = 0;
        $stepConfig = new PluginMetademandsConfigstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->fields['id']]);

        if ($metademands->fields['step_by_step_mode'] == 1) {
            if (isset($stepConfig->fields['step_by_step_interface'])) {
                switch ($stepConfig->fields['step_by_step_interface']) {
                    case PluginMetademandsConfigstep::BOTH_INTERFACE:
                        $use_as_step = 1;
                        break;
                    case PluginMetademandsConfigstep::ONLY_HELPDESK_INTERFACE:
                        if (Session::getCurrentInterface() == 'helpdesk') {
                            $use_as_step = 1;
                        }
                        break;
                    case PluginMetademandsConfigstep::ONLY_CENTRAL_INTERFACE:
                        if (Session::getCurrentInterface() == 'central') {
                            $use_as_step = 1;
                        }
                        break;
                }
            }
        }
        if ($preview) {
            $use_as_step = 0;
        }

        if ($use_as_step == 1) {
            $listStepBlocks = PluginMetademandsStep::defineStepblocks($metademands->fields['id']);
        }

        $updatestepform = 0;
        $havenextuser = true;
        if (!isset($stepConfig->fields['supervisor_validation'])
            && !isset($stepConfig->fields['link_user_block'])
            && !isset($stepConfig->fields['multiple_link_groups_blocks'])) {
            $havenextuser = false;
        } elseif ($block_current_id_stepform != 99999999) {
            $canSeeNextBlock = PluginMetademandsStep::canSeeBlock(
                $metademands->fields['id'],
                $block_current_id_stepform + 1
            );
            if (!$canSeeNextBlock) {
                $havenextuser = true;
                $updatestepform = 1;
            }
        }

        $fields = new PluginMetademandsField();
        $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands->fields['id']]);
        $all_meta_fields = [];
        if (is_array($fields_data) && count($fields_data) > 0) {
            foreach ($fields_data as $data) {
                $label = "";
                if (isset($data['name'])) {
                    $label = Toolbox::stripslashes_deep($data['name']);
                }
                $metademand_params = new PluginMetademandsFieldParameter();
                $metademand_params->getFromDBByCrit(
                    ["plugin_metademands_fields_id" => $data["id"]]
                );
                $all_meta_fields[$data['id']] = (isset($metademand_params->fields['hide_title'])
                    && $metademand_params->fields['hide_title'] == 1) ? PluginMetademandsField::getFieldTypesName(
                        $data['type']
                    ) : $label;
            }
        }
        $json_all_meta_fields = json_encode($all_meta_fields);

        $paramUrl = "";
        if ($current_ticket > 0 && !$meta_validated) {
            $paramUrl = "current_ticket_id=$current_ticket&meta_validated=$meta_validated&";
        }

        $stepConfig = new PluginMetademandsConfigstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->getID()]);
        if ($metademands->fields['step_by_step_mode'] == 1
            && $stepConfig->fields['see_blocks_as_tab'] == 1  && !$preview) {
            $block_id = 0;
            if (isset($_REQUEST['block_id'])) {
                $block_id = $_REQUEST['block_id'];
            }
        }

        $metaparams['root_doc'] = $root_doc;
        $metaparams['token'] = $token;
        $metaparams['ID'] = $metademands->fields['id'];
        $metaparams['useconfirm'] = $metademands->fields['use_confirm'];
        $metaparams['confirmmsg'] = Toolbox::addslashes_deep(__("You have not entered any values. Is this normal?", 'metademands'));
        $metaparams['nameform'] = Toolbox::addslashes_deep(
            $metademands->fields['name']
        ) . "_" . $_SESSION['glpi_currenttime'] . "_" . $_SESSION['glpiID'];
        $metaparams['paramUrl'] = $paramUrl;
        $metaparams['seeform'] = $seeform;

        //MSG
        $metaparams['nexttitle'] = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
        $metaparams['submittitle'] = $submittitle;
        $metaparams['alert'] = __('Thanks to fill mandatory fields', 'metademands');
        $metaparams['alert_regex'] = __("These fields don\'t respect regex", 'metademands');
        //End MSG

        //Use as step parameters
        $metaparams['use_as_step'] = $use_as_step;
        $metaparams['listStepBlocks'] = $listStepBlocks;
        $metaparams['nextsteptitle'] = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
        $metaparams['submitsteptitle'] = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & send to another user / group', 'metademands');
        //For multi User forms
        $metaparams['havenextuser'] = $havenextuser;
        $metaparams['updatestepform'] = $updatestepform;
        //End For multi User forms

        //End Use as step parameters

        //Basket parameters
        $metaparams['see_summary'] = $see_summary;
        //End Basket parameters

        //For alert for validate script
        $metaparams['json_all_meta_fields'] = $json_all_meta_fields;



        //For block as tab ?
        $metaparams['block_id'] = $block_id;

        return $metaparams;
    }

    public static function getConditionsParams($metademands)
    {

        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $metaparams['root_doc'] = $root_doc;

        $title = _sx('button', 'Save & Post', 'metademands');

        $childs_meta = PluginMetademandsMetademandTask::getChildMetademandsToCreate($metademands->fields['id']);
        if (count($childs_meta) > 0) {
            $title = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
        }
        $submittitle = "<i class=\"fas fa-save\"></i>&nbsp;" . $title;


        $metaparams['submittitle'] = $submittitle;
        $metaparams['nextsteptitle'] = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";

        $use_condition = false;

        $show_rule = $metademands->fields['show_rule'];

        if ($show_rule != PluginMetademandsCondition::SHOW_RULE_ALWAYS) {
            $condition = new PluginMetademandsCondition();
            $conditions = $condition->find(['plugin_metademands_metademands_id' => $metademands->fields['id']]);
            if (count($conditions) > 0) {
                $use_condition = true;
            }
        }

        //Condition params
        $metaparams['use_condition'] = $use_condition;
        $metaparams['show_rule'] = $show_rule;

        $metaparams['show_button'] = 1;
        if ($show_rule == PluginMetademandsCondition::SHOW_RULE_HIDDEN) {
            $metaparams['show_button'] = 0;
        }
        $metaparams['use_richtext'] = 0;
        $richtext_id = [];

        $richtext_fields = getAllDataFromTable(
            "glpi_plugin_metademands_fields",
            ['plugin_metademands_metademands_id' => $metademands->fields['id'], 'type' => 'textarea']
        );
        foreach ($richtext_fields as $f) {
            $fieldparameter = new PluginMetademandsFieldParameter();
            if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $f['id']])) {
                if ($fieldparameter->fields['use_richtext'] == 1) {
                    $metaparams['use_richtext'] = 1;
                    $richtext_id[] = $f['id'];
                }
            }
        }
        $metaparams['richtext_id'] = json_encode($richtext_id);

        //End Condition params
        return $metaparams;
    }


    /**
     * Display a metademand's content
     * @param $metademands_id int PluginMetademandsMetademand id, metademand to display
     * @param array $lines array of PluginMetademandsField which need to be displayed
     * @param       $metademands_data
     * @param bool $preview
     * @param int $itilcategories_id
     */
    public static function constructForm(
        $metademands_id,
        $metademands_data,
        $step,
        $lines = [],
        $preview = false,
        $itilcategories_id = 0,
        $seeform = false,
        $current_ticket = 0,
        $meta_validated = 1,
        $draft_id = 0,
        $draft_name = ""
    ) {
        global $CFG_GLPI;

        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($metademands_id);

        //Redirected after end user Step
        $user_id = Session::getLoginUserID();
        $url = $CFG_GLPI['root_doc'] . PLUGIN_METADEMANDS_DIR_NOFULL . "/front/wizard.form.php";
        if (isset($_SESSION['plugin_metademands'][$user_id]['redirect_wizard'])) {
            if (Plugin::isPluginActive('servicecatalog')
                && Session::haveRight("plugin_servicecatalog", READ)) {
                if (PluginServicecatalogConfig::getConfig()->getMultiEntityRedirection()) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php?changeactiveentity");
                } else {
                    $type = $metademands->fields['type'];
                    if ($type > 0) {
                        $url = PLUGIN_SERVICECATALOG_WEBDIR . "/front/choosecategory.form.php?type=$type&level=1";
                    } else {
                        $url = PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php";
                    }
                }
            }
            unset($_SESSION['plugin_metademands'][$user_id]);
            Html::redirect($url);
        }
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        $block_current_id_stepform = $_SESSION['plugin_metademands'][$metademands_id]['block_id'] ?? 99999999;
        if ($block_current_id_stepform != 99999999) {
            if (!PluginMetademandsStep::canSeeBlock($metademands_id, $block_current_id_stepform)
                && $preview == false) {
                Session::addMessageAfterRedirect(__('You do not have access to the form', 'metademands'));
                Html::redirect($url);
            }
        }
        $lineForStepByStep = [];
        $data_form = [];
        $values_saved = $_SESSION['plugin_metademands'][$metademands_id]['fields'] ?? [];

        // fields arranged by their ranks
        $allfields = [];
        foreach ($lines as $fields) {
            if (array_key_exists($fields["rank"], $allfields)) {
                $allfields[$fields["rank"]][] = $fields;
            } else {
                $allfields[$fields["rank"]] = [$fields];
            }
            if ($metademands->fields['step_by_step_mode'] == 1 && $fields['rank'] < $block_current_id_stepform) {
                $lineForStepByStep[$fields['id']] = $fields;
                if (isset($values_saved[$fields['id']])) {
                    $data_form[$fields['id']] = $values_saved[$fields['id']];
                }
            }
        }

        //Prepare subblocks
        $subblocks = [];
        $subblocks_data = [];
        foreach ($allfields as $blockid => $blockfields) {
            foreach ($blockfields as $value) {

                $fieldopt = new PluginMetademandsFieldOption();
                if ($opts = $fieldopt->find(
                    [
                        "plugin_metademands_fields_id" => $value['id'],
                        "hidden_block_same_block" => 1,
                    ]
                )) {
                    foreach ($opts as $opt) {
                        $subblocks[] = $opt['hidden_block'];
                    }
                }
            }
            if (count($subblocks) > 0) {
                foreach ($blockfields as $value) {
                    if (in_array($value['rank'], $subblocks)) {
                        $subblocks_data[$value['rank']][] = $value;
                    }
                }
            }
        }

        $use_as_step = 0;
        $stepConfig = new PluginMetademandsConfigstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id]);

        if ($metademands->fields['step_by_step_mode'] == 1) {
            if (isset($stepConfig->fields['step_by_step_interface'])) {
                switch ($stepConfig->fields['step_by_step_interface']) {
                    case PluginMetademandsConfigstep::BOTH_INTERFACE:
                        $use_as_step = 1;
                        break;
                    case PluginMetademandsConfigstep::ONLY_HELPDESK_INTERFACE:
                        if (Session::getCurrentInterface() == 'helpdesk') {
                            $use_as_step = 1;
                        }
                        break;
                    case PluginMetademandsConfigstep::ONLY_CENTRAL_INTERFACE:
                        if (Session::getCurrentInterface() == 'central') {
                            $use_as_step = 1;
                        }
                        break;
                }
            }
        }
        if ($preview) {
            $use_as_step = 0;
        }

        $see_summary = 0;
        if ($metademands->fields['is_basket'] == 1) {
            $see_summary = 1;
        }

        $hidden_blocks = [];
        $all_hidden_blocks = [];


        $columns = 2;
        $cpt = 0;

        $basketline = new PluginMetademandsBasketline();
        if ($basketlinesFind = $basketline->find([
            'plugin_metademands_metademands_id' => $metademands_id,
            'users_id' => Session::getLoginUserID(),
        ])) {
            echo "<div class='alert alert-warning d-flex'>";
            echo "<b>" . __('You have items on your basket', 'metademands') . "</b></div>";
        }

        if (count($lines)) {
            if ($use_as_step == 0) {
                echo "<div class='tab-nostep'>";
                $cpt = 1;
            }
            // #meta-form to avoid hijacking the whole page
            // e.preventDefault() to avoid reloading the page and lose filled values
            echo Html::scriptBlock(
                '$("#meta-form").keypress(function(e){
                            if (e.which == 13){
                                var target = $(e.target);
                                if(!target.is("textarea")) {
                                     e.preventDefault();
                                     $("#submitjob").click();
                                     $("#nextBtn").click();
                                }
                            }
                });'
            );

            $metaparams = self::getDefaultParams($metademands, $preview, $seeform, $current_ticket, $meta_validated);

            $metaconditionsparams = self::getConditionsParams($metademands);

            if ($metademands->fields['is_basket'] == 1) {
                echo Html::hidden('see_basket_summary', ['value' => 1]);
            }

            $displayBlocksAsTab = 0;
            if ($metademands->fields['step_by_step_mode'] == 1
                && isset($stepConfig->fields['see_blocks_as_tab'])
                && $stepConfig->fields['see_blocks_as_tab'] == 1) {
                $displayBlocksAsTab = 1;
            }
            //Prepare subblocks
            if (!$preview) {
                if (count($subblocks) > 0) {
                    foreach ($subblocks as $subblock) {
                        unset($allfields[$subblock]);
                    }
                }
            }

            echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
            echo "</div>";

            if ($metademands->fields['step_by_step_mode'] == 1
                && $displayBlocksAsTab == 1  && !$preview) {

                foreach ($metaparams as $key => $val) {
                    if (isset($metaparams[$key])) {
                        $$key = $metaparams[$key];
                    }
                }

                foreach ($metaconditionsparams as $key => $val) {
                    if (isset($metaconditionsparams[$key])) {
                        $$key = $metaconditionsparams[$key];
                    }
                }

                echo Html::scriptBlock(
                    "$(document).ready(function () {
                        var hash = window.location.hash;
                        var fieldid = sessionStorage.getItem('loadedblock');
                        var block_id = $block_id;
                        
                        window.metademandparams = {};
                        
                        metademandparams.root_doc = '$root_doc';
                        metademandparams.paramUrl = '$paramUrl';
                        metademandparams.token = '$token';
                        metademandparams.id = '$ID';
                        metademandparams.nameform = '$nameform';
                        metademandparams.block_id = '$block_id';
                        
                        metademandparams.nexttitle = '$nexttitle';
                        metademandparams.submittitle = '$submittitle';
                        metademandparams.msg = '$alert';
                        metademandparams.msg_regex = '$alert_regex';
                        
                        metademandparams.seesummary = '$see_summary';
                        
                        metademandparams.json_all_meta_fields = {$json_all_meta_fields};
                        metademandparams.currentTab = 0; // Current tab is set to be the first tab (0)
                       
                        metademandparams.use_condition = '$use_condition';
                        metademandparams.show_rule = '$show_rule';
                        metademandparams.show_button = '$show_button';
                        metademandparams.use_richtext = '$use_richtext';
                        metademandparams.richtext_ids = {$richtext_id};
                        
                        metademandparams.use_as_step = '$use_as_step';
                        metademandparams.listStepBlock = [" . implode(",", $listStepBlocks) . "];
                        metademandparams.havenextuser = '$havenextuser';
                        metademandparams.updatestepform = '$updatestepform';
                        metademandparams.submitsteptitle = '$submitsteptitle';
                        metademandparams.nextsteptitle = '$nextsteptitle';

                        if (fieldid && document.getElementById(fieldid)) {
                            updateActiveTab(fieldid.replace('block', ''));
                            hash = '#' + fieldid;
                        } else if (hash.startsWith('#block') && document.getElementById(hash.substring(1))) {
                            updateActiveTab(hash.replace('#block', ''));
                        } else {
                            if (block_id > 0) {
                            } else {
                              block_id = 1;
                            }
                            updateActiveTab(block_id);
                            sessionStorage.setItem('loadedblock', 'block' + block_id);
                            window.location.hash = '#block' + block_id;
                        }

//                    $('#fieldslist a').click(function (e) {
//                        e.preventDefault();
//
//                         var tabId = $(this).attr('href').replace('#block', '');
//                         var loadedId = sessionStorage.getItem('loadedblock').replace('block', '');
//
//                         var clicbloc = parseInt(tabId.replace('block', ''));
////                         console.log(clicbloc);
////                         console.log(parseInt(loadedId));
//                         
//                         if (clicbloc > parseInt(loadedId)) {
//                         
//                                sessionStorage.setItem('loadedblock', clicbloc);
//                                updateActiveTab(tabId.replace('block', ''));
//                                window.location.hash = '#block' + tabId;
//                                plugin_metademands_wizard_nextBtn(1, metademandparams, metademandconditionsparams);
//                                
//                        } else if (clicbloc <= parseInt(loadedId)) {
//                                
//                                sessionStorage.setItem('loadedblock', clicbloc);
//                                updateActiveTab(tabId.replace('block', ''));
//                                window.location.hash = '#block' + tabId;
//                                plugin_metademands_wizard_prevBtn(clicbloc, metademandparams, metademandconditionsparams);
//                        }
//
//                    });

                    $('ul.nav-tabs > li > a').on('shown.bs.tab', function (e) {
                        var id = $(e.target).attr('href').substr(1);
                        sessionStorage.setItem('loadedblock', id);
                        window.location.hash = id;
                    });
                });"
                );

                foreach ($allfields as $blockid => $blockfields) {
                    $i = 0;

                    foreach ($blockfields as $value) {
                        if ($value['type'] == 'title-block' && $value['rank'] == $blockid) {
                            $i++;
                            if ($i > 0) {
                                $name = $value['name'];
                                if ($debug || $preview) {
                                    $name .= " #$blockid";
                                }
                                $blocks[$blockid] = $name;
                            }
                        }
                        if ($i == 0) {
                            $title = __('Block', 'metademands') . " " . $value['rank'];
                            $blocks[$blockid] = $title;
                        }
                    }
                }
                if (count($blocks) > 0) {
                    echo "<div class='tabs-container'>";
                    echo "<button form='' class='scroll-btn scroll-left'><i class='fas fa-chevron-left'></i></button>";
                    echo "<div class='d-flex flex-nowrap scrollable-tabs'>";
                    echo "<ul class='nav nav-tabs flex-nowrap' style='border-bottom:unset' role='tablist' id='fieldslist'>";
                    $hiddenblocks = [];
                    foreach ($blocks as $idblock => $block) {
                        $nameblock = $block;

                        $display = 'display:block';
                        if ($idblock == 1) {
                            $display = 'display:block';
                        }
                        $field = new PluginMetademandsField();

                        $fieldsmeta = $field->find(["plugin_metademands_metademands_id" => $metademands->getID()]);
                        foreach ($fieldsmeta as $fieldmeta) {
                            $fieldopt = new PluginMetademandsFieldOption();
                            if ($opts = $fieldopt->find(
                                [
                                    "plugin_metademands_fields_id" => $fieldmeta['id'],
                                    "hidden_block" => $idblock,
                                ]
                            )) {
                                foreach ($opts as $opt) {
                                    $hiddenblocks[] = $opt['hidden_block'];
                                }
                            }
                        }
                        if (in_array($idblock, $hiddenblocks)) {
                            $display = 'display:none';
                        }

                        echo "<li class='nav-item'>";
                        echo "<a class='nav-link tablinks' style='$display' id='ablock$idblock' href='#block" . $idblock . "' data-toggle='tab'>" . $nameblock . "</a>";
                        echo "</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    echo "<button form='' class='scroll-btn scroll-right'><i class='fas fa-chevron-right'></i></button>";
                    echo "</div>";

                    echo Html::scriptBlock(
                        'setTimeout(() => {
                                    const scrollContainer = document.querySelector(".scrollable-tabs");
                                    const scrollLeftBtn = document.querySelector(".scroll-left");
                                    const scrollRightBtn = document.querySelector(".scroll-right");
                                
                                    if (scrollLeftBtn && scrollRightBtn && scrollContainer) {
                                        scrollLeftBtn.addEventListener("click", function () {
                                            scrollContainer.scrollBy({ left: -150, behavior: "smooth" });
                                        });
                                
                                        scrollRightBtn.addEventListener("click", function () {
                                            scrollContainer.scrollBy({ left: 150, behavior: "smooth" });
                                        });
                                    }
                                }, 500);
            '
                    );
                }
            }

            foreach ($allfields as $block => $line) {

                if ($use_as_step == 1 && $metademands->fields['is_order'] == 0) {
                    if (!in_array($block, $all_hidden_blocks)) {
                        echo "<div class='tab-step'>";
                        $cpt++;
                    }
                }

                self::displayBlockContent($metademands, $metademands_data, $preview, $block, $line, $subblocks_data, $itilcategories_id);


                if ($use_as_step == 1 && $metademands->fields['is_order'] == 0) {
                    if (!in_array($block, $all_hidden_blocks)) {
                        echo "</div>";
                    }
                }
            }
            if ($use_as_step == 0) {
                echo "</div>";
            }

            if ($metademands->fields['is_order'] == 0
                && !$preview) {
                //TO DROP ?
                //                (isset($options['resources_id'])
                //                    && $options['resources_id'] > 0)
                if ($current_ticket > 0
                    && (((!$meta_validated
                            && !$metademands->fields['can_update']) ||
                        ($meta_validated
                            && !$metademands->fields['can_clone']))
                    || !Session::haveRight('plugin_metademands_updatemeta', READ))) {
                    return false;
                }
                echo "<div class=\"form-sc-group\">";
                echo "<div class='center'>";

                echo "<div style='overflow:auto;'>";

                if ($use_as_step == 1) {
                    echo "<br><div id='nextMsg' class='alert alert-info center'>";
                    echo "</div>";
                }

                $config = PluginMetademandsConfig::getInstance();
                if ($config['use_draft']
                    && $draft_id == 0) {
                    //button create draft
                    echo PluginMetademandsDraft::createDraftInput(PluginMetademandsDraft::DEFAULT_MODE);
                }

                if (Session::haveRight("plugin_metademands_cancelform", READ)
                    && isset(
                        $_SESSION['plugin_metademands'][$metademands->getID()]['plugin_metademands_stepforms_id']
                    )) {
                    $target = PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.form.php";
                    $plugin_metademands_stepforms_id = $_SESSION['plugin_metademands'][$metademands->getID(
                    )]['plugin_metademands_stepforms_id'];
                    echo "<span style='color:darkred;font-size: 14px !important;margin-right: 8px'>";
                    Html::showSimpleForm(
                        $target,
                        'delete_form_from_list',
                        _sx('button', 'Cancel form', 'metademands'),
                        ['plugin_metademands_stepforms_id' => $plugin_metademands_stepforms_id],
                        //                        'fa-trash-alt fa-2x'
                    );
                    echo "</span>";
                }

                echo "<button type='button' id='prevBtn' style='margin-right: 8px;font-size: 14px !important;' class='btn btn-primary'>";
                echo "<i class='ti ti-chevron-left'></i>&nbsp;" . __('Previous', 'metademands') . "</button>";

                echo "&nbsp;<button type='button' id='nextBtn' style='margin-right: 8px;font-size: 14px !important;'  class='btn btn-primary'>";
                echo __('Next', 'metademands') . "&nbsp;<i class='ti ti-chevron-right'></i></button>";


                //                echo "</span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";

                if ($see_summary == 0
                    && $displayBlocksAsTab == 0) {
                    //Circles which indicates the steps of the form:
                    echo "<div class='step_wizard_div center'>";

                    if ($cpt > 1) {
                        for ($j = 1; $j <= $cpt; $j++) {
                            echo "<span class='step_wizard'></span>";
                        }
                    } else {
                        echo "<span class='step_wizard' style='display: none'></span>";
                    }

                    echo "</div>";
                }

                if (!empty($data_form)) {
                    $modal_html = '';
                    $parent_fields = PluginMetademandsMetademand::formatFields(
                        $lineForStepByStep,
                        $metademands_id,
                        [$metademands_id => $data_form],
                        []
                    );
                    $form = new PluginMetademandsStepform();
                    if (isset($_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id'])
                        && $form->getFromDBByCrit(
                            ['id' => $_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id']]
                        )) {
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

                        $modal_html .= Glpi\RichText\RichText::getSafeHtml($parent_fields['content']);
                        $title = __('Previous data edited', 'metademands');
                        $setting_dialog = json_encode(stripslashes($modal_html));
                        echo Html::scriptBlock(
                            "$(function() {
                                                    glpi_html_dialog({
                                                         title: '$title',
                                                         body: {$setting_dialog},
                                                         dialogclass: 'modal-lg',
                                                    });
                                                });"
                        );

                        if (isset($_SESSION['plugin_metademands'][$metademands_id]['hidden_blocks'])) {
                            if (is_array($_SESSION['plugin_metademands'][$metademands_id]['hidden_blocks'])) {
                                $hidden_blocks = $_SESSION['plugin_metademands'][$metademands_id]['hidden_blocks'];
                                $script = "";
                                foreach ($hidden_blocks as $hidden_b) {
                                    foreach ($hidden_b as $hidden_) {
                                        $script .= "$('div[bloc-id=\"bloc$hidden_\"]').hide();";
                                    }
                                }
                                echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
                            }
                        }
                    }
                }


                if (isset($_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id'])) {
                    echo Html::hidden(
                        'plugin_metademands_stepforms_id',
                        ['value' => $_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id']]
                    );
                }

                echo "<span id = 'modalgroupspan'>";
                echo "</span>";
                echo "<a id='backtotop'></a>";
                //                Modal Bootstrap confirmation
                echo "<div class='modal fade' id='confirmationModal' tabindex='-1' role='dialog' aria-labelledby='confirmationModalLabel' aria-hidden='true'>";
                echo "<div class='modal-dialog modal-dialog-centered' role='document'>";
                echo "<div class='modal-content'>";
                echo "<div class='modal-header'>";
                echo "<h5 class='modal-title' id='confirmationModalLabel'>" . __('Confirmation', 'metademands') . "</h5>";
                echo "<button type='button' class='close btn-close' data-bs-dismiss='modal' aria-label='" . __('Close') . "'></button>";
                echo "</div>";
                echo "<div class='modal-body'>";
                echo __("You have not entered any values. Is this normal?", 'metademands');
                echo "</div>";
                echo "<div class='modal-footer'>";
                echo "<button type='button' class='btn btn-secondary' id='confirmNo' data-bs-dismiss='modal'>" . __('No') . "</button>";
                echo "<button type='button' class='btn btn-primary' id='confirmYes' data-bs-dismiss='modal'>" . __('Yes') . "</button>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                self::validateScript($metaparams, $metaconditionsparams);
            }

            if ($draft_id != 0) {

                echo "<div class='boutons_draft' >";
                echo "<button form='' id='button_save_mydraft' class='submit btn btn-success btn-sm update_draft' onclick=\"updateThisDraft(" . $draft_id . ", '" . $draft_name . "')\">";
                echo __('Upgrade');
                echo "</button>";

                echo "<button form='' class='submit btn btn-danger btn-sm delete_draft' onclick=\"deleteThisDraft(" . $draft_id . ")\">";
                echo __('Delete');
                echo "</button>";
                echo "</div>";

                $users_id = Session::getLoginUserID();
                $trad = __('Careful all the lines are not confirm, are you sure you want to continue ?', 'metademands');

                echo "<script>
                        
                        function updateThisDraft(draft_id, draft_name) {
                            //Security in case of unconfirmed line
                            var tr_input = document.querySelectorAll('#freetable_table #tr_input input');
                            if (tr_input.length > 0) {
                                var careful = false;    
                            
                                for(var j = 0; j < tr_input.length; j++) {
                                   if(tr_input[j].value != '' && tr_input[j].value != '0'){
                                        careful = true;
                                   } 
                                }
                                
                                if(careful){
                                    if (!confirm('{$trad}')) {   
                                        return;
                                    }
                                }
                                
                            }
    
                            if(typeof tinyMCE !== 'undefined'){
                                tinyMCE.triggerSave();
                            }
    
                            jQuery('.resume_builder_input').trigger('change');
                            $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                            $('#ajax_loader').show();
                            arrayDatas = $('#wizard_form.formCustomDraft').serializeArray();
                            arrayDatas.push({name: \"save_draft\", value: true});
                            arrayDatas.push({name: \"plugin_metademands_drafts_id\", value: draft_id});
                            arrayDatas.push({name: \"draft_name\", value: draft_name});
                            arrayDatas.push({name: \"step\", value: 2});
                            arrayDatas.push({name: \"fied\", value: ''});
                            arrayDatas.push({name: \"_users_id_requester\", value: $users_id});
                            arrayDatas.push({name: \"metademands_id\", value: $metademands_id});
    
                            $.ajax({
                            url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/adddraft.php',
                               type: 'POST',
                               data: arrayDatas,
                               success: function(response){
                                   window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/draft.form.php?id='+draft_id
                                },
                               error: function(xhr, status, error) {
                                  console.log(xhr);
                                  console.log(status);
                                  console.log(error);
                                }
                            });
                        }
    
                        function deleteThisDraft(draft_id) {
                              var self_delete = true;
                              $('#ajax_loader').show();
                              $.ajax({
                                 url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/deletedraft.php',
                                    type: 'POST',
                                    data:
                                      {
                                        users_id:$users_id,
                                        plugin_metademands_metademands_id: $metademands_id,
                                        drafts_id: draft_id,
                                        self_delete: self_delete
                                      },
                                    success: function(response){
                                        $('#bodyDraft').html(response);
                                        $('#ajax_loader').hide();
                                        window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/draft.php'
                                     },
                                    error: function(xhr, status, error) {
                                       console.log(xhr);
                                       console.log(status);
                                       console.log(error);
                                     }
                                 });
                           };
                                
                        var check_free_table = document.querySelector('#freetable_table');
                        if(check_free_table){
                            document.querySelector('.boutons_draft #button_save_mydraft').style='display:none';    
                        }
    
                    </script>";
            }
        } else {
            echo "<div class='center'><b>" . __('No item to display') . "</b></div>";
        }
    }


    public static function displayBlockContent($metademands, $metademands_data, $preview, $block, $line, $subblocks_data, $itilcategories_id)
    {


        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        $keys = array_keys($line);

        $style = "";
        // Color
        if ($preview || $debug) {
            $color = PluginMetademandsField::setColor($block);
            $style .= "padding-top:5px;
                      padding-bottom:10px;
                      border-top :3px solid #" . $color . ";
                      border-left :3px solid #" . $color . ";
                      border-bottom :3px solid #" . $color . ";
                      border-right :3px solid #" . $color . ";";
            echo '<style type="text/css">
                       .preview-md-';
            echo $block;
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
            $style .= "background-color:" . $background_color . ";";
        }
        $styleasTab = "";

        $displayBlocksAsTab = 0;
        $stepConfig = new PluginMetademandsConfigstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->getID()]);
        if ($metademands->fields['step_by_step_mode'] == 1
            && isset($stepConfig->fields['see_blocks_as_tab'])
            && $stepConfig->fields['see_blocks_as_tab'] == 1) {
            $displayBlocksAsTab = 1;
        }
        //        if ($displayBlocksAsTab == 1) {
        //            if ($metademands->fields['hide_title'] == 0) {
        //                $styleasTab = "overflow: hidden;height: 550px;max-height: 550px;overflow-y: scroll;";
        //            } else {
        //                $styleasTab = "overflow: hidden;height: 650px;max-height: 650px;overflow-y: scroll;";
        //            }
        //        }

        echo "<div bloc-id='bloc" . $block . "' style='$styleasTab $style' class='card tab-sc-child-" . $block . "'>";

        if (($displayBlocksAsTab == 0 || $preview)
            && $line[$keys[0]]['type'] == 'title-block') {

            $data = $line[$keys[0]];
            $fieldparameter = new PluginMetademandsFieldParameter();
            if ($fieldparameter->getFromDBByCrit(
                ['plugin_metademands_fields_id' => $line[$keys[0]]['id']]
            )) {
                unset($fieldparameter->fields['plugin_metademands_fields_id']);
                unset($fieldparameter->fields['id']);

                $params = $fieldparameter->fields;
                $data = array_merge($line[$keys[0]], $params);
                if (isset($fieldparameter->fields['default'])) {
                    $line[$keys[0]]['default_values'] = PluginMetademandsFieldParameter::_unserialize(
                        $fieldparameter->fields['default']
                    );
                }

                if (isset($fieldparameter->fields['custom'])) {
                    $line[$keys[0]]['custom_values'] = PluginMetademandsFieldParameter::_unserialize(
                        $fieldparameter->fields['custom']
                    );
                }
            }

            $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
            $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

            //Block Title
            if (isset($line[$keys[0]]['type'])
                && in_array($line[$keys[0]]['type'], $allowed_customvalues_types)
                || in_array($line[$keys[0]]['item'], $allowed_customvalues_items)) {
                $field_custom = new PluginMetademandsFieldCustomvalue();
                if ($customs = $field_custom->find(
                    ["plugin_metademands_fields_id" => $line[$keys[0]]['id']],
                    "rank"
                )) {
                    if (count($customs) > 0) {
                        $line[$keys[0]]['custom_values'] = $customs;
                    }
                }
            }

            PluginMetademandsField::displayFieldByType(
                $metademands,
                $metademands_data,
                $data,
                $preview,
                $itilcategories_id
            );
        }

        echo "<div class='card-body' bloc-hideid='bloc" . $block . "'>";

        if ($preview || $debug) {
            echo "<div class=\"row preview-md preview-md-$block\" data-title='" . $block . "'>";
        } else {
            echo "<div class=\"row\" style='$style;padding: 0.5rem 0.5rem;padding-top: initial;'>";
        }

        foreach ($line as $key => $data) {
            self::displayBlockFields($metademands, $metademands_data, $preview, $keys, $line, $key, $data, $block, $itilcategories_id);
        }

        $subblocks = [];
        $check_values  = [];
        foreach ($line as $key => $data) {
            $fieldopt = new PluginMetademandsFieldOption();
            if ($opts = $fieldopt->find(
                [
                    "plugin_metademands_fields_id" => $data['id'],
                    "hidden_block_same_block" => 1,
                ]
            )) {
                foreach ($opts as $opt) {
                    $check_values[$opt['check_value']] = $opt['hidden_block'];
                }
                asort($check_values);
                $subblocks[$data['rank']] = $check_values;
            }
        }

        foreach ($subblocks as $subblock => $subfields) {
            // Display subblocks
            if ($block == $subblock) {
                if (count($subfields) > 0) {
                    foreach ($subfields as $checkvalue => $subfield) {
                        $subs = $subblocks_data[$subfield] ?? [];
                        if (count($subs) > 0) {
                            echo "<div class='col-md-12 md-bottom form-group row' bloc-id='subbloc" . $subfield . "'>";

                            foreach ($subs as $k => $sub) {
                                self::displayBlockFields(
                                    $metademands,
                                    $metademands_data,
                                    $preview,
                                    $keys,
                                    $subs,
                                    $k,
                                    $sub,
                                    $block,
                                    $itilcategories_id
                                );
                            }
                        }
                        echo "</div>";
                    }
                }

            }
        }


        echo "</div>";
        echo "</div>";
        echo "</div>";

    }


    public static function displayBlockFields($metademands, $metademands_data, $preview, $keys, $line, $key, $data, $block, $itilcategories_id)
    {

        $count = 0;
        $columns = 2;
        $style_left_right = 'padding: 0.5rem 0.5rem;';
        $keyIndexes = array_flip($keys);

        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        $config_link = "";
        if (Session::getCurrentInterface() == 'central' && $preview) {
            $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL(
                'PluginMetademandsField'
            ) . "?id=" . $data['id'] . "'>";
            $config_link .= "<i class='fas fa-wrench'></i></a>";
        }

        $fieldparameter = new PluginMetademandsFieldParameter();
        if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['id']])) {
            unset($fieldparameter->fields['plugin_metademands_fields_id']);
            unset($fieldparameter->fields['id']);

            $params = $fieldparameter->fields;
            $data = array_merge($data, $params);

            if (isset($fieldparameter->fields['default'])) {
                $data['default_values'] = PluginMetademandsFieldParameter::_unserialize(
                    $fieldparameter->fields['default']
                );
            }

            if (isset($fieldparameter->fields['custom'])) {
                $data['custom_values'] = PluginMetademandsFieldParameter::_unserialize(
                    $fieldparameter->fields['custom']
                );
            }
        }

        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        if (isset($data['type'])
            && (in_array($data['type'], $allowed_customvalues_types)
                || in_array($data['item'], $allowed_customvalues_items))
            && $data['item'] != "urgency"
            && $data['item'] != "priority"
            && $data['item'] != "impact") {
            $field_custom = new PluginMetademandsFieldCustomvalue();
            if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
                if (count($customs) > 0) {
                    $data['custom_values'] = $customs;
                }
            }
        }

        // Manage ranks ???
        if (isset($keyIndexes[$key])
            && isset($keys[$keyIndexes[$key] - 1])
            && $data['rank'] != $line[$keys[$keyIndexes[$key] - 1]]['rank']) {
            //End bloc-hideid
            echo "</div>";

            echo "</div>";
            echo "</div>";
            echo "<div bloc-id='bloc" . $block . "'>";

            // Title block field
            if ($data['type'] == 'title-block') {
                if ($preview || $debug) {
                    $color = PluginMetademandsField::setColor($block);
                    $style = 'padding-top:5px;
                                          padding-bottom:10px;
                                          border-top :3px solid #' . $color . ';
                                          border-left :3px solid #' . $color . ';
                                          border-right :3px solid #' . $color;
                    echo '<style type="text/css">
                                        .preview-md-';
                    echo $block;
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
                    echo "<div class=\"row preview-md preview-md-$block\" data-title='" . $block . "' style='$style'>";
                } else {
                    echo "<div>";
                }
                echo "<br><h4 class=\"alert alert-light\"><span style='color:" . $data['color'] . ";'>";

                if (empty($label = PluginMetademandsField::displayField($data['id'], 'name'))) {
                    $label = $data['name'];
                }

                echo $label;

                if ($debug) {
                    echo " (ID:" . $data['id'] . ")";
                }
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
                echo "<i id='up" . $block . "' class='fa-1x fas fa-chevron-up pointer' style='right:40px;position: absolute;color:" . $data['color'] . ";'></i>";
                $rand = mt_rand();
                echo Html::scriptBlock(
                    "var myelement$rand = '#up" . $block . "';
                                 var bloc$rand = 'bloc" . $block . "';
                                 $(myelement$rand).click(function() {     
                                     if($('[bloc-hideid =' + bloc$rand + ']:visible').length) {
                                         $('[bloc-hideid =' + bloc$rand + ']').hide();
                                         $(myelement$rand).toggleClass('fa-chevron-up fa-chevron-down');
                                     } else {
                                         $('[bloc-hideid =' + bloc$rand + ']').show();
                                         $(myelement$rand).toggleClass('fa-chevron-down fa-chevron-up');
                                     }
                                 });"
                );
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

            echo "<div bloc-hideid='bloc" . $block . "'>";

            if ($preview || $debug) {
                $color = PluginMetademandsField::setColor($block);
                echo '<style type="text/css">
                           .preview-md-';
                echo $block;
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
                echo "<div class=\"row preview-md preview-md-$block\" data-title='" . $block . "' style='$style'>";
            } else {
                $background_color = "";
                if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                    $background_color = $meta->fields['background_color'];
                }
                echo "<div class=\"row class1\" style='background-color: " . $background_color . ";$style_left_right'>";
            }

            $count = 0;
        }

        // Title field
        if ($data['type'] != 'title-block') {

            // end wrapper div classes
            //see fields
            PluginMetademandsField::displayFieldByType(
                $metademands,
                $metademands_data,
                $data,
                $preview,
                $itilcategories_id,
                $count
            );

        }

        // Next row
        if ($count > $columns) {
            if ($preview || $debug) {
                $color = PluginMetademandsField::setColor($data['rank']);
                $style_left_right = 'padding-bottom:10px;
                                       border-left :3px solid #' . $color . ';
                                       border-right :3px solid #' . $color;
            }

            $count = 0;
        }

        // If values are saved in session we retrieve it
        //needed to load twice
        if (isset($_SESSION['plugin_metademands'][$metademands->getID()]['fields'])) {

            foreach ($_SESSION['plugin_metademands'][$metademands->getID()]['fields'] as $id => $value) {
                if (strval($data['id']) === strval($id)) {
                    $data['value'] = $value;
                } elseif ($data['id'] . '-2' === $id) {
                    $data['value-2'] = $value;
                }
            }
        }

        if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['id']])) {
            unset($fieldparameter->fields['plugin_metademands_fields_id']);
            unset($fieldparameter->fields['id']);

            $params = $fieldparameter->fields;
            $data = array_merge($data, $params);

            if (isset($fieldparameter->fields['default'])) {
                $data['default_values'] = PluginMetademandsFieldParameter::_unserialize(
                    $fieldparameter->fields['default']
                );
            }

            if (isset($fieldparameter->fields['custom'])) {
                $data['custom_values'] = PluginMetademandsFieldParameter::_unserialize(
                    $fieldparameter->fields['custom']
                );
            }
        }

        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        if (isset($data['type'])
            && in_array($data['type'], $allowed_customvalues_types)
            || in_array($data['item'], $allowed_customvalues_items)) {
            $field_custom = new PluginMetademandsFieldCustomvalue();
            if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
                if (count($customs) > 0) {
                    $data['custom_values'] = $customs;
                }
            }
        }

        //verifie si une sous metademande doit etre lancé
        PluginMetademandsFieldOption::taskScript($data);

        //Active champs obligatoires sur les fields_link
        PluginMetademandsFieldOption::fieldsMandatoryScript($data);

        //Affiche les hidden_link
        PluginMetademandsFieldOption::fieldsHiddenScript($data);

        //cache ou affiche les hidden_block & child_blocks
        PluginMetademandsFieldOption::blocksHiddenScript($data);

        PluginMetademandsFieldOption::checkboxScript($data);

        PluginMetademandsFieldOption::checkConditions($data);
    }


    /**
     * @param $params
     * @return void
     */
    public static function validateScript($metaparams, $metaconditionsparams)
    {

        foreach ($metaparams as $key => $val) {
            if (isset($metaparams[$key])) {
                $$key = $metaparams[$key];
            }
        }

        foreach ($metaconditionsparams as $key => $val) {
            if (isset($metaconditionsparams[$key])) {
                $$key = $metaconditionsparams[$key];
            }
        }

        echo "<script>
                  $(document).ready(function (){

                    window.metademandparams = {};
                    metademandparams.useconfirm = '$useconfirm';
                    metademandparams.confirmmsg = '$confirmmsg';
                    metademandparams.root_doc = '$root_doc';
                    metademandparams.paramUrl = '$paramUrl';
                    metademandparams.seeform = '$seeform';
                    metademandparams.token = '$token';
                    metademandparams.id = '$ID';
                    metademandparams.nameform = '$nameform';
                    metademandparams.block_id = '$block_id';
                    
                    metademandparams.nexttitle = '$nexttitle';
                    metademandparams.submittitle = '$submittitle';
                   
                    metademandparams.msg = '$alert';
                    metademandparams.msg_regex = '$alert_regex';

                    metademandparams.seesummary = '$see_summary';
                    
                    metademandparams.json_all_meta_fields = {$json_all_meta_fields};
                    metademandparams.currentTab = 0; // Current tab is set to be the first tab (0)
                    
                    metademandparams.use_as_step = '$use_as_step';
                    metademandparams.listStepBlock = [" . implode(",", $listStepBlocks) . "];
                    metademandparams.havenextuser = '$havenextuser';
                    metademandparams.updatestepform = '$updatestepform';
                    metademandparams.submitsteptitle = '$submitsteptitle';
                    metademandparams.nextsteptitle = '$nextsteptitle';
                    
                    window.metademandconditionsparams = {};
                    metademandconditionsparams.root_doc = '$root_doc';
                    metademandconditionsparams.submittitle = '$submittitle';
                    metademandconditionsparams.nextsteptitle = '$nextsteptitle';
                    metademandconditionsparams.use_condition = '$use_condition';
                    metademandconditionsparams.show_rule = '$show_rule';
                    metademandconditionsparams.show_button = '$show_button';
                    metademandconditionsparams.use_richtext = '$use_richtext';
                    metademandconditionsparams.richtext_ids = {$richtext_id};
                    
                    const prevBtn = document.getElementById('prevBtn');
                    const nextBtn = document.getElementById('nextBtn');

                    firstnumTab = plugin_metademands_wizard_findFirstTab($block_id, metademandparams);
                    
                    plugin_metademands_wizard_showTab(firstnumTab, metademandparams, metademandconditionsparams);
                    
                    prevBtn.addEventListener('click', () => {
                      plugin_metademands_wizard_prevBtn(-1, firstnumTab, metademandparams, metademandconditionsparams);
                    });
                    
                    nextBtn.addEventListener('click', async () => {
                          const result = await plugin_metademands_wizard_nextBtn(1, firstnumTab, metademandparams, metademandconditionsparams);
                          if (result !== false) {
                            plugin_metademands_wizard_showTab(firstnumTab, metademandparams, metademandconditionsparams);
                          }
                        });
                  });
               </script>";
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
        if (isset($values['fields']['current_ticket_id']) && $values['fields']['current_ticket_id'] > 0) {
            $options['current_ticket_id'] = $values['fields']['current_ticket_id'];
        }
        if (isset($values['fields']['meta_validated'])) {
            $options['meta_validated'] = $values['fields']['meta_validated'];
        }

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

                            $check = $basketclass->getFromDBByCrit([
                                "plugin_metademands_metademands_id" => $metademands_id,
                                'plugin_metademands_fields_id' => $key,
                                'line' => $line,
                                'users_id' => Session::getLoginUserID(),
                                'name' => "upload",
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

                        $result = PluginMetademandsMetademand::addObjects($metademands_id, $datas, $options);
                        if (is_array($result)) {
                            Session::addMessageAfterRedirect($result['message']);
                        }
                    }
                    $basketclass->deleteByCriteria([
                        'plugin_metademands_metademands_id' => $metademands_id,
                        'users_id' => Session::getLoginUserID(),
                    ]);
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
                                'name' => "upload",
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

                    $basketclass->deleteByCriteria([
                        'plugin_metademands_metademands_id' => $metademands_id,
                        'users_id' => Session::getLoginUserID(),
                    ]);

                    $result = PluginMetademandsMetademand::addObjects($metademands_id, $values, $options);
                    if (is_array($result)) {
                        Session::addMessageAfterRedirect($result['message']);
                    }
                }
            } else {
                //not in basket
                $result = PluginMetademandsMetademand::addObjects($metademands_id, $values, $options);
                if (isset($values['plugin_metademands_stepforms_id'])) {
                    $step = new PluginMetademandsStepform();
                    $step->deleteAfterCreate($values['plugin_metademands_stepforms_id'], false);
                }

                if (is_array($result)) {
                    Session::addMessageAfterRedirect($result['message']);
                }
            }
        }
        unset($_SESSION['plugin_metademands']);

        if (!empty($options['resources_id'])) {
            Html::redirect(PLUGIN_RESOURCES_WEBDIR . "/front/wizard.form.php");
        } elseif (isset($options['collect_metademands']) && $options['collect_metademands'] == true) {
            return true;
        } else {
            if (Plugin::isPluginActive('servicecatalog')
                && Session::haveRight("plugin_servicecatalog", READ)) {
                if (PluginServicecatalogConfig::getConfig()->getMultiEntityRedirection()) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php?changeactiveentity");
                } else {
                    $type = $metademands->fields['type'];
                    if ($type > 0) {
                        Html::back();
                        //                        Html::redirect(
                        //                            PLUGIN_SERVICECATALOG_WEBDIR . "/front/choosecategory.form.php?type=$type&level=1"
                        //                        );
                    } else {
                        Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
                    }
                }
            } elseif (Session::haveRight("plugin_metademands", READ)) {
                Html::redirect($self->getFormURL() . "?step=" . PluginMetademandsMetademand::STEP_INIT);
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
     * @param false $on_order
     *
     * @return array
     */
    public static function checkvalues($value, $id, $post, $fieldname, $on_order = false)
    {
        $KO = false;
        $content = [];

        $field = new PluginMetademandsField();
        if ($field->getFromDB($value["id"])) {
            $value = PluginMetademandsField::getAllParamsFromField($field);
        }

        if (($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval') && !isset($value['second_date_ok'])) {
            $value['second_date_ok'] = true;
            $value['id'] = $id . '-2';
            $value['name'] = $value['label2'];
            $data[$id . '-2'] = $value;
        }

        if (!isset($post[$fieldname][$id])) {
            $post[$fieldname][$id] = "";
        }
        if ($value['is_mandatory'] == 1
            && $value['type'] != 'title'
            && $value['type'] != 'title-block'
            && $value['type'] != 'informations'
            && $value['type'] != 'checkbox'
            && $value['type'] != 'radio'
            && $value['item'] != 'ITILCategory_Metademands'
            && $value['type'] != 'upload'
            && $value['type'] != 'freetable'
            && $value['type'] != 'dropdown_multiple') {
            if (!self::checkMandatoryFields(
                $fieldname,
                $value,
                [
                    'id' => $id,
                    'value' => $post[$fieldname][$id],
                ],
                $post
            )) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = $post[$fieldname][$id];
                if (isset($post[$fieldname][$id . "-2"]) &&
                    ($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval')
                    && $value['second_date_ok']) {
                    $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id . "-2"] = $post[$fieldname][$id . "-2"];
                }
            }
        } elseif ($value['is_mandatory'] == 1
            && $value['item'] == 'ITILCategory_Metademands') {
            if (!self::checkMandatoryFields(
                $fieldname,
                $value,
                [
                    'id' => $id,
                    'value' => $post[$fieldname][$id],
                ],
                $post
            )) {
                $KO = true;
            } else {
                $content[$id]['plugin_metademands_fields_id'] = $id;
                if ($on_order == false) {
                    $content[$id]['value'] = $post['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                } else {
                    $content[$id]['value'] = $post['basket_plugin_servicecatalog_itilcategories_id'] ?? 0;
                }

                $content[$id]['value2'] = "";
                $content[$id]['item'] = $value['item'];
                $content[$id]['type'] = $value['type'];
                $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = $post[$fieldname][$id];
            }
        } elseif ($value['is_mandatory'] == 1
            && $value['type'] == 'checkbox') {
            if (!self::checkMandatoryFields(
                $fieldname,
                $value,
                ['id' => $id, 'value' => $post[$fieldname][$id]],
                $post
            )) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = $post[$fieldname][$id];
            }
        } elseif ($value['is_mandatory'] == 1
            && $value['type'] == 'radio') {
            if (!self::checkMandatoryFields(
                $fieldname,
                $value,
                ['id' => $id, 'value' => $post[$fieldname][$id]],
                $post
            )) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = $post[$fieldname][$id];
            }
        } elseif ($value['is_mandatory'] == 1
            && $value['type'] == 'upload') {
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
                //                if (isset($post['_filename'])) {
                //                    foreach ($post['_filename'] as $key => $filename) {
                //                        $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields']['files']['_prefix_filename'][] = $post['_prefix_filename'][$key];
                //                        $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields']['files']['_tag_filename'][] = $post['_tag_filename'][$key];
                //                        $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields']['files']['_filename'][] = $post['_filename'][$key];
                //                    }
                //                }
            }
        } elseif ($value['is_mandatory'] == 1
            && $value['type'] == 'dropdown_multiple') {
            if (!isset($post[$fieldname][$id])) {
                if (!self::checkMandatoryFields(
                    $fieldname,
                    $value,
                    [
                        'id' => $id,
                        'value' => [],
                    ],
                    $post
                )) {
                    $KO = true;
                    $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = [];
                } else {
                    $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = [];
                }
            } else {
                $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = $post[$fieldname][$id];
            }
        }
        //INFO : not used for update basket
        if ($value['item'] != 'ITILCategory_Metademands'
            && $KO === false
            && isset($post[$fieldname][$id])) {
            $content[$id]['plugin_metademands_fields_id'] = $id;
            if ($value['type'] != "upload") {
                if ($value['type'] == "freetable") {
                    $content[$id]['value'] = (is_array(
                        $post[$fieldname][$id]
                    )) ? PluginMetademandsFieldParameter::_serializeArray(
                        $post[$fieldname][$id]
                    ) : $post[$fieldname][$id];
                } else {
                    if (is_array($post[$fieldname][$id])) {
                        $content[$id]['value'] = PluginMetademandsFieldParameter::_serializeArray($post[$fieldname][$id]);
                    } else {
                        $content[$id]['value'] = PluginMetademandsFieldParameter::_serialize($post[$fieldname][$id]);
                    }
                }
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
        $hidden_blocks = $_SESSION['plugin_metademands'][$post["metademands_id"]]['hidden_blocks'] ?? [];
        $dbu = new DbUtils();

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

            $class = PluginMetademandsField::getClassFromType($value['type']);

            switch ($value['type']) {
                case 'title':
                case 'title-block':
                case 'informations':
                case 'link':
                case 'basket':
                    break;
                case 'datetime_interval':
                case 'date_interval':
                case 'datetime':
                case 'time':
                case 'date':
                case 'freetable':
                case 'range':
                case 'number':
                case 'radio':
                case 'dropdown_multiple':
                case 'dropdown':
                case 'dropdown_meta':
                case 'textarea':
                case 'url':
                case 'email':
                case 'tel':
                case 'text':
                    $result = $class::checkMandatoryFields($value, $fields);
                    if ($result['checkKo'] == 1) {
                        $checkKo[] = $result['checkKo'];
                        $msg[] = $result['msg'];
                    }
                    break;
                case 'yesno':
                case 'checkbox':
                case 'dropdown_object':
                case 'dropdown_ldap':
                    $result = $class::checkMandatoryFields($value, $fields);
                    $checkKo[] = $result['checkKo'];
                    $msg[] = $result['msg'];
                    break;
                case 'upload':
                    $result = $class::checkMandatoryFields($value, $post);
                    if ($result['checkKo'] == 1) {
                        $checkKo[] = $result['checkKo'];
                        $msg[] = $result['msg'];
                    }
                    break;
                default:
                    break;
            }

            // Check linked field mandatory
            if (!empty($value['fields_link'])
                && !empty($value['check_value'])
                && PluginMetademandsTicket_Field::isCheckValueOK(
                    $fields['value'],
                    $value['check_value'],
                    $value['type']
                )
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

            // Check date
            if ($value['type'] == "date"
                || $value['type'] == "datetime"
                || $value['type'] == "date_interval"
                || $value['type'] == "datetime_interval") {
                // date not < today
                if ($fields['value'] != 'NULL'
                    && !empty($fields['value'])
                    && $value['use_future_date'] == 1
                    && !(strtotime($fields['value']) >= strtotime(date('Y-m-d')))) {
                    $msg[] = sprintf(__("Date %s cannot be less than today's date", 'metademands'), $value['name']);
                    $checkKo[] = 1;
                }
            }

            // Check date interval is right
            if (($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval')
                && isset($all_fields[$fields['id'] . '-2'])) {
                $value2 = $all_fields[$fields['id'] . '-2'];
                if (strtotime($fields['value']) > strtotime($value2)) {
                    $msg[] = sprintf(
                        __('Date %1$s cannot be greater than date %2$s', 'metademands'),
                        $value['name'],
                        $value['label2']
                    );
                    $checkKoDateInterval[] = 1;
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
                    if (!preg_match('/' . $value['regex'] . '/', $fields['value'])) {
                        $msg3[] = Toolbox::stripslashes_deep($value['name']);
                        $checkRegex[] = 1;
                    }
                }
            }
        }

        if (in_array(1, $checkKo)
            || in_array(1, $checkKoDateInterval)) {
            Session::addMessageAfterRedirect(
                sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)),
                false,
                ERROR
            );
            return false;
        }
        if (in_array(1, $checkNbDoc)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __("Too much documents are upload, max %s. Please correct: %s", "metademands"),
                    $value["max_upload"],
                    implode(', ', $msg2)
                ),
                false,
                ERROR
            );
            return false;
        }
        if (in_array(1, $checkRegex)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __("Field do not correspond to the expected format. Please correct: %s", "metademands"),
                    implode(', ', $msg3)
                ),
                false,
                ERROR
            );
            return false;
        }

        return true;
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
    //    public function checkValueOk($check_value, $plugin_metademands_tasks_id, $metademandtasks_tasks_id, $id, $value, $post)
    //    {
    //        if (isset($post[$id])
    //            && $check_value != null
    //            && in_array($plugin_metademands_tasks_id, $metademandtasks_tasks_id)) {
    //
    //            if (!PluginMetademandsTicket_Field::isCheckValueOK($post[$id], $check_value, $value['type'])) {
    //
    //                $metademandToHide = array_keys($metademandtasks_tasks_id, $plugin_metademands_tasks_id);
    //                $_SESSION['metademands_hide'][$metademandToHide[0]] = $metademandToHide[0];
    //                unset($_SESSION['son_meta'][$metademandToHide[0]]);
    //            }
    //        }
    //    }


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
            $hex = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
        } elseif (strlen($color) == 3) {
            $hex = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
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

    public static function createPluginNewKindOfCategory($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getNewKindOfCategory'])) {
                    return $item->getNewKindOfCategory();
                }
            }
        }
    }
}
