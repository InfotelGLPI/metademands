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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsProfile
 */
class PluginMetademandsProfile extends Profile
{
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Right management', 'Rights management', $nb, 'metademands');
    }

    public static function getIcon()
    {
        return "ti ti-share";
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
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE, PURGE]);
    }

    /**
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(PluginMetademandsMetademand::getTypeName(2));
        }
        return '';
    }


    /**
     * @param \CommonGLPI $item
     * @param int         $tabnum
     * @param int         $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            $ID   = $item->getID();
            $prof = new self();

            $rights = [
                'plugin_metademands' => 0,
                'plugin_metademands_followup' => 0,
                'plugin_metademands_updatemeta' => 0,
                'plugin_metademands_on_login' => 0,
                'plugin_metademands_in_menu' => 0,
                'plugin_metademands_createmeta' => 0,
                'plugin_metademands_validatemeta' => 0,
                'plugin_metademands_fillform' => 0,
                'plugin_metademands_cancelform' => 0,
                'plugin_metademands_publicforms' => 0,
            ];

            self::addDefaultProfileInfos($ID, $rights);
            $prof->showForm($ID);
        }

        return true;
    }


    /**
     * @param int  $profiles_id
     * @param bool $openform
     * @param bool $closeform
     *
     * @return bool|void
     */
    public function showForm($profiles_id = 0, $openform = true, $closeform = true)
    {
        echo "<div class='firstbloc'>";
        if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform) {
            $profile = new Profile();
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        $rights = $this->getAllRights();

        $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => _n('Meta-Demand', 'Meta-Demands', 2, 'metademands')]);

        echo "<table class='tab_cadre_fixehov'>";

        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='6'>" . _n('Meta-Demand', 'Meta-Demands', 2, 'metademands') . "</th>";
        echo "</th></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_createmeta']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Create a meta-demand', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_createmeta[1_0]',
            'checked' => $effective_rights['plugin_metademands_createmeta']]);
        echo "</td></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_validatemeta']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Validate a meta-demand', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_validatemeta[1_0]',
            'checked' => $effective_rights['plugin_metademands_validatemeta']]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='6'>" . ucfirst(_n('form', 'forms', 2, 'metademands')) . "</th>";
        echo "</th></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_fillform']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Fill out a form', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_fillform[1_0]',
            'checked' => $effective_rights['plugin_metademands_fillform']]);
        echo "</td></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_cancelform']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Cancel /delete a form', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_cancelform[1_0]',
            'checked' => $effective_rights['plugin_metademands_cancelform']]);
        echo "</td></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_publicforms']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Define a private / public model', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_publicforms[1_0]',
            'checked' => $effective_rights['plugin_metademands_publicforms']]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Right to update a meta-demand form from the ticket', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_updatemeta']);
        Html::showCheckbox(['name'    => '_plugin_metademands_updatemeta[1_0]',
            'checked' => $effective_rights['plugin_metademands_updatemeta']]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='6'>" . __('Simplified interface') . "</th>";
        echo "</th></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_on_login']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Show form selection on connection and replace the create form', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_on_login[1_0]',
            'checked' => $effective_rights['plugin_metademands_on_login']]);
        echo "</td></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_metademands_in_menu']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Hide button in menu', 'metademands') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_metademands_in_menu[1_0]',
            'checked' => $effective_rights['plugin_metademands_in_menu']]);
        echo "</td></tr>\n";
        echo "</table>";

        if ($canedit
            && $closeform) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";

        $this->showLegend();
    }

    /**
     * @param bool $all
     * @param int  $profiles_id
     *
     * @return array
     */
    public static function getAllRights($all = false)
    {
        $rights = [
            ['itemtype' => 'PluginMetademandsMetademand',
                'label'    => _n('Meta-Demand', 'Meta-Demands', 2, 'metademands'),
                'field'    => 'plugin_metademands',
            ],
        ];

        $rights[] = ['itemtype' => 'PluginMetademandsInterticketfollowup',
            'label'    => _n('Inter ticket followup', 'Inter ticket followups', 2, 'metademands'),
            'field'    => 'plugin_metademands_followup',
        ];

        if ($all) {
            $rights[] = ['itemtype' => 'PluginMetademandsWizard',
                'label'    => __('Create a meta-demand', 'metademands'),
                'field'    => 'plugin_metademands_createmeta',
            ];
            $rights[] = ['itemtype' => 'PluginMetademandsWizard',
                'label'    => __('Validate a meta-demand', 'metademands'),
                'field'    => 'plugin_metademands_validatemeta',
            ];
            $rights[] = ['itemtype' => 'PluginMetademandsStepform',
                'label'    => __('Fill out a form', 'metademands'),
                'field'    => 'plugin_metademands_fillform',
            ];
            $rights[] = ['itemtype' => 'PluginMetademandsStepform',
                'label'    => __('Cancel / delete a form', 'metademands'),
                'field'    => 'plugin_metademands_cancelform',
            ];
            $rights[] = ['itemtype' => 'PluginMetademandsForm',
                'label'    => __('Define a private / public model', 'metademands'),
                'field'    => 'plugin_metademands_publicforms',
            ];
            $rights[] = ['itemtype' => 'PluginMetademandsWizard',
                'label'    => __('Right to update a meta-demand form from the ticket', 'metademands'),
                'field'    => 'plugin_metademands_updatemeta',
            ];
            $rights[] = ['itemtype'  => 'PluginMetademandsMetademand',
                'label'     => __('Show form selection on connection and replace the create form', 'metademands'),
                'field'     => 'plugin_metademands_on_login',
            ];
            $rights[] = ['itemtype'  => 'PluginMetademandsMetademand',
                'label'     => __('Hide button in menu', 'metademands'),
                'field'     => 'plugin_metademands_in_menu',
            ];
        }

        return $rights;
    }


    /**
     * @param string $interface
     *
     * @return array
     */
    public static function getItemRights($interface = 'central')
    {
        if ($interface == 'central') {
            $values = [CREATE => __('Create'),
                READ   => __('Read'),
                UPDATE => __('Update'),
                PURGE  => ['short' => __('Purge'),
                    'long'  => _x('button', 'Delete permanently')]];
        } else {
            $values = [READ => __('Read')];
        }

        return $values;
    }


    /**
     * Init profiles
     *
     * @param $old_right
     *
     * @return int
     */

    public static function translateARight($old_right)
    {
        switch ($old_right) {
            case 'r':
                return READ;
            case 'w':
                return ALLSTANDARDRIGHT;
            case '0':
            case '1':
                return $old_right;

            default:
                return 0;
        }
    }

    public static function migrateOneProfile($profiles_id)
    {
        global $DB;
        //Cannot launch migration if there's nothing to migrate...
        if (!$DB->tableExists('glpi_plugin_metademands_profiles')) {
            return true;
        }

        $it = $DB->request([
            'FROM' => 'glpi_plugin_metademands_profiles',
            'WHERE' => ['profiles_id' => $profiles_id],
        ]);
        foreach ($it as $profile_data) {
            $matching       = ['metademands' => 'plugin_metademands'];
            $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
            foreach ($matching as $old => $new) {
                if (!isset($current_rights[$old])) {
                    $DB->update('glpi_profilerights', ['rights' => self::translateARight($profile_data[$old])], [
                        'name'        => $new,
                        'profiles_id' => $profiles_id,
                    ]);
                }
            }
        }
        return;
    }


    /**
     * Initialize profiles, and migrate it necessary
     */
    public static function initProfile()
    {
        global $DB;
        $profile = new self();

        $dbu = new DbUtils();
        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights(true) as $data) {
            if ($dbu->countElementsInTable(
                "glpi_profilerights",
                ["name" => $data['field']]
            ) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_profiles',
        ]);
        foreach ($it as $prof) {
            self::migrateOneProfile($prof['id']);
        }

        $it = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
                'name' => ['LIKE', '%plugin_metademands%'],
            ],
        ]);
        foreach ($it as $prof) {
            if (isset($_SESSION['glpiactiveprofile'])) {
                $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
            }
        }
    }

    /**
     * @param $profiles_id
     */
    public static function createFirstAccess($profiles_id)
    {
        $rights = ['plugin_metademands'            => ALLSTANDARDRIGHT,
            'plugin_metademands_followup'   => ALLSTANDARDRIGHT,
            'plugin_metademands_updatemeta' => 1,
            'plugin_metademands_on_login' => 0,
            'plugin_metademands_in_menu' => 0,
            'plugin_metademands_createmeta' => 1,
            'plugin_metademands_validatemeta' => 1,
            'plugin_metademands_fillform' => 0,
            'plugin_metademands_cancelform' => 0,
            'plugin_metademands_publicforms' => 0];

        self::addDefaultProfileInfos(
            $profiles_id,
            $rights,
            true
        );
    }

    public static function removeRightsFromSession()
    {
        foreach (self::getAllRights(true) as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }

    public static function removeRightsFromDB()
    {
        $plugprof = new ProfileRight();
        foreach (self::getAllRights(true) as $right) {
            $plugprof->deleteByCriteria(['name' => $right['field']]);
        }
    }

    /**
     * @param      $profiles_id
     * @param      $rights
     * @param bool $drop_existing
     */
    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        $dbu          = new DbUtils();
        $profileRight = new ProfileRight();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable(
                'glpi_profilerights',
                ["profiles_id" => $profiles_id, "name" => $right]
            ) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable(
                'glpi_profilerights',
                ["profiles_id" => $profiles_id, "name" => $right]
            )) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }
}
