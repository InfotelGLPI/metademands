<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\MailTask;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use Ticket_Ticket;
use User;
use UserEmail;

class MailTaskTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), [
                'id'                    => 1,
                'create_pdf'            => 0,
                'show_form_changes'     => 0,
                'childs_parent_content' => 0,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createMetademand(array $extra = []): Metademand
    {
        return $this->createItem(Metademand::class, array_merge([
            'name'                 => 'Test Metademand',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => \Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ], $extra));
    }

    private function createTextField(int $metademands_id): Field
    {
        return $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => 'text',
            'name'        => 'Text field',
            'rank'        => 1,
            'order'       => 1,
            'entities_id' => 0,
        ]);
    }

    private function createMailTypeTask(int $metademands_id, string $name = 'Mail notification'): Task
    {
        return $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'name'          => $name,
            'type'          => Task::MAIL_TYPE,
            'level'         => 1,
            'entities_id'   => 0,
            'formatastable' => 0,
            'useBlock'      => 0,
            'block_use'     => '[]',
        ]);
    }

    private function createMailTask(int $tasks_id, array $extra = []): MailTask
    {
        return $this->createItem(MailTask::class, array_merge([
            'plugin_metademands_tasks_id' => $tasks_id,
            'content'              => '<p>Hello, this is a test notification.</p>',
            'users_id_recipient'   => 0,
            'groups_id_recipient'  => 0,
            'email'                => '',
            'itilcategories_id'    => 0,
        ], $extra));
    }

    /** Creates a GLPI user with a registered email address. Returns [User, email]. */
    private function createUserWithEmail(string $login, string $email): array
    {
        $user = $this->createItem(User::class, [
            'name'      => $login,
            'realname'  => 'Test',
            'firstname' => 'User',
            'entities_id' => 0,
        ]);

        $userEmail = $this->createItem(UserEmail::class, [
            'users_id'   => $user->getID(),
            'email'      => $email,
            'is_default' => 1,
        ]);

        return [$user, $email];
    }

    // -------------------------------------------------------------------------
    // Group 1: DB persistence
    // -------------------------------------------------------------------------

    /**
     * A MailTask record must be persistable for a Task of type MAIL_TYPE.
     */
    public function testMailTaskCanBePersistedForMailTypeTask(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $task       = $this->createMailTypeTask($metademand->getID());
        $mailTask   = $this->createMailTask($task->getID(), [
            'content' => '<p>Notification body</p>',
        ]);

        $this->assertGreaterThan(0, $mailTask->getID());

        // The record must be retrievable by its linked task ID
        $found = new MailTask();
        $this->assertTrue(
            $found->getFromDBByCrit(['plugin_metademands_tasks_id' => $task->getID()]),
            'MailTask must be retrievable from DB by its tasks_id'
        );
        $this->assertSame('<p>Notification body</p>', $found->fields['content']);
    }

    /**
     * A Task of type MAIL_TYPE must be tagged correctly with the MAIL_TYPE constant (3).
     */
    public function testMailTypeConstantValue(): void
    {
        $this->assertSame(3, Task::MAIL_TYPE, 'MAIL_TYPE must equal 3');
    }

    /**
     * The MailTask table must exist after plugin install.
     */
    public function testMailTaskTableExists(): void
    {
        global $DB;
        $this->assertTrue(
            $DB->tableExists(MailTask::getTable()),
            'Table ' . MailTask::getTable() . ' must exist'
        );
    }

    // -------------------------------------------------------------------------
    // Group 2: addObjects() — MAIL_TYPE must not create a child ticket
    // -------------------------------------------------------------------------

    /**
     * When a metademand has a MAIL_TYPE task (no recipient configured),
     * addObjects() must still create the parent ticket without crashing,
     * and must not create any Ticket_Ticket link.
     */
    public function testMailTypeTaskDoesNotCreateChildTicket(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand(['force_create_tasks' => 0]);
        $field      = $this->createTextField($metademand->getID());
        $task       = $this->createMailTypeTask($metademand->getID());

        // No recipient — sendMail() will never be reached
        $this->createMailTask($task->getID());

        $count_before = countElementsInTable(Ticket_Ticket::getTable());

        $result = Metademand::addObjects($metademand->getID(), ['fields' => [$field->getID() => 'Test value']]);

        $this->assertIsArray($result, 'addObjects() must return an array');
        $this->assertGreaterThan(0, $result['id'], 'The parent ticket must be created');
        $this->assertSame(
            $count_before,
            countElementsInTable(Ticket_Ticket::getTable()),
            'A MAIL_TYPE task must never create a Ticket_Ticket child link'
        );
    }

    /**
     * When the recipient user has no email address registered in glpi_useremails,
     * addObjects() must complete without error and without sending any mail.
     * No "Fail to send email" message must appear in the session.
     */
    public function testNoMailAttemptedWhenRecipientHasNoEmail(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand(['force_create_tasks' => 0]);
        $field      = $this->createTextField($metademand->getID());
        $task       = $this->createMailTypeTask($metademand->getID());

        // User without any email address
        $user = $this->createItem(User::class, [
            'name'        => 'noemail_user',
            'entities_id' => 0,
        ]);
        $this->createMailTask($task->getID(), ['users_id_recipient' => $user->getID()]);

        // Clear any pre-existing session messages
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        $result = Metademand::addObjects($metademand->getID(), ['fields' => [$field->getID() => 'Test']]);

        $this->assertGreaterThan(0, $result['id'], 'Parent ticket must still be created');

        $flat_messages = array_merge(...array_values($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? []));
        $this->assertNotContains(
            __('Fail to send email', 'metademands'),
            $flat_messages,
            'No mail failure must be reported when the recipient has no email address'
        );
    }

    // -------------------------------------------------------------------------
    // Group 3: MailTask::sendMail() direct call
    // -------------------------------------------------------------------------

    /**
     * sendMail() must always return a boolean, never throw an uncaught exception,
     * regardless of whether SMTP is available in the test environment.
     */
    public function testSendMailReturnsBoolRegardlessOfSmtpAvailability(): void
    {
        global $CFG_GLPI;

        $orig_from   = $CFG_GLPI['from_email']      ?? '';
        $orig_name   = $CFG_GLPI['from_email_name'] ?? '';
        $orig_mode   = $CFG_GLPI['smtp_mode']        ?? MAIL_MAIL;

        // Force native sendmail DSN — will fail gracefully when sendmail is absent
        $CFG_GLPI['from_email']      = 'no-reply@test.local';
        $CFG_GLPI['from_email_name'] = 'GLPI Test';
        $CFG_GLPI['smtp_mode']       = MAIL_MAIL;

        try {
            $result = MailTask::sendMail(
                'Test subject',
                [['email' => 'recipient@test.local', 'name' => 'Recipient Test']],
                '<p>Test body</p>'
            );
            $this->assertIsBool(
                $result,
                'sendMail() must return a boolean regardless of SMTP availability'
            );
        } finally {
            $CFG_GLPI['from_email']      = $orig_from;
            $CFG_GLPI['from_email_name'] = $orig_name;
            $CFG_GLPI['smtp_mode']       = $orig_mode;
            // sendMail() adds a session message on failure; consume it so tearDown does not complain
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        }
    }

    /**
     * sendMail() must accept a plain string as recipient (not just an array).
     */
    public function testSendMailAcceptsStringRecipient(): void
    {
        global $CFG_GLPI;

        $orig_from   = $CFG_GLPI['from_email']      ?? '';
        $orig_name   = $CFG_GLPI['from_email_name'] ?? '';
        $orig_mode   = $CFG_GLPI['smtp_mode']        ?? MAIL_MAIL;

        $CFG_GLPI['from_email']      = 'no-reply@test.local';
        $CFG_GLPI['from_email_name'] = 'GLPI Test';
        $CFG_GLPI['smtp_mode']       = MAIL_MAIL;

        try {
            $result = MailTask::sendMail(
                'Test subject plain',
                'recipient@test.local',
                '<p>Test body plain</p>'
            );
            $this->assertIsBool($result, 'sendMail() must return a boolean with a string recipient');
        } finally {
            $CFG_GLPI['from_email']      = $orig_from;
            $CFG_GLPI['from_email_name'] = $orig_name;
            $CFG_GLPI['smtp_mode']       = $orig_mode;
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        }
    }

    // -------------------------------------------------------------------------
    // Group 4: addObjects() — sendMail() triggered when recipient has an email
    // -------------------------------------------------------------------------

    /**
     * When the configured recipient user has a valid email address,
     * addObjects() must attempt to send the mail.
     * The attempt is confirmed by the presence of either the success or failure
     * session message — both prove the sendMail() code path was reached.
     */
    public function testSendMailIsAttemptedWhenRecipientHasValidEmail(): void
    {
        global $CFG_GLPI;

        $this->login('glpi', 'glpi');

        $orig_from   = $CFG_GLPI['from_email']      ?? '';
        $orig_name   = $CFG_GLPI['from_email_name'] ?? '';
        $orig_mode   = $CFG_GLPI['smtp_mode']        ?? MAIL_MAIL;

        $CFG_GLPI['from_email']      = 'no-reply@test.local';
        $CFG_GLPI['from_email_name'] = 'GLPI Test';
        $CFG_GLPI['smtp_mode']       = MAIL_MAIL;

        try {
            $metademand = $this->createMetademand(['force_create_tasks' => 0]);
            $field      = $this->createTextField($metademand->getID());
            $task       = $this->createMailTypeTask($metademand->getID());

            [$user] = $this->createUserWithEmail('mail_recipient', 'recipient@test.local');

            $this->createMailTask($task->getID(), [
                'users_id_recipient' => $user->getID(),
                'content'            => '<p>Hello #' . $field->getID() . '#</p>',
            ]);

            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

            $result = Metademand::addObjects(
                $metademand->getID(),
                ['fields' => [$field->getID() => 'World']]
            );

            $this->assertGreaterThan(0, $result['id'], 'Parent ticket must be created');

            // Flatten all session messages across severity levels
            $flat_messages = array_merge(...array_values($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? []));

            $mail_attempted = in_array(__('Email sent', 'metademands'), $flat_messages)
                || in_array(__('Fail to send email', 'metademands'), $flat_messages);

            // Consume messages before tearDown checks the session
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

            $this->assertTrue(
                $mail_attempted,
                'sendMail() must have been called: either "Email sent" or "Fail to send email" '
                . 'must appear in the session messages when a recipient email is configured'
            );
        } finally {
            $CFG_GLPI['from_email']      = $orig_from;
            $CFG_GLPI['from_email_name'] = $orig_name;
            $CFG_GLPI['smtp_mode']       = $orig_mode;
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        }
    }
}
