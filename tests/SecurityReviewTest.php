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

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Signature;
use GlpiPlugin\Metademands\Metademand;

/**
 * Regressions of the 3.7.0 security review (four LOW findings):
 * - block dropdown on an itemtype the reader may not see;
 * - lookups of a user's details tied to a user field of a form the caller may fill in;
 * - actors posted for a ticket replayed against the picker (account state, "my groups");
 * - a signature reloaded from a stored record accepted only for its own field.
 */
class SecurityReviewTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();
        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        unset($_SESSION['plugin_metademands_signatures']);
    }

    public function tearDown(): void
    {
        unset($_SESSION['plugin_metademands_signatures']);
        parent::tearDown();
    }

    private function createMetademand(): Metademand
    {
        /** @var Metademand */
        return $this->createItem(Metademand::class, [
            'name'             => 'Security review',
            'entities_id'      => $this->getTestRootEntity(true),
            'is_recursive'     => 1,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    private function createField(Metademand $metademand, array $input, array $custom = []): Field
    {
        /** @var Field $field */
        $field = $this->createItem(Field::class, $input + [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'name'                              => 'Field',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'custom'                       => FieldParameter::_serialize($custom),
        ], ['custom']);

        return $field;
    }

    private function createUser(array $input = []): int
    {
        $users_id = $this->createItem(\User::class, $input + [
            'name' => 'md_sec_' . $this->getUniqueString(),
        ])->getID();
        $this->createItem(\Profile_User::class, [
            'users_id'     => $users_id,
            'profiles_id'  => getItemByTypeName(\Profile::class, 'Self-Service', true),
            'entities_id'  => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);

        return $users_id;
    }

    /** Self-service caller: no READ on users, nor on contracts */
    private function asSelfService(): void
    {
        $_SESSION['glpiactiveprofile'][\User::$rightname]     = 0;
        $_SESSION['glpiactiveprofile'][\Contract::$rightname] = 0;
    }

    private static function callPrivate(string $class, string $method, array $args): mixed
    {
        $reflection = new \ReflectionMethod($class, $method);

        return $reflection->invokeArgs(null, $args);
    }

    // -------------------------------------------------------------------------
    // Block dropdown (src/Fields/Dropdown.php)
    // -------------------------------------------------------------------------

    public function testBlockDropdownListsNothingOnAnItemtypeTheReaderCannotSee(): void
    {
        $metademand = $this->createMetademand();
        $this->createItem(\Contract::class, [
            'name'        => 'Secret contract',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $data = [
            'id'                                => 1,
            'item'                              => \Contract::class,
            'display_type'                      => Dropdown::BLOCK_DISPLAY,
            'is_mandatory'                      => 0,
            'icon'                              => '',
            'plugin_metademands_metademands_id' => $metademand->getID(),
        ];

        ob_start();
        Dropdown::showWizardField($data, 'field', 0, false, 0);
        $allowed = (string) ob_get_clean();
        $this->assertStringContainsString('Secret contract', $allowed);

        $this->asSelfService();
        ob_start();
        Dropdown::showWizardField($data, 'field', 0, false, 0);
        $refused = (string) ob_get_clean();
        $this->assertStringNotContainsString('Secret contract', $refused);
    }

    // -------------------------------------------------------------------------
    // Lookups of a user's details (src/Config.php)
    // -------------------------------------------------------------------------

    public function testALookupByIdNeedsTheUserFieldOfAForm(): void
    {
        $target = $this->createUser();
        $this->asSelfService();

        // Same entity: the former rule. Without a field, an Ajax lookup is now refused.
        $this->assertTrue(Config::canCurrentUserViewRequester($target));
        $this->assertFalse(Config::canCurrentUserViewRequester($target, 0, true));
        // Self is always allowed
        $this->assertTrue(Config::canCurrentUserViewRequester((int) \Session::getLoginUserID(), 0, true));
    }

    public function testALookupThroughAUserFieldReplaysItsPicker(): void
    {
        $metademand = $this->createMetademand();
        $user_field = $this->createField($metademand, ['type' => 'dropdown_object', 'item' => \User::class]);
        $text_field = $this->createField($metademand, ['type' => 'text', 'item' => '']);
        $target     = $this->createUser();
        $inactive   = $this->createUser(['is_active' => 0]);
        $expired    = $this->createUser(['end_date' => '2020-01-01 00:00:00']);
        $this->asSelfService();

        $this->assertTrue(Config::canCurrentUserViewRequester($target, $user_field->getID(), true));
        $this->assertFalse(Config::canCurrentUserViewRequester($inactive, $user_field->getID(), true));
        $this->assertFalse(Config::canCurrentUserViewRequester($expired, $user_field->getID(), true));
        // Not a user field
        $this->assertFalse(Config::canCurrentUserViewRequester($target, $text_field->getID(), true));
    }

    public function testALookupThroughTheFieldOfAFormOutOfReachIsRefused(): void
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Other entity',
            'entities_id'      => getItemByTypeName(\Entity::class, '_test_child_2', true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $user_field = $this->createField($metademand, ['type' => 'dropdown_object', 'item' => \User::class]);
        $target     = $this->createUser();
        $this->setEntity('_test_child_1', false);
        $this->asSelfService();

        $this->assertFalse(Config::canCurrentUserViewRequester($target, $user_field->getID(), true));
    }

    public function testOnlyUsersOfMyGroupsIsReplayed(): void
    {
        $metademand = $this->createMetademand();
        $user_field = $this->createField(
            $metademand,
            ['type' => 'dropdown_object', 'item' => \User::class],
            ['user_group' => 1],
        );
        $group    = $this->createItem(\Group::class, ['name' => 'Mine', 'entities_id' => $this->getTestRootEntity(true)]);
        $member   = $this->createUser();
        $stranger = $this->createUser();
        $this->createItem(\Group_User::class, ['groups_id' => $group->getID(), 'users_id' => $member]);
        $_SESSION['glpigroups'] = [$group->getID()];
        $this->asSelfService();

        $this->assertTrue(Config::isOfferedByUserField($member, $user_field->getID()));
        $this->assertFalse(Config::isOfferedByUserField($stranger, $user_field->getID()));
    }

    // -------------------------------------------------------------------------
    // Actors posted for the ticket (src/Metademand.php)
    // -------------------------------------------------------------------------

    public function testActorsMustBeSelectableAccounts(): void
    {
        $active   = $this->createUser();
        $inactive = $this->createUser(['is_active' => 0]);
        $future   = $this->createUser(['begin_date' => '2037-01-01 00:00:00']);
        $deleted  = $this->createUser();
        (new \User())->delete(['id' => $deleted]);

        $accepted = self::callPrivate(
            Metademand::class,
            'filterActorValues',
            [[$active, $inactive, $future, $deleted], \User::class, []],
        );

        $this->assertSame([$active], $accepted);
    }

    public function testActorsOfAMyGroupsFieldMustShareAGroup(): void
    {
        $group    = $this->createItem(\Group::class, ['name' => 'Team', 'entities_id' => $this->getTestRootEntity(true)]);
        $member   = $this->createUser();
        $stranger = $this->createUser();
        $this->createItem(\Group_User::class, ['groups_id' => $group->getID(), 'users_id' => $member]);
        $_SESSION['glpigroups'] = [$group->getID()];
        $field = ['custom_values' => ['user_group' => 1]];

        $accepted = self::callPrivate(
            Metademand::class,
            'filterActorValues',
            [[$member, $stranger, (int) \Session::getLoginUserID()], \User::class, $field],
        );

        $this->assertSame([$member, (int) \Session::getLoginUserID()], $accepted);
        // Without the option, both members of the entities are accepted
        $this->assertSame(
            [$member, $stranger],
            self::callPrivate(Metademand::class, 'filterActorValues', [[$member, $stranger], \User::class, []]),
        );
    }

    // -------------------------------------------------------------------------
    // Signatures (src/Fields/Signature.php)
    // -------------------------------------------------------------------------

    public function testALoadedSignatureIsOnlyAcceptedForItsOwnField(): void
    {
        $metademand = $this->createMetademand();
        $signature  = $this->createField($metademand, ['type' => 'signature', 'item' => '']);
        $other      = $this->createField($metademand, ['type' => 'signature', 'item' => '']);

        $path = 'md_sec/' . $this->getUniqueString() . '.png';
        @mkdir(GLPI_PICTURE_DIR . '/md_sec', 0o775, true);
        file_put_contents(GLPI_PICTURE_DIR . '/' . $path, 'png');
        try {
            // Reloaded from a stored draft / form: the signature of its field
            Signature::registerLoadedValues([$signature->getID() => $path]);

            $this->assertSame($path, Signature::sanitizeSubmittedValue($path, $signature->getID()));
            $this->assertSame('', Signature::sanitizeSubmittedValue($path, $other->getID()));
            // Display of a stored value: the path alone
            $this->assertSame($path, Signature::sanitizeSubmittedValue($path));
            // Never deletable by the session that reloaded it
            $this->assertFalse(Signature::isOwnUpload($path));
        } finally {
            @unlink(GLPI_PICTURE_DIR . '/' . $path);
        }
    }

    public function testAnUploadOfTheSessionStaysUsableAndDeletable(): void
    {
        $metademand = $this->createMetademand();
        $signature  = $this->createField($metademand, ['type' => 'signature', 'item' => '']);

        $path = 'md_sec/' . $this->getUniqueString() . '.png';
        @mkdir(GLPI_PICTURE_DIR . '/md_sec', 0o775, true);
        file_put_contents(GLPI_PICTURE_DIR . '/' . $path, 'png');
        try {
            Signature::registerUpload($path);
            // Reloading a draft holding it does not downgrade it
            Signature::registerLoadedValues([$signature->getID() => $path]);

            $this->assertTrue(Signature::isOwnUpload($path));
            $this->assertSame($path, Signature::sanitizeSubmittedValue($path, $signature->getID()));
        } finally {
            @unlink(GLPI_PICTURE_DIR . '/' . $path);
        }
    }

    public function testAnUnknownSignatureIsRefused(): void
    {
        $this->assertSame('', Signature::sanitizeSubmittedValue('someone/else.png', 1));
        $this->assertSame('', Signature::sanitizeSubmittedValue(['array'], 1));
    }
}
