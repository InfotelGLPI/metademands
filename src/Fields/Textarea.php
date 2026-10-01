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
use Glpi\Toolbox\FrontEnd;
use Glpi\UI\ThemeManager;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\MetademandTask;
use Html;

/**
 * Textarea Class
 *
 **/
class Textarea extends CommonDBTM
{
    private $uploads = [];

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
        return __('Textarea', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }
        $self = new self();
        $cols = 20;
        $rows = 5;

        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            $rand = mt_rand();
            $name = 'field[' . $data['id'] . ']';

            $namedrop = 'dropdoc' . $rand;

            if (!empty($comment)) {
                $comment = RichText::getTextFromHtml($comment);
            }

            // Capture the widget output (self::textarea() echoes by default).
            ob_start();
            self::textarea([
                'name' => $name,
                'placeholder' => $comment,
                'value' => $value,
                'rand' => $rand,
                'editor_id' => $namefield . $data['id'],
                'enable_fileupload' => true,
                'enable_richtext' => true,
                //                'enable_images' => true,
                'required' => ($data['is_mandatory'] ? "required" : ""),
                'cols' => $cols,
                'rows' => $rows,
                'uploads' => $self->uploads,
            ]);
            $textarea_html = ob_get_clean();

            echo TemplateRenderer::getInstance()->render(
                '@metademands/fields/field_textarea.html.twig',
                [
                    'is_richtext'   => true,
                    'textarea_html' => $textarea_html,
                    'drop_zone_id'  => $namedrop,
                ],
            );
        } else {
            if (!empty($comment)) {
                $comment = RichText::getTextFromHtml($comment);
            }
            $name_attr = $namefield . "[" . $data['id'] . "]";
            echo TemplateRenderer::getInstance()->render(
                '@metademands/fields/field_textarea.html.twig',
                [
                    'is_richtext' => false,
                    'is_required' => isset($data['is_mandatory']) && $data['is_mandatory'] == 1,
                    'rows'        => $rows,
                    'cols'        => $cols,
                    // Auto-escaped: placeholder hardened, value byte-identical to legacy.
                    'comment'     => $comment,
                    'value'       => $value,
                    'name_attr'   => $name_attr,
                    'id_attr'     => $name_attr,
                ],
            );
        }
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_textarea.html.twig',
            ['use_richtext' => $params['use_richtext']],
        );
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        ob_start();

        if ($params['use_richtext'] == 0) {
            self::showValueToCheck($fieldoption, $params);
        } else {
            echo __('Not available with Rich text option', 'metademands');
        }
        $cell_content = ob_get_clean();

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => false,
            'with_tech_group' => false,
            'content'         => $cell_content,
        ];

        if ($params['check_value'] == '') {
            $params['check_value'] = 1;
        }

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => '',
                'label'             => __('If field empty', 'metademands'),
                'label_colspan'     => 2,
                'regex_html'        => '',
                'valuetocheck'      => $valuetocheck,
                'link_html'         => $link_html,
            ],
        );
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        $options[1] = __('No');
        //cannot use it
        //        $options[2] = __('Yes');
        \Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    public static function showParamsValueToCheck($params): string
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        return $options[$params['check_value']] ?? "";
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == 2 && $value != "")) {
            return false;
        } elseif ($check_value == 1 && $value == "") {
            return false;
        }
        return true;
    }

    public static function fieldsMandatoryScript($data)
    {
        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            // Not supported
            return;
        }
        // Value 1: mandatory when the field is filled, otherwise when it is empty
        $options = [
            'negate' => array_values(array_filter(array_keys($data['options'] ?? []), static fn($idc) => $idc != 1)),
        ];
        if (isset($data['value'])) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'filled', $options);
    }

    public static function taskScript($data)
    {
        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            // Not supported for the rich text editor
            return;
        }
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix']);
    }

    public static function fieldsHiddenScript($data)
    {
        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            // Not supported
            return;
        }
        // Value 1: shown when the field is filled, otherwise when it is empty
        $options = [
            'negate' => array_values(array_filter(array_keys($data['options'] ?? []), static fn($idc) => $idc != 1)),
        ];
        if (isset($data['value'])) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'filled', $options);
    }

    public static function blocksHiddenScript($data)
    {
        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            // Not supported
            return;
        }
        // Value 1: blocks shown when the field is filled, otherwise when it is empty
        $options = [
            'negate' => array_values(array_filter(array_keys($data['options'] ?? []), static fn($idc) => $idc != 1)),
        ];
        if (isset($data['value'])) {
            $options['restore'] = ['val' => (string) $data['value']];
        }
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'filled', $options);
    }

    public static function checkConditions($data, $metaparams)
    {
        // A rich text editor has no change event: its keyup re-evaluates the conditions
        Condition::displayTrigger(
            $data,
            $metaparams,
            isset($data['use_richtext']) && $data['use_richtext'] == 1
                ? ['richtext' => true]
                : ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'],
        );
    }

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
            && ($fields['value'] === null || $fields['value'] === '')) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function getFieldValue($field)
    {
        return RichText::getSafeHtml($field['value']);
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
        $colspan = $is_order ? 12 : 2;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            // The raw value goes to the template, which sanitizes it with |safe_html
            // exactly as getFieldValue() does for the $return_value callers.
            $result[$field['rank']]['content'] .= Field::renderContentBlock(
                (bool) $formatAsTable,
                (string) $title_style,
                (string) $label,
                $field['hide_title'] == 0,
                (string) $field['value'],
                $colspan,
            );
        }

        return $result;
    }

    public static function textarea(
        $options = []
    ) {
        //default options
        $p['name'] = 'text';
        $p['filecontainer'] = 'fileupload_info';
        $p['rand'] = mt_rand();
        $p['editor_id'] = 'text' . $p['rand'];
        $p['value'] = '';
        $p['placeholder'] = '';
        $p['enable_richtext'] = false;
        $p['enable_images'] = true;
        $p['enable_fileupload'] = false;
        $p['display'] = true;
        $p['cols'] = 20;
        $p['rows'] = 5;
        $p['multiple'] = true;
        $p['required'] = false;
        $p['uploads'] = [];

        //merge default options with options parameter
        $p = array_merge($p, $options);

        $required = $p['required'] ? 'required' : '';
        $display = '';
        // Escaped exactly like Html::textarea() in the core. This method is a fork of it,
        // kept only for the editor height and placeholder that initEditorSystem() below
        // takes as extra arguments, and the escapes had been dropped along the way. The
        // value is an answer typed by a user, stored raw since GLPI 10 and replayed to the
        // other actors of a step form, so the missing escape on it was a stored XSS: a
        // closing </textarea> in the answer was enough to break out of the element.
        // Escaping does not harm the rich text, TinyMCE reads the decoded .value.
        $display .= "<textarea class='form-control' name='" . htmlescape($p['name']) . "' id='" . htmlescape($p['editor_id']) . "'
                             rows='" . ((int) $p['rows']) . "' cols='" . ((int) $p['cols']) . "' $required>"
            . htmlescape($p['value']) . "</textarea>";

        if ($p['enable_richtext']) {
            $height = $p['rows'] * 24;
            $display .= self::initEditorSystem(
                $p['editor_id'],
                $p['rand'],
                false,
                false,
                $p['enable_images'],
                $height,
                $p['placeholder'],
            );
        }
        if (!$p['enable_fileupload'] && $p['enable_richtext'] && $p['enable_images']) {
            $p_rt = $p;
            $p_rt['display'] = false;
            $p_rt['only_uploaded_files'] = true;
            $p_rt['required'] = false;
            $p_rt['dropZone'] = 'dropdoc' . $p['rand'];
            $display .= Html::file($p_rt);
        }

        if ($p['enable_fileupload']) {
            $p_rt = $p;
            $p_rt['display'] = false;
            $p_rt['required'] = false;
            $p_rt['dropZone'] = 'dropdoc' . $p['rand'];
            $display .= Html::file($p_rt);
        }

        if ($p['display']) {
            echo $display;
            return true;
        } else {
            return $display;
        }
    }

    /**
     * Init the Editor System to a textarea
     *
     * @param string $name name of the html textarea to use
     * @param string $rand rand of the html textarea to use (if empty no image paste system)(default '')
     * @param boolean $display display or get js script (true by default)
     * @param boolean $readonly editor will be readonly or not
     * @param boolean $enable_images enable image pasting in rich text
     *
     * @return void|string
     *    integer if param display=true
     *    string if param display=false (HTML code)
     **/
    public static function initEditorSystem(
        $id,
        $rand = '',
        $display = true,
        $readonly = false,
        $enable_images = true,
        int $editor_height = 150,
        $placeholder_comment = ''
    ) {
        global $CFG_GLPI, $DB;

        // load tinymce lib
        Html::requireJs('tinymce');

        $language = $_SESSION['glpilanguage'];
        if (!file_exists(GLPI_ROOT . "/public/lib/tinymce-i18n/langs6/$language.js")) {
            $language = $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2];
            if (!file_exists(GLPI_ROOT . "/public/lib/tinymce-i18n/langs6/$language.js")) {
                $language = "en_GB";
            }
        }
        $language_url = $CFG_GLPI['root_doc'] . '/lib/tinymce-i18n/langs6/' . $language . '.js';

        // Apply all GLPI styles to editor content
        $theme = ThemeManager::getInstance()->getCurrentTheme();
        $content_css_paths = [
            'css/glpi.scss',
            'css/core_palettes.scss',
        ];
        if ($theme->isCustomTheme()) {
            $content_css_paths[] = $theme->getPath();
        }
        $content_css = preg_replace('/^.*href="([^"]+)".*$/', '$1', Html::css('lib/base.css', ['force_no_version' => true]));
        $content_css .= ',' . preg_replace('/^.*href="([^"]+)".*$/', '$1', Html::css('lib/tabler.css', ['force_no_version' => true]));
        $content_css .= ',' . implode(',', array_map(static fn($path) => preg_replace('/^.*href="([^"]+)".*$/', '$1', Html::scss($path, ['force_no_version' => true])), $content_css_paths));
        // Fix & encoding so it can be loaded as expected in debug mode
        $content_css = str_replace('&amp;', '&', $content_css);
        $skin_url = preg_replace('/^.*href="([^"]+)".*$/', '$1', Html::css('css/tinymce_empty_skin', ['force_no_version' => true], false));
        $content_css = jsescape($content_css);
        $cache_suffix = '?v=' . FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $readonlyjs = $readonly ? 'true' : 'false';

        $invalid_elements = 'applet,canvas,embed,form,object';
        if (!$enable_images) {
            $invalid_elements .= ',img';
        }
        if (!GLPI_ALLOW_IFRAME_IN_RICH_TEXT) {
            $invalid_elements .= ',iframe';
        }

        $plugins = [
            'autoresize',
            'code',
            'directionality',
            'fullscreen',
            'link',
            'lists',
            'quickbars',
            'searchreplace',
            'table',
        ];
        if ($enable_images) {
            $plugins[] = 'image';
            $plugins[] = 'glpi_upload_doc';
        }
        if ($DB->use_utf8mb4) {
            $plugins[] = 'emoticons';
        }
        $pluginsjs = json_encode($plugins);

        $language_opts = '';
        if ($language !== 'en_GB') {
            $language_opts = json_encode([
                'language' => $language,
                'language_url' => $language_url,
            ]);
        }

        // The sink below is an ES6 template literal, where ${...} is evaluated as
        // JavaScript: addslashes() escapes the quotes, the backslash and NUL, but neither
        // the backquote nor ${, so it never protected this site. getSafeHtml() sanitizes
        // HTML and has no reason to drop those characters, which are not HTML-significant.
        // Build the whole fragment here and let json_encode() emit its own delimiters,
        // exactly as src/Wizard.php:1490 already does -- hence the unquoted sink.
        $placeholder = RichText::getSafeHtml($placeholder_comment);
        $placeholder_content = json_encode(
            '<div id="placeholder">' . $placeholder . '</div>',
            JSON_HEX_TAG | JSON_HEX_AMP,
        );
        $mandatory_field_msg = json_encode(__('The description field is mandatory', 'servicecatalog'));
        // init tinymce
        $js = <<<JS
         $(function() {
            var is_dark = false;//$('html').css('--is-dark').trim() === 'true'
            var richtext_layout = "{$_SESSION['glpirichtext_layout']}";

            // init editor
            tinyMCE.init(Object.assign({
               license_key: 'gpl',

               link_default_target: '_blank',
               branding: false,
               selector: '#{$id}',
               text_patterns: false,
               paste_webkit_styles: 'all',

               plugins: {$pluginsjs},

               // Appearance
               skin_url: '{$skin_url}',
               body_class: 'rich_text_container',
               content_css: '{$content_css}',
               highlight_on_focus: false,

               min_height: $editor_height,
                height: $editor_height, // Must be used with min_height to prevent "height jump" when the page is loaded
               resize: true,

               // disable path indicator in bottom bar
               elementpath: false,

                // inline toolbar configuration
               menubar: false,
               toolbar: richtext_layout == 'classic'
                  ? 'styles | bold italic | forecolor backcolor | bullist numlist outdent indent | emoticons table link image | code fullscreen'
                  : false,
               quickbars_insert_toolbar: richtext_layout == 'inline'
                  ? 'emoticons quicktable quickimage quicklink | bullist numlist | outdent indent '
                  : false,
               quickbars_selection_toolbar: richtext_layout == 'inline'
                  ? 'bold italic | styles | forecolor backcolor '
                  : false,
               contextmenu: richtext_layout == 'classic'
                  ? false
                  : 'copy paste | emoticons table image link | undo redo | code fullscreen',

               // Content settings
               entity_encoding: 'raw',
               invalid_elements: '{$invalid_elements}',
               readonly: {$readonlyjs},
               relative_urls: false,
               remove_script_host: false,

               // Misc options
               browser_spellcheck: true,
               cache_suffix: '{$cache_suffix}',

               // Security options
               // Iframes are disabled by default. We assume that administrator that enable it are aware of the potential security issues.
               sandbox_iframes: false,

               setup: function(editor) {
                  // "required" state handling
                  if ($('#$id').attr('required') == 'required') {
                     $('#$id').removeAttr('required'); // Necessary to bypass browser validation

                     editor.on('submit', function (e) {
                        if ($('#$id').val() == '') {
                           const field = $('#$id').closest('.form-field').find('label').text().replace('*', '').trim();
                           alert({$mandatory_field_msg}.replace('%s', field));
                           e.preventDefault();

                           // Prevent other events to run
                           // Needed to not break single submit forms
                           e.stopPropagation();
                        }
                     });
                     editor.on('keyup', function (e) {
                        editor.save();
                        if ($('#$id').val() == '') {
                           $(editor.container).addClass('required');
                        } else {
                           $(editor.container).removeClass('required');
                        }
                     });
                     editor.on('init', function (e) {
                        if (strip_tags($('#$id').val()) == '') {
                           $(editor.container).addClass('required');
                        }
                     });
                     editor.on('paste', function (e) {
                        // Remove required on paste event
                        // This is only needed when pasting with right click (context menu)
                        // Pasting with Ctrl+V is already handled by keyup event above
                        $(editor.container).removeClass('required');
                     });
                  }
                  editor.on('Change', function (e) {
                     // Nothing fancy here. Since this is only used for tracking unsaved changes,
                     // we want to keep the logic in common.js with the other form input events.
                     onTinyMCEChange(e);
                  });
                  // ctrl + enter submit the parent form
                  editor.addShortcut('ctrl+13', 'submit', function() {
                     editor.save();
                     submitparentForm($('#$id'));
                  });
                  editor.on('init', () => {
                     if ($('#$id').val() == '') {
                     editor.setContent($placeholder_content);
                     }
                  });
                  // When the editor is clicked we monitor what is being clicked and
                  // take appropriate actions. This is how we dedect if a insert template
                  // button has been clicked. This event is triggered for every click inside
                  // TinyMCE.
                  // https://www.tiny.cloud/docs/advanced/events/
                  const placeholderManager = (e) => {

                     // Check if the content contains the placeholder inserted above.
                     // The get() function looks for an id attribute.
                     // https://www.tiny.cloud/docs/api/tinymce.dom/tinymce.dom.domutils/#get
                     const placeholderExists = editor.dom.get('placeholder');

                     if (placeholderExists) {

                        // In this demo we want to start an empty document with a title.
                           // This does not force having a title for a document, it's simply
                           // a convenience feature.
                           editor.undoManager.transact(() => {
                              editor.setContent('');
                           });
                     }
                  };

                  // Bind the click event listener to the placeholder manager function
                  editor.once('click tap keydown', placeholderManager);

                  editor.on('Undo', () => {
                     // Rebind the click event listener when the editor is reverted back
                     // to the original content
                     if (!editor.undoManager.hasUndo()) {
                        editor.once('click tap keydown', placeholderManager);
                     }
                  });
                  editor.on('PreInit', () => {
                     // To prevent the placeholder to be submitted out of TinyMCE we
                     // remove it upon serialization. In this case, any <div> tag
                     // will be removed, so adapt it to your needs.
                     // https://www.tiny.cloud/docs/api/tinymce.dom/tinymce.dom.serializer/#addnodefilter
                     editor.serializer.addNodeFilter('div', nodes => {
                        nodes.forEach(node => {
                           node.remove();
                        });
                     });
                  });
               },
               content_style: `
                #placeholder {
                    color: #aaa;
                    display: flex;
                    flex-direction: column;
                    -webkit-user-select: none; /* Prevent any selections on the element */
                    user-select: none;
                }

                #placeholder * {
                    -webkit-user-select: none; /* Prevent any selections on the element */
                    user-select: none;
                }`
            }, {$language_opts}));
         });
JS;

        if ($display) {
            echo Html::scriptBlock($js);
        } else {
            return Html::scriptBlock($js);
        }
    }
}
