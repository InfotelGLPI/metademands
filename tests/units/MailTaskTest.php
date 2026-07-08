<?php

namespace GlpiPlugin\Metademands\Tests\Units;

use GlpiPlugin\Metademands\Tests\MetademandsTestCase;
use PluginMetademandsConfig;
use PluginMetademandsField;
use PluginMetademandsMailTask;
use PluginMetademandsMetademand;
use PluginMetademandsTask;
use Ticket_Ticket;
use User;
use UserEmail;

class MailTaskTest extends MetademandsTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ensurePluginConfig(): void
    {
        global $DB;
        if (!countElementsInTable(PluginMetademandsConfig::getTable())) {
            $DB->insert(PluginMetademandsConfig::getTable(), [
                'id'                    => 1,
                'create_pdf'            => 0,
                'show_form_changes'     => 0,
                'childs_parent_content' => 0,
            ]);
        }
    }

    private function createMetademand(array $extra = []): PluginMetademandsMetademand
    {
        $meta = new PluginMetademandsMetademand();
        $id   = $meta->add(array_merge([
            'name'                 => 'Test Metademand MailTask',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => \Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ], $extra));
        $this->assertGreaterThan(0, $id, 'Failed to create Metademand');
        $meta->getFromDB($id);
        return $meta;
    }

    private function createTextField(int $metademands_id): PluginMetademandsField
    {
        $field = new PluginMetademandsField();
        $id    = $field->add([
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => 'text',
            'name'        => 'Text field',
            'rank'        => 1,
            'order'       => 1,
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $id, 'Failed to create Field');
        $field->getFromDB($id);
        return $field;
    }

    private function createMailTypeTask(int $metademands_id, string $name = 'Mail notification'): PluginMetademandsTask
    {
        $task = new PluginMetademandsTask();
        $id   = $task->add([
            'plugin_metademands_metademands_id' => $metademands_id,
            'name'          => $name,
            'type'          => PluginMetademandsTask::MAIL_TYPE,
            'level'         => 1,
            'entities_id'   => 0,
            'formatastable' => 0,
            'useBlock'      => 0,
            'block_use'     => '[]',
        ]);
        $this->assertGreaterThan(0, $id, 'Failed to create Task');
        $task->getFromDB($id);
        return $task;
    }

    private function createMailTask(int $tasks_id, array $extra = []): PluginMetademandsMailTask
    {
        $mailTask = new PluginMetademandsMailTask();
        $id       = $mailTask->add(array_merge([
            'plugin_metademands_tasks_id' => $tasks_id,
            'content'             => '<p>Test notification body</p>',
            'users_id_recipient'  => 0,
            'groups_id_recipient' => 0,
            'email'               => '',
            'itilcategories_id'   => 0,
        ], $extra));
        $this->assertGreaterThan(0, $id, 'Failed to create MailTask');
        $mailTask->getFromDB($id);
        return $mailTask;
    }

    private function createUserWithEmail(string $login, string $email): User
    {
        $user = new User();
        $id   = $user->add([
            'name'        => $login,
            'realname'    => 'Test',
            'firstname'   => 'User',
            'entities_id' => 0,
            '_useremails' => [$email],
        ]);
        $this->assertGreaterThan(0, $id, "Failed to create user $login");
        $user->getFromDB($id);
        return $user;
    }

    // -------------------------------------------------------------------------
    // Group 1: DB persistence
    // -------------------------------------------------------------------------

    public function testMailTaskCanBePersistedForMailTypeTask(): void
    {
        $this->login();
        $this->ensurePluginConfig();

        $meta     = $this->createMetademand();
        $task     = $this->createMailTypeTask($meta->getID());
        $mailTask = $this->createMailTask($task->getID(), ['content' => '<p>Notification</p>']);

        $this->assertGreaterThan(0, $mailTask->getID());

        $found = new PluginMetademandsMailTask();
        $this->assertTrue(
            $found->getFromDBByCrit(['plugin_metademands_tasks_id' => $task->getID()]),
            'MailTask must be retrievable from DB by its tasks_id'
        );
        $this->assertSame('<p>Notification</p>', $found->fields['content']);
    }

    public function testMailTypeConstantValue(): void
    {
        $this->assertSame(3, PluginMetademandsTask::MAIL_TYPE, 'MAIL_TYPE must equal 3');
    }

    public function testMailTaskTableExists(): void
    {
        global $DB;
        $this->assertTrue(
            $DB->tableExists(PluginMetademandsMailTask::getTable()),
            'Table ' . PluginMetademandsMailTask::getTable() . ' must exist'
        );
    }

    // -------------------------------------------------------------------------
    // Group 2: addObjects() — MAIL_TYPE must not create a child ticket
    // -------------------------------------------------------------------------

    public function testMailTypeTaskDoesNotCreateChildTicket(): void
    {
        $this->login();
        $this->ensurePluginConfig();

        $meta  = $this->createMetademand(['force_create_tasks' => 0]);
        $field = $this->createTextField($meta->getID());
        $task  = $this->createMailTypeTask($meta->getID());
        $this->createMailTask($task->getID());

        $count_before = countElementsInTable(Ticket_Ticket::getTable());

        $result = PluginMetademandsMetademand::addObjects(
            $meta->getID(),
            ['fields' => [$field->getID() => 'Test value']]
        );

        $this->assertIsArray($result, 'addObjects() must return an array');
        $this->assertGreaterThan(0, $result['id'], 'Parent ticket must be created');
        $this->assertSame(
            $count_before,
            countElementsInTable(Ticket_Ticket::getTable()),
            'A MAIL_TYPE task must never create a Ticket_Ticket child link'
        );
    }

    public function testNoMailAttemptedWhenRecipientHasNoEmail(): void
    {
        $this->login();
        $this->ensurePluginConfig();

        $meta  = $this->createMetademand(['force_create_tasks' => 0]);
        $field = $this->createTextField($meta->getID());
        $task  = $this->createMailTypeTask($meta->getID());

        $user = new User();
        $uid  = $user->add(['name' => 'noemail_' . uniqid(), 'entities_id' => 0]);
        $this->assertGreaterThan(0, $uid);
        $this->createMailTask($task->getID(), ['users_id_recipient' => $uid]);

        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        $result = PluginMetademandsMetademand::addObjects(
            $meta->getID(),
            ['fields' => [$field->getID() => 'Test']]
        );

        $this->assertGreaterThan(0, $result['id'], 'Parent ticket must still be created');

        $flat = array_merge(...array_values($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? [[]]));
        $this->assertNotContains(
            __('Fail to send email', 'metademands'),
            $flat,
            'No mail failure must appear when the recipient has no email address'
        );
    }

    // -------------------------------------------------------------------------
    // Group 3: PluginMetademandsMailTask::sendMail() direct call
    // -------------------------------------------------------------------------

    public function testSendMailReturnsBoolRegardlessOfSmtpAvailability(): void
    {
        global $CFG_GLPI;

        $orig_from = $CFG_GLPI['from_email']      ?? '';
        $orig_name = $CFG_GLPI['from_email_name'] ?? '';
        $orig_mode = $CFG_GLPI['smtp_mode']        ?? MAIL_MAIL;

        $CFG_GLPI['from_email']      = 'no-reply@test.local';
        $CFG_GLPI['from_email_name'] = 'GLPI Test';
        $CFG_GLPI['smtp_mode']       = MAIL_MAIL;

        try {
            $result = PluginMetademandsMailTask::sendMail(
                'Test subject',
                [['email' => 'recipient@test.local', 'name' => 'Recipient']],
                '<p>Test body</p>'
            );
            $this->assertIsBool($result, 'sendMail() must return a boolean regardless of SMTP availability');
        } finally {
            $CFG_GLPI['from_email']      = $orig_from;
            $CFG_GLPI['from_email_name'] = $orig_name;
            $CFG_GLPI['smtp_mode']       = $orig_mode;
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        }
    }

    public function testSendMailAcceptsStringRecipient(): void
    {
        global $CFG_GLPI;

        $orig_from = $CFG_GLPI['from_email']      ?? '';
        $orig_name = $CFG_GLPI['from_email_name'] ?? '';
        $orig_mode = $CFG_GLPI['smtp_mode']        ?? MAIL_MAIL;

        $CFG_GLPI['from_email']      = 'no-reply@test.local';
        $CFG_GLPI['from_email_name'] = 'GLPI Test';
        $CFG_GLPI['smtp_mode']       = MAIL_MAIL;

        try {
            $result = PluginMetademandsMailTask::sendMail(
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
    // Group 4: sendMail() triggered when recipient has a valid email
    // -------------------------------------------------------------------------

    public function testSendMailIsAttemptedWhenRecipientHasValidEmail(): void
    {
        global $CFG_GLPI;

        $this->login();
        $this->ensurePluginConfig();

        $orig_from = $CFG_GLPI['from_email']      ?? '';
        $orig_name = $CFG_GLPI['from_email_name'] ?? '';
        $orig_mode = $CFG_GLPI['smtp_mode']        ?? MAIL_MAIL;

        $CFG_GLPI['from_email']      = 'no-reply@test.local';
        $CFG_GLPI['from_email_name'] = 'GLPI Test';
        $CFG_GLPI['smtp_mode']       = MAIL_MAIL;

        try {
            $meta  = $this->createMetademand(['force_create_tasks' => 0]);
            $field = $this->createTextField($meta->getID());
            $task  = $this->createMailTypeTask($meta->getID());
            $user  = $this->createUserWithEmail('mailrecipient_' . uniqid(), 'recipient@test.local');

            $this->createMailTask($task->getID(), [
                'users_id_recipient' => $user->getID(),
                'content'            => '<p>Hello #' . $field->getID() . '#</p>',
            ]);

            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

            $result = PluginMetademandsMetademand::addObjects(
                $meta->getID(),
                ['fields' => [$field->getID() => 'World']]
            );

            $this->assertGreaterThan(0, $result['id'], 'Parent ticket must be created');

            $flat = array_merge(...array_values($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? [[]]));

            $mail_attempted = in_array(__('Email sent', 'metademands'), $flat)
                || in_array(__('Fail to send email', 'metademands'), $flat);

            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

            $this->assertTrue(
                $mail_attempted,
                'sendMail() must have been called — expected "Email sent" or "Fail to send email" in session messages'
            );
        } finally {
            $CFG_GLPI['from_email']      = $orig_from;
            $CFG_GLPI['from_email_name'] = $orig_name;
            $CFG_GLPI['smtp_mode']       = $orig_mode;
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        }
    }
}
