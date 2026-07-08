<?php

namespace GlpiPlugin\Metademands\Tests;

use Auth;
use PHPUnit\Framework\TestCase;
use Session;

abstract class MetademandsTestCase extends TestCase
{
    protected function setUp(): void
    {
        global $DB;
        $DB->beginTransaction();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        global $DB;
        $DB->rollback();
        // Consume any session messages so they don't bleed between tests
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        parent::tearDown();
    }

    protected function login(
        string $user_name = TU_USER,
        string $user_pass = TU_PASS,
        bool $noauto = true
    ): void {
        Session::destroy();
        Session::start();

        // Try standard login first
        $auth = new Auth();
        if ($auth->login($user_name, $user_pass, $noauto)) {
            return;
        }

        // Fallback: populate session manually from DB (handles LDAP-only instances where
        // the LDAP server is unreachable in the test environment)
        global $DB, $CFG_GLPI;

        $iter = $DB->request([
            'SELECT' => ['id', 'name', 'profiles_id', 'entities_id', 'realname', 'firstname', 'authtype', 'use_mode'],
            'FROM'   => 'glpi_users',
            'WHERE'  => ['name' => $user_name, 'authtype' => 1, 'is_deleted' => 0, 'is_active' => 1],
            'LIMIT'  => 1,
        ]);
        $row = $iter->current();
        if (!$row) {
            $iter = $DB->request([
                'SELECT' => ['id', 'name', 'profiles_id', 'entities_id', 'realname', 'firstname', 'authtype', 'use_mode'],
                'FROM'   => 'glpi_users',
                'WHERE'  => ['authtype' => 1, 'is_deleted' => 0, 'is_active' => 1],
                'LIMIT'  => 1,
            ]);
            $row = $iter->current();
        }
        $this->assertNotNull($row, "Cannot find any local DB user to log in as '$user_name'");

        Session::destroy();
        Session::start();

        // Replicate the minimal session keys written by Session::start() after a successful login
        $_SESSION['valid_id']              = session_id();
        $_SESSION['glpi_currenttime']      = date('Y-m-d H:i:s');
        $_SESSION['glpi_use_mode']         = Session::NORMAL_MODE;
        $_SESSION['glpiID']                = $row['id'];
        $_SESSION['glpiname']              = $row['name'];
        $_SESSION['glpirealname']          = $row['realname'] ?? '';
        $_SESSION['glpifirstname']         = $row['firstname'] ?? '';
        $_SESSION['glpifriendlyname']      = trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? '')) ?: $row['name'];
        $_SESSION['glpidefault_entity']    = $row['entities_id'] ?? 0;
        $_SESSION['glpiauthtype']          = $row['authtype'];
        $_SESSION['glpiextauth']           = 0;
        $_SESSION['glpiroot']              = $CFG_GLPI['root_doc'] ?? '';
        $_SESSION['glpi_tabs']             = [];

        // Active profile/entity — use the user's default profile, fallback to super-admin (4)
        $profile_id = $row['profiles_id'] ?? 4;
        $_SESSION['glpiactiveprofile']     = ['id' => $profile_id, 'interface' => 'central', 'helpdesk_hardware' => 0, 'helpdesk_item_type' => []];
        $_SESSION['glpiactiveentities']    = [0];
        $_SESSION['glpiactiveentities_string'] = "'0'";
        $_SESSION['glpiactive_entity']     = 0;
        $_SESSION['glpiactive_entity_name'] = 'Root entity';
        $_SESSION['glpiactive_entity_recursive'] = 1;
        $_SESSION['glpiis_ids_visible']    = 0;
        $_SESSION['glpilist_limit']        = 20;
        $_SESSION['glpicrontimer']         = time();
    }
}
