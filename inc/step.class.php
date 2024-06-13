<?php
/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * PluginMetademandsStep Class
 *
 **/
class PluginMetademandsStep extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Step-by-Step block', 'Step-by-Step blocks', $nb, 'metademands');
    }

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


    public function canCreateItem()
    {
        return true;
    }


    public static function canUpdate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }

    /**
     * Get the standard massive actions which are forbidden
     *
     * @return array an array of massive actions
     **@since version 0.84
     *
     * This should be overloaded in Class
     *
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case PluginMetademandsMetademand::getType():
                if ($item->fields['step_by_step_mode'] == 1) {
                    return self::createTabEntry(self::getTypeName(2));
                } else {
                    return false;
                }

                break;
        }
    }

    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     **
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case PluginMetademandsMetademand::getType():
                self::showStepByBlock($item);
                break;
        }
        return true;
    }


    /**
     * @param $plugin_metademands_metademands_id
     * @param $block_id
     *
     * @return false|mixed
     */
    public static function getGroupForNextBlock($plugin_metademands_metademands_id, $block_id)
    {
        $self = new self();
        $condition = [
            'block_id' => $block_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id
        ];

        $steps = $self->find($condition);
        if (count($steps) > 0) {
            foreach ($steps as $step) {
                if (isset($step['groups_id'])) {
                    return $step['groups_id'];
                }
            }
        }
        return false;
    }

    /**
     * @param $plugin_metademands_metademands_id
     * @param $block_id
     *
     * @return false|mixed
     */
    public static function getMsgForNextBlock($plugin_metademands_metademands_id, $block_id)
    {
        global $DB;

        $rank = 0;
        $ranks = $DB->request(['SELECT' => ['MAX' => 'rank AS maxblock'],
            'FROM' => 'glpi_plugin_metademands_fields',
            'WHERE' => ['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]]);

        foreach ($ranks as $data) {
            $rank = $data['maxblock'];
        }

        $self = new self();
        $condition = [
            'block_id' => $block_id,
            'NOT' => ['block_id' => $rank],
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id
        ];

        $steps = $self->find($condition);
        if (count($steps) > 0) {
            foreach ($steps as $step) {
                if (isset($step['message'])) {
                    return $step['message'];
                }
            }
        }
        return false;
    }

    /**
     * Display all translated field for a dropdown
     *
     * @param $item a Dropdown item
     *
     * @return true;
     **/
    public static function showStepByBlock($item)
    {
        global $DB, $CFG_GLPI;

        $rand = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        if ($canedit) {
            echo "<div id='viewstepbybloc" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addstepbybloc" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = [
                'type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id' => -1
            ];
            Ajax::updateItemJsCode(
                "viewstepbybloc" . $item->getType() . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
                "<a class='submit btn btn-primary' href='javascript:addstepbybloc" .
                $item->getType() . $item->getID() . "$rand();'>" . __('Add a new association', 'metademands') .
                "</a></div><br>";
        }
        $iterator = $DB->request([
            'FROM' => getTableForItemType(__CLASS__),
            'WHERE' => [
                'plugin_metademands_metademands_id' => $item->getID(),
            ],
            'ORDER' => [
                'block_id ASC'
            ],
        ]);

        $field = new PluginMetademandsField();
        $fields = $field->find(["plugin_metademands_metademands_id" => $item->getID()]);
        $blocks = [];
        $self = new self();
        foreach ($fields as $f) {
            $steps = $self->find([
                'plugin_metademands_metademands_id' => $item->getID(),
                'block_id' => intval($f['rank'])
            ]);
            foreach ($steps as $step) {
                if (!isset($blocks[$f['rank']]) &&
                    (!$self->getFromDBByCrit([
                        'plugin_metademands_metademands_id' => $item->getID(),
                        'block_id' => intval($f['rank']),
                        'id' => $step['id']
                    ]))) {
                    $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
                }
            }
        }
        ksort($blocks);
        if (count($blocks) > 0) {
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo "<i class='fas fa-exclamation-triangle fa-3x'></i>&nbsp;";
            echo __(
                'Be careful if all blocks are not assigned, they will be displayed to the last assigned group',
                'metademands'
            );
            echo "</div>";
        }

        if (count($iterator)) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='3'>" . __("List of associations", 'metademands') . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("Block", 'metademands') . "</th>";
            echo "<th>" . __("Group") . "</th>";
            echo "<th>" . __("Message", 'metademands') . "</th>";
            foreach ($iterator as $data) {
                $onhover = '';
                if ($canedit) {
                    $onhover = "style='cursor:pointer'
                           onClick=\"viewEditstepbyblock" . $item->getType() . $data['id'] . "$rand();\"";
                }
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }

                echo "<td $onhover>";
                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditstepbyblock" . $item->getType() . $data['id'] . "$rand() {\n";
                    $params = [
                        'type' => __CLASS__,
                        'parenttype' => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id' => $data["id"]
                    ];
                    Ajax::updateItemJsCode(
                        "viewstepbybloc" . $item->getType() . $item->getID() . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo($data['block_id']);
                echo "</td><td $onhover>";
                echo Dropdown::getDropdownName(Group::getTable(), $data['groups_id']);
                echo "</td><td $onhover>";
                echo Glpi\RichText\RichText::getTextFromHtml($data['message']);
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
            echo "<th class='b'>" . __("No association found", 'metademands') . "</th></tr></table>";
        }
        return true;
    }


    /**
     * Display translation form
     *
     * @param int $ID field (default -1)
     * @param     $options   array
     *
     * @return bool
     */
    public function showForm($ID = -1, $options = [])
    {
        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }
        if ($ID > 0) {
            $this->check($ID, UPDATE);
        } else {
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();

            // Create item
            $this->check(-1, CREATE, $options);
        }
        $configStep = new PluginMetademandsConfigstep();
        $res = $configStep->getFromDBByCrit(['plugin_metademands_metademands_id' => $item->fields['id']]);
        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Block', 'metademands') . "</td>";
        echo "<td>";
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->getID()]);
//      echo Html::hidden('itemtype', ['value' => get_class($item)]);
        $field = new PluginMetademandsField();
        $fields = $field->find(["plugin_metademands_metademands_id" => $item->getID()]);
        $blocks = [];
        $self = new self();
        foreach ($fields as $f) {
            if (isset($configStep->fields['multiple_link_groups_blocks'])) {
                if (!isset($blocks[$f['rank']])) {
                    $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
                }
            } else {
                //Remove block_id <=> groups_id multiple links
//                $blocks_link = new PluginMetademandsStep();
//                $block_links = $blocks_link->find([
//                    'plugin_metademands_metademands_id' => $options['items_id'],
//                    'block_id' => $f['rank']
//                ]);
//                if (count($block_links) > 1) {
//                    foreach ($block_links as $bl) {
//                        $blocks_link->delete(['id' => $bl['id']]);
//                    }
//                }
                if (!isset($blocks[$f['rank']]) &&
                    (!$self->getFromDBByCrit([
                            'plugin_metademands_metademands_id' => $item->getID(),
                            'block_id' => intval($f['rank'])
                        ])
                        || $self->getFromDBByCrit([
                            'plugin_metademands_metademands_id' => $item->getID(),
                            'block_id' => intval($f['rank']),
                            'id' => $ID
                        ]))) {
                    $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
                }
            }
        }
        ksort($blocks);

        if ($this->fields['block_id']) {
            Dropdown::showFromArray(
                'block_id',
                $blocks,
                [
                    'value' => $this->fields['block_id'],
                    'width' => '100%',
                    'entity' => $_SESSION['glpiactiveentities']
                ]
            );
        } else {
            $values = [$this->fields['block_id']];

            Dropdown::showFromArray(
                'block_id',
                $blocks,
                [
                    'values' => $values,
                    'width' => '100%',
                    'multiple' => true,
                    'entity' => $_SESSION['glpiactiveentities']
                ]
            );
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . Group::getTypeName() . "</td>";
        echo "<td>";
        $meta_group = new PluginMetademandsGroup();
        $meta_groups = $meta_group->find(['plugin_metademands_metademands_id' => $item->getID()]);
        $groups = [];
        foreach ($meta_groups as $group) {
            $gr = new Group();
            $gr->getFromDB($group['groups_id']);
            $groups[$group['groups_id']] = $gr->getFriendlyName();
        }
        if (!empty($groups)) {
            Dropdown::showFromArray(
                'groups_id',
                $groups,
                [
                    'value' => $this->fields['groups_id'],
                    'width' => '100%',
                    'entity' => $_SESSION['glpiactiveentities']
                ]
            );
        } else {
            Group::dropdown([
                'name' => 'groups_id',
                'value' => $this->fields['groups_id']
            ]);
        }


        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Message to next group on form', 'metademands') . "</td>";
        echo "<td>";
        Html::textarea([
            'name' => 'message',
            'value' => $this->fields['message'],
            'enable_richtext' => false,
            'cols' => 80,
            'rows' => 3
        ]);
        echo "</td>";
        echo "</tr>";
        $this->showFormButtons($options);
        return true;
    }

    /**
     * @param $metademands_id
     *
     * @return bool
     */
    static function isUserHaveRight($metademands_id)
    {
        $dbu = new DbUtils();
        // Get metademand groups
        $metademands_groups_data = $dbu->getAllDataFromTable(
            'glpi_plugin_metademands_steps',
            ['`plugin_metademands_metademands_id`' => $metademands_id, 'block_id' => 1]
        );

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($metademands_id);
        if (!empty($metademands_groups_data) && $metademand->fields['step_by_step_mode'] == 1) {
            $metademands_groups_id = [];
            foreach ($metademands_groups_data as $groups) {
                $metademands_groups_id[] = $groups['groups_id'];
            }

            // Is the user allowed with his groups ?
            $group_user_data = Group_User::getUserGroups(Session::getLoginUserID());
            foreach ($group_user_data as $groups) {
                if (in_array($groups['id'], $metademands_groups_id)) {
                    return true;
                }
            }

            return false;
        }

        // No restrictions if no group was added in metademand
        return true;
    }

    /**
     * @param $metademands_id
     * @param $block_id
     * @param $user_id
     *
     *
     * @return bool
     */
    static function canSeeBlock($metademand_id, $block_id)
    {
        $return = false;
        $user_id = Session::getLoginUserID();
        $metademandStep = new PluginMetademandsStep();
        $steps = $metademandStep->find(
            [
                'plugin_metademands_metademands_id' => $metademand_id,
                'block_id' => $block_id
            ]
        );
        $groups_id = [];
        foreach ($steps as $step) {
            $groups_id[] = $step['groups_id'];
        }
        $groupsUser = Group_User::getUserGroups($user_id);
        foreach ($groupsUser as $gu) {
            if (in_array($gu['id'], $groups_id)) {
                $return = true;
            }
        }
        return $return;
    }

    function prepareInputForAdd($input)
    {
        $steps = new PluginMetademandsStep();
        $condition = [
            'block_id' => $input['block_id'],
            'groups_id' => $input['groups_id'],
            'plugin_metademands_metademands_id' => $input['plugin_metademands_metademands_id']
        ];
        $result = $steps->getFromDBByCrit($condition);
        if ($result) {
            Session::addMessageAfterRedirect(
                __('This group is already assigned to this block', 'metademands'),
                false,
                ERROR
            );
            $input = [];
        }
        return $input;
    }

    /**
     * display the next group modal
     *
     * @return string
     */
    static function showModal()
    {
        global $CFG_GLPI;
        $step = new PluginMetademandsStep();
        $user_id = Session::getLoginUserID();
        if (isset($_POST['metademands_id']) && !empty($_POST['metademands_id'])) {
            $meta_id = $_POST['metademands_id'];
        }
        if (isset($_POST['block_id']) && !empty($_POST['block_id'])) {
            $block_id = $_POST['block_id'];
        }

        $_SESSION['plugin_metademands'][$user_id] = $_POST;
        $url = PLUGIN_METADEMANDS_WEBDIR . '/front/nextGroup.form.php?block_id=' . $block_id;
        $return = Ajax::createIframeModalWindow(
            'modalgroup',
            $url,
            [
                'title' => __('Next recipient', 'metademands'),
                'display' => false,
                'reloadonclose' => true,
                'autoopen' => true,
                'width' => 400,
                'height' => 400
            ]
        );

        return $return;
    }

    /**
     * Create and display the form inside next group modal
     *
     * @return string
     */
    static function showModalForm()
    {
        global $CFG_GLPI;

        $conf = new PluginMetademandsConfigstep();
        $step = new PluginMetademandsStep();
        $group = new Group();
        $groupUser = new Group_User();
        $user_id = Session::getLoginUserID();

        $nextGroups = [];
//        $rand = mt_rand();
        if (isset($_SESSION['plugin_metademands'][$user_id])) {
            $meta_id = $_SESSION['plugin_metademands'][$user_id]['metademands_id'];
            $conf->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta_id]);
        }
        $block_id = 0;
        if (isset($_GET['block_id']) && !empty($_GET['block_id'])) {
            $block_id = $_GET['block_id'];
        }
        if (!$conf->fields['multiple_link_groups_blocks'] && !$conf->fields['link_user_block']) {

            Html::popHeader('nextGroup');

            echo "<div class='alert alert-important alert-danger d-flex'>";
            echo "<b>" . __('There is a problem with the setup', 'metademands') . "</b></div>";

        } elseif ($conf->fields['multiple_link_groups_blocks']
            || (!$conf->fields['multiple_link_groups_blocks'] && $conf->fields['link_user_block'])) {

            echo "<form name='nextGroup_form' method='post' action='" . PLUGIN_METADEMANDS_WEBDIR . "/front/nextGroup.form.php'>";
            echo "<table class='tab_cadre_fixe'>";
            if (isset($_SESSION['plugin_metademands'][$user_id])) {
                if (isset($_SESSION['plugin_metademands'][$meta_id]['plugin_metademands_stepforms_id'])) {
                    echo Html::hidden('plugin_metademands_stepforms_id', ['value' => $_SESSION['plugin_metademands'][$meta_id]['plugin_metademands_stepforms_id']]);
                }
                $post = $_SESSION['plugin_metademands'][$user_id];
                echo Html::hidden('tickets_id', ['value' => $post['tickets_id']]);
                echo Html::hidden('resources_id', ['value' => $post['resources_id']]);
                echo Html::hidden('resources_step', ['value' => $post['resources_step']]);
                echo Html::hidden('block_id', ['value' => $post['block_id']]);
                echo Html::hidden('form_name', ['value' => $post['form_name']]);
                echo Html::hidden('_users_id_requester', ['value' => $post['_users_id_requester']]);
                echo Html::hidden('form_metademands_id', ['value' => $post['form_metademands_id']]);
                echo Html::hidden('metademands_id', ['value' => $post['metademands_id']]);
                echo Html::hidden('create_metademands', ['value' => $post['create_metademands']]);
                echo Html::hidden('step', ['value' => $post['step']]);
                echo Html::hidden('action', ['value' => $post['action']]);
                echo Html::hidden('update_stepform', ['value' => $post['update_stepform']]);

                $block_id = $post['block_id'];

            }

            $steps = $step->find([
                'plugin_metademands_metademands_id' => $meta_id,
                'block_id' => $block_id
            ]);

            if (count($steps) > 0) {
                foreach ($steps as $s) {
                    $res = $group->getFromDBByCrit(['id' => $s['groups_id']]);
                    if ($res) {
                        $nextGroups[$group->fields['id']] = $group->fields['name'];
                    }
                }
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='2'>";
                echo "<label class='control-label center' for='next_groups_id'>" . __(
                        'Select the next group',
                        'metademands'
                    ) . "&nbsp;</label>";
                echo "</td>";
                echo "<td colspan='2'>";
                $rand =  Dropdown::showFromArray(
                    'next_groups_id',
                    $nextGroups,
                    [
                        'display_emptychoice' => true,
                        'on_change' => 'plugin_md_reloaduser()'
                    ]
                );
                echo "</td>";
                echo "</tr>";
            }
        }
        if ($conf->fields['link_user_block']) {

            echo "<script type='text/javascript'>";
            echo "function plugin_md_reloaduser(){";
            $params = ['action'            => 'reloadUser',
                'next_groups_id' => '__VALUE__',
            ];
            Ajax::updateItemJsCode(
                'show_users_by_group',
                PLUGIN_METADEMANDS_WEBDIR . "/ajax/dropdownNextUser.php",
                $params,
                'dropdown_next_groups_id' . $rand
            );
            echo "};";
            echo "</script>";

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "<div id ='show_users_by_group'>";
            echo "</div>";
            echo "</td>";
            echo "</tr>";

        }
        if ($conf->fields['multiple_link_groups_blocks'] || $conf->fields['link_user_block']) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";

            echo Html::submit(_sx(
                'button',
                'Validate',
                'metademands'
            ), ['name'  => 'execute',
                'id'    => 'formsubmit',
                'class' => 'btn btn-primary']);

            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        Html::closeform();

    }

    /**
     * Display drop-down list of users groups
     *
     * @param $groupUsers
     */
    public static function displayNextUser($groupUsers)
    {
        $user = new User();
        $users = [];
        foreach ($groupUsers as $grpUsr) {
            $res = $user->getFromDBByCrit(['id' => $grpUsr['users_id']]);
            if ($res) {
                $users[$grpUsr['users_id']] = getUserName($grpUsr['users_id'], 0, true);
            }
        }
        echo "<label class='control-label center' for='next_users_id'>" . __('User') . "&nbsp;</label>";
        echo "</br>";

        $options = [
            'display_emptychoice' => true,
            'display' => false,
        ];
        echo Dropdown::showFromArray(
            'next_users_id',
            $users,
            $options);
    }

    static function nextUser()
    {
        $KO = false;
        $metademands = new PluginMetademandsMetademand();
        $wizard = new PluginMetademandsWizard();
        $fields = new PluginMetademandsField();
        $user_id = Session::getLoginUserID();

        if (isset($_POST['action']) && $_POST['action'] == 'nextUser') {
            $nblines = 0;
            $KO = false;

            if ($nblines == 0) {
                if (isset($_POST['field'])) {
                    $post = $_POST['field'];
                } else {
                    $post = $_SESSION['plugin_metademands'][$user_id]['field'];
                }
                $nblines = 1;
            }

            if ($KO === false) {
                $checks = [];
                $content = [];

                for ($i = 0; $i < $nblines; $i++) {
                    $_POST['field'] = $post;
                    $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
                    if (count($metademands_data)) {
                        foreach ($metademands_data as $form_step => $data) {
                            $docitem = null;
                            foreach ($data as $form_metademands_id => $line) {
                                foreach ($line['form'] as $id => $value) {
                                    if (!isset($post[$id])) {
                                        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id])
                                            && $value['plugin_metademands_metademands_id'] != $_POST['form_metademands_id']) {
                                            $_POST['field'][$id] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id];
                                        } else {
                                            $_POST['field'][$id] = [];
                                        }
                                    } else {
                                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id] = $post[$id];
                                    }

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
                                    }

                                    if ($data["item"] == "ITILCategory_Requestevolutions") {
                                        $_POST['field'][$id] = $_POST['field_plugin_requestevolutions_itilcategories_id'] ?? 0;
                                    }
                                }
                            }
                        }
                    }
                    $metademands->getFromDB($_POST['metademands_id']);
                    if ($KO === false) {
                        // Save requester user
                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
                        // Case of simple ticket convertion
                        if (isset($_POST['items_id']) && $_POST['itemtype'] == 'Ticket') {
                            $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['tickets_id'] = $_POST['items_id'];
                        }
                        // Resources id
                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
                        // Resources step
                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];

                        //Category id if have category field
                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
                            (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
                                && $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
                        if(isset($_POST['field_plugin_requestevolutions_itilcategories_id'])){
                            //Category id if have category field
                            $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_requestevolutions_itilcategories_id'] = $_POST['field_plugin_requestevolutions_itilcategories_id'];
                        }
                    }

                    $forms = new PluginMetademandsStepform();
                    //         if (isset($_POST['plugin_metademands_forms_id'])
                    //             && !empty($_POST['plugin_metademands_forms_id'])) {
                    //            $form_id = $_POST['plugin_metademands_forms_id'];
                    //            $forms->getFromDB($_POST['plugin_metademands_forms_id']);
                    //            $forms_values = new PluginMetademandsForm_Value();
                    //            $forms_values->deleteByCriteria(['plugin_metademands_forms_id' => $form_id]);
                    //            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
                    //            if (count($metademands_data)) {
                    //               foreach ($metademands_data as $form_step => $data) {
                    //                  $docitem = null;
                    //                  foreach ($data as $form_metademands_id => $line) {
                    //                     PluginMetademandsForm_Value::setFormValues($line['form'], $_POST['field'], $form_id);
                    //                  }
                    //               }
                    //            }
                    //            PluginMetademandsForm_Value::loadFormValues($form_id);
                    //            $_POST['form_name'] = $forms->getField('name');
                    //         } else {
                    if (!isset($_POST['block_id']) || (isset($_POST['block_id']) && empty($_POST['block_id']))) {
                        Session::addMessageAfterRedirect(
                            __('Error assigning to next group', 'metademands'),
                            false,
                            ERROR
                        );
                        break;
                    }

                    $inputs = [];
                    $inputs['name'] = Toolbox::addslashes_deep($_POST['form_name']);
                    $inputs['users_id'] = Session::getLoginUserID();
                    if (isset($_POST['next_groups_id'])) {
                        $inputs['groups_id_dest'] = $_POST['next_groups_id'];
                    } else {
                        $inputs['groups_id_dest'] = PluginMetademandsStep::getGroupForNextBlock(
                            $_POST['metademands_id'],
                            $_POST['block_id']
                        );
                    }
                    $inputs['plugin_metademands_metademands_id'] = $_POST['metademands_id'];
                    $inputs['date'] = date('Y-m-d H:i:s');
                    $nbday = 7;
                    if (isset($_SESSION['plugin_metademands'][$user_id]['users_id_dest'])) {
                        $inputs['users_id_dest'] = $_SESSION['plugin_metademands'][$user_id]['users_id_dest'];
                    }
                    if ($nbday == 0) {
                        $inputs['reminder_date'] = null;
                    } elseif ($nbday == 1) {
                        $inputs['reminder_date'] = date('Y-m-d', strtotime("+ $nbday day"));
                    } elseif ($nbday > 1) {
                        $inputs['reminder_date'] = date('Y-m-d', strtotime("+ $nbday days"));
                    }
                    $inputs['block_id'] = $_POST['block_id'];
                    $actor = new PluginMetademandsStepform_Actor();

                    if ((isset($_POST['plugin_metademands_stepforms_id'])
                            && !empty($_POST['plugin_metademands_stepforms_id']))
                        || $_POST['update_stepform'] == 1) {
                        $form_new_id = $_POST['plugin_metademands_stepforms_id'];

                        $inputsUpdate = [
                            'id' => $form_new_id,
                            'users_id' => $user_id,
                            'groups_id_dest' => $inputs['groups_id_dest'],
                            'reminder_date' => $inputs['reminder_date'],
                            'date' => $inputs['date'],
                            'block_id' => $inputs['block_id']
                        ];
                        if (isset($inputs['users_id_dest'])) {
                            $inputsUpdate['users_id_dest'] = $inputs['users_id_dest'];
                        }


                        $forms->update($inputsUpdate);
                        $actor->add([
                            'plugin_metademands_stepforms_id' => $form_new_id,
                            'users_id' => $user_id
                        ]);

                        if (isset($inputs['users_id_dest'])) {
                            $actor->add([
                                'plugin_metademands_stepforms_id' => $form_new_id,
                                'users_id' => $inputsUpdate['users_id_dest']
                            ]);
                        }

                        $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
                        if (count($metademands_data) && $form_new_id > 0) {
                            foreach ($metademands_data as $form_step => $data) {
                                $docitem = null;
                                foreach ($data as $form_metademands_id => $line) {
                                    PluginMetademandsStepform_Value::setFormValues(
                                        $_POST['metademands_id'],
                                        $line['form'],
                                        $_POST['field'],
                                        $form_new_id
                                    );
                                }
                            }
                        }
                    } else {
                        if ($form_new_id = $forms->add($inputs)) {
                            $actor->add([
                                'plugin_metademands_stepforms_id' => $form_new_id,
                                'users_id' => $inputs['users_id']
                            ]);
                            if (isset($inputs['users_id_dest'])) {
                                $actor->add([
                                    'plugin_metademands_stepforms_id' => $form_new_id,
                                    'users_id' => $inputs['users_id_dest']
                                ]);
                            }
                            unset($_SESSION['plugin_metademands'][$user_id]);

                            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);

                            if (count($metademands_data) && $form_new_id > 0) {
                                foreach ($metademands_data as $form_step => $data) {
                                    $docitem = null;
                                    foreach ($data as $form_metademands_id => $line) {
                                        PluginMetademandsStepform_Value::setFormValues(
                                            $_POST['metademands_id'],
                                            $line['form'],
                                            $_POST['field'],
                                            $form_new_id
                                        );
                                    }
                                }
                            }
                        } else {
                            $KO = false;
                        }
                    }
                    //         }
                }
            }
        }
        return $KO;
    }

}
