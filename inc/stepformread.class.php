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
 * Class PluginMetademandsStepform
 */
class PluginMetademandsStepformread extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

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
        return _n('Step a metademands form', 'Steps a metademands form', $nb, 'metademands');
    }

    public static function getWaitingFormsByMaker()
    {
        $stepform   = new PluginMetademandsStepform();
        $stepformActors = new PluginMetademandsStepform_Actor();

        $waitingForms = [];
        $stepforms  = [];

        $stepforms  = $stepform->find();

        foreach ($stepforms as $id => $form) {
            if(Session::getLoginUserID() == $form['users_id']) {
                $waitingForms[$id] = $form;
            }

            foreach ($stepformActors->find(['plugin_metademands_stepforms_id' => $id]) as $idformactor => $formActor) {
                if(Session::getLoginUserID() == $formActor['users_id']) {
                    $waitingForms[$id] = $form;
                }
            }
        }
        return $waitingForms;
    }

    public static function getIcon()
    {
        return "ti ti-eye";
    }

    public function showWaitingFormStandardViewReadOnly()
    {
        $meta = new PluginMetademandsMetademand();
        $stepforms = self::getWaitingFormsByMaker();

        echo "<div class='left'>";

        if (!empty($stepforms)) {
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th>" . __("ID") . "</th>";
            echo "<th>" . __('Name') . "</th>";
            echo "<th>" . __('Step', 'metademands') . "</th>";
            echo "<th>" . __('Creation date') . "</th>";
            echo "<th>" . __('Group in charge of the next step', 'metademands') . "</th>";
            echo "<th>" . __('User in charge of the next step', 'metademands') . "</th>";
            echo "</tr>";


            foreach ($stepforms as $data) {

                $meta->getFromDB($data['plugin_metademands_metademands_id']);

                echo "<td>";
                echo $data['id'];
                echo "</td>";
                echo "<td>";
                echo $meta->getName();
                echo "</td>";
                echo "<td>";
                echo $data['block_id'];
                echo "</td>";
                echo "<td>";
                echo Html::convDateTime($data['date']);
                echo "</td>";
                echo "<td>";
                echo $data['groups_id_dest'] > 0 ? Group::getFriendlyNameById($data['groups_id_dest']) : '';
                echo "</td>";
                echo "<td>";
                echo $data['users_id_dest'] > 0 ? getUserName($data['users_id_dest'], 1) : '';
                echo "</td>";


                echo "</tr>";
            }
            echo "</table>";

        } else {
            echo "<br><div class='alert alert-important alert-info center'>";
            echo __("No existing forms founded", 'metademands');
            echo "</div>";
        }
        echo "</div>";
    }

    public function showWaitingFormReadOnly()
    {
        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.css.php");

        $stepforms = self::getWaitingFormsByMaker();

        if (!empty($stepforms)) {
            echo "<div class=\"row\">";
            echo "<div class=\"col-md-12\">";
            echo "<h4><div class='alert alert-dark' role='alert'>";
            $icon = "fa-share-alt";
            if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                $icon = $meta->fields['icon'];
            }
            $cnt = count($stepforms);
            echo "<i class='fa-2x fas $icon'></i>&nbsp;";
            echo _n('Form in progress', 'Forms in progress', $cnt, 'metademands');
            echo "</div></h4></div></div>";

            echo "<div id='listmeta'>";

            foreach ($stepforms as $id => $name) {
                $meta = new PluginMetademandsMetademand();
                if ($meta->getFromDB($name['plugin_metademands_metademands_id'])) {
                    $metaID = $name['plugin_metademands_metademands_id'];
                    $block_id = $name['block_id'];
                    echo '<div class="btnsc-normal" style="min-height: 300px" >';
                    $fasize = "fa-4x";
                    echo "<div class='center'>";
                    $icon = "fa-share-alt";
                    if (!empty($meta->fields['icon'])) {
                        $icon = $meta->fields['icon'];
                    }
                    echo "<i class='sc-colorform bt-interface fa-menu-md fas $icon $fasize' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>";//$style
                    echo "</div>";

                    echo "<br><p> <span class='sc-colorform'>";
                    if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
                        echo $meta->getName();
                    } else {
                        echo $n;
                    }

                    echo "</span><br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                    printf(__('Created on %s'), Html::convDate($name['date']));
                    echo "</span></em>";

                    echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                    echo __('Step', 'metademands');
                    echo $block_id;
                    echo "</span></em>";
                    if ($name['groups_id_dest'] > 0) {
                        echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                        echo __('Group in charge of the next step', 'metademands');
                        echo "<br>";
                        echo Group::getFriendlyNameById($name['groups_id_dest']);
                        echo "</span></em>";
                    }
                    if ($name['users_id_dest'] > 0) {
                        echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                        echo __('User in charge of the next step', 'metademands');
                        echo "<br>";
                        echo getUserName($name['users_id_dest']);
                        echo "</span></em>";
                    }


                    ;
                    //TODO Change to new right
                    if (Session::haveRight("plugin_metademands_cancelform", READ)) {
                        $target = PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.form.php";
                        echo "<br><span style='color:darkred'>";
                        Html::showSimpleForm(
                            $target,
                            'delete_form_from_list',
                            _sx('button', 'Delete form', 'metademands'),
                            ['plugin_metademands_stepforms_id' => $id],
                            'fa-trash-alt fa-1x'
                        );
                        echo "</span>";
                    }
                    echo "</p></div>";
                }
            }
            echo "</div>";
        } else {
            echo "<br><div class='alert alert-important alert-info center'>";
            echo __("No existing forms founded", 'metademands');
            echo "</div>";
        }


    }

}
