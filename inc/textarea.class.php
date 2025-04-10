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

use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * PluginMetademandsTextarea Class
 *
 **/
class PluginMetademandsTextarea extends CommonDBTM
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
    static function getTypeName($nb = 0)
    {
        return __('Textarea', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }
        $value = Html::cleanPostForTextArea($value);
        $self = new self();
        $required = "";
        if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
            $rand = mt_rand();
            $name = 'field['. $data['id'] .']';

            if (!empty($comment)) {
                $comment = Glpi\RichText\RichText::getTextFromHtml($comment);
            }

            self::textarea(['name' => $name,
                'placeholder' => $comment,
                'value' => $value,
                'rand' => $rand,
                'editor_id' => $namefield . $data['id'],
                'enable_fileupload' => true,
                'enable_richtext' => true,
//                'enable_images' => true,
                'required' => ($data['is_mandatory'] ? "required" : ""),
                'cols' => 80,
                'rows' => 6,
                'uploads' => $self->uploads]);

            echo Html::scriptBlock("$('.fileupload').hide();");
            echo"<style>
                        .fileupload.only-uploaded-files {
                            display: none;
                        }

                     </style>";

        } else {
            if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
                $required = "required='required'";
            }
            if (!empty($comment)) {
                $comment = Glpi\RichText\RichText::getTextFromHtml($comment);
            }
            $field = "<textarea $required class='form-control' rows='6' cols='80' 
               placeholder=\"" . $comment . "\" 
               name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "]'>" . $value . "</textarea>";
            echo $field;
        }


    }

    static function showFieldCustomValues($params)
    {

    }

    static function showFieldParameters($params)
    {

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Use richt text', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('use_richtext', ($params['use_richtext']));
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";
    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {

        echo "<tr>";
        echo "<td>";
        echo __('If field empty', 'metademands');
        echo "</td>";
        echo "<td>";
        if ($params['use_richtext'] == 0) {
            self::showValueToCheck($fieldoption, $params);
        } else {
            echo __('Not available with Rich text option', 'metademands');
        }
        echo "</td>";
        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params);
    }

    static function showValueToCheck($item, $params)
    {
        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        if ($item->getID() == 0) {
            foreach ($existing_options as $existing_option) {
                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
            }
        }
        $options[1] = __('No');
        //cannot use it
//        $options[2] = __('Yes');
        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    static function showParamsValueToCheck($params)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        echo $options[$params['check_value']] ?? "";

    }

    static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == 2 && $value != "")) {
            return false;
        } elseif ($check_value == 1 && $value == "") {
            return false;
        }
    }

    static function fieldsMandatoryScript($data) {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";
        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsMandatoryScript-tel $id');";
        }

        if (count($check_values) > 0) {
            if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
                //not supported
            } else {
                //Si la valeur est en session
                if (isset($data['value'])) {
                    $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
                }

                $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                $display = 0;
                foreach ($check_values as $idc => $check_value) {
                    $fields_link = $check_value['fields_link'];

                    if (isset($idc) && $idc == 1) {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                 sessionStorage.setItem('hiddenlink$name', $fields_link);
                                  " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                              } else {
                                 $('#metademands_wizard_red" . $fields_link . "').html('*');
                                 $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                 //Special case Upload field
                                 if(document.querySelector(\"[id-field='field$fields_link'] div input\")){
                                    document.querySelector(\"[id-field='field$fields_link'] div input\").required = true;
                                 }
                              }
                            ";
                    } else {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                 $('#metademands_wizard_red" . $fields_link . "').html('*');
                                 $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                 //Special case Upload field
                                 if(document.querySelector(\"[id-field='field$fields_link'] div input\")){
                                    document.querySelector(\"[id-field='field$fields_link'] div input\").required = true;
                                 }
                             } else {
                                $('#metademands_wizard_red" . $fields_link . "').html('');
                                sessionStorage.setItem('hiddenlink$name', $fields_link);
                                 " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                             }";
                    }
                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $fields_link;
                    }
                }

                if ($display > 0) {
                    $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
                }

                $onchange .= "});";

                echo Html::scriptBlock(
                    '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
                );
            }
        }
    }

    static function taskScript($data)
    {

        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('taskScript-textarea $id');";
        }

        if (count($check_values) > 0) {
            if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
                //not supported
            } else {
                //Si la valeur est en session
                if (isset($data['value'])) {
                    $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
                }

                $title = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
                $nextsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . __(
                        'Next',
                        'metademands'
                    ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


                foreach ($check_values as $idc => $check_value) {
                    $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];
                    if ($tasks_id) {
                        if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
                            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                            $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
                            $script .= "});";
                        }
                    }
                }
                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                foreach ($check_values as $idc => $check_value) {
                    $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];

                    $script .= "if ($(this).val().trim().length < 1) {
                                     $.ajax({
                                         url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                         data: { tasks_id: $tasks_id,
                                      used: 0 },
                                      success: function(response){
                                           if (response != 1) {
                                               document.getElementById('nextBtn').innerHTML = '$title'
                                           }
                                        },
                                    });
    
                                     ";

                    $script .= "      } else {
                                     $.ajax({
                                         url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                         data: { tasks_id: $tasks_id,
                                      used: 1 },
                                      success: function(response){
                                           if (response != 1) {
                                               document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
                                           }
                                        },
                                    });
    
                                     
                                     ";
                    $script .= "}";
                }
                $script .= "});";

                echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
            }
        }
    }

    static function fieldsHiddenScript($data)
    {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";
        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-textarea $id');";
        }

        if (count($check_values) > 0) {

            if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
                //not supported
            } else {
                //default hide of all hidden links
                foreach ($check_values as $idc => $check_value) {
                    $hidden_link = $check_value['hidden_link'];
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                }

                //Si la valeur est en session
                if (isset($data['value'])) {
                    $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
                }

                $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                $display = 0;
                foreach ($check_values as $idc => $check_value) {
                    $hidden_link = $check_value['hidden_link'];

                    if (isset($idc) && $idc == 1) {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                 $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                 sessionStorage.setItem('hiddenlink$name', $hidden_link);
                                  " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                              } else {
                                 $('[id-field =\"field" . $hidden_link . "\"]').show();
                              }
                                                    ";
                        $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_link;
                        }
                    } else {
                        $onchange .= "if ($(this).val().trim().length < 1) {
                                $('[id-field =\"field" . $hidden_link . "\"]').show();
                             } else {
                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                sessionStorage.setItem('hiddenlink$name', $hidden_link);
                                 " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                             }";

                        $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_link;
                        }
                    }
                }
                if ($display > 0) {
                    $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                    $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
                }

                $onchange .= "});";

                echo Html::scriptBlock(
                    '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
                );
            }
        }
    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        //add childs by idc
        $childs_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $child) {
                                $childs_by_checkvalue[$idc][] = $child;
                            }
                        }
                    }
                }
            }
        }

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('blocksHiddenScript-textarea $id');";
        }

        if (count($check_values) > 0) {
            if (isset($data['use_richtext']) && $data['use_richtext'] == 1) {
                //not supported
            } else {
                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                $script .= "var tohide = {};";

                //by default - hide all
                $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
                if (!isset($data['value'])) {
                    $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
                }
                $display = 0;
                foreach ($check_values as $idc => $check_value) {
                    $blocks_idc = [];
                    $hidden_block = $check_value['hidden_block'];

                    if (isset($idc) && $idc == 1) {
                        $script .= "if ($(this).val().trim().length > 0) {";
                        $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                        $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                        $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
                        $('[bloc-id =\"subbloc'+$hidden_block+'\"]').show();";
                        $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $script .= "if (document.getElementById('ablock" . $childs . "'))
                                                    document.getElementById('ablock" . $childs . "').style.display = 'block';
                                                    $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields(
                                                $metaid,
                                                $childs
                                            );
                                    }
                                }
                            }
                        }

                        if (isset($data['value']) && $idc == $data['value']) {
                            $display = $hidden_block;
                        }

                        $script .= " } else {";

                        //specific - one value
                        $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                        $script .= " }";

//                    $script .= " }";
//
//                    $script .= "if ($(this).val() != $idc) {";
//                    if (is_array($blocks_idc) && count($blocks_idc) > 0) {
//                        foreach ($blocks_idc as $k => $block_idc) {
//                            $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
//                        }
//                    }
//                    $script .= " }";
                    }
                }

                if ($display > 0) {
                    $script2 .= "$('[bloc-id =\"bloc" . $display . "\"]').show();
                    $('[bloc-id =\"subbloc" . $display . "\"]').show();";
                }


                $script .= "fixButtonIndicator();";
                $script .= "});";
            }
            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    /**
     * @param array $value
     * @param array $fields
     * @return bool
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {

        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && empty($fields['value'])) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function getFieldValue($field)
    {
        $field['value'] = htmlspecialchars_decode($field['value']);

        return $field['value'];
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
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

    public
    static function textarea($options = [])
    {
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
        $p['cols'] = 100;
        $p['rows'] = 15;
        $p['multiple'] = true;
        $p['required'] = false;
        $p['uploads'] = [];

        //merge default options with options parameter
        $p = array_merge($p, $options);

        $required = $p['required'] ? 'required' : '';
        $display = '';
        $display .= "<textarea class='form-control' name='" . $p['name'] . "' id='" . $p['editor_id'] . "'
                             rows='" . $p['rows'] . "' cols='" . $p['cols'] . "' $required>" .
            $p['value'] . "</textarea>";

        if ($p['enable_richtext']) {
            $display .= self::initEditorSystem($p['editor_id'], $p['rand'], false, false, $p['enable_images'], $p['placeholder']);
        }
        if (!$p['enable_fileupload'] && $p['enable_richtext'] && $p['enable_images']) {
            $p_rt = $p;
            $p_rt['display'] = false;
            $p_rt['only_uploaded_files'] = true;
            $p_rt['required'] = false;
            $display .= Html::file($p_rt);
        }

        if ($p['enable_fileupload']) {
            $p_rt = $p;
            unset($p_rt['name']);
            $p_rt['display'] = false;
            $p_rt['required'] = false;
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
    public
    static function initEditorSystem($id, $rand = '', $display = true, $readonly = false, $enable_images = true, $placeholder_comment = '')
    {
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
        $language_url = $CFG_GLPI['root_doc'] . '/public/lib/tinymce-i18n/langs6/' . $language . '.js';

        // Apply all GLPI styles to editor content
        $content_css = preg_replace('/^.*href="([^"]+)".*$/', '$1', Html::scss(('css/palettes/' . $_SESSION['glpipalette'] ?? 'auror') . '.scss', ['force_no_version' => true]))
            . ',' . preg_replace('/^.*href="([^"]+)".*$/', '$1', Html::css('public/lib/base.css', ['force_no_version' => true]));

        $cache_suffix = '?v=' . \Glpi\Toolbox\FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $readonlyjs   = $readonly ? 'true' : 'false';

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
                'language_url' => $language_url
            ]);
        }

        $placeholder = Glpi\RichText\RichText::getSafeHtml($placeholder_comment);
        $placeholder = addslashes($placeholder);
        $mandatory_field_msg = json_encode(__('The description field is mandatory', 'servicecatalog'));
        // init tinymce
        $js = <<<JS
         $(function() {
            var is_dark = $('html').css('--is-dark').trim() === 'true';
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
               skin_url: is_dark
                  ? CFG_GLPI['root_doc']+'/public/lib/tinymce/skins/ui/oxide-dark'
                  : CFG_GLPI['root_doc']+'/public/lib/tinymce/skins/ui/oxide',
               body_class: 'rich_text_container',
               content_css: '{$content_css}',
               highlight_on_focus: false,

               min_height: 250,
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
                     editor.setContent(`
                              <div id="placeholder">
                            $placeholder
                        </div>
                          `);
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
