<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
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


if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsFormcreator
 */
class PluginMetademandsFormcreator extends CommonDBTM
{

    static $rightname = 'plugin_metademands';

    static function getTable($classname = null)
    {
        return PluginMetademandsMetademand::getTable();
    }

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
    static function getTypeName($nb = 0)
    {
        return PluginMetademandsMetademand::getTypeName();
    }

   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @param CommonGLPI $item Item on which the tab need to be displayed
    * @param int        $withtemplate is a template object ? (default 0)
    *
    * @return string tab name
    * @since version 0.83
    *
    */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item->getType() == 'PluginFormcreatorForm' && $this->canUpdate()) {
                return self::getTypeName();
            }
        }
        return '';
    }

   /**
    * show Tab content
    *
    * @param CommonGLPI $item Item on which the tab need to be displayed
    * @param integer    $tabnum tab number (default 1)
    * @param int        $withtemplate is a template object ? (default 0)
    *
    * @return boolean
    * @since version 0.83
    *
    */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'PluginFormcreatorForm':
                $form = new self();
                $form->showSetupFromFormcreator($item->getID());

                break;
        }

        return true;
    }

   /**
    * Configuring
    *
    * @param $ID
    */
    static function showSetupFromFormcreator($ID)
    {

        echo "<form name='form' method='post' action='" . self::getFormURL() . "' enctype='multipart/form-data'>";
        echo Html::hidden('plugin_formcreator_forms_id', ['value' => $ID]);
        echo "<tr class='tab_bg_1'><td class='center' colspan='3'>";
        echo Html::submit(__('Export XML', 'metademands'), ['name' => 'exportXML', 'class' => 'btn btn-primary']);
        echo "</td></tr>";

        echo "</table></div>";

        Html::closeForm();
    }

    static function transformFieldTypeForMetademands($type, $item = null)
    {
        if ($type === 'dropdown' && $item === 'Location') {
            return 'dropdown';
        }
        $map = [
//                'select' => 'dropdown',
            'glpiselect' => 'dropdown_object',
            'select' => 'dropdown_meta',
            'multiselect' => 'dropdown_multiple',
            'description' => 'title',
//                'description' => 'title-block',
//            'description' => 'informations',
            'text' => 'text',
//                'text' => 'tel',
            'email' => 'email',
//                'text' => 'url',
            'textarea' => 'textarea',
//            'select' => 'yesno',
            'checkboxes' => 'checkbox',
            'radios' => 'radio',
            'integer' => 'number',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
            'file' => 'upload',
//                'description' => 'link',
            'requesttype' => '**meta type**',
        ];
        return $map[$type] ?? "";
    }

    public static function exportAsXMLForMetademands($plugin_formcreator_forms_id)
    {
        $form = new PluginFormcreatorForm();
        $form->getFromDB($plugin_formcreator_forms_id);

        $fields = $form->fields;

        //TODOXML Use target for object_to_create & type & category (PluginFormcreatorTargetTicket..)
        //TODOXML groups
        //TODOXML Traductions ?
        //TODOXML Massive export ?
        //TODOXML child tickets ?

        $fields['type'] = 2;
        $fields['object_to_create'] = "Ticket";
        unset($fields['access_rights']);
        unset($fields['show_rule']);
        unset($fields['uuid']);
        unset($fields['formanswer_name']);
        unset($fields['users']);
        unset($fields['groups']);
        unset($fields['profiles']);

        $entities_id = $fields['entities_id'];
//        PluginFormcreatorSection
        $sections = getAllDataFromTable('glpi_plugin_formcreator_sections',
            ['plugin_formcreator_forms_id' => $plugin_formcreator_forms_id]);

        $fields['metafields'] = [];
        $fields['metafieldparameters'] = [];
        $fields['metafieldcustoms'] = [];
        $fields['metafieldoptions'] = [];

        $metafields = [];
        foreach ($sections as $ids => $section) {

            $questions = getAllDataFromTable('glpi_plugin_formcreator_questions',
                ['plugin_formcreator_sections_id' => $ids]);

            foreach ($questions as $idq => $question) {

                $metafields['type'] = self::transformFieldTypeForMetademands($question['fieldtype'], $question['itemtype']);
                if (empty($metafields['type'])) {
                    continue;
                }

                $metafields['id'] = $idq;
                $metafields['entities_id'] = $entities_id;
                $sec = new PluginFormcreatorSection();
                $sec->getFromDB($question['plugin_formcreator_sections_id']);
                $metafields['rank'] = $sec->fields['order'];
                $metafields['order'] = $question['row'] + 1;

                $metafields['name'] = $question['name'];
                $metafields['item'] = $question['itemtype'];
                $metafields['comment'] = $question['description'];
                $fields['metafields']['field' . $idq] = $metafields;

                $fields['metafieldparameters']['fieldparameters' . $idq]['plugin_metademands_fields_id'] = $idq;
                $fields['metafieldparameters']['fieldparameters' . $idq]['color'] = "#000000";
                $fields['metafieldparameters']['fieldparameters' . $idq]['link_to_user'] = "0";
                $fields['metafieldparameters']['fieldparameters' . $idq]['is_mandatory'] = $question['required'];

                $customvalues = [];

                if (in_array($metafields['type'], PluginMetademandsField::$field_customvalues_types)
                    || $question['fieldtype'] = 'yesno') {

                    if (!empty($question['values'])) {
                        $values = Toolbox::jsonDecode($question['values'], true);

                        $cpt = 0;
                        foreach ($values as $k => $value) {
                            $idcv = $idq + $cpt;
                            $customvalues[$idq][$k]['id'] = $idcv;
                            $customvalues[$idq][$k]['name'] = $value;
                            $customvalues[$idq][$k]['rank'] = $cpt;
                            $customvalues[$idq][$k]['plugin_metademands_fields_id'] = $idq;
                            $cpt++;
                        }
                        $tempid = 0;

                        foreach ($customvalues as $idc => $cnt) {
                            foreach ($cnt as $i => $customvalue) {
                                $tempid++;
                                $fields['metafieldcustoms']['fieldcustoms' .$idc. $tempid] = $customvalue;
                            }
                        }
                    }
                }
                $options = [];
                $conditions = getAllDataFromTable('glpi_plugin_formcreator_conditions',
                    ['itemtype' => 'PluginFormcreatorQuestion', 'items_id' => $idq]);
                if (count($conditions) > 0) {

                    $cpt = 0;
                    foreach ($conditions as $key => $val) {
//                        $idcd = $idq + $cpt;
                        $options[$idq][$key]['id'] = $val['id'];

                        $showValue = $val['show_value'];
                        if ($question['fieldtype'] === 'yesno') {
                            if ($val['show_value'] == 'non') {
                                $showValue = 1;
                            } elseif ($val['show_value'] == 'oui') {
                                $showValue = '2';
                            }
                        }  else if (in_array($metafields['type'], PluginMetademandsField::$field_customvalues_types)) {
                            $fieldcustomvalues = new PluginMetademandsFieldCustomvalue();
                            $fieldcustomvalues->getFromDBByCrit(["name" => $val['show_value']]);
                            $showValue = $fieldcustomvalues->fields['id'] ?? "";
                        }

                        $options[$idq][$key]['check_value'] = $showValue;
                        $options[$idq][$key]['hidden_link'] = $val['items_id'];
                        $options[$idq][$key]['plugin_metademands_fields_id'] = $val['plugin_formcreator_questions_id'];;
                        $cpt++;
                    }
                    $tempid = 0;

                    foreach ($options as $ido => $cnt) {
                        foreach ($cnt as $j => $option) {
                            $tempid++;
                            $fields['metafieldoptions']['fieldoptions' .$ido. $tempid] = $option;
                        }
                    }
                }
            }
        }




//        $metatranslation = new PluginMetademandsMetademandTranslation();
//        $translations = $metatranslation->find([
//            'items_id' => $this->getID(),
//            'itemtype' => self::getType()
//        ]);
//        foreach ($translations as $id => $translation) {
//            $fields['translations']['meta_translation' . $id] = $translation;
//        }
//        $metafield = new PluginMetademandsField();
//        $metafieldoption = new PluginMetademandsFieldOption();
//        $metafieldparameter = new PluginMetademandsFieldParameter();
//        $metafieldcustom = new PluginMetademandsFieldCustomvalue();
//        $metafieldfreetablefield = new PluginMetademandsFreetablefield();
//        $stepconfig = new PluginMetademandsConfigstep();
//        $step = new PluginMetademandsStep();
//        $condition = new PluginMetademandsCondition();
//        $fields['metafields'] = [];
//        $fields['stepconfig'] = [];
//        $fields['step'] = [];
//        $fields['metafieldparameters'] = [];
//        $fields['metafieldoptions'] = [];
//        $fields['metafieldcustoms'] = [];
//        $fields['metafieldfreetablefields'] = [];
//
//        $stepconfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->getID()]);
//        $fields['stepconfig'] = $stepconfig->fields;
//
//        $steps = $step->find(['plugin_metademands_metademands_id' => $this->getID()]);
//        foreach ($steps as $id => $ste) {
//            $fields['step'][$id] = $ste;
//        }
//
//        //TODO GroupConfig
//
//        $metafields = $metafield->find(['plugin_metademands_metademands_id' => $this->getID()]);
//        foreach ($metafields as $id => $metafield) {
//            $fields['metafields']['field' . $id] = $metafield;
//
//            $metafieldparameters = $metafieldparameter->find(['plugin_metademands_fields_id' => $metafield["id"]]);
//            foreach ($metafieldparameters as $idparameters => $metafieldparam) {
//                $fields['metafieldparameters']['fieldparameters' . $idparameters] = $metafieldparam;
//            }
//
//            $metaconditions = $condition->find(['plugin_metademands_fields_id' => $metafield['id']]);
//            if (!empty($metaconditions)) {
//                foreach ($metaconditions as $key => $value) {
//                    $fields['metafields']['field' . $id]['condition_' . $key] = $value;
//                }
//            }
//
//            $metafieldoptions = $metafieldoption->find(['plugin_metademands_fields_id' => $metafield["id"]]);
//            foreach ($metafieldoptions as $idoptions => $metafieldopt) {
//                $fields['metafieldoptions']['fieldoptions' . $idoptions] = $metafieldopt;
//            }
//
//            $metafieldcustoms = $metafieldcustom->find(['plugin_metademands_fields_id' => $metafield["id"]]);
//            foreach ($metafieldcustoms as $idcustoms => $metafieldcusto) {
//                $fields['metafieldcustoms']['fieldcustoms' . $idcustoms] = $metafieldcusto;
//            }
//            $metafieldfreetablefields = $metafieldfreetablefield->find(['plugin_metademands_fields_id' => $metafield["id"]]);
//            foreach ($metafieldfreetablefields as $idfreetables => $metafieldfreetable) {
//                $fields['metafieldfreetablefields']['freetablefields' . $idfreetables] = $metafieldfreetable;
//            }
//        }
//
//        $fieldtranslation = new PluginMetademandsFieldTranslation();
//        foreach ($fields['metafields'] as $id => $f) {
//            $translationsfield = $fieldtranslation->find([
//                'items_id' => $f['id'],
//                'itemtype' => PluginMetademandsField::getType()
//            ]);
//            foreach ($translationsfield as $k => $v) {
//                $fields['metafields'][$id]['fieldtranslations']['translation'] = $v;
//            }
//        }
//        $resourceMeta = new PluginMetademandsMetademand_Resource();
//        $resourceMeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->getID()]);
//        $fields['resource'] = $resourceMeta->fields;
//        $meta_Task = new PluginMetademandsTask();
//        $tasks = $meta_Task->find(['plugin_metademands_metademands_id' => $this->getID()]);
//        $fields['tasks'] = [];
//        foreach ($tasks as $id => $task) {
//            $fields['tasks']['task' . $id] = $task;
//        }
//        $metaTask = new PluginMetademandsMetademandTask();
//        $metatasks = $metaTask->find(['plugin_metademands_metademands_id' => $this->getID()]);
//        foreach ($metatasks as $id => $task) {
//            $fields['metatasks']['metatask' . $id] = $task;
//        }
//
//        $ticketTask = new PluginMetademandsTicketTask();
//        $metaMailTask = new PluginMetademandsMailTask();
//
//        foreach ($fields['tasks'] as $id => $task) {
//            if ($task['type'] == PluginMetademandsTask::TICKET_TYPE) {
//                $ticketTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']]);
//                $fields['tasks'][$id]['tickettask'] = $ticketTask->fields;
//            }
//            if ($task['type'] == PluginMetademandsTask::MAIL_TYPE) {
//                $metaMailTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']]);
//                $fields['tasks'][$id]['mailtask'] = $metaMailTask->fields;
//            }
//        }

        $xml = new SimpleXMLElement(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?><metademand><version>" . PLUGIN_METADEMANDS_VERSION . "</version></metademand>"
        );

        self::toXml($xml, $fields);

        $safeName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $form->getField('name'));
        $safeName = mb_ereg_replace("([\.]{2,})", '', $safeName);
        $name = "/metademands/" . $safeName . ".xml";

        $xml->saveXML(GLPI_PLUGIN_DOC_DIR . $name);

        return "_plugins" . $name;
    }

    public static function toXml(SimpleXMLElement &$parent, array &$data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $parent->addChild($key);
                self::toXml($child, $value);
            } else {
                // if the key is an integer, it needs text with it to actually work.

                if ($key != 0 && $key == (int)$key) {
                    $key = "key_$key";
                }

                //            if($key == 'name' || $key == 'completename' || $key == 'comments' || $key == 'label2')
                if ($value != null) {
                    $value = htmlspecialchars($value, ENT_NOQUOTES);
                    $parent->addChild($key, $value);
                }
            }
        }
    }


    static function generateCustomCode()
    {
        $part1 = bin2hex(random_bytes(4));
        $part2 = bin2hex(random_bytes(4));
        $part3 = bin2hex(random_bytes(7));
        $part4 = '';
        for ($i = 0; $i < 7; $i++) {
            $part4 .= random_int(0, 9);
        }
        return "{$part1}-{$part2}-{$part3}.{$part4}";
    }

    static function transformFieldTypeForFormcreator($type, $item = null)
    {
        if ($type === 'dropdown' && $item === 'Location') {
            return 'dropdown';
        }
        $map = [
            'dropdown' => 'select',
            'dropdown_object' => 'glpiselect',
            'dropdown_meta' => 'select',
            'dropdown_multiple' => 'multiselect',
            'title' => 'description',
            'title-block' => 'description',
            'informations' => 'description',
            'text' => 'text',
            'tel' => 'text',
            'email' => 'email',
            'url' => 'text',
            'textarea' => 'textarea',
            'yesno' => 'select',
            'checkbox' => 'checkboxes',
            'radio' => 'radios',
            'number' => 'integer',
//        'range' => '',
//        'freetable' => '',
//        'basket' => '',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
//        'date_interval' => '',
//        'datetime_interval' => '',
            'upload' => 'file',
            'link' => 'description',
//        'signature' => '',
//        'parent_field' => ''
            '**meta type**' => 'requesttype',
        ];
        return $map[$type] ?? "";
    }

    static function getOptionsByFieldId($prefix, $fieldId)
    {

        $options = getAllDataFromTable('glpi_plugin_metademands_fieldoptions',
            ['hidden_link' => $fieldId]);

        $renamedOptions = [];

        $countByQuestionId = [];
        foreach ($options as $option) {
            $id = $prefix . $option['plugin_metademands_fields_id'];
            $countByQuestionId[$id] = ($countByQuestionId[$id] ?? 0) + 1;
        }

        foreach ($options as $option) {

            $fieldorigin = new PluginMetademandsField();
            $fieldorigin->getFromDB($option['plugin_metademands_fields_id']);

            $fieldcustomvalues = new PluginMetademandsFieldCustomvalue();

            $showCondition = 1;
            $showValue = "";
            $showOrder = 1;

            if ($fieldorigin->fields['type'] === 'yesno') {
                if ((int)$option['check_value'] === 1) {
                    $showValue = 'non';
                    $showOrder = 2;
                } elseif ((int)$option['check_value'] === 2) {
                    $showValue = 'oui';
                }
            } else if ($fieldorigin->fields['type'] === 'text') {
                $showCondition = 2;

            } else if (in_array($fieldorigin->fields["type"], PluginMetademandsField::$field_customvalues_types)) {
                $fieldcustomvalues->getFromDB($option['check_value']);
                $showValue = $fieldcustomvalues->fields['name'] ?? "";
                $showOrder = 1; //$fieldcustomvalues->fields['rank'] ?? 0
            }
            $questionId = $prefix . $option['plugin_metademands_fields_id'];
            $logic = $countByQuestionId[$questionId] > 1 ? 2 : 1;
            $renamedOptions[] = [
                'itemtype' => "PluginFormcreatorQuestion",
                'plugin_formcreator_questions_id' => $questionId,
                'show_condition' => $showCondition,
                'show_value' => $showValue,
                'show_logic' => $logic,
                'order' => $showOrder,
                'uuid' => $prefix . $option['id'],
                //'child_blocks' => json_decode($option['childs_blocks']),
                // 'checkbox_value' => $option['checkbox_value'],
                //'checkbox_id' => $option['checkbox_id'],
                //'parent_field_id' => $option['parent_field_id']

            ];
        }

        return $renamedOptions;
    }

    static function getSectionConditions($fieldId, $prefix)
    {

        $options = getAllDataFromTable('glpi_plugin_metademands_fieldoptions',
            ['plugin_metademands_fields_id' => $fieldId]);

        $conditions = [];
        $countByQuestionId = [];
        foreach ($options as $option) {
            $id = $prefix . $option['plugin_metademands_fields_id'];
            $countByQuestionId[$id] = ($countByQuestionId[$id] ?? 0) + 1;
        }

        foreach ($options as $option) {

            if (isset($option['hidden_block'])
                && $option['hidden_block'] > 0) {

                $fieldorigin = new PluginMetademandsField();
                $fieldorigin->getFromDB($option['plugin_metademands_fields_id']);

                $fieldcustomvalues = new PluginMetademandsFieldCustomvalue();

                $showCondition = 1;
                $showValue = "";
                $showOrder = 1;

                if ($fieldorigin->fields['type'] === 'yesno') {
                    if ((int)$option['check_value'] === 1) {
                        $showValue = 'non';
                        $showOrder = 2;
                    } elseif ((int)$option['check_value'] === 2) {
                        $showValue = 'oui';
                    }
                } else if ($fieldorigin->fields['type'] === 'text') {
                    $showCondition = 2;

                } else if (in_array($fieldorigin->fields["type"], PluginMetademandsField::$field_customvalues_types)) {
                    $fieldcustomvalues->getFromDB($option['check_value']);
                    $showValue = $fieldcustomvalues->fields['name'] ?? "";
                    $showOrder = 1; //$fieldcustomvalues->fields['rank'] ?? 0
                }
                $questionId = $prefix . $option['plugin_metademands_fields_id'];
                $logic = $countByQuestionId[$questionId] > 1 ? 2 : 1;
                $conditions[$option['hidden_block']][] = [
                    'itemtype' => "PluginFormcreatorSection",
                    'plugin_formcreator_questions_id' => $questionId,
                    'show_condition' => $showCondition,
                    'show_value' => $showValue,
                    'show_logic' => $logic,
                    'order' => $showOrder,
                    'uuid' => $prefix . $option['id'] . '1'
                ];
            }

        }
        return $conditions;
    }

    static function generateFieldParameters($fieldtype, $fieldId, $prefix)
    {

        switch ($fieldtype) {

            case 'text':
            case 'textarea':
            case 'integer':
                return [
                    $fieldtype => [
                        "range" => [
                            "range_min" => 0,
                            "range_max" => 0,
                            "fieldname" => "range",
                            "uuid" => $prefix . $fieldId
                        ],
                        "regex" => [
                            "regex" => null,
                            "range_max" => 0,
                            "fieldname" => "regex",
                            "uuid" => $prefix . $fieldId
                        ]
                    ]
                ];
            case 'checkboxes':
            case 'multiselect':
                return [
                    $fieldtype => [
                        "range" => [
                            "range_min" => 0,
                            "range_max" => 0,
                            "fieldname" => "range",
                            "uuid" => $prefix . $fieldId
                        ]
                    ]
                ];
            default:
                return [];
        }
    }

    public static function exportAsJSONForFormcreator($metademands)
    {
        //TODOJSON case not null value -> add regex
        //TODOJSON error reload page
        //TODOJSON cible
        //TODO test link case
        //TODOJSON rights
        //TODOJSON Traductions ?
        //TODOJSON Massive export ?
        //TODOJSON child tickets ?

        $metademands_id = $metademands->getID();
        $prefix = self::generateCustomCode();
        if (!$metademands_id) {
            die(json_encode(["error" => __('No item found')]));
        }
        $entity_name = "";

        $entity = new Entity();
        if ($entity->getFromDB($metademands->fields['entities_id'])) {
            $entity_name = $entity->fields['completename'];
        }

        // JSON array initialization for the response
        $json = [
            "schema_version" => PLUGIN_FORMCREATOR_SCHEMA_VERSION,
            "forms" => []
        ];

        // Basic form
        $form = [
            "name" => $metademands->fields['name'] ?? "",
            "is_recursive" => (int)($metademands->fields['is_recursive']),
            "icon" => $metademands->fields['icon'] ?? 0,
            "icon_color" => $metademands->fields['title_color'],
            "background_color" => $metademands->fields['background_color'],
            "access_rights" => 1,
            "description" => $metademands->fields['comment'],
            "content" => $metademands->fields['comment'],
            "is_active" => (int)($metademands->fields['is_active']),
            "language" => "",
            "helpdesk_home" => 0,
            "is_deleted" => $metademands->fields['is_deleted'],
            "validation_required" => 0,
            "is_default" => 0,
            "is_captcha_enabled" => 0,
            "show_rule" => $metademands->fields['show_rule'],
            "formanswer_name" => $metademands->fields['name'],
            "is_visible" => 0,
            "uuid" => $prefix . $metademands_id,
            "users" => [],
            "profiles" => [],
            "_entity" => $entity_name,
            "_plugin_formcreator_category" => "",
            "_profiles" => [],
            "_users" => [],
            "_groups" => [],
            "_sections" => [],
            "_conditions" => [],
            "_targets" => [
                "PluginFormcreatorTargetTicket" => [],
                "PluginFormcreatorTargetChange" => [],
                "PluginFormcreatorTargetProblem" => []
            ],
            "_validators" => [],
            "_translations" => []
        ];

        $metademands_groups_data = getAllDataFromTable('glpi_plugin_metademands_groups',
            ['plugin_metademands_metademands_id' => $metademands_id]);

        $formattedGroups = [];
        $IDGroups = [];
        if (!empty($metademands_groups_data)) {
            foreach ($metademands_groups_data as $groups) {
                $metagroup = new Group();
                $metagroup->getFromDB($groups['groups_id']);
                // Create a table to store groups in the desired format
                $formattedGroups[] = [
                    "uuid" => $prefix . $groups['groups_id'],
                    "_group" => $metagroup->fields['name']
                ];
                $IDGroups[] = $groups['groups_id'];
            }
        }

        // Add groups to the form
        $form["groups"] = $IDGroups;
        $form["_groups"] = $formattedGroups;


        $fields = getAllDataFromTable('glpi_plugin_metademands_fields',
            ['plugin_metademands_metademands_id' => $metademands_id]);


        $sections = [];

        $questionsAdded = [];
        foreach ($fields as $field) {
            $fieldId = $field['id'];

            $params = [];
            $fieldmeta = new PluginMetademandsField();
            if ($fieldmeta->getFromDB($fieldId)) {
                $params = PluginMetademandsField::getAllParamsFromField($fieldmeta);
            }

            // If this question has already been added, skip it.
            if (isset($questionsAdded[$fieldId])) {
                continue;
            }
            $questionsAdded[$fieldId] = true;
            $rank = $params['rank'];

            if (!isset($sections[$rank])) {
                $sections[$rank] = [
                    "name" => "Section " . $rank,
                    "order" => $rank,
                    "show_rule" => 1,
                    "uuid" => $prefix . $rank,
                    "_questions" => []
                ];

                if ($titles_block = $fieldmeta->find(['plugin_metademands_metademands_id' => $metademands_id,
                    'rank' => $rank,
                    'type' => 'title-block'])) {
                    foreach ($titles_block as $title_block) {
                        $sections[$rank]["name"] = $title_block['name'];
                    }
//                    continue;
                }
            }

            $fieldtype = self::transformFieldTypeForFormcreator($params['type'], $params['item']);

//            if (empty($fieldtype)) {
//                continue;
//            }
            // Créer la question
            if (!empty($fieldtype)) {
            $question = [
                "name" => $params['name'],
                "fieldtype" => $fieldtype,
                "required" => $params['is_mandatory'],
                "show_empty" => 1,
                "default_values" => "",
                "itemtype" => $params['item'] !== null ? $params['item'] : "",
                "values" => "",
                "description" => "",
                "row" => $params['order'],
                "col" => 0,
                "width" => 4,
                "show_rule" => 1,
                "uuid" => $prefix . $fieldId,
                "_conditions" => [],
                "_parameters" => self::generateFieldParameters($fieldtype, $fieldId, $prefix)
            ];
            if ($params['type'] === 'link') {

                if ($params['custom']) {
                    $decodedData = json_decode($params['custom'], true);

                    if (isset($decodedData['1'])) {
                        $decodedUrl = $decodedData['1'];

                        $decodedUrl = urldecode($decodedUrl);
                        $question['description'] = "<a href=\"$decodedUrl\" target=\"_blank\">$decodedUrl</a>";

                    } else {

                        $question['description'] = __('No link', 'metademands');
                    }
                }

            } else {

                $question['description'] = $params['label2'] . $params['comment'];
            }
            // Check if options are associated with this field
            $options = self::getOptionsByFieldId($prefix, $fieldId);
            if ($options) {
                $question['show_rule'] = 2;
                $question['_conditions'] = $options;
            }


            if ($params['type'] === 'dropdown_multiple' && $params['item'] === 'User') {
                //TODOJSON ??
//                $users = getUsers($pdo);
                $users = [];
                $question['values'] = json_encode($users);
            } else {

//                Toolbox::logInfo($params['custom_values']);
                if (is_array($params['custom_values'])) {
                    $custom_values= [];
                    foreach ($params['custom_values'] as $k => $customs) {
                        if (isset($customs['name'])) {
                            $custom_values[] = $customs['name'];
                        }
                    }
                    $question['values'] = json_encode($custom_values);
                }

            }
            if ($params['type'] === 'yesno') {
                $question['values'] = json_encode(["oui", "non"]);
                $question['itemtype'] = "other";
            }

            $sections[$rank]['_questions'][] = $question;
            }
        }
        foreach ($fields as $field) {
            $fieldtype = self::transformFieldTypeForFormcreator($params['type'], $params['item']);

            if (empty($fieldtype)) {
                continue;
            }
            $sectionConditions = self::getSectionConditions($field['id'], $prefix);
            foreach ($sectionConditions as $block => $cond) {
                $sections[$block]['_conditions'] = $cond;
                if (count($sections[$block]['_conditions']) > 0) {
                    $sections[$block]['show_rule'] = 2;
                }
            }
        }


        ksort($sections);
        $form['_sections'] = array_values($sections);
        $filename = 'metademand_' . date('Ymd_His') . '.json';
        $json['forms'][] = $form;
        $jsonOutput = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Force download in browser
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($jsonOutput));
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $jsonOutput;

    }
}
