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

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use GlpiPlugin\Metademands\Basketline;
use GlpiPlugin\Metademands\Basketobject;
use GlpiPlugin\Metademands\BasketobjectInjection;
use GlpiPlugin\Metademands\BasketobjectTranslation;
use GlpiPlugin\Metademands\Basketobjecttype;
use GlpiPlugin\Metademands\BasketobjecttypeTranslation;
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Configstep;
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Metademands\Draft_Value;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\FieldTranslation;
use GlpiPlugin\Metademands\Form;
use GlpiPlugin\Metademands\Form_Value;
use GlpiPlugin\Metademands\Freetablefield;
use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\GroupConfig;
use GlpiPlugin\Metademands\Helpdesk\Tile\MetademandPageTile;
use GlpiPlugin\Metademands\Interticketfollowup;
use GlpiPlugin\Metademands\MailTask;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Metademand_Resource;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Metademands\MetademandTranslation;
use GlpiPlugin\Metademands\MetademandValidation;
use GlpiPlugin\Metademands\Profile;
use GlpiPlugin\Metademands\Step;
use GlpiPlugin\Metademands\Stepform;
use GlpiPlugin\Metademands\Stepform_Actor;
use GlpiPlugin\Metademands\Stepform_Value;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Field;
use GlpiPlugin\Metademands\Ticket_Metademand;
use GlpiPlugin\Metademands\Ticket_Task;
use GlpiPlugin\Metademands\TicketField;
use GlpiPlugin\Metademands\TicketTask;

use function Safe\mkdir;

/**
 * @return bool
 * @throws GlpitestSQLError
 */
function plugin_metademands_install()
{
    global $DB;

    $migration = new Migration(PLUGIN_METADEMANDS_VERSION);

    if (!$DB->tableExists("glpi_plugin_metademands_fields", false)
    && !$DB->tableExists("glpi_plugin_metademands_fieldparameters", false)
        && !$DB->tableExists("glpi_plugin_metademands_fieldoptions", false)) {

        // Adds the right(s) to all pre-existing profiles with no access by default
        Profile::initProfile();

        Basketline::install($migration);
        Basketobject::install($migration);
        BasketobjectTranslation::install($migration);
        Basketobjecttype::install($migration);
        BasketobjecttypeTranslation::install($migration);
        Condition::install($migration);
        Config::install($migration);
        Configstep::install($migration);
        Draft::install($migration);
        Draft_Value::install($migration);
        Field::install($migration);
        FieldCustomvalue::install($migration);
        FieldOption::install($migration);
        FieldParameter::install($migration);
        FieldTranslation::install($migration);
        Form::install($migration);
        Form_Value::install($migration);
        Freetablefield::install($migration);
        Group::install($migration);
        GroupConfig::install($migration);
        Interticketfollowup::install($migration);
        MailTask::install($migration);
        Metademand::install($migration);
        MetademandPageTile::install($migration);
        Metademand_Resource::install($migration);
        MetademandTask::install($migration);
        MetademandTranslation::install($migration);
        MetademandValidation::install($migration);
        Step::install($migration);
        Stepform::install($migration);
        Stepform_Actor::install($migration);
        Stepform_Value::install($migration);
        Task::install($migration);
        Ticket_Field::install($migration);
        Ticket_Metademand::install($migration);
        Ticket_Task::install($migration);
        TicketField::install($migration);
        TicketTask::install($migration);

        $migration->executeMigration();

    } else {

        Basketline::install($migration);
        Basketobject::install($migration);
        BasketobjectTranslation::install($migration);
        Basketobjecttype::install($migration);
        BasketobjecttypeTranslation::install($migration);
        Condition::install($migration);
        Config::install($migration);

        Draft::install($migration);
        Draft_Value::install($migration);
        Field::install($migration);

        FieldTranslation::install($migration);
        Form::install($migration);
        Form_Value::install($migration);
        Freetablefield::install($migration);
        Group::install($migration);
        GroupConfig::install($migration);
        Interticketfollowup::install($migration);
        MailTask::install($migration);
        Metademand::install($migration);
        MetademandPageTile::install($migration);
        Metademand_Resource::install($migration);
        MetademandTask::install($migration);
        MetademandTranslation::install($migration);
        MetademandValidation::install($migration);
        Step::install($migration);

        Stepform_Actor::install($migration);
        Stepform_Value::install($migration);
        Task::install($migration);
        Ticket_Field::install($migration);
        Ticket_Metademand::install($migration);
        Ticket_Task::install($migration);
        TicketField::install($migration);
        TicketTask::install($migration);

        //version 2.7.5
        $table_fields = "glpi_plugin_metademands_fields";

        if (!$DB->fieldExists($table_fields, "display_type", false)
            && !$DB->tableExists("glpi_plugin_metademands_fieldoptions", false)) {

            FieldOption::preMigrateFieldsOptions($migration);

        }

        //version 3.3.0
        //Creation glpi_plugin_metademands_fieldoptions
        if (!$DB->tableExists("glpi_plugin_metademands_fieldoptions", false)) {

            Configstep::install($migration);
            FieldOption::install($migration);
            Stepform::install($migration);

            FieldOption::migrateFieldsOptions($migration);

        }


        //version 3.3.11
        //creation glpi_plugin_metademands_fieldparameters / glpi_plugin_metademands_fieldcustomvalues
        if (!$DB->tableExists("glpi_plugin_metademands_fieldparameters", false)) {

            FieldParameter::install($migration);
            FieldCustomvalue::install($migration);

            FieldParameter::migrateFieldsParameters($migration);

        }
    }

    Interticketfollowup::addNotifications();
    Stepform::addNotifications();

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

    $rep_files_metademands = GLPI_PLUGIN_DOC_DIR . "/metademands";
    if (!is_dir($rep_files_metademands)) {
        mkdir($rep_files_metademands);
    }

    return true;
}

// Uninstall process for plugin : need to return true if succeeded
/**
 * @return bool
 * @throws GlpitestSQLError
 */
function plugin_metademands_uninstall()
{
    global $DB;

    // Plugin tables deletion
    Basketline::uninstall();
    Basketobject::uninstall();
    BasketobjectTranslation::uninstall();
    Basketobjecttype::uninstall();
    BasketobjecttypeTranslation::uninstall();
    Condition::uninstall();
    Config::uninstall();
    Configstep::uninstall();
    Draft::uninstall();
    Draft_Value::uninstall();
    Field::uninstall();
    FieldCustomvalue::uninstall();
    FieldOption::uninstall();
    FieldParameter::uninstall();
    FieldTranslation::uninstall();
    Form::uninstall();
    Form_Value::uninstall();
    Freetablefield::uninstall();
    Group::uninstall();
    GroupConfig::uninstall();
    Interticketfollowup::uninstall();
    MailTask::uninstall();
    Metademand::uninstall();
    MetademandPageTile::uninstall();
    Metademand_Resource::uninstall();
    MetademandTask::uninstall();
    MetademandTranslation::uninstall();
    MetademandValidation::uninstall();
    Step::uninstall();
    Stepform::uninstall();
    Stepform_Actor::uninstall();
    Stepform_Value::uninstall();
    Task::uninstall();
    Ticket_Field::uninstall();
    Ticket_Metademand::uninstall();
    Ticket_Task::uninstall();
    TicketField::uninstall();
    TicketTask::uninstall();

    //old tables
    $tables = [
        "glpi_plugin_metademands_tickets_itilenvironments",
        "glpi_plugin_metademands_tickets_itilapplications",
        "glpi_plugin_metademands_itilenvironments",
        "glpi_plugin_metademands_itilapplications",
        "glpi_plugin_metademands_profiles",
       ];
    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    Profile::removeRightsFromSession();
    Profile::removeRightsFromDB();

    return true;
}

// How to display specific actions ?
// options contain at least itemtype and and action
/**
 * @param array $options
 */
//function plugin_metademands_MassiveActionsDisplay($options = []) {
//
//   switch ($options['itemtype']) {
//      case 'Metademand':
//         switch ($options['action']) {
//            case "plugin_metademands_duplicate":
//               echo "&nbsp;". Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
//               break;
//         }
//         break;
//   }
//}

/**
 * Triggered after an item was transferred
 * @param $parm array-key
 * 'type' => transfered item type,
 * 'id' => transfered item id,
 * 'newID' => transfered item id,
 * 'entities_id' => transfered to entity id
 * @return void
 */
function plugin_item_transfer_metademands($parm)
{
    // transfer a metademand's relation after GLPI transfered it in Transfer->transferItem()
    if ($parm['type'] === Metademand::class) {
        global $DB;
        $tables = [
            'glpi_plugin_metademands_fields',
            'glpi_plugin_metademands_tasks',
            'glpi_plugin_metademands_groupconfigs',
            'glpi_plugin_metademands_groups',
            'glpi_plugin_metademands_metademands_resources',
            'glpi_plugin_metademands_ticketfields',
        ];
        foreach ($tables as $table) {
            $DB->update(
                $table,
                ['entities_id' => $parm['entities_id']],
                [
                    'WHERE' => [
                        'plugin_metademands_metademands_id' => $parm['id'],
                    ],
                ]
            );
        }
    }
}

function plugin_metademands_item_purge($item)
{

    if ($item instanceof Ticket) {
        $temp = new Form();
        $temp->deleteByCriteria(['items_id' =>  $item->getID(), 'itemtype' => 'Ticket']);

        $temp = new Ticket_Task();
        $temp->deleteByCriteria(['tickets_id' =>  $item->getID()]);

        $temp = new Ticket_Task();
        $temp->deleteByCriteria(['parent_tickets_id' =>  $item->getID()]);

        $temp = new Interticketfollowup();
        $temp->deleteByCriteria(['tickets_id' =>  $item->getID()]);

        $temp = new Interticketfollowup();
        $temp->deleteByCriteria(['tickets_id' =>  $item->getID()]);

        $temp = new MetademandValidation();
        $temp->deleteByCriteria(['tickets_id' =>  $item->getID()]);

        $temp = new Ticket_Field();
        $temp->deleteByCriteria(['tickets_id' =>  $item->getID()]);

        $temp = new Ticket_Metademand();
        $temp->deleteByCriteria(['tickets_id' =>  $item->getID()]);

        $temp = new Ticket_Metademand();
        $temp->deleteByCriteria(['parent_tickets_id' =>  $item->getID()]);
    }
    return true;
}

// Define dropdown relations
/**
 * @return array|string[][]
 */
function plugin_metademands_getDatabaseRelations()
{

    if (Plugin::isPluginActive("metademands")) {
        return ["glpi_entities" => ["glpi_plugin_metademands_metademands"           => "entities_id",
            "glpi_plugin_metademands_fields"                => "entities_id",
            "glpi_plugin_metademands_metademands_resources" => "entities_id",
            "glpi_plugin_metademands_ticketfields"          => "entities_id",
            "glpi_plugin_metademands_tasks"                 => "entities_id"],

            "glpi_plugin_metademands_metademands" => ["glpi_plugin_metademands_fields"                => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_tickets_metademands"   => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_metademandtasks"       => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_ticketfields"          => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_tasks"                 => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_groups"                => "plugin_metademands_metademands_id",
                //                                                          "glpi_plugin_metademands_basketlines"           => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_metademandvalidations" => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_metademands_resources" => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_drafts"                => "plugin_metademands_metademands_id",
                "glpi_plugin_metademands_configsteps"           => "plugin_metademands_metademands_id"],

            "glpi_tickets"                   => [
                //                    "glpi_plugin_metademands_tickets_fields"        => "tickets_id",
                //                                                     "glpi_plugin_metademands_metademandvalidations" => "tickets_id",
                //                                                     "glpi_plugin_metademands_tickets_tasks"         => "tickets_id",
                //                                                     "glpi_plugin_metademands_tickets_tasks"         => "parent_tickets_id",
                //                                                     "glpi_plugin_metademands_tickets_metademands"   => "tickets_id",
                //                                                     "glpi_plugin_metademands_tickets_metademands"   => "parent_tickets_id",
                //                                                     "glpi_plugin_metademands_interticketfollowups"   => "tickets_id",
            ],
            "glpi_users"                     => ["glpi_plugin_metademands_basketlines"           => "users_id",
                "glpi_plugin_metademands_metademandvalidations" => "users_id",
                "glpi_plugin_metademands_tickettasks"           => "users_id_assign",
                "glpi_plugin_metademands_tickettasks"           => "users_id_requester",
                "glpi_plugin_metademands_tickettasks"           => "users_id_observer",
                "glpi_plugin_metademands_drafts"                => "users_id",
            ],
            "glpi_groups"                    => ["glpi_plugin_metademands_groups"      => "groups_id",
                "glpi_plugin_metademands_tickettasks" => "groups_id_assign",
                "glpi_plugin_metademands_tickettasks" => "groups_id_requester",
                "glpi_plugin_metademands_tickettasks" => "groups_id_observer",
            ],
            "glpi_itilcategories"            => ["glpi_plugin_metademands_metademands" => "itilcategories_id",
                "glpi_plugin_metademands_tickettasks" => "itilcategories_id",
            ],
            "glpi_plugin_metademands_fields" => ["glpi_plugin_metademands_tickets_fields" => "plugin_metademands_fields_id",
                "glpi_plugin_metademands_drafts_values"  => "plugin_metademands_fields_id",
                "glpi_plugin_metademands_basketlines"    => "plugin_metademands_fields_id",
                "glpi_plugin_metademands_fieldoptions"    => "plugin_metademands_fields_id"],

            "glpi_plugin_metademands_tasks" => ["glpi_plugin_metademands_fieldoptions"          => "plugin_metademands_tasks_id",
                "glpi_plugin_metademands_tickettasks"     => "plugin_metademands_tasks_id",
                "glpi_plugin_metademands_tickets_tasks"   => "plugin_metademands_tasks_id",
                "glpi_plugin_metademands_metademandtasks" => "plugin_metademands_tasks_id"],

            "glpi_plugin_metademands_drafts" => ["glpi_plugin_metademands_drafts_values" => "plugin_metademands_drafts_id"],
        ];
    } else {
        return [];
    }
}


// Define search option for types of the plugins
/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_metademands_getAddSearchOptions($itemtype)
{

    $sopt = [];
    if ($itemtype == "Ticket") {
        if (Session::haveRight("plugin_metademands", READ)) {
            $sopt[9499]['table']         = 'glpi_users';
            $sopt[9499]['field']         = 'name';
            $sopt[9499]['linkfield']     = 'users_id';
            $sopt[9499]['name']          = __("Metademand approver", 'metademands');
            $sopt[9499]['datatype']      = "itemlink";
            $sopt[9499]['forcegroupby']  = true;
            $sopt[9499]['joinparams']    = ['beforejoin' => [
                'table'      => 'glpi_plugin_metademands_metademandvalidations',
                'joinparams' => [
                    'jointype' => 'child',
                ],
            ]];
            $sopt[9499]['massiveaction'] = false;

            $sopt[9500]['table']         = 'glpi_plugin_metademands_tickets_metademands';
            $sopt[9500]['field']         = 'status';
            $sopt[9500]['name']          = __('Metademand status', 'metademands');
            $sopt[9500]['datatype']      = "specific";
            $sopt[9500]['searchtype']    = "equals";
            $sopt[9500]['joinparams']    = ['jointype' => 'child'];
            $sopt[9500]['massiveaction'] = false;

            $sopt[9501]['table']         = 'glpi_plugin_metademands_metademandvalidations';
            $sopt[9501]['field']         = 'validate';
            $sopt[9501]['name']          = MetademandValidation::getTypeName(1);
            $sopt[9501]['datatype']      = "specific";
            $sopt[9501]['searchtype']    = "equals";
            $sopt[9501]['joinparams']    = ['jointype' => 'child'];
            $sopt[9501]['massiveaction'] = false;

            $sopt[9502]['table']        = 'glpi_plugin_metademands_tickets_tasks';
            $sopt[9502]['field']        = 'id';
            $sopt[9502]['name']         = __("Group child ticket", 'metademands');
            $sopt[9502]['datatype']     = "specific";
            $sopt[9502]['searchtype']   = "equals";
            $sopt[9502]['forcegroupby'] = true;
            //         $sopt[9502]['linkfield']     = 'parent_tickets_id';
            $sopt[9502]['joinparams']    = ['jointype'  => 'child',
                'linkfield' => 'parent_tickets_id'];
            $sopt[9502]['massiveaction'] = false;

            $sopt[9503]['table']      = 'glpi_plugin_metademands_tickets_tasks';
            $sopt[9503]['field']      = 'tickets_id';
            $sopt[9503]['name']       = __('Link to metademands', 'metademands');
            $sopt[9503]['datatype']   = "specific";
            $sopt[9503]['searchtype'] = "";
            //         $sopt[9503]['forcegroupby']    = true;
            //         $sopt[9502]['linkfield']     = 'parent_tickets_id';
            $sopt[9503]['joinparams'] = ['jointype'  => 'child',
                'linkfield' => 'parent_tickets_id'];
            //         $sopt[9503]['joinparams']    = ['jointype'  => 'child'];
            $sopt[9503]['massiveaction'] = false;

            $sopt[9504]['table']        = 'glpi_plugin_metademands_tickets_tasks';
            $sopt[9504]['field']        = 'plugin_metademands_tasks_id';
            $sopt[9504]['name']         = __("Technician child ticket", 'metademands');
            $sopt[9504]['datatype']     = "specific";
            $sopt[9504]['searchtype']   = "equals";
            $sopt[9504]['forcegroupby'] = true;
            //        $sopt[9502]['linkfield']     = 'parent_tickets_id';
            $sopt[9504]['joinparams']    = ['jointype'  => 'child',
                'linkfield' => 'parent_tickets_id'];
            $sopt[9504]['massiveaction'] = false;
        }
    }
    return $sopt;
}


/**
 * @param $link
 * @param $nott
 * @param $type
 * @param $ID
 * @param $val
 * @param $searchtype
 *
 * @return string
 */
function plugin_metademands_addWhere($link, $nott, $type, $ID, $val, $searchtype)
{

    $searchopt = Search::getOptions($type);
    $table     = $searchopt[$ID]["table"];
    $field     = $searchopt[$ID]["field"];

    switch ($table . "." . $field) {
        case "glpi_plugin_metademands_tickets_metademands.status":
            if (is_numeric($val)) {
                return $link . " `glpi_plugin_metademands_tickets_metademands`.`status` = '$val'";
            }
            break;

        case "glpi_plugin_metademands_metademandvalidations.validate":
            $AND = "";
            if ($val == MetademandValidation::TO_VALIDATE
                || $val == MetademandValidation::TO_VALIDATE_WITHOUTTASK) {
                $AND = "AND glpi_tickets.status IN ( " . implode(",", Ticket::getNotSolvedStatusArray()) . ")";
            }
            if (is_numeric($val)) {
                return $link . " `glpi_plugin_metademands_metademandvalidations`.`validate` >= -1
                        AND `glpi_plugin_metademands_metademandvalidations`.`validate` = '$val' $AND";
            }

            break;

        case "glpi_plugin_metademands_tickets_tasks.id":
            switch ($searchtype) {
                case 'equals':
                    if ($val === '0') {
                        return " ";
                    }
                    if ($val == 'mygroups') {
                        return " $link (`glpi_groups_metademands`.`id` IN ('" . implode(
                            "','",
                            $_SESSION['glpigroups']
                        ) . "')) ";
                    } else {
                        return " $link (`glpi_groups_metademands`.`id` IN ('" . $val . "')) ";
                    }
                    break;
                case 'notequals':
                    return " $link (`glpi_groups_metademands`.`id` NOT IN ('" . implode(
                        "','",
                        $_SESSION['glpigroups']
                    ) . "')) ";
                    break;
                case 'contains':
                    return " ";
                    break;
            }
            break;

        case "glpi_plugin_metademands_tickets_tasks.plugin_metademands_tasks_id":
            switch ($searchtype) {
                case 'equals':
                    if ($val === '0') {
                        return " ";
                    }
                    return " $link (`glpi_users_metademands`.`id` IN ('" . $val . "')) ";
                    break;
                case 'notequals':
                    return " $link (`glpi_users_metademands`.`id` NOT IN ('" . $val . "')) ";
                    break;
                case 'contains':
                    return " ";
                    break;
            }

            break;
        case "glpi_plugin_metademands_tickets_tasks.tickets_id":
            return " ";
            break;
    }
    return "";
}

/**
 * @param $type
 * @param $ref_table
 * @param $new_table
 * @param $linkfield
 * @param $already_link_tables
 *
 * @return Left|string
 */
function plugin_metademands_addLeftJoin($type, $ref_table, $new_table, $linkfield, &$already_link_tables)
{

    // Rename table for meta left join
    $AS = "";
    // Multiple link possibilies case
    if ($new_table == "glpi_plugin_metademands_tickets_tasks") {
        $AS = " AS " . $new_table;
    }

    switch ($new_table) {
        //
        case "glpi_plugin_metademands_tickets_tasks":

            $out['LEFT JOIN'] = [
                'glpi_plugin_metademands_tickets_tasks'.$AS => [
                    'ON' => [
                        $ref_table   => 'id',
                        'glpi_plugin_metademands_tickets_tasks'                  => 'parent_tickets_id'
                    ],
                ],
                'glpi_groups_tickets AS glpi_groups_tickets_metademands' => [
                    'ON' => [
                        $new_table   => 'tickets_id',
                        'glpi_groups_tickets_metademands'                  => 'tickets_id', [
                            'AND' => [
                                'glpi_groups_tickets_metademands.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
                'glpi_groups AS glpi_groups_metademands' => [
                    'ON' => [
                        'glpi_groups_tickets_metademands'   => 'groups_id',
                        'glpi_groups_metademands'                  => 'id'
                    ],
                ],
                'glpi_tickets AS glpi_tickets_metademands' => [
                    'ON' => [
                        $new_table   => 'tickets_id',
                        'glpi_tickets_metademands'                  => 'id', [
                            'AND' => [
                                'glpi_tickets_metademands.is_deleted' => 0,
                            ],
                        ],
                    ],
                ],
                'glpi_tickets_users AS glpi_users_tickets_metademands' => [
                    'ON' => [
                        $new_table   => 'tickets_id',
                        'glpi_users_tickets_metademands'                  => 'tickets_id', [
                            'AND' => [
                                'glpi_users_tickets_metademands.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
                'glpi_users AS glpi_users_metademands' => [
                    'ON' => [
                        'glpi_users_tickets_metademands'   => 'users_id',
                        'glpi_users_metademands'                  => 'id'
                    ],
                ],
            ];
            return $out;
//
//            return "LEFT JOIN `glpi_plugin_metademands_tickets_tasks` $AS ON (`$ref_table`.`id` = `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` )
//          LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_metademands ON (`$new_table`.`tickets_id` = `glpi_groups_tickets_metademands`.`tickets_id`
//          AND `glpi_groups_tickets_metademands`.`type` = " . CommonITILActor::ASSIGN . " )
//          LEFT JOIN `glpi_groups` AS glpi_groups_metademands ON (`glpi_groups_tickets_metademands`.`groups_id` = `glpi_groups_metademands`.`id` )
//          LEFT JOIN `glpi_tickets` AS glpi_tickets_metademands ON (`$new_table`.`tickets_id` = `glpi_tickets_metademands`.`id`
//          AND `glpi_tickets_metademands`.`is_deleted` = 0)
//          LEFT JOIN `glpi_tickets_users` AS glpi_users_tickets_metademands ON (`$new_table`.`tickets_id` = `glpi_users_tickets_metademands`.`tickets_id`
//          AND `glpi_users_tickets_metademands`.`type` = " . CommonITILActor::ASSIGN . " )
//          LEFT JOIN `glpi_users` AS glpi_users_metademands ON (`glpi_users_tickets_metademands`.`users_id` = `glpi_users_metademands`.`id` )";
//            break;
    }
    return "";
}

/**
 * @param $type
 * @param $ID
 * @param $num
 *
 * @return string
 */
function plugin_metademands_addSelect($type, $ID, $num)
{
    global $DB;
    $searchopt = Search::getOptions($type);
    $table     = $searchopt[$ID]["table"];
    $field     = $searchopt[$ID]["field"];

    if ($table == "glpi_plugin_metademands_tickets_tasks"
        && $type == "Ticket") {
        if ($ID == 9504) {

            //Prepare 11.0.1
            $concat = QueryFunction::groupConcat(
                expression: QueryFunction::concat([
                    QueryFunction::ifnull('glpi_users_metademands.id', new QueryExpression($DB::quoteValue(Search::NULLVALUE))),
                ]),
                separator: Search::LONGSEP,
                distinct: true,
                order_by: 'glpi_users_metademands.id',
                alias: "ITEM_{$num}"
            );
//            return $concat;

            return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`glpi_users_metademands`.`id`, '__NULL__')) ORDER BY `glpi_users_metademands`.`id` SEPARATOR '$$##$$') AS `ITEM_$num` ";

        }
        //Prepare 11.0.1
        $concat = QueryFunction::groupConcat(
            expression: QueryFunction::concat([
                QueryFunction::ifnull('glpi_groups_metademands.completename', new QueryExpression($DB::quoteValue(Search::NULLVALUE))),
            ]),
            separator: Search::LONGSEP,
            distinct: true,
            order_by: 'glpi_groups_metademands.completename',
            alias: "ITEM_{$num}"
        );

//        return $concat;
        return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`glpi_groups_metademands`.`completename`, '__NULL__')) ORDER BY `glpi_groups_metademands`.`completename` SEPARATOR '$$##$$') AS `ITEM_$num` ";

    } else {
        return "";
    }
}

/**
 * @param        $type
 * @param        $field
 * @param        $data
 * @param        $num
 * @param string $linkfield
 *
 * @return string
 * @throws GlpitestSQLError
 */
function plugin_metademands_giveItem($type, $field, $data, $num, $linkfield = "")
{
    global $CFG_GLPI;
    switch ($field) {
        case 9499:
            $out = getUserName($data['raw']["ITEM_" . $num], 0, true);
            return $out;
        case 9500:
            $out = Ticket_Metademand::getStatusName($data['raw']["ITEM_" . $num]);
            return $out;
        case 9501:
            if ($data['raw']["ITEM_" . $num] > -1) {
                $style = "style='background-color: " . MetademandValidation::getStatusColor($data['raw']["ITEM_" . $num]) . ";'";
                $out   = "<div class='center' $style>";
                $out   .= MetademandValidation::getStatusName($data['raw']["ITEM_" . $num]);
                $out   .= "</div>";
            } else {
                $out = "";
            }
            return $out;
            //      case 9502 :
            //         $out   = Ticket_Metademand::getStatusName($data['raw']["ITEM_" . $num]);
            //         return $out;
            //         break;
        case 9503:
            $out                                  = $data['id'];
            $options['criteria'][0]['field']      = 50; // metademand status
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $data['id'];
            $options['criteria'][0]['link']       = 'AND';

            $options['criteria'][1]['field']      = 8; // groups_id
            $options['criteria'][1]['searchtype'] = 'equals';
            $options['criteria'][1]['value']      = 'mygroups';
            $options['criteria'][1]['link']       = 'AND';

            $options['criteria'][2]['field']      = 12; // status
            $options['criteria'][2]['searchtype'] = 'equals';
            $options['criteria'][2]['value']      = 'notold';
            $options['criteria'][2]['link']       = 'AND';


            $metademands = new Ticket_Metademand();

            if ($metademands->getFromDBByCrit(['tickets_id' => $data['id']])) {
                $DB                               = DBConnection::getReadConnection();
                $query
                    = [
                    'SELECT' => ['COUNT' => 'glpi_plugin_metademands_tickets_metademands.id AS total_running'],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_plugin_metademands_tickets_metademands' => [
                            'ON' => [
                                'glpi_tickets' => 'id',
                                'glpi_plugin_metademands_tickets_metademands'          => 'tickets_id'
                            ]
                        ],
                        'glpi_plugin_metademands_tickets_tasks' => [
                            'ON' => [
                                'glpi_tickets' => 'id',
                                'glpi_plugin_metademands_tickets_tasks'          => 'parent_tickets_id'
                            ]
                        ],
                        'glpi_groups_tickets AS glpi_groups_tickets_metademands' => [
                            'ON' => [
                                'glpi_plugin_metademands_tickets_tasks' => 'tickets_id',
                                'glpi_groups_tickets_metademands'          => 'tickets_id'
                            ]
                        ],
                        'glpi_groups AS glpi_groups_metademands' => [
                            'ON' => [
                                'glpi_groups_tickets_metademands' => 'groups_id',
                                'glpi_groups_metademands'          => 'id'
                            ]
                        ]
                    ],
                    'WHERE' => [
                        'glpi_tickets.is_deleted' => 0,
                        'glpi_plugin_metademands_tickets_metademands.status' => Ticket_Metademand::RUNNING,
                        'glpi_groups_metademands.id' => $_SESSION['glpigroups'],
                        'glpi_tickets.id' => $data['id'],
                    ],
                ];
                $query['WHERE'] = $query['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                $total_running_parents_meta = $DB->request($query);

                $total_running = 0;
                foreach ($total_running_parents_meta as $row) {
                    $total_running = $row['total_running'];
                }
                if ($total_running > 0) {
                    $out = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                           . Toolbox::append_params($options, '&amp;') . "\"><i class='center style=\"font-size:2em;\" ti ti-share'></i></a>";
                    return $out;
                } else {
                    return " ";
                }
            }
            return " ";
        case 9504:
            $result = "";
            if (isset($data["Ticket_9504"]) && !is_null($data["Ticket_9504"])) {
                if (isset($data["Ticket_9504"]["count"])) {
                    $count = $data["Ticket_9504"]["count"];
                    $i     = 0;
                    for ($i; $i < $count; $i++) {
                        if ($i != 0) {
                            $result .= "\n";
                        }
                        $result .= getUserName($data["Ticket_9504"][$i]["name"], 0, true);
                    }
                }
            }
            return $result;
    }

    return "";
}


function plugin_metademands_hook_dashboard_cards($cards)
{
    if ($cards === null) {
        $cards = [];
    }


    $cards["count_running_metademands"] = [
        'widgettype' => ['bigNumber'],
        'itemtype'   => Metademand::getType(),
        'group'      => __('Assistance'),
        'label'      => __("Running metademands", "metademands"),
        'provider'   => "GlpiPlugin\Metademands\Metademand::getRunningMetademands",
        'cache'      => false,
        'args'       => [
            'params' => [
            ],
        ],
        'filters'    => [
            'dates', 'dates_mod', 'itilcategory',
            'group_tech', 'user_tech', 'requesttype', 'location',
        ],
    ];


    $cards["count_metademands_to_be_closed"] = [
        'widgettype' => ['bigNumber'],
        'itemtype'   => Metademand::getType(),
        'group'      => __('Assistance'),
        'label'      => __("Metademands to be closed", "metademands"),
        'provider'   => "GlpiPlugin\Metademands\Metademand::getMetademandsToBeClosed",
        'cache'      => false,
        'args'       => [
            'params' => [
            ],
        ],
        'filters'    => [
            'dates', 'dates_mod', 'itilcategory',
            'group_tech', 'user_tech', 'requesttype', 'location',
        ],
    ];

    $cards["count_metademands_need_validation"] = [
        'widgettype' => ['bigNumber'],
        'itemtype'   => Metademand::getType(),
        'group'      => __('Assistance'),
        'label'      => __("Metademands to be validated", "metademands"),
        'provider'   => "GlpiPlugin\Metademands\Metademand::getMetademandsToBeValidated",
        'cache'      => false,
        'args'       => [
            'params' => [
            ],
        ],
        'filters'    => [
            'dates', 'dates_mod', 'itilcategory',
            'group_tech', 'user_tech', 'requesttype', 'location',
        ],
    ];

    $cards["count_running_metademands_my_group_children"] = [
        'widgettype' => ['bigNumber'],
        'itemtype'   => Metademand::getType(),
        'group'      => __('Assistance'),
        'label'      => __("Running metademands with tickets of my groups", "metademands"),
        'provider'   => "GlpiPlugin\Metademands\Metademand::getRunningMetademandsAndMygroups",
        'cache'      => false,
        'args'       => [
            'params' => [
            ],
        ],
        'filters'    => [
            'dates', 'dates_mod', 'itilcategory',
            'group_tech', 'user_tech', 'requesttype', 'location',
        ],
    ];

    return $cards;
}

function plugin_datainjection_populate_basketobjects()
{
    global $INJECTABLE_TYPES;
    $INJECTABLE_TYPES[BasketobjectInjection::class] = 'metademands';
}

function plugin_metademands_getDropdown()
{
    if (Plugin::isPluginActive("metademands")) {
        return [
            Basketobjecttype::class  => Basketobjecttype::getTypeName(2),
        ];
    } else {
        return [];
    }
}

function plugin_metademands_addDefaultWhere($itemtype)
{

    switch ($itemtype) {
        case Draft::class:
            $currentUser = Session::getLoginUserID();
            return "users_id = $currentUser";
    }
}
