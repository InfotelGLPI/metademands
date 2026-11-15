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
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;
use GlpiPlugin\Metademands\Fields\Dropdownobject;
use GlpiPlugin\Metademands\Metademand;
use Group;
use Group_User;
use User;

/**
 * "Members of these groups" of a user field: only the members of the chosen groups are
 * offered (a dropdown of managers...), in the list, in the server side checks, and the
 * groups are found again by their names on another GLPI.
 */
class FieldMemberGroupsTest extends DbTestCase
{
    /**
     * @return array{managers: int, others: int, tech: int, normal: int}
     */
    private function createGroups(): array
    {
        $managers = $this->createItem(Group::class, ['name' => 'Managers ' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        $others = $this->createItem(Group::class, ['name' => 'Others ' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        $tech = getItemByTypeName(User::class, 'tech', true);
        $normal = getItemByTypeName(User::class, 'normal', true);
        $this->createItem(Group_User::class, ['groups_id' => $managers->getID(), 'users_id' => $tech]);
        $this->createItem(Group_User::class, ['groups_id' => $others->getID(), 'users_id' => $normal]);

        return ['managers' => $managers->getID(), 'others' => $others->getID(), 'tech' => $tech, 'normal' => $normal];
    }

    private function createUserField(array $member_groups): Field
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Member groups ' . mt_rand(),
            'entities_id'      => 0,
            'is_recursive'     => 1,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'dropdown_object',
            'item'                              => User::class,
            'name'                              => 'Manager',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => 0,
        ], ['order']);
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'custom'                       => FieldParameter::_serialize([
                'member_groups' => FieldParameter::formatMemberGroups($member_groups),
            ]),
        ], ['custom']);

        return $field;
    }

    /**
     * @param array{results: array<int, array<string, mixed>>, count: int} $data
     *
     * @return int[]
     */
    private function getIds(array $data): array
    {
        return array_values(array_filter(array_column($data['results'], 'id')));
    }

    public function testStorage(): void
    {
        $this->assertSame('3,7', FieldParameter::formatMemberGroups(['', '3', 7, '3', 'x', -1]));
        $this->assertSame('', FieldParameter::formatMemberGroups(''));
        $this->assertSame([3, 7], FieldParameter::getMemberGroupsIds(['member_groups' => '3,7']));
        $this->assertSame([3, 7], FieldParameter::getMemberGroupsIds(FieldParameter::_serialize(['member_groups' => '3,7', 'user_group' => 1])));
        $this->assertSame([], FieldParameter::getMemberGroupsIds(['user_group' => 1]));
        $this->assertSame([], FieldParameter::getMemberGroupsIds(''));
    }

    public function testTheListOffersTheMembersOnly(): void
    {
        $this->login();
        $groups = $this->createGroups();

        $data = Dropdownobject::getGroupMembersDropdown([$groups['managers']], null, []);
        $this->assertSame([$groups['tech']], $this->getIds($data));
        $this->assertSame(0, $data['results'][0]['id']);

        $both = Dropdownobject::getGroupMembersDropdown([$groups['managers'], $groups['others']], null, []);
        $this->assertEqualsCanonicalizing([$groups['tech'], $groups['normal']], $this->getIds($both));

        // Search, left out users, only the groups of the user too
        $this->assertSame([$groups['normal']], $this->getIds(
            Dropdownobject::getGroupMembersDropdown([$groups['managers'], $groups['others']], null, ['searchText' => 'norm']),
        ));
        $this->assertSame([$groups['normal']], $this->getIds(
            Dropdownobject::getGroupMembersDropdown([$groups['managers'], $groups['others']], null, ['used' => [$groups['tech']]]),
        ));
        $this->assertSame([$groups['tech']], $this->getIds(
            Dropdownobject::getGroupMembersDropdown([$groups['managers'], $groups['others']], [$groups['managers']], []),
        ));
    }

    public function testOnlyTheMembersAreAccepted(): void
    {
        $this->login();
        $groups = $this->createGroups();
        $field = $this->createUserField([$groups['managers']]);

        $this->assertTrue(Config::isOfferedByUserField($groups['tech'], $field->getID()));
        $this->assertFalse(Config::isOfferedByUserField($groups['normal'], $field->getID()));
        // The caller themself as well, when outside the groups
        $this->assertFalse(Config::isOfferedByUserField((int) \Session::getLoginUserID(), $field->getID()));

        $this->assertTrue(FieldParameter::isMemberOfGroups($groups['tech'], [$groups['managers']]));
        $this->assertFalse(FieldParameter::isMemberOfGroups($groups['normal'], [$groups['managers']]));
        $this->assertFalse(FieldParameter::isMemberOfGroups($groups['tech'], []));
    }

    public function testTheWizardListComesFromThePlugin(): void
    {
        $this->login();
        $groups = $this->createGroups();
        $field = $this->createUserField([$groups['managers']]);
        $parameter = new FieldParameter();
        $this->assertTrue($parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field->getID()]));
        $data = $field->fields + [
            'display_type'                        => 0,
            'default_use_id_requester'            => 1,
            'default_use_id_requester_supervisor' => 0,
            'link_to_user'                        => 0,
            'readonly'                            => 0,
            'is_mandatory'                        => 0,
            'custom'                              => $parameter->fields['custom'],
        ];

        ob_start();
        Dropdownobject::showWizardField($data, 'field', 0, false, 0);
        $html = (string) ob_get_clean();

        // The url of the select2, escaped for JS by the core
        $this->assertMatchesRegularExpression(
            '/ajax\\\\?\/dropdownGroupMembers\.php(\?|\\\\u003F)fields_id(=|\\\\u003D)' . $field->getID() . '\b/',
            $html,
        );
        $this->assertStringNotContainsString('getDropdownUsers.php', $html);
        // The requester, outside the managers, is not preselected
        $this->assertStringNotContainsString(
            'value="' . \Session::getLoginUserID() . '" selected',
            $html,
        );
    }

    public function testTheParametersOfferTheGroups(): void
    {
        $this->login();
        $groups = $this->createGroups();
        $params = [
            'id'                                  => 12,
            'plugin_metademands_metademands_id'   => 5,
            'object_to_create'                    => 'Ticket',
            'used_by_child'                       => 0,
            'link_to_user'                        => 0,
            'display_type'                        => 0,
            'custom_values'                       => FieldParameter::_serialize(['member_groups' => (string) $groups['managers']]),
            'default_use_id_requester'            => 0,
            'default_use_id_requester_supervisor' => 0,
            'informations_to_display'             => '',
            'readonly'                            => 0,
        ];

        foreach ([
            Dropdownobject::showFieldParameters($params + ['type' => 'dropdown_object', 'item' => User::class]),
            Dropdownmultiple::showFieldParameters($params + ['type' => 'dropdown_multiple', 'item' => User::class]),
        ] as $html) {
            $this->assertMatchesRegularExpression('/name=[\'"]member_groups\[\][\'"]/', $html);
            $this->assertMatchesRegularExpression('/<option value=[\'"]' . $groups['managers'] . '[\'"][^>]*selected/', $html);
            $this->assertMatchesRegularExpression('/<option value=[\'"]' . $groups['others'] . '[\'"](?![^>]*selected)/', $html);
        }
    }

    public function testImportFindsTheGroupsByName(): void
    {
        $this->login();
        $groups = $this->createGroups();
        $field = $this->createUserField([$groups['managers'], $groups['others']]);

        $target = Export::exportAsXMLForMetademands((int) $field->fields['plugin_metademands_metademands_id']);
        $this->assertNotEmpty($target);
        $xml = (string) file_get_contents($target['path']);
        @unlink($target['path']);

        // On the target GLPI, the managers have another id and the others do not exist
        $managers = new Group();
        $this->assertTrue($managers->getFromDB($groups['managers']));
        $others = new Group();
        $this->assertTrue($others->getFromDB($groups['others']));
        $others_name = $others->fields['completename'];
        $this->assertTrue($others->delete(['id' => $groups['others']], true));
        $this->assertTrue($managers->delete(['id' => $groups['managers']], true));
        $new_managers = $this->createItem(Group::class, ['name' => $managers->fields['name'], 'entities_id' => 0, 'is_recursive' => 1]);

        $file = GLPI_TMP_DIR . '/md_export_member_groups.xml';
        file_put_contents($file, $xml);
        $import = new \ReflectionMethod(Export::class, 'importXmlFile');
        $new_id = (int) $import->invoke(null, $file);
        @unlink($file);
        $this->assertGreaterThan(0, $new_id);

        $new_field = new Field();
        $this->assertTrue($new_field->getFromDBByCrit(['plugin_metademands_metademands_id' => $new_id, 'name' => 'Manager']));
        $parameter = new FieldParameter();
        $this->assertTrue($parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $new_field->getID()]));
        $this->assertSame([$new_managers->getID()], FieldParameter::getMemberGroupsIds($parameter->fields['custom']));

        $this->hasSessionMessages(WARNING, [
            sprintf(
                __('Groups left out of "Members of these groups", no group of that name here: %s', 'metademands'),
                '"' . $others_name . '"',
            ),
        ]);
    }
}
