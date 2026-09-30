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
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Metademands\Form;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;
use Session;

/**
 * Pieces of the wizard rendered by Wizard: the designer-defined rich HTML is
 * sanitized by |safe_html, the core widgets and the sub-templates are called in place.
 */
class WizardTemplatesTest extends DbTestCase
{
    private const RICH = '<p><b>Bold</b></p><script>alert(1)</script>';

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context): string
    {
        return TemplateRenderer::getInstance()->render('@metademands/wizard/' . $template . '.html.twig', $context);
    }

    public function testListDropdownIsPrintedByTheTemplate(): void
    {
        $this->login();

        $html = $this->render('metademands_list', [
            'display_cards' => false,
            'meta_type'     => 1,
            'step_show'     => 2,
            'metademands'   => [0 => '-----', 7 => 'Meta <b>x</b>'],
        ]);

        $this->assertMatchesRegularExpression('/<select[^>]*name=["\']metademands_id["\']/', $html);
        $this->assertStringContainsString('Meta &lt;b&gt;x&lt;/b&gt;', $html);
    }

    public function testListCardsSanitizeTheCommentAndIncludeTheMostUsed(): void
    {
        $html = $this->render('metademands_list', [
            'display_cards' => true,
            'meta_type'     => 1,
            'most_used'     => [[
                'url'        => '/wizard.form.php?metademands_id=3',
                'icon'       => 'ti-share',
                'is_fa_icon' => false,
                'name'       => 'Most <i>used</i>',
            ]],
            'entries'       => [[
                'url'          => '/wizard.form.php?metademands_id=4',
                'tooltip'      => 'Bold',
                'icon'         => 'ti-share',
                'is_fa_icon'   => false,
                'icon_color'   => '',
                'name'         => 'Card',
                'comment'      => self::RICH,
                'drafts_label' => '',
            ]],
        ]);

        $this->assertStringContainsString('Most &lt;i&gt;used&lt;/i&gt;', $html);
        $this->assertStringContainsString('<b>Bold</b>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function testListWithoutMostUsedPrintsNoFavourites(): void
    {
        $html = $this->render('metademands_list', [
            'display_cards' => true,
            'meta_type'     => 1,
            'most_used'     => [],
            'entries'       => [],
        ]);

        $this->assertStringNotContainsString('md-fav-icon-stack', $html);
    }

    public function testBlockBreakBuildsItsTooltipAndConfigLink(): void
    {
        $html = $this->render('block_break', [
            'block'            => 2,
            'is_preview'       => true,
            'debug'            => false,
            'preview_color'    => 'ff0000',
            'background_color' => '',
            'config_url'       => '/field.form.php?id=5',
            'title'            => [
                'color'   => '#000',
                'label'   => 'Block <i>2</i>',
                'id'      => 5,
                'label2'  => '<b>Help</b><script>alert(2)</script>',
                'comment' => self::RICH,
            ],
        ]);

        $this->assertStringContainsString('Block &lt;i&gt;2&lt;/i&gt;', $html);
        $this->assertStringContainsString('<a href="/field.form.php?id=5"><i class="ti ti-settings"></i></a>', $html);
        $this->assertStringContainsString('class="form-help" data-bs-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-bs-title="&lt;b&gt;Help&lt;/b&gt;"', $html);
        $this->assertStringNotContainsString('alert(2)', $html);
        $this->assertStringContainsString('<label><i><p><b>Bold</b></p></i></label>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function testBlockBreakWithoutConfigUrlHasNoLink(): void
    {
        $html = $this->render('block_break', [
            'block'            => 2,
            'is_preview'       => false,
            'debug'            => false,
            'preview_color'    => '',
            'background_color' => '',
            'config_url'       => '',
            'title'            => ['color' => '#000', 'label' => 'Block', 'id' => 5, 'label2' => '', 'comment' => ''],
        ]);

        $this->assertStringNotContainsString('ti-settings', $html);
        $this->assertStringNotContainsString('form-help', $html);
        $this->assertStringNotContainsString('<label>', $html);
    }

    public function testBreadcrumbEncodesItsScriptForTheInlineScript(): void
    {
        $script = "$('#launchrootype1').click(function() {  window.location = '/x?a=1&b=2</script>';  });";
        $html = $this->render('wizard_breadcrumb', [
            'tree_name'       => '<a id="launchrootype1">Root</a>',
            'tree_script'     => $script,
            'alert_class'     => '',
            'alert_style'     => '',
            'display_warning' => '',
            'faq_url'         => '',
        ]);

        $this->assertStringContainsString(
            'document.createTextNode(' . json_encode($script, JSON_HEX_TAG | JSON_HEX_AMP) . ')',
            $html,
        );
        $this->assertStringNotContainsString('&b=2', $html);
        $this->assertSame(1, substr_count($html, '</script>'));
    }

    public function testCancelFormPostsTheStepFormWithItsToken(): void
    {
        $this->login();

        $html = $this->render('form_nav_buttons', [
            'use_as_step'       => 0,
            'use_draft'         => false,
            'cancel_form'       => ['url' => '/plugins/metademands/front/stepform.form.php', 'stepforms_id' => 42],
            'show_step_circles' => false,
            'step_count'        => 1,
        ]);

        $this->assertStringContainsString('onclick="submitGetLink(&quot;\/plugins\/metademands\/front\/stepform.form.php&quot;', $html);
        $this->assertStringContainsString('&quot;delete_form_from_list&quot;:&quot;delete_form_from_list&quot;', $html);
        $this->assertStringContainsString('&quot;plugin_metademands_stepforms_id&quot;:42', $html);
        $this->assertMatchesRegularExpression('/&quot;_glpi_csrf_token&quot;:&quot;[0-9a-f]{64}&quot;/', $html);
        $this->assertStringContainsString(_x('button', 'Cancel form', 'metademands'), $html);
    }

    public function testNoCancelFormWithoutStepForm(): void
    {
        $html = $this->render('form_nav_buttons', [
            'use_as_step'       => 0,
            'use_draft'         => false,
            'cancel_form'       => null,
            'show_step_circles' => false,
            'step_count'        => 1,
        ]);

        $this->assertStringNotContainsString('submitGetLink', $html);
    }

    public function testNextButtonCarriesItsTitlesAsEscapedData(): void
    {
        $titles = ['next' => ['before' => '', 'label' => '</button><b>x</b>', 'after' => 'ti ti-chevron-right']];

        $html = $this->render('form_nav_buttons', [
            'use_as_step'       => 0,
            'use_draft'         => false,
            'cancel_form'       => null,
            'show_step_circles' => false,
            'step_count'        => 1,
            'button_titles'     => $titles,
        ]);

        $this->assertSame(1, preg_match('/<button[^>]*id="nextBtn"[^>]*data-md-titles="([^"]*)"/', $html, $matches));
        $this->assertStringNotContainsString('<b>', $matches[1]);
        $this->assertSame($titles, json_decode(html_entity_decode($matches[1], ENT_QUOTES), true));
        $this->assertStringContainsString('data-md-title-kind="next"', $html);
    }

    public function testButtonTitlesFollowTheKindOfMetademand(): void
    {
        $this->login();

        $titles = [];
        foreach (['plain' => [0, 0], 'order' => [1, 0], 'basket' => [0, 1]] as $kind => [$is_order, $is_basket]) {
            $metademand = $this->createItem(Metademand::class, [
                'name'             => 'Titles ' . $kind,
                'entities_id'      => $this->getTestRootEntity(true),
                'object_to_create' => 'Ticket',
                'type'             => 0,
                'is_order'         => $is_order,
                'is_basket'        => $is_basket,
            ]);
            $titles[$kind] = Wizard::getButtonTitles($metademand);
        }

        $post = ['before' => 'ti ti-device-floppy', 'label' => _x('button', 'Save & Post', 'metademands'), 'after' => ''];
        $this->assertSame($post, $titles['plain']['submit']);
        $this->assertSame($post, $titles['plain']['post']);
        $this->assertSame($post, $titles['plain']['conditions_submit']);
        $this->assertSame(
            ['before' => '', 'label' => __('Next', 'metademands'), 'after' => 'ti ti-chevron-right'],
            $titles['plain']['next'],
        );
        $this->assertSame('ti ti-device-floppy', $titles['plain']['savenext']['before']);

        $add = ['before' => 'ti ti-plus', 'label' => _x('button', 'Add to basket', 'metademands'), 'after' => ''];
        $this->assertSame($add, $titles['order']['submit']);
        $this->assertSame($add, $titles['order']['conditions_submit']);

        $this->assertSame(_x('button', 'See basket summary & send it', 'metademands'), $titles['basket']['submit']['label']);
        $this->assertSame('ti ti-device-floppy', $titles['basket']['submit']['before']);
        // The conditions compare the button with the plain submit title of a basket
        $this->assertSame($post, $titles['basket']['conditions_submit']);

        // No markup in the labels: the script creates the icons
        foreach ($titles as $by_kind) {
            foreach ($by_kind as $title) {
                $this->assertStringNotContainsString('<', $title['label']);
            }
        }
    }

    public function testFormBlocksIncludeTheTabBar(): void
    {
        $html = $this->render('form_blocks', [
            'blocks'      => [['in_step' => true, 'html' => '<div class="card">B</div>']],
            'wrap_nostep' => true,
            'is_basket'   => false,
            'tabs'        => ['blocks' => [1 => 'Tab <i>1</i>'], 'hidden_blocks' => [], 'block_id' => 1],
        ]);

        $this->assertStringContainsString('id="ablock1"', $html);
        $this->assertStringContainsString('Tab &lt;i&gt;1&lt;/i&gt;', $html);
        // .tab-step.firstChild has to be the card of the block
        $this->assertStringContainsString('<div class="tab-step"><div class="card">B</div></div>', $html);

        $html = $this->render('form_blocks', [
            'blocks'      => [],
            'wrap_nostep' => false,
            'is_basket'   => false,
            'tabs'        => null,
        ]);
        $this->assertStringNotContainsString('tabs-container', $html);
    }

    public function testPreviousDataIsSanitized(): void
    {
        $html = $this->render('form_previous_data', [
            'title'      => 'Previous',
            'user_label' => '',
            'user_name'  => '',
            'content'    => self::RICH,
        ]);

        $this->assertStringContainsString('<b>Bold</b>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }
    public function testModelsAndDraftsIncludeTheListsOfEachTab(): void
    {
        $this->login();
        $user_id = Session::getLoginUserID();

        $html = $this->render('models_and_drafts', [
            'toggle_class' => 'mydraft-withtitle',
            'toggle_title' => 'Your forms',
            'tabs'         => [
                [
                    'id'    => 'divformmodels',
                    'label' => 'Your <b>models</b>',
                    'lists' => [
                        Form::getPrivateFormsContext($user_id, 0),
                        Form::getPublicFormsContext(0),
                    ],
                ],
                [
                    'id'    => 'divdrafts',
                    'label' => 'Your drafts',
                    'lists' => [Draft::getUserDraftsContext($user_id, 0)],
                ],
            ],
        ]);

        $this->assertStringContainsString('Your &lt;b&gt;models&lt;/b&gt;', $html);
        $this->assertMatchesRegularExpression('/id="divformmodels"[^>]*class="tab-pane fade show active"/', $html);
        $this->assertMatchesRegularExpression('/id="divdrafts"[^>]*class="tab-pane fade"/', $html);

        // Each tab body is the list template the class renders on its own.
        $private = Form::getPrivateFormsContext($user_id, 0);
        $this->assertSame('@metademands/forms/private_models_list.html.twig', $private['template']);
        $drafts = Draft::showDraftsForUserMetademand($user_id, 0);
        $this->assertNotSame('', trim($drafts));
        $this->assertStringContainsString(trim(strtok($drafts, "\n")), $html);
    }
}
