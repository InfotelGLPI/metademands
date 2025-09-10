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
 * PluginMetademandsLdapdropdown Class
 *
 **/
class PluginMetademandsLdapdropdown extends CommonDBTM
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
        return __('Ldap Dropdown', 'metademands');
    }

    public static function getTable($classname = null)
    {
        return RuleRightParameter::getTable();
    }

    public static function getForeignKeyField()
    {
        return '';
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $itilcategories_id)
    {

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "";

        $opt = ['value'     => $value,
            'entity'    => $_SESSION['glpiactiveentities'],
            'name'      => $namefield . "[" . $data['id'] . "]",
            'display'   => false,
            'condition' => [
                'plugin_metademands_fieldparameters_id' => $data['id']
            ]
        ];
        if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
            $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
        }

        $opt['url'] = PLUGIN_METADEMANDS_WEBDIR. '/ajax/getldapvalues.php';

        if ($item = getItemForItemtype(self::class)) {
            $field = Dropdown::show(self::class, $opt);
        }
        echo $field;
    }

    public static function getDropdownValue($post, $json = true)
    {
        // Count real items returned
        $count = 0;

        if (isset($post['condition']) && !empty($post['condition']) && !is_array($post['condition'])) {
            // Retrieve conditions from SESSION using its key
            $key = $post['condition'];
            $post['condition'] = [];
            if (isset($_SESSION['glpicondition']) && isset($_SESSION['glpicondition'][$key])) {
                $post['condition'] = $_SESSION['glpicondition'][$key];
            }
        }

        $post['searchText'] ??= '';

        $values = ['ldap_auth'=> 0,
            'ldap_attribute' => 0,
            'ldap_filter' => ""];

        $param = new PluginMetademandsFieldParameter();
        if ($param->getFromDB($post['condition']['plugin_metademands_fieldparameters_id'])) {

            $values = ['ldap_auth'=> $param->fields['authldaps_id'],
                'ldap_attribute' => $param->fields['ldap_attribute'],
                'ldap_filter' => html_entity_decode($param->fields['ldap_filter'])];
        }

        $values = json_encode($values);
        // Search values
        $ldap_values   = json_decode($values, JSON_OBJECT_AS_ARRAY);
        $ldap_dropdown = new RuleRightParameter();
        if (!$ldap_dropdown->getFromDB($ldap_values['ldap_attribute'])) {
            return "";
        }
        $attribute     = [$ldap_dropdown->fields['value']];

        $config_ldap = new AuthLDAP();
        if (!$config_ldap->getFromDB($ldap_values['ldap_auth'])) {
            return "";
        }

        set_error_handler([self::class, 'ldapErrorHandler'], E_WARNING);

        if ($post['searchText'] != '') {
            $ldap_values['ldap_filter'] = sprintf(
                "(& %s (%s))",
                $ldap_values['ldap_filter'],
                $attribute[0] . '=*' . $post['searchText'] . '*'
            );
        }

        $tab_values = [];
        try {
            $cookie = '';
            $ds = $config_ldap->connect();
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
            $foundCount = 0;
            do {
                if (AuthLDAP::isLdapPageSizeAvailable($config_ldap)) {
                    $controls = [
                        [
                            'oid'        => LDAP_CONTROL_PAGEDRESULTS,
                            'iscritical' => true,
                            'value'      => [
                                'size'    => $config_ldap->fields['pagesize'],
                                'cookie'  => $cookie,
                            ],
                        ],
                    ];
                    $result = ldap_search($ds, $config_ldap->fields['basedn'], $ldap_values['ldap_filter'], $attribute, 0, -1, -1, LDAP_DEREF_NEVER, $controls);
                    ldap_parse_result($ds, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
                    $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '';
                } else {
                    $result  = ldap_search($ds, $config_ldap->fields['basedn'], $ldap_values['ldap_filter'], $attribute);
                }

                $entries = ldap_get_entries($ds, $result);
                // openldap return 4 for Size limit exceeded
                $limitexceeded = in_array(ldap_errno($ds), [4, 11]);

                if ($limitexceeded) {
                    trigger_error("LDAP size limit exceeded", E_USER_WARNING);
                }

                unset($entries['count']);

                foreach ($entries as $attr) {
                    if (!isset($attr[$attribute[0]]) || in_array($attr[$attribute[0]][0], $tab_values)) {
                        continue;
                    }

                    $foundCount++;
                    if ($foundCount < ((int) $post['page'] - 1) * (int) $post['page_limit'] + 1) {
                        // before the requested page
                        continue;
                    }
                    if ($foundCount > ((int) $post['page']) * (int) $post['page_limit']) {
                        // after the requested page
                        break;
                    }

                    $tab_values[] = [
                        'id'   => $attr[$attribute[0]][0],
                        'text' => $attr[$attribute[0]][0],
                    ];
                    $count++;
                    if ($count >= $post['page_limit']) {
                        break;
                    }
                }
            } while ($cookie !== null && $cookie != '' && $count < $post['page_limit']);
        } catch (Exception $e) {
            restore_error_handler();
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        restore_error_handler();

        $tab_values = Sanitizer::unsanitize($tab_values);
        usort($tab_values, function ($a, $b) {
            return strnatcmp($a['text'], $b['text']);
        });
        $ret['results'] = $tab_values;
        $ret['count']   = $count;

        return ($json === true) ? json_encode($ret) : $ret;
    }

    public static function ldapErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (0 === error_reporting()) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params)
    {

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo _n('LDAP directory', 'LDAP directories', 1);
        echo "</td>";
        echo "<td>";
        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $opt = [
            'name'                 => 'authldaps_id',
                'value'                => $params['authldaps_id'],
            'condition'            => ['is_active' => 1],
            'on_change'             => "plugin_metademands_changeLDAP('$root_doc', this)",
//                'display_emptychoice'  => false,
//                'rand'                 => $rand,
        ];
        AuthLDAP::dropdown($opt);
        echo "</td>";

        echo "<td>";
        echo __('Filter', 'metademands');
        echo "</td>";
        echo "<td>";
        echo Html::input('ldap_filter', ['value' => $params["ldap_filter"], 'size' => 50]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Attribute', 'metademands');
        echo "</td>";
        echo "<td>";
        RuleRightParameter::dropdown([
            'name'                 => 'ldap_attribute',
                'value'                => $params['ldap_attribute'],
        ]);
        echo "</td>";
        echo "<td colspan='2'>";
        echo "</td>";
        echo "</tr>";
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        //        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
        echo "</td>";
        echo "<td class = 'dropdown-valuetocheck'>";
        self::showValueToCheck($fieldoption, $params);
        echo "</td>";

        echo "<script type = \"text/javascript\">
                 $('td.dropdown-valuetocheck select').on('change', function() {
                 let formOption = [
                     " . $params['ID'] . ",
                         $(this).val(),
                         $('select[name=\"plugin_metademands_tasks_id\"]').val(),
                         $('select[name=\"fields_link\"]').val(),
                         $('select[name=\"hidden_link\"]').val(),
                         $('select[name=\"hidden_block\"]').val(),
                         JSON.stringify($('select[name=\"childs_blocks[][]\"]').val()),
                         $('select[name=\"users_id_validate\"]').val(),
                         $('select[name=\"checkbox_id\"]').val()
                  ];

                     reloadviewOption(formOption);
                 });";


        echo " </script>";

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params);
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        switch ($params["item"]) {
            default:
                $dbu = new DbUtils();
                if ($item = $dbu->getItemForItemtype($params["item"])) {
                    //               if ($params['value'] == 'group') {
                    //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                    //               } else {
                    $name = "check_value";
                    //               }

                    $params['item']::Dropdown(["name" => $name,
                        "value" => $params['check_value'],
                        'used' => $already_used,
                        'display_emptychoice' => false,
                        'toadd' => [-1 => __('Not null value', 'metademands')]]);
                }
                //                else {
                //                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                //                        $elements[-1] = __('Not null value', 'metademands');
                //                        if (is_array(json_decode($params['custom_values'], true))) {
                //                            $elements += json_decode($params['custom_values'], true);
                //                        }
                //                        foreach ($elements as $key => $val) {
                //                            if ($key != 0) {
                //                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                //                            }
                //                        }
                //                    } else {
                //                        $elements[-1] = __('Not null value', 'metademands');
                //                        if (is_array(json_decode($params['custom_values'], true))) {
                //                            $elements += json_decode($params['custom_values'], true);
                //                        }
                //                        foreach ($elements as $key => $val) {
                //                            $elements[$key] = urldecode($val);
                //                        }
                //                    }
                //                    Dropdown::showFromArray(
                //                        "check_value",
                //                        $elements,
                //                        ['value' => $params['check_value'], 'used' => $already_used]
                //                    );
                //                }
                break;
        }
    }


    public static function showParamsValueToCheck($params)
    {
        if ($params['check_value'] == -1) {
            echo __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        echo Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
                    } else {
                        if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                            $elements = [];
                            if (is_array(json_decode($params['custom_values'], true))) {
                                $elements += json_decode($params['custom_values'], true);
                            }
                            foreach ($elements as $key => $val) {
                                if ($key != 0) {
                                    $elements[$key] = $params["item"]::getFriendlyNameById($key);
                                }
                            }
                            echo $elements[$params['check_value']];
                        } else {
                            $elements = [];
                            if (!is_array($params['custom_values'])
                                && $params['custom_values'] != null
                                && is_array(json_decode($params['custom_values'], true))) {
                                $elements += json_decode($params['custom_values'], true);
                            }
                            foreach ($elements as $key => $val) {
                                $elements[$key] = urldecode($val);
                            }
                            echo $elements[$params['check_value']] ?? "";
                        }
                    }
                    break;
            }
        }
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } elseif ($check_value != $value
            && ($check_value != PluginMetademandsField::$not_null && $check_value != 0)) {
            return false;
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

    public static function fieldsMandatoryScript($data)
    {
//        $check_values = $data['options'] ?? [];
//        $id = $data["id"];
//
//        $name = "field[" . $data["id"] . "]";
//        if ($data["item"] == "ITILCategory_Metademands") {
//            $name = "field_plugin_servicecatalog_itilcategories_id";
//        }
//
//        $onchange = "";
//        $pre_onchange = "";
//        $post_onchange = "";
//        $debug = (isset($_SESSION['glpi_use_mode'])
//        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
//        if ($debug) {
//            $onchange = "console.log('fieldsHiddenScript-dropdownmeta $id');";
//        }
//
//        if (count($check_values) > 0) {
//            //Si la valeur est en session
//            if (isset($data['value'])) {
//                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
//            }
//
//
//            $onchange .= "$('[name=\"$name\"]').change(function() {";
//
//            $onchange .= "var tohide = {};";
//
//            $display = 0;
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($check_value['fields_link'] as $fields_link) {
//                    $onchange .= "if ($fields_link in tohide) {
//                            } else {
//                                tohide[$fields_link] = true;
//                            }
//                            if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
//                                tohide[$fields_link] = false;
//                            }";
//
//
//                    if (isset($data['value']) && $idc == $data['value']) {
//                        $display = $fields_link;
//                    }
//
//                    $onchange .= "$.each( tohide, function( key, value ) {
//                        if (value == true) {
//                            var id = '#metademands_wizard_red'+ key;
//                            $(id).html('');
//                            sessionStorage.setItem('hiddenlink$name', key);
//                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
//                            $('[name =\"field['+ key +']\"]').removeAttr('required');
//                        } else {
//                             var id = '#metademands_wizard_red'+ key;
//                             var fieldid = 'field'+ key;
//                             $(id).html('*');
//                             $('[name =\"field[' + key + ']\"]').attr('required', 'required');
//                             //Special case Upload field
//                                  sessionStorage.setItem('mandatoryfile$name', key);
//                                 " . PluginMetademandsFieldoption::checkMandatoryFile($fields_link, $name) . "
//                        }
//                    });
//              ";
//                }
//
//                if ($display > 0) {
//                    $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
//                }
//
//                $onchange .= "});";
//            }
//            echo Html::scriptBlock(
//                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
//            );
//        }
    }


    public static function taskScript($data)
    {

//        $check_values = $data['options'] ?? [];
//        $metaid = $data['plugin_metademands_metademands_id'];
//        $id = $data["id"];
//
//        $script = "";
//        $script2 = "";
//        $debug = (isset($_SESSION['glpi_use_mode'])
//        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
//        if ($debug) {
//            $script = "console.log('taskScript-dropdown $id');";
//        }
//
//        if (count($check_values) > 0) {
//            //Si la valeur est en session
//            if (isset($data['value'])) {
//                $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
//            }
//
//            $title = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
//            $nextsteptitle = __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";
//
//
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
//                    if ($tasks_id) {
//                        if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
//                            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
//                            $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
//                            $script .= "});";
//                        }
//                    }
//                }
//            }
//
//            $name = "field[" . $data["id"] . "]";
//            $script .= "$('[name=\"$name\"]').change(function() {";
//            $script .= "var tohide = {};";
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
//                    $script .= "if ($tasks_id in tohide) {
//                        } else {
//                            tohide[$tasks_id] = true;
//                        }
//                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
//                            tohide[$tasks_id] = false;
//                        }";
//
//                    $script .= "$.each( tohide, function( key, value ) {
//                        if (value == true) {
//                            $.ajax({
//                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
//                                     data: { tasks_id: $tasks_id,
//                                  used: 0 },
//                                  success: function(response){
//                                       if (response != 1) {
//                                           document.getElementById('nextBtn').innerHTML = '$title'
//                                       }
//                                    },
//                                });
//                        } else {
//                             $.ajax({
//                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
//                                     data: { tasks_id: $tasks_id,
//                                  used: 1 },
//                                  success: function(response){
//                                       if (response != 1) {
//                                           document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
//                                       }
//                                    },
//                                });
//
//                        }
//                    });
//              ";
//                }
//            }
//            $script .= "});";
//
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($check_value['plugin_metademands_tasks_id'] as $tasks_id) {
//                    if (is_array(PluginMetademandsFieldParameter::_unserialize($data['default']))) {
//                        $default_values = PluginMetademandsFieldParameter::_unserialize($data['default']);
//
//                        foreach ($default_values as $k => $v) {
//                            if ($v == 1) {
//                                if ($idc == $k) {
//                                    if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 1)) {
//                                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
//                                        $script .= "document.getElementById('nextBtn').innerHTML = '$nextsteptitle'";
//                                        $script .= "});";
//                                    }
//                                } else {
//                                    if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
//                                        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
//                                        $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
//                                        $script .= "});";
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
//        }
    }

    public static function fieldsHiddenScript($data)
    {

//        $check_values = $data['options'] ?? [];
//        $id = $data["id"];
//
//        $name = "field[" . $data["id"] . "]";
//
//        $onchange = "";
//        $pre_onchange = "";
//        $post_onchange = "";
//        $debug = (isset($_SESSION['glpi_use_mode'])
//        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
//        if ($debug) {
//            $onchange = "console.log('fieldsHiddenScript-dropdown $id');";
//        }
//
//        //add childs by idc
//        $childs_by_checkvalue = [];
//        foreach ($check_values as $idc => $check_value) {
//            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
//                $childs_blocks = json_decode($check_value['childs_blocks'], true);
//                if (isset($childs_blocks)
//                    && is_array($childs_blocks)
//                    && count($childs_blocks) > 0) {
//                    foreach ($childs_blocks as $childs) {
//                        if (is_array($childs)) {
//                            foreach ($childs as $child) {
//                                $childs_by_checkvalue[$idc][] = $child;
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        if (count($check_values) > 0) {
//            //default hide of all hidden links
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($check_value['hidden_link'] as $hidden_link) {
//                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
//                }
//            }
//
//            //Si la valeur est en session
//            if (isset($data['value'])) {
//                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
//            }
//
//            $onchange .= "$('[name=\"$name\"]').change(function() {";
//
//            $onchange .= "var tohide = {};";
//            $display = 0;
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($check_value['hidden_link'] as $hidden_link) {
//                    $onchange .= "if ($hidden_link in tohide) {
//                        } else {
//                            tohide[$hidden_link] = true;
//                        }
//                        if ( ($(this).val() == $idc ||  ($(this).val() != 0 && $idc == -1 ))) {
//                            tohide[$hidden_link] = false;
//                        }";
//
//                    if (isset($data['value']) && $idc == $data['value']) {
//                        $display = $hidden_link;
//                    }
//
//                    $onchange .= "$.each( tohide, function( key, value ) {
//                        if (value == true) {
//                            $('[id-field =\"field'+key+'\"]').hide();
//                            sessionStorage.setItem('hiddenlink$name', key);
//                            $('[name =\"field['+key+']\"]').removeAttr('required');
//                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name);
//
//                    if (is_array($childs_by_checkvalue)) {
//                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
//                            if ($idc == $k) {
//                                foreach ($childs_blocks as $childs) {
//                                    $onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').hide();
//                                            $('[bloc-id =\"subbloc" . $childs . "\"]').hide();
//                                            if (document.getElementById('ablock" . $childs . "'))
//                                                document.getElementById('ablock" . $childs . "').style.display = 'none';";
//                                }
//                            }
//                        }
//                    }
//                    $onchange .= "} else {
//                            $('[id-field =\"field'+key+'\"]').show();
//                        }
//                    });
//              ";
//                }
//            }
//
//            if ($display > 0) {
//                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
//                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
//            }
//            $onchange .= "});";
//
//            echo Html::scriptBlock('$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});');
//        }
    }

    public static function blocksHiddenScript($data)
    {
//        $metaid = $data['plugin_metademands_metademands_id'];
//        $check_values = $data['options'] ?? [];
//        $id = $data["id"];
//
//        $name = "field[" . $data["id"] . "]";
//
//        //add childs by idc
//        $childs_by_checkvalue = [];
//        foreach ($check_values as $idc => $check_value) {
//            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
//                $childs_blocks = json_decode($check_value['childs_blocks'], true);
//                if (isset($childs_blocks)
//                    && is_array($childs_blocks)
//                    && count($childs_blocks) > 0) {
//                    foreach ($childs_blocks as $childs) {
//                        if (is_array($childs)) {
//                            foreach ($childs as $child) {
//                                $childs_by_checkvalue[$idc][] = $child;
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        $script = "";
//        $script2 = "";
//        $debug = (isset($_SESSION['glpi_use_mode'])
//        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
//        if ($debug) {
//            $script = "console.log('blocksHiddenScript-dropdown $id');";
//        }
//
//        if (count($check_values) > 0) {
//
//            //by default - hide all
//            $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
//            if (!isset($data['value'])) {
//                $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
//            }
//
//            //Si la valeur est en session
//            if (isset($data['value'])) {
//                $script .= "$('[name=\"$name\"]').val(" . $data['value'] . ").trigger('change');";
//            }
//
//
//            $script .= "$('[name=\"$name\"]').change(function() {";
//
//            $script .= "var tohide = {};";
//
//            $display = 0;
//            foreach ($check_values as $idc => $check_value) {
//                foreach ($check_value['hidden_block'] as $hidden_block) {
//                    $blocks_idc = [];
//
//                    $script .= "if ($(this).val() == $idc || $idc == -1 ) {";
//
//                    //specific for radio / dropdowns - one value
//                    $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
//
//                    $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
//                document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
//                $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
//                $('[bloc-id =\"subbloc'+$hidden_block+'\"]').show();";
//                    $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
//
//                    if (is_array($childs_by_checkvalue)) {
//                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
//                            if ($idc == $k) {
//                                foreach ($childs_blocks as $childs) {
//                                    $options = getAllDataFromTable('glpi_plugin_metademands_fieldoptions',
//                                        ['hidden_block' => $childs]);
//                                    if (count($options) == 0) {
//                                        $script .= "if (document.getElementById('ablock" . $childs . "'))
//                                            document.getElementById('ablock" . $childs . "').style.display = 'block';
//                                            $('[bloc-id =\"bloc" . $childs . "\"]').show();
//                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields(
//                                                $metaid,
//                                                $childs
//                                            );
//                                    }
//                                }
//                            }
//                        }
//                    }
//
//                    if (isset($data['value']) && $idc == $data['value']) {
//                        $display = $hidden_block;
//                    }
//
//                    $script .= " }";
//
//                    $script .= "if ($(this).val() != $idc) {";
//                    if (is_array($blocks_idc) && count($blocks_idc) > 0) {
//                        foreach ($blocks_idc as $k => $block_idc) {
//                            $script .= "if (document.getElementById('ablock" . $block_idc . "'))
//                                    document.getElementById('ablock" . $block_idc . "').style.display = 'none';
//                                    $('[bloc-id =\"bloc" . $block_idc . "\"]').hide();
//                                    $('[bloc-id =\"subbloc" . $block_idc . "\"]').hide();";
//                        }
//                    }
//                    $script .= " }";
//
//                    $script .= "if ($(this).val() == 0 ) {";
//                    $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
//                    $script .= " }";
//                }
//            }
//
//            if ($display > 0) {
//                $script2 .= "if (document.getElementById('ablock" . $display . "'))
//                document.getElementById('ablock" . $display . "').style.display = 'block';
//                $('[bloc-id =\"bloc" . $display . "\"]').show();
//                $('[bloc-id =\"subbloc" . $display . "\"]').show();";
//            }
//
//            $script .= "});";
//
//            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
//        }
    }

    public static function checkConditions($data, $metaparams)
    {

        foreach ($metaparams as $key => $val) {
            if (isset($metaparams[$key])) {
                $$key = $metaparams[$key];
            }
        }

        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $onchange = "window.metademandconditionsparams = {};
                        metademandconditionsparams.submittitle = '$submittitle';
                        metademandconditionsparams.nextsteptitle = '$nextsteptitle';
                        metademandconditionsparams.use_condition = '$use_condition';
                        metademandconditionsparams.show_rule = '$show_rule';
                        metademandconditionsparams.show_button = '$show_button';
                        metademandconditionsparams.use_richtext = '$use_richtext';
                        metademandconditionsparams.richtext_ids = {$richtext_id};
                        metademandconditionsparams.root_doc = '$root_doc';";
        $name = "field[" . $data["id"] . "]";
        $onchange .= "$('[name=\"$name\"]').change(function() {";
        $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
        $onchange .= "});";

        echo Html::scriptBlock(
            '$(document).ready(function() {' . $onchange . '});'
        );
    }

    public static function getFieldValue($field)
    {
        return $field['value'];
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {

        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            switch ($field['item']) {
                default:
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
                    break;
            }
        }

        return $result;
    }

}
