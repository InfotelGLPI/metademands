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

include('../../../inc/includes.php');
Session::checkLoginUser();

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$field = new PluginMetademandsField();

if (isset($_POST['type']) && $_POST['type'] == 'dropdown_object'
    && isset($_POST['item']) && ($_POST['item'] == 'Group'
       || $_POST['item'] == 'User')) {
   if (isset($_POST['is_assign'])) {
      $custom_values['is_assign'] = $_POST['is_assign'];
   }
   if (isset($_POST['is_watcher'])) {
      $custom_values['is_watcher'] = $_POST['is_watcher'];
   }
   if (isset($_POST['is_requester'])) {
      $custom_values['is_requester'] = $_POST['is_requester'];
   }
    if (isset($_POST['user_group'])) {
        $custom_values['user_group'] = $_POST['user_group'];
    }
}

if (isset($_POST['item']) && isset($_POST['type'])
    && (empty($_POST['item']) || $_POST['item'] === 0)) {
   $_POST['item'] = $_POST['type'];
}

if (isset($custom_values)) {
    $_POST['custom_values'] = $custom_values;
}

if (isset($_POST["add"])) {
   if (isset($_POST["custom_values"]) && is_array($_POST["custom_values"])) {
      if (isset($_POST['type']) && $_POST['type'] == 'dropdown_multiple') {
         $_POST['item'] = 'other';
      }
      $_POST["custom_values"] = PluginMetademandsField::_serialize($_POST["custom_values"]);

      if (isset($_POST["comment_values"])) {
         $_POST["comment_values"] = PluginMetademandsField::_serialize($_POST["comment_values"]);
      }
      if (isset($_POST["default_values"])) {
         $_POST["default_values"] = PluginMetademandsField::_serialize($_POST["default_values"]);
      }
      if (isset($_POST["informations_to_display"])) {
         $_POST["informations_to_display"] = PluginMetademandsField::_serialize($_POST["informations_to_display"]);
      }
   }
    if ((!isset($_POST["custom_values"]) || empty($_POST["custom_values"])) && $_POST['type'] == 'yesno') {
        $_POST["custom_values"] = 1;
    }

   // Check update rights for fields
   $field->check(-1, UPDATE, $_POST);

   if ($_POST['id'] = $field->add($_POST)) {
      $field->recalculateOrder($_POST);
      PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_ADD);
      unset($_SESSION['glpi_plugin_metademands_fields']);
   }

   Html::back();

} else if (isset($_POST["update"])) {

   if ($_POST["type"] == 'checkbox'
       || $_POST["type"] == 'radio') {
      $_POST["item"] = 0;
   }

   if ((isset($_POST["custom_values"]) && is_array($_POST["custom_values"]) ||
       isset($_POST["default_values"]) && is_array($_POST["default_values"]))
       && ((isset($_POST['item']) && $_POST["item"] == 'other')
           || $_POST["type"] == 'checkbox'
           || $_POST["type"] == 'radio'
           || $_POST["type"] == 'dropdown_multiple'
           || (isset($_POST['item']) && ($_POST['item'] == 'Group'
                                         || $_POST['item'] == 'User'))
           || $_POST['type'] == 'number'
           || $_POST['type'] == 'basket')) {
      $comment_values = "";
      $custom_values  = [];
      $default_values = [];

      if (isset($_POST['custom_values'])) {
         $custom_values = $_POST['custom_values'];
      }
      if (isset($_POST['comment_values'])) {
         $comment_values = $_POST['comment_values'];
      }
      if (isset($_POST['default_values'])) {
         $default_values = $_POST['default_values'];
      }

      $_POST["custom_values"]  = PluginMetademandsField::_serialize($custom_values);
      $_POST["comment_values"] = PluginMetademandsField::_serialize($comment_values);
      $_POST["default_values"] = PluginMetademandsField::_serialize($default_values);

   } else if ($_POST["type"] == 'link') {
      $_POST["custom_values"]  = PluginMetademandsField::_serialize($_POST['custom_values']);
      $_POST["comment_values"] = '';
   } else if ($_POST["type"] != 'yesno') {
       //used for default_values don't uncomment
//      $_POST["custom_values"]  = '';
      $_POST["comment_values"] = '';
   }
   if (isset($_POST["value"]) && is_array($_POST["value"])) {
      $_POST["value"] = PluginMetademandsField::_serialize($_POST["value"]);
   }

   $informations_to_display = [];
   if (isset($_POST['informations_to_display'])) {
      $informations_to_display = $_POST['informations_to_display'];
   }
   $_POST["informations_to_display"] = PluginMetademandsField::_serialize($informations_to_display);

   if (!isset($_POST['item'])) {
      $_POST['item'] = "";
   }
    if (isset($_POST['type']) && $_POST['type'] == 'dropdown_multiple'
        && isset($_POST['item']) && $_POST['item'] == 'User') {
        if (isset($_POST['user_group'])) {
            $custom_values['user_group'] = $_POST['user_group'];
            $_POST["custom_values"]  = PluginMetademandsField::_serialize($custom_values);
        }
    }

   //    Check update rights for fields
   $field->check(-1, UPDATE, $_POST);


   if ($_POST['type'] == 'yesno') {
       unset($_POST['default_values']);
   }


   if ($field->update($_POST)) {
      $field->recalculateOrder($_POST);
      PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_UPDATE);

      //Hook to add and update values add from plugins
      if (isset($PLUGIN_HOOKS['metademands'])) {
         foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
            $p       = $_POST;
            $new_res = PluginMetademandsField::getPluginSaveOptions($plug, $p);
         }
      }
   }

    Html::back();

} else if (isset($_POST["purge"])) {

   // Check update rights for fields
   $field->check(-1, UPDATE, $_POST);
   $field->delete($_POST, 1);
   PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_DELETE);
   $field->redirectToList();

} else if (isset($_POST["delete_custom_value"])) {
   if (isset($_POST["custom_values"]) && is_array($_POST["custom_values"])) {
      foreach ($_POST["custom_values"] as $key => $value) {
         if ($key == key($_POST["delete_custom_value"])) {
            unset($_POST["custom_values"][$key]);
            unset($_POST["comment_values"][$key]);
            unset($_POST["default_values"][$key]);
         }
      }
      $_POST["custom_values"]  = PluginMetademandsField::_serialize($_POST["custom_values"]);
      $_POST["comment_values"] = PluginMetademandsField::_serialize($_POST["comment_values"]);
      $_POST["default_values"] = PluginMetademandsField::_serialize($_POST["default_values"]);
      // Check update rights for fields
      $field->check(-1, UPDATE, $_POST);
      $field->update($_POST);
   }

    $field->redirectToList();

} else if (isset($_POST["delete_field_custom_values"])) {

   $crit = [
      'id' => $_POST['plugin_metademands_fields_id'],
   ];
   $field->getFromDBByCrit($crit);

   $custom_values = PluginMetademandsField::_unserialize($field->fields["custom_values"]);

   unset($custom_values[$_POST['id']]);


   $keys = range(1, count($custom_values));
   $values = array_values($custom_values);
   if (count($keys) == count($values)) {
       $custom_values = array_combine($keys, $values);
       $field->update([
           'id'            => $_POST['plugin_metademands_fields_id'],
           'custom_values' => PluginMetademandsField::_serialize($custom_values)
       ]);
   }

    $default_values = PluginMetademandsField::_unserialize($field->fields["default_values"]);

    unset($default_values[$_POST['id']]);

    $keys = range(1, count($default_values));
    $values = array_values($default_values);

    if (count($keys) == count($values)) {
        $default_values = array_combine($keys, $values);
        $field->update([
            'id' => $_POST['plugin_metademands_fields_id'],
            'default_values' => PluginMetademandsField::_serialize($default_values)
        ]);
    }
    $comment_values = PluginMetademandsField::_unserialize($field->fields["comment_values"]);

    unset($comment_values[$_POST['id']]);

    $keys = range(1, count($comment_values));
    $values = array_values($comment_values);

    if (count($keys) == count($values)) {
        $comment_values = array_combine($keys, $values);
        $field->update([
            'id' => $_POST['plugin_metademands_fields_id'],
            'comment_values' => PluginMetademandsField::_serialize($comment_values)
        ]);
    }


    Html::back();

} else {
   $field->checkGlobal(READ);
   Html::header(PluginMetademandsField::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");
   Html::requireJs('tinymce');
   $field->display(['id' => $_GET["id"]]);
   Html::footer();
}
