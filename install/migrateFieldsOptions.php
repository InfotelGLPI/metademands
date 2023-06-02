<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2019 by the Metademands Development Team.
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

/**
 * Update from 2.6.4 to 2.7.1
 * Glpi upgrade to 9.5
 * @return bool for success (will die for most error)
 * */

ini_set("memory_limit", "-1");
ini_set("max_execution_time", 0);
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('../../..'));
}

include_once(GLPI_ROOT . "/inc/autoload.function.php");
include_once(GLPI_ROOT . "/inc/db.function.php");
include_once(GLPI_ROOT . "/inc/based_config.php");
include_once(GLPI_CONFIG_DIR . "/config_db.php");
include_once(GLPI_ROOT . "/inc/define.php");

$GLPI = new GLPI();
$GLPI->initLogger();
Config::detectRootDoc();

if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
} else {
   die("Can't write in " . GLPI_SESSION_DIR . "\r\n");
}
Session::start();
$_SESSION['glpi_use_mode'] = 0;
Session::loadLanguage();

global $DB;
if (!$DB->connected) {
   die("No DB connection\r\n");
}
$CFG_GLPI['notifications_ajax']    = 0;
$CFG_GLPI['notifications_mailing'] = 0;
$CFG_GLPI['use_notifications']     = 0;

function migrateFieldsOptions()
{
    $input = [];
    $field = new PluginMetademandsField();
    $fields = $field->find();
    foreach ($fields as $f) {

        if ($f["plugin_metademands_metademands_id"] > 0) {

            $fieldopt = new PluginMetademandsFieldOption();
            $input["plugin_metademands_fields_id"] = $f["id"];
            $input["parent_field_id"] = $f['parent_field_id'];

            $check_values = PluginMetademandsField::_unserialize($f['check_value']);
            if (is_array($check_values)) {
                foreach ($check_values as $k => $check_value) {

                    if (($f["type"] == 'date' ||
                            $f["type"] == 'datetime' ||
                            $f["type"] == 'date_interval' ||
                            $f["type"] == 'datetime_interval'
                        ) && $check_value == 1) {
                        $field->update(["id" => $f["id"], "use_future_date" => 1]);
                    }
                    $input["check_value"] = $check_value;

                    $plugin_metademands_tasks_id = PluginMetademandsField::_unserialize($f['plugin_metademands_tasks_id']);

                    if (is_array($plugin_metademands_tasks_id)) {
                        if (isset($plugin_metademands_tasks_id[$check_value])) {
                            $input["plugin_metademands_tasks_id"] = $plugin_metademands_tasks_id[$check_value];
                        } else {
                            if (isset($plugin_metademands_tasks_id[0])) {
                                $input["plugin_metademands_tasks_id"] = $plugin_metademands_tasks_id[0];
                            } else {
                                $input["plugin_metademands_tasks_id"] = 0;
                            }
                        }
                    } else {
                        $input["plugin_metademands_tasks_id"] = $plugin_metademands_tasks_id;
                    }

                    $users_id_validate = PluginMetademandsField::_unserialize($f['users_id_validate']);
                    if (is_array($users_id_validate)) {
                        if (isset($users_id_validate[$check_value])) {
                            $input["users_id_validate"] = $users_id_validate[$check_value];
                        } else {
                            if (isset($users_id_validate[0])) {
                                $input["users_id_validate"] = $users_id_validate[0];
                            } else {
                                $input["users_id_validate"] = 0;
                            }
                        }
                    } else {
                        $input["users_id_validate"] = $users_id_validate;
                    }

                    $fields_link = PluginMetademandsField::_unserialize($f['fields_link']);
                    if (is_array($fields_link)) {
                        if (isset($fields_link[$check_value])) {
                            $input["fields_link"] = $fields_link[$check_value];
                        } else {
                            if (isset($fields_link[0])) {
                                $input["fields_link"] = $fields_link[0];
                            } else {
                                $input["fields_link"] = 0;
                            }
                        }
                    } else {
                        $input["fields_link"] = $fields_link;
                    }

                    $hidden_link = PluginMetademandsField::_unserialize($f['hidden_link']);
                    if (is_array($hidden_link)) {
                        if (isset($hidden_link[$check_value])) {
                            $input["hidden_link"] = $hidden_link[$check_value];
                        } else {
                            if (isset($hidden_link[0])) {
                                $input["hidden_link"] = $hidden_link[0];
                            } else {
                                $input["hidden_link"] = 0;
                            }
                        }
                    } else {
                        $input["hidden_link"] = $hidden_link;
                    }

                    $hidden_block = PluginMetademandsField::_unserialize($f['hidden_block']);
                    if (is_array($hidden_block)) {
                        if (isset($hidden_block[$check_value])) {
                            $input["hidden_block"] = $hidden_block[$check_value];
                        } else {
                            if (isset($hidden_block[0])) {
                                $input["hidden_block"] = $hidden_block[0];
                            } else {
                                $input["hidden_block"] = 0;
                            }
                        }
                    } else {
                        $input["hidden_block"] = $hidden_block;
                    }


                    $childs_blocks = json_decode($f['childs_blocks'], true);

                    if (is_array($childs_blocks)) {
                        if (isset($childs_blocks[$check_value])) {
                            $input["childs_blocks"] = json_encode($childs_blocks[$check_value]);
                        } else {
                            $input["childs_blocks"] = json_encode([]);
                        }
                    } else {
                        $input["childs_blocks"] = json_encode($childs_blocks);
                    }


                    $checkbox_value = PluginMetademandsField::_unserialize($f['checkbox_value']);
                    if (is_array($checkbox_value)) {
                        if (isset($checkbox_value[$check_value])) {
                            $input["checkbox_value"] = $checkbox_value[$check_value];
                        } else {
                            if (isset($checkbox_value[0])) {
                                $input["checkbox_value"] = $checkbox_value[0];
                            } else {
                                $input["checkbox_value"] = 0;
                            }
                        }
                    } else {
                        $input["checkbox_value"] = $checkbox_value;
                    }

                    $checkbox_id = PluginMetademandsField::_unserialize($f['checkbox_id']);
                    if (is_array($checkbox_id)) {
                        if (isset($checkbox_id[$check_value])) {
                            $input["checkbox_id"] = $checkbox_id[$check_value];
                        } else {
                            if (isset($checkbox_id[0])) {
                                $input["checkbox_id"] = $checkbox_id[0];
                            } else {
                                $input["checkbox_id"] = 0;
                            }
                        }
                    } else {
                        $input["checkbox_id"] = $checkbox_id;
                    }

                    $fieldopt->add($input);
                }

            } else {
                $input["check_value"] = $check_value = $f["check_value"];

                $plugin_metademands_tasks_id = PluginMetademandsField::_unserialize($f['plugin_metademands_tasks_id']);

                if (is_array($plugin_metademands_tasks_id)) {
                    if (isset($plugin_metademands_tasks_id[$check_value])) {
                        $input["plugin_metademands_tasks_id"] = $plugin_metademands_tasks_id[$check_value];
                    } else {
                        if (isset($plugin_metademands_tasks_id[0])) {
                            $input["plugin_metademands_tasks_id"] = $plugin_metademands_tasks_id[0];
                        } else {
                            $input["plugin_metademands_tasks_id"] = 0;
                        }
                    }
                } else {
                    $input["plugin_metademands_tasks_id"] = $plugin_metademands_tasks_id;
                }

                $users_id_validate = PluginMetademandsField::_unserialize($f['users_id_validate']);
                if (is_array($users_id_validate)) {
                    if (isset($users_id_validate[$check_value])) {
                        $input["users_id_validate"] = $users_id_validate[$check_value];
                    } else {
                        if (isset($users_id_validate[0])) {
                            $input["users_id_validate"] = $users_id_validate[0];
                        } else {
                            $input["users_id_validate"] = 0;
                        }
                    }
                } else {
                    $input["users_id_validate"] = $users_id_validate;
                }

                $fields_link = PluginMetademandsField::_unserialize($f['fields_link']);
                if (is_array($fields_link)) {
                    if (isset($fields_link[$check_value])) {
                        $input["fields_link"] = $fields_link[$check_value];
                    } else {
                        if (isset($fields_link[0])) {
                            $input["fields_link"] = $fields_link[0];
                        } else {
                            $input["fields_link"] = 0;
                        }
                    }
                } else {
                    $input["fields_link"] = $fields_link;
                }

                $hidden_link = PluginMetademandsField::_unserialize($f['hidden_link']);
                if (is_array($hidden_link)) {
                    if (isset($hidden_link[$check_value])) {
                        $input["hidden_link"] = $hidden_link[$check_value];
                    } else {
                        if (isset($hidden_link[0])) {
                            $input["hidden_link"] = $hidden_link[0];
                        } else {
                            $input["hidden_link"] = 0;
                        }
                    }
                } else {
                    $input["hidden_link"] = $hidden_link;
                }

                $hidden_block = PluginMetademandsField::_unserialize($f['hidden_block']);
                if (is_array($hidden_block)) {
                    if (isset($hidden_block[$check_value])) {
                        $input["hidden_block"] = $hidden_block[$check_value];
                    } else {
                        if (isset($hidden_block[0])) {
                            $input["hidden_block"] = $hidden_block[0];
                        } else {
                            $input["hidden_block"] = 0;
                        }
                    }
                } else {
                    $input["hidden_block"] = $hidden_block;
                }


                $childs_blocks = json_decode($f['childs_blocks'], true);

                if (is_array($childs_blocks)) {
                    if (isset($childs_blocks[$check_value])) {
                        $input["childs_blocks"] = json_encode($childs_blocks[$check_value]);
                    } else {
                        $input["childs_blocks"] = json_encode([]);
                    }
                } else {
                    $input["childs_blocks"] = json_encode($childs_blocks);
                }


                $checkbox_value = PluginMetademandsField::_unserialize($f['checkbox_value']);
                if (is_array($checkbox_value)) {
                    if (isset($checkbox_value[$check_value])) {
                        $input["checkbox_value"] = $checkbox_value[$check_value];
                    } else {
                        if (isset($checkbox_value[0])) {
                            $input["checkbox_value"] = $checkbox_value[0];
                        } else {
                            $input["checkbox_value"] = 0;
                        }
                    }
                } else {
                    $input["checkbox_value"] = $checkbox_value;
                }

                $checkbox_id = PluginMetademandsField::_unserialize($f['checkbox_id']);
                if (is_array($checkbox_id)) {
                    if (isset($checkbox_id[$check_value])) {
                        $input["checkbox_id"] = $checkbox_id[$check_value];
                    } else {
                        if (isset($checkbox_id[0])) {
                            $input["checkbox_id"] = $checkbox_id[0];
                        } else {
                            $input["checkbox_id"] = 0;
                        }
                    }
                } else {
                    $input["checkbox_id"] = $checkbox_id;
                }

                $fieldopt->add($input);
            }
        }
    }
}