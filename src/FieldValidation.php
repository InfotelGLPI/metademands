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
 * A field option may designate its validators through User fields of the form, single
 * or multiple (FieldOption.validation_fields_ids): when the option value is selected,
 * the child tickets of the tasks of this value are not created with the others
 * (Metademand::holdTasksForFieldValidation()). They are kept here, and a native
 * TicketValidation is requested on the parent ticket to each user of those fields.
 * They are created once every validator accepted; one refusal, or a request deleted,
 * drops them.
 */
class FieldValidation extends CommonDBTM
{
    public static string $rightname = 'plugin_metademands';

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
                        `ticketvalidations_ids`             longtext,
                        `users_ids_validate`                longtext,
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

        // Several validators: one validation request to each of them, as JSON lists
        if (!$DB->fieldExists($table, 'ticketvalidations_ids')) {
            $migration->addField($table, 'ticketvalidations_ids', 'longtext', ['after' => 'plugin_metademands_metademands_id']);
            $migration->addField($table, 'users_ids_validate', 'longtext', ['after' => 'ticketvalidations_ids']);
            $migration->migrationOneTable($table);
        }
        // The single validator of the first version joins the lists
        if ($DB->fieldExists($table, 'ticketvalidations_id')) {
            foreach ($DB->request(['FROM' => $table]) as $row) {
                $DB->update($table, [
                    'ticketvalidations_ids' => json_encode(array_values(array_filter([(int) $row['ticketvalidations_id']]))),
                    'users_ids_validate'    => json_encode(array_values(array_filter([(int) $row['users_id_validate']]))),
                ], ['id' => $row['id']]);
            }
            $migration->dropKey($table, 'ticketvalidations_id');
            $migration->dropKey($table, 'users_id_validate');
            $migration->dropField($table, 'ticketvalidations_id');
            $migration->dropField($table, 'users_id_validate');
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * User fields of a metademand that may designate validators: single user pickers
     * (dropdown_object) and multiple ones (dropdown_multiple), each user validating.
     *
     * @return array<int, string> field id => "rank - name"
     */
    public static function getValidatorFieldChoices(int $metademands_id): array
    {
        $choices = [];
        $field   = new Field();
        foreach ($field->find([
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'                              => ['dropdown_object', 'dropdown_multiple'],
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
     * An option carrying validator fields (FieldOption.validation_fields_ids) holds the
     * tasks of every option of its value: same field, same value to check. The value is
     * tested as Ticket_Field::checkTicketCreation() tests it for the creation of the
     * tasks, so a held task is one that would have been created.
     *
     * @return array<int, int[]> tasks id => validator field ids, sorted
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
            $validation_fields_ids = array_values(array_filter(
                FieldOption::getValidationFieldsIds($data),
                static fn(int $id) => isset($validator_fields[$id]),
            ));
            if ($validation_fields_ids === []) {
                continue;
            }
            sort($validation_fields_ids);

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
                    $held[$tasks_id] = $validation_fields_ids;
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
            $held = new self();
            $held->fields = $data;
            $is_waiting = (int) $data['status'] === self::WAITING;
            $tasks      = json_decode($data['tickets_to_create'] ?? '', true);
            if (!is_array($tasks)) {
                continue;
            }

            $validators = implode(', ', array_map(
                static fn(int $users_id) => getUserName($users_id),
                self::decodeIds($data['users_ids_validate'] ?? ''),
            ));
            $status_name = CommonITILValidation::getStatus(
                $is_waiting ? CommonITILValidation::WAITING : CommonITILValidation::REFUSED,
            );
            // Several validators: how many already accepted
            $statuses = $held->getValidationStatuses();
            if ($is_waiting && count($statuses) > 1) {
                $status_name .= sprintf(
                    ' (%d/%d)',
                    count(array_filter($statuses, static fn(int $status) => $status === CommonITILValidation::ACCEPTED)),
                    count($statuses),
                );
            }

            foreach ($tasks as $task) {
                // The sub-tasks only follow their parent child ticket
                if ((int) ($task['level'] ?? $data['tasklevel']) !== (int) $data['tasklevel']) {
                    continue;
                }
                $rows[] = [
                    'task_name'   => Metademand::$SON_PREFIX . ($task['tickettasks_name'] ?? ''),
                    'validator'   => $validators,
                    'date'        => Html::convDateTime($data['date_creation']),
                    'is_waiting'  => $is_waiting,
                    'status_name' => $status_name,
                ];
            }
        }

        return $rows;
    }

    /**
     * Positive ids of a JSON list.
     *
     * @return int[]
     */
    private static function decodeIds(?string $json): array
    {
        $ids = json_decode((string) $json, true);

        return is_array($ids) ? array_values(array_filter(array_map('intval', $ids), static fn(int $id) => $id > 0)) : [];
    }

    /**
     * Status of each validation requested for the held child tickets, by validation id:
     * a validation deleted since is missing.
     *
     * @return array<int, int>
     */
    private function getValidationStatuses(): array
    {
        $ids = self::decodeIds($this->fields['ticketvalidations_ids'] ?? '');
        if ($ids === []) {
            return [];
        }

        $statuses = [];
        foreach ((new \TicketValidation())->find(['id' => $ids]) as $id => $validation) {
            $statuses[(int) $id] = (int) $validation['status'];
        }

        return $statuses;
    }

    /**
     * The held child tickets still waiting for this validation.
     */
    private static function findWaitingFor(\TicketValidation $validation): ?self
    {
        foreach ((new self())->find([
            'tickets_id' => (int) $validation->fields['tickets_id'],
            'status'     => self::WAITING,
        ]) as $data) {
            if (in_array($validation->getID(), self::decodeIds($data['ticketvalidations_ids'] ?? ''), true)) {
                $held = new self();
                $held->getFromDB((int) $data['id']);

                return $held;
            }
        }

        return null;
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
     * Request the validation of held child tickets on the parent ticket, to each validator.
     *
     * @param int   $tickets_id        parent ticket
     * @param int   $metademands_id    metademand creating the children
     * @param int[] $users_ids         validators, each one must accept
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
        array $users_ids,
        array $tasks,
        array $parent_fields,
        int $tasklevel,
        array $input_field,
        array $input_field_main,
        bool $as_tasks = false,
    ): bool {
        $users_ids = array_values(array_unique(array_filter(array_map('intval', $users_ids), static fn(int $id) => $id > 0)));
        if ($users_ids === []) {
            return false;
        }

        $comment = sprintf(
            htmlescape(__('Validation of the child tickets: %s', 'metademands')),
            htmlescape(self::getTaskNames($tasks, $tasklevel)),
        );
        $validation     = new \TicketValidation();
        $validations_id = [];
        foreach ($users_ids as $users_id) {
            $id = $validation->add([
                'tickets_id'         => $tickets_id,
                'itemtype_target'    => User::class,
                'items_id_target'    => $users_id,
                'comment_submission' => $comment,
            ]);
            if (!$id) {
                trigger_error(sprintf(
                    'metademands: the validation of the held child tickets of ticket %d could not be requested',
                    $tickets_id,
                ), E_USER_WARNING);
                // All or nothing: the children are created at once instead
                foreach ($validations_id as $requested) {
                    $validation->delete(['id' => $requested], true);
                }
                return false;
            }
            $validations_id[] = (int) $id;
        }

        $held = new self();
        if (!$held->add([
            'tickets_id'                        => $tickets_id,
            'plugin_metademands_metademands_id' => $metademands_id,
            'ticketvalidations_ids'             => json_encode($validations_id),
            'users_ids_validate'                => json_encode($users_ids),
            'status'                            => self::WAITING,
            'tasklevel'                         => $tasklevel,
            'as_tasks'                          => (int) $as_tasks,
            'tickets_to_create'                 => json_encode($tasks, JSON_INVALID_UTF8_SUBSTITUTE),
            'parent_fields'                     => json_encode($parent_fields, JSON_INVALID_UTF8_SUBSTITUTE),
            'input_field'                       => json_encode($input_field, JSON_INVALID_UTF8_SUBSTITUTE),
            'input_field_main'                  => json_encode($input_field_main, JSON_INVALID_UTF8_SUBSTITUTE),
        ])) {
            // Nothing would answer them: the children are created at once instead
            foreach ($validations_id as $requested) {
                $validation->delete(['id' => $requested], true);
            }
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
     * ITEM_UPDATE hook of TicketValidation: create the held child tickets once every
     * validator accepted, drop them on the first refusal.
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

        $held = self::findWaitingFor($validation);
        if ($held === null) {
            return;
        }

        if ($status === CommonITILValidation::REFUSED) {
            $held->refuse($validation);
            return;
        }

        // Every validation requested still there, and accepted
        $statuses = $held->getValidationStatuses();
        if (count($statuses) === count(self::decodeIds($held->fields['ticketvalidations_ids'] ?? ''))
            && count(array_filter($statuses, static fn(int $s) => $s !== CommonITILValidation::ACCEPTED)) === 0) {
            $held->createHeldTickets();
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
        $held = self::findWaitingFor($validation);
        if ($held === null) {
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
