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
 * Class PluginMetademandsExport
 */
class PluginMetademandsExport extends CommonDBTM
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
        return __('Export', 'metademands');

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
                return PluginMetademandsMetademand::getTypeName();
            } elseif ($item->getType() == 'PluginMetademandsMetademand' && $this->canUpdate()) {
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
                $form->showExportFromFormcreator($item->getID());
                break;
            case 'PluginMetademandsMetademand':
                $form = new self();
                $form->showExportFromMetademands($item->getID());

                break;
        }

        return true;
    }

   /**
    * Configuring
    *
    * @param $ID
    */
    static function showExportFromFormcreator($ID)
    {

        echo "<form name='form' method='post' action='" . self::getFormURL() . "' enctype='multipart/form-data'>";
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo Html::hidden('plugin_formcreator_forms_id', ['value' => $ID]);
        echo "<tr class='tab_bg_1'>";

        echo "<td class='left'>";
        echo __('Export the form to XML format for use with metademands plugin', 'metademands');
        echo "</td>";
        echo "<td class='center'>";
        echo Html::submit(__('Export XML', 'metademands'), ['name' => 'exportFormcreatorXML', 'class' => 'btn btn-primary']);
        echo "</td>";

        echo "</tr>";
        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * Configuring
     *
     * @param $ID
     */
    static function showExportFromMetademands($ID)
    {

        echo "<form name='form' method='post' action='" . self::getFormURL() . "' enctype='multipart/form-data'>";
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $ID]);
        echo "<tr class='tab_bg_1'>";

        echo "<td class='left'>";
        echo __('Export the metademand to XML format for use on another GLPI', 'metademands');
        echo "</td>";
        echo "<td class='center'>";
        echo Html::submit(__('Export XML', 'metademands'), ['name' => 'exportMetademandsXML', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";
        if (Plugin::isPluginActive("formcreator")) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='left'>";
            echo __('Export the metademand to JSON format for use with formcreator plugin', 'metademands');
            echo "</td>";
            echo "<td class='center'>";
            echo Html::submit(
                __('Export JSON', 'metademands'),
                ['name' => 'exportMetademandsJSON', 'class' => 'btn btn-primary']
            );
            echo "</td>";
        }
        echo "</tr>";
        echo "</table></div>";
        Html::closeForm();
    }

    public static function exportAsXMLForMetademands($id)
    {
        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($id);
        $fields = $metademands->fields;
        $metatranslation = new PluginMetademandsMetademandTranslation();
        $translations = $metatranslation->find([
            'items_id' => $metademands->getID(),
            'itemtype' => self::getType()
        ]);
        foreach ($translations as $id => $translation) {
            $fields['translations']['meta_translation' . $id] = $translation;
        }
        $metafield = new PluginMetademandsField();
        $metafieldoption = new PluginMetademandsFieldOption();
        $metafieldparameter = new PluginMetademandsFieldParameter();
        $metafieldcustom = new PluginMetademandsFieldCustomvalue();
        $metafieldfreetablefield = new PluginMetademandsFreetablefield();
        $stepconfig = new PluginMetademandsConfigstep();
        $step = new PluginMetademandsStep();
        $condition = new PluginMetademandsCondition();
        $fields['metafields'] = [];
        $fields['stepconfig'] = [];
        $fields['step'] = [];
        $fields['metafieldparameters'] = [];
        $fields['metafieldoptions'] = [];
        $fields['metafieldcustoms'] = [];
        $fields['metafieldfreetablefields'] = [];

        $stepconfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->getID()]);
        $fields['stepconfig'] = $stepconfig->fields;

        $steps = $step->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($steps as $id => $ste) {
            $fields['step'][$id] = $ste;
        }

        //TODO GroupConfig

        $metafields = $metafield->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($metafields as $id => $metafield) {
            $fields['metafields']['field' . $id] = $metafield;

            $metafieldparameters = $metafieldparameter->find(['plugin_metademands_fields_id' => $metafield["id"]]);
            foreach ($metafieldparameters as $idparameters => $metafieldparam) {
                $fields['metafieldparameters']['fieldparameters' . $idparameters] = $metafieldparam;
            }

            $metaconditions = $condition->find(['plugin_metademands_fields_id' => $metafield['id']]);
            if (!empty($metaconditions)) {
                foreach ($metaconditions as $key => $value) {
                    $fields['metafields']['field' . $id]['condition_' . $key] = $value;
                }
            }

            $metafieldoptions = $metafieldoption->find(['plugin_metademands_fields_id' => $metafield["id"]]);
            foreach ($metafieldoptions as $idoptions => $metafieldopt) {
                $fields['metafieldoptions']['fieldoptions' . $idoptions] = $metafieldopt;
            }

            $metafieldcustoms = $metafieldcustom->find(['plugin_metademands_fields_id' => $metafield["id"]]);
            foreach ($metafieldcustoms as $idcustoms => $metafieldcusto) {
                $fields['metafieldcustoms']['fieldcustoms' . $idcustoms] = $metafieldcusto;
            }
            $metafieldfreetablefields = $metafieldfreetablefield->find(['plugin_metademands_fields_id' => $metafield["id"]]);
            foreach ($metafieldfreetablefields as $idfreetables => $metafieldfreetable) {
                $fields['metafieldfreetablefields']['freetablefields' . $idfreetables] = $metafieldfreetable;
            }
        }

        $fieldtranslation = new PluginMetademandsFieldTranslation();
        foreach ($fields['metafields'] as $id => $f) {
            $translationsfield = $fieldtranslation->find([
                'items_id' => $f['id'],
                'itemtype' => PluginMetademandsField::getType()
            ]);
            foreach ($translationsfield as $k => $v) {
                $fields['metafields'][$id]['fieldtranslations']['translation'] = $v;
            }
        }
        $resourceMeta = new PluginMetademandsMetademand_Resource();
        $resourceMeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands->getID()]);
        $fields['resource'] = $resourceMeta->fields;
        $meta_Task = new PluginMetademandsTask();
        $tasks = $meta_Task->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        $fields['tasks'] = [];
        foreach ($tasks as $id => $task) {
            $fields['tasks']['task' . $id] = $task;
        }
        $metaTask = new PluginMetademandsMetademandTask();
        $metatasks = $metaTask->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($metatasks as $id => $task) {
            $fields['metatasks']['metatask' . $id] = $task;
        }

        $ticketTask = new PluginMetademandsTicketTask();
        $metaMailTask = new PluginMetademandsMailTask();

        foreach ($fields['tasks'] as $id => $task) {
            if ($task['type'] == PluginMetademandsTask::TICKET_TYPE) {
                $ticketTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']]);
                $fields['tasks'][$id]['tickettask'] = $ticketTask->fields;
            }
            if ($task['type'] == PluginMetademandsTask::MAIL_TYPE) {
                $metaMailTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']]);
                $fields['tasks'][$id]['mailtask'] = $metaMailTask->fields;
            }
        }

        $xml = new SimpleXMLElement(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?><metademand><version>" . PLUGIN_METADEMANDS_VERSION . "</version></metademand>"
        );

        self::toXml($xml, $fields);

        $safeName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $metademands->getField('name'));
        $safeName = mb_ereg_replace("([\.]{2,})", '', $safeName);
        $name = "/metademands/" . $safeName . ".xml";

        $xml->saveXML(GLPI_PLUGIN_DOC_DIR . $name);

        return "_plugins" . $name;
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

    public static function exportAsXMLFromFormcreator($plugin_formcreator_forms_id)
    {
        $form = new PluginFormcreatorForm();
        $form->getFromDB($plugin_formcreator_forms_id);

        $fields = $form->fields;

        //TODOXML Use target for object_to_create & type & category (PluginFormcreatorTargetTicket..)
        //TODOXML groups
        //TODOXML Traductions ?
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
        if ($type === 'dropdown_multiple' && $item === 'User') {
            return 'actor';
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

    public static function exportAsJSONForFormcreator($id)
    {
        //TODOJSON case not null value -> add regex
        //TODOJSON target PluginFormcreatorTargetTicket
        //TODOJSON rights
        //TODOJSON Traductions ?
        //TODOJSON child tickets ?
        $metademands = new PluginMetademandsMetademand();
        $metademands->getFromDB($id);
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
                if (isset($params['custom_values'][1])) {
                    $decodedUrl = urldecode($params['custom_values'][1]);
                    $question['description'] = "<a href=\"$decodedUrl\" target=\"_blank\">$decodedUrl</a>";
                } else {
                    $question['description'] = __('No link', 'metademands');
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

            if (is_array($params['custom_values'])) {
                $custom_values= [];
                foreach ($params['custom_values'] as $k => $customs) {
                    if (isset($customs['name'])) {
                        $custom_values[] = $customs['name'];
                    }
                }
                $question['values'] = json_encode($custom_values);
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

        $json['forms'][] = $form;
        $jsonOutput = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $safeName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $metademands->getField('name'));
        $safeName = mb_ereg_replace("([\.]{2,})", '', $safeName);
        $name = "/metademands/" . $safeName . ".json";

        $file = fopen(GLPI_PLUGIN_DOC_DIR .$name,'w+') or die("File not found");
        fwrite($file, $jsonOutput);
        fclose($file);

        return "_plugins" . $name;

    }

    public static function showImportForm()
    {
        echo "<div class='center'>";
        echo "<form name='import_file_form' id='import_file_form' method='post'
            action='" . self::getFormURL() . "' enctype='multipart/form-data'>";
        echo "<table class='tab_cadre' width='30%' cellpadding='5'>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __("Metademand file to import", 'metademands');
        echo "</td>";
        echo "<td>";
        //      echo Html::file(['name'=>'meta_file', 'accept' => 'text/*']);
        echo "<input class='form-control' type='file' name='meta_file' accept='text/*'>";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td  class='center' colspan='2'>";
        echo Html::submit(__('Import', 'metademands'), ['name' => 'import_file', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }



    public static function importXml()
    {
        if (isset($_FILES['meta_file'])) {
            if (!count($_FILES['meta_file'])
                || empty($_FILES['meta_file']['name'])
                || !is_file($_FILES['meta_file']['tmp_name'])
            ) {
                switch ($_FILES['meta_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        Session::addMessageAfterRedirect(
                            __('File too large to be added.'),
                            false,
                            ERROR
                        );
                        return false;
                        break;

                    case UPLOAD_ERR_NO_FILE:
                        Session::addMessageAfterRedirect(__('No file specified', 'metademands'), false, ERROR);
                        return false;
                        break;
                }
            } else {
                $tmp = explode(".", $_FILES['meta_file']['name']);
                $extension = array_pop($tmp);
                if (Toolbox::getMime($_FILES['meta_file']['tmp_name'], 'text') && $extension == "xml") {
                    // Unlink old picture (clean on changing format)
                    $filename = "tmpfileMeta";
                    $picture_path = GLPI_PLUGIN_DOC_DIR . "/metademands/{$filename}.$extension";
                    Document::renameForce($_FILES['meta_file']['tmp_name'], $picture_path);
                    $file = $picture_path;
                } else {
                    Session::addMessageAfterRedirect(
                        __('The file is not an XML file', 'metademands'),
                        false,
                        ERROR
                    );
                    return false;
                }
            }
        }


        // $xml = simplexml_load_file(GLPI_PLUGIN_DOC_DIR . '/test.xml');
        $xml = simplexml_load_file($file);
        $json = json_encode($xml);
        $datas = json_decode($json, true);
        $metademand = new PluginMetademandsMetademand();
        $oldId = $datas['id'];
        unset($datas['id']);
        unset($datas['date_creation']);
        unset($datas['date_mod']);
        unset($datas['itilcategories_id']);
        $datas['entities_id'] = $_SESSION['glpiactive_entity'];

        $mapTableField = [];
        $mapTableFieldReverse = [];
        $mapTableCheckValue = [];

        $fields = [];
        $stepconfig = [];
        $steps = [];

        $version = 0;
        if (isset($datas['version'])) {
            $version = $datas['version'];
        }

        if (isset($datas['metafields'])) {
            $fields = $datas['metafields'];
        }

        if (isset($datas['stepconfig'])) {
            $stepconfig = $datas['stepconfig'];
        }

        if (isset($datas['step'])) {
            $steps = $datas['step'];
        }

        if (isset($datas['metafields'])) {
            $fields = $datas['metafields'];
        }

        $fieldoptions = [];
        if (isset($datas['metafieldoptions'])) {
            $fieldoptions = $datas['metafieldoptions'];
        }
        $fieldoldparams = [];
        $fieldparameters = [];
        if (isset($datas['metafieldparameters'])) {
            $fieldparameters = $datas['metafieldparameters'];
        }
        $fieldoldcustoms = [];
        $fieldcustoms = [];
        if (isset($datas['metafieldcustoms'])) {
            $fieldcustoms = $datas['metafieldcustoms'];
        }

        $fieldfreetablefields = [];
        if (isset($datas['metafieldfreetablefields'])) {
            $fieldfreetablefields = $datas['metafieldfreetablefields'];
        }

        $tasks = [];
        if (isset($datas['tasks'])) {
            $tasks = $datas['tasks'];
        }

        $resource = [];
        if (isset($datas['resources'])) {
            $resource = $datas['resources'];
        }

        $metatasks = [];
        if (isset($datas['metatasks'])) {
            $metatasks = $datas['metatasks'];
        }

        $translations = [];
        if (isset($datas['translations'])) {
            $translations = $datas['translations'];
        }

        foreach ($datas as $key => $data) {
            if (is_array($data) && empty($data)) {
                $datas[$key] = '';
            } elseif (!is_array($data)) {
                $datas[$key] = Html::entity_decode_deep($data);
            }
        }
        $datas = Toolbox::addslashes_deep($datas);
        $newIDMeta = $metademand->add($datas);
        //      $translations = [];

        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        foreach ($fields as $k => $field) {
            $metaconditions = [];
            foreach ($field as $key => $f) {
                $fields[$k][$key] = Html::entity_decode_deep($f);

                $fieldoldparams[$k][$key] = Html::entity_decode_deep($f);

                if ($key == "custom_values" && isset($field['type'])
                    && in_array($field['type'], $allowed_customvalues_types)
                    || (isset($field['item']) && in_array($field['item'], $allowed_customvalues_items))
                ) {
                    $fieldoldcustoms[$k]["id"] = $fields[$k]["id"];
                    if (is_array($f)) {
                        $fieldoldcustoms[$k][$key] = PluginMetademandsFieldParameter::_serializeArray($f);
                    } else {
                        $fieldoldcustoms[$k][$key] = PluginMetademandsFieldParameter::_unserialize($f);
                    }

//                    if ($field['type'] != 'yesno') {
//                        $fieldcustoms[$k][$key] = PluginMetademandsFieldParameter::_serialize($fields[$k][$key]);
//                    }
                    if (is_null($fields[$k][$key])) {
                        $fieldoldcustoms[$k][$key] = "[]";
                    }
//                } elseif ($key == "comment_values") {
//                    $fields[$k][$key] = PluginMetademandsField::_unserialize($f);
//                    $fields[$k][$key] = PluginMetademandsField::_serialize($fields[$k][$key]);
//                    if (is_null($fields[$k][$key])) {
//                        $fields[$k][$key] = "[]";
//                    }
                } elseif (str_contains($key, 'condition')) {
                    $metaconditions[] = $f;
//                } elseif ($key == "default_values") {
//                    $fields[$k][$key] = PluginMetademandsField::_unserialize($f);
//                    $fields[$k][$key] = PluginMetademandsField::_serialize($fields[$k][$key]);
//                    if (is_null($fields[$k][$key])) {
//                        $fields[$k][$key] = "[]";
//                    }
                } elseif ($key == "informations_to_display") {
                    $fields[$k][$key] = PluginMetademandsFieldParameter::_unserialize($f);
                    $fields[$k][$key] = PluginMetademandsFieldParameter::_serialize($fields[$k][$key]);
                    // legacy support
                    if (isset($field['item']) && $field['item'] == 'User' && $f == '[]') {
                        $fields[$k][$key] = '["full_name"]';
                    } elseif (is_null($fields[$k][$key])) {
                        $fields[$k][$key] = "[]";
                    }
                } elseif ($key == "fieldtranslations") {
                    $fieldstranslations = $f;
                } else {
                    if (is_array($f) && empty($f)) {
                        $fields[$k][$key] = '';
                    }
                }
            }

            $oldIDField = $fields[$k]["id"];
            unset($fields[$k]["id"]);
            $fields[$k]['entities_id'] = $_SESSION['glpiactive_entity'];

            $fields[$k] = Toolbox::addslashes_deep($fields[$k]);

            $fields[$k]["plugin_metademands_metademands_id"] = $newIDMeta;
            $fields[$k]["date_creation"] = $_SESSION['glpi_currenttime'];
            $fields[$k]["date_mod"] = $_SESSION['glpi_currenttime'];

            $metaField = new PluginMetademandsField();
            $newIDField = $metaField->add($fields[$k]);
            $condition = new PluginMetademandsCondition();
            if (count($metaconditions) > 0) {
                foreach ($metaconditions as $cond) {
                    unset($cond['id']);
                    $cond['plugin_metademands_fields_id'] = $newIDField;
                    $cond['plugin_metademands_metademands_id'] = $newIDMeta;
                    $condition->add($cond);
                }
            }
            $mapTableField[$oldIDField] = $newIDField;
            $mapTableFieldReverse[$newIDField] = $oldIDField;
            if (isset($fieldstranslations)) {
                foreach ($fieldstranslations as $fieldstranslation) {
                    unset($fieldstranslation['id']);
                    $fieldstranslation['value'] = Html::entity_decode_deep(
                        Toolbox::addslashes_deep($fieldstranslation['value'])
                    );
                    $fieldstranslation['field'] = Html::entity_decode_deep(
                        Toolbox::addslashes_deep($fieldstranslation['field'])
                    );
                    $fieldstranslation['items_id'] = $newIDField;

                    $trans = new PluginMetademandsFieldTranslation();
                    $trans->add($fieldstranslation);
                }
            }
            //TODO Change fields id for link_to_user fields
        }
        $mapTableTask = [];
        $mapTableTaskReverse = [];


        foreach ($tasks as $k => $task) {
            $oldIDTask = $task['id'];
            unset($task['id']);
            unset($task['ancestors_cache']);
            unset($task['sons_cache']);
            $task = Toolbox::addslashes_deep($task);
            $tickettask = $task['tickettask'] ?? [];
            $mailtask = $task['mailtask'] ?? [];

            foreach ($task as $key => $val) {
                $task[$key] = Html::entity_decode_deep($val);
            }
            $task['entities_id'] = $_SESSION['glpiactive_entity'];

            $task['plugin_metademands_metademands_id'] = $newIDMeta;

            $meta_task = new PluginMetademandsTask();
            $newIDTask = $meta_task->add($task);

            $mapTableTask[$oldIDTask] = $newIDTask;
            $mapTableTaskReverse[$newIDTask] = $oldIDTask;


            if (is_array($tickettask) && !empty($tickettask)) {
                unset($tickettask['id']);
                foreach ($tickettask as $key => $val) {
                    if (is_array($val) && empty($val)) {
                        $tickettask[$key] = '';
                    } elseif (!is_array($val)) {
                        $tickettask[$key] = Html::entity_decode_deep($val);
                    }
                }
                $tickettask['plugin_metademands_tasks_id'] = $newIDTask;
                $tickettaskP = new PluginMetademandsTicketTask();
                $tickettaskP->add($tickettask);
            }

            if (is_array($mailtask) && !empty($mailtask)) {
                unset($mailtask['id']);

                foreach ($mailtask as $key => $val) {
                    if (is_array($val) && empty($val)) {
                        $mailtask[$key] = '';
                    } elseif (!is_array($val)) {
                        $mailtask[$key] = Html::entity_decode_deep($val);
                    }
                }
                $mailtask['plugin_metademands_tasks_id'] = $newIDTask;
                $mailtaskP = new PluginMetademandsMailTask();
                $mailtaskP->add($mailtask);
            }
        }

        //Add new params & update fields
        $fieldMetaparam = new PluginMetademandsFieldParameter();

        if ($version < "3.3.11") {
            foreach ($fieldoldparams as $new => $old) {
                $plugin_metademands_fields_id = $old["id"] ?? 0;
                $empty_values = PluginMetademandsFieldParameter::_serialize([]);;

                $toUpdate["custom_values"] = $old["custom_values"] ?? $empty_values;
                $toUpdate["default_values"] = $old["default_values"] ?? $empty_values;
                $toUpdate["comment_values"] = $old["comment_values"] ?? $empty_values;
                $toUpdate["hide_title"] = $old["hide_title"] ?? 0;
                $toUpdate["is_mandatory"] = $old["is_mandatory"] ?? 0;
                $toUpdate["max_upload"] = $old["max_upload"] ?? 0;
                $toUpdate["regex"] = $old["regex"] ?? "";
                $toUpdate["color"] = $old["color"] ?? "";
                $toUpdate["row_display"] = $old["row_display"] ?? 0;
                $toUpdate["is_basket"] = $old["is_basket"] ?? 0;
                $toUpdate["display_type"] = $old["display_type"] ?? 0;
                $toUpdate["used_by_ticket"] = $old["used_by_ticket"] ?? 0;
                $toUpdate["used_by_child"] = $old["used_by_child"] ?? 0;
                $toUpdate["link_to_user"] = $old["link_to_user"] ?? 0;
                $toUpdate["default_use_id_requester"] = $old["default_use_id_requester"] ?? 0;
                $toUpdate["default_use_id_requester_supervisor"] = $old["default_use_id_requester_supervisor"] ?? 0;
                $toUpdate["use_future_date"] = $old["use_future_date"] ?? 0;
                $toUpdate["use_date_now"] = $old["use_date_now"] ?? 0;
                $toUpdate["additional_number_day"] = $old["additional_number_day"] ?? 0;
                $toUpdate["informations_to_display"] = PluginMetademandsFieldParameter::_serialize(['full_name']);
                $toUpdate["use_richtext"] = $old["use_richtext"] ?? 0;
                $toUpdate["icon"] = $old["icon"] ?? "";
                $toUpdate["readonly"] = $old["readonly"] ?? 0;
                $toUpdate["hidden"] = $old["hidden"] ?? 0;

                if ($plugin_metademands_fields_id != 0
                    && isset($mapTableField[$plugin_metademands_fields_id])) {
                    $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                }
                $fielddata = new PluginMetademandsField();
                if ($fielddata->getFromDB($toUpdate['plugin_metademands_fields_id'])) {
                    $toUpdate["type"] = $fielddata->fields["type"];
                    $toUpdate["item"] = $fielddata->fields["item"];
                }

                $fieldMetaparam->add($toUpdate);
            }
        }

        if ($version >= "3.3.11") {
            foreach ($fieldparameters as $new => $old) {
                $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
                $empty_values = PluginMetademandsFieldParameter::_serialize([]);;

                $toUpdate["custom"] = $old["custom"] ?? $empty_values;
                $toUpdate["default"] = $old["default"] ?? $empty_values;
                $toUpdate["hide_title"] = $old["hide_title"] ?? 0;
                $toUpdate["is_mandatory"] = $old["is_mandatory"] ?? 0;
                $toUpdate["max_upload"] = $old["max_upload"] ?? 0;
                $toUpdate["regex"] = $old["regex"] ?? "";
                $toUpdate["color"] = $old["color"] ?? "";
                $toUpdate["row_display"] = $old["row_display"] ?? 0;
                $toUpdate["is_basket"] = $old["is_basket"] ?? 0;
                $toUpdate["display_type"] = $old["display_type"] ?? 0;
                $toUpdate["used_by_ticket"] = $old["used_by_ticket"] ?? 0;
                $toUpdate["used_by_child"] = $old["used_by_child"] ?? 0;
                $toUpdate["link_to_user"] = $old["link_to_user"] ?? 0;
                $toUpdate["default_use_id_requester"] = $old["default_use_id_requester"] ?? 0;
                $toUpdate["default_use_id_requester_supervisor"] = $old["default_use_id_requester_supervisor"] ?? 0;
                $toUpdate["use_future_date"] = $old["use_future_date"] ?? 0;
                $toUpdate["use_date_now"] = $old["use_date_now"] ?? 0;
                $toUpdate["additional_number_day"] = $old["additional_number_day"] ?? 0;

                if (isset($old["informations_to_display"]) && $old["informations_to_display"] != NULL) {
                    if (PluginMetademandsFieldParameter::_serialize($old["informations_to_display"]) != NULL) {
                        $toUpdate["informations_to_display"] = PluginMetademandsFieldParameter::_serialize(
                            $old["informations_to_display"]
                        );
                    }
                } else {
                    $toUpdate["informations_to_display"] =PluginMetademandsFieldParameter::_serialize(['full_name']);
                }
                $toUpdate["use_richtext"] = $old["use_richtext"] ?? 0;
                $toUpdate["icon"] = $old["icon"] ?? "";
                $toUpdate["readonly"] = $old["readonly"] ?? 0;
                $toUpdate["hidden"] = $old["hidden"] ?? 0;

                if ($plugin_metademands_fields_id != 0
                    && isset($mapTableField[$plugin_metademands_fields_id])) {
                    $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                }

                $fieldMetaparam->add($toUpdate);
            }
        }

        //Add new custom values & update fields
        $fieldMetacustom = new PluginMetademandsFieldCustomvalue();
        $custom_values = [];
        if ($version < "3.3.11") {
            foreach ($fieldoldcustoms as $new => $old) {
                $plugin_metademands_fields_id = $old["id"] ?? 0;
                $custom_values = $old["custom_values"] ?? [];

                if (count($custom_values) > 0) {
                    foreach ($custom_values as $rank => $custom_value) {
                        $name = Toolbox::addslashes_deep($custom_value);
                        $oldrank = $rank;
                        $is_default = $old["is_default"] ?? 0;
                        $comment = $old["comment"] ?? "";

                        $toUpdate = [];
                        if ($name != "") {
                            $toUpdate["name"] = $name;
                        }
                        if ($is_default != 0) {
                            $toUpdate["is_default"] = $is_default;
                        }
                        if ($comment != "") {
                            $toUpdate["comment"] = Toolbox::addslashes_deep($comment);
                        }
                        $toUpdate["rank"] = $oldrank - 1;

                        if ($plugin_metademands_fields_id != 0
                            && isset($mapTableField[$plugin_metademands_fields_id])) {
                            $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                        }
                        $newcustomid = $fieldMetacustom->add($toUpdate);
                        $mapTableCheckValue[$old["id"]][$oldrank] = $newcustomid;
                    }
                }
            }
        }

        if ($version >= "3.3.11") {
            foreach ($fieldcustoms as $new => $old) {
                $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
                $name = $old["name"] ?? "";
                $is_default = $old["is_default"] ?? 0;
                $comment = $old["comment"] ?? "";
                $icon = $old["icon"] ?? "";
                $rank = $old["rank"] ?? 0;

                $toUpdate = [];
                if ($name != "") {
                    $toUpdate["name"] = Toolbox::addslashes_deep($name);
                }
                if ($is_default != 0) {
                    $toUpdate["is_default"] = $is_default;
                }
                if ($comment != "") {
                    $toUpdate["comment"] = Toolbox::addslashes_deep($comment);
                }
                if ($icon != "") {
                    $toUpdate["icon"] = $icon;
                }
                if ($rank != 0) {
                    $toUpdate["rank"] = $rank;
                }

                if ($plugin_metademands_fields_id != 0
                    && isset($mapTableField[$plugin_metademands_fields_id])) {
                    $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                }

                $newcustomfield = $fieldMetacustom->add($toUpdate);
                $mapTableCheckValue[$old["id"]] = $newcustomfield;
            }
        }

        //Add new freetable fields
        $fieldMetafreetablefield = new PluginMetademandsFreetablefield();
        if ($version >= "3.3.20") {
            foreach ($fieldfreetablefields as $new => $old) {
                $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
                $name = $old["name"] ?? "";
                $internal_name = $old["internal_name"] ?? "";
                $type = $old["type"] ?? "text";
                $comment = $old["comment"] ?? "";
                $dropdown_values = $old["dropdown_values"] ?? "";
                $is_mandatory = $old["is_mandatory"] ?? 0;
                $rank = $old["rank"] ?? 0;

                $toUpdate = [];
                if ($name != "") {
                    $toUpdate["name"] = Toolbox::addslashes_deep($name);
                }
                if ($internal_name != "") {
                    $toUpdate["internal_name"] = Toolbox::addslashes_deep($internal_name);
                }
                if ($type != "") {
                    $toUpdate["type"] = $type;
                }
                if ($comment != "") {
                    $toUpdate["comment"] = Toolbox::addslashes_deep($comment);
                }
                if ($dropdown_values != "") {
                    $toUpdate["dropdown_values"] = Toolbox::addslashes_deep($dropdown_values);
                }
                if ($is_mandatory) {
                    $toUpdate["is_mandatory"] = $is_mandatory;
                }
                if ($rank != 0) {
                    $toUpdate["rank"] = $rank;
                }

                if ($plugin_metademands_fields_id != 0
                    && isset($mapTableField[$plugin_metademands_fields_id])) {
                    $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                }

                $newfreetablefield = $fieldMetafreetablefield->add($toUpdate);
                $mapTableCheckValue[$old["id"]] = $newfreetablefield;
            }
        }

        //Add new options & update fields
        $fieldMetaopt = new PluginMetademandsFieldOption();
        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        foreach ($fieldoptions as $new => $old) {
//            $fieldMeta->getFromDBByCrit(["plugin_metademands_fileds_id" => $new]);

//            if (isset($field['type'])
//                && !in_array($field['type'], $allowed_customvalues_types)
//                || (isset($field['item']) && !in_array($field['item'], $allowed_customvalues_items))
//            ) {
            $check_value = $old["check_value"] ?? 0;
            $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
            $plugin_metademands_tasks_id = $old["plugin_metademands_tasks_id"] ?? 0;
            $fields_link = $old["fields_link"] ?? 0;
            $hidden_link = $old["hidden_link"] ?? 0;
            $hidden_block = $old["hidden_block"] ?? 0;
            $users_id_validate = $old["users_id_validate"] ?? 0;
            $childs_blocks = $old["childs_blocks"] ?? [];
            $checkbox_value = $old["checkbox_value"] ?? 0;
            $checkbox_id = $old["checkbox_id"] ?? 0;
            $hidden_block_same_block = $old["hidden_block_same_block"] ?? 0;
//            $parent_field_id = $old["parent_field_id"]??0;
//
            $toUpdate = [];
            if ($check_value != 0) {
                $toUpdate["check_value"] = $check_value;
            }
            if ($version >= "3.3.11") {
                if ($check_value != 0
                    && isset($mapTableCheckValue[$check_value])) {
                    $toUpdate['check_value'] = $mapTableCheckValue[$check_value];
                }
            } else {
                if ($check_value != 0
                    && isset($mapTableCheckValue[$plugin_metademands_fields_id][$check_value])) {
                    $toUpdate['check_value'] = $mapTableCheckValue[$plugin_metademands_fields_id][$check_value];
                }
            }

            if ($plugin_metademands_tasks_id != 0 && isset($mapTableTask[$plugin_metademands_tasks_id])) {
                $toUpdate["plugin_metademands_tasks_id"] = $mapTableTask[$plugin_metademands_tasks_id];
            }
            if ($fields_link != 0 && isset($mapTableField[$fields_link])) {
                $toUpdate["fields_link"] = $mapTableField[$fields_link];
            }
            if ($hidden_link != 0 && isset($mapTableField[$hidden_link])) {
                $toUpdate["hidden_link"] = $mapTableField[$hidden_link];
            }
            if ($hidden_block != 0) {
                $toUpdate["hidden_block"] = $hidden_block;
            }
            if ($users_id_validate != 0) {
                $toUpdate["users_id_validate"] = $users_id_validate;
            }
            if ($childs_blocks) {
                $toUpdate["childs_blocks"] = $childs_blocks;
            }
            if ($checkbox_value != 0) {
                $toUpdate["checkbox_value"] = $checkbox_value;
            }
            if ($checkbox_id != 0) {
                $toUpdate["checkbox_id"] = $checkbox_id;
            }
            if ($hidden_block_same_block != 0) {
                $toUpdate["hidden_block_same_block"] = $hidden_block_same_block;
            }
//            if ($parent_field_id != 0 && isset($mapTableField[$parent_field_id])) {
//                $toUpdate["parent_field_id"] = $mapTableField[$parent_field_id];
//            }
//
            if ($plugin_metademands_fields_id != 0
                && isset($mapTableField[$plugin_metademands_fields_id])) {
                $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
            }

            $fieldMetaopt->add($toUpdate);
//            }
        }

        foreach ($mapTableTaskReverse as $new => $old) {
            $meta_task = new PluginMetademandsTask();
            $meta_task->getFromDB($new);
            $toUpdate = [];
            $toUpdate['id'] = $new;
            if (isset($mapTableTask[$meta_task->fields["plugin_metademands_tasks_id"]])) {
                $toUpdate["plugin_metademands_tasks_id"] = $mapTableTask[$meta_task->fields["plugin_metademands_tasks_id"]];
            }
            $meta_task->update($toUpdate);
        }

        if (!empty($resource)) {
            $resource['plugin_metademands_metademands_id'] = $newIDMeta;
            $resource_meta = new PluginMetademandsMetademand_Resource();
            $resource_meta->add($resource);
        }

        if (!empty($stepconfig)) {
            $stepconfig_meta = new PluginMetademandsConfigstep();
            if($stepconfig_meta->getFromDBByCrit(['plugin_metademands_metademands_id' => $newIDMeta])) {
                $stepconfig['id'] = $stepconfig_meta->fields['id'];
                $stepconfig['plugin_metademands_metademands_id'] = $newIDMeta;
                $stepconfig['see_blocks_as_tab'] = $stepconfig['see_blocks_as_tab'] ?? 0;
                $stepconfig['link_user_block'] = $stepconfig['link_user_block'] ?? 0;
                $stepconfig['multiple_link_groups_blocks'] = $stepconfig['multiple_link_groups_blocks'] ?? 0;
                $stepconfig['add_user_as_requester'] = $stepconfig['add_user_as_requester'] ?? 0;
                $stepconfig['supervisor_validation'] = $stepconfig['supervisor_validation'] ?? 0;
                $stepconfig['step_by_step_interface'] = $stepconfig['step_by_step_interface'] ?? 0;
                $stepconfig_meta->update($stepconfig);
            }
        }

        if (!empty($steps)) {
            foreach ($steps as $key => $ste) {
                $meta_step = new PluginMetademandsStep();
                $metas['block_id'] = $ste['block_id'];
                $metas['groups_id'] = $ste['groups_id'];
                $metas['only_by_supervisor'] = $ste['only_by_supervisor'];
                $metas['reminder_delay'] = $ste['reminder_delay'];
                $metas['message'] = $ste['message'];
                $metas['plugin_metademands_metademands_id'] = $newIDMeta;
                $meta_step->add($metas);
            }
        }

        if (!empty($metatasks)) {
            foreach ($metatasks as $key => $metatask) {
                $meta_metatask = new PluginMetademandsMetademandTask();
                $metat = [];
                $metat['plugin_metademands_metademands_id'] = $newIDMeta;
                $metat['plugin_metademands_tasks_id'] = $mapTableTask[$metatask['plugin_metademands_tasks_id']];
                $meta_metatask->add($metat);
            }
        }

        if (!empty($translations)) {
            foreach ($translations as $key => $trans) {
                $meta_translation = new PluginMetademandsMetademandTranslation();
                $trans['value'] = Html::entity_decode_deep(Toolbox::addslashes_deep($trans['value']));
                $trans['field'] = Html::entity_decode_deep($trans['field']);
                unset($trans['id']);
                $trans['items_id'] = $newIDMeta;

                $meta_translation->add($trans);
            }
        }
        unlink($file);

        return $newIDMeta;
    }
}
