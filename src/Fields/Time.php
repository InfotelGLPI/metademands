<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Html;
use Locale;
use GlpiPlugin\Metademands\Field;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Time Class
 *
 **/
class Time extends CommonDBTM
{
    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Time', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "<span style='width: 50%!important;display: -webkit-box;'>";
        $field .= self::showTimeField($namefield . "[" . $data['id'] . "]", [
            'value' => $value,
            'display' => false,
            'required' => (bool) $data['is_mandatory'],
            'size' => 40,
        ]);
        $field .= "</span>";

        echo $field;
    }

    /**
     * Display TimeField form
     *
     * @param string $name
     * @param array  $options
     *   - value      : default value to display (default '')
     *   - timestep   : step for time in minute (-1 use default config) (default -1)
     *   - maybeempty : may be empty ? (true by default)
     *   - canedit    : could not modify element (true by default)
     *   - mintime    : minimum allowed time (default '')
     *   - maxtime    : maximum allowed time (default '')
     *   - display    : boolean display or get string (default true)
     *   - rand       : specific random value (default generated one)
     *   - required   : required field (will add required attribute)
     *   - on_change  : function to execute when date selection changed
     * @return string
     */
    public static function showTimeField($name, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p = [
            'value'      => '',
            'maybeempty' => true,
            'canedit'    => true,
            'mintime'    => '',
            'maxtime'    => '',
            'timestep'   => -1,
            'display'    => true,
            'rand'       => mt_rand(),
            'required'   => false,
            'on_change'  => '',
        ];

        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }

        if ($p['timestep'] < 0) {
            $p['timestep'] = $CFG_GLPI['time_step'];
        }

        $hour_value = '';
        if (!empty($p['value'])) {
            $hour_value = $p['value'];
        }

        if (!empty($p['mintime'])) {
            // Check time in interval
            if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
                $hour_value = $p['mintime'];
            }
        }
        if (!empty($p['maxtime'])) {
            // Check time in interval
            if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
                $hour_value = $p['maxtime'];
            }
        }
        // reconstruct value to be valid
        if (!empty($hour_value)) {
            $p['value'] = $hour_value;
        }

        $required = $p['required'] == true
            ? " required='required'"
            : "";
        $disabled = !$p['canedit']
            ? " disabled='disabled'"
            : "";
        $clear    = $p['maybeempty'] && $p['canedit']
            ? "<a data-clear  title='" . __s('Clear') . "'>
               <i class='input-group-text fas fa-times-circle pointer'></i>
            </a>"
            : "";

        $output = <<<HTML
         <div class="input-group flex-grow-1 flatpickr" id="showtime{$p['rand']}">
            <input type="text" name="{$name}" value="{$p['value']}"
                   {$required} {$disabled} data-input class="form-control rounded-start ps-2">
            <a class="input-button" data-toggle>
               <i class="input-group-text far fa-clock fa-lg pointer"></i>
            </a>
            $clear
         </div>
HTML;
        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $js = <<<JS
      $(function() {
         $("#showtime{$p['rand']}").flatpickr({
            dateFormat: 'H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            noCalendar: true, // only time picker
            enableSeconds: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            minuteIncrement: "{$p['timestep']}",
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            }
         });
      });
JS;
        $output .= Html::scriptBlock($js);

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }


    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params) {}

    /**
     * @param array $value
     * @param array $fields
     * @return array
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && ($fields['value'] == 'NULL' || empty($fields['value']))) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        return $field['value'];
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $style_title,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        }

        return $result;
    }
}
