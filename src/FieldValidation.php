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

use CommonDBTM;
use CommonITILValidation;
use DBConnection;
use Html;
use ITILFollowup;
use Migration;
use Session;
use User;

/**
 * Child tickets held until a GLPI validation of the parent ticket is answered.
 *
 * A field option may designate its validator through a User field of the form
 * (FieldOption.validation_fields_id): when the option value is selected, the
 * child tickets of the tasks of this value are not created with the others
 * (Metademand::holdTasksForFieldValidation()). They are kept here, a native
 * TicketValidation is requested on the parent ticket, and the answer of the
 * validator creates them (accepted) or drops them (refused, request deleted).
 */
class FieldValidation extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    public const WAITING  = 0;
    public const CREATED  = 1;
    public const REFUSED  = 2;

    public static function getTypeName($nb = 0)
    {
        return _n('Held child ticket', 'Held child tickets', $nb, 'metademands');
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table             = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id`                                int {$default_key_sign} NOT NULL auto_increment,
                        `tickets_id`                        int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `ticketvalidations_id`              int {$default_key_sign} NOT NULL DEFAULT '0',
                        `users_id_validate`                 int {$default_key_sign} NOT NULL DEFAULT '0',
                        `status`                            tinyint      NOT NULL DEFAULT '0',
                        `tasklevel`                         int          NOT NULL DEFAULT '1',
                        `as_tasks`                          tinyint      NOT NULL DEFAULT '0',
                        `tickets_to_create`                 longtext,
                        `parent_fields`                     longtext,
                        `input_field`                       longtext,
                        `input_field_main`                  longtext,
                        `date_creation`                     timestamp    NULL DEFAULT NULL,
                        `date_mod`                          timestamp    NULL DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `tickets_id` (`tickets_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
                        KEY `ticketvalidations_id` (`ticketvalidations_id`),
                        KEY `users_id_validate` (`users_id_validate`),
                        KEY `status` (`status`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        // Tasks of the parent ticket instead of child tickets: chosen by the validator of
        // the metademand (MetademandValidation::CREATE_TASKS)
        if (!$DB->fieldExists($table, 'as_tasks')) {
            $migration->addField($table, 'as_tasks', "tinyint NOT NULL DEFAULT '0'", ['after' => 'tasklevel']);
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * User fields of a metademand that may designate the validator: a single user
     * picker (dropdown_object on User), so that one validation is requested.
     *
     * @return array<int, string> field id => "rank - name"
     */
    public static function getValidatorFieldChoices(int $metademands_id): array
    {
        $choices = [];
        $field   = new Field();
        foreach ($field->find([
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'                              => 'dropdown_object',
            'item'                              => User::getType(),
            // Not in the wizard: nobody to choose
            'is_active'                         => 1,
        ], ['rank', 'order']) as $data) {
            $choices[(int) $data['id']] = $data['rank'] . ' - ' . urldecode(html_entity_decode($data['name']));
        }

        return $choices;
    }

    /**
     * Whether a field may designate the validator of an option of this metademand.
     */
    public static function isValidatorField(int $fields_id, int $metademands_id): bool
    {
        return isset(self::getValidatorFieldChoices($metademands_id)[$fields_id]);
    }

    /**
     * Tasks whose child tickets wait for a validation, given the values saved for the
     * parent ticket.
     *
     * An option carrying a validator field (FieldOption.validation_fields_id) holds the
     * tasks of every option of its value: same field, same value to check. The value is
     * tested as Ticket_Field::checkTicketCreation() tests it for the creation of the
     * tasks, so a held task is one that would have been created.
     *
     * @return array<int, int> tasks id => validator field id
     */
    public static function getHeldTaskFields(int $metademands_id, int $tickets_id): array
    {
        // The options of an inactive field hold nothing
        $fields = (new Field())->find(['plugin_metademands_metademands_id' => $metademands_id, 'is_active' => 1]);
        if ($fields === []) {
            return [];
        }

        $option  = new FieldOption();
        $options = $option->find(['plugin_metademands_fields_id' => array_keys($fields)]);
        // A validator field made inactive since: nobody chosen, nothing held
        $validator_fields = self::getValidatorFieldChoices($metademands_id);

        $held = [];
        foreach ($options as $data) {
            $validation_fields_id = (int) ($data['validation_fields_id'] ?? 0);
            if ($validation_fields_id <= 0 || !isset($validator_fields[$validation_fields_id])) {
                continue;
            }

            $fields_id = (int) $data['plugin_metademands_fields_id'];
            $saved     = new Ticket_Field();
            $value     = null;
            if ($saved->getFromDBByCrit([
                'tickets_id'                   => $tickets_id,
                'plugin_metademands_fields_id' => $fields_id,
            ])) {
                $value = FieldParameter::_unserialize($saved->fields['value']) ?? $saved->fields['value'];
            }
            if (!Ticket_Field::isCheckValueOKFieldsLinks($value, $data['check_value'], $fields[$fields_id]['type'])) {
                continue;
            }

            foreach ($options as $same_value) {
                $tasks_id = (int) $same_value['plugin_metademands_tasks_id'];
                if ($tasks_id > 0
                    && (int) $same_value['plugin_metademands_fields_id'] === $fields_id
                    && $same_value['check_value'] == $data['check_value']
                    && ($same_value['check_type_value'] ?? 1) == ($data['check_type_value'] ?? 1)
                    && (string) ($same_value['check_value_regex'] ?? '') === (string) ($data['check_value_regex'] ?? '')
                    && !isset($held[$tasks_id])) {
                    $held[$tasks_id] = $validation_fields_id;
                }
            }
        }

        return $held;
    }

    /**
     * Child tickets of a parent ticket held by a validation, still waiting or refused,
     * for the followup of the demand (Metademand::showPluginForTicket()): the accepted
     * ones are real child tickets listed with the others.
     *
     * @return array<int, array{task_name: string, validator: string, date: string, is_waiting: bool, status_name: string}>
     */
    public static function getHeldRows(int $tickets_id): array
    {
        $rows = [];
        foreach ((new self())->find([
            'tickets_id' => $tickets_id,
            'status'     => [self::WAITING, self::REFUSED],
        ], ['id']) as $data) {
            $is_waiting = (int) $data['status'] === self::WAITING;
            $tasks      = json_decode($data['tickets_to_create'] ?? '', true);
            if (!is_array($tasks)) {
                continue;
            }

            foreach ($tasks as $task) {
                // The sub-tasks only follow their parent child ticket
                if ((int) ($task['level'] ?? $data['tasklevel']) !== (int) $data['tasklevel']) {
                    continue;
                }
                $rows[] = [
                    'task_name'   => Metademand::$SON_PREFIX . ($task['tickettasks_name'] ?? ''),
                    'validator'   => getUserName((int) $data['users_id_validate']),
                    'date'        => Html::convDateTime($data['date_creation']),
                    'is_waiting'  => $is_waiting,
                    'status_name' => CommonITILValidation::getStatus(
                        $is_waiting ? CommonITILValidation::WAITING : CommonITILValidation::REFUSED,
                    ),
                ];
            }
        }

        return $rows;
    }

    /**
     * Whether child tickets of this parent ticket still wait for a validation.
     */
    public static function hasWaiting(int $tickets_id): bool
    {
        return countElementsInTable(self::getTable(), [
            'tickets_id' => $tickets_id,
            'status'     => self::WAITING,
        ]) > 0;
    }

    /**
     * Request the validation of held child tickets on the parent ticket.
     *
     * @param int   $tickets_id        parent ticket
     * @param int   $metademands_id    metademand creating the children
     * @param int   $users_id_validate validator
     * @param array $tasks             task rows as given to Metademand::createSonsTickets()
     * @param array $parent_fields     parent fields as given to Metademand::createSonsTickets()
     * @param int   $tasklevel         level of the tasks
     * @param array $input_field       values of the "fields" plugin for the children
     * @param array $input_field_main  values of the "fields" plugin for the children
     * @param bool  $as_tasks          create tasks of the parent ticket instead of child tickets
     *
     * @return bool false when the validation could not be requested
     */
    public static function hold(
        int $tickets_id,
        int $metademands_id,
        int $users_id_validate,
        array $tasks,
        array $parent_fields,
        int $tasklevel,
        array $input_field,
        array $input_field_main,
        bool $as_tasks = false,
    ): bool {
        $validation = new \TicketValidation();
        $validations_id = $validation->add([
            'tickets_id'         => $tickets_id,
            'itemtype_target'    => User::class,
            'items_id_target'    => $users_id_validate,
            'comment_submission' => sprintf(
                htmlescape(__('Validation of the child tickets: %s', 'metademands')),
                htmlescape(self::getTaskNames($tasks, $tasklevel)),
            ),
        ]);
        if (!$validations_id) {
            trigger_error(sprintf(
                'metademands: the validation of the held child tickets of ticket %d could not be requested',
                $tickets_id,
            ), E_USER_WARNING);
            return false;
        }

        $held = new self();
        if (!$held->add([
            'tickets_id'                        => $tickets_id,
            'plugin_metademands_metademands_id' => $metademands_id,
            'ticketvalidations_id'              => $validations_id,
            'users_id_validate'                 => $users_id_validate,
            'status'                            => self::WAITING,
            'tasklevel'                         => $tasklevel,
            'as_tasks'                          => (int) $as_tasks,
            'tickets_to_create'                 => json_encode($tasks, JSON_INVALID_UTF8_SUBSTITUTE),
            'parent_fields'                     => json_encode($parent_fields, JSON_INVALID_UTF8_SUBSTITUTE),
            'input_field'                       => json_encode($input_field, JSON_INVALID_UTF8_SUBSTITUTE),
            'input_field_main'                  => json_encode($input_field_main, JSON_INVALID_UTF8_SUBSTITUTE),
        ])) {
            // Nothing would answer it: the children are created at once instead
            $validation->delete(['id' => $validations_id], true);
            return false;
        }

        return true;
    }

    /**
     * Names of the child tickets of the level held, for the messages.
     */
    private static function getTaskNames(array $tasks, int $tasklevel): string
    {
        $names = [];
        foreach ($tasks as $task) {
            if ((int) ($task['level'] ?? $tasklevel) === $tasklevel && !empty($task['tickettasks_name'])) {
                $names[] = $task['tickettasks_name'];
            }
        }

        return implode(', ', $names);
    }

    /**
     * ITEM_UPDATE hook of TicketValidation: create or drop the held child tickets.
     */
    public static function afterTicketValidationUpdate(\TicketValidation $validation): void
    {
        if (!in_array('status', $validation->updates, true)) {
            return;
        }

        $status = (int) $validation->fields['status'];
        if ($status !== CommonITILValidation::ACCEPTED && $status !== CommonITILValidation::REFUSED) {
            return;
        }

        $held = new self();
        if (!$held->getFromDBByCrit([
            'ticketvalidations_id' => $validation->getID(),
            'status'               => self::WAITING,
        ])) {
            return;
        }

        if ($status === CommonITILValidation::ACCEPTED) {
            $held->createHeldTickets();
        } else {
            $held->refuse($validation);
        }
    }

    /**
     * Create the held child tickets.
     */
    public function createHeldTickets(): bool
    {
        $tasks            = json_decode($this->fields['tickets_to_create'] ?? '', true);
        $parent_fields    = json_decode($this->fields['parent_fields'] ?? '', true);
        $input_field      = json_decode($this->fields['input_field'] ?? '', true);
        $input_field_main = json_decode($this->fields['input_field_main'] ?? '', true);

        // Marked first: the creation updates the parent ticket, which must not see
        // these children as still waiting.
        $this->update([
            'id'     => $this->getID(),
            'status' => self::CREATED,
        ]);

        if (!is_array($tasks) || $tasks === []) {
            return true;
        }

        $tickets_id = (int) $this->fields['tickets_id'];

        $metademand = new Metademand();
        $parent     = new \Ticket();
        if (!$metademand->getFromDB($this->fields['plugin_metademands_metademands_id'])
            || !$parent->getFromDB($tickets_id)) {
            trigger_error(sprintf(
                'metademands: the held child tickets of ticket %d cannot be created, its metademand or the ticket is gone',
                $tickets_id,
            ), E_USER_WARNING);
            return false;
        }

        // Created during the session of the validator: keep the submitter of the demand
        // as the writer of the children, as when they are created at once
        if (!is_array($parent_fields)) {
            $parent_fields = [];
        }
        if (empty($parent_fields['users_id_recipient'])) {
            $parent_fields['users_id_recipient'] = $parent->fields['users_id_recipient'];
        }

        if ((int) $metademand->fields['force_create_tasks'] === 1 || (int) ($this->fields['as_tasks'] ?? 0) === 1) {
            $ok = true;
            foreach ($tasks as $task) {
                $ticket_task = new \TicketTask();
                if (!$ticket_task->add([
                    'tickets_id'     => $tickets_id,
                    'content'        => $task['tickettasks_name'] . ' ' . $task['content'],
                    'groups_id_tech' => $task['groups_id_assign'] ?? 0,
                    'users_id_tech'  => $task['users_id_assign'] ?? 0,
                ])) {
                    $ok = false;
                }
            }
            return $ok;
        }

        return (bool) Metademand::createSonsTickets(
            $metademand->getID(),
            $tickets_id,
            $parent_fields,
            $tickets_id,
            $tasks,
            (int) $this->fields['tasklevel'],
            is_array($input_field) ? $input_field : [],
            is_array($input_field_main) ? $input_field_main : [],
        );
    }

    /**
     * ITEM_PURGE hook of TicketValidation: a validation request deleted before its answer
     * approves nothing, so its held child tickets are dropped as on a refusal. Creating
     * them would let whoever may delete the request (its own submitter among others)
     * bypass the validator.
     */
    public static function afterTicketValidationPurge(\TicketValidation $validation): void
    {
        $held = new self();
        if (!$held->getFromDBByCrit([
            'ticketvalidations_id' => $validation->getID(),
            'status'               => self::WAITING,
        ])) {
            return;
        }

        $held->drop(sprintf(
            htmlescape(__('Validation request deleted, the held child tickets will not be created: %s', 'metademands')),
            htmlescape(self::getTaskNames(
                $held->getHeldTasks(),
                (int) $held->fields['tasklevel'],
            )),
        ));
    }

    /**
     * Drop the held child tickets and trace the refusal on the parent ticket.
     */
    public function refuse(\TicketValidation $validation): void
    {
        $users_id = (int) $validation->fields['users_id_validate'] ?: (int) Session::getLoginUserID();

        $content = sprintf(
            htmlescape(__('Child tickets refused by %1$s, they will not be created: %2$s', 'metademands')),
            htmlescape(getUserName($users_id)),
            htmlescape(self::getTaskNames($this->getHeldTasks(), (int) $this->fields['tasklevel'])),
        );
        // Rich text typed by the validator
        $comment = trim((string) ($validation->fields['comment_validation'] ?? ''));
        if ($comment !== '') {
            $content .= '<br>' . sprintf(htmlescape(__('Reason: %s', 'metademands')), $comment);
        }

        $this->drop($content);
    }

    /**
     * Tasks held, as stored.
     */
    private function getHeldTasks(): array
    {
        $tasks = json_decode($this->fields['tickets_to_create'] ?? '', true);

        return is_array($tasks) ? $tasks : [];
    }

    /**
     * Mark the held child tickets as dropped and explain it on the parent ticket.
     *
     * @param string $content HTML of the followup
     */
    private function drop(string $content): void
    {
        $this->update([
            'id'     => $this->getID(),
            'status' => self::REFUSED,
        ]);

        $followup = new ITILFollowup();
        $followup->add([
            'itemtype'   => \Ticket::class,
            'items_id'   => (int) $this->fields['tickets_id'],
            'content'    => $content,
            'is_private' => 0,
            // The native answer notification already informs the requester
            '_disablenotif' => true,
        ]);

        // The parent ticket may now be closable: no child is waiting any more
        $ticket = new \Ticket();
        if ($ticket->getFromDB((int) $this->fields['tickets_id'])) {
            Ticket_Metademand::changeMetademandGlobalStatus($ticket);
        }
    }
}
