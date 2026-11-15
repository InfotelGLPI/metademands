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
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Metademands\Metademand;
use Session;

/**
 * Drafts of a meta-demand (forms/drafts_list, draft_modal and draft_show): the input,
 * buttons and hidden inputs are written by the templates.
 */
class DraftTemplatesTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Drafts',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $datas
     */
    private function renderDraft(array $datas): string
    {
        ob_start();
        Draft::showDraft($datas + [
            'plugin_metademands_drafts_id'   => 0,
            'plugin_metademands_drafts_name' => 'Draft',
        ]);

        return (string) ob_get_clean();
    }

    public function testDraftListWritesItsInputsAndButtons(): void
    {
        $this->login();

        $id = $this->createMetademand()->getID();
        $_SESSION['plugin_metademands'][$id]['plugin_metademands_drafts_id'] = 5;

        $html = Draft::showDraftsForUserMetademand(Session::getLoginUserID(), $id);

        $this->assertStringContainsString('<input type="text" name="draft_name" value="" maxlength="250"', $html);
        $this->assertMatchesRegularExpression(
            '/<button type="submit" value="' . preg_quote(_x('button', 'Save as draft', 'metademands'), '/')
            . '" name="save_draft" form=""\s+id="submitSave" class="btn btn-success btn-sm">/',
            $html,
        );
        $this->assertStringContainsString('name="clean_form"', $html);
        $this->assertStringContainsString(
            '<input type="hidden" name="plugin_metademands_drafts_id" value="5" id="plugin_metademands_drafts_id">',
            $html,
        );
    }

    public function testDraftListButtonsCarryNoInlineHandler(): void
    {
        $this->login();

        $id = $this->createMetademand()->getID();
        $name = '<b>x</b>"\'';
        $draft = $this->createItem(Draft::class, [
            'name'                              => $name,
            'plugin_metademands_metademands_id' => $id,
            'users_id'                          => Session::getLoginUserID(),
            'date'                              => '2026-11-14 10:00:00',
        ]);
        // The loaded draft also gets its "save" button
        $_SESSION['plugin_metademands'][$id]['plugin_metademands_drafts_id'] = $draft->getID();

        $html = Draft::showDraftsForUserMetademand(Session::getLoginUserID(), $id);

        // The CSP blocks inline handlers: the buttons only carry data attributes
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString(
            'data-metademands-action="loadDraft" data-metademands-args="[' . $draft->getID() . ']"',
            $html,
        );
        $this->assertStringContainsString(
            'data-metademands-action="deleteDraft" data-metademands-args="[' . $draft->getID() . ']"',
            $html,
        );
        // The name, once a JS string of the handler, is JSON escaped into the attribute
        $this->assertMatchesRegularExpression(
            '/data-metademands-update-draft="(\d+)" data-metademands-args="'
            . preg_quote(htmlescape(json_encode([$draft->getID(), $name])), '/') . '"/',
            $html,
        );
        // ... and the listener bound right after it, on the same random number
        preg_match('/data-metademands-update-draft="(\d+)"/', $html, $matches);
        $this->assertStringContainsString(
            "document.querySelectorAll('[data-metademands-update-draft=\"" . $matches[1] . "\"]')",
            $html,
        );
    }

    public function testDraftModalWritesItsInputAndButton(): void
    {
        $this->login();

        $html = (string) Draft::createDraftModalWindow('my_new_draft', ['display' => false]);

        $this->assertStringContainsString(
            '<input type="text" name="draft_name" value="" maxlength="250" size="40" class="draft_name"',
            $html,
        );
        // Called by the delegated listener of public/scripts/csp_handlers.js (CSP)
        $this->assertStringContainsString('data-metademands-action="saveMyDraft"', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('<span>' . _x('button', 'Save as draft', 'metademands') . '</span>', $html);
    }

    public function testShowDraftWritesItsHiddenInputs(): void
    {
        $this->login();

        $id = $this->createMetademand()->getID();

        $html = $this->renderDraft(['plugin_metademands_id' => $id]);

        $this->assertStringContainsString('<input type="hidden" name="_glpi_csrf_token"', $html);
        $this->assertStringContainsString('<input type="hidden" name="step" value="1">', $html);
        $this->assertStringContainsString('<input type="hidden" name="metademands_id" value="' . $id . '">', $html);
        $this->assertStringContainsString(
            '<input type="hidden" name="_users_id_requester" value="' . Session::getLoginUserID() . '">',
            $html,
        );
        $this->assertStringNotContainsString('name="previous"', $html);
    }

    public function testShowDraftWithoutFormOffersToGoBack(): void
    {
        $this->login();

        // No step is built without a metademand
        $html = $this->renderDraft(['plugin_metademands_id' => 0]);

        $this->assertStringContainsString(__('No results found'), $html);
        $this->assertMatchesRegularExpression(
            '/<button type="submit" name="previous" value="' . __('Previous') . '" class="btn btn-primary">\s*<span>'
            . __('Previous') . '<\/span>\s*<\/button>/',
            $html,
        );
        $this->assertStringContainsString('<input type="hidden" name="previous_metademands_id" value="0">', $html);
    }

    public function testDraftInputShowsTheButtonAndItsModal(): void
    {
        $this->login();

        ob_start();
        Draft::showDraftInput(Draft::DEFAULT_MODE);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString(_x('button', 'Save as draft', 'metademands'), $html);
        $this->assertStringContainsString('my_new_draft', $html);
        // Shown in the wizard...
        $this->assertMatchesRegularExpression('/<div id="div_save_draft" style="(?![^"]*display:none)[^"]*"/', $html);

        // ... but a free table shows it with its first line only
        ob_start();
        Draft::showDraftInput(Draft::DEFAULT_MODE, true);
        $html = (string) ob_get_clean();
        $this->assertMatchesRegularExpression('/<div id="div_save_draft" style="[^"]*display:none"/', $html);
    }
}
