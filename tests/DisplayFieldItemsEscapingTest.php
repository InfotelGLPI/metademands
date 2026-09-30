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

use GlpiPlugin\Metademands\Fields\Number;
use GlpiPlugin\Metademands\Fields\Range;
use GlpiPlugin\Metademands\Fields\Time;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Field labels and field values are stored as plain text and end up in the markup built by
 * every Fields\*::displayFieldItems() implementation. They are escaped at each of those sinks
 * rather than once at their source, because Metademand::getFieldValue() hands some labels
 * back to Twig callers.
 *
 * The types migrated to the ticket_content/*.html.twig templates are rendered, and their
 * escaping checked, by TicketContentCellsTest in the integration suite (the templates need
 * the GLPI runtime). This runtime-free test keeps the value filtering of the typed fields and
 * a static guard against any concatenating sink that forgets to escape its label.
 */
class DisplayFieldItemsEscapingTest extends TestCase
{
    /**
     * Number, Range and Time drop anything that is not the kind of value they advertise, so a
     * forged submission never reaches the markup at all.
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function provideTypedFieldTypes(): array
    {
        return [
            'number' => [Number::class, '<b>1</b>'],
            'range'  => [Range::class, '1 <b>'],
            'time'   => [Time::class, '12:30<b>'],
        ];
    }

    /**
     * @param class-string $field_class
     */
    #[DataProvider('provideTypedFieldTypes')]
    public function testForgedValueIsDropped(string $field_class, string $forged_value): void
    {
        $this->assertSame('', $field_class::getFieldValue(['value' => $forged_value]));
    }

    /**
     * Static guard: a field type added later must escape its label like every existing one.
     * displayFieldItems() implementations all build their markup by appending to
     * $result[...]['content'], so a bare $label appended there is the defect this catches.
     */
    public function testNoFieldTypeAppendsAnUnescapedLabel(): void
    {
        $offenders = [];

        foreach (glob(dirname(__DIR__) . '/src/Fields/*.php') ?: [] as $path) {
            $source = (string) file_get_contents($path);
            foreach (explode("\n", $source) as $number => $line) {
                if (preg_match('/\[\'content\'\]\s*\.=\s*\$label\d*\s*;/', $line) === 1) {
                    $offenders[] = basename($path) . ':' . ($number + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These sinks append a label without escaping it: ' . implode(', ', $offenders),
        );
    }
}
