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
use GlpiPlugin\Metademands\Field;
use Html;
use Toolbox;

/**
 * Signature Class
 *
 **/
class Signature extends CommonDBTM
{
    /**
     * Session allow-list of the signature paths this session may submit, keyed by path.
     * Kept outside $_SESSION['plugin_metademands'], which the wizard wipes on several
     * transitions while a reopened draft or form still re-posts its signature.
     */
    private const SESSION_KEY = 'plugin_metademands_signatures';

    /** Uploaded by this session through ajax/addsignature.php: usable and deletable. */
    private const ORIGIN_UPLOAD = 'upload';

    /** Reloaded from a draft / form / step form this session opened: usable only. */
    private const ORIGIN_LOADED = 'loaded';

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
        return __('Signature', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        $field_id       = $data['id'];
        $name           = $namefield . "[" . $field_id . "]";
        $is_mandatory   = ($data['is_mandatory'] == 1) ? 1 : 0;
        $metademands_id = (int) $data['plugin_metademands_metademands_id'];

        // Unique HTML IDs per field to support multiple Signature fields on the same form
        $canvas_id = "signature-pad-$field_id";
        $save_id   = "savesign-$field_id";
        $clear_id  = "clearsign-$field_id";
        $hidden_id = "hiddenId-$field_id";
        $result_id = "result-$field_id";

        $has_value = !empty($value);
        // Show existing signature as preview when editing a saved value.
        // picture_url and value are auto-escaped by {{ }} in the template
        // (byte-identical to the legacy htmlspecialchars(ENT_QUOTES) for valid UTF-8).
        $picture_url = $has_value ? Toolbox::getPictureUrl($value) : '';

        // Labels are trusted translations; ids/name are safe per-field strings.
        $label_add   = __('Add your signature', 'metademands');
        $label_clear = __('Clear', 'metademands');

        $script_tag = Html::script(PLUGIN_METADEMANDS_WEBDIR . "/lib/signature/js/signature_pad.umd.min.js");
        $css_tag    = Html::css(PLUGIN_METADEMANDS_WEBDIR . "/lib/signature/css/signature_pad.umd.css");

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_signature.html.twig',
            [
                'canvas_id'     => $canvas_id,
                'has_value'     => $has_value,
                'field_id'      => $field_id,
                'picture_url'   => $picture_url,
                'save_id'       => $save_id,
                'clear_id'      => $clear_id,
                'label_add'     => $label_add,
                'label_clear'   => $label_clear,
                'result_id'     => $result_id,
                'hidden_id'     => $hidden_id,
                'name'          => $name,
                'value'         => $value ?? '',
                'script_tag'    => $script_tag,
                'css_tag'       => $css_tag,
                // Read by public/scripts/wizard_form.js (initSignature).
                'metademands_id' => $metademands_id,
                'is_mandatory'   => $is_mandatory,
                'add_url'        => PLUGIN_METADEMANDS_WEBDIR . '/ajax/addsignature.php',
                'remove_url'     => PLUGIN_METADEMANDS_WEBDIR . '/ajax/removesignature.php',
                'messages'       => [
                    'add'        => __('Your signature has been uploaded', 'metademands'),
                    'remove'     => __('Your signature has been deleted', 'metademands'),
                    'failadd'    => __('There was a problem on upload your signature', 'metademands'),
                    'failremove' => __('There was a problem on delete your signature', 'metademands'),
                    'mandatory'  => __('This field is mandatory', 'metademands'),
                ],
            ],
        );
    }


    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        return '';
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params) {}

    public static function showValueToCheck($item, $params) {}


    /**
     * @param array $value
     * @param array $fields
     * @return array
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg     = "";
        $checkKo = 0;

        if ($value['is_mandatory'] && ($fields['value'] === null || $fields['value'] === '')) {
            $msg     = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        return true;
    }

    /**
     * Filter a submitted signature value against the session allow-list.
     *
     * ajax/addsignature.php echoes the stored path back to the browser and the
     * wizard script copies it into a hidden input, so whatever comes back under
     * field[<id>] is fully client controlled. That value is later concatenated to
     * GLPI_PICTURE_DIR by MetademandPdf and handed to TCPDF, which reads the file
     * from disk: an arbitrary path would turn into a directory traversal, and a
     * path belonging to somebody else into a stolen handwritten signature. Only
     * the paths addsignature.php produced for the current session are accepted --
     * the very same allow-list ajax/removesignature.php already consults.
     *
     * @param mixed $value Raw value posted for a signature field
     *
     * @return string Accepted path, or an empty string
     */
    public static function sanitizeSubmittedValue($value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        // Only paths this session uploaded, or reloaded from a draft / form it was allowed
        // to open (see registerLoadedValues()), are accepted: a well-formed path to a
        // picture somebody else signed is refused, whatever its shape.
        if (!isset($_SESSION[self::SESSION_KEY][$value])) {
            return '';
        }

        $root = realpath(GLPI_PICTURE_DIR);
        $real = realpath(GLPI_PICTURE_DIR . '/' . $value);
        if ($root === false || $real === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return '';
        }

        return $value;
    }

    /**
     * Remember a signature uploaded by the current session.
     *
     * @param string $path Path returned by Toolbox::savePicture()
     *
     * @return void
     */
    public static function registerUpload(string $path): void
    {
        $_SESSION[self::SESSION_KEY][$path] = self::ORIGIN_UPLOAD;
    }

    /**
     * Whether the current session uploaded this signature itself, and may thus delete it.
     * A signature reloaded from a stored draft or form belongs to its original signer.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isOwnUpload(string $path): bool
    {
        return ($_SESSION[self::SESSION_KEY][$path] ?? null) === self::ORIGIN_UPLOAD;
    }

    /**
     * Forget a signature once it has been deleted.
     *
     * @param string $path
     *
     * @return void
     */
    public static function forgetUpload(string $path): void
    {
        unset($_SESSION[self::SESSION_KEY][$path]);
    }

    /**
     * Allow the signature values reloaded from a stored draft / form / step form, so
     * that they survive the round trip through the hidden input of the reopened form.
     * Only values stored under a field of type signature are registered: a text field
     * of one's own draft must not be able to allow an arbitrary picture path.
     *
     * @param array<int|string, mixed> $values Stored values keyed by fields_id
     *
     * @return void
     */
    public static function registerLoadedValues(array $values): void
    {
        // Decode the stored value exactly as the loaders put it in session.
        $values = array_map(
            static fn($value) => is_string($value) ? (json_decode($value, true) ?? $value) : $value,
            $values,
        );
        $values = array_filter($values, static fn($value) => is_string($value) && $value !== '');
        if ($values === []) {
            return;
        }

        $field = new Field();
        $signature_fields = $field->find([
            'id'   => array_map('intval', array_keys($values)),
            'type' => 'signature',
        ]);
        foreach ($signature_fields as $signature_field) {
            $path = $values[$signature_field['id']] ?? '';
            if ($path !== '' && !isset($_SESSION[self::SESSION_KEY][$path])) {
                $_SESSION[self::SESSION_KEY][$path] = self::ORIGIN_LOADED;
            }
        }
    }

    public static function showParamsValueToCheck($params): string
    {
        return '';
    }

    public static function fieldsMandatoryScript($data) {}

    public static function taskScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        $picture_url = self::getPictureUrl($field);
        if ($picture_url === '') {
            return '';
        }

        return "<img src='" . htmlspecialchars($picture_url, ENT_QUOTES) . "'>";
    }

    /**
     * URL of the signature picture, or an empty string when the stored path is not valid.
     */
    private static function getPictureUrl(array $field): string
    {
        // Toolbox::getPictureUrl() validates nothing, so a legacy row holding a crafted
        // path would still build a document.send.php URL pointing outside GLPI_PICTURE_DIR.
        $path = self::sanitizeSubmittedValue($field['value'] ?? '');
        if ($path === '') {
            return '';
        }

        return (string) Toolbox::getPictureUrl($path);
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $title_style,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => self::getPictureUrl($field),
                    'colspan' => $colspan,
                    'image'   => true,
                ]],
            );
        }

        return $result;
    }

}
