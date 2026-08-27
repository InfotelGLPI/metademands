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

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Field;
use Html;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Information Class
 *
 **/
class Information extends CommonDBTM
{
    public const INFO = 1;
    public const WARNING = 2;
    public const ALERT = 3;
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
        return __('Informations', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {

        $display = "alert-info";
        if ($data["display_type"] == self::WARNING) {
            $display = "alert-warning";
        }
        if ($data["display_type"] == self::ALERT) {
            $display = "alert-danger";
        }

        // Build the three content fragments separately so the template can auto-escape the
        // designer-defined name while keeping the already-sanitized rich HTML (comment/label2) raw.
        $name = "";
        $name_html = "";
        if ($data['hide_title'] == 0) {
            if (empty($name_title = Field::displayField($data['id'], 'name'))) {
                $name_title = $data['name'];
            }
            $name = (string) $name_title;
            $name_html = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        }

        $comment_html = "";
        if (!empty($data['comment'])) {
            $comment = Field::displayField($data['id'], 'comment') ?: $data['comment'];
            $comment_html = RichText::getSafeHtml($comment);
        }

        $label2_html = "";
        if (!empty($data['label2'])) {
            $label2 = Field::displayField($data['id'], 'label2') ?: $data['label2'];
            $label2_html = RichText::getSafeHtml($label2);
        }

        // Preserve the exact original visibility gate (!empty on the concatenated payload,
        // including the "0" edge case) while rendering the fragments separately.
        $todisplay = $name_html . $comment_html . $label2_html;
        $icon = (string) ($data['icon'] ?? '');

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_display_information.html.twig', [
            'display_class' => $display,
            'show_content'  => (bool) ($on_order == false && !empty($todisplay)),
            'has_icon'      => (bool) $icon,
            'icon'          => $icon,
            'icon_is_fa'    => str_contains($icon, 'fa-'),
            'color'         => $data['color'],
            'name'          => $name,
            'comment_html'  => $comment_html,
            'label2_html'   => $label2_html,
            'preview'       => (bool) $preview,
            'config_link'   => $config_link,
        ]);
    }

    public static function showFieldCustomValues($params) {}


    public static function showFieldParameters($params): string
    {
        ob_start();
        Html::showColorField('color', ['value' => $params["color"]]);
        $color_html = ob_get_clean();

        $values[self::INFO] = __('Information', 'metademands');
        $values[self::WARNING] = __('Warning', 'metademands');
        $values[self::ALERT] = __('Alert', 'metademands');
        $display_type_html = \Dropdown::showFromArray("display_type", $values, [
            'value'   => $params['display_type'],
            'display' => false,
        ]);

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_color.html.twig',
            [
                'color_html'        => $color_html,
                'display_type_html' => $display_type_html,
            ],
        );
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $result[$field['rank']]['display'] = false;
        return $result;
    }
}
