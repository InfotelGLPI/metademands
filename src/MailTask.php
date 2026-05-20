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

use CommonDBChild;
use CommonITILActor;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use GLPIMailer;
use GLPINetwork;
use Html;
use ITILCategory;
use Migration;
use Session;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class MailTask
 */
class MailTask extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = Task::class;
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

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `content`                     text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `users_id_recipient`          int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `groups_id_recipient`         int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `plugin_metademands_tasks_id` int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `itilcategories_id`           int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `email`                       varchar(255)                    DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
                        KEY `users_id_recipient` (`users_id_recipient`),
                        KEY `groups_id_recipient` (`groups_id_recipient`),
                        KEY `itilcategories_id` (`itilcategories_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        if ($DB->fieldExists($table, "itilcategories_id")) {
            $migration->changeField($table, 'itilcategories_id', 'itilcategories_id', " int {$default_key_sign} NOT NULL DEFAULT '0'");
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    public static function showMailTaskForm($metademands_id, $tasktype, $input = [])
    {
        $metademands = new Metademand();
        $metademands->getFromDB($metademands_id);

        $values = [
            'mailtask_id'                    => 0,
            'itilcategories_id'              => 0,
            'type'                           => \Ticket::DEMAND_TYPE,
            'parent_tasks_id'                => 0,
            'plugin_metademands_tasks_id'    => 0,
            'content'                        => ' ',
            'name'                           => ' ',
            'block_use'                      => 1,
            'useBlock'                       => 1,
            'block_parent_ticket_resolution' => 0,
            'formatastable'                  => 1,
            'entities_id'                    => 0,
            'is_recursive'                   => 0,
        ];
        foreach ($input as $key => $val) {
            $values[$key] = $val;
        }

        if (isset($_SESSION["metademandsHelpdeskSaved"])) {
            foreach ($_SESSION["metademandsHelpdeskSaved"] as $name => $value) {
                $values[$name] = $value;
            }
            unset($_SESSION["metademandsHelpdeskSaved"]);
        }

        if ($values['block_use'] != null && !is_array($values['block_use'])) {
            $values['block_use'] = json_decode($values['block_use'], true);
        }
        if ($values['block_use'] == null) {
            $values['block_use'] = [];
        }

        $values['name'] = stripslashes($values['name']);
        $values['type'] = $metademands->getField("type");

        $field  = new Field();
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

        ob_start();
        \Dropdown::showYesNo('useBlock', $values['useBlock']);
        $use_block_html = ob_get_clean();

        ob_start();
        \Dropdown::showFromArray('block_use', $blocks, [
            'values'   => $values['block_use'],
            'width'    => '100%',
            'multiple' => true,
            'entity'   => $_SESSION['glpiactiveentities'],
        ]);
        $block_use_html = ob_get_clean();

        $ticket = new \Ticket();
        ob_start();
        User::dropdown([
            'name'   => 'users_id_recipient',
            'value'  => $values['users_id_recipient'] ?? 0,
            'entity' => $metademands->fields["entities_id"],
            'right'  => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER),
        ]);
        $user_recipient_html = ob_get_clean();

        ob_start();
        \Dropdown::show('Group', [
            'name'      => 'groups_id_recipient',
            'value'     => $values['groups_id_recipient'] ?? 0,
            'entity'    => $metademands->fields["entities_id"],
            'condition' => ['is_requester' => 1],
        ]);
        $group_recipient_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/mailtask_form.html.twig', [
            'use_block_html'       => $use_block_html,
            'block_use_html'       => $block_use_html,
            'user_recipient_html'  => $user_recipient_html,
            'group_recipient_html' => $group_recipient_html,
            'name'                 => $values['name'] ?? '',
            'content'              => stripslashes($values['content'] ?? ''),
            'mailtask_id'          => $values['mailtask_id'],
        ]);
    }


    public static function sendMail($title, $recipient, $body)
    {
        global $CFG_GLPI;

        $transport = Transport::fromDsn(GLPIMailer::buildDsn(true));

        $mmail = new GLPIMailer($transport);
        $mail = $mmail->getEmail();

        $mail->getHeaders()->addTextHeader("Auto-Submitted", "auto-generated");
        // For exchange
        $mail->getHeaders()->addTextHeader("X-Auto-Response-Suppress", "OOF, DR, NDR, RN, NRN");

        $mail->from(new Address($CFG_GLPI["from_email"],  $CFG_GLPI["from_email_name"]));

        if (is_array($recipient)) {
            foreach ($recipient as $r) {
                if (empty($r['name'])) {
                    $r['name'] = $r['email'];
                }
                $mail->to(new Address($r['email'], $r['name']));
            }
        } else {
            $mail->to(new Address($recipient, $recipient));
        }

        $mail->subject($title);

        $mail->html($body);

        if (!$mmail->Send()) {
            Session::addMessageAfterRedirect(
                __('Fail to send email', 'metademands'),
                false,
                ERROR
            );
            return false;
        } else {
            Session::addMessageAfterRedirect(__('Email sent', 'metademands'));
            return true;
        }
    }

}
