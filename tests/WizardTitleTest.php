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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

/**
 * Frame of the wizard (wizard.html.twig): the title card, the models and drafts
 * drop-down and the abort message are included with their context, and the form
 * closes itself (GLPI 12: no CSRF token).
 */
class WizardTitleTest extends DbTestCase
{
    /**
     * @param array<string, mixed> $fields
     */
    private function createMetademand(array $fields = []): Metademand
    {
        return $this->createItem(Metademand::class, $fields + [
            'name'             => 'Wizard title',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderWizard(array $options): string
    {
        ob_start();
        (new Wizard())->showWizard($options);

        return (string) ob_get_clean();
    }

    public function testTitleCardSanitizesTheDesignerComment(): void
    {
        $this->login();

        $metademand = $this->createMetademand([
            'name'    => 'Title <i>x</i>',
            'comment' => '<p><b>Bold</b></p><script>alert(1)</script>',
        ]);

        $html = $this->renderWizard([
            'step'           => Metademand::STEP_SHOW,
            'metademands_id' => $metademand->getID(),
        ]);

        $this->assertStringContainsString('Title &lt;i&gt;x&lt;/i&gt;', $html);
        $this->assertStringContainsString('<b>Bold</b>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        // The models and drafts drop-down is included inside the title card
        $this->assertStringContainsString('id="divnavforms"', $html);
        $this->assertStringContainsString('mydraft-withtitle', $html);
        // The wizard form closes itself, without CSRF token (GLPI 12)
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
        $this->assertSame(1, substr_count($html, '</form>'));
    }

    public function testPreviewHasNoModelsAndDrafts(): void
    {
        $this->login();

        $metademand = $this->createMetademand();

        $html = $this->renderWizard([
            'step'           => Metademand::STEP_SHOW,
            'metademands_id' => $metademand->getID(),
            'preview'        => true,
        ]);

        $this->assertStringContainsString('md-title', $html);
        $this->assertStringNotContainsString('divnavforms', $html);
    }

    public function testHiddenTitleKeepsTheDropDownOutsideTheCard(): void
    {
        $this->login();

        $metademand = $this->createMetademand(['hide_title' => 1]);

        $html = $this->renderWizard([
            'step'           => Metademand::STEP_SHOW,
            'metademands_id' => $metademand->getID(),
        ]);

        $this->assertStringNotContainsString('md-title', $html);
        $this->assertStringContainsString('mydraft-withouttitle', $html);
    }

    public function testIllustrationIsDrawnByTheTemplate(): void
    {
        $this->login();

        $metademand = $this->createMetademand(['illustration' => 'request-service']);

        $html = $this->renderWizard([
            'step'           => Metademand::STEP_SHOW,
            'metademands_id' => $metademand->getID(),
            'preview'        => true,
        ]);

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('margin-top: -45px', $html);
    }

    public function testAbortMessageIsEscapedAndLeavesTheFormOpen(): void
    {
        $html = TemplateRenderer::getInstance()->render('@metademands/wizard/wizard.html.twig', [
            'form_action'       => '/wizard.form.php',
            'models_and_drafts' => null,
            'maintenance'       => false,
            'preview'           => false,
            'breadcrumb'        => null,
            'hidden_fields'     => [],
            'header'            => '',
            'icon'              => '',
            'is_fa_icon'        => false,
            'metademand_title'  => null,
            'requester_id'      => null,
            'abort'             => 'message',
            'abort_message'     => '<b>Denied</b>',
            'steps'             => '',
        ]);

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('&lt;b&gt;Denied&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('</form>', $html);
        $this->assertStringNotContainsString('_glpi_csrf_token', $html);
    }

    public function testCategoryDetailsModalIsPrintedByTheTemplate(): void
    {
        $html = TemplateRenderer::getInstance()->render('@metademands/wizard/metademand_title.html.twig', [
            'background_color'      => '',
            'style_title_color'     => '',
            'icon_color'            => '',
            'margin_top'            => 'margin-top: 5px',
            'illustration'          => '',
            'icon'                  => '',
            'is_fa_icon'            => false,
            'title'                 => 'Title',
            'cat_name'              => '',
            'category_completename' => '',
            'category_details_id'   => 12,
            'category_details_url'  => '/categorydetail.form.php?category_id=12',
            'settings_url'          => '',
            'comment'               => '',
            'models_and_drafts'     => null,
        ]);

        $this->assertStringContainsString('data-bs-target="#categorydetails12"', $html);
        $this->assertStringContainsString('id="categorydetails12"', $html);
        $this->assertStringContainsString('id="iframecategorydetails12"', $html);
        $this->assertStringContainsString('/categorydetail.form.php', $html);
    }
}
