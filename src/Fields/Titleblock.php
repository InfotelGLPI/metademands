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
use Toolbox;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use Session;

/**
 * Titleblock Class
 *
 **/
class Titleblock extends CommonDBTM
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
        return __('Block title', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_url)
    {
        $debug = isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $rank = (int) $data['rank'];

        $icon = (string) ($data['icon'] ?? '');

        if (empty($label = Field::displayField($data['id'], 'name'))) {
            $label = $data['name'];
        }

        $has_label2 = isset($data['label2']) && !empty($data['label2']);
        $label2 = '';
        if ($has_label2) {
            if (empty($label2 = Field::displayField($data['id'], 'label2'))) {
                $label2 = $data['label2'];
            }
        }

        $has_comment = !empty($data['comment']);
        $comment = '';
        if ($has_comment) {
            if (empty($comment = Field::displayField($data['id'], 'comment'))) {
                $comment = $data['comment'];
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_display_titleblock.html.twig', [
            'is_preview_or_debug' => (bool) ($preview || $debug),
            'bg_color'            => 'var(--tblr-bg-surface, #FFF)',
            'color'               => Metademand::toThemedForeground($data['color']),
            'rank'                => $rank,
            'has_icon'            => (bool) $icon,
            'icon'                => $icon,
            'icon_is_fa'          => str_contains($icon, 'fa-'),
            'label'               => $label,
            'debug'               => $debug,
            'id'                  => $data['id'],
            'config_url'          => $config_url,
            'has_label2'          => $has_label2,
            'label2'              => $label2,
            'has_comment'         => $has_comment,
            'comment'             => $comment,
        ]);

        // The collapse toggle is a delegated handler in public/scripts/wizard_form.js,
        // keyed on the data-metademands-collapse marker the template above carries.
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_color.html.twig',
            ['color' => $params['color']],
        );
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function displayFieldItems(&$result, $formatAsTable, $title_style, $label, $field, $return_value, $lang, $is_order = false)
    {
        //to true automatickly if another field on the block is loaded
        $result[$field['rank']]['display'] = false;
        $result[$field['rank']]['content'] .= Field::renderContentCells(
            (bool) $formatAsTable,
            (string) $title_style,
            [[
                'label'   => (string) $label,
                'colspan' => $is_order ? 12 : 2,
                'heading' => true,
            ]],
        );

        return $result;
    }
}
