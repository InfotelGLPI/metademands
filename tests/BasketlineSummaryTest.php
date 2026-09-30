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
use GlpiPlugin\Metademands\Basketline;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use Session;

/**
 * Basket of an order meta-demand (forms/basketline_summary.html.twig): the lines are
 * included by the template, the buttons no longer nest forms in #wizard_form and the
 * posted payload travels as a data attribute instead of an inline script.
 */
class BasketlineSummaryTest extends DbTestCase
{
    private const MARKUP = '</script><script>alert(1)</script>';

    private function renderSummary(array $post): string
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Order',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'is_order'         => 1,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'Reference',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        foreach ([1, 2] as $line) {
            $this->createItem(Basketline::class, [
                'users_id'                          => Session::getLoginUserID(),
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'plugin_metademands_fields_id'      => $field->getID(),
                'line'                              => $line,
                'name'                              => 'text',
                'value'                             => 'Value ' . $line,
            ]);
        }

        $post['metademands_id'] = $metademand->getID();
        ob_start();
        Basketline::displayBasketSummary($metademand->getID(), [$field->fields], $post);

        return (string) ob_get_clean();
    }

    public function testSummaryIncludesTheLinesAndDrivesTheButtonsByAttributes(): void
    {
        $this->login();

        $html = $this->renderSummary(['tickets_id' => '12abc', 'comment' => self::MARKUP]);

        // One form per basket line, none around the summary buttons
        $this->assertSame(2, substr_count($html, '<form'));
        $this->assertMatchesRegularExpression('/name="update_basket_line"\s+value="1"/', $html);
        $this->assertMatchesRegularExpression('/name="update_basket_line"\s+value="2"/', $html);
        // Each line form carries its hidden fields and token, without the core helpers
        // (the summary holds one more meta-demand field, read by the wizard form)
        $this->assertSame(3, substr_count($html, '<input type="hidden" name="form_metademands_id"'));
        $this->assertSame(2, substr_count($html, '<input type="hidden" name="_glpi_csrf_token"'));
        $this->assertStringNotContainsString('onclick', $html);

        // The order payload is an escaped attribute, not an inline script
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('submitOrder', $html);
        $this->assertStringContainsString('data-md-basketline-submit', $html);
        preg_match('/data-md-basketline-order="([^"]*)"/', $html, $matches);
        $order = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertSame(self::MARKUP, $order['post']['comment']);
        $this->assertSame(12, $order['post']['current_ticket_id']);
        $this->assertStringContainsString('current_ticket_id=12&meta_validated=&metademands_id=', $order['wizard_url']);
        $this->assertStringEndsWith('/front/wizard.form.php', $order['wizard_form_url']);

        // Clear, delete of each line and previous post their own fields, with a token
        preg_match_all('/data-md-basketline-post="([^"]*)"/', $html, $matches);
        $this->assertCount(4, $matches[1]);
        [$clear, $delete1, $delete2, $previous] = array_map(
            static fn(string $json) => json_decode(html_entity_decode($json, ENT_QUOTES), true),
            $matches[1],
        );
        $this->assertSame(1, $clear['clear_basket']);
        $this->assertSame(1, $delete1['delete_basket_line']);
        $this->assertSame(2, $delete2['delete_basket_line']);
        $this->assertSame($order['post']['metademands_id'], $delete2['metademands_id']);
        $this->assertSame(1, $previous['clean_form']);
        $this->assertSame(Metademand::STEP_SHOW, $previous['step']);
        foreach ([$clear, $delete1, $delete2, $previous] as $fields) {
            $this->assertNotEmpty($fields['_glpi_csrf_token']);
        }
    }
}
