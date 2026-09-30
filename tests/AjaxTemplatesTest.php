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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\MetademandValidation;
use GlpiPlugin\Metademands\TicketField;
use Group;
use Search;
use Ticket;
use User;

/**
 * Fragments returned by the ajax/ endpoints of the plugin: the hidden inputs are
 * written by the templates, the core widgets are called in place.
 */
class AjaxTemplatesTest extends DbTestCase
{
    private const MARKUP = '1"><b>x</b>';

    private const ESCAPED_MARKUP = '1&quot;&gt;&lt;b&gt;x&lt;/b&gt;';

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context): string
    {
        return TemplateRenderer::getInstance()->render('@metademands/ajax/' . $template . '.html.twig', $context);
    }

    public function testReadonlyDropdownValueEscapesItsHiddenInput(): void
    {
        $html = $this->render('readonly_dropdown_value', [
            'value_name' => '<i>Root</i>',
            'field_name' => 'field[12]',
            'value'      => self::MARKUP,
        ]);

        $this->assertStringContainsString('&lt;i&gt;Root&lt;/i&gt;', $html);
        $this->assertStringContainsString(
            '<input type="hidden" name="field[12]" value="' . self::ESCAPED_MARKUP . '">',
            $html,
        );
    }

    public function testMassiveActionFieldPrintsTheWidgetOfTheSearchOption(): void
    {
        $this->login();
        $options = Search::getOptions(Ticket::class);

        // status: a ticket dropdown laid out bare; date: a date picker in the two-cell table
        foreach ([12 => ['status', false], 15 => ['date', true]] as $id => [$widget, $use_table]) {
            $search = $options[$id];
            $input  = ['value' => ''];
            $this->assertSame($use_table, TicketField::massiveActionFieldUsesTable(Ticket::class, $search, $input));

            $html = $this->render('massiveaction_field', [
                'use_table'  => $use_table,
                'itemtype'   => Ticket::class,
                'search'     => $search,
                'input'      => $input,
                'field_name' => self::MARKUP,
            ]);

            // the core dropdowns quote their attributes with either kind of quote
            $this->assertMatchesRegularExpression('/name=["\']' . $widget . '["\']/', $html);
            $this->assertStringContainsString(
                '<input type="hidden" name="field" value="' . self::ESCAPED_MARKUP . '">',
                $html,
            );
            $this->assertSame($use_table, str_contains($html, '<table>'));
        }
    }

    public function testGroupToAssignOffersTheAssignableGroups(): void
    {
        $this->login();

        $group = $this->createItem(Group::class, [
            'name'        => 'Assignable group',
            'entities_id' => $this->getTestRootEntity(true),
            'is_assign'   => 1,
        ]);

        $html = trim($this->render('group_to_assign', [
            'condition' => MetademandValidation::getAssignableGroupCriteria(),
            'value'     => $group->getID(),
        ]));

        $this->assertStringStartsWith('<td colspan="2">', $html);
        $this->assertStringContainsString('name="group_to_assign"', $html);
        $this->assertStringContainsString('Assignable group', $html);
        $this->assertStringEndsWith('</td>', $html);
    }

    public function testReloadItemPrintsTheDropdownOfTheType(): void
    {
        $this->login();

        $html = $this->render('reload_item', ['type' => 'dropdown_meta']);

        $this->assertStringContainsString('<label>' . __('Object', 'metademands') . '</label>', $html);
        $this->assertStringContainsString("<select name='item'", $html);
        $this->assertStringContainsString(__('Category of the metademand', 'metademands'), $html);
    }

    public function testUserTooltipPrintsTheInformationCard(): void
    {
        $this->login();

        $user = new User();
        $this->assertTrue($user->getFromDB(getItemByTypeName(User::class, TU_USER, true)));

        $html = $this->render('user_tooltip', ['user' => $user]);

        $this->assertStringContainsString('alert alert-info', $html);
        $this->assertStringContainsString($user->getInfoCard(), $html);
    }
}
