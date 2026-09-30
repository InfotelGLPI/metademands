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
use GlpiPlugin\Metademands\Basketobject;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Basket;
use GlpiPlugin\Metademands\Fields\Checkbox;
use GlpiPlugin\Metademands\Fields\Date;
use GlpiPlugin\Metademands\Fields\Dateinterval;
use GlpiPlugin\Metademands\Fields\Datetime;
use GlpiPlugin\Metademands\Fields\Datetimeinterval;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;
use GlpiPlugin\Metademands\Fields\Dropdownobject;
use GlpiPlugin\Metademands\Fields\Email;
use GlpiPlugin\Metademands\Fields\Freetable;
use GlpiPlugin\Metademands\Fields\Ldapdropdown;
use GlpiPlugin\Metademands\Fields\Link;
use GlpiPlugin\Metademands\Fields\Number;
use GlpiPlugin\Metademands\Fields\Radio;
use GlpiPlugin\Metademands\Fields\Range;
use GlpiPlugin\Metademands\Fields\Signature;
use GlpiPlugin\Metademands\Fields\Tel;
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Fields\Time;
use GlpiPlugin\Metademands\Fields\Title;
use GlpiPlugin\Metademands\Fields\Titleblock;
use GlpiPlugin\Metademands\Fields\Url;
use GlpiPlugin\Metademands\Fields\Yesno;
use GlpiPlugin\Metademands\Freetablefield;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

/**
 * Ticket content cells rendered through the ticket_content/*.html.twig templates (lot B of
 * docs/TWIG_MIGRATION.md). The markup is stored in the ticket and sent by notifications:
 * labels and values must be escaped, and the inline style of the title cell must survive.
 */
class TicketContentCellsTest extends DbTestCase
{
    private const XSS_LABEL = '<img src=x onerror=alert(1)>"\'';


    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function provideCellFieldTypes(): array
    {
        return [
            'text'   => [Text::class, 'Some text'],
            'tel'    => [Tel::class, '0102030405'],
            'url'    => [Url::class, 'https://example.org/'],
            'email'  => [Email::class, 'someone@example.org'],
            'number' => [Number::class, '42'],
            'date'     => [Date::class, '2026-09-22'],
            'datetime' => [Datetime::class, '2026-09-22 08:15:00'],
            'range'  => [Range::class, '7'],
            'time'   => [Time::class, '12:30'],
            'yesno'  => [Yesno::class, '2'],
            'link'   => [Link::class, 'https://example.org/'],
            'ldapdropdown' => [Ldapdropdown::class, 'CN=Someone,DC=example,DC=org'],
        ];
    }

    /**
     * @param class-string $field_class
     */
    #[DataProvider('provideCellFieldTypes')]
    public function testCellsAreEscapedAndStyled(string $field_class, string $value): void
    {
        $content = $this->renderContent($field_class, self::XSS_LABEL, $value);

        $this->assertStringNotContainsString('<img', $content);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $content);
        $this->assertStringStartsWith('<td class="title" style="color:#red;width: 40%;" colspan="1">', $content);
        $this->assertStringContainsString('<td colspan="1">', $content);
    }

    /**
     * @param class-string $field_class
     */
    #[DataProvider('provideCellFieldTypes')]
    public function testCellsOutsideTableLayout(string $field_class, string $value): void
    {
        $content = $this->renderContent($field_class, 'Label', $value, false);

        $this->assertStringStartsWith('Label', $content);
        $this->assertStringNotContainsString('<td', $content);
    }

    /**
     * Textarea keeps its rich-text value but drops what the core sanitizer drops.
     */
    public function testTextareaBlockSanitizesItsValue(): void
    {
        $content = $this->renderContent(
            Textarea::class,
            self::XSS_LABEL,
            '<p>Hello <b>world</b></p><script>alert(1)</script>',
        );

        $this->assertStringStartsWith('<tr><th class="title" style="color:#red;width: 40%;" colspan="2">', $content);
        $this->assertStringNotContainsString('<img', $content);
        $this->assertStringContainsString('<b>world</b>', $content);
        $this->assertStringNotContainsString('<script', $content);
        // The value row is left open: Metademand::formatFields() closes it.
        $this->assertStringEndsWith('</td>', $content);
    }

    /**
     * Link builds an anchor out of the submitted value: the anchor is markup, the value inside
     * it is not.
     */
    public function testLinkValueCannotBreakOutOfTheAnchor(): void
    {
        $content = $this->renderContent(Link::class, 'Label', 'https://example.org/"><script>alert(1)</script>');

        $this->assertStringContainsString('<a href="https://example.org/&quot;&gt;&lt;script&gt;', $content);
        $this->assertStringNotContainsString('<script>', $content);
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function provideTitleFieldTypes(): array
    {
        return [
            'title'      => [Title::class],
            'titleblock' => [Titleblock::class],
        ];
    }

    /**
     * Title fields render their label alone, in a <th> without the title style, or bare
     * outside the table layout.
     *
     * @param class-string $field_class
     */
    #[DataProvider('provideTitleFieldTypes')]
    public function testTitleIsAHeadingCell(string $field_class): void
    {
        $escaped = htmlspecialchars(self::XSS_LABEL, ENT_QUOTES, 'UTF-8');

        $this->assertSame('<th colspan="2">' . $escaped . '</th>', $this->renderContent($field_class, self::XSS_LABEL, ''));
        $this->assertSame($escaped, $this->renderContent($field_class, self::XSS_LABEL, '', false));
    }

    /**
     * Signature renders the picture of a path the session is allowed to use, and nothing
     * for any other path.
     */
    public function testSignatureRendersOnlyAnAllowedPicture(): void
    {
        $path = '_metademands_test/' . uniqid('sign_') . '.png';
        mkdir(GLPI_PICTURE_DIR . '/_metademands_test', 0o777, true);
        file_put_contents(GLPI_PICTURE_DIR . '/' . $path, 'png');

        try {
            $forged = $this->renderContent(Signature::class, self::XSS_LABEL, $path);
            $this->assertStringNotContainsString('<img', $forged);
            $this->assertStringEndsWith('<td colspan="1"></td>', $forged);

            Signature::registerUpload($path);
            $allowed = $this->renderContent(Signature::class, self::XSS_LABEL, $path);
            $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $allowed);
            $this->assertMatchesRegularExpression('#<td colspan="1"><img src="[^"<>]+"></td>$#', $allowed);
        } finally {
            Signature::forgetUpload($path);
            unlink(GLPI_PICTURE_DIR . '/' . $path);
            rmdir(GLPI_PICTURE_DIR . '/_metademands_test');
        }
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function provideChoiceFieldTypes(): array
    {
        return [
            'checkbox' => [Checkbox::class, '{"5":"on"}'],
            'radio'    => [Radio::class, '5'],
        ];
    }

    /**
     * Checkbox and Radio render the label and the name of the chosen custom value, both
     * escaped.
     *
     * @param class-string $field_class
     */
    #[DataProvider('provideChoiceFieldTypes')]
    public function testChoiceRendersItsCustomValue(string $field_class, string $value): void
    {
        $content = $this->renderContent($field_class, 'Label', $value, true, [
            'custom_values' => [['id' => 5, 'name' => self::XSS_LABEL]],
        ]);

        $this->assertStringNotContainsString('<img', $content);
        $this->assertStringStartsWith('<td class="title" style="color:#red;width: 40%;" colspan="1">Label</td>', $content);
        $this->assertStringEndsWith('<td colspan="1">&lt;img src=x onerror=alert(1)&gt;&quot;&#039;</td>', $content);
    }

    /**
     * A Radio without custom values renders its label alone, in a value cell.
     */
    public function testRadioWithoutCustomValuesRendersALoneCell(): void
    {
        $escaped = htmlspecialchars(self::XSS_LABEL, ENT_QUOTES, 'UTF-8');

        $this->assertSame('<td colspan="1">' . $escaped . '</td>', $this->renderContent(Radio::class, self::XSS_LABEL, '1'));
        $this->assertSame($escaped, $this->renderContent(Radio::class, self::XSS_LABEL, '1', false));
    }

    /**
     * Dropdown and Dropdownobject render the name of the chosen item, escaped.
     */
    public function testDropdownRendersTheItemName(): void
    {
        $location = $this->createItem(Location::class, [
            'name'        => self::XSS_LABEL,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        foreach ([Dropdown::class, Dropdownobject::class] as $field_class) {
            $content = $this->renderContent($field_class, 'Label', (string) $location->getID(), true, ['item' => Location::class]);

            $this->assertStringNotContainsString('<img', $content, $field_class);
            $this->assertStringContainsString('<td colspan="1">&lt;img src=x onerror=alert(1)&gt;', $content, $field_class);
        }
    }

    /**
     * Dropdownmeta renders the name of the chosen ITIL value.
     */
    public function testDropdownmetaRendersThePriorityName(): void
    {
        $content = $this->renderContent(Dropdownmeta::class, 'Label', '5', true, ['item' => 'priority', 'hidden' => 0]);

        $this->assertStringEndsWith('<td colspan="1">' . htmlspecialchars(\Ticket::getPriorityName(5), ENT_QUOTES) . '</td>', $content);
    }

    /**
     * User fields render the chosen informations of the user: space-separated for
     * Dropdownobject, as a nested table for Dropdownmultiple. All of them are escaped.
     */
    public function testUserFieldsRenderTheChosenInformations(): void
    {
        $users = [];
        foreach (['First', 'Second'] as $firstname) {
            $users[] = $this->createItem(User::class, [
                'name'      => uniqid('md_user_'),
                'realname'  => self::XSS_LABEL,
                'firstname' => $firstname,
            ])->getID();
        }
        $extra = ['item' => User::class, 'informations_to_display' => '["realname","firstname"]'];
        $escaped = htmlspecialchars(self::XSS_LABEL, ENT_QUOTES, 'UTF-8');

        $object = $this->renderContent(Dropdownobject::class, 'Label', (string) $users[0], true, $extra);
        $this->assertStringEndsWith('<td colspan="1">' . $escaped . ' First </td>', $object);

        $multiple = $this->renderContent(Dropdownmultiple::class, 'Label', '', true, ['value' => $users] + $extra);
        $this->assertStringNotContainsString('<img', $multiple);
        $this->assertStringEndsWith(
            '<td colspan="1"><table style="border:0;">'
            . '<tr><td>' . $escaped . '</td><td>First</td></tr>'
            . '<tr><td>' . $escaped . '</td><td>Second</td></tr>'
            . '</table></td>',
            $multiple,
        );
    }

    /**
     * Basket renders a bordered table of the chosen objects, escaped, then its total.
     */
    public function testBasketRendersItsObjectsAsATable(): void
    {
        global $DB;

        $DB->insert(Basketobject::getTable(), ['name' => self::XSS_LABEL, 'reference' => 'REF-1', 'description' => 'Desc']);
        $object_id = $DB->insertId();
        $DB->insert('glpi_plugin_metademands_fields', ['name' => 'Basket', 'type' => 'basket']);
        $field_id = $DB->insertId();
        $DB->insert(FieldParameter::getTable(), ['plugin_metademands_fields_id' => $field_id, 'custom' => '["0","0"]']);

        $extra = ['id' => $field_id, 'plugin_metademands_metademands_id' => 0, 'value' => [$object_id => 1]];
        $content = $this->renderContent(Basket::class, 'Basket', '', true, $extra);

        $td = '<td style="border: 1px solid black;">';
        $this->assertStringNotContainsString('<img', $content);
        $this->assertStringStartsWith('<tr><th style="border: 1px solid black;">', $content);
        $this->assertStringContainsString(
            '<tr>' . $td . 'REF-1</td>' . $td . '&lt;img src=x onerror=alert(1)&gt;&quot;&#039;</td>' . $td . 'Desc</td>' . $td . '1</td></tr>',
            $content,
        );
        $this->assertStringEndsWith('<th style="border: 1px solid black;" colspan="3">Total</th>' . $td . '1.00</td></tr>', $content);

        $text = $this->renderContent(Basket::class, 'Basket', '', false, $extra);
        $this->assertSame('REF-1' . htmlspecialchars(self::XSS_LABEL, ENT_QUOTES) . 'Desc11.00', $text);
    }

    /**
     * Free table renders its label, its column names and the rows of the requester,
     * all escaped (the requester values also sanitized); date columns are converted.
     */
    public function testFreetableRendersTheRequesterRows(): void
    {
        global $DB;

        $field_id = 424242;
        foreach ([['col_a', Freetablefield::TYPE_TEXT, self::XSS_LABEL], ['col_b', Freetablefield::TYPE_DATE, 'When']] as $rank => [$internal, $type, $name]) {
            $DB->insert(Freetablefield::getTable(), [
                'plugin_metademands_fields_id' => $field_id,
                'internal_name'                => $internal,
                'type'                         => $type,
                'name'                         => $name,
                'rank'                         => $rank + 1,
            ]);
        }
        $_SESSION['plugin_metademands'][0]['freetables'][$field_id] = [
            ['col_a' => self::XSS_LABEL, 'col_b' => '2026-09-30'],
        ];

        try {
            $content = $this->renderContent(Freetable::class, self::XSS_LABEL, '', true, ['id' => $field_id, 'plugin_metademands_metademands_id' => 0]);
        } finally {
            unset($_SESSION['plugin_metademands'][0]);
        }

        $escaped = htmlspecialchars(self::XSS_LABEL, ENT_QUOTES, 'UTF-8');
        $td = '<td style="border: 1px solid #CCC;" colspan="6">';
        $this->assertStringNotContainsString('<img', $content);
        $this->assertSame(
            '<tr><td class="title" style="color:#red;width: 40%;" colspan="2">' . $escaped . '</td></tr>'
            . '<tr><th style="border: 1px solid #CCC;" colspan="6">' . $escaped . '</th><th style="border: 1px solid #CCC;" colspan="6">When</th></tr>'
            // the requester value goes through the rich-text sanitizer first, which drops the tag
            . '<tr>' . $td . '&quot;&#039;</td>' . $td . htmlspecialchars((string) \Html::convDate('2026-09-30')) . '</td></tr>',
            $content,
        );
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function provideIntervalFieldTypes(): array
    {
        return [
            'dateinterval'     => [Dateinterval::class],
            'datetimeinterval' => [Datetimeinterval::class],
        ];
    }

    /**
     * Interval fields render the start and the end as two label / value pairs, the end one
     * on a row of its own; both labels are escaped. label2 is stored as HTML and turned into
     * text first, so markup encoded in it must come back as entities, not as tags.
     *
     * @param class-string $field_class
     */
    #[DataProvider('provideIntervalFieldTypes')]
    public function testIntervalSpansTwoRows(string $field_class): void
    {
        $content = $this->renderContent($field_class, self::XSS_LABEL, '2026-09-22', true, [
            'value2' => '2026-09-30',
            'label2' => '<p>&lt;b&gt;End&lt;/b&gt;</p>',
        ]);

        $this->assertStringNotContainsString('<img', $content);
        $this->assertStringNotContainsString('<b>', $content);
        $this->assertStringContainsString('&lt;b&gt;End&lt;/b&gt;', $content);
        $this->assertSame(1, substr_count($content, '</tr><tr class="odd">'));
        $this->assertSame(2, substr_count($content, '<td class="title" style="color:#red;width: 40%;"'));
    }

    /**
     * @param class-string $field_class
     * @param array<string, mixed> $extra additional field keys
     */
    private function renderContent(string $field_class, string $label, string $value, bool $format_as_table = true, array $extra = []): string
    {
        $rank   = 1;
        $result = [$rank => ['content' => '', 'display' => false]];
        $field  = [
            'id'         => 1,
            'rank'       => $rank,
            'name'       => $label,
            'value'      => $value,
            'item'       => '',
            'hide_title' => 0,
        ];
        $field = $extra + $field;

        $field_class::displayFieldItems(
            $result,
            $format_as_table,
            'color:#red;width: 40%;',
            $label,
            $field,
            false,
            'en_GB',
        );

        return $result[$rank]['content'];
    }
}
