<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2026 by the Metademands Development Team.

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

namespace GlpiPlugin\Metademands;

use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Html;
use ProfileRight;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Profile
 */
class Profile extends \Profile
{
    public static $rightname = "profile";
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
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(Metademand::getTypeName(2));
        }
        return '';
    }


    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if (!$item instanceof \Profile || !self::canView()) {
            return false;
        }

        $profile = new \Profile();
        $profile->getFromDB($item->getID());

        $rights = self::getAllRights($profile->getField('interface'));

        $twig = TemplateRenderer::getInstance();
        $twig->display('@metademands/profile.html.twig', [
            'id'      => $item->getID(),
            'profile' => $profile,
            'title'   => self::getTypeName(Session::getPluralNumber()),
            'rights'  => $rights,
        ]);

        return true;
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

    /**
     * @param bool $all
     * @param int  $profiles_id
     *
     * @return array
     */
    public static function getAllRights($all = false)
    {
        $rights = [
            ['itemtype' => Metademand::class,
                'label'    => _n('Meta-Demand', 'Meta-Demands', 2, 'metademands'),
                'field'    => 'plugin_metademands',
            ],
        ];

        $rights[] = ['itemtype' => Interticketfollowup::class,
            'label'    => _n('Inter ticket followup', 'Inter ticket followups', 2, 'metademands'),
            'field'    => 'plugin_metademands_followup',
        ];

        if ($all) {
            $rights[] = ['itemtype' => Wizard::class,
                'label'    => __('Create a meta-demand', 'metademands'),
                'field'    => 'plugin_metademands_createmeta',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype' => Wizard::class,
                'label'    => __('Validate a meta-demand', 'metademands'),
                'field'    => 'plugin_metademands_validatemeta',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype' => Stepform::class,
                'label'    => __('Fill out a form', 'metademands'),
                'field'    => 'plugin_metademands_fillform',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype' => Stepform::class,
                'label'    => __('Cancel / delete a form', 'metademands'),
                'field'    => 'plugin_metademands_cancelform',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype' => Form::class,
                'label'    => __('Define a private / public model', 'metademands'),
                'field'    => 'plugin_metademands_publicforms',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype' => Wizard::class,
                'label'    => __('Right to update a meta-demand form from the ticket', 'metademands'),
                'field'    => 'plugin_metademands_updatemeta',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype'  => Metademand::class,
                'label'     => __('Show form selection on connection and replace the create form', 'metademands'),
                'field'     => 'plugin_metademands_on_login',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
            $rights[] = ['itemtype'  => Metademand::class,
                'label'     => __('Hide button in menu', 'metademands'),
                'field'     => 'plugin_metademands_in_menu',
                'rights' => [
                    READ  => __s('Read'),
                ],
            ];
        }

        return $rights;
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
