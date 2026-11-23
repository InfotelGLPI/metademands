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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Fields\Freetable;
use GlpiPlugin\Metademands\Freetablefield;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandPdf;
use Html;
use ReflectionMethod;

/**
 * Free table of the ticket PDF: the totals of an orderfollowup metademand, as in the ticket
 * content, and columns sized after their content within the width of the other fields.
 */
class FreetablePdfTest extends DbTestCase
{
    /**
     * A purchase free table, as orderfollowup builds it, with the lines of the issue
     *
     * @return array{array<string, mixed>, array<int, array<int, array<string, string>>>}
     */
    private function createOrderFreetable(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Purchase',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'freetable',
            'name'                              => 'References',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $rank = 0;
        foreach (['reference' => 'Reference', 'designation' => 'Designation', 'description' => 'Description', 'quantity' => 'Quantity', 'unit_price' => 'Unit price (TTC)'] as $internal_name => $name) {
            $this->createItem(Freetablefield::class, [
                'plugin_metademands_fields_id' => $field->getID(),
                'internal_name'                => $internal_name,
                'type'                         => Freetablefield::TYPE_TEXT,
                'name'                         => $name,
                'is_mandatory'                 => 0,
                'rank'                         => ++$rank,
            ]);
        }

        $lines = [
            ['reference' => '65 443171', 'designation' => 'My first octoplay polydrop', 'description' => 'jeu de construction', 'quantity' => '1', 'unit_price' => '62.16'],
            ['reference' => '69 781171', 'designation' => 'Mini paires de jumelles BUKI', 'description' => 'jumelles sciences', 'quantity' => '10', 'unit_price' => '10.32'],
            ['reference' => '69 781171', 'designation' => 'jumelles BUKI', 'description' => 'matériel sciences', 'quantity' => '10', 'unit_price' => '10.32'],
        ];

        return [$field->fields, [$field->getID() => $lines]];
    }

    public function testOrderFreetableCarriesTheTotals(): void
    {
        $this->login();
        [$elt, $fields] = $this->createOrderFreetable();

        $result = Freetable::displayFieldPDF($elt, $fields, 'References', true);

        $this->assertSame(
            ['Reference', 'Designation', 'Description', 'Quantity', 'Unit price (TTC)', __('Total (TTC)', 'orderfollowup')],
            $result['header'],
        );
        $this->assertSame(
            [
                Html::formatNumber(62.16, false, 2) . ' €',
                Html::formatNumber(103.2, false, 2) . ' €',
                Html::formatNumber(103.2, false, 2) . ' €',
            ],
            array_column($result['rows'], 5),
        );
        // VAT of 20% when orderfollowup has no configuration
        $this->assertSame(
            [
                __('Grand total (TTC)', 'orderfollowup') => Html::formatNumber(268.56, false, 2) . ' €',
                __('Grand total (HT)', 'orderfollowup') . ' ' . __('(if VAT 20%)', 'orderfollowup') => Html::formatNumber(223.8, false, 2) . ' €',
            ],
            $result['totals'],
        );
    }

    public function testFreetableWithoutOrderfollowupHasNoTotals(): void
    {
        $this->login();
        [$elt, $fields] = $this->createOrderFreetable();

        $result = Freetable::displayFieldPDF($elt, $fields, 'References', false);

        $this->assertSame(['Reference', 'Designation', 'Description', 'Quantity', 'Unit price (TTC)'], $result['header']);
        $this->assertSame(['65 443171', 'My first octoplay polydrop', 'jeu de construction', '1', '62.16'], $result['rows'][0]);
        $this->assertSame([], $result['totals']);
    }

    public function testColumnsAreSizedAfterTheirContentWithinTheFields(): void
    {
        $this->login();
        [$elt, $fields] = $this->createOrderFreetable();
        $result = Freetable::displayFieldPDF($elt, $fields, 'References', true);

        $pdf = new MetademandPdf('Title', 'Subtitle', 1);
        $pdf->AddPage();
        $widths = (new ReflectionMethod(MetademandPdf::class, 'getFreeTableWidths'))
            ->invoke($pdf, $result['header'], $result['rows'], (float) $pdf->title_width);

        // As wide as the other fields
        $this->assertEqualsWithDelta((float) $pdf->title_width, array_sum($widths), 0.01);
        // The designations get more room than the quantities
        $this->assertGreaterThan($widths[3], $widths[1]);
        // Every title keeps its longest word on one line
        foreach ($result['header'] as $i => $title) {
            foreach (explode(' ', $title) as $word) {
                $this->assertGreaterThanOrEqual($pdf->GetStringWidth($word), $widths[$i]);
            }
        }

        // The table and its totals are drawn
        $pdf->BasicTableFreeTable($result['header'], $result['rows'], '', $result['totals']);
        $pdf->Close();
        $this->assertStringStartsWith('%PDF', $pdf->Output('', 'S'));
    }

    /**
     * Once the ticket is created, the PDF draws the order lines kept by orderfollowup, whose
     * amounts come formatted (Freeinput::displayFieldPDF()): the grand totals are summed from the
     * stored lines, a formatted amount with a thousands separator not being a number
     */
    public function testOrderLinesOfOrderfollowupAreDrawnAsTheFreeTable(): void
    {
        $this->login();
        $unit_price = __('Unit price (TTC)', 'orderfollowup');
        $line_total = __('Total (TTC)', 'orderfollowup');
        $items = [
            12 => [
                'Reference'   => '65 443171',
                'Designation' => 'My first octoplay polydrop',
                'Description' => 'jeu de construction',
                'Quantity'    => 2,
                $unit_price   => Html::formatNumber(617.25, false, 2),
                $line_total   => Html::formatNumber(1234.5, false, 2),
            ],
        ];
        $lines = [12 => ['unit_price' => '617.25', 'quantity' => '2']];

        $pdf = new MetademandPdf('Title', 'Subtitle', 1);
        $pdf->AddPage();
        [$header, $data, $totals] = (new ReflectionMethod(MetademandPdf::class, 'getOrderLinesTable'))
            ->invoke($pdf, $items, $lines);

        $this->assertSame(['Reference', 'Designation', 'Description', 'Quantity', $unit_price, $line_total], $header);
        $this->assertSame(
            ['65 443171', 'My first octoplay polydrop', 'jeu de construction', '2', Html::formatNumber(617.25, false, 2) . ' €', Html::formatNumber(1234.5, false, 2) . ' €'],
            $data[0],
        );
        $this->assertSame(
            [
                __('Grand total (TTC)', 'orderfollowup') => Html::formatNumber(1234.5, false, 2) . ' €',
                __('Grand total (HT)', 'orderfollowup') . ' ' . __('(if VAT 20%)', 'orderfollowup') => Html::formatNumber(1028.75, false, 2) . ' €',
            ],
            $totals,
        );

        $pdf->BasicTableFreeTable($header, $data, '', $totals);
        $pdf->Close();
        $this->assertStringStartsWith('%PDF', $pdf->Output('', 'S'));
    }
}
