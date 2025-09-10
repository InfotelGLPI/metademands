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
 * Class PluginMetademandsMailTask
 */
class PluginMetademandsMailTask extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsTask';
    public static $items_id = 'plugin_metademands_tasks_id';

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
        return __('Task creation', 'metademands');
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
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    public static function showMailTaskForm($metademands_id, $tasktype, $input = [])
    {

        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($metademands_id);

        // Default values
        $values = [
            'mailtask_id' => 0,
            'itilcategories_id' => 0,
            'type' => Ticket::DEMAND_TYPE,
            'parent_tasks_id' => 0,
            'plugin_metademands_tasks_id' => 0,
            'content' => '',
            'name' => '',
            'block_use' => 1,
            'useBlock' => 1,
            'block_parent_ticket_resolution' => 0,
            'formatastable' => 1,
            'entities_id' => 0,
            'is_recursive' => 0];
        // Init values
        foreach ($input as $key => $val) {
            $values[$key] = $val;
        }

        //      $values['block_use'] = json_decode($values['block_use']);

        // Restore saved value or override with page parameter
        if (isset($_SESSION["metademandsHelpdeskSaved"])) {
            foreach ($_SESSION["metademandsHelpdeskSaved"] as $name => $value) {
                $values[$name] = $value;
            }
            unset($_SESSION["metademandsHelpdeskSaved"]);
        }

        if ($values['block_use'] != null
            && !is_array($values['block_use'])) {
            $values['block_use'] = json_decode($values['block_use'], true);
        }
        if ($values['block_use'] == null) {
            $values['block_use'] = [];
        }

        // Clean text fields
        $values['name'] = stripslashes($values['name']);
        $values['type'] = $metademands->getField("type");

        // In percent
        $colsize1 = '13';
        $colsize3 = '87';

        echo "<div>";
        echo "<table class='tab_cadre_fixe' id='mainformtable'>";

        echo "<tr class='tab_bg_1'>";

        echo "<th>" . sprintf(__('%1$s'), __('Use block', 'metademands')) . "</th>";
        echo "<td>";

        Dropdown::showYesNo('useBlock', $values['useBlock']);

        echo "</td>";
        echo "<td colspan='4'></td>";

        echo "</tr>";
        echo "<tr class='tab_bg_1'>";

        echo "<th>" . sprintf(__('%1$s'), __('Block to use', 'metademands')) . "</th>";
        echo "<td>";

        $field = new PluginMetademandsField();
        $fields = $field->find(["plugin_metademands_metademands_id" => $metademands_id]);
        $blocks = [];
        foreach ($fields as $f) {
            if (!isset($blocks[$f['rank']])) {
                $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
            }
        }
        ksort($blocks);
        if (!is_array($values['block_use'])) {
            $values['block_use'] = [$values['block_use']];
        }
        Dropdown::showFromArray(
            'block_use',
            $blocks,
            ['values' => $values['block_use'],
                'width' => '100%',
                'multiple' => true,
                'entity' => $_SESSION['glpiactiveentities']]
        );
        echo "</td>";
        echo "<td colspan='4'></td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";

        echo "<th>" . sprintf(__('%1$s'), __('Format the description of the childs ticket as a table', 'metademands')) . "</th>";
        echo "<td>";
        Dropdown::showYesNo('formatastable', $values['formatastable']);
        echo "</td>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Category') . "</th>";
        echo "<td>";
        ITILCategory::dropdown([
            'name' => 'itilcategories_id',
            'value' => $values['itilcategories_id']
        ]);
        echo "</td>";
        echo "<td colspan='4'></td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4'></td>";
        echo "</tr>";
        echo "<td colspan='4'></td>";
        echo "</table>";

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th rowspan='3' width='$colsize1%'>" . __('Actors', 'metademands') . "</th>";
        echo "<th>" . __('Recipient') . "</th>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $ticket = new Ticket();
        // Requester user
        //         echo CommonITILObject::getActorIcon('user', CommonITILActor::REQUESTER) . '&nbsp;';
        User::dropdown(['name' => 'users_id_recipient',
            'value' => isset($values['users_id_recipient']) ? $values['users_id_recipient'] : 0,
            'entity' => $metademands->fields["entities_id"],
            'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER)]);
        echo "</td>";
        echo "<td>";
        echo "</td>";

        echo "<td>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        // Requester Group
        //         echo CommonITILObject::getActorIcon('group', CommonITILActor::REQUESTER) . '&nbsp;';
        Dropdown::show('Group', ['name' => 'groups_id_recipient',
            'value' => isset($values['groups_id_recipient']) ? $values['groups_id_recipient'] : 0,
            'entity' => $metademands->fields["entities_id"],
            'condition' => ['is_requester' => 1]]);
        echo "</td>";
        echo "<td>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "<table class='tab_cadre_fixe'>";
        // Title
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Title') . "</th>";
        echo "<td width='$colsize3%'>";
        $name = isset($values['name']) ? $values['name'] : '';
        echo Html::input('name', ['value' => $name, 'size' => 90]);
        echo "</td>";
        echo "</tr>";

        // Description
        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1%'>" . __('Description') . "</th>";
        echo "<td width='$colsize3%'>";

        $rand = mt_rand();
        $rand_text = mt_rand();
        Html::initEditorSystem("content" . $rand, $rand, true);
        if (!isset($values['content'])) {
            $content = '';
        } else {
            $content = $values['content'];
        }
        echo "<div id='content$rand_text'>";
        Html::textarea(['name' => 'content',
            'value' => stripslashes($content),
            'id' => 'content' . $rand,
            'rows' => 3,
            'enable_richtext' => true,
            'enable_fileupload' => false,
            'enable_images' => false]);
        echo "</div>";
        echo Html::hidden('mailtask_id', ['value' => $values['mailtask_id']]);
        echo "</td>";
        echo "</tr>";
        echo "</table>";

        echo "</div>";
    }


    static function sendMail($title, $recipient, $body)
    {
        global $CFG_GLPI;

        $mmail = new GLPIMailer();

        $mmail->AddCustomHeader("Auto-Submitted: auto-generated");
        // For exchange
        $mmail->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");
        $mmail->SetFrom($CFG_GLPI["from_email"], $CFG_GLPI["from_email_name"], false);
        $mmail->isHTML(true);

        if (is_array($recipient)) {
            foreach ($recipient as $r) {
                if(empty($r['name'])){
                    $r['name'] = $r['email'];
                }
                $mmail->addAddress($r['email'], $r['name']);
            }
        } else {
            $mmail->AddAddress($recipient, $recipient);
        }
        $mmail->Subject = $title;
        $mmail->Body = $body;

        if (!$mmail->Send()) {
            Session::addMessageAfterRedirect(__('Fail to send email', 'metademands'), false,
                ERROR);
            GLPINetwork::addErrorMessageAfterRedirect();
            return false;
        } else {
            Session::addMessageAfterRedirect(__('Email sent', 'metademands'));
            return true;
        }
    }

}
