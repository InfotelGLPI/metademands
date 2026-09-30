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
use GlpiPlugin\Metademands\Fields\Date;
use GlpiPlugin\Metademands\Fields\Dateinterval;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;
use GlpiPlugin\Metademands\Fields\Dropdownobject;
use GlpiPlugin\Metademands\Fields\Information;
use GlpiPlugin\Metademands\Fields\Ldapdropdown;
use GlpiPlugin\Metademands\Fields\Radio;
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Fields\Title;
use GlpiPlugin\Metademands\Fields\Upload;
use GlpiPlugin\Metademands\Fields\Yesno;

/**
 * Specific parameters of the fields (templates/fields/field_parameter_*.html.twig): the
 * templates get values only and render the widgets through the core macros.
 */
class FieldParameterSpecificTest extends DbTestCase
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function getParams(string $type, string $item = '', array $params = []): array
    {
        return $params + [
            'id'                                  => 12,
            'plugin_metademands_metademands_id'   => 5,
            'type'                                => $type,
            'item'                                => $item,
            'object_to_create'                    => 'Ticket',
            'used_by_child'                       => 1,
            'use_future_date'                     => 0,
            'use_date_now'                        => 1,
            'additional_number_day'               => 7,
            'link_to_user'                        => 0,
            'used_by_ticket'                      => 0,
            'regex'                               => '',
            'display_type'                        => 1,
            'custom_values'                       => '',
            'default_use_id_requester'            => 1,
            'default_use_id_requester_supervisor' => 0,
            'informations_to_display'             => '',
            'readonly'                            => 1,
            'hidden'                              => 0,
            'color'                               => '#123456',
            'max_upload'                          => 3,
            'use_richtext'                        => 1,
        ];
    }

    private function assertHasName(string $name, string $html): void
    {
        $this->assertMatchesRegularExpression('/name=[\'"]' . preg_quote($name, '/') . '[\'"]/', $html);
    }

    private function assertNoName(string $name, string $html): void
    {
        $this->assertDoesNotMatchRegularExpression('/name=[\'"]' . preg_quote($name, '/') . '[\'"]/', $html);
    }

    /**
     * Option selected in the select named $name.
     */
    private function assertSelected(string $name, string $value, string $html): void
    {
        $this->assertMatchesRegularExpression(
            '/name=[\'"]' . preg_quote($name, '/') . '[\'"][^>]*>.*?<option value=[\'"]'
            . preg_quote($value, '/') . '[\'"][^>]*selected.*?<\/select>/s',
            $html,
        );
    }

    public function testDateOffersItsValues(): void
    {
        $this->login();

        $html = Date::showFieldParameters($this->getParams('date'));

        $this->assertSelected('used_by_child', '1', $html);
        $this->assertSelected('use_future_date', '0', $html);
        $this->assertSelected('use_date_now', '1', $html);
        // Number dropdown of the core, loaded through AJAX with its current value
        $this->assertHasName('additional_number_day', $html);
        $this->assertStringContainsString('getDropdownNumber.php', $html);
    }

    public function testIntervalHasNoChildTicketParameter(): void
    {
        $this->login();

        $html = Dateinterval::showFieldParameters($this->getParams('date_interval'));

        $this->assertSelected('use_date_now', '1', $html);
        $this->assertNoName('used_by_child', $html);
    }

    public function testTextEscapesTheRegexAndOffersUserInformationWhenLinked(): void
    {
        $this->login();

        $html = Text::showFieldParameters($this->getParams('text', '', ['regex' => '"><b>x</b>']));
        $this->assertStringContainsString('value="&quot;&gt;&lt;b&gt;x&lt;/b&gt;"', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertSelected('link_to_user', '0', $html);
        $this->assertNoName('used_by_ticket', $html);

        $html = Text::showFieldParameters($this->getParams('text', '', ['link_to_user' => 4, 'used_by_ticket' => 11]));
        $this->assertSelected('used_by_ticket', '11', $html);
    }

    public function testLdapdropdownKeepsTheDirectoryChangeHandler(): void
    {
        $this->login();

        $html = Ldapdropdown::showFieldParameters($this->getParams('dropdown_ldap', '', [
            'authldaps_id'   => 0,
            'ldap_filter'    => '(uid=*)',
            'ldap_attribute' => 0,
        ]));

        $this->assertHasName('authldaps_id', $html);
        $this->assertStringContainsString('plugin_metademands_changeLDAP', $html);
        $this->assertStringContainsString('value="(uid=*)"', $html);
        $this->assertHasName('ldap_attribute', $html);
    }

    public function testSimpleDropdownsKeepTheirValue(): void
    {
        $this->login();

        $this->assertSelected('display_type', '1', Yesno::showFieldParameters($this->getParams('yesno')));
        $this->assertSelected('display_type', '1', Radio::showFieldParameters($this->getParams('radio')));
        $this->assertSelected('max_upload', '3', Upload::showFieldParameters($this->getParams('upload')));
        $this->assertSelected('use_richtext', '1', Textarea::showFieldParameters($this->getParams('textarea')));
    }

    public function testColorAndInformationType(): void
    {
        $this->login();

        $html = Title::showFieldParameters($this->getParams('title', '', ['color' => '"><b>x</b>']));
        $this->assertStringContainsString('<input type="color" name="color" value="&quot;&gt;&lt;b&gt;x&lt;/b&gt;">', $html);
        $this->assertNoName('display_type', $html);

        $html = Information::showFieldParameters($this->getParams('informations', '', ['display_type' => 2]));
        $this->assertStringContainsString('value="#123456"', $html);
        $this->assertSelected('display_type', '2', $html);
    }

    public function testDropdownOfLocationsOffersItsOptions(): void
    {
        $this->login();

        $html = Dropdown::showFieldParameters($this->getParams('dropdown', 'Location', ['location_depth' => 3]));

        $this->assertSelected('used_by_child', '1', $html);
        $this->assertHasName('link_to_user', $html);
        $this->assertHasName('root_items_id', $html);
        $this->assertSelected('location_depth', '3', $html);
        $this->assertStringContainsString(__('Splitted display', 'metademands'), $html);
    }

    public function testDropdownmetaOfCategory(): void
    {
        $this->login();

        $html = Dropdownmeta::showFieldParameters($this->getParams('dropdown_meta', 'ITILCategory_Metademands'));

        $this->assertSelected('readonly', '1', $html);
        $this->assertSelected('hidden', '0', $html);
        $this->assertNoName('display_type', $html);
    }

    public function testDropdownmultipleOfUsers(): void
    {
        $this->login();

        $html = Dropdownmultiple::showFieldParameters($this->getParams('dropdown_multiple', 'User', [
            'informations_to_display' => json_encode(['email', 'name']),
        ]));

        $this->assertSelected('default_use_id_requester', '1', $html);
        $this->assertHasName('informations_to_display[]', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]email[\'"][^>]*selected/', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]name[\'"][^>]*selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value=[\'"]full_name[\'"][^>]*selected/', $html);
    }

    public function testDropdownobjectOfGroups(): void
    {
        $this->login();

        $html = Dropdownobject::showFieldParameters($this->getParams('dropdown_object', 'Group', [
            'custom_values' => json_encode(['is_watcher' => 1]),
        ]));

        $this->assertHasName('link_to_user', $html);
        $this->assertSelected('used_by_child', '1', $html);
        $this->assertSelected('is_watcher', '1', $html);
        $this->assertSelected('is_assign', '0', $html);

        $html = Dropdownobject::showFieldParameters($this->getParams('dropdown_object', 'User', [
            'object_to_create' => 'Change',
        ]));
        $this->assertSelected('readonly', '1', $html);
        $this->assertHasName('informations_to_display[]', $html);
        $this->assertNoName('used_by_child', $html);
    }
}
