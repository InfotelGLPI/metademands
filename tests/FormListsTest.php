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
use GlpiPlugin\Metademands\Form;
use GlpiPlugin\Metademands\Metademand;
use Session;

/**
 * Saved forms and models of a meta-demand (forms/*_list.html.twig): the tooltip is a
 * Bootstrap `form-help` whose content is escaped into its attribute, the input and
 * buttons are written by the templates.
 */
class FormListsTest extends DbTestCase
{
    private const MARKUP = '<b>x</b>"';

    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Saved forms',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function createForm(Metademand $metademand, array $input): Form
    {
        return $this->createItem(Form::class, $input + [
            'name'                              => self::MARKUP,
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'users_id'                          => Session::getLoginUserID(),
            'date'                              => '2026-09-30 10:00:00',
            'entities_id'                       => $this->getTestRootEntity(true),
        ]);
    }

    public function testUserFormTooltipIsEscapedIntoItsAttribute(): void
    {
        $this->login();

        $metademand = $this->createMetademand();
        $this->createForm($metademand, ['is_model' => 0]);

        $html = Form::showFormsForUserMetademand(Session::getLoginUserID(), $metademand->getID());

        $this->assertStringContainsString('<span class="form-help" data-bs-toggle="tooltip"', $html);
        // Name escaped once in the tooltip HTML, then the whole HTML escaped into the attribute
        $this->assertStringContainsString(
            'data-bs-title="' . htmlescape(__('Name') . ' : ' . htmlescape(self::MARKUP) . '<br>'),
            $html,
        );
        $this->assertStringNotContainsString('.qtip(', $html);
        $this->assertStringNotContainsString(self::MARKUP, $html);
    }

    public function testLinkedItemIsKeptAsHtmlInTheTooltip(): void
    {
        $this->login();

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Linked ticket',
            'content'     => 'Content',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $metademand = $this->createMetademand();
        $this->createForm($metademand, [
            'is_model' => 0,
            'itemtype' => \Ticket::class,
            'items_id' => $ticket->getID(),
        ]);

        $html = Form::showFormsForUserMetademand(Session::getLoginUserID(), $metademand->getID());

        $this->assertStringContainsString(htmlescape('<br>' . __('URL') . ' : ' . $ticket->getLink()), $html);
    }

    public function testPrivateModelsWriteTheirInputAndButtons(): void
    {
        $this->login();

        $metademand = $this->createMetademand();
        $this->createForm($metademand, ['is_model' => 1, 'is_private' => 1]);

        $html = Form::showPrivateFormsForUserMetademand(Session::getLoginUserID(), $metademand->getID());

        $this->assertStringContainsString('<input type="text" name="form_name" maxlength="250"', $html);
        $this->assertMatchesRegularExpression(
            '/<button type="submit" value="' . preg_quote(_x('button', 'Save as model', 'metademands'), '/')
            . '" name="save_form" form=""\s+id="FormAdd\d+" class="btn btn-success btn-sm">/',
            $html,
        );
        $this->assertStringContainsString('name="clean_form"', $html);
        $this->assertStringContainsString('data-bs-title="', $html);
        $this->assertStringNotContainsString(self::MARKUP, $html);
    }

    public function testPublicModelTooltipEscapesItsAuthor(): void
    {
        $this->login();

        $metademand = $this->createMetademand();
        $this->createForm($metademand, ['is_model' => 1, 'is_private' => 0]);

        $html = Form::showPublicFormsForUserMetademand($metademand->getID());

        $author = getUserName(Session::getLoginUserID());
        $this->assertStringContainsString(
            htmlescape(__('Created by', 'metademands') . ' : ' . htmlescape($author)),
            $html,
        );
        $this->assertStringContainsString('<span class="form-help"', $html);
    }
}
