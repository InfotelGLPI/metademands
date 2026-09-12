<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use Ajax;
use CommonDBTM;
use CommonGLPI;
use CommonITILActor;
use DbUtils;
use Glpi\Application\View\Extension\IllustrationExtension;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
use Glpi\RichText\RichText;
use GlpiPlugin\Servicecatalog\Category;
use GlpiPlugin\Servicecatalog\Config as ServiceCatalogConfig;
use Html;
use ITILCategory;
use KnowbaseItem;
use Plugin;
use Session;
use Toolbox;
use User;

/**
 * Class Wizard
 */
class Wizard extends CommonDBTM
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
        return CommonDBTM::getTable(Metademand::class);
    }

    /**
     * Normalize richtext textarea upload POST data into standard upload arrays.
     *
     * When Html::file() is called with name="field[N]" (from Textarea::textarea()),
     * jQuery File Upload creates inputs named _field[N][idx], _prefix_field[N][idx],
     * _tag_field[N][idx] instead of the standard _filename[idx] etc.
     * This method converts those field-specific names to the standard format so
     * adddraft.php and addform.php can store them uniformly in the session.
     *
     * @param array $post $_POST data
     * @return array{_filename: list<string>, _prefix_filename: list<string>, _tag_filename: list<string>}
     */
    public static function extractRichtextFieldUploads(array $post): array
    {
        $result = [
            '_filename'        => [],
            '_prefix_filename' => [],
            '_tag_filename'    => [],
        ];

        if (!isset($post['_field']) || !is_array($post['_field'])) {
            return $result;
        }

        foreach ($post['_field'] as $field_id => $files) {
            if (!is_array($files)) {
                continue;
            }
            foreach ($files as $idx => $filename) {
                if (empty($filename)) {
                    continue;
                }
                $result['_filename'][]        = $filename;
                $result['_prefix_filename'][] = $post['_prefix_field'][$field_id][$idx] ?? '';
                $result['_tag_filename'][]    = $post['_tag_field'][$field_id][$idx] ?? '';
            }
        }

        return $result;
    }

    public static function getIcon()
    {
        return "ti ti-device-imac-search";
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
    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        if (Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE])
            || Session::haveRight('plugin_metademands_createmeta', READ)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == Metademand::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["id" => $item->getID()],
                    ),
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
     * @throws \GlpitestSQLError
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == Metademand::class) {
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

        $metademand = new Metademand();

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

        $wizard = new Wizard();
        $options = [
            'step' => Metademand::STEP_SHOW,
            'metademands_id' => $item->getID(),
            'preview' => true,
        ];

        // showWizard() still prints its markup; the legacy $background_color read here was
        // dead code, nothing downstream ever used it.
        ob_start();
        $wizard->showWizard($options);
        $wizard_html = (string) ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/wizard/preview.html.twig', [
            'title'       => Wizard::getTypeName(),
            'wizard_html' => $wizard_html,
        ]);

        return true;
    }

    /**
     * @param User $user
     */
    public static function showUserInformations(User $user)
    {
        echo $user->getInfoCard();

        $cond['is_requester'] = 1;
        $groups = Field::getUserGroup(
            $_SESSION['glpiactiveentities'],
            $user->getID(),
            $cond,
            false,
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
    public static function showMetademandTitle($meta, $parameters)
    {
        $config = Config::getInstance();

        $title_color       = "#000";
        $background_color  = "#000";
        $style_title_color = "";
        if (!empty($meta->fields['title_color'])) {
            $title_color       = $meta->fields['title_color'];
            $style_title_color = $title_color;
        }
        if (!empty($meta->fields['background_color'])) {
            $background_color = $meta->fields['background_color'];
        }
        $icon_color = "color:color-mix(in srgb, transparent, $title_color var(--tblr-link-opacity, 100%))";

        $icon = "";
        if (!empty($config['icon_incident']) && $meta->fields['type'] == \Ticket::INCIDENT_TYPE) {
            $icon = $config['icon_incident'];
        }
        if (!empty($config['icon_request']) && $meta->fields['type'] == \Ticket::DEMAND_TYPE) {
            $icon = $config['icon_request'];
        }
        if (!empty($config['icon_problem']) && $meta->fields['type'] == "Problem") {
            $icon = $config['icon_problem'];
        }
        if (!empty($config['icon_change']) && $meta->fields['type'] == "Change") {
            $icon = $config['icon_change'];
        }
        if (!empty($meta->fields['icon'])) {
            $icon = $meta->fields['icon'];
        }

        $illustration = "";
        if (!empty($meta->fields['illustration'])) {
            $illustration = (new IllustrationExtension())->renderIllustration($meta->fields['illustration']);
        }

        if (empty($title = Metademand::displayField($meta->getID(), 'name'))) {
            $title = $meta->getName();
        }

        $category_completename = "";
        if (isset($parameters['itilcategories_id'])
            && isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) > 1) {
                $itil_category = new ITILCategory();
                if ($itil_category->getFromDB($parameters['itilcategories_id'])) {
                    $category_completename = $itil_category->fields['completename'];
                }
            }
        }

        $category_details_id    = 0;
        $category_details_modal = "";
        if (Plugin::isPluginActive('servicecatalog')) {
            $configsc = new ServiceCatalogConfig();
            $seedetail = 1;
            // Kept as a string literal on purpose: servicecatalog is an optional plugin and the
            // class may simply not exist here.
            if (method_exists("GlpiPlugin\\Servicecatalog\\Config", "getDetailBeforeFormRedirect")) {
                $seedetail = $configsc->getDetailBeforeFormRedirect();
            }
            if ($configsc->seeCategoryDetails() && $seedetail == 0) {
                $itilcategories_id = 0;
                $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'] ?? '', true);
                if (is_array($cats) && count($cats) == 1) {
                    foreach ($cats as $cat) {
                        $itilcategories_id = $cat;
                    }
                }
                $helpdesk_category = new Category();
                if ($itilcategories_id > 0 && $helpdesk_category->getFromDBByCategory($itilcategories_id)
                    && ($helpdesk_category->fields['comment_incident'] != null
                        || $helpdesk_category->fields['comment_request'] != null
                        || $helpdesk_category->fields['service_detail'] != null
                        || $helpdesk_category->fields['service_users'] != null
                        || $helpdesk_category->fields['service_ttr'] != null
                        || $helpdesk_category->fields['service_use'] != null
                        || $helpdesk_category->fields['service_supervision'] != null
                        || $helpdesk_category->fields['service_rules'] != null)) {
                    $category_details_id    = $itilcategories_id;
                    $category_details_modal = Ajax::createIframeModalWindow(
                        'categorydetails' . $itilcategories_id,
                        PLUGIN_SERVICECATALOG_WEBDIR . "/front/categorydetail.form.php?type=" . $meta->fields['type'] . "&category_id=" . $itilcategories_id,
                        [
                            'title' => __('More informations', 'servicecatalog'),
                            'display' => false,
                            'width' => 1050,
                            'height' => 500,
                        ],
                    );
                }
            }
        }

        $settings_url = "";
        if (Session::getCurrentInterface() == 'central'
            && Session::haveRight('plugin_metademands', UPDATE)
            && !$parameters['seeform']) {
            $settings_url = Toolbox::getItemTypeFormURL(Metademand::class) . "?id=" . $meta->getID();
        }

        // 'comment' is rich HTML authored in TinyMCE by the metademand designer: it is the only
        // value handed to the template unescaped, so it must be sanitized here.
        $comment = "";
        if (!empty($meta->fields['comment'])) {
            if (empty($comment = Metademand::displayField($meta->getID(), 'comment'))) {
                $comment = $meta->fields['comment'];
            }
            $comment = RichText::getSafeHtml($comment);
        }

        if (!isset($parameters['from_draft'])) {
            $parameters['from_draft'] = 0;
        }
        $models_and_drafts = "";
        if ($parameters['from_draft'] == 0) {
            $models_and_drafts = self::showmodelsAndDrafts($parameters, true);
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/metademand_title.html.twig', [
            'background_color'       => $background_color,
            'style_title_color'      => $style_title_color,
            'icon_color'             => $icon_color,
            'margin_top'             => empty($illustration) ? "margin-top: 5px" : "margin-top: -45px",
            'illustration'           => $illustration,
            'icon'                   => $icon,
            'is_fa_icon'             => str_contains($icon, 'fa-'),
            'title'                  => $title,
            'cat_name'               => $parameters['cat_name'] ?? "",
            'category_completename'  => $category_completename,
            'category_details_id'    => $category_details_id,
            'category_details_modal' => $category_details_modal,
            'settings_url'           => $settings_url,
            'comment'                => $comment,
            'models_and_drafts'      => $models_and_drafts,
        ]);
    }

    /**
     * Render the drop-down holding the models, the created forms and the drafts of the
     * current user for a metademand.
     *
     * @param array    $parameters
     * @param bool|int $with_title whether the metademand title is shown above the toggle
     *
     * @return string
     */
    public static function showmodelsAndDrafts($parameters, $with_title = 1)
    {
        if ($parameters['preview'] || $parameters['seeform']) {
            return '';
        }

        $config = Config::getInstance();
        $user_id = Session::getLoginUserID();
        $meta_id = $parameters['metademands_id'];

        $tabs = [
            [
                'id' => 'divformmodels',
                'label' => __('Your models', 'metademands'),
                'content' => Form::showPrivateFormsForUserMetademand($user_id, $meta_id)
                    . Form::showPublicFormsForUserMetademand($meta_id),
            ],
            [
                'id' => 'divforms',
                'label' => __('Your created forms', 'metademands'),
                'content' => Form::showFormsForUserMetademand($user_id, $meta_id),
            ],
        ];

        if ($config['use_draft']) {
            $tabs[] = [
                'id' => 'divdrafts',
                'label' => __('Your drafts', 'metademands'),
                'content' => Draft::showDraftsForUserMetademand($user_id, $meta_id),
            ];
        }

        return TemplateRenderer::getInstance()->render('@metademands/wizard/models_and_drafts.html.twig', [
            'toggle_class' => $with_title ? 'mydraft-withtitle' : 'mydraft-withouttitle',
            'toggle_title' => _x('button', 'Your forms', 'metademands'),
            'tabs' => $tabs,
        ]);
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
            'step' => Metademand::STEP_INIT,
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
            'defaultvalues' => [],
        ];

        // if given parameters, override defaults
        foreach ($parameters as $key => $value) {
            if (isset($options[$key])) {
                $parameters[$key] = $options[$key];
            }
        }
        $_SESSION['servicecatalog']['sc_itilcategories_id'] = $parameters['itilcategories_id'];
        // Retrieve session values
        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_id'])) {
            $parameters['resources_id'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_id'];
        }
        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_step'])) {
            $parameters['resources_step'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['resources_step'];
        }
        if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['ancestor_tickets_id'])) {
            $parameters['ancestor_tickets_id'] = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['ancestor_tickets_id'];
        }
        $meta = new Metademand();
        $maintenance_mode = 0;
        $title = 1;

        if ($meta->getFromDB($parameters['metademands_id'])) {
            $maintenance_mode = $meta->fields['maintenance_mode'];
            $_SESSION['servicecatalog']['sc_itilcategories_id'] = $meta->fields['itilcategories_id'];
            $title = $meta->fields['hide_title'] ? 0 : 1;
        }

        $models_and_drafts = "";
        if ($parameters['step'] > Metademand::STEP_LIST && $title == 0) {
            $models_and_drafts = self::showmodelsAndDrafts($parameters, false);
        }

        $template_vars = [
            'form_action'       => Toolbox::getItemTypeFormURL(__CLASS__),
            'models_and_drafts' => $models_and_drafts,
            'maintenance'       => ($maintenance_mode == 1 && !$parameters['preview']),
            'preview'           => (bool) $parameters['preview'],
            'breadcrumb'        => "",
            'hidden_fields'     => [],
            'header'            => "",
            'icon'              => "",
            'is_fa_icon'        => false,
            'metademand_title'  => "",
            'requester_id'      => null,
            'abort'             => "",
            'abort_message'     => "",
            'steps'             => "",
            'close_form'        => "",
        ];

        if ($template_vars['maintenance']) {
            TemplateRenderer::getInstance()->display('@metademands/wizard/wizard.html.twig', $template_vars);
            return true;
        }

        // Breadcrumb: only the service catalog interface exposes a category path to walk back up.
        if ($parameters['step'] > Metademand::STEP_LIST
            && $meta->getFromDB($parameters['metademands_id'])
            && Plugin::isPluginActive('servicecatalog')
            && Session::getCurrentInterface() != 'central'
            && $parameters['itilcategories_id'] > 0) {
            $template_vars['breadcrumb'] = self::getWizardBreadcrumb($meta, (int) $parameters['itilcategories_id']);
        }

        // Case of simple ticket convertion
        $template_vars['hidden_fields'] = [
            'tickets_id'          => $parameters['tickets_id'],
            'resources_id'        => $parameters['resources_id'],
            'resources_step'      => $parameters['resources_step'],
            'block_id'            => $parameters['block_id'],
            'ancestor_tickets_id' => $parameters['ancestor_tickets_id'],
        ];

        if ($parameters['step'] == Metademand::STEP_INIT) {
            // Wizard title
            $template_vars['header'] = 'init';
            $icon = "ti-share";
            if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                $icon = $meta->fields['icon'];
            }
            $template_vars['icon'] = $icon;
            $template_vars['is_fa_icon'] = str_contains($icon, 'fa-');
        } elseif ($parameters['step'] == Metademand::STEP_LIST) {
            // Wizard title
            $template_vars['header'] = 'list';
            $icon = "ti-share";

            $config = Config::getInstance();
            if (!empty($config['icon_incident']) && $parameters['meta_type'] == \Ticket::INCIDENT_TYPE) {
                $icon = $config['icon_incident'];
            }
            if (!empty($config['icon_request']) && $parameters['meta_type'] == \Ticket::DEMAND_TYPE) {
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

            $template_vars['icon'] = $icon;
            $template_vars['is_fa_icon'] = str_contains($icon, 'fa-');
        } elseif ($parameters['step'] > Metademand::STEP_LIST) {
            $template_vars['header'] = 'form';
            if ($title == 1) {
                ob_start();
                self::showMetademandTitle($meta, $parameters);
                $template_vars['metademand_title'] = (string) ob_get_clean();
            }

            if ($parameters['preview'] == 0) {
                if (Step::checkSupervisorForUser($meta->getID()) == false) {
                    $template_vars['abort'] = 'silent';
                    TemplateRenderer::getInstance()->display('@metademands/wizard/wizard.html.twig', $template_vars);
                    return false;
                }
            }

            // Display user informations
            $userid = Session::getLoginUserID();
            // If ticket exists we get its first requester
            if ($parameters['tickets_id']) {
                $users_id_requester = Ticket::getUsedActors(
                    $parameters['tickets_id'],
                    CommonITILActor::REQUESTER,
                    'users_id',
                );
                if (count($users_id_requester)) {
                    $userid = $users_id_requester[0];
                }
            }

            // Retrieve session values
            if (isset($_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['_users_id_requester'])) {
                $userid = $_SESSION['plugin_metademands'][$parameters['metademands_id']]['fields']['_users_id_requester'];
            }

            $canuse = Group::isUserHaveRight($parameters['metademands_id']);
            if ($parameters['preview'] == 1) {
                $canuse = 1;
            }
            // Rights management
            $denied_message = "";
            if (Session::getCurrentInterface() == 'central'
                && !empty($parameters['tickets_id'])
                && !Session::haveRight('ticket', UPDATE)) {
                $denied_message = __("You don't have the right to update tickets", 'metademands');
            } elseif (!$canuse) {
                $denied_message = __("You don't have the right to create meta-demand", 'metademands');
            }
            if ($denied_message !== "") {
                $template_vars['abort'] = 'message';
                $template_vars['abort_message'] = self::showMessage($denied_message, true);
                TemplateRenderer::getInstance()->display('@metademands/wizard/wizard.html.twig', $template_vars);
                return false;
            }
            $template_vars['requester_id'] = $userid;
        }

        $options['resources_id'] = $parameters['resources_id'];
        $options['itilcategories_id'] = $parameters['itilcategories_id'];

        ob_start();
        self::showWizardSteps(
            $parameters['step'],
            $parameters['metademands_id'],
            $parameters['preview'],
            $options,
            $parameters['seeform'],
            $parameters['current_ticket_id'],
            $parameters['meta_validated'],
        );
        $template_vars['steps'] = (string) ob_get_clean();
        $template_vars['close_form'] = (string) Html::closeForm(false);

        TemplateRenderer::getInstance()->display('@metademands/wizard/wizard.html.twig', $template_vars);
    }

    /**
     * Build the service catalog breadcrumb displayed above the wizard.
     *
     * @param Metademand $meta
     * @param int        $itilcategories_id
     *
     * @return string
     */
    private static function getWizardBreadcrumb(Metademand $meta, int $itilcategories_id): string
    {
        $treename = Category::getTreeCategoryFriendlyName(
            $meta->fields['type'],
            $itilcategories_id,
            6,
        );

        $alert_style = "";
        $alert_class = "";
        $plugin = new Plugin();
        if ($plugin->getInfo('servicecatalog')["version"] > "2.0.8") {
            $config = new ServiceCatalogConfig();
            if ($config->getLayout() == ServiceCatalogConfig::BOOTSTRAPPED
                || $config->getLayout() == ServiceCatalogConfig::BOOTSTRAPPED_COLOR) {
                $alert_style = "border: 1px solid transparent;border-radius: 1px;margin: 0px;";
            }
            if ($config->getforceBackgroundColor() == 1) {
                $alert_class = "alert-important";
            }
        }

        $display_warning  = "";
        $faq_url          = "";
        $helpdesk_category = new Category();
        if ($helpdesk_category->getFromDBByCategory($itilcategories_id)) {
            if (!empty($helpdesk_category->fields['display_warning'])) {
                $display_warning = Category::displayField($helpdesk_category, 'display_warning');
            }
            if (!empty($helpdesk_category->fields['knowbaseitems_id'])
                && Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
                $faq_url = PLUGIN_SERVICECATALOG_WEBDIR . "/front/faq.php?from_ticket=1"
                    . "&itilcategories_id=" . $itilcategories_id
                    . "&type=" . $meta->fields['type']
                    . "&id=" . (int) $helpdesk_category->fields['knowbaseitems_id'];
            }
        }

        return TemplateRenderer::getInstance()->render('@metademands/wizard/wizard_breadcrumb.html.twig', [
            'tree_name'       => $treename['name'],
            // getTreeCategoryFriendlyName() json_encode()s its script: decoding it gives back the
            // JavaScript string literal (quotes included) the inline script turns into a text node.
            'tree_script'     => json_decode($treename['script']),
            'alert_class'     => $alert_class,
            'alert_style'     => $alert_style,
            'display_warning' => $display_warning,
            'faq_url'         => $faq_url,
        ]);
    }

    /**
     * @param       $step
     * @param int $metademands_id
     * @param bool $preview
     * @param array $options
     * @param bool $seeform
     * @param int $current_ticket
     * @param int $meta_validated
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
            // The spinner is hidden again by public/scripts/wizard_form.js: the inline
            // $(window).load() that used to do it here relied on an alias jQuery dropped in 3.0.
            TemplateRenderer::getInstance()->display('@metademands/wizard/ajax_loader.html.twig');
        }
        if ($step === Metademand::STEP_CREATE) {
            $values = $_SESSION['plugin_metademands'][$metademands_id] ?? [];
            if (count($values) > 0) {
                self::createMetademands($metademands_id, $values, $options);
            }
        } elseif ($step == 0) {
            self::listMetademandTypes();
        } else {
            switch ($step) {
                case Metademand::STEP_LIST:
                    if (isset($options['meta_type'])) {
                        $_SESSION['plugin_metademands']['type'] = $options['meta_type'];
                        self::listMetademands($options['meta_type']);
                    } else {
                        TemplateRenderer::getInstance()->display('@metademands/wizard/alert.html.twig', [
                            'message' => __('No existing forms founded', 'metademands'),
                            'level'   => 'info',
                        ]);
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
                        $seeform,
                    );
                    break;
            }
            //            Comment Fix for parameter display_type 0
            //            echo Html::hidden('step', ['value' => $step]);
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
    public static function selectMetademands($all = false, $limit = "", $type = \Ticket::DEMAND_TYPE)
    {
        global $DB;

        $criteria = [
            'SELECT' => ['id','name','comment'],
            'FROM' => 'glpi_plugin_metademands_metademands',
            'WHERE' => [
                'is_template' => 0,
                'is_deleted' => 0,
                'is_active' => 1,
                ['OR'          => [
                    'is_order'  => 1,
                    'NOT'       => ['itilcategories_id' => null],
                ],
                ],
            ],
            'ORDERBY' => 'name',
        ];
        if (!empty($limit)) {
            $criteria['LIMIT'] = $limit;
        }

        if ($type == \Ticket::INCIDENT_TYPE || $type == \Ticket::DEMAND_TYPE) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['type' => $type];
            if ($all == true) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['NOT'       => ['type' => null]];
            }
        } else {
            $criteria['WHERE'] = $criteria['WHERE'] + ['object_to_create' => $type];
            if ($all == true) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['NOT'       => ['object_to_create' => null]];
            }
        }

        $criteria['WHERE'] = $criteria['WHERE'] + ['NOT'       => ['id' => new QuerySubQuery(
            [
                'SELECT' => 'plugin_metademands_metademands_id',
                'FROM'   => 'glpi_plugin_metademands_metademands_resources',
            ],
        )]];

        // Honor recursivity: a metademand shared from an ancestor entity (is_recursive = 1)
        // must be listed in child entities, so enable the recursive restriction (4th arg).
        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_plugin_metademands_metademands',
            '',
            '',
            true,
        );

        $metademands = [];
        $iterator = $DB->request($criteria);

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $canuse = Group::isUserHaveRight($data['id']);
                $canuse_step = Step::isUserHaveRight($data['id']);
                if ($canuse && $canuse_step) {
                    if (empty($name = Metademand::displayField($data['id'], 'name'))) {
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
                    case \Ticket::INCIDENT_TYPE:
                        return __('Report an incident', 'metademands');
                    case \Ticket::DEMAND_TYPE:
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

        $metademands_incidents = self::selectMetademands(false, "", \Ticket::INCIDENT_TYPE);
        if (count($metademands_incidents) > 0) {
            $data[\Ticket::INCIDENT_TYPE] = self::getMetademandTypeName('Ticket', \Ticket::INCIDENT_TYPE);
        }

        $metademands_requests = self::selectMetademands(false, "", \Ticket::DEMAND_TYPE);
        if (count($metademands_requests) > 0) {
            $data[\Ticket::DEMAND_TYPE] = self::getMetademandTypeName('Ticket', \Ticket::DEMAND_TYPE);
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
        echo Html::css(PLUGIN_METADEMANDS_WEBDIR . "/css/wizard.css.php");

        $data   = self::countMetademandTypes();
        $config = Config::getInstance();

        if (count($data) == 1) {
            foreach ($data as $type => $typename) {
                Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=1&meta_type=$type");
            }
        }

        $entries = [];
        foreach ($data as $type => $typename) {
            $icon = "ti-share";
            if (!empty($config['icon_incident']) && $type == \Ticket::INCIDENT_TYPE) {
                $icon = $config['icon_incident'];
            }
            if (!empty($config['icon_request']) && $type == \Ticket::DEMAND_TYPE) {
                $icon = $config['icon_request'];
            }
            if (!empty($config['icon_problem']) && $type == "Problem") {
                $icon = $config['icon_problem'];
            }
            if (!empty($config['icon_change']) && $type == "Change") {
                $icon = $config['icon_change'];
            }

            $entries[] = [
                'url'        => PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?step=" . Metademand::STEP_LIST . "&meta_type=" . $type,
                'icon'       => $icon,
                'is_fa_icon' => str_contains($icon, 'fa-'),
                'name'       => $typename,
            ];
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/metademand_types.html.twig', [
            'entries' => $entries,
        ]);
    }


    public static function showMostUsedMetademands($type)
    {
        global $DB;

        switch ($type) {
            case \Ticket::INCIDENT_TYPE:
                $crit = [
                    'glpi_itilcategories.is_incident' => 1,
                    'glpi_itilcategories.entities_id' => $_SESSION["glpiactive_entity"],
                    'glpi_tickets.type' => $type,
                ];

                break;
            case \Ticket::DEMAND_TYPE:
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
            'glpi_tickets',
        );

        $iterator = $DB->request($criteria);

        $entries = [];
        foreach ($iterator as $row) {
            $meta = new Metademand();
            $meta->getFromDB($row['plugin_metademands_metademands_id']);
            $icon = "ti-share";
            if (!empty($meta->fields['icon'])) {
                $icon = $meta->fields['icon'];
            }

            $entries[] = [
                'url'        => PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $row['plugin_metademands_metademands_id'] . "&step=" . Metademand::STEP_SHOW,
                'icon'       => $icon,
                'is_fa_icon' => str_contains($icon, 'fa-'),
                'name'       => $row['name'],
            ];
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/most_used_metademands.html.twig', [
            'entries' => $entries,
        ]);
    }

    /**
     * @throws \GlpitestSQLError
     */
    public static function listMetademands($type)
    {
        echo Html::css(PLUGIN_METADEMANDS_WEBDIR . "/css/wizard.css.php");

        $config = Config::getInstance();
        $meta   = new Metademand();

        if ($config['display_type'] != 1) {
            // METADEMAND list
            $options['display_emptychoice'] = true;
            $options['type'] = $type;
            $data = $meta->listMetademands(false, $options);

            ob_start();
            \Dropdown::showFromArray('metademands_id', $data, ['width' => 250]);
            $dropdown = (string) ob_get_clean();

            TemplateRenderer::getInstance()->display('@metademands/wizard/metademands_list.html.twig', [
                'display_cards' => false,
                'meta_type'     => $type,
                'step_show'     => Metademand::STEP_SHOW,
                'dropdown'      => $dropdown,
                'submit'        => Html::submit(
                    __('Next', 'metademands'),
                    ['name' => 'next', 'class' => 'btn btn-primary'],
                ),
            ]);
            return;
        }

        $metademands = self::selectMetademands(false, "", $type);

        if (count($metademands) == 1) {
            foreach ($metademands as $id => $name) {
                if ($meta->getFromDB($id)) {
                    Html::redirect(
                        PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=" . Metademand::STEP_SHOW,
                    );
                }
            }
            return;
        }
        if (count($metademands) < 1) {
            return;
        }

        $most_used = "";
        if ($config['see_top'] && ($type == \Ticket::INCIDENT_TYPE || $type == \Ticket::DEMAND_TYPE)) {
            ob_start();
            self::showMostUsedMetademands($type);
            $most_used = (string) ob_get_clean();
        }

        $entries = [];
        foreach ($metademands as $id => $name) {
            $current = new Metademand();
            if (!$current->getFromDB($id)) {
                continue;
            }

            if (empty($name_meta = Metademand::displayField($current->getID(), 'name'))) {
                $name_meta = $current->getName();
            }

            $comment_meta = Metademand::displayField($current->getID(), 'comment');
            if (empty($comment_meta) && !empty($current->fields['comment'])) {
                $comment_meta = $current->fields['comment'];
            }

            $icon = "ti-share";
            if (!empty($config['icon_incident']) && $type == \Ticket::INCIDENT_TYPE) {
                $icon = $config['icon_incident'];
            }
            if (!empty($config['icon_request']) && $type == \Ticket::DEMAND_TYPE) {
                $icon = $config['icon_request'];
            }
            if (!empty($config['icon_problem']) && $type == "Problem") {
                $icon = $config['icon_problem'];
            }
            if (!empty($config['icon_change']) && $type == "Change") {
                $icon = $config['icon_change'];
            }
            if (!empty($current->fields['icon'])) {
                $icon = $current->fields['icon'];
            }

            if (!empty($current->fields['title_color'])) {
                $icon_color = "color:color-mix(in srgb, transparent, " . $current->fields['title_color'] . " var(--tblr-link-opacity, 100%))";
            } else {
                $icon_color = "color:color-mix(in srgb, transparent, var(--tblr-navbar-color) var(--tblr-link-opacity, 100%))";
            }

            $drafts_label = "";
            if ($config['use_draft']) {
                $count_drafts = Draft::countDraftsForUserMetademand(
                    Session::getLoginUserID(),
                    $id,
                );
                if ($count_drafts > 0) {
                    $drafts_label = sprintf(
                        _n('You have %d draft', 'You have %d drafts', $count_drafts, 'metademands'),
                        $count_drafts,
                    );
                }
            }

            $entries[] = [
                'url'          => PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=" . Metademand::STEP_SHOW,
                'tooltip'      => RichText::getTextFromHtml($comment_meta),
                'icon'         => $icon,
                'is_fa_icon'   => str_contains($icon, 'fa-'),
                'icon_color'   => $icon_color,
                'name'         => $name_meta,
                // Rich HTML comment: the only value handed to the template unescaped, sanitize it here.
                'comment'      => !empty($comment_meta) ? RichText::getSafeHtml($comment_meta) : "",
                'drafts_label' => $drafts_label,
            ];
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/metademands_list.html.twig', [
            'display_cards' => true,
            'meta_type'     => $type,
            'most_used'     => $most_used,
            'entries'       => $entries,
        ]);
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
        $parameters = ['itilcategories_id' => 0, 'defaultvalues' => []];

        // if given parameters, override defaults
        foreach ($options as $key => $value) {
            if (isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        $metademands = new Metademand();
        $metademands_data = Metademand::constructMetademands($metademands_id);
        $metademands->getFromDB($metademands_id);

        $_SESSION['metademands_hide'] = [];

        $has_data = count($metademands_data) > 0;
        $see_summary = 0;
        $forms = "";
        $show_actions = false;
        $actions_margin = false;
        $show_print = false;

        if ($has_data) {
            if (isset($metademands->fields['is_basket']) && $metademands->fields['is_basket'] == 1) {
                $see_summary = 1;
            }

            // The form itself is still assembled by the legacy constructForm() echo chain:
            // capture it so the template only has to place the resulting fragment.
            ob_start();
            foreach ($metademands_data as $form_step => $data) {
                if ($form_step == $step) {
                    foreach ($data as $form_metademands_id => $line) {
                        $fields = $line['form'];
                        foreach ($fields as $fid => $field) {
                            if (isset($parameters['defaultvalues'][$fid])) {
                                $fields[$fid]['value'] = $parameters['defaultvalues'][$fid];
                            }
                        }
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
                            $meta_validated,
                        );

                        if ($seeform == 0) {
                            unset($_SESSION['plugin_metademands'][$metademands_id]['fields']);
                        }
                        echo Html::hidden('form_metademands_id', ['value' => $form_metademands_id]);
                        echo Html::hidden('is_private', ['value' => 1]);
                    }
                }
            }
            $forms = ob_get_clean();

            $show_actions = !$preview && (!$seeform
                    || (isset($options['resources_id'])
                        && $options['resources_id'] > 0)
                    || ($current_ticket > 0
                        && ((!$meta_validated
                                && $metademands->fields['can_update'] == true)
                            || ($meta_validated
                                && $metademands->fields['can_clone'] == true))
                        && Session::haveRight('plugin_metademands_updatemeta', READ)));
            $actions_margin = ($see_summary == 0);

            $show_print = !$preview
                && isset($metademands->fields['step_by_step_mode'])
                && $metademands->fields['step_by_step_mode'] == 0
                && $see_summary == 0;
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard_metademands.html.twig', [
            'has_data'            => $has_data,
            'forms'               => $forms,
            'metademands_id'      => $metademands_id,
            'show_actions'        => $show_actions,
            'actions_margin'      => $actions_margin,
            'ancestor_tickets_id' => $options['ancestor_tickets_id'] ?? null,
            'show_print'          => $show_print,
        ]);
    }


    public static function getDefaultParams($metademands, $preview, $seeform, $current_ticket, $meta_validated)
    {

        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $token = Session::getNewCSRFToken();

        $title = _sx('button', 'Save & Post', 'metademands');
        $icon = "ti ti-device-floppy";

        $childs_meta = MetademandTask::getChildMetademandsToCreate($metademands->fields['id']);
        if (count($childs_meta) > 0) {
            $title = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
        }

        $see_summary = 0;
        if ($metademands->fields['is_order'] == 1) {
            $title = _sx('button', 'Add to basket', 'metademands');
            $icon = "ti ti-plus";
        }

        if ($metademands->fields['is_basket'] == 1) {
            $title = _sx('button', 'See basket summary & send it', 'metademands');
            $see_summary = 1;
        }
        $submittitle = "<i class=\"$icon\"></i>&nbsp;" . $title;

        $block_id = $_SESSION['plugin_metademands'][$metademands->fields['id']]['block_id'] ?? 0;

        $edit_model = $_SESSION['plugin_metademands'][$metademands->fields['id']]['edit_model'] ?? 0;

        $use_model = $_SESSION['plugin_metademands'][$metademands->fields['id']]['use_model'] ?? 0;

        $block_current_id_stepform = $_SESSION['plugin_metademands'][$metademands->fields['id']]['block_id'] ?? 99999999;

        $listStepBlocks = [];

        $use_as_step = 0;
        $stepConfig = new Configstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->fields['id']]);

        if ($metademands->fields['step_by_step_mode'] == 1) {
            if (isset($stepConfig->fields['step_by_step_interface'])) {
                switch ($stepConfig->fields['step_by_step_interface']) {
                    case Configstep::BOTH_INTERFACE:
                        $use_as_step = 1;
                        break;
                    case Configstep::ONLY_HELPDESK_INTERFACE:
                        if (Session::getCurrentInterface() == 'helpdesk') {
                            $use_as_step = 1;
                        }
                        break;
                    case Configstep::ONLY_CENTRAL_INTERFACE:
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
            $listStepBlocks = Step::defineStepblocks($metademands->fields['id']);
        }

        $updatestepform = 0;
        $havenextuser = true;

        if (!isset($stepConfig->fields['supervisor_validation'])
            && !isset($stepConfig->fields['link_user_block'])
            && !isset($stepConfig->fields['multiple_link_groups_blocks'])) {
            $havenextuser = false;
        } elseif ($block_current_id_stepform != 99999999) {
            $canSeeNextBlock = Step::canSeeBlock(
                $metademands->fields['id'],
                $block_current_id_stepform + 1,
            );
            if (!$canSeeNextBlock) {
                $havenextuser = true;
                $updatestepform = 1;
            }
        }
        $changestepbystepoption = false;

        if ($metademands->fields['step_by_step_mode'] == 1) {
            $metademandsconfigsteps = new Configstep();
            foreach ($metademandsconfigsteps->find(['plugin_metademands_metademands_id' => $metademands->fields['id']]) as $row) {
                if ($row['change_step_by_step_option'] == 1) {
                    $changestepbystepoption = true;
                }
            }
        }

        $fields = new Field();
        $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands->fields['id']]);

        // Batch-load related data for all fields of this metademand (3 queries total instead of 3N)
        $all_field_ids = array_column(is_array($fields_data) ? $fields_data : [], 'id');
        FieldParameter::preloadForFields($all_field_ids);
        FieldCustomvalue::preloadForFields($all_field_ids);
        FieldOption::preloadForFields($all_field_ids);

        $all_meta_fields = [];
        if (is_array($fields_data) && count($fields_data) > 0) {
            foreach ($fields_data as $data) {
                $translated = Field::displayField($data['id'], 'name');
                $label = $translated !== '' ? $translated : ($data['name'] ?? '');
                $fp_data = FieldParameter::getFromStaticCache((int) $data['id']);
                $all_meta_fields[$data['id']] = ($fp_data !== null && $fp_data !== false
                    && isset($fp_data['hide_title']) && $fp_data['hide_title'] == 1)
                    ? Field::getFieldTypesName($data['type'])
                    : $label;
            }
        }
        // Injected verbatim into an inline <script> object literal below: without
        // JSON_HEX_TAG|JSON_HEX_AMP a stored label containing </script> closes the
        // block before the JSON parser ever runs. Never HEX_QUOT/HEX_APOS here, they
        // would break the literal.
        $json_all_meta_fields = json_encode($all_meta_fields, JSON_HEX_TAG | JSON_HEX_AMP);

        // Defence in depth: both callers normalize their input now, but this method is the
        // sink that builds the query string finally embedded in JavaScript, so it must not
        // depend on them. The @param int above is declarative only, nothing enforced it.
        $current_ticket = (int) $current_ticket;
        $paramUrl = "";
        if ($current_ticket > 0 && !$meta_validated) {
            $paramUrl = "current_ticket_id=$current_ticket&meta_validated=" . (int) $meta_validated . "&";
        }

        $stepConfig = new Configstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->getID()]);
        if ($metademands->fields['step_by_step_mode'] == 1
            && $stepConfig->fields['see_blocks_as_tab'] == 1  && !$preview) {
            $block_id = 0;
            if (isset($_REQUEST['block_id'])) {
                // Reflected into the inline <script> below, once inside a string literal
                // and once as a bare call argument: normalise at the source.
                $block_id = (int) $_REQUEST['block_id'];
            }
        }

        $metaparams['root_doc'] = $root_doc;
        $metaparams['token'] = $token;
        $metaparams['ID'] = $metademands->fields['id'];
        $metaparams['edit_model'] = $edit_model;
        $metaparams['use_model'] = $use_model;
        $metaparams['useconfirm'] = $metademands->fields['use_confirm'];
        $metaparams['is_order'] = $metademands->fields['is_order'];
        // addslashes() escapes quotes but neither < nor >, so it cannot protect a JS
        // string literal: the HTML parser looks for </script> first. json_encode()
        // emits the surrounding quotes itself, hence the unquoted sinks.
        $metaparams['confirmmsg'] = json_encode(
            __("You have not entered any values. Is this normal?", 'metademands'),
            JSON_HEX_TAG | JSON_HEX_AMP,
        );
        $metaparams['nameform'] = json_encode(
            $metademands->fields['name']
            . "_" . $_SESSION['glpi_currenttime'] . "_" . $_SESSION['glpiID'],
            JSON_HEX_TAG | JSON_HEX_AMP,
        );
        // Emitted unquoted in validateScript(), like confirmmsg and nameform above:
        // json_encode() supplies its own delimiters. public/scripts/metademands.js
        // concatenates this value into a URL, so it must stay a plain string -- which a
        // JSON string literal is. Never HEX_QUOT/HEX_APOS, they would break the literal.
        $metaparams['paramUrl'] = json_encode($paramUrl, JSON_HEX_TAG | JSON_HEX_AMP);
        if ($metademands->fields['can_update'] == 1 && !$meta_validated) {
            $metaparams['seeform'] = 0;
        } else {
            $metaparams['seeform'] = $seeform;
        }

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
        $metaparams['submitsteptitle'] = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & send to another user / group', 'metademands');
        //For multi User forms
        $metaparams['havenextuser'] = $havenextuser;
        $metaparams['updatestepform'] = $updatestepform;
        $metaparams['changestepbystepoption'] = $changestepbystepoption;
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
        $icon = "ti ti-device-floppy";
        if ($metademands->fields['is_order'] == 1) {
            $title = _sx('button', 'Add to basket', 'metademands');
            $icon = "ti ti-plus";
        }

        $childs_meta = MetademandTask::getChildMetademandsToCreate($metademands->fields['id']);
        if (count($childs_meta) > 0) {
            $title = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
        }
        $submittitle = "<i class=\"$icon\"></i>&nbsp;" . $title;


        $metaparams['submittitle'] = $submittitle;
        $metaparams['nextsteptitle'] = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";

        $use_condition = false;

        $show_rule = $metademands->fields['show_rule'];

        if ($show_rule != Condition::SHOW_RULE_ALWAYS) {
            $condition = new Condition();
            $conditions = $condition->find(['plugin_metademands_metademands_id' => $metademands->fields['id']]);
            if (count($conditions) > 0) {
                $use_condition = true;
            }
        }

        //Condition params
        $metaparams['use_condition'] = $use_condition;
        $metaparams['show_rule'] = $show_rule;

        $metaparams['show_button'] = 1;
        if ($show_rule == Condition::SHOW_RULE_HIDDEN) {
            $metaparams['show_button'] = 0;
        }
        $metaparams['use_richtext'] = 0;
        $richtext_id = [];

        $richtext_fields = getAllDataFromTable(
            "glpi_plugin_metademands_fields",
            ['plugin_metademands_metademands_id' => $metademands->fields['id'], 'type' => 'textarea'],
        );
        foreach ($richtext_fields as $f) {
            $fieldparameter = new FieldParameter();
            if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $f['id']])) {
                if ($fieldparameter->fields['use_richtext'] == 1) {
                    $metaparams['use_richtext'] = 1;
                    $richtext_id[] = $f['id'];
                }
            }
        }
        $metaparams['richtext_id'] = json_encode($richtext_id, JSON_HEX_TAG | JSON_HEX_AMP);

        //End Condition params
        return $metaparams;
    }


    /**
     * Display a metademand's content
     * @param $metademands_id int Metademand id, metademand to display
     * @param array $lines array of Field which need to be displayed
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

        $metademands = new Metademand();
        $metademands->getFromDB($metademands_id);

        //Redirected after end user Step
        $user_id = Session::getLoginUserID();
        $url = $CFG_GLPI['root_doc'] . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php";
        if (isset($_SESSION['plugin_metademands'][$user_id]['redirect_wizard'])) {
            if (Plugin::isPluginActive('servicecatalog')
                && Session::haveRight("plugin_servicecatalog", READ)) {
                if (method_exists(ServiceCatalogConfig::class, 'getMultiEntityRedirection') && ServiceCatalogConfig::getConfig()->getMultiEntityRedirection()) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php?changeactiveentity");
                } elseif (Session::haveRight("plugin_servicecatalog_redirect_on_menu", READ)) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
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
            if (!Step::canSeeBlock($metademands_id, $block_current_id_stepform)
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
            if ($metademands->fields['step_by_step_mode'] == 1
                && $fields['rank'] < $block_current_id_stepform) {
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
                $cached_opts = FieldOption::getFromStaticCache((int) $value['id']);
                if ($cached_opts === false) {
                    // cache miss — fallback to DB
                    $fieldopt = new FieldOption();
                    $cached_opts = $fieldopt->find([
                        "plugin_metademands_fields_id" => $value['id'],
                        "hidden_block_same_block"      => 1,
                    ]);
                }
                foreach ($cached_opts as $opt) {
                    if (($opt['hidden_block_same_block'] ?? 0) == 1) {
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
        $stepConfig = new Configstep();
        if (countElementsInTable("glpi_plugin_metademands_configsteps", ['plugin_metademands_metademands_id' => $metademands_id]) > 1) {
            $stepConfig->deleteByCriteria(['plugin_metademands_metademands_id' => $metademands_id]);
            $stepConfig->add(['plugin_metademands_metademands_id' => $metademands_id]);
            TemplateRenderer::getInstance()->display('@metademands/wizard/alert.html.twig', [
                'message' => __('There was a problem. The step-by-step mode configuration was reset', 'metademands'),
                'level'   => 'warning',
            ]);
        }
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id]);

        if (isset($metademands->fields['step_by_step_mode'])
            && $metademands->fields['step_by_step_mode'] == 1) {
            if (isset($stepConfig->fields['step_by_step_interface'])) {
                switch ($stepConfig->fields['step_by_step_interface']) {
                    case Configstep::BOTH_INTERFACE:
                        $use_as_step = 1;
                        break;
                    case Configstep::ONLY_HELPDESK_INTERFACE:
                        if (Session::getCurrentInterface() == 'helpdesk') {
                            $use_as_step = 1;
                        }
                        break;
                    case Configstep::ONLY_CENTRAL_INTERFACE:
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
        if (isset($metademands->fields['is_basket'])
            && $metademands->fields['is_basket'] == 1) {
            $see_summary = 1;
        }

        $hidden_blocks = [];
        $all_hidden_blocks = [];


        $columns = 2;
        $cpt = 0;

        $basketline = new Basketline();
        $basket_lines = $basketline->find([
            'plugin_metademands_metademands_id' => $metademands_id,
            'users_id' => Session::getLoginUserID(),
        ]);
        if (count($basket_lines) > 0 && !$preview) {
            // The #submitjob handler moved to public/scripts/wizard_form.js; it reads the endpoint
            // from the data attribute carried by the alert.
            TemplateRenderer::getInstance()->display('@metademands/wizard/form_basket_alert.html.twig', [
                'basket_url'  => PLUGIN_METADEMANDS_WEBDIR . '/ajax/createmetademands.php?metademands_id='
                    . (int) $metademands->fields['id'] . '&step=2',
                'hidden_html' => Html::hidden('see_basket_summary', ['value' => 1]),
                'submit_html' => Html::submit(_sx('button', 'See your basket', 'metademands'), [
                    'name'  => 'next_button',
                    'form'  => '',
                    'icon'  => 'ti ti-shopping-bag',
                    'id'    => 'submitjob',
                    'class' => 'metademand_next_button btn btn-success',
                ]),
            ]);
        }

        if (count($lines)) {
            $tabs_html = '';
            if ($use_as_step == 0) {
                $cpt = 1;
            }
            // The Enter key handler moved to public/scripts/wizard_form.js, delegated on #meta-form
            // so that it survives the Ajax reinjection of the wizard.

            $metaparams = self::getDefaultParams($metademands, $preview, $seeform, $current_ticket, $meta_validated);

            $metaconditionsparams = self::getConditionsParams($metademands);

            $hidden_basket_html = '';
            if ($metademands->fields['is_basket'] == 1) {
                $hidden_basket_html = Html::hidden('see_basket_summary', ['value' => 1]);
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

                // The tab bootstrap (hash, sessionStorage and shown.bs.tab) moved to
                // public/scripts/wizard_form.js. The window.metademandparams block that used to be
                // emitted here was dead: validateScript() resets that object further down, so every
                // value assigned at this point was overwritten before any script could read it.

                $blocks = [];
                foreach ($allfields as $blockid => $blockfields) {
                    $i = 0;

                    foreach ($blockfields as $value) {
                        if ($value['type'] == 'title-block' && $value['rank'] == $blockid) {
                            $i++;
                            if ($i > 0) {
                                $name = empty(Field::displayField($value['id'], 'name')) ? $value['name'] : Field::displayField($value['id'], 'name');
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
                    // A tab is hidden as soon as one field option of this meta-demand hides its
                    // block. The legacy loop asked the very same question with two queries per
                    // block, so the answer is resolved once here.
                    $hidden_tabs = [];
                    $field = new Field();
                    $meta_fields_ids = array_column(
                        $field->find(['plugin_metademands_metademands_id' => $metademands->getID()]),
                        'id',
                    );
                    if (count($meta_fields_ids) > 0) {
                        $fieldopt = new FieldOption();
                        foreach ($fieldopt->find([
                            'plugin_metademands_fields_id' => $meta_fields_ids,
                            'hidden_block' => ['>', 0],
                        ]) as $opt) {
                            $hidden_tabs[] = (int) $opt['hidden_block'];
                        }
                    }

                    $tab_blocks = [];
                    foreach ($blocks as $idblock => $nameblock) {
                        $tab_blocks[(int) $idblock] = $nameblock;
                    }

                    // The block names come from the designer-defined title-block fields: Twig
                    // escapes them as element text (stored XSS). The scroll handlers live in
                    // public/scripts/wizard_form.js.
                    $tabs_html = TemplateRenderer::getInstance()->render('@metademands/wizard/form_tabs.html.twig', [
                        'blocks'        => $tab_blocks,
                        'hidden_blocks' => $hidden_tabs,
                        'block_id'      => (int) $block_id,
                    ]);
                }
            }
            $use_model = $_SESSION['plugin_metademands'][$metademands->fields['id']]['use_model'] ?? 0;

            $blocks_html = [];
            foreach ($allfields as $block => $line) {
                // A block hidden by a field option stays out of the step wrapper, the way the
                // legacy loop did: .tab-step hides its content until the step flow opens it.
                $in_step = ($use_as_step == 1 && !in_array($block, $all_hidden_blocks));
                if ($in_step) {
                    $cpt++;
                }

                ob_start();
                self::displayBlockContent($metademands, $metademands_data, $preview, $block, $line, $subblocks_data, $itilcategories_id, $use_model);
                $blocks_html[] = ['in_step' => $in_step, 'html' => trim((string) ob_get_clean())];
            }

            // The .tab-nostep wrapper, the basket flag, the Ajax loader and the block tab bar
            // are emitted by the same template as the blocks themselves: they all belong to the
            // same wrapper, which used to be opened and closed by two `echo` several hundred
            // lines apart.
            TemplateRenderer::getInstance()->display('@metademands/wizard/form_blocks.html.twig', [
                'blocks'              => $blocks_html,
                'wrap_nostep'         => $use_as_step == 0,
                'hidden_basket_html'  => $hidden_basket_html,
                'tabs_html'           => $tabs_html,
            ]);

            if (!$preview) {
                //$metademands->fields['is_order'] == 0
                //                &&
                //TO DROP ?
                //                (isset($options['resources_id'])
                //                    && $options['resources_id'] > 0)
                if ($seeform == 0 && $current_ticket > 0
                    && (((!$meta_validated
                            && !$metademands->fields['can_update'])
                        || ($meta_validated
                            && !$metademands->fields['can_clone']))
                    || !Session::haveRight('plugin_metademands_updatemeta', READ))) {
                    Session::addMessageAfterRedirect(__("You don't have the right to modify this form or the metademand don't accept form modifications", 'metademands'), false, ERROR);
                    Html::back();
                }
                $config = Config::getInstance();
                $draft_input_html = '';
                if ($config['use_draft']
                    && $draft_id == 0) {
                    //button create draft
                    $draft_input_html = Draft::createDraftInput(Draft::DEFAULT_MODE);
                }

                $cancel_form_html = '';
                if (Session::haveRight("plugin_metademands_cancelform", READ)
                    && isset(
                        $_SESSION['plugin_metademands'][$metademands->getID()]['plugin_metademands_stepforms_id'],
                    )) {
                    $cancel_form_html = Html::getSimpleForm(
                        PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.form.php",
                        'delete_form_from_list',
                        _sx('button', 'Cancel form', 'metademands'),
                        [
                            'plugin_metademands_stepforms_id' => $_SESSION['plugin_metademands'][$metademands->getID(
                            )]['plugin_metademands_stepforms_id'],
                        ],
                    );
                }

                TemplateRenderer::getInstance()->display('@metademands/wizard/form_nav_buttons.html.twig', [
                    'use_as_step'       => $use_as_step,
                    'draft_input_html'  => $draft_input_html,
                    'cancel_form_html'  => $cancel_form_html,
                    'show_step_circles' => $see_summary == 0 && $displayBlocksAsTab == 0,
                    'step_count'        => $cpt,
                ]);

                if (!empty($data_form)) {
                    $parent_fields = Metademand::formatFields(
                        $lineForStepByStep,
                        $metademands_id,
                        [$metademands_id => $data_form],
                        [],
                    );
                    $form = new Stepform();
                    if (isset($_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id'])
                        && $form->getFromDBByCrit(
                            ['id' => $_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id']],
                        )) {
                        // The dialog markup moved to a template: Twig escapes the requester
                        // supplied name and public/scripts/wizard_form.js opens the dialog, so
                        // nothing is concatenated into a JavaScript string literal any more.
                        $previousUser = new User();
                        $user_label = '';
                        $user_name = '';
                        if ($previousUser->getFromDBByCrit(['id' => $form->fields['users_id']])) {
                            $user_label = __('Previous user', 'metademands');
                            $user_name = trim(
                                $previousUser->fields['realname'] . ' ' . $previousUser->fields['firstname'],
                            );
                        }

                        TemplateRenderer::getInstance()->display('@metademands/wizard/form_previous_data.html.twig', [
                            'title' => __('Previous data edited', 'metademands'),
                            'user_label' => $user_label,
                            'user_name' => $user_name,
                            'content' => stripslashes(RichText::getSafeHtml($parent_fields['content'])),
                        ]);

                        $hidden_blocks = $_SESSION['plugin_metademands'][$metademands_id]['hidden_blocks'] ?? [];
                        if (is_array($hidden_blocks) && count($hidden_blocks) > 0) {
                            $hidden_ranks = [];
                            foreach ($hidden_blocks as $hidden_b) {
                                foreach ($hidden_b as $hidden_) {
                                    $hidden_ranks[] = (int) $hidden_;
                                }
                            }
                            TemplateRenderer::getInstance()->display(
                                '@metademands/wizard/form_hidden_blocks.html.twig',
                                ['hidden_blocks' => $hidden_ranks],
                            );
                        }
                    }
                }


                if (isset($_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id'])) {
                    echo Html::hidden(
                        'plugin_metademands_stepforms_id',
                        ['value' => $_SESSION['plugin_metademands'][$metademands_id]['plugin_metademands_stepforms_id']],
                    );
                }

                // #modalgroupspan and the Bootstrap confirmation modal are pure markup: they moved
                // to a template so Twig escapes their labels.
                TemplateRenderer::getInstance()->display('@metademands/wizard/form_confirm_modal.html.twig');
                self::validateScript($metaparams, $metaconditionsparams);
            }

            if ($draft_id != 0) {
                // The update / delete handlers moved to public/scripts/wizard_form.js. Their
                // configuration, the requester supplied draft name included, now travels as data
                // attributes instead of being concatenated into a JavaScript string literal.
                TemplateRenderer::getInstance()->display('@metademands/wizard/form_draft_buttons.html.twig', [
                    'draft_id'       => (int) $draft_id,
                    'draft_name'     => (string) $draft_name,
                    'metademands_id' => (int) $metademands_id,
                    'users_id'       => (int) Session::getLoginUserID(),
                    'add_url'        => PLUGIN_METADEMANDS_WEBDIR . '/ajax/adddraft.php',
                    'delete_url'     => PLUGIN_METADEMANDS_WEBDIR . '/ajax/deletedraft.php',
                    'draft_form_url' => PLUGIN_METADEMANDS_WEBDIR . '/front/draft.form.php',
                    'draft_list_url' => PLUGIN_METADEMANDS_WEBDIR . '/front/draft.php',
                ]);
            }
        } else {
            TemplateRenderer::getInstance()->display('@metademands/wizard/no_results.html.twig');
        }
    }


    /**
     * Render one block of the wizard: its title field, the fields it holds and the
     * sub-blocks a field option opens inside this very same block.
     *
     * @param Metademand $metademands
     * @param array      $metademands_data
     * @param bool       $preview
     * @param int        $block
     * @param array      $line
     * @param array      $subblocks_data
     * @param int        $itilcategories_id
     * @param int        $use_model
     */
    public static function displayBlockContent($metademands, $metademands_data, $preview, $block, $line, $subblocks_data, $itilcategories_id, $use_model)
    {
        $debug = isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        $keys = array_keys($line);

        // The frame and the floating block number only show up to a designer. The colour
        // used to be printed as a <style> block once per block; it now travels as the
        // --md-preview-color variable read by .md-preview-tag in css/metademands.css.
        $preview_color = ($preview || $debug) ? Field::setColor($block) : '';

        // Blocks shown as tabs already carry their title in the tab itself, except in
        // the preview where the designer needs to tell them apart.
        $display_blocks_as_tab = 0;
        static $sc_cache = [];
        $meta_id_for_sc = $metademands->getID();
        if (!isset($sc_cache[$meta_id_for_sc])) {
            $sc_obj = new Configstep();
            $sc_obj->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id_for_sc]);
            $sc_cache[$meta_id_for_sc] = $sc_obj->fields ?? [];
        }
        if ($metademands->fields['step_by_step_mode'] == 1
            && isset($sc_cache[$meta_id_for_sc]['see_blocks_as_tab'])
            && $sc_cache[$meta_id_for_sc]['see_blocks_as_tab'] == 1) {
            $display_blocks_as_tab = 1;
        }

        // getBlockTitleHtml() enriches $line with the parameters and the custom values of
        // the title field, which the fields loop below reads back.
        $title_html = '';
        if (($display_blocks_as_tab == 0 || $preview)
            && $line[$keys[0]]['type'] == 'title-block') {
            $title_html = self::getBlockTitleHtml($metademands, $metademands_data, $line, $preview, $itilcategories_id);
        }

        ob_start();
        foreach ($line as $key => $data) {
            self::displayBlockFields($metademands, $metademands_data, $preview, $keys, $line, $key, $data, $block, $itilcategories_id);
        }
        $fields_html = (string) ob_get_clean();

        $subblocks_html = [];
        foreach (self::getSameBlockSubblocks($line, $block) as $subfield) {
            $subs = $subblocks_data[$subfield] ?? [];
            if (count($subs) === 0) {
                // The legacy code closed a wrapper it had not opened when the sub-block held
                // no field, so the row of the whole block was cut short right there.
                continue;
            }

            ob_start();
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
                    $itilcategories_id,
                );
            }
            $subblocks_html[] = ['id' => $subfield, 'html' => (string) ob_get_clean()];
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/block_content.html.twig', [
            'block' => (int) $block,
            'preview_color' => $preview_color,
            'background_color' => $metademands->fields['background_color'] ?? '',
            'is_preview' => $preview || $debug,
            'show_model_alert' => ($block == 1 && $use_model == 1),
            'title_html' => $title_html,
            'fields_html' => $fields_html,
            'subblocks' => $subblocks_html,
        ]);
    }


    /**
     * Build the title field of a block and enrich its line with the parameters and the
     * custom values the designer attached to it.
     *
     * @param Metademand $metademands
     * @param array      $metademands_data
     * @param array      $line              enriched in place, the fields loop reads it back
     * @param bool       $preview
     * @param int        $itilcategories_id
     *
     * @return string
     */
    private static function getBlockTitleHtml($metademands, $metademands_data, array &$line, $preview, $itilcategories_id): string
    {
        $keys = array_keys($line);
        $data = $line[$keys[0]];
        $tb_field_id = (int) $line[$keys[0]]['id'];

        $fp_tb = FieldParameter::getFromStaticCache($tb_field_id);
        if ($fp_tb === false) {
            $fieldparameter = new FieldParameter();
            $fp_tb = $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $tb_field_id])
                ? $fieldparameter->fields
                : null;
        }

        if ($fp_tb !== null) {
            $params = $fp_tb;
            unset($params['plugin_metademands_fields_id'], $params['id']);
            $data = array_merge($line[$keys[0]], $params);
            if (isset($fp_tb['default'])) {
                $line[$keys[0]]['default_values'] = FieldParameter::_unserialize($fp_tb['default']);
            }
            if (isset($fp_tb['custom'])) {
                $line[$keys[0]]['custom_values'] = FieldParameter::_unserialize($fp_tb['custom']);
            }
        }

        if (in_array($line[$keys[0]]['type'], FieldCustomvalue::$allowed_customvalues_types)
            || in_array($line[$keys[0]]['item'], FieldCustomvalue::$allowed_customvalues_items)) {
            $fc_tb = FieldCustomvalue::getFromStaticCache($tb_field_id);
            if ($fc_tb === false) {
                $field_custom = new FieldCustomvalue();
                $fc_tb = $field_custom->find(['plugin_metademands_fields_id' => $tb_field_id], 'rank') ?: [];
            }
            if (count($fc_tb) > 0) {
                $line[$keys[0]]['custom_values'] = $fc_tb;
            }
        }

        ob_start();
        Field::displayFieldByType($metademands, $metademands_data, $data, $preview, $itilcategories_id);

        return (string) ob_get_clean();
    }


    /**
     * List the sub-blocks the fields of a block open inside that same block, in the
     * order of the value that triggers them.
     *
     * @param array $line
     * @param int   $block
     *
     * @return array list of sub-block ranks
     */
    private static function getSameBlockSubblocks(array $line, $block): array
    {
        $subblocks = [];
        $check_values = [];

        foreach ($line as $data) {
            $fo_cached = FieldOption::getFromStaticCache((int) $data['id']);
            if ($fo_cached === false) {
                $fieldopt = new FieldOption();
                $fo_cached = $fieldopt->find([
                    'plugin_metademands_fields_id' => $data['id'],
                    'hidden_block_same_block'      => 1,
                ]) ?: [];
            }

            $has_subblock = false;
            foreach ($fo_cached as $opt) {
                if (($opt['hidden_block_same_block'] ?? 0) != 1) {
                    continue;
                }
                $check_values[$opt['check_value']] = $opt['hidden_block'];
                $has_subblock = true;
            }

            if ($has_subblock) {
                asort($check_values);
                $subblocks[$data['rank']] = $check_values;
            }
        }

        return array_values($subblocks[$block] ?? []);
    }


    /**
     * Render one field of a block: the break it may open towards another block, the
     * field itself, then the scripts its options carry.
     *
     * @param Metademand $metademands
     * @param array      $metademands_data
     * @param bool       $preview
     * @param array      $keys
     * @param array      $line
     * @param int|string $key
     * @param array      $data
     * @param int        $block
     * @param int        $itilcategories_id
     */
    public static function displayBlockFields($metademands, $metademands_data, $preview, $keys, $line, $key, $data, $block, $itilcategories_id)
    {
        $debug = isset($_SESSION['glpi_use_mode'])
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        $config_link = '';
        if (Session::getCurrentInterface() == 'central' && $preview) {
            $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL(Field::class) . "?id=" . $data['id'] . "'>"
                . "<i class='ti ti-settings'></i></a>";
        }

        $data = self::mergeFieldParameters($data);
        $data = self::mergeFieldCustomValues($data, true);

        // The field belongs to another block than the one being rendered: close the
        // wrappers of the current block and open the ones of the new block.
        $key_indexes = array_flip($keys);
        if (isset($key_indexes[$key])
            && isset($keys[$key_indexes[$key] - 1])
            && $data['rank'] != $line[$keys[$key_indexes[$key] - 1]]['rank']) {
            self::displayBlockBreak($metademands, $data, $preview || $debug, $debug, $block, $config_link);
        }

        if ($data['type'] != 'title-block') {
            // The counter used to drive a column layout whose style was computed after
            // the row had already been printed, so it never reached the markup.
            $count = 0;
            Field::displayFieldByType(
                $metademands,
                $metademands_data,
                $data,
                $preview,
                $itilcategories_id,
                $count,
            );
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

        // The urgency, priority and impact fields are kept out of the merge feeding the
        // renderer above, but the option scripts below still read their custom values.
        // The second FieldParameter merge that stood here queried the very same row as
        // mergeFieldParameters() and merged the very same keys, none of which can reach
        // the session values set right above: the table holds no 'value' column.
        $data = self::mergeFieldCustomValues($data, false);

        //verifie si une sous metademande doit etre lancé
        FieldOption::taskScript($data, $itilcategories_id);

        //Active champs obligatoires sur les fields_link
        FieldOption::fieldsMandatoryScript($data, $itilcategories_id);

        //Affiche les hidden_link
        FieldOption::fieldsHiddenScript($data, $itilcategories_id);

        //cache ou affiche les hidden_block & child_blocks
        FieldOption::blocksHiddenScript($data, $itilcategories_id);

        FieldOption::checkboxScript($data);

        FieldOption::checkConditions($data);
    }


    /**
     * Merge the parameters a designer set on a field into its data.
     *
     * @param array $data
     *
     * @return array
     */
    private static function mergeFieldParameters(array $data): array
    {
        $cached = FieldParameter::getFromStaticCache((int) $data['id']);
        if ($cached === false) {
            // cache not warmed — fall back to single DB query
            $fieldparameter = new FieldParameter();
            $cached = $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['id']])
                ? $fieldparameter->fields
                : null;
        }

        if ($cached === null) {
            return $data;
        }

        $params = $cached;
        unset($params['plugin_metademands_fields_id'], $params['id']);
        $data = array_merge($data, $params);

        if (isset($cached['default'])) {
            $data['default_values'] = FieldParameter::_unserialize($cached['default']);
        }
        if (isset($cached['custom'])) {
            $data['custom_values'] = FieldParameter::_unserialize($cached['custom']);
        }

        return $data;
    }


    /**
     * Merge the values a designer listed for a field into its data.
     *
     * @param array $data
     * @param bool  $skip_itil_levels urgency, priority and impact carry the core value
     *                                lists, the renderer must not see a custom one
     *
     * @return array
     */
    private static function mergeFieldCustomValues(array $data, bool $skip_itil_levels): array
    {
        if (!isset($data['type'])
            || (!in_array($data['type'], FieldCustomvalue::$allowed_customvalues_types)
                && !in_array($data['item'], FieldCustomvalue::$allowed_customvalues_items))) {
            return $data;
        }

        if ($skip_itil_levels
            && in_array($data['item'], ['urgency', 'priority', 'impact'])) {
            return $data;
        }

        $cached = FieldCustomvalue::getFromStaticCache((int) $data['id']);
        if ($cached === false) {
            $field_custom = new FieldCustomvalue();
            $cached = $field_custom->find(['plugin_metademands_fields_id' => $data['id']], 'rank') ?: [];
        }

        if (count($cached) > 0) {
            $data['custom_values'] = $cached;
        }

        return $data;
    }


    /**
     * Close the wrappers of the block being rendered and open the ones of the block the
     * current field belongs to.
     *
     * @param Metademand $metademands
     * @param array      $data
     * @param bool       $is_preview
     * @param bool       $debug
     * @param int        $block
     * @param string     $config_link
     */
    private static function displayBlockBreak($metademands, array $data, bool $is_preview, bool $debug, $block, string $config_link): void
    {
        $title = null;

        if ($data['type'] == 'title-block') {
            if (empty($label = Field::displayField($data['id'], 'name'))) {
                $label = $data['name'];
            }

            $label2_tooltip_html = '';
            if (!empty($data['label2'])) {
                if (empty($label2 = Field::displayField($data['id'], 'label2'))) {
                    $label2 = $data['label2'];
                }
                // showToolTip() prints by default, which used to flush the tooltip before
                // the title it belongs to.
                $label2_tooltip_html = Html::showToolTip(
                    RichText::getSafeHtml($label2),
                    ['awesome-class' => 'ti ti-info-circle', 'display' => false],
                );
            }

            $comment_html = '';
            if (!empty($data['comment'])) {
                if (empty($comment = Field::displayField($data['id'], 'comment'))) {
                    $comment = $data['comment'];
                }
                // Designer-defined rich comment displayed to every requester: sanitize it
                // like the secondary label above.
                $comment_html = RichText::getSafeHtml($comment);
            }

            $title = [
                'color' => $data['color'] ?? '',
                'label' => $label,
                'id' => $data['id'],
                'label2_tooltip_html' => $label2_tooltip_html,
                'comment_html' => $comment_html,
            ];
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/block_break.html.twig', [
            'block' => (int) $block,
            'is_preview' => $is_preview,
            'debug' => $debug,
            'preview_color' => $is_preview ? Field::setColor($block) : '',
            // The legacy code read this colour from an undefined $meta variable, so the
            // row never got the background the designer had picked.
            'background_color' => $metademands->fields['background_color'] ?? '',
            'config_link' => $config_link,
            'title' => $title,
        ]);
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
                    metademandparams.confirmmsg = $confirmmsg;
                    metademandparams.is_order = '$is_order';
                    metademandparams.root_doc = '$root_doc';
                    metademandparams.paramUrl = $paramUrl;
                    metademandparams.edit_model = '$edit_model';
                    metademandparams.seeform = '$seeform';
                    metademandparams.token = '$token';
                    metademandparams.id = '$ID';
                    metademandparams.nameform = $nameform;
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
                    metademandparams.changestepbystepoption = '$changestepbystepoption';
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
                    const nextBtn2 = document.getElementById('nextBtn2');

                    firstnumTab = plugin_metademands_wizard_findFirstTab(metademandparams.block_id, metademandparams);

                    plugin_metademands_wizard_showTab(firstnumTab, metademandparams, metademandconditionsparams);

                    prevBtn.addEventListener('click', () => {
                      plugin_metademands_wizard_prevBtn(-1, firstnumTab, metademandparams, metademandconditionsparams);
                    });

                    nextBtn.addEventListener('click', async () => {
                          const result = await plugin_metademands_wizard_nextBtn(1, firstnumTab, metademandparams, metademandconditionsparams, false);
                          if (result !== false) {
                            plugin_metademands_wizard_showTab(firstnumTab, metademandparams, metademandconditionsparams);
                          }
                        });

                    nextBtn2.addEventListener('click', async () => {
                          const result = await plugin_metademands_wizard_nextBtn(1, firstnumTab, metademandparams, metademandconditionsparams, true);
                          if (result !== false) {
                            plugin_metademands_wizard_showTab(firstnumTab, metademandparams, metademandconditionsparams);
                          }
                        });

                    document.querySelectorAll('a.tablinks').forEach(function(tabLink) {
                        tabLink.addEventListener('click', async function(e) {
                            e.preventDefault();
                            const targetBlockId = parseInt(this.id.replace('ablock', ''));
                            await plugin_metademands_wizard_goToTab(targetBlockId, firstnumTab, metademandparams, metademandconditionsparams);
                        });
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
        // Second entry point into the same paramUrl sink, with the same PHP 8 string
        // comparison pitfall as front/wizard.form.php:296.
        $current_ticket_id = (int) ($values['fields']['current_ticket_id'] ?? 0);
        if ($current_ticket_id > 0) {
            $options['current_ticket_id'] = $current_ticket_id;
        }
        if (isset($values['fields']['meta_validated'])) {
            $options['meta_validated'] = $values['fields']['meta_validated'];
        }

        $self = new self();
        $metademands = new Metademand();
        if ($metademands->getFromDB($metademands_id)) {
            if ($metademands->fields['is_order'] == 1
                && isset($values['basket'])) {
                $basketclass = new Basketline();
                $current_user = Session::getLoginUserID();

                // Preload all upload basket lines for this user/metademand in one query
                // Indexed as $upload_index[field_id][line] = row
                $upload_index = [];
                foreach ($basketclass->find([
                    'plugin_metademands_metademands_id' => $metademands_id,
                    'users_id' => $current_user,
                    'name' => 'upload',
                ]) as $row) {
                    $upload_index[(int) $row['plugin_metademands_fields_id']][(int) $row['line']] = $row;
                }

                if ($metademands->fields['create_one_ticket'] == 0) {
                    //create one ticket for each basket
                    foreach ($values['basket'] as $k => $basket) {
                        $datas = [];
                        $datas['basket'] = $basket;

                        unset(
                            $values['fields']['_filename'],
                            $values['fields']['_prefix_filename'],
                            $values['fields']['_tag_filename'],
                        );
                        $filename = [];
                        $prefixname = [];
                        $tagname = [];
                        $line = $k + 1;
                        foreach ($basket as $key => $val) {
                            if (isset($upload_index[(int) $key][$line]) && !empty($val)) {
                                $files = json_decode($val, true);
                                foreach ($files as $file) {
                                    $filename[]    = $file['_filename'];
                                    $prefixname[]  = $file['_prefix_filename'];
                                    $tagname[]     = $file['_tag_filename'];
                                }
                            }
                        }

                        $values['fields']['_filename'] = $filename;
                        $values['fields']['_prefix_filename'] = $prefixname;
                        $values['fields']['_tag_filename'] = $tagname;

                        $datas['fields'] = $values['fields'];

                        $result = Metademand::addObjects($metademands_id, $datas, $options);
                        if (is_array($result)) {
                            Session::addMessageAfterRedirect($result['message']);
                        }
                    }
                    $basketclass->deleteByCriteria([
                        'plugin_metademands_metademands_id' => $metademands_id,
                        'users_id' => $current_user,
                    ]);
                } else {
                    //create one ticket for all basket
                    unset(
                        $values['fields']['_filename'],
                        $values['fields']['_prefix_filename'],
                        $values['fields']['_tag_filename'],
                    );
                    $filename = [];
                    $prefixname = [];
                    $tagname = [];
                    foreach ($values['basket'] as $k => $basket) {
                        $line = $k + 1;
                        foreach ($basket as $key => $val) {
                            if (isset($upload_index[(int) $key][$line]) && !empty($val)) {
                                $files = json_decode($val, true);
                                foreach ($files as $file) {
                                    $filename[]    = $file['_filename'];
                                    $prefixname[]  = $file['_prefix_filename'];
                                    $tagname[]     = $file['_tag_filename'];
                                }
                            }
                        }
                    }
                    $values['fields']['_filename'] = $filename;
                    $values['fields']['_prefix_filename'] = $prefixname;
                    $values['fields']['_tag_filename'] = $tagname;

                    $basketclass->deleteByCriteria([
                        'plugin_metademands_metademands_id' => $metademands_id,
                        'users_id' => $current_user,
                    ]);

                    $result = Metademand::addObjects($metademands_id, $values, $options);
                    if (is_array($result)) {
                        Session::addMessageAfterRedirect($result['message']);
                    }
                }
            } else {
                //not in basket
                $result = Metademand::addObjects($metademands_id, $values, $options);
                if (isset($values['plugin_metademands_stepforms_id'])) {
                    $step = new Stepform();
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
                if (method_exists(ServiceCatalogConfig::class, 'getMultiEntityRedirection') && ServiceCatalogConfig::getConfig()->getMultiEntityRedirection()) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php?changeactiveentity");
                } elseif (method_exists(ServiceCatalogConfig::class, 'getTicketRedirection') && ServiceCatalogConfig::getConfig()->getTicketRedirection() && isset($result) && isset($result['id']) && $result['id'] > 0) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/ticket.form.php?id=" . $result['id']);
                } elseif (Session::haveRight("plugin_servicecatalog_redirect_on_menu", READ)) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
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
                Html::redirect($self->getFormURL() . "?step=" . Metademand::STEP_INIT);
            } else {
                Html::back();
            }
        }
    }

    /**
     * @param      $message
     * @param bool $error
     *
     * @return string
     */
    public static function showMessage($message, $error = false)
    {
        return TemplateRenderer::getInstance()->render('@metademands/wizard/message.html.twig', [
            'message'  => $message,
            'is_error' => (bool) $error,
        ]);
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

        $field = new Field();
        if ($field->getFromDB($value["id"])) {
            $value = Field::getAllParamsFromField($field);
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

        //        $content[$id]['plugin_metademands_fields_id'] = $id;
        //        $content[$id]['value'] = (isset($post[$fieldname][$id])) ? $post[$fieldname][$id] : "";
        //        $content[$id]['value2'] = (isset($post[$fieldname][$id . "-2"])) ? $post[$fieldname][$id . "-2"] : "";
        //        $content[$id]['item'] = $value['item'];
        //        $content[$id]['type'] = $value['type'];
        //
        //        return ['result' => $KO, 'content' => $content];


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
                $post,
            )) {
                $KO = true;
            } else {
                $_SESSION['plugin_metademands'][$post['form_metademands_id']]['fields'][$id] = $post[$fieldname][$id];
                if (isset($post[$fieldname][$id . "-2"])
                    && ($value['type'] == 'date_interval' || $value['type'] == 'datetime_interval')
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
                $post,
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
                $post,
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
                $post,
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
                    $post,
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
                        $post[$fieldname][$id],
                    )) ? FieldParameter::_serializeArray(
                        $post[$fieldname][$id],
                    ) : $post[$fieldname][$id];
                } else {
                    if (is_array($post[$fieldname][$id])) {
                        $content[$id]['value'] = FieldParameter::_serializeArray($post[$fieldname][$id]);
                    } else {
                        $content[$id]['value'] = $post[$fieldname][$id];
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
        if (isset($post["form_metademands_id"])) {
            $post["metademands_id"] = $post["form_metademands_id"];
        }
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

            $class = Field::getClassFromType($value['type']);

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
                && Ticket_Field::isCheckValueOK(
                    $fields['value'],
                    $value['check_value'],
                    $value['type'],
                )
                && (empty($all_fields[$value['fields_link']]) || $all_fields[$value['fields_link']] == 'NULL')
            ) {
                $field = new Field();
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
                    $msg[] = sprintf(__("Date %s cannot be less than initial date", 'metademands'), $value['name']);
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
                        $msg3[] = addslashes($value['name']);
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
                ERROR,
            );
            return false;
        }
        if (in_array(1, $checkNbDoc)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __("Too much documents are upload, max %s. Please correct: %s", "metademands"),
                    $value["max_upload"],
                    implode(', ', $msg2),
                ),
                false,
                ERROR,
            );
            return false;
        }
        if (in_array(1, $checkRegex)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __("Field do not correspond to the expected format. Please correct: %s", "metademands"),
                    implode(', ', $msg3),
                ),
                false,
                ERROR,
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
    //            if (!Ticket_Field::isCheckValueOK($post[$id], $check_value, $value['type'])) {
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
        return false;
    }
}
