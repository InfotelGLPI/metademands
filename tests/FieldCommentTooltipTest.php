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
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;
use Ticket;

/**
 * The comment of a field is shown in a tooltip next to its label (Field::getTextHints()), but
 * in the text inputs and the plain textareas, whose placeholder it is: "use_richtext", a
 * parameter of the textareas, hid it for every other type.
 */
class FieldCommentTooltipTest extends DbTestCase
{
    private function addField(Metademand $metademand, string $type, string $item, int $order, array $parameters = []): void
    {
        $id = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => 0,
            'type'                              => $type,
            'item'                              => $item,
            'name'                              => strtoupper($type) . 'FIELD',
            'comment'                           => '<p>' . strtoupper($type) . 'COMMENT</p>',
            'rank'                              => 1,
            'order'                             => $order,
        ], ['item'])->getID();
        $this->createItem(FieldParameter::class, $parameters + ['plugin_metademands_fields_id' => $id, 'is_mandatory' => 0]);
    }

    public function testTheCommentIsATooltipButForThePlaceholders(): void
    {
        $this->login();
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Tooltips ' . mt_rand(),
            'entities_id'      => 0,
            'object_to_create' => 'Ticket',
            'type'             => Ticket::DEMAND_TYPE,
        ]);
        $this->addField($metademand, 'dropdown_object', 'User', 1);
        $this->addField($metademand, 'date', '', 2);
        // A plain textarea: its comment is its placeholder
        $this->addField($metademand, 'textarea', '', 3, ['use_richtext' => 0]);
        $this->addField($metademand, 'text', '', 4);

        $data = Metademand::constructMetademands($metademand->getID());
        ob_start();
        Wizard::constructForm(
            $metademand->getID(),
            $data,
            Metademand::STEP_SHOW,
            $data[Metademand::STEP_SHOW][$metademand->getID()]['form'],
            true,
        );
        $html = (string) ob_get_clean();

        $tooltip = static fn(string $text): string => '/class="form-help" data-bs-toggle="tooltip"[^>]*title="[^"]*' . $text . '/';
        $this->assertMatchesRegularExpression($tooltip('DROPDOWN_OBJECTCOMMENT'), $html);
        $this->assertMatchesRegularExpression($tooltip('DATECOMMENT'), $html);
        // Placeholders
        $this->assertDoesNotMatchRegularExpression($tooltip('TEXTAREACOMMENT'), $html);
        $this->assertDoesNotMatchRegularExpression($tooltip('TEXTCOMMENT'), $html);
    }
}
