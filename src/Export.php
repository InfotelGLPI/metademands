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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use CommonDropdown;
use CommonGLPI;
use CommonTreeDropdown;
use Document;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Form\Comment;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use GlpiPlugin\Metademands\Fields\Forminformation;
use GlpiPlugin\Resources\ContractType;
use Html;
use Session;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Toolbox;

/**
 * Class {
 */
class Export extends CommonDBTM
{
    public static string $rightname = 'plugin_metademands';

    /** Upper bound accepted for an imported meta-demand XML document, in bytes. */
    private const MAX_IMPORT_XML_BYTES = 5 * 1024 * 1024;

    /** Session entry of the uploaded file waiting for the confirmation of its import */
    private const PENDING_IMPORT_SESSION_KEY = 'plugin_metademands_pending_import';

    /** Name of the working copy of an uploaded file (moveImportedXmlFile()) */
    private const IMPORT_FILE_PATTERN = '/^import_[0-9a-f]+\.[0-9]+\.xml$/';

    /** Field tags of a text, #id# and #id.attr# (Metademand::resolvePlaceholders()) */
    private const FIELD_TAG_PATTERN = '/#([1-9][0-9]*)(?=(?:\.[A-Za-z0-9_]+)?#)/';

    /** Values of the item of a dropdown field which are not a class (Field::$field_specificobjects too) */
    private const FIELD_PSEUDO_ITEMS = ['other', 'ITILCategory_Metademands'];

    public static function getTable($classname = null)
    {
        return Metademand::getTable();
    }

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Export', 'metademands');
    }

    public static function getIcon()
    {
        return "ti ti-file-export";
    }

    /**
     * Get Tab Name used for itemtype
     *
     * NB : Only called for existing object
     *      Must check right on what will be displayed + template
     *
     * @param CommonGLPI $item Item on which the tab need to be displayed
     * @param int $withtemplate is a template object ? (default 0)
     *
     * @return string tab name
     * @since version 0.83
     *
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            if ($item->getType() == Form::class && $this->canUpdate()) {
                return self::createTabEntry(Metademand::getTypeName());
            } elseif ($item->getType() == Metademand::class && $this->canUpdate()) {
                return self::createTabEntry(self::getTypeName());
            }
        }
        return '';
    }

    /**
     * show Tab content
     *
     * @param CommonGLPI $item Item on which the tab need to be displayed
     * @param integer $tabnum tab number (default 1)
     * @param int $withtemplate is a template object ? (default 0)
     *
     * @return boolean
     * @since version 0.83
     *
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Form::class:
                $form = new self();
                $form->showExportFromGLPIForm($item->getID());
                break;
            case Metademand::class:
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
    public static function showExportFromGLPIForm($ID)
    {
        TemplateRenderer::getInstance()->display('@metademands/export_form.html.twig', [
            'target'       => self::getFormURL(),
            'hidden_name'  => 'forms_id',
            'hidden_value' => $ID,
            'rows'         => [
                [
                    'label'        => __('Export the form to XML format for use with metademands plugin', 'metademands'),
                    'button_name'  => 'exportFormGLPIXML',
                    'button_label' => __('Export XML', 'metademands'),
                ],
            ],
        ]);
    }

    /**
     * Configuring
     *
     * @param $ID
     */
    public static function showExportFromMetademands($ID)
    {
        TemplateRenderer::getInstance()->display('@metademands/export_form.html.twig', [
            'target'       => self::getFormURL(),
            'hidden_name'  => 'plugin_metademands_metademands_id',
            'hidden_value' => $ID,
            'rows'         => [
                [
                    'label'        => __('Export the metademand to XML format for use on another GLPI', 'metademands'),
                    'button_name'  => 'exportMetademandsXML',
                    'button_label' => __('Export XML', 'metademands'),
                ],
                [
                    'label'        => __('Export the metademand to JSON format for use with forms from GLPI', 'metademands'),
                    'button_name'  => 'exportMetademandsJSON',
                    'button_label' => __('Export JSON', 'metademands'),
                ],
            ],
        ]);
    }

    public static function exportAsXMLForMetademands($id)
    {
        $metademands = new Metademand();
        // The control belongs here rather than in each caller: front/export.form.php and
        // ajax/export_metademand.php both hand a client identifier straight to this method, which
        // serialises the whole definition - fields, options, conditions and generation tasks, so
        // notification recipients, assigned groups and ticket templates included.
        if (!$metademands->getFromDB($id)
            || !Session::haveAccessToEntity(
                $metademands->fields['entities_id'],
                $metademands->fields['is_recursive'],
            )) {
            throw new AccessDeniedHttpException();
        }
        $fields = $metademands->fields;
        $metatranslation = new MetademandTranslation();
        $translations = $metatranslation->find([
            'items_id' => $metademands->getID(),
            'itemtype' => Metademand::class,
        ]);
        foreach ($translations as $id => $translation) {
            $fields['translations']['meta_translation' . $id] = $translation;
        }
        $metafield = new Field();
        $metafieldoption = new FieldOption();
        $metafieldparameter = new FieldParameter();
        $metafieldcustom = new FieldCustomvalue();
        $metafieldfreetablefield = new Freetablefield();
        $stepconfig = new Configstep();
        $step = new Step();
        $condition = new Condition();
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
            $fields['step']['step' . $id] = $ste;
        }

        //TODO GroupConfig

        $metafields = $metafield->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($metafields as $id => $metafield) {
            $fields['metafields']['field' . $id] = $metafield;

            $metafieldparameters = $metafieldparameter->find(['plugin_metademands_fields_id' => $metafield["id"]]);
            foreach ($metafieldparameters as $idparameters => $metafieldparam) {
                $fields['metafieldparameters']['fieldparameters' . $idparameters] = $metafieldparam;
                // "Members of these groups": their names, their ids differing on another GLPI
                $group = new \Group();
                foreach (FieldParameter::getMemberGroupsIds($metafieldparam['custom'] ?? '') as $groups_id) {
                    if ($group->getFromDB($groups_id)) {
                        $fields['metafieldparameters']['fieldparameters' . $idparameters]['member_groups_names']['group' . $groups_id]
                            = $group->fields['completename'];
                    }
                }
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
            $metafieldfreetablefields = $metafieldfreetablefield->find(
                ['plugin_metademands_fields_id' => $metafield["id"]],
            );
            foreach ($metafieldfreetablefields as $idfreetables => $metafieldfreetable) {
                $fields['metafieldfreetablefields']['freetablefields' . $idfreetables] = $metafieldfreetable;
            }
        }

        $fieldtranslation = new FieldTranslation();
        foreach ($fields['metafields'] as $id => $f) {
            $translationsfield = $fieldtranslation->find([
                'items_id' => $f['id'],
                'itemtype' => Field::getType(),
            ]);
            foreach ($translationsfield as $k => $v) {
                $fields['metafields'][$id]['fieldtranslations']['translation' . $k] = $v;
            }
        }
        // Contract types of the Resources plugin leading to the meta-demand, several of them
        // maybe: their names as well, their ids differing on another GLPI
        $fields['resource_links'] = [];
        $resource_links = (new Metademand_Resource())->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($resource_links as $id => $resource_link) {
            $fields['resource_links']['resource_link' . $id] = $resource_link + [
                'contracttype_name' => self::getContractTypeName((int) $resource_link['plugin_resources_contracttypes_id']),
            ];
        }
        $meta_Task = new Task();
        $tasks = $meta_Task->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        $fields['tasks'] = [];
        foreach ($tasks as $id => $task) {
            $fields['tasks']['task' . $id] = $task;
        }
        $metaTask = new MetademandTask();
        $metatasks = $metaTask->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($metatasks as $id => $task) {
            $fields['metatasks']['metatask' . $id] = $task;
        }

        $ticketTask = new TicketTask();
        $metaMailTask = new MailTask();

        foreach ($fields['tasks'] as $id => $task) {
            // A task of the parent ticket keeps its content in a TicketTask as well. Read
            // into the same objects: a task without its row must not get the previous one
            if (($task['type'] == Task::TICKET_TYPE || $task['type'] == Task::TASK_TYPE)
                && $ticketTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']])) {
                $fields['tasks'][$id]['tickettask'] = $ticketTask->fields;
            }
            if ($task['type'] == Task::MAIL_TYPE
                && $metaMailTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $task['id']])) {
                $fields['tasks'][$id]['mailtask'] = $metaMailTask->fields;
            }
        }

        // Fields of the object to create: a value pointing at a GLPI object (user, group,
        // location…) carries the name of that object as well, its id differing on another GLPI
        $fields['ticketfields'] = [];
        $ticketfields = (new TicketField())->find(['plugin_metademands_metademands_id' => $metademands->getID()]);
        foreach ($ticketfields as $id => $ticketfield) {
            $fields['ticketfields']['ticketfield' . $id] = $ticketfield + TicketField::getExportedValueItem(
                (string) $metademands->fields['object_to_create'],
                $ticketfield,
            );
        }

        $xml = new SimpleXMLElement(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?><metademand><version>" . PLUGIN_METADEMANDS_VERSION . "</version></metademand>",
        );

        self::toXml($xml, $fields);

        $target = self::buildExportTarget($metademands->getField('name'), 'xml');
        if ($target === [] || $xml->saveXML($target['path']) === false) {
            Session::addMessageAfterRedirect(__('Unable to create the export file', 'metademands'), false, ERROR);
            return [];
        }

        return $target;
    }

    public static function transformFieldTypeFromMetademands($type, $item = null)
    {
        if ($type === QuestionTypeItemDropdown::class && $item === 'Location') {
            return 'dropdown';
        }
        $map = [
            QuestionTypeItemDropdown::class => 'dropdown',
            QuestionTypeRequester::class => 'dropdown_object',
            QuestionTypeItem::class => 'dropdown_object',
            QuestionTypeDropdown::class => 'dropdown_meta',
            //            QuestionTypeDropdown::class => 'dropdown_multiple',
            //            'description' => 'title',
            //            'description' => 'informations',
            QuestionTypeShortText::class => 'text',
            //                'text' => 'tel',
            QuestionTypeEmail::class => 'email',
            //                'text' => 'url',
            QuestionTypeLongText::class => 'textarea',
            //            QuestionTypeItemDropdown::class => 'yesno',
            QuestionTypeCheckbox::class => 'checkbox',
            QuestionTypeRadio::class => 'radio',
            QuestionTypeNumber::class => 'number',
            //            QuestionTypeDateTime::class => 'date',
            //            QuestionTypeDateTime::class => 'time',
            QuestionTypeDateTime::class => 'datetime',
            QuestionTypeFile::class => 'upload',
            //                'description' => 'link',
            QuestionTypeRequestType::class => '**meta type**',
        ];
        return $map[$type] ?? "";
    }

    public static function exportAsXMLFromGLPI($forms_forms_id)
    {
        //TODO
        $form = new Form();
        // The identifier comes from $_POST["forms_id"] with only a global right bit in front of
        // it; confront the core form with the session before serialising it.
        if (!$form->can($forms_forms_id, READ)) {
            throw new AccessDeniedHttpException();
        }

        $fields = $form->fields;

        //TODOXML Traductions ?
        //TODOXML child tickets ?

        $fields['type'] = 2;
        $fields['object_to_create'] = "Ticket";
        unset($fields['access_rights']);
        unset($fields['visibility_strategy']);
        unset($fields['uuid']);
        unset($fields['formanswer_name']);
        unset($fields['users']);
        unset($fields['groups']);
        unset($fields['profiles']);

        $entities_id = $fields['entities_id'];

        $sections = getAllDataFromTable(
            'glpi_forms_sections',
            ['forms_forms_id' => $forms_forms_id],
        );

        $fields['metafields'] = [];
        $fields['metafieldparameters'] = [];
        $fields['metafieldcustoms'] = [];
        $fields['metafieldoptions'] = [];

        // The header of the form, above its sections, becomes the information of the form
        $header = trim((string) ($fields['header'] ?? ''));
        unset($fields['header']);

        /** @var array<string, int> $questions_by_uuid question uuid => question id (destination conditions) */
        $questions_by_uuid = [];
        /** @var array<int, string> $question_names */
        $question_names = [];
        /** @var array<int, array<string, int>> $choices question id => option key => custom value id */
        $choices = [];
        /** @var array<int, array<string, string>> $choice_labels question id => option key => label */
        $choice_labels = [];
        /** @var array<int, string> $blocks */
        $blocks = [];
        /** @var list<array{block: int, comment: array<string, mixed>}> $comments */
        $comments = [];
        $secid = 0;
        foreach ($sections as $ids => $section) {
            $secid++;
            // The sections of a GLPI form are ranked from 0, the blocks of a metademand from 1
            $block = (int) $section['rank'] + 1;
            $blocks[$block] = (string) $block;
            $title_id = $ids . "00" . $secid;
            $fields['metafields']['field' . $title_id] = [
                'id'          => $title_id,
                'entities_id' => $entities_id,
                'rank'        => $block,
                'name'        => $section['name'],
                'type'        => 'title-block',
                'item'        => '',
            ];
            $fields['metafieldparameters']['fieldparameters' . $title_id] = [
                'plugin_metademands_fields_id' => $title_id,
                'color'                        => '#000000',
            ];

            $questions = getAllDataFromTable(
                'glpi_forms_questions',
                ['forms_sections_id' => $ids],
            );
            foreach ($questions as $idq => $question) {
                $config = json_decode((string) ($question['extra_data'] ?? ''), true);
                $config = is_array($config) ? $config : [];
                $itemtype = is_string($config['itemtype'] ?? null) ? $config['itemtype'] : null;

                $type = self::transformFieldTypeFromMetademands($question['type'], $itemtype);
                // A dropdown question allowing several answers
                if ($type === 'dropdown_meta' && !empty($config['is_multiple_dropdown'])) {
                    $type = 'dropdown_multiple';
                }
                if (empty($type)) {
                    continue;
                }

                $item = $itemtype;
                if (in_array($type, ['dropdown_meta', 'dropdown_multiple'], true) && empty($itemtype)) {
                    $item = 'other';
                } elseif ($type === 'dropdown_object' && empty($itemtype)) {
                    $item = 'User';
                } elseif (in_array($type, ['radio', 'checkbox'], true)) {
                    // As the fields of these types created in the plugin
                    $item = $type;
                }

                $questions_by_uuid[(string) $question['uuid']] = (int) $idq;
                $question_names[(int) $idq] = (string) $question['name'];
                $fields['metafields']['field' . $idq] = [
                    'id'          => $idq,
                    'entities_id' => $entities_id,
                    'rank'        => $block,
                    'order'       => (int) $question['vertical_rank'] + 1,
                    'name'        => $question['name'],
                    'type'        => $type,
                    'item'        => $item,
                    'comment'     => $question['description'],
                ];
                $fields['metafieldparameters']['fieldparameters' . $idq] = [
                    'plugin_metademands_fields_id' => $idq,
                    'color'                        => '#000000',
                    'link_to_user'                 => '0',
                    'is_mandatory'                 => $question['is_mandatory'],
                ];

                // The values of a dropdown, radio or checkbox question (extra_data "options":
                // key => label), with the default ones
                if (in_array($type, Field::$field_customvalues_types, true) && is_array($config['options'] ?? null)) {
                    $defaults = self::getQuestionDefaultKeys($question['default_value'] ?? null);
                    $rank = 0;
                    foreach ($config['options'] as $key => $label) {
                        // Unique in the file, the options of the destinations referring to it
                        $customvalues_id = (int) $idq * 1000 + $rank + 1;
                        $choices[(int) $idq][(string) $key] = $customvalues_id;
                        $choice_labels[(int) $idq][(string) $key] = (string) $label;
                        $fields['metafieldcustoms']['fieldcustoms' . $customvalues_id] = [
                            'id'                           => $customvalues_id,
                            'plugin_metademands_fields_id' => $idq,
                            'name'                         => (string) $label,
                            'rank'                         => $rank,
                            'is_default'                   => in_array((string) $key, $defaults, true) ? 1 : 0,
                        ];
                        $rank++;
                    }
                }
            }

            // The comments of the section, among its questions: their visibility conditions may
            // refer to the questions of the following sections, read once all of them are
            foreach (getAllDataFromTable(Comment::getTable(), ['forms_sections_id' => $ids]) as $comment) {
                $comments[] = ['block' => $block, 'comment' => $comment];
            }
        }

        // The comments of the form: informations, in their block at their place among the
        // questions (the vertical ranks of the questions and the comments of a section follow
        // each other), shown on their visibility conditions when the options express them
        // exactly (options showing a field, "hidden_link"), always shown otherwise
        $options_id = 0;
        $untranslated_comments = [];
        $comment_id = max([0, ...array_map('intval', array_column($fields['metafields'], 'id'))]);
        foreach ($comments as ['block' => $block, 'comment' => $comment]) {
            $comment_id++;
            $fields['metafields']['field' . $comment_id] = [
                'id'          => $comment_id,
                'entities_id' => $entities_id,
                'rank'        => $block,
                'order'       => (int) $comment['vertical_rank'] + 1,
                'name'        => $comment['name'],
                'type'        => 'informations',
                'item'        => 'informations',
                'comment'     => $comment['description'],
            ];
            // The whole row, its name shown as the title of the comment in the GLPI form
            $fields['metafieldparameters']['fieldparameters' . $comment_id] = [
                'plugin_metademands_fields_id' => $comment_id,
                'color'                        => '#000000',
                'row_display'                  => 1,
                'hide_title'                   => 0,
            ];

            $conditions = json_decode((string) ($comment['conditions'] ?? ''), true);
            [$options, $exact, $description] = self::getOptionsFromFormConditions(
                (string) ($comment['visibility_strategy'] ?? ''),
                is_array($conditions) ? $conditions : [],
                $questions_by_uuid,
                $question_names,
                $choices,
                $choice_labels,
            );
            foreach ($options as $option) {
                $options_id++;
                $fields['metafieldoptions']['fieldoptions' . $options_id] = $option + [
                    'id'          => $options_id,
                    'hidden_link' => $comment_id,
                ];
            }
            if (!$exact) {
                // Reported by the summary of the import
                // (not "..._conditions": the import takes the keys holding "condition" for conditions)
                $fields['metafields']['field' . $comment_id]['untranslated_visibility'] = $description;
                $untranslated_comments[] = (string) $comment['name'];
            }
        }
        if ($untranslated_comments !== []) {
            Session::addMessageAfterRedirect(
                sprintf(
                    _n(
                        '%d comment exported without its visibility conditions, its information always shown: see the summary of the import',
                        '%d comments exported without their visibility conditions, their informations always shown: see the summary of the import',
                        count($untranslated_comments),
                        'metademands',
                    ),
                    count($untranslated_comments),
                ),
                false,
                WARNING,
            );
        }

        // The header of the form: the information of the form, above the blocks as the header
        // above the sections (first tab when they are tabs). Its title only names its tab, a
        // header having none. Its id follows the ones of the sections and the questions.
        if ($header !== '') {
            $information_id = max([0, ...array_map('intval', array_column($fields['metafields'], 'id'))]) + 1;
            $fields['metafields'] = [
                'field' . $information_id => [
                    'id'          => $information_id,
                    'entities_id' => $entities_id,
                    'rank'        => Forminformation::BLOCK,
                    'order'       => 1,
                    'name'        => Forminformation::getTypeName(1),
                    'type'        => 'forminformation',
                    'item'        => 'forminformation',
                    'comment'     => $header,
                ],
            ] + $fields['metafields'];
            $fields['metafieldparameters']['fieldparameters' . $information_id] = [
                'plugin_metademands_fields_id' => $information_id,
                'color'                        => '#000000',
                'hide_title'                   => 1,
            ];
        }

        // The destinations of the form: the child tickets, created on the conditions of the form
        // when the options of the metademand express them exactly, always created otherwise
        $fields['tasks'] = [];
        $untranslated = [];
        $skipped = [];
        foreach (getAllDataFromTable(FormDestination::getTable(), ['forms_forms_id' => $forms_forms_id]) as $destination) {
            if ($destination['itemtype'] !== FormDestinationTicket::class) {
                $skipped[] = (string) $destination['name'];
                continue;
            }
            $config = json_decode((string) $destination['config'], true);
            $task = self::getTaskFromFormDestination($destination, is_array($config) ? $config : [], (int) $entities_id, array_values($blocks));

            $conditions = json_decode((string) ($destination['conditions'] ?? ''), true);
            [$options, $exact, $description] = self::getOptionsFromFormConditions(
                (string) ($destination['creation_strategy'] ?? ''),
                is_array($conditions) ? $conditions : [],
                $questions_by_uuid,
                $question_names,
                $choices,
                $choice_labels,
            );
            foreach ($options as $option) {
                $options_id++;
                $fields['metafieldoptions']['fieldoptions' . $options_id] = $option + [
                    'id'                          => $options_id,
                    'plugin_metademands_tasks_id' => (int) $destination['id'],
                ];
            }
            if (!$exact) {
                // Kept on the child object, and reported by the summary of the import
                $task['comment'] = sprintf(__('GLPI conditions not expressible by the options: %s', 'metademands'), $description);
                $task['untranslated_conditions'] = $description;
                // Several child tickets may share a title: the name of the destination tells them apart
                $task['destination_name'] = (string) $destination['name'];
                $untranslated[] = $task['name'];
            }
            $fields['tasks']['task' . $destination['id']] = $task;
        }
        if ($untranslated !== []) {
            Session::addMessageAfterRedirect(
                sprintf(
                    _n(
                        '%d destination exported without its conditions, its child ticket always created: see the summary of the import',
                        '%d destinations exported without their conditions, their child tickets always created: see the summary of the import',
                        count($untranslated),
                        'metademands',
                    ),
                    count($untranslated),
                ),
                false,
                WARNING,
            );
        }
        if ($skipped !== []) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __('Destinations not exported, a metademand creating child tickets only: %s', 'metademands'),
                    implode(', ', $skipped),
                ),
                false,
                WARNING,
            );
        }

        $xml = new SimpleXMLElement(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?><metademand><version>" . PLUGIN_METADEMANDS_VERSION . "</version></metademand>",
        );

        self::toXml($xml, $fields);

        $target = self::buildExportTarget($form->getField('name'), 'xml');
        if ($target === [] || $xml->saveXML($target['path']) === false) {
            Session::addMessageAfterRedirect(__('Unable to create the export file', 'metademands'), false, ERROR);
            return [];
        }

        return $target;
    }

    /**
     * Keys of the options selected by default in a dropdown, radio or checkbox question of a
     * GLPI form: one key, a JSON list or a comma separated list.
     *
     * @return list<string>
     */
    private static function getQuestionDefaultKeys(mixed $default_value): array
    {
        if (!is_string($default_value) || trim($default_value) === '') {
            return [];
        }
        $decoded = json_decode($default_value, true);
        $keys = is_array($decoded) ? $decoded : explode(',', $default_value);

        return array_values(array_filter(
            array_map(static fn(mixed $key): string => is_scalar($key) ? trim((string) $key) : '', $keys),
            static fn(string $key): bool => $key !== '',
        ));
    }

    /**
     * Text of a destination of a GLPI form (title, content), the answers of its questions turned
     * into field tags of the metademand (#id#, the question ids being the field ids of the
     * export), the other tags into their text.
     */
    private static function convertFormTags(string $html): string
    {
        return preg_replace_callback(
            '/<span\b([^>]*\bdata-form-tag="true"[^>]*)>(.*?)<\/span>/is',
            static function (array $matches): string {
                preg_match('/\bdata-form-tag-value="([^"]*)"/', $matches[1], $value);
                preg_match('/\bdata-form-tag-provider="([^"]*)"/', $matches[1], $provider);
                if (str_ends_with($provider[1] ?? '', 'AnswerTagProvider')
                    && preg_match('/^\d+/', $value[1] ?? '', $question_id) === 1) {
                    return '#' . $question_id[0] . '#';
                }
                // A question, section or comment title: its text, without the "#Type: " prefix
                $text = html_entity_decode(strip_tags($matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return preg_replace('/^#[^:]*:\s*/u', '', $text) ?? $text;
            },
            $html,
        ) ?? $html;
    }

    /**
     * Child ticket of the metademand made of a ticket destination of a GLPI form: its title and
     * content, its category, entity and type, its actors (one group and one user of each kind,
     * as a child object holds them). The ids are kept, the export being imported on the GLPI
     * of the form or on a GLPI holding the same objects (see the summary of the import).
     *
     * @param array<string, mixed> $destination row of the destination
     * @param array<string, mixed> $config      its configuration
     * @param list<string>         $blocks      blocks of the metademand
     *
     * @return array<string, mixed>
     */
    private static function getTaskFromFormDestination(array $destination, array $config, int $entities_id, array $blocks): array
    {
        $prefix = 'glpi-form-destination-commonitilfield-';

        $title = self::convertFormTags((string) ($config[$prefix . 'titlefield']['value'] ?? ''));
        $title = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($title === '') {
            $title = (string) $destination['name'];
        }
        $title = mb_substr($title, 0, 255);

        // The content generated from all the answers: the answers of every block, as a table
        $auto_content = !empty($config[$prefix . 'contentfield_auto']);
        $content = $auto_content ? '' : self::convertFormTags((string) ($config[$prefix . 'contentfield']['value'] ?? ''));

        $entity = $config[$prefix . 'entityfield'] ?? [];
        $category = $config[$prefix . 'itilcategoryfield'] ?? [];
        $request_type = $config[$prefix . 'requesttypefield'] ?? [];
        $actors = static function (string $field, string $itemtype) use ($config, $prefix): int {
            $actors_config = $config[$prefix . $field] ?? [];
            if (!in_array('specific_values', (array) ($actors_config['strategies'] ?? []), true)) {
                return 0;
            }
            $ids = (array) ($actors_config['specific_itilactors_ids'][$itemtype] ?? []);

            return (int) (reset($ids) ?: 0);
        };

        return [
            'id'                             => (int) $destination['id'],
            'name'                           => $title,
            'completename'                   => $title,
            'type'                           => Task::TICKET_TYPE,
            'level'                          => 1,
            'plugin_metademands_tasks_id'    => 0,
            'entities_id'                    => $entities_id,
            'is_recursive'                   => 0,
            'useBlock'                       => $auto_content ? 1 : 0,
            'block_use'                      => json_encode($auto_content ? $blocks : []),
            'formatastable'                  => 1,
            'block_parent_ticket_resolution' => 1,
            'comment'                        => '',
            'tickettask'                     => [
                'entities_id'         => ($entity['strategy'] ?? '') === 'specific_value'
                    ? (int) ($entity['specific_entity_id'] ?? $entities_id)
                    : $entities_id,
                'content'             => $content,
                'itilcategories_id'   => ($category['strategy'] ?? '') === 'specific_value'
                    ? (int) ($category['specific_itilcategory_id'] ?? 0)
                    : 0,
                'type'                => ($request_type['strategy'] ?? '') === 'specific_value'
                    ? (int) ($request_type['specific_request_type'] ?? \Ticket::DEMAND_TYPE)
                    : \Ticket::DEMAND_TYPE,
                'groups_id_assign'    => $actors('assigneefield', \Group::class),
                'users_id_assign'     => $actors('assigneefield', \User::class),
                'groups_id_observer'  => $actors('observerfield', \Group::class),
                'users_id_observer'   => $actors('observerfield', \User::class),
                'groups_id_requester' => $actors('requesterfield', \Group::class),
                'users_id_requester'  => $actors('requesterfield', \User::class),
            ],
        ];
    }

    /**
     * Options of the metademand creating the child ticket of a destination of a GLPI form on its
     * conditions, or showing the information of a comment of the form on its visibility ones. A
     * child object (a field) linked to options is created (shown) when one of them is met (OR),
     * so that the conditions are expressed exactly when they are:
     * - none (always created, always shown);
     * - "created if" ("visible if") one condition, or conditions all joined by OR;
     * - "created unless" ("hidden if") one condition, or conditions all joined by AND (not A and
     *   not B…);
     * on a value of a question with values: equals / contains, and their negations ("is not"
     * options).
     * Otherwise no option is returned: the child ticket is always created, the field always shown.
     *
     * @param list<array<string, mixed>>       $conditions
     * @param array<string, int>               $questions_by_uuid
     * @param array<int, string>               $question_names
     * @param array<int, array<string, int>>    $choices       question id => option key => custom value id
     * @param array<int, array<string, string>> $choice_labels question id => option key => label
     *
     * @return array{0: list<array{plugin_metademands_fields_id: int, check_value: int, is_negated: int}>, 1: bool, 2: string}
     *               options, whether they express the conditions exactly, conditions as a text
     */
    private static function getOptionsFromFormConditions(
        string $strategy,
        array $conditions,
        array $questions_by_uuid,
        array $question_names,
        array $choices,
        array $choice_labels,
    ): array {
        $strategies = [
            'created_if'     => __('Created if', 'metademands'),
            'created_unless' => __('Created unless', 'metademands'),
            'visible_if'     => __('Shown if', 'metademands'),
            'hidden_if'      => __('Hidden if', 'metademands'),
        ];
        if ($conditions === [] || !isset($strategies[$strategy])) {
            return [[], true, ''];
        }

        $unless = in_array($strategy, ['created_unless', 'hidden_if'], true);
        $operators = [
            'equals'       => '=',
            'not_equals'   => '≠',
            'contains'     => __('contains'),
            'not_contains' => __('does not contain'),
        ];
        $options = [];
        $exact = true;
        $texts = [];
        foreach (array_values($conditions) as $index => $condition) {
            $operator = (string) ($condition['value_operator'] ?? '');
            $logic = strtolower((string) ($condition['logic_operator'] ?? 'and'));
            $questions_id = $questions_by_uuid[(string) ($condition['item_uuid'] ?? '')] ?? 0;
            $value = $condition['value'] ?? null;
            $key = is_scalar($value) ? (string) $value : '';

            $texts[] = ($index > 0 ? ($logic === 'or' ? __('or', 'metademands') : __('and', 'metademands')) . ' ' : '')
                . ($question_names[$questions_id] ?? '?') . ' '
                . ($operators[$operator] ?? match ($operator) {
                    'empty'     => __('is empty', 'metademands'),
                    'not_empty' => __('is not empty', 'metademands'),
                    default     => $operator,
                })
                . ($key !== '' ? ' ' . ($choice_labels[$questions_id][$key] ?? $key) : '');

            // A chain the options join as they do: OR for "created if", AND for "created unless"
            if ($index > 0 && $logic !== ($unless ? 'and' : 'or')) {
                $exact = false;
            }
            $customvalues_id = $choices[$questions_id][$key] ?? null;
            if (($condition['item_type'] ?? '') !== 'question'
                || !isset($operators[$operator])
                || $customvalues_id === null) {
                $exact = false;
                continue;
            }
            $negated = in_array($operator, ['not_equals', 'not_contains'], true);
            $options[] = [
                'plugin_metademands_fields_id' => $questions_id,
                'check_value'                  => $customvalues_id,
                // "Created unless A and B" is "created if not A or not B"
                'is_negated'                   => (int) ($negated !== $unless),
            ];
        }

        $text = $strategies[$strategy] . ' ' . implode(' ', $texts);

        return $exact ? [$options, true, $text] : [[], false, $text];
    }

    public static function toXml(SimpleXMLElement &$parent, array &$data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $parent->addChild($key);
                self::toXml($child, $value);
            } else {
                // if the key is an integer, it needs text with it to actually work.

                if ($key != 0 && $key == (int) $key) {
                    $key = "key_$key";
                }

                //            if($key == 'name' || $key == 'completename' || $key == 'comments' || $key == 'label2')
                if ($value !== null) {
                    $value = htmlspecialchars((string) $value, ENT_NOQUOTES, 'UTF-8');
                    $parent->addChild($key, $value);
                }
            }
        }
    }


    public static function generateCustomCode()
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

    public static function transformFieldTypeForGLPI($type, $item = null)
    {
        if ($type === 'dropdown' && $item === 'Location') {
            return QuestionTypeItemDropdown::class;
        }
        if ($type === 'dropdown_multiple' && $item === 'User') {
            return QuestionTypeRequester::class;
        }
        if ($type === 'dropdown_meta' && $item === 'urgency') {
            return QuestionTypeUrgency::class;
        }


        $map = [
            'dropdown' => QuestionTypeItemDropdown::class,
            'dropdown_object' => QuestionTypeItem::class,
            'dropdown_meta' => QuestionTypeDropdown::class,
            'dropdown_multiple' => QuestionTypeDropdown::class,
            //        'title' => '',
            //        'informations' => '',
            'text' => QuestionTypeShortText::class,
            'tel' => QuestionTypeShortText::class,
            'email' => QuestionTypeEmail::class,
            'url' => QuestionTypeShortText::class,
            'textarea' => QuestionTypeLongText::class,
            'yesno' => QuestionTypeDropdown::class,
            'checkbox' => QuestionTypeCheckbox::class,
            'radio' => QuestionTypeRadio::class,
            'number' => QuestionTypeNumber::class,
            //        'range' => '',
            //        'freetable' => '',
            //        'basket' => '',
            'date' => QuestionTypeDateTime::class,
            'time' => QuestionTypeDateTime::class,
            'datetime' => QuestionTypeDateTime::class,
            //        'date_interval' => '',
            //        'datetime_interval' => '',
            'upload' => QuestionTypeFile::class,
            //         'link' => '',
            //        'signature' => '',
            //        'parent_field' => ''
            '**meta type**' => QuestionTypeRequestType::class,
        ];
        return $map[$type] ?? "";
    }

    public static function getOptionsByFieldId($fieldId, $prefix, $fieldvalues = [])
    {
        $options = getAllDataFromTable(
            'glpi_plugin_metademands_fieldoptions',
            ['hidden_link' => $fieldId],
        );

        $renamedOptions = [];
        //
        //        $countByQuestionId = [];
        //        foreach ($options as $option) {
        //            $id = $prefix . $option['plugin_metademands_fields_id'];
        //            $countByQuestionId[$id] = ($countByQuestionId[$id] ?? 0) + 1;
        //        }

        foreach ($options as $option) {
            $fieldorigin = new Field();
            $fieldorigin->getFromDB($option['plugin_metademands_fields_id']);

            $uuid = $prefix . $option['plugin_metademands_fields_id'];
            $fieldcustomvalues = new FieldCustomvalue();

            $showCondition = "equals";
            $showValue = "";
            //            $showOrder = 1;

            if ($fieldorigin->fields['type'] === 'yesno') {
                if ((int) $option['check_value'] === 1) {
                    $showValue = array_search('Non', $fieldvalues[$option['plugin_metademands_fields_id']]);
                }
                if ((int) $option['check_value'] === 2) {
                    $showValue = array_search('Oui', $fieldvalues[$option['plugin_metademands_fields_id']]);
                }
            } elseif ($fieldorigin->fields['type'] === 'text') {
                $showCondition = "contains";
            } elseif ((int) $option['check_value'] === -1) {
                $showCondition = "not_empty";
            } elseif (in_array($fieldorigin->fields["type"], Field::$field_customvalues_types)) {
                $fieldcustomvalues->getFromDB($option['check_value']);
                $showValue = array_search(
                    $fieldcustomvalues->fields['name'],
                    $fieldvalues[$option['plugin_metademands_fields_id']],
                );
                $showOrder = 1; //$fieldcustomvalues->fields['rank'] ?? 0
            }
            //            $questionId = $prefix . $option['plugin_metademands_fields_id'];
            //            $logic = $countByQuestionId[$questionId] > 1 ? 2 : 1;
            $renamedOptions[] = [
                'item_uuid' => $uuid,
                'item_type' => "question",
                //                    'forms_questions_id' => $questionId,
                'value_operator' => $showCondition,
                'logic_operator' => "and",
                'value' => [$showValue],
                //                    'order' => $showOrder,

            ];
        }

        return $renamedOptions;
    }

    public static function getSectionConditions($fieldId, $uuid, $fieldvalues = [])
    {
        $options = getAllDataFromTable(
            'glpi_plugin_metademands_fieldoptions',
            ['plugin_metademands_fields_id' => $fieldId],
        );

        $conditions = [];
        //        $countByQuestionId = [];
        //        foreach ($options as $option) {
        //            $id = $prefix . $option['plugin_metademands_fields_id'];
        //            $countByQuestionId[$id] = ($countByQuestionId[$id] ?? 0) + 1;
        //        }

        foreach ($options as $option) {
            if (isset($option['hidden_block'])
                && $option['hidden_block'] > 0) {
                $fieldorigin = new Field();
                $fieldorigin->getFromDB($option['plugin_metademands_fields_id']);

                $fieldcustomvalues = new FieldCustomvalue();

                $showCondition = "equals";
                $showValue = "";
                $showOrder = 1;

                if ($fieldorigin->fields['type'] === 'yesno') {
                    if ((int) $option['check_value'] === 1) {
                        $showValue = array_search('oui', $fieldvalues);
                    }
                    if ((int) $option['check_value'] === 2) {
                        $showValue = array_search('non', $fieldvalues);
                    }
                } elseif ($fieldorigin->fields['type'] === 'text') {
                    $showCondition = "contains";
                } elseif ((int) $option['check_value'] === -1) {
                    $showCondition = "not_empty";
                } elseif (in_array($fieldorigin->fields["type"], Field::$field_customvalues_types)) {
                    $fieldcustomvalues->getFromDB($option['check_value']);
                    $showValue = array_search($fieldcustomvalues->fields['name'], $fieldvalues);
                    $showOrder = 1; //$fieldcustomvalues->fields['rank'] ?? 0
                }
                //                $questionId = $prefix . $option['plugin_metademands_fields_id'];
                //                $logic = $countByQuestionId[$questionId] > 1 ? 2 : 1;

                $conditions[$option['hidden_block']][] = [
                    'item_uuid' => $uuid,
                    'item_type' => "question",
                    //                    'forms_questions_id' => $questionId,
                    'value_operator' => $showCondition,
                    'logic_operator' => "and",
                    'value' => [$showValue],
                    //                    'order' => $showOrder,

                ];
            }
        }
        return $conditions;
    }

    public static function generateFieldParameters($fieldtype, $fieldId, $prefix)
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
                            "uuid" => $prefix . $fieldId,
                        ],
                        "regex" => [
                            "regex" => null,
                            "range_max" => 0,
                            "fieldname" => "regex",
                            "uuid" => $prefix . $fieldId,
                        ],
                    ],
                ];
            case 'checkboxes':
            case 'multiselect':
                return [
                    $fieldtype => [
                        "range" => [
                            "range_min" => 0,
                            "range_max" => 0,
                            "fieldname" => "range",
                            "uuid" => $prefix . $fieldId,
                        ],
                    ],
                ];
            default:
                return [];
        }
    }

    public static function exportAsJSONForGLPIForm($id)
    {
        //TODOJSON Traductions ?
        //TODOJSON child tickets ?

        $metademands = new Metademand();
        // Same reasoning as exportAsXMLForMetademands(): control at the sink so that every caller,
        // present and future, is covered.
        if (!$metademands->getFromDB($id)
            || !Session::haveAccessToEntity(
                $metademands->fields['entities_id'],
                $metademands->fields['is_recursive'],
            )) {
            throw new AccessDeniedHttpException();
        }
        $metademands_id = $metademands->getID();
        $prefix = self::generateCustomCode();
        if (!$metademands_id) {
            die(json_encode(["error" => __s('No results found')]));
        }
        $entity_name = "";

        $entity = new Entity();
        if ($entity->getFromDB($metademands->fields['entities_id'])) {
            $entity_name = $entity->fields['completename'];
        }

        // JSON array initialization for the response
        $json = [
            "version" => 1,
            "forms" => [],
        ];

        // Basic form
        $form = [
            "id" => $metademands->getID(),
            "uuid" => $prefix . $metademands_id,
            "name" => $metademands->fields['name'] ?? "",
            "header" => null,
            "description" => $metademands->fields['comment'],
            "illustration" => "",
            "entity_name" => $entity_name,
            "is_recursive" => (int) ($metademands->fields['is_recursive']),
            "is_active" => (int) ($metademands->fields['is_active']),
            "submit_button_visibility_strategy" => "",
            "submit_button_conditions" => [],
            "sections" => [],
            "comments" => [],
            "questions" => [],
            "policies" => [],
            "destinations" => [],
            "translations" => [],
        ];

        $form["data_requirements"] = [["itemtype" => "Entity", "name" => $entity_name]];

        $metademands_groups_data = getAllDataFromTable(
            'glpi_plugin_metademands_groups',
            ['plugin_metademands_metademands_id' => $metademands_id],
        );

        $groups_required = [];
        $policies = [];
        if (!empty($metademands_groups_data)) {
            $policies = [
                "strategy" => "Glpi\\Form\\AccessControl\\ControlType\\AllowList",
                "is_active" => true,
            ];
            $policies["config"]["user_ids"] = [];
            $policies["config"]["profile_ids"] = [];
            foreach ($metademands_groups_data as $groups) {
                $metagroup = new \Group();
                $metagroup->getFromDB($groups['groups_id']);
                $groups_required[] = ["itemtype" => "Group", "name" => $metagroup->getName()];
                $policies["config"]["group_ids"][] = $metagroup->getName();
            }

            $form["policies"] = array_merge(
                $form["policies"],
                [$policies],
            );

            $form["data_requirements"] = array_merge(
                $form["data_requirements"],
                $groups_required,
            );
        }


        if ($metademands->fields['object_to_create'] === "Ticket") {
            $newTicket = [
                "id" => $metademands->getID(),
                "name" => "Ticket from metademand",
                "itemtype" => "Glpi\Form\Destination\FormDestinationTicket",
                "config" => [],
                "creation_strategy" => "always_created",
                "conditions" => [],
            ];
            $config = [];
            $metademands_ticketfields_data = getAllDataFromTable(
                'glpi_plugin_metademands_ticketfields',
                ['plugin_metademands_metademands_id' => $metademands_id, 'num' => 1],
            );
            if (!empty($metademands_ticketfields_data)) {
                foreach ($metademands_ticketfields_data as $ticketfields_data) {
                    $config["glpi-form-destination-commonitilfield-titlefield"] = [
                        "value" => $ticketfields_data['value'],
                    ];
                }
            }

            $itilcategories_id = json_decode($metademands->fields['itilcategories_id'], true);
            if (is_array($itilcategories_id) && count($itilcategories_id) == 1) {
                $category = new \ITILCategory();
                foreach ($itilcategories_id as $itilcategorie_id) {
                    if ($category->getFromDB($itilcategorie_id)) {
                        $config["glpi-form-destination-commonitilfield-itilcategoryfield"] = [
                            "strategy" => "specific_value",
                            "specific_itilcategory_id" => $category->getRawCompleteName(),
                        ];
                        $form["data_requirements"] = array_merge(
                            $form["data_requirements"],
                            [["itemtype" => "ITILCategory", "name" => $category->getRawCompleteName()]],
                        );
                    }
                }
            }
            $config["glpi-form-destination-commonitilfield-requesttypefield"] = [
                "strategy" => "specific_value",
                "specific_request_type" => $metademands->fields['type'],
            ];


            $metademands_ticketfields_groupdata = getAllDataFromTable(
                'glpi_plugin_metademands_ticketfields',
                ['plugin_metademands_metademands_id' => $metademands_id, 'num' => 8],
            );
            if (!empty($metademands_ticketfields_groupdata)) {
                $group = new \Group();
                foreach ($metademands_ticketfields_groupdata as $ticketfields_groupdata) {
                    if ($group->getFromDB($ticketfields_groupdata['value'])) {
                        $config["glpi-form-destination-commonitilfield-assigneefield"] = [
                            "strategies" => ["specific_values"],
                            "specific_question_ids" => null,
                            "specific_itilactors_ids" => ['Group' => [$group->getName()]],
                        ];
                        $form["data_requirements"] = array_merge(
                            $form["data_requirements"],
                            [["itemtype" => "Group", "name" => $group->getName()]],
                        );
                    }
                }
            }

            $newTicket["config"] = array_merge($newTicket["config"], $config);

            $form["destinations"][] = $newTicket;
        } elseif ($metademands->fields['object_to_create'] === "Problem") {
            $newProblem = [
                "id" => $metademands->getID(),
                "name" => "Problem from metademand",
                "itemtype" => "Glpi\Form\Destination\FormDestinationProblem",
                "config" => [],
                "creation_strategy" => "always_created",
                "conditions" => [],
            ];

            $config = [];
            $metademands_ticketfields_data = getAllDataFromTable(
                'glpi_plugin_metademands_ticketfields',
                ['plugin_metademands_metademands_id' => $metademands_id, 'num' => 1],
            );
            if (!empty($metademands_ticketfields_data)) {
                foreach ($metademands_ticketfields_data as $ticketfields_data) {
                    $config["glpi-form-destination-commonitilfield-titlefield"] = [
                        "value" => $ticketfields_data['value'],
                    ];
                }
            }

            $itilcategories_id = json_decode($metademands->fields['itilcategories_id'], true);
            if (is_array($itilcategories_id) && count($itilcategories_id) == 1) {
                $category = new \ITILCategory();
                foreach ($itilcategories_id as $itilcategorie_id) {
                    if ($category->getFromDB($itilcategorie_id)) {
                        $config["glpi-form-destination-commonitilfield-itilcategoryfield"] = [
                            "strategy" => "specific_value",
                            "specific_itilcategory_id" => $category->getRawCompleteName(),
                        ];
                        $form["data_requirements"] = array_merge(
                            $form["data_requirements"],
                            [["itemtype" => "ITILCategory", "name" => $category->getRawCompleteName()]],
                        );
                    }
                }
            }

            $metademands_ticketfields_groupdata = getAllDataFromTable(
                'glpi_plugin_metademands_ticketfields',
                ['plugin_metademands_metademands_id' => $metademands_id, 'num' => 8],
            );
            if (!empty($metademands_ticketfields_groupdata)) {
                $group = new \Group();
                foreach ($metademands_ticketfields_groupdata as $ticketfields_groupdata) {
                    if ($group->getFromDB($ticketfields_groupdata['value'])) {
                        $config["glpi-form-destination-commonitilfield-assigneefield"] = [
                            "strategies" => ["specific_values"],
                            "specific_question_ids" => null,
                            "specific_itilactors_ids" => ['Group' => [$group->getName()]],
                        ];
                        $form["data_requirements"] = array_merge(
                            $form["data_requirements"],
                            [["itemtype" => "Group", "name" => $group->getName()]],
                        );
                    }
                }
            }

            $newProblem["config"] = array_merge($newProblem["config"], $config);

            $form["destinations"][] = $newProblem;
        } elseif ($metademands->fields['object_to_create'] === "Change") {
            $newChange = [
                "id" => $metademands->getID(),
                "name" => "Change from metademand",
                "itemtype" => "Glpi\Form\Destination\FormDestinationChange",
                "config" => [],
                "creation_strategy" => "always_created",
                "conditions" => [],
            ];

            $config = [];
            $metademands_ticketfields_data = getAllDataFromTable(
                'glpi_plugin_metademands_ticketfields',
                ['plugin_metademands_metademands_id' => $metademands_id, 'num' => 1],
            );
            if (!empty($metademands_ticketfields_data)) {
                foreach ($metademands_ticketfields_data as $ticketfields_data) {
                    $config["glpi-form-destination-commonitilfield-titlefield"] = [
                        "value" => $ticketfields_data['value'],
                    ];
                }
            }

            $itilcategories_id = json_decode($metademands->fields['itilcategories_id'], true);
            if (is_array($itilcategories_id) && count($itilcategories_id) == 1) {
                $category = new \ITILCategory();
                foreach ($itilcategories_id as $itilcategorie_id) {
                    if ($category->getFromDB($itilcategorie_id)) {
                        $config["glpi-form-destination-commonitilfield-itilcategoryfield"] = [
                            "strategy" => "specific_value",
                            "specific_itilcategory_id" => $category->getRawCompleteName(),
                        ];
                        $form["data_requirements"] = array_merge(
                            $form["data_requirements"],
                            [["itemtype" => "ITILCategory", "name" => $category->getRawCompleteName()]],
                        );
                    }
                }
            }

            $metademands_ticketfields_groupdata = getAllDataFromTable(
                'glpi_plugin_metademands_ticketfields',
                ['plugin_metademands_metademands_id' => $metademands_id, 'num' => 8],
            );
            if (!empty($metademands_ticketfields_groupdata)) {
                $group = new \Group();
                foreach ($metademands_ticketfields_groupdata as $ticketfields_groupdata) {
                    if ($group->getFromDB($ticketfields_groupdata['value'])) {
                        $config["glpi-form-destination-commonitilfield-assigneefield"] = [
                            "strategies" => ["specific_values"],
                            "specific_question_ids" => null,
                            "specific_itilactors_ids" => ['Group' => [$group->getName()]],
                        ];
                        $form["data_requirements"] = array_merge(
                            $form["data_requirements"],
                            [["itemtype" => "Group", "name" => $group->getName()]],
                        );
                    }
                }
            }

            $newChange["config"] = array_merge($newChange["config"], $config);

            $form["destinations"][] = $newChange;
        }

        $criteria['WHERE'] = ['plugin_metademands_metademands_id' => $metademands_id];
        $criteria['ORDER'] = ['rank', 'order'];

        $fields = getAllDataFromTable(
            'glpi_plugin_metademands_fields',
            $criteria,
        );


        $sections = [];
        $conditions = [];
        $questionsAdded = [];

        foreach ($fields as $field) {
            $fieldId = $field['id'];

            // The information of the form is the header of the GLPI form, shown above its
            // sections as it is above the blocks (exportAsXMLFromGLPI() reads it back)
            if ($field['type'] === 'forminformation') {
                if ((int) ($field['is_active'] ?? 1) === 1) {
                    $header = ($field['comment'] ?? '') . ($field['label2'] ?? '');
                    $form['header'] = $header !== '' ? $header : null;
                }
                continue;
            }

            $params = [];
            $fieldmeta = new Field();
            if ($fieldmeta->getFromDB($fieldId)) {
                $params = Field::getAllParamsFromField($fieldmeta);
            }

            // If this question has already been added, skip it.
            if (isset($questionsAdded[$fieldId])) {
                continue;
            }
            $questionsAdded[$fieldId] = true;
            $rank = $params['rank'];

            if (!isset($sections[$rank])) {
                $sections[$rank] = [
                    "id" => $rank,
                    "uuid" => $prefix . $rank,
                    "name" => "Section " . $rank,
                    "description" => null,
                    // The blocks of a metademand are numbered from 1, the sections of a GLPI
                    // form ranked from 0 (as exportAsXMLFromGLPI() reads them back)
                    "rank" => max(0, (int) $rank - 1),
                    "visibility_strategy" => "",
                    "conditions" => [],
                ];
                $questions[$rank] = [];

                if ($titles_block = $fieldmeta->find([
                    'plugin_metademands_metademands_id' => $metademands_id,
                    'rank' => $rank,
                    'type' => 'title-block',
                ])) {
                    foreach ($titles_block as $title_block) {
                        $sections[$rank]["name"] = $title_block['name'];
                    }
                }
            }

            $fieldtype = self::transformFieldTypeForGLPI($params['type'], $params['item']);
            $values = [];
            //            if (empty($fieldtype)) {
            //                continue;
            //            }
            // Créer la question
            if (!empty($fieldtype)) {
                $question = [
                    "id" => $params['id'],
                    "uuid" => $prefix . $fieldId,
                    "name" => $params['name'],
                    "type" => $fieldtype,
                    "is_mandatory" => $params['is_mandatory'],
                    "vertical_rank" => $params['order'],
                    "horizontal_rank" => null,
                    "description" => null,
                    "extra_data" => null,
                    "section_id" => $rank,
                    "visibility_strategy" => 1,
                    "validation_strategy" => "",
                    "conditions" => [],
                    "validation_conditions" => [],
                    //                    "width" => 4,
                    //                    "show_empty" => 1,
                    //                    "_parameters" => self::generateFieldParameters($fieldtype, $fieldId, $prefix),
                ];

                if ($params['item'] !== null && getItemForItemtype($params['item'])) {
                    if ($params['item'] instanceof CommonDropdown
                        || $params['item'] instanceof CommonTreeDropdown
                        || $fieldtype == QuestionTypeItemDropdown::class) {
                        $question['extra_data'] = [
                            "itemtype" => $params['item'],
                            "categories_filter" => [
                                "request",
                                "incident",
                                "change",
                                "problem",
                            ],
                            "root_items_id" => 0,
                            "location_depth" => 0,
                            "subtree_depth" => 0,
                            "selectable_tree_root" => false,
                        ];
                    } else {
                        $question['extra_data'] = [
                            "itemtype"             => $params['item'],
                            "root_items_id"        => 0,
                            "subtree_depth"        => 0,
                            "selectable_tree_root" => false,
                        ];
                    }
                    $question['default_value'] = ["items_id" => 0];
                } else {
                    $question['default_value'] = "";
                }
                if ($params['type'] === 'dropdown_multiple') {
                    $question['extra_data'] = ["is_multiple_dropdown" => 1];
                }
                if ($params['type'] === 'dropdown_multiple' && $params['item'] === 'User') {
                    $question['extra_data'] = ["is_multiple_actors" => 1];
                }
                if ($params['type'] === 'date') {
                    $question['extra_data'] = [
                        "is_default_value_current_time" => 0,
                        "is_date_enabled" => 1,
                        "is_time_enabled" => 0,
                    ];
                }
                if ($params['type'] === 'datetime') {
                    $question['extra_data'] = [
                        "is_default_value_current_time" => 0,
                        "is_date_enabled" => 1,
                        "is_time_enabled" => 1,
                    ];
                }
                if ($params['type'] === 'time') {
                    $question['extra_data'] = [
                        "is_default_value_current_time" => 0,
                        "is_date_enabled" => 0,
                        "is_time_enabled" => 1,
                    ];
                }
                if ($params['type'] === 'link') {
                    if (isset($params['custom_values'][1])) {
                        $decodedUrl = urldecode($params['custom_values'][1]);
                        $safeUrl    = htmlescape($decodedUrl);
                        // Only web schemes become a link: javascript:/data: URLs are kept as text.
                        $scheme = strtolower((string) parse_url($decodedUrl, PHP_URL_SCHEME));
                        if (in_array($scheme, ['http', 'https'], true)) {
                            $question['description'] = "<a href=\"$safeUrl\" target=\"_blank\" rel=\"noopener noreferrer\">$safeUrl</a>";
                        } else {
                            $question['description'] = $safeUrl;
                        }
                    } else {
                        $question['description'] = __('No link', 'metademands');
                    }
                } else {
                    $question['description'] = $params['comment'];
                }
                if ($params['type'] === 'yesno') {
                    $rand1 = mt_rand();
                    $rand2 = mt_rand();
                    $values = [
                        $rand1 => "Oui",
                        $rand2 => "Non",
                    ];
                    $question['extra_data'] = ["options" => $values, "is_multiple_dropdown" => false];
                }

                if (is_array($params['custom_values'])
                    && count($params['custom_values']) > 0
                    && $params['type'] != "dropdown"
                    && $params['type'] != "number"
                    && $params['type'] != "dropdown_object"
                    && $params['item'] != 'User') {
                    $custom_values = [];
                    foreach ($params['custom_values'] as $k => $customs) {
                        if (isset($customs['name'])) {
                            $rand = mt_rand();
                            $values[$rand] = $customs['name'];
                        }
                    }
                    $question['extra_data'] = ["options" => $values];
                }

                // Check if options are associated with this field
                $valuesbyfields[$fieldId] = $values;
                $options = self::getOptionsByFieldId($fieldId, $prefix, $valuesbyfields);
                if ($options) {
                    $question['visibility_strategy'] = "visible_if";
                    $question['conditions'] = $options;
                }

                $questions[$rank][] = $question;

                //Sections conditions
                $sectionConditions = self::getSectionConditions($fieldId, $prefix . $fieldId, $values);
                if (count($sectionConditions) > 0) {
                    foreach ($sectionConditions as $block => $cond) {
                        $conditions[$block]['conditions'] = $cond;
                        if (count($conditions[$block]['conditions']) > 0) {
                            $conditions[$block]['visibility_strategy'] = "visible_if";
                        }
                    }
                }
            }
        }
        foreach ($conditions as $r => $cond) {
            if (isset($sections[$r])) {
                $sections[$r] = array_merge(
                    $sections[$r],
                    $cond,
                );
            }
        }

        $list_questions = [];
        foreach ($questions as $r => $ql) {
            foreach ($ql as $q) {
                $list_questions[] = $q;
            }
        }

        ksort($sections);
        $form['sections'] = array_values($sections);
        $form['questions'] = array_values($list_questions);
        $json['forms'][] = $form;
        $jsonOutput = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // die() would abort the request in the middle of the page: report the failure the
        // way the other export paths do and let the caller handle it.
        $target = self::buildExportTarget($metademands->getField('name'), 'json');
        if ($target === [] || file_put_contents($target['path'], $jsonOutput) === false) {
            Session::addMessageAfterRedirect(__('Unable to create the export file', 'metademands'), false, ERROR);
            return [];
        }

        return $target;
    }

    /**
     * Build the working path an export is written to, and the name proposed for download.
     *
     * The label alone used to name the file, inside GLPI_PLUGIN_DOC_DIR/metademands which is
     * shared by every entity: two meta-demands carrying the same label, exported at the same
     * moment from two entities, wrote over each other and the streamed download served the
     * definition of the other one. The random suffix makes the working copy unique per request
     * -- what ajax/export_metademand.php already does for the ZIP archive -- while the name
     * offered to the browser, also used as the entry name inside that archive, stays readable.
     *
     * @param string $label     name of the exported meta-demand or form
     * @param string $extension extension of the generated file, without the dot
     *
     * @return array{path: string, filename: string}|array{} empty when the directory is unusable
     */
    private static function buildExportTarget(string $label, string $extension): array
    {
        $safe_name = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $label);
        $safe_name = mb_ereg_replace("([\.]{2,})", '', $safe_name);
        if (!is_string($safe_name) || trim($safe_name) === '') {
            // A label made only of stripped characters would leave the suffix alone as a name.
            $safe_name = 'metademand';
        }

        $directory = GLPI_PLUGIN_DOC_DIR . '/metademands';
        if (!is_dir($directory) && !@mkdir($directory, 0o775, true) && !is_dir($directory)) {
            return [];
        }

        return [
            'path' => $directory . '/' . $safe_name . '_' . bin2hex(random_bytes(8)) . '.' . $extension,
            'filename' => $safe_name . '.' . $extension,
        ];
    }

    /**
     * Stream an exported definition, then drop it from the shared document directory.
     *
     * The three export methods above write their file below GLPI_PLUGIN_DOC_DIR, which is
     * shared by every entity, and used to leave it there: the exported definitions piled
     * up and stayed readable to anyone who could reach the directory. Toolbox::getFileAsResponse()
     * answers with a streamed response, so the file is still needed when this method
     * returns -- the unlink is therefore deferred to shutdown, once the response has been
     * sent, rather than placed in a finally block that would fire before the first byte.
     *
     * @param string $path            absolute path of the generated file
     * @param string $filename        name proposed to the browser
     * @param string $mime            mime type of the generated file
     * @param bool   $expires_headers whether caching headers should be emitted
     *
     * @return Response
     */
    public static function sendFileAndPurge(
        string $path,
        string $filename,
        string $mime,
        bool $expires_headers = false
    ): Response {
        $response = Toolbox::getFileAsResponse($path, $filename, $mime, $expires_headers);

        register_shutdown_function(static function () use ($path): void {
            if (file_exists($path)) {
                @unlink($path);
            }
        });

        return $response;
    }

    public static function showImportForm()
    {
        TemplateRenderer::getInstance()->display('@metademands/export_import_form.html.twig', [
            'target' => self::getFormURL(),
        ]);
    }


    /**
     * First step of the import: keep the uploaded file until its import is confirmed on its
     * summary (showImportSummary()), in place of the file of a previous upload.
     */
    public static function prepareImport(): bool
    {
        $file = self::moveImportedXmlFile();
        if ($file === false) {
            return false;
        }

        self::cancelPendingImport();
        self::purgeStaleImportFiles();
        $_SESSION[self::PENDING_IMPORT_SESSION_KEY] = [
            // Proves on the confirmation that the summary of this very file was displayed
            'token'    => bin2hex(random_bytes(16)),
            'file'     => basename($file),
            'filename' => (string) ($_FILES['meta_file']['name'] ?? ''),
        ];

        return true;
    }

    /**
     * Summary of the file waiting for its import: what the import creates, the errors
     * preventing it and what it cannot carry over to this GLPI, with the buttons confirming or
     * cancelling the import.
     *
     * @return bool false when no file is waiting for its import
     */
    public static function showImportSummary(): bool
    {
        $pending = self::getPendingImport();
        if ($pending === null) {
            return false;
        }

        $entities_id = (int) $_SESSION['glpiactive_entity'];
        $error       = null;
        $datas       = self::readImportFile($pending['path'], $error);
        if ($datas === null) {
            $summary             = self::newImportSummary($entities_id);
            $summary['errors'][] = (string) $error;
        } else {
            $summary = self::analyzeImportData($datas, $entities_id);
        }

        TemplateRenderer::getInstance()->display('@metademands/export_import_summary.html.twig', [
            'target'   => self::getFormURL(),
            'token'    => $pending['token'],
            'filename' => $pending['filename'],
            'summary'  => $summary,
        ]);

        return true;
    }

    /**
     * Second step of the import: import the file waiting for it, once its summary was seen
     * (its token) and if it has no error. The file is dropped whatever the outcome.
     *
     * @return int|false id of the new meta-demand
     */
    public static function importPendingFile(string $token)
    {
        $pending = self::getPendingImport();
        if ($pending === null || $token === '' || !hash_equals($pending['token'], $token)) {
            Session::addMessageAfterRedirect(
                __('No file is waiting for its import, please select it again', 'metademands'),
                false,
                ERROR,
            );
            return false;
        }

        try {
            $datas = self::readImportFile($pending['path']);
            // The button is disabled on errors, which is checked again here
            if ($datas === null
                || self::analyzeImportData($datas, (int) $_SESSION['glpiactive_entity'])['errors'] !== []) {
                Session::addMessageAfterRedirect(
                    __('The file cannot be imported, see the errors of its summary', 'metademands'),
                    false,
                    ERROR,
                );
                return false;
            }

            return self::importXmlFile($pending['path']);
        } finally {
            // The uploaded copy lives in the plugin document directory: drop it whatever the
            // import did, including when it threw, so no readable XML is left behind.
            self::cancelPendingImport();
        }
    }

    /**
     * Drop the file waiting for its import, if any.
     */
    public static function cancelPendingImport(): void
    {
        $pending = self::getPendingImport();
        if ($pending !== null) {
            @unlink($pending['path']);
        }
        unset($_SESSION[self::PENDING_IMPORT_SESSION_KEY]);
    }

    /**
     * File waiting for its import, kept by prepareImport().
     *
     * @return array{token: string, path: string, filename: string}|null
     */
    private static function getPendingImport(): ?array
    {
        $pending = $_SESSION[self::PENDING_IMPORT_SESSION_KEY] ?? null;
        if (!is_array($pending)
            || !is_string($pending['token'] ?? null)
            || !is_string($pending['file'] ?? null)
            || preg_match(self::IMPORT_FILE_PATTERN, $pending['file']) !== 1) {
            return null;
        }

        $path = GLPI_PLUGIN_DOC_DIR . '/metademands/' . $pending['file'];
        if (!is_file($path)) {
            return null;
        }

        $filename = $pending['filename'] ?? '';

        return [
            'token'    => $pending['token'],
            'path'     => $path,
            'filename' => is_string($filename) && $filename !== '' ? $filename : $pending['file'],
        ];
    }

    /**
     * Drop the files of the imports left without confirmation nor cancellation (session
     * expired, page closed), a day after their upload.
     */
    private static function purgeStaleImportFiles(): void
    {
        foreach (glob(GLPI_PLUGIN_DOC_DIR . '/metademands/import_*.xml') ?: [] as $path) {
            if (preg_match(self::IMPORT_FILE_PATTERN, basename($path)) === 1
                && (int) @filemtime($path) < time() - DAY_TIMESTAMP) {
                @unlink($path);
            }
        }
    }

    /**
     * Content of a meta-demand file, as the import reads it.
     *
     * @param string|null $error why the file cannot be read, when it cannot
     *
     * @return array<mixed>|null null when the file is not a meta-demand export
     */
    public static function readImportFile(string $file, ?string &$error = null): ?array
    {
        // LIBXML_NONET keeps the parser off the network whatever the document declares. A
        // malformed document is reported by the summary rather than by PHP warnings.
        $use_errors = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_file($file, SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);
        }
        if ($xml === false) {
            $error = __('The file is not an XML file', 'metademands');
            return null;
        }

        $datas = self::normalizeXmlData(json_decode((string) json_encode($xml), true));
        if ($xml->getName() !== 'metademand' || !is_array($datas)) {
            $error = __('The file is not a meta-demand export', 'metademands');
            return null;
        }

        return $datas;
    }

    /**
     * Summary of the import of a meta-demand file, without writing anything: what the import
     * creates, the errors preventing it, and what it cannot carry over to this GLPI, read as
     * importXmlFile() reads the file.
     *
     * @param array<mixed> $datas       content of the file (readImportFile())
     * @param int          $entities_id entity the meta-demand is imported into
     *
     * @return array{
     *     name: string,
     *     object_to_create: string,
     *     type: string,
     *     version: string,
     *     entity: string,
     *     counts: list<array{label: string, count: int}>,
     *     errors: list<string>,
     *     warnings: list<string>,
     *     infos: list<string>,
     * }
     */
    public static function analyzeImportData(array $datas, int $entities_id): array
    {
        $summary = self::newImportSummary($entities_id);

        $name             = is_string($datas['name'] ?? null) ? trim($datas['name']) : '';
        $object_to_create = is_string($datas['object_to_create'] ?? null) ? $datas['object_to_create'] : '';
        $version          = $datas['version'] ?? 0;
        // Read as importXmlFile() reads it: the options, parameters and custom values changed
        // of format in 3.3.11, the columns of the free tables are imported from 3.3.20
        $new_format         = $version >= "3.3.11";
        $with_table_columns = $version >= "3.3.20";
        $known_object       = in_array($object_to_create, Metademand::$types, true);

        $summary['name']    = $name;
        $summary['version'] = is_scalar($version) ? (string) $version : '';

        $fields       = self::getImportRows($datas, 'metafields');
        $options      = self::getImportRows($datas, 'metafieldoptions');
        $parameters   = self::getImportRows($datas, 'metafieldparameters');
        $customvalues = self::getImportRows($datas, 'metafieldcustoms');
        $columns      = self::getImportRows($datas, 'metafieldfreetablefields');
        $tasks        = self::getImportRows($datas, 'tasks');
        $metatasks    = self::getImportRows($datas, 'metatasks');
        $steps        = self::getImportRows($datas, 'step');
        $ticketfields = self::getImportRows($datas, 'ticketfields');
        $translations = self::getImportRows($datas, 'translations');

        // The meta-demand itself cannot be created
        if (!$known_object) {
            $summary['errors'][] = $object_to_create === ''
                ? __('The file does not give the object the meta-demand creates', 'metademands')
                : sprintf(__('The meta-demand creates an unknown object: %s', 'metademands'), $object_to_create);
            $summary['object_to_create'] = $object_to_create;
        } else {
            $summary['object_to_create'] = $object_to_create::getTypeName(1);
            if ($object_to_create === 'Ticket') {
                $summary['type'] = (string) \Ticket::getTicketTypeName((int) ($datas['type'] ?? 0));
            }
        }

        if ($name === '') {
            $summary['warnings'][] = __('The meta-demand of the file has no name', 'metademands');
        } elseif (countElementsInTable(
            Metademand::getTable(),
            [
                'name'        => $name,
                'is_deleted'  => 0,
                'is_template' => 0,
            ] + getEntitiesRestrictCriteria(Metademand::getTable(), '', $entities_id, true),
        ) > 0) {
            $summary['warnings'][] = sprintf(
                __('A meta-demand named "%s" already exists: the import creates another one', 'metademands'),
                $name,
            );
        }

        if (is_string($version) && version_compare($version, PLUGIN_METADEMANDS_VERSION, '>')) {
            $summary['warnings'][] = sprintf(
                __('The file comes from a more recent version of the plugin (%1$s) than this one (%2$s): the settings unknown here are not imported', 'metademands'),
                $version,
                PLUGIN_METADEMANDS_VERSION,
            );
        }

        if ($fields === []) {
            $summary['warnings'][] = __('The meta-demand of the file has no field', 'metademands');
        }

        /** @var array<int, true> $fields_ids ids of the fields of the file */
        $fields_ids = [];
        /** @var array<int, true> $tasks_ids ids of the child objects of the file */
        $tasks_ids = [];
        $blocks    = [];
        foreach ($fields as $field) {
            $fields_ids[(int) ($field['id'] ?? 0)] = true;
            $blocks[(int) ($field['rank'] ?? 0)]   = true;
        }
        foreach ($tasks as $task) {
            $tasks_ids[(int) ($task['id'] ?? 0)] = true;
        }
        unset($fields_ids[0], $tasks_ids[0]);

        $conditions         = 0;
        $field_translations = 0;
        $unknown_types      = [];
        $unknown_items      = [];
        // References to fields and child objects missing from the file: the import drops them
        $lost_field_links = 0;
        $lost_task_links  = 0;
        /** @var array<string, int> $detached elements of fields missing from the file, not imported */
        $detached = [];
        /** @var array<string, array<int, int>> $missing_items GLPI objects referenced by their id, missing here */
        $missing_items = [];
        // GLPI objects referenced by their id, found here (maybe other objects than on the source GLPI)
        $found_items = 0;

        $pseudo_items = array_merge(self::FIELD_PSEUDO_ITEMS, Field::$field_specificobjects);
        foreach ($fields as $field) {
            foreach (array_keys($field) as $key) {
                // As importXmlFile() finds them
                if (str_contains((string) $key, 'condition')) {
                    $conditions++;
                }
            }
            $field_translations += count(self::getImportRows($field, 'fieldtranslations'));

            $type = is_string($field['type'] ?? null) ? $field['type'] : '';
            $item = is_string($field['item'] ?? null) ? $field['item'] : '';
            if (!in_array($type, Field::$field_types, true)
                && Field::getFieldTypesName($type) === \Dropdown::EMPTY_VALUE) {
                // A type added by a plugin missing here
                $unknown_types[$type] = $type;
            } elseif (in_array($type, ['dropdown', 'dropdown_object', 'dropdown_meta', 'dropdown_multiple'], true)
                && $item !== ''
                && !in_array($item, $pseudo_items, true)
                // Only a class name is handed to the autoloader
                && (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $item) !== 1
                    || !is_a($item, CommonDBTM::class, true))) {
                $unknown_items[$item] = $item;
            }

            // "Display field after"
            if (!self::isKeptReference($field['plugin_metademands_fields_id'] ?? 0, $fields_ids)) {
                $lost_field_links++;
            }
        }

        foreach ($options as $option) {
            if (!isset($fields_ids[(int) ($option['plugin_metademands_fields_id'] ?? 0)])) {
                $label            = FieldOption::getTypeName(2);
                $detached[$label] = ($detached[$label] ?? 0) + 1;
                continue;
            }
            foreach (['fields_link', 'hidden_link', 'checkbox_id'] as $key) {
                if (!self::isKeptReference($option[$key] ?? 0, $fields_ids)) {
                    $lost_field_links++;
                }
            }
            $validation_fields_ids = isset($option['validation_fields_ids'])
                ? FieldOption::getValidationFieldsIds($option)
                : array_filter([(int) ($option['validation_fields_id'] ?? 0)]);
            foreach ($validation_fields_ids as $validation_fields_id) {
                if (!isset($fields_ids[(int) $validation_fields_id])) {
                    $lost_field_links++;
                }
            }
            if (!self::isKeptReference($option['plugin_metademands_tasks_id'] ?? 0, $tasks_ids)) {
                $lost_task_links++;
            }
            self::collectReferencedItems($option, ['users_id_validate', 'itilvalidationtemplates_id'], $missing_items, $found_items);
        }

        $groups_not_found = [];
        if ($new_format) {
            foreach ($parameters as $parameter) {
                if (!isset($fields_ids[(int) ($parameter['plugin_metademands_fields_id'] ?? 0)])) {
                    $label            = FieldParameter::getTypeName(2);
                    $detached[$label] = ($detached[$label] ?? 0) + 1;
                    continue;
                }
                // "Link this field to a user field"
                if (!self::isKeptReference($parameter['link_to_user'] ?? 0, $fields_ids)) {
                    $lost_field_links++;
                }
                $custom = FieldParameter::_unserialize($parameter['custom'] ?? FieldParameter::_serialize([]));
                if (is_array($custom) && FieldParameter::getMemberGroupsIds($custom) !== []) {
                    array_push($groups_not_found, ...self::findMemberGroups($parameter, $entities_id)[1]);
                }
                self::collectReferencedItems($parameter, ['authldaps_id'], $missing_items, $found_items);
            }
            foreach ($customvalues as $customvalue) {
                if (!isset($fields_ids[(int) ($customvalue['plugin_metademands_fields_id'] ?? 0)])) {
                    $label            = FieldCustomvalue::getTypeName(2);
                    $detached[$label] = ($detached[$label] ?? 0) + 1;
                }
            }
        }
        if ($with_table_columns) {
            foreach ($columns as $column) {
                if (!isset($fields_ids[(int) ($column['plugin_metademands_fields_id'] ?? 0)])) {
                    $label            = Freetablefield::getTypeName(2);
                    $detached[$label] = ($detached[$label] ?? 0) + 1;
                }
            }
        }

        // Texts whose tags point at fields: the import points them at the new fields
        $texts       = [];
        $task_counts = [];
        // Child tickets of the destinations of a GLPI form whose conditions the options could
        // not express (getOptionsFromFormConditions()): always created
        $untranslated_tasks = [];
        foreach ($tasks as $task) {
            $label               = sprintf('%1$s (%2$s)', __('Child creation', 'metademands'), Task::getTaskTypeName((int) ($task['type'] ?? 0)));
            $task_counts[$label] = ($task_counts[$label] ?? 0) + 1;
            if (is_string($task['untranslated_conditions'] ?? null) && $task['untranslated_conditions'] !== '') {
                $untranslated_tasks[] = sprintf(
                    __('Child object "%1$s" always created, its GLPI conditions not being expressible by the options: %2$s', 'metademands'),
                    is_string($task['destination_name'] ?? null) && $task['destination_name'] !== ''
                        ? $task['destination_name']
                        : (is_string($task['name'] ?? null) ? $task['name'] : ''),
                    $task['untranslated_conditions'],
                );
            }
            if (!self::isKeptReference($task['plugin_metademands_tasks_id'] ?? 0, $tasks_ids)) {
                $lost_task_links++;
            }
            $texts[] = $task['name'] ?? null;
            $texts[] = $task['completename'] ?? null;

            $tickettask = is_array($task['tickettask'] ?? null) ? $task['tickettask'] : [];
            $texts[]    = $tickettask['content'] ?? null;
            self::collectReferencedItems($tickettask, [
                'itilcategories_id',
                'requesttypes_id',
                'groups_id_assign',
                'users_id_assign',
                'groups_id_requester',
                'users_id_requester',
                'groups_id_observer',
                'users_id_observer',
            ], $missing_items, $found_items);

            $mailtask = is_array($task['mailtask'] ?? null) ? $task['mailtask'] : [];
            $texts[]  = $mailtask['content'] ?? null;
            self::collectReferencedItems($mailtask, [
                'users_id_recipient',
                'groups_id_recipient',
                'itilcategories_id',
            ], $missing_items, $found_items);
        }
        foreach ($metatasks as $metatask) {
            if (!isset($tasks_ids[(int) ($metatask['plugin_metademands_tasks_id'] ?? 0)])) {
                $lost_task_links++;
            }
        }
        foreach ($steps as $step) {
            self::collectReferencedItems($step, ['groups_id'], $missing_items, $found_items);
        }
        self::collectReferencedItems($datas, ['forms_categories_id'], $missing_items, $found_items);

        $ticketfields_not_found = [];
        foreach ($ticketfields as $ticketfield) {
            if (!isset($ticketfield['num'])) {
                continue;
            }
            $texts[] = $ticketfield['value'] ?? null;
            $item    = $known_object
                ? self::findTicketFieldItem($object_to_create, $ticketfield, $entities_id)
                : null;
            if ($item !== null && $item['id'] === 0) {
                $ticketfields_not_found[] = $item['label'];
            }
        }

        $unknown_tags = [];
        foreach ($texts as $text) {
            if (is_string($text) && preg_match_all(self::FIELD_TAG_PATTERN, $text, $matches) > 0) {
                foreach ($matches[1] as $tag_id) {
                    if (!isset($fields_ids[(int) $tag_id])) {
                        $unknown_tags[(int) $tag_id] = '#' . $tag_id . '#';
                    }
                }
            }
        }

        if ($unknown_types !== []) {
            $summary['warnings'][] = sprintf(
                __('Fields of a type unknown here, a plugin may be missing: %s', 'metademands'),
                implode(', ', $unknown_types),
            );
        }
        if ($unknown_items !== []) {
            $summary['warnings'][] = sprintf(
                __('Fields on objects unknown here, a plugin may be missing: %s', 'metademands'),
                implode(', ', $unknown_items),
            );
        }
        foreach ($detached as $label => $count) {
            $summary['warnings'][] = sprintf(
                __('%1$s attached to no field of the file, not imported: %2$d', 'metademands'),
                $label,
                $count,
            );
        }
        if ($lost_field_links > 0) {
            $summary['warnings'][] = sprintf(
                _n(
                    '%d link to a field missing from the file is dropped',
                    '%d links to fields missing from the file are dropped',
                    $lost_field_links,
                    'metademands',
                ),
                $lost_field_links,
            );
        }
        if ($lost_task_links > 0) {
            $summary['warnings'][] = sprintf(
                _n(
                    '%d link to a child object missing from the file is dropped',
                    '%d links to child objects missing from the file are dropped',
                    $lost_task_links,
                    'metademands',
                ),
                $lost_task_links,
            );
        }
        array_push($summary['warnings'], ...$untranslated_tasks);
        // Informations of the comments of a GLPI form whose visibility conditions the options
        // could not express (exportAsXMLFromGLPI()): always shown
        foreach ($fields as $field) {
            if (is_string($field['untranslated_visibility'] ?? null) && $field['untranslated_visibility'] !== '') {
                $summary['warnings'][] = sprintf(
                    __('Information "%1$s" always shown, its GLPI conditions not being expressible by the options: %2$s', 'metademands'),
                    is_string($field['name'] ?? null) ? $field['name'] : '',
                    $field['untranslated_visibility'],
                );
            }
        }
        if ($unknown_tags !== []) {
            ksort($unknown_tags);
            $summary['warnings'][] = sprintf(
                __('Tags pointing at fields missing from the file: %s', 'metademands'),
                implode(', ', $unknown_tags),
            );
        }
        foreach ($missing_items as $itemtype => $ids) {
            $summary['warnings'][] = sprintf(
                __('%1$s not found on this GLPI, to set again after the import: %2$s', 'metademands'),
                $itemtype::getTypeName(count($ids)),
                implode(', ', array_map(static fn(int $id): string => '#' . $id, $ids)),
            );
        }
        if ($ticketfields_not_found !== []) {
            $summary['warnings'][] = sprintf(
                __('Fields of the ticket not imported, no item of that name here: %s', 'metademands'),
                implode(', ', $ticketfields_not_found),
            );
        }
        if ($groups_not_found !== []) {
            $summary['warnings'][] = sprintf(
                __('Groups left out of "Members of these groups", no group of that name here: %s', 'metademands'),
                implode(', ', array_unique($groups_not_found)),
            );
        }

        if ($found_items > 0) {
            $summary['infos'][] = __('The users, groups and categories of the child objects, the validators and the step groups are kept by their id: check them after the import if the file comes from another GLPI', 'metademands');
        }
        if (self::hasBlocksFromZero($datas)) {
            $summary['infos'][] = __('The blocks of the file begin at 0, as the sections of a GLPI form: they are numbered from 1', 'metademands');
        }
        $categories = is_string($datas['itilcategories_id'] ?? null) ? json_decode($datas['itilcategories_id'], true) : null;
        if (is_array($categories) && $categories !== []) {
            $summary['infos'][] = __('The categories of the meta-demand are not imported: choose them after the import', 'metademands');
        }

        // Contract types of the Resources plugin leading to the meta-demand
        $resource_links = self::getImportedResourceLinks($datas);
        if ($resource_links !== [] && !getItemForItemtype(ContractType::class) instanceof CommonDBTM) {
            $summary['warnings'][] = __('The Resources plugin is not active here: the links to its contract types are not imported', 'metademands');
        } elseif ($resource_links !== []) {
            $contracttypes_not_found = [];
            $contracttypes_by_id     = [];
            $contracttypes_linked    = [];
            foreach ($resource_links as $resource_link) {
                $contracttype = self::findContractType($resource_link, $entities_id);
                if ($contracttype['id'] === 0) {
                    $contracttypes_not_found[] = $contracttype['label'];
                    continue;
                }
                if ($contracttype['by_id']) {
                    $contracttypes_by_id[] = $contracttype['label'] . ' (' . \Dropdown::getDropdownName(ContractType::getTable(), $contracttype['id']) . ')';
                }
                // A new resource of that contract type goes on leading to the first meta-demand
                if (countElementsInTable(Metademand_Resource::getTable(), [
                    'plugin_resources_contracttypes_id' => $contracttype['id'],
                    'entities_id'                       => $entities_id,
                ]) > 0) {
                    $contracttypes_linked[] = $contracttype['label'];
                }
            }
            if ($contracttypes_not_found !== []) {
                $summary['warnings'][] = sprintf(
                    __('Contract types of the Resources plugin not found here, their links are not imported: %s', 'metademands'),
                    implode(', ', $contracttypes_not_found),
                );
            }
            if ($contracttypes_linked !== []) {
                $summary['warnings'][] = sprintf(
                    __('Contract types of the Resources plugin already leading to another meta-demand here, which they keep leading to: %s', 'metademands'),
                    implode(', ', $contracttypes_linked),
                );
            }
            if ($contracttypes_by_id !== []) {
                $summary['infos'][] = sprintf(
                    __('Contract types of the Resources plugin found by their id, the file coming from a previous version of the plugin: %s', 'metademands'),
                    implode(', ', $contracttypes_by_id),
                );
            }
        }

        $counts = [
            ['label' => _n('Block', 'Blocks', count($blocks), 'metademands'), 'count' => count($blocks)],
            ['label' => _n('Field', 'Fields', count($fields), 'metademands'), 'count' => count($fields)],
            ['label' => FieldCustomvalue::getTypeName(count($customvalues)), 'count' => count($customvalues)],
            ['label' => FieldOption::getTypeName(count($options)), 'count' => count($options)],
            ['label' => Condition::getTypeName($conditions), 'count' => $conditions],
            ['label' => Freetablefield::getTypeName(count($columns)), 'count' => count($columns)],
        ];
        foreach ($task_counts as $label => $count) {
            $counts[] = ['label' => $label, 'count' => $count];
        }
        $counts[] = ['label' => Step::getTypeName(count($steps)), 'count' => count($steps)];
        $counts[] = ['label' => TicketField::getTypeName(count($ticketfields)), 'count' => count($ticketfields)];
        $counts[] = [
            'label' => _n('Contract type of the Resources plugin', 'Contract types of the Resources plugin', count($resource_links), 'metademands'),
            'count' => count($resource_links),
        ];
        $nb_translations = count($translations) + $field_translations;
        $counts[] = ['label' => _n('Translation', 'Translations', $nb_translations, 'metademands'), 'count' => $nb_translations];
        $summary['counts'] = array_values(array_filter($counts, static fn(array $count): bool => $count['count'] > 0));

        return $summary;
    }

    /**
     * Empty summary of an import (analyzeImportData()).
     *
     * @return array{
     *     name: string,
     *     object_to_create: string,
     *     type: string,
     *     version: string,
     *     entity: string,
     *     counts: list<array{label: string, count: int}>,
     *     errors: list<string>,
     *     warnings: list<string>,
     *     infos: list<string>,
     * }
     */
    private static function newImportSummary(int $entities_id): array
    {
        return [
            'name'             => '',
            'object_to_create' => '',
            'type'             => '',
            'version'          => '',
            'entity'           => (string) \Dropdown::getDropdownName(Entity::getTable(), $entities_id),
            'counts'           => [],
            'errors'           => [],
            'warnings'         => [],
            'infos'            => [],
        ];
    }

    /**
     * Rows of a collection of the file (fields, options…), named after their ids in the XML
     * document (field12…).
     *
     * @param array<mixed> $datas
     *
     * @return array<array<mixed>>
     */
    private static function getImportRows(array $datas, string $key): array
    {
        $rows = $datas[$key] ?? null;

        return is_array($rows) ? array_filter($rows, 'is_array') : [];
    }

    /**
     * Whether the blocks of the file begin at 0: a GLPI form exported by the previous versions
     * (exportAsXMLFromGLPI() kept the ranks of its sections, numbered from 0). A block 0 is no
     * block in a metademand, but the one of its form information (Forminformation::BLOCK),
     * left where it is. Only a file whose options, child objects and steps refer to no block
     * is concerned, so that shifting its fields keeps it consistent.
     *
     * @param array<mixed> $datas
     */
    private static function hasBlocksFromZero(array $datas): bool
    {
        $from_zero = false;
        foreach (self::getImportRows($datas, 'metafields') as $field) {
            if (is_numeric($field['rank'] ?? null) && (int) $field['rank'] === 0
                && ($field['type'] ?? '') !== 'forminformation') {
                $from_zero = true;
                break;
            }
        }
        if (!$from_zero || self::getImportRows($datas, 'step') !== []) {
            return false;
        }

        $refers = static fn(mixed $blocks): bool => is_array($blocks)
            ? $blocks !== []
            : !in_array(trim((string) $blocks), ['', '[]', '0'], true);
        foreach (self::getImportRows($datas, 'metafieldoptions') as $option) {
            if ((int) ($option['hidden_block'] ?? 0) > 0 || $refers($option['childs_blocks'] ?? null)) {
                return false;
            }
        }
        foreach (self::getImportRows($datas, 'tasks') as $task) {
            if ($refers($task['block_use'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Blocks of the file numbered from 1 when they begin at 0 (hasBlocksFromZero()).
     *
     * @param array<mixed> $datas
     *
     * @return array<mixed>
     */
    private static function shiftBlocksFromZero(array $datas): array
    {
        if (!self::hasBlocksFromZero($datas)) {
            return $datas;
        }
        foreach (self::getImportRows($datas, 'metafields') as $key => $field) {
            if (is_numeric($field['rank'] ?? null) && ($field['type'] ?? '') !== 'forminformation') {
                $datas['metafields'][$key]['rank'] = (int) $field['rank'] + 1;
            }
        }

        return $datas;
    }

    /**
     * Whether the import keeps a reference of the file to one of its elements: no reference,
     * or a reference to an element of the file.
     *
     * @param array<int, true> $ids ids of the elements of the file
     */
    private static function isKeptReference(mixed $id, array $ids): bool
    {
        return !is_numeric($id) || (int) $id <= 0 || isset($ids[(int) $id]);
    }

    /**
     * GLPI objects a row of the file points at by their id, kept as such by the import: the
     * ones missing from this GLPI are added to $missing, the others counted in $found.
     *
     * @param array<mixed>                   $row
     * @param string[]                       $keys    foreign keys of the row
     * @param array<string, array<int, int>> $missing itemtype => ids
     */
    private static function collectReferencedItems(array $row, array $keys, array &$missing, int &$found): void
    {
        foreach ($keys as $key) {
            $id = is_numeric($row[$key] ?? null) ? (int) $row[$key] : 0;
            if ($id <= 0) {
                continue;
            }
            $itemtype = getItemtypeForForeignKeyField($key);
            $item     = getItemForItemtype($itemtype);
            if (!$item instanceof CommonDBTM) {
                continue;
            }
            if ($item->getFromDB($id)) {
                $found++;
            } else {
                $missing[$itemtype][$id] = $id;
            }
        }
    }

    /**
     * Groups of "Members of these groups" of a parameter of the file, found by their names,
     * their ids differing on another GLPI.
     *
     * @param array<mixed> $parameter
     *
     * @return array{0: list<int>, 1: list<string>} ids of the groups found, names of the others
     */
    private static function findMemberGroups(array $parameter, int $entities_id): array
    {
        $found   = [];
        $missing = [];
        foreach ((array) ($parameter['member_groups_names'] ?? []) as $group_name) {
            if (!is_string($group_name) || $group_name === '') {
                continue;
            }
            $groups_id = TicketField::findValueItem(\Group::class, $group_name, $entities_id);
            if ($groups_id > 0) {
                $found[] = $groups_id;
            } else {
                $missing[] = '"' . $group_name . '"';
            }
        }

        return [$found, $missing];
    }

    /**
     * Object of this GLPI a field of the ticket of the file points at, found by its name, its
     * id differing on another GLPI.
     *
     * @param array<mixed> $ticketfield
     *
     * @return array{id: int, label: string}|null null when the value is not an object (a text…),
     *                                            id 0 when there is no object of that name here
     */
    private static function findTicketFieldItem(string $object_to_create, array $ticketfield, int $entities_id): ?array
    {
        $value    = $ticketfield['value'] ?? '';
        $itemtype = TicketField::getValueItemtype($object_to_create, (int) ($ticketfield['num'] ?? 0));
        if ($itemtype === null || !is_numeric($value) || (int) $value <= 0) {
            return null;
        }
        $name = is_string($ticketfield['value_name'] ?? null) ? $ticketfield['value_name'] : '';

        return [
            'id'    => TicketField::findValueItem($itemtype, $name, $entities_id),
            'label' => $itemtype::getTypeName(1) . ' "' . ($name !== '' ? $name : $value) . '"',
        ];
    }

    /**
     * Name of a contract type of the Resources plugin, exported with the links to the
     * meta-demand; empty when the plugin is not active.
     */
    private static function getContractTypeName(int $contracttypes_id): string
    {
        $contracttype = getItemForItemtype(ContractType::class);
        if (!$contracttype instanceof CommonDBTM
            || $contracttypes_id <= 0
            || !$contracttype->getFromDB($contracttypes_id)) {
            return '';
        }

        return (string) ($contracttype->fields['name'] ?? '');
    }

    /**
     * Links of the file to contract types of the Resources plugin: several of them, with their
     * names (resource_links), or the single link of the exports of the previous versions, which
     * carry no name (resource).
     *
     * @param array<mixed> $datas
     *
     * @return list<array<mixed>>
     */
    private static function getImportedResourceLinks(array $datas): array
    {
        $resource_links = array_values(self::getImportRows($datas, 'resource_links'));
        if ($resource_links === [] && is_array($datas['resource'] ?? null)) {
            $resource_links[] = $datas['resource'];
        }

        return array_values(array_filter(
            $resource_links,
            static fn(array $resource_link): bool => (int) ($resource_link['plugin_resources_contracttypes_id'] ?? 0) > 0,
        ));
    }

    /**
     * Contract type of the Resources plugin a link of the file points at: the one of the same
     * name here, or of the same id for a link exported by a previous version (no name).
     *
     * @param array<mixed> $resource_link
     *
     * @return array{id: int, label: string, by_id: bool} id 0 when there is none, or when the
     *                                                    plugin is not active
     */
    private static function findContractType(array $resource_link, int $entities_id): array
    {
        $id    = (int) ($resource_link['plugin_resources_contracttypes_id'] ?? 0);
        $name  = is_string($resource_link['contracttype_name'] ?? null) ? $resource_link['contracttype_name'] : '';
        $found = [
            'id'    => 0,
            'label' => $name !== '' ? '"' . $name . '"' : '#' . $id,
            'by_id' => $name === '',
        ];

        $contracttype = getItemForItemtype(ContractType::class);
        if (!$contracttype instanceof CommonDBTM) {
            return $found;
        }
        if ($name !== '') {
            $found['id'] = TicketField::findValueItem(ContractType::class, $name, $entities_id);
        } elseif ($contracttype->getFromDB($id)) {
            $found['id'] = $id;
        }

        return $found;
    }

    /**
     * Validate the uploaded meta-demand file and move it to a private working copy.
     *
     * @return string|false the path of the working copy, or false when the upload is refused
     */
    private static function moveImportedXmlFile()
    {
        if (
            !isset($_FILES['meta_file'])
            || !is_array($_FILES['meta_file'])
            || !isset($_FILES['meta_file']['error'])
        ) {
            Session::addMessageAfterRedirect(__('No file specified', 'metademands'), false, ERROR);
            return false;
        }

        if (
            empty($_FILES['meta_file']['name'])
            || !is_uploaded_file($_FILES['meta_file']['tmp_name'])
            || $_FILES['meta_file']['error'] !== UPLOAD_ERR_OK
        ) {
            // Fail closed: any upload error, known or not, refuses the import instead of
            // falling through to an unset working file.
            switch ($_FILES['meta_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    Session::addMessageAfterRedirect(
                        __('File too large to be added.'),
                        false,
                        ERROR,
                    );
                    return false;

                case UPLOAD_ERR_NO_FILE:
                    Session::addMessageAfterRedirect(__('No file specified', 'metademands'), false, ERROR);
                    return false;

                default:
                    Session::addMessageAfterRedirect(__('Unable to upload the file', 'metademands'), false, ERROR);
                    return false;
            }
        }

        // Entity expansion happens inside the parser, before any business logic runs: bound the
        // document the same way ajax/addsignature.php bounds an uploaded signature.
        if (($_FILES['meta_file']['size'] ?? 0) > self::MAX_IMPORT_XML_BYTES) {
            Session::addMessageAfterRedirect(__('File too large to be added.'), false, ERROR);
            return false;
        }

        $tmp = explode(".", $_FILES['meta_file']['name']);
        $extension = array_pop($tmp);
        if (!Toolbox::getMime($_FILES['meta_file']['tmp_name'], 'text') || $extension != "xml") {
            Session::addMessageAfterRedirect(
                __('The file is not an XML file', 'metademands'),
                false,
                ERROR,
            );
            return false;
        }

        // Unique working name: a fixed one would let two concurrent imports read each other's file.
        $filename     = uniqid('import_', true);
        $picture_path = GLPI_PLUGIN_DOC_DIR . "/metademands/{$filename}.$extension";
        Document::renameForce($_FILES['meta_file']['tmp_name'], $picture_path);

        return $picture_path;
    }

    /**
     * @param string $file
     *
     * @return int|false
     */
    private static function importXmlFile($file)
    {
        // A rejected document must stop the import instead of flowing into json_encode(false)
        $error = null;
        $datas = self::readImportFile($file, $error);
        if ($datas === null) {
            Session::addMessageAfterRedirect((string) $error, false, ERROR);
            return false;
        }
        $datas = self::shiftBlocksFromZero($datas);
        $metademand = new Metademand();
        $oldId = $datas['id'] ?? 0;
        unset($datas['id']);
        unset($datas['date_creation']);
        unset($datas['date_mod']);
        unset($datas['itilcategories_id']);
        $datas['entities_id'] = $_SESSION['glpiactive_entity'];

        $mapTableField = [];
        $mapTableFieldReverse = [];
        $mapTableCheckValue = [];
        /** @var array<int|string, int> $customvalueFields old custom value id => old field id */
        $customvalueFields = [];

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

        $resource_links = self::getImportedResourceLinks($datas);

        $metatasks = [];
        if (isset($datas['metatasks'])) {
            $metatasks = $datas['metatasks'];
        }

        $translations = [];
        if (isset($datas['translations'])) {
            $translations = $datas['translations'];
        }

        $ticketfields = [];
        if (isset($datas['ticketfields']) && is_array($datas['ticketfields'])) {
            $ticketfields = $datas['ticketfields'];
        }

        foreach ($datas as $key => $data) {
            if (is_array($data) && empty($data)) {
                $datas[$key] = '';
            } elseif (!is_array($data)) {
                $datas[$key] = $data;
            }
        }

        $newIDMeta = $metademand->add($datas);
        if (!$newIDMeta) {
            // Nothing to attach the fields, options and child objects to
            return false;
        }
        //      $translations = [];

        $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

        /** @var array<int, array{fields_id: int, check_value: mixed}> $imported_conditions new condition id => old field id, value */
        $imported_conditions = [];
        /** @var array<int, int> $previous_fields new field id => old id of its "display after" field */
        $previous_fields = [];
        foreach ($fields as $k => $field) {
            $metaconditions = [];
            // The translations of the previous field otherwise went to a field without any
            unset($fieldstranslations);
            foreach ($field as $key => $f) {
                $fields[$k][$key] = $f;

                $fieldoldparams[$k][$key] = $f;

                if ($key == "custom_values" && isset($field['type'])
                    && in_array($field['type'], $allowed_customvalues_types)
                    || (isset($field['item']) && in_array($field['item'], $allowed_customvalues_items))
                ) {
                    $fieldoldcustoms[$k]["id"] = $fields[$k]["id"];
                    if (is_array($f)) {
                        $fieldoldcustoms[$k][$key] = FieldParameter::_serializeArray($f);
                    } else {
                        $fieldoldcustoms[$k][$key] = FieldParameter::_unserialize($f);
                    }

                    if (is_null($fields[$k][$key])) {
                        $fieldoldcustoms[$k][$key] = "[]";
                    }
                } elseif (str_contains($key, 'condition')) {
                    $metaconditions[] = $f;
                } elseif ($key == "informations_to_display") {
                    $fields[$k][$key] = FieldParameter::_unserialize($f);
                    $fields[$k][$key] = FieldParameter::_serialize($fields[$k][$key]);
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

            $fields[$k]["plugin_metademands_metademands_id"] = $newIDMeta;
            $fields[$k]["date_creation"] = $_SESSION['glpi_currenttime'];
            $fields[$k]["date_mod"] = $_SESSION['glpi_currenttime'];

            $metaField = new Field();
            $newIDField = $metaField->add($fields[$k]);
            $condition = new Condition();
            if (count($metaconditions) > 0) {
                foreach ($metaconditions as $cond) {
                    unset($cond['id']);
                    $cond['plugin_metademands_fields_id'] = $newIDField;
                    $cond['plugin_metademands_metademands_id'] = $newIDMeta;
                    $new_conditions_id = $condition->add($cond);
                    // Its value may be a custom value of the field, not imported yet
                    if ($new_conditions_id) {
                        $imported_conditions[$new_conditions_id] = [
                            'fields_id'   => (int) $oldIDField,
                            'check_value' => $cond['check_value'] ?? null,
                        ];
                    }
                }
            }
            $mapTableField[$oldIDField] = $newIDField;
            $mapTableFieldReverse[$newIDField] = $oldIDField;
            // "Display field after": another field, which may not be imported yet
            $previous_fields[$newIDField] = (int) ($fields[$k]['plugin_metademands_fields_id'] ?? 0);
            if (isset($fieldstranslations)) {
                foreach ($fieldstranslations as $fieldstranslation) {
                    unset($fieldstranslation['id']);
                    $fieldstranslation['items_id'] = $newIDField;
                    // Backward compatibility: old XML used 'field'+'value', new uses 'key'+'translations'
                    if (isset($fieldstranslation['field']) && !isset($fieldstranslation['key'])) {
                        $fieldstranslation['key'] = $fieldstranslation['field'];
                        unset($fieldstranslation['field']);
                    }
                    if (isset($fieldstranslation['value']) && !isset($fieldstranslation['translations'])) {
                        $fieldstranslation['translations'] = ['one' => $fieldstranslation['value']];
                        unset($fieldstranslation['value']);
                    }
                    $trans = new FieldTranslation();
                    $trans->add($fieldstranslation);
                }
            }
        }

        foreach ($previous_fields as $new_fields_id => $old_previous_id) {
            $metaField = new Field();
            if ($old_previous_id > 0 && $metaField->getFromDB($new_fields_id)) {
                $metaField->fields['plugin_metademands_fields_id'] = $mapTableField[$old_previous_id] ?? 0;
                $metaField->updateInDB(['plugin_metademands_fields_id']);
            }
        }

        $mapTableTask = [];
        $mapTableTaskReverse = [];


        foreach ($tasks as $k => $task) {
            $oldIDTask = $task['id'];
            unset($task['id']);
            unset($task['ancestors_cache']);
            unset($task['sons_cache']);
            $tickettask = $task['tickettask'] ?? [];
            $mailtask = $task['mailtask'] ?? [];

            foreach ($task as $key => $val) {
                $task[$key] = $val;
            }
            $task['entities_id'] = $_SESSION['glpiactive_entity'];
            // The tags of the title point at the fields created above
            foreach (['name', 'completename'] as $key) {
                if (isset($task[$key]) && is_string($task[$key])) {
                    $task[$key] = self::remapFieldTags($task[$key], $mapTableField);
                }
            }

            $task['plugin_metademands_metademands_id'] = $newIDMeta;

            $meta_task = new Task();
            $newIDTask = $meta_task->add($task);

            $mapTableTask[$oldIDTask] = $newIDTask;
            $mapTableTaskReverse[$newIDTask] = $oldIDTask;


            if (is_array($tickettask) && !empty($tickettask)) {
                unset($tickettask['id']);
                foreach ($tickettask as $key => $val) {
                    if (is_array($val) && empty($val)) {
                        $tickettask[$key] = '';
                    } elseif (!is_array($val)) {
                        $tickettask[$key] = $val;
                    }
                }
                if (isset($tickettask['content']) && is_string($tickettask['content'])) {
                    $tickettask['content'] = self::remapFieldTags($tickettask['content'], $mapTableField);
                }
                $tickettask['plugin_metademands_tasks_id'] = $newIDTask;
                $tickettaskP = new TicketTask();
                $tickettaskP->add($tickettask);
            }

            if (is_array($mailtask) && !empty($mailtask)) {
                unset($mailtask['id']);

                foreach ($mailtask as $key => $val) {
                    if (is_array($val) && empty($val)) {
                        $mailtask[$key] = '';
                    } elseif (!is_array($val)) {
                        $mailtask[$key] = $val;
                    }
                }
                if (isset($mailtask['content']) && is_string($mailtask['content'])) {
                    $mailtask['content'] = self::remapFieldTags($mailtask['content'], $mapTableField);
                }
                $mailtask['plugin_metademands_tasks_id'] = $newIDTask;
                $mailtaskP = new MailTask();
                $mailtaskP->add($mailtask);
            }
        }

        //Add new params & update fields
        $fieldMetaparam = new FieldParameter();

        if ($version < "3.3.11") {
            foreach ($fieldoldparams as $new => $old) {
                $plugin_metademands_fields_id = $old["id"] ?? 0;
                $empty_values = FieldParameter::_serialize([]);
                ;

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
                // "Link this field to a user field": the new id of that field
                $toUpdate["link_to_user"] = $mapTableField[(int) ($old["link_to_user"] ?? 0)] ?? 0;
                $toUpdate["default_use_id_requester"] = $old["default_use_id_requester"] ?? 0;
                $toUpdate["default_use_id_requester_supervisor"] = $old["default_use_id_requester_supervisor"] ?? 0;
                $toUpdate["use_future_date"] = $old["use_future_date"] ?? 0;
                $toUpdate['authldaps_id'] = $old['authldaps_id'] ?? 0;
                $toUpdate['ldap_attribute'] = $old['ldap_attribute'] ?? 0;
                $toUpdate['ldap_filter'] = $old['ldap_filter'] ?? "";
                $toUpdate["use_date_now"] = $old["use_date_now"] ?? 0;
                $toUpdate["additional_number_day"] = $old["additional_number_day"] ?? 0;
                $toUpdate["informations_to_display"] = FieldParameter::_serialize(['full_name']);
                $toUpdate["use_richtext"] = $old["use_richtext"] ?? 0;
                $toUpdate["icon"] = $old["icon"] ?? "";
                $toUpdate["readonly"] = $old["readonly"] ?? 0;
                $toUpdate["hidden"] = $old["hidden"] ?? 0;

                if ($plugin_metademands_fields_id != 0
                    && isset($mapTableField[$plugin_metademands_fields_id])) {
                    $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                }
                $fielddata = new Field();
                if ($fielddata->getFromDB($toUpdate['plugin_metademands_fields_id'])) {
                    $toUpdate["type"] = $fielddata->fields["type"];
                    $toUpdate["item"] = $fielddata->fields["item"];
                }

                $fieldMetaparam->add($toUpdate);
            }
        }

        $groups_not_found = [];
        if ($version >= "3.3.11") {
            foreach ($fieldparameters as $new => $old) {
                $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
                // Attached to no field of the file (analyzeImportData() reports it)
                if (!isset($mapTableField[$plugin_metademands_fields_id])) {
                    continue;
                }
                $empty_values = FieldParameter::_serialize([]);
                // Nothing of the previous parameter is carried over
                $toUpdate = [];

                $toUpdate["custom"] = $old["custom"] ?? $empty_values;
                // "Members of these groups": the groups of the same names here, the others
                // left out (rather than the groups holding their old ids)
                $custom = FieldParameter::_unserialize($toUpdate["custom"]);
                if (is_array($custom) && FieldParameter::getMemberGroupsIds($custom) !== []) {
                    [$member_groups, $missing_groups] = self::findMemberGroups($old, (int) $_SESSION['glpiactive_entity']);
                    array_push($groups_not_found, ...$missing_groups);
                    $custom['member_groups'] = FieldParameter::formatMemberGroups($member_groups);
                    $toUpdate["custom"] = FieldParameter::_serialize($custom);
                }
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
                // "Link this field to a user field": the new id of that field
                $toUpdate["link_to_user"] = $mapTableField[(int) ($old["link_to_user"] ?? 0)] ?? 0;
                $toUpdate["default_use_id_requester"] = $old["default_use_id_requester"] ?? 0;
                $toUpdate["default_use_id_requester_supervisor"] = $old["default_use_id_requester_supervisor"] ?? 0;
                $toUpdate["use_future_date"] = $old["use_future_date"] ?? 0;
                $toUpdate['authldaps_id'] = $old['authldaps_id'] ?? 0;
                $toUpdate['ldap_attribute'] = $old['ldap_attribute'] ?? 0;
                $toUpdate['ldap_filter'] = $old['ldap_filter'] ?? "";
                $toUpdate["use_date_now"] = $old["use_date_now"] ?? 0;
                $toUpdate["additional_number_day"] = $old["additional_number_day"] ?? 0;

                if (isset($old["informations_to_display"]) && $old["informations_to_display"] != null) {
                    if (FieldParameter::_serialize($old["informations_to_display"]) != null) {
                        $toUpdate["informations_to_display"] = FieldParameter::_serialize(
                            $old["informations_to_display"],
                        );
                    }
                } else {
                    $toUpdate["informations_to_display"] = FieldParameter::_serialize(['full_name']);
                }
                $toUpdate["use_richtext"] = $old["use_richtext"] ?? 0;
                $toUpdate["icon"] = $old["icon"] ?? "";
                $toUpdate["readonly"] = $old["readonly"] ?? 0;
                $toUpdate["hidden"] = $old["hidden"] ?? 0;
                $toUpdate["show_location_card"] = $old["show_location_card"] ?? 0;

                if ($plugin_metademands_fields_id != 0
                    && isset($mapTableField[$plugin_metademands_fields_id])) {
                    $toUpdate['plugin_metademands_fields_id'] = $mapTableField[$plugin_metademands_fields_id];
                }

                $fieldMetaparam->add($toUpdate);
            }
        }

        //Add new custom values & update fields
        $fieldMetacustom = new FieldCustomvalue();
        $custom_values = [];
        if ($version < "3.3.11") {
            foreach ($fieldoldcustoms as $new => $old) {
                $plugin_metademands_fields_id = $old["id"] ?? 0;
                $custom_values = $old["custom_values"] ?? [];

                if (count($custom_values) > 0) {
                    foreach ($custom_values as $rank => $custom_value) {
                        $name = $custom_value;
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
                            $toUpdate["comment"] = $comment;
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
                // Attached to no field of the file (analyzeImportData() reports it)
                if (!isset($mapTableField[$plugin_metademands_fields_id])) {
                    continue;
                }
                $name = $old["name"] ?? "";
                $is_default = $old["is_default"] ?? 0;
                $comment = $old["comment"] ?? "";
                $icon = $old["icon"] ?? "";
                $rank = $old["rank"] ?? 0;

                $toUpdate = [];
                if ($name != "") {
                    $toUpdate["name"] = $name;
                }
                if ($is_default != 0) {
                    $toUpdate["is_default"] = $is_default;
                }
                if ($comment != "") {
                    $toUpdate["comment"] = $comment;
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
                // The field each custom value belongs to: only the options of that field compare
                // their value with it (see the options below)
                $customvalueFields[$old["id"]] = (int) $plugin_metademands_fields_id;
            }
        }

        //Add new freetable fields
        $fieldMetafreetablefield = new Freetablefield();
        if ($version >= "3.3.20") {
            foreach ($fieldfreetablefields as $new => $old) {
                $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
                // Attached to no field of the file (analyzeImportData() reports it)
                if (!isset($mapTableField[$plugin_metademands_fields_id])) {
                    continue;
                }
                $name = $old["name"] ?? "";
                $internal_name = $old["internal_name"] ?? "";
                $type = $old["type"] ?? "text";
                $comment = $old["comment"] ?? "";
                $dropdown_values = $old["dropdown_values"] ?? "";
                $is_mandatory = $old["is_mandatory"] ?? 0;
                $rank = $old["rank"] ?? 0;

                $toUpdate = [];
                if ($name != "") {
                    $toUpdate["name"] = $name;
                }
                if ($internal_name != "") {
                    $toUpdate["internal_name"] = $internal_name;
                }
                if ($type != "") {
                    $toUpdate["type"] = $type;
                }
                if ($comment != "") {
                    $toUpdate["comment"] = $comment;
                }
                if ($dropdown_values != "") {
                    $toUpdate["dropdown_values"] = $dropdown_values;
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

                // Not in $mapTableCheckValue: no option checks a column of a free table, and its
                // old id overwrote the mapping of a custom value holding the same old id
                $fieldMetafreetablefield->add($toUpdate);
            }
        }

        // The value a condition compares its field with: the new id of a custom value of that
        // field, as for the value an option checks below
        if ($version >= "3.3.11") {
            $condition = new Condition();
            foreach ($imported_conditions as $new_conditions_id => $imported_condition) {
                $check_value = $imported_condition['check_value'];
                if (is_numeric($check_value)
                    && isset($mapTableCheckValue[(int) $check_value])
                    && ($customvalueFields[(int) $check_value] ?? null) === $imported_condition['fields_id']) {
                    $condition->update([
                        'id'          => $new_conditions_id,
                        'check_value' => $mapTableCheckValue[(int) $check_value],
                    ]);
                }
            }
        }

        //Add new options & update fields
        $fieldMetaopt = new FieldOption();
        $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

        foreach ($fieldoptions as $new => $old) {
            //            $fieldMeta->getFromDBByCrit(["plugin_metademands_fileds_id" => $new]);

            //            if (isset($field['type'])
            //                && !in_array($field['type'], $allowed_customvalues_types)
            //                || (isset($field['item']) && !in_array($field['item'], $allowed_customvalues_items))
            //            ) {
            $check_value = $old["check_value"] ?? 0;
            $plugin_metademands_fields_id = $old["plugin_metademands_fields_id"] ?? 0;
            // Attached to no field of the file (analyzeImportData() reports it)
            if (!isset($mapTableField[$plugin_metademands_fields_id])) {
                continue;
            }
            $plugin_metademands_tasks_id = $old["plugin_metademands_tasks_id"] ?? 0;
            $fields_link = $old["fields_link"] ?? 0;
            $hidden_link = $old["hidden_link"] ?? 0;
            $hidden_block = $old["hidden_block"] ?? 0;
            $users_id_validate = $old["users_id_validate"] ?? 0;
            // A list since validators may be several; a single id in the archives of the first version
            $validation_fields_ids = isset($old["validation_fields_ids"])
                ? FieldOption::getValidationFieldsIds($old)
                : array_filter([(int) ($old["validation_fields_id"] ?? 0)]);
            $childs_blocks = $old["childs_blocks"] ?? [];
            $checkbox_value = $old["checkbox_value"] ?? 0;
            $checkbox_id = $old["checkbox_id"] ?? 0;
            $hidden_block_same_block = $old["hidden_block_same_block"] ?? 0;
            // Exported but not read back: an option compared with a regular expression became
            // a comparison with a value, and opened its child blocks for other answers
            $check_type_value = (int) ($old["check_type_value"] ?? 0);
            $check_value_regex = $old["check_value_regex"] ?? null;
            $assign_tech_group = $old["assign_tech_group"] ?? null;
            //            $parent_field_id = $old["parent_field_id"]??0;
            //
            $toUpdate = [];
            if ($check_value != 0) {
                $toUpdate["check_value"] = $check_value;
            }
            if ($version >= "3.3.11") {
                // A value to check is the id of a custom value of the same field only: a yes / no
                // (1 / 2), a number or a dropdown item kept its value, which used to be mapped to
                // the custom value of another field holding the same old id (a "yes" turned into
                // the id of an unrelated custom value, and its option never matched again)
                if ($check_value != 0
                    && isset($mapTableCheckValue[$check_value])
                    && ($customvalueFields[$check_value] ?? null) === (int) $plugin_metademands_fields_id) {
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
            // Any value but the value to check; the validation template when it exists here
            if (!empty($old["is_negated"])) {
                $toUpdate["is_negated"] = 1;
            }
            $validation_template = new \ITILValidationTemplate();
            if ((int) ($old["itilvalidationtemplates_id"] ?? 0) > 0
                && $validation_template->getFromDB((int) $old["itilvalidationtemplates_id"])) {
                $toUpdate["itilvalidationtemplates_id"] = $validation_template->getID();
            }
            $validation_fields_ids = array_values(array_filter(array_map(
                static fn(int $id) => $mapTableField[$id] ?? 0,
                $validation_fields_ids,
            )));
            if ($validation_fields_ids !== []) {
                $toUpdate["validation_fields_ids"] = json_encode($validation_fields_ids);
            }
            // A flat list ([1]) is the value left by 3.6.15 (issue #367) on instances not updated
            // since: it hid no block there, while normalizeChildsBlocks() would make it a group
            // and open block 1. Same rule as FieldOption::resetFlatChildsBlocks().
            $decoded_blocks = is_string($childs_blocks) ? json_decode($childs_blocks, true) : $childs_blocks;
            if (is_array($decoded_blocks)
                && $decoded_blocks !== []
                && count(array_filter($decoded_blocks, 'is_array')) === 0) {
                $childs_blocks = [];
            }
            if ($childs_blocks) {
                $toUpdate["childs_blocks"] = $childs_blocks;
            }
            // The checkbox field the option depends on, and the custom value of that field
            // it checks, as the value to check above
            if ($checkbox_id != 0 && isset($mapTableField[$checkbox_id])) {
                $toUpdate["checkbox_id"] = $mapTableField[$checkbox_id];
                if ($checkbox_value != 0) {
                    $toUpdate["checkbox_value"] = $checkbox_value;
                    if ($version >= "3.3.11") {
                        if (isset($mapTableCheckValue[$checkbox_value])
                            && ($customvalueFields[$checkbox_value] ?? null) === (int) $checkbox_id) {
                            $toUpdate["checkbox_value"] = $mapTableCheckValue[$checkbox_value];
                        }
                    } elseif (isset($mapTableCheckValue[$checkbox_id][$checkbox_value])) {
                        $toUpdate["checkbox_value"] = $mapTableCheckValue[$checkbox_id][$checkbox_value];
                    }
                }
            }
            if ($hidden_block_same_block != 0) {
                $toUpdate["hidden_block_same_block"] = $hidden_block_same_block;
            }
            if ($check_type_value > 0) {
                $toUpdate["check_type_value"] = $check_type_value;
            }
            if (is_string($check_value_regex) && $check_value_regex !== '') {
                $toUpdate["check_value_regex"] = $check_value_regex;
            }
            if (is_string($assign_tech_group) && is_array(json_decode($assign_tech_group, true))) {
                $toUpdate["assign_tech_group"] = $assign_tech_group;
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
            $meta_task = new Task();
            $meta_task->getFromDB($new);
            $toUpdate = [];
            $toUpdate['id'] = $new;
            $parent_tasks_id = (int) ($meta_task->fields["plugin_metademands_tasks_id"] ?? 0);
            if ($parent_tasks_id > 0) {
                // A parent missing from the file is dropped, rather than kept as the old id
                // of an unrelated child object of this GLPI
                $toUpdate["plugin_metademands_tasks_id"] = $mapTableTask[$parent_tasks_id] ?? 0;
            }
            $meta_task->update($toUpdate);
        }

        // Contract types of the Resources plugin leading to the meta-demand: the ones of the
        // same names here, in the entity of the meta-demand
        $contracttypes_not_found = [];
        foreach ($resource_links as $resource_link) {
            $contracttype = self::findContractType($resource_link, (int) $_SESSION['glpiactive_entity']);
            if ($contracttype['id'] === 0) {
                $contracttypes_not_found[] = $contracttype['label'];
                continue;
            }
            (new Metademand_Resource())->add([
                'plugin_resources_contracttypes_id' => $contracttype['id'],
                'plugin_metademands_metademands_id' => $newIDMeta,
                'entities_id'                       => $_SESSION['glpiactive_entity'],
            ]);
        }
        if ($contracttypes_not_found !== []) {
            Session::addMessageAfterRedirect(
                getItemForItemtype(ContractType::class) instanceof CommonDBTM
                    ? sprintf(
                        __('Contract types of the Resources plugin not found here, their links are not imported: %s', 'metademands'),
                        implode(', ', $contracttypes_not_found),
                    )
                    : __('The Resources plugin is not active here: the links to its contract types are not imported', 'metademands'),
                false,
                WARNING,
            );
        }

        if (!empty($stepconfig)) {
            $stepconfig_meta = new Configstep();
            if ($stepconfig_meta->getFromDBByCrit(['plugin_metademands_metademands_id' => $newIDMeta])) {
                $stepconfig['id'] = $stepconfig_meta->fields['id'];
                $stepconfig['plugin_metademands_metademands_id'] = $newIDMeta;
                $stepconfig['see_blocks_as_tab'] ??= 0;
                $stepconfig['link_user_block'] ??= 0;
                $stepconfig['multiple_link_groups_blocks'] ??= 0;
                $stepconfig['add_user_as_requester'] ??= 0;
                $stepconfig['supervisor_validation'] ??= 0;
                $stepconfig['step_by_step_interface'] ??= 0;
                $stepconfig_meta->update($stepconfig);
            }
        }

        if (!empty($steps)) {
            foreach ($steps as $key => $ste) {
                $meta_step = new Step();
                $metas['block_id'] = $ste['block_id'];
                $metas['groups_id'] = $ste['groups_id'] ?? 0;
                $metas['only_by_supervisor'] = $ste['only_by_supervisor'] ?? 0;
                $metas['reminder_delay'] = $ste['reminder_delay'] ?? "";
                $metas['message'] = $ste['message'] ?? "";
                $metas['plugin_metademands_metademands_id'] = $newIDMeta;
                $meta_step->add($metas);
            }
        }

        if (!empty($metatasks)) {
            foreach ($metatasks as $key => $metatask) {
                // Its child object is missing from the file (analyzeImportData() reports it)
                if (!isset($mapTableTask[$metatask['plugin_metademands_tasks_id'] ?? 0])) {
                    continue;
                }
                $meta_metatask = new MetademandTask();
                $metat = [];
                $metat['plugin_metademands_metademands_id'] = $newIDMeta;
                $metat['plugin_metademands_tasks_id'] = $mapTableTask[$metatask['plugin_metademands_tasks_id']];
                $meta_metatask->add($metat);
            }
        }

        // Fields of the object to create: the tags of a text point at the new fields, a value
        // pointing at a GLPI object at the object of the same name here, the row being left out
        // when there is none (rather than pointing at another object holding the old id)
        $not_found = [];
        $object_to_create = (string) ($metademand->fields['object_to_create'] ?? '');
        foreach ($ticketfields as $ticketfield) {
            if (!is_array($ticketfield) || !isset($ticketfield['num'])) {
                continue;
            }
            $num   = (int) $ticketfield['num'];
            $value = $ticketfield['value'] ?? '';
            $item  = self::findTicketFieldItem($object_to_create, $ticketfield, (int) $_SESSION['glpiactive_entity']);
            if ($item !== null) {
                if ($item['id'] === 0) {
                    $not_found[] = $item['label'];
                    continue;
                }
                $value = $item['id'];
            } elseif (is_string($value)) {
                $value = self::remapFieldTags($value, $mapTableField);
            }
            // The field synchronised with the template on the add takes the value
            TicketField::copyField((int) $newIDMeta, [
                'num'          => $num,
                'value'        => $value,
                'is_mandatory' => (int) ($ticketfield['is_mandatory'] ?? 0),
                'is_deletable' => (int) ($ticketfield['is_deletable'] ?? 1),
                'is_recursive' => (int) ($ticketfield['is_recursive'] ?? 0),
                'entities_id'  => $_SESSION['glpiactive_entity'],
            ]);
        }
        if ($not_found !== []) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __('Fields of the ticket not imported, no item of that name here: %s', 'metademands'),
                    implode(', ', $not_found),
                ),
                false,
                WARNING,
            );
        }
        if ($groups_not_found !== []) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __('Groups left out of "Members of these groups", no group of that name here: %s', 'metademands'),
                    implode(', ', array_unique($groups_not_found)),
                ),
                false,
                WARNING,
            );
        }

        if (!empty($translations)) {
            foreach ($translations as $key => $trans) {
                $meta_translation = new MetademandTranslation();
                unset($trans['id']);
                $trans['items_id'] = $newIDMeta;
                // Backward compatibility: old XML used 'field'+'value', new uses 'key'+'translations'
                if (isset($trans['field']) && !isset($trans['key'])) {
                    $trans['key'] = $trans['field'];
                    unset($trans['field']);
                }
                if (isset($trans['value']) && !isset($trans['translations'])) {
                    $trans['translations'] = ['one' => $trans['value']];
                    unset($trans['value']);
                }
                $meta_translation->add($trans);
            }
        }

        return $newIDMeta;
    }

    /**
     * Point the field tags of a text (#id# and #id.attr#, resolved by
     * Metademand::resolvePlaceholders()) at the new ids of their fields.
     *
     * Only the ids of the map are changed, in one pass so that a new id is never mapped
     * again. A tag may end on the '#' opening the next one (#12#13#), as the resolver splits
     * the text on '#': the closing '#' is looked ahead, not consumed.
     *
     * @param array<int|string, int|string> $map_fields old field id => new field id
     */
    public static function remapFieldTags(string $text, array $map_fields): string
    {
        if ($text === '' || $map_fields === []) {
            return $text;
        }

        return preg_replace_callback(
            self::FIELD_TAG_PATTERN,
            static fn(array $matches): string => '#' . ($map_fields[$matches[1]] ?? $matches[1]),
            $text,
        ) ?? $text;
    }

    private static function normalizeXmlData($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        if (count($data) === 0) {
            return null;
        }
        foreach ($data as $k => $v) {
            $data[$k] = self::normalizeXmlData($v);
        }
        return $data;
    }
}
