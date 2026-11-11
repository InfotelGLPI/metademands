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
use GlpiPlugin\Metademands\Export;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;

/**
 * Import of an exported metademand: the value an option checks is mapped to the new id of a
 * custom value of its own field only. A yes / no option ("yes" = 2) used to be mapped to the
 * custom value of another field whose old id was 2, and never matched again.
 */
class ExportCheckValueTest extends DbTestCase
{
    public function testYesNoOptionKeepsItsValueAndCustomValuesAreMapped(): void
    {
        $this->login();

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Export check value',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $base = [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'rank'                              => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ];
        $yesno = $this->createItem(Field::class, $base + ['type' => 'yesno', 'item' => 'yesno', 'name' => 'Yes no', 'order' => 1], ['order']);
        $radio = $this->createItem(Field::class, $base + ['type' => 'radio', 'item' => 'radio', 'name' => 'Radio', 'order' => 2], ['order', 'item']);
        $custom = $this->createItem(FieldCustomvalue::class, [
            'plugin_metademands_fields_id' => $radio->getID(),
            'name'                         => 'Choice',
            'rank'                         => 0,
        ]);
        $this->createItem(FieldOption::class, ['plugin_metademands_fields_id' => $yesno->getID(), 'check_value' => 2, 'hidden_block' => 2], ['check_value']);
        $this->createItem(FieldOption::class, ['plugin_metademands_fields_id' => $radio->getID(), 'check_value' => $custom->getID(), 'hidden_block' => 3], ['check_value']);

        $target = Export::exportAsXMLForMetademands($metademand->getID());
        $this->assertNotEmpty($target);

        // Give the custom value the old id 2, the value of "yes": the case of an instance where
        // the first custom values were created first
        $xml = file_get_contents($target['path']);
        @unlink($target['path']);
        $old_id = (string) $custom->getID();
        $xml = preg_replace('/<id>' . $old_id . '<\/id>/', '<id>2</id>', $xml);
        $xml = preg_replace('/<check_value>' . $old_id . '<\/check_value>/', '<check_value>2</check_value>', $xml);
        $file = GLPI_TMP_DIR . '/md_export_check_value.xml';
        file_put_contents($file, $xml);

        $import = new \ReflectionMethod(Export::class, 'importXmlFile');
        $new_id = $import->invoke(null, $file);
        @unlink($file);
        $this->assertGreaterThan(0, (int) $new_id);

        $field = new Field();
        $new_yesno = $field->find(['plugin_metademands_metademands_id' => $new_id, 'type' => 'yesno']);
        $new_radio = $field->find(['plugin_metademands_metademands_id' => $new_id, 'type' => 'radio']);
        $this->assertCount(1, $new_yesno);
        $this->assertCount(1, $new_radio);
        $option = new FieldOption();

        // "Yes" stays 2
        $yesno_options = $option->find(['plugin_metademands_fields_id' => array_key_first($new_yesno)]);
        $this->assertSame(['2'], array_values(array_map(static fn($o) => (string) $o['check_value'], $yesno_options)));

        // The radio checks the new id of its own custom value
        $new_custom = (new FieldCustomvalue())->find(['plugin_metademands_fields_id' => array_key_first($new_radio)]);
        $this->assertCount(1, $new_custom);
        $radio_options = $option->find(['plugin_metademands_fields_id' => array_key_first($new_radio)]);
        $this->assertSame(
            [(string) array_key_first($new_custom)],
            array_values(array_map(static fn($o) => (string) $o['check_value'], $radio_options)),
        );
    }
}
