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
use Html;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Wizard;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Title Class
 *
 **/
class Title extends CommonDBTM
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
        return __('Title');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {
        $debug = isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        if ($data['hide_title'] != 0) {
            return;
        }

        $icon = (string) ($data['icon'] ?? '');

        if (empty($label = Field::displayField($data['id'], 'name'))) {
            $label = "";
            if (isset($data['name'])) {
                $label = $data['name'];
            }
        }

        // The label2 tooltip is rendered by Html::showToolTip() which echoes directly:
        // capture it so it can be injected raw into the template.
        $has_label2 = isset($data['label2']) && !empty($data['label2']);
        $label2_tooltip_html = '';
        if ($has_label2) {
            if (empty($label2 = Field::displayField($data['id'], 'label2'))) {
                $label2 = $data['label2'];
            }
            ob_start();
            Html::showToolTip(
                RichText::getSafeHtml($label2),
                ['awesome-class' => 'ti ti-info-circle'],
            );
            $label2_tooltip_html = ob_get_clean();
        }

        $has_comment = !empty($data['comment']);
        $comment_html = '';
        if ($has_comment) {
            if (empty($comment = Field::displayField($data['id'], 'comment'))) {
                $comment = $data['comment'];
            }
            $comment_html = RichText::getSafeHtml($comment);
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_display_title.html.twig', [
            'id'                  => (int) $data['id'],
            'color'               => $data['color'],
            'color_rgba'          => Wizard::hex2rgba($data['color'], "0.03"),
            'has_icon'            => (bool) $icon,
            'icon'                => $icon,
            'icon_is_fa'          => str_contains($icon, 'fa-'),
            'label'               => $label,
            'debug'               => $debug,
            'has_label2'          => $has_label2,
            'label2_tooltip_html' => $label2_tooltip_html,
            'preview'             => (bool) $preview,
            'config_link'         => $config_link,
            'has_comment'         => $has_comment,
            'comment_html'        => $comment_html,
        ]);
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        ob_start();
        Html::showColorField('color', ['value' => $params["color"]]);
        $color_html = ob_get_clean();

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_color.html.twig',
            ['color_html' => $color_html],
        );
    }


    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}


    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        //to true automatickly if another field on the block is loaded
        $result[$field['rank']]['display'] = false;
        if ($formatAsTable) {
            $colspan = $is_order ? 12 : 2;
            $result[$field['rank']]['content'] .= "<th colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= $label;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</th>";
        }

        return $result;
    }
}
