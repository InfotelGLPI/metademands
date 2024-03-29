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
$field->getFromDB($_POST['plugin_metademands_fields_id']);

$fieldparameter = new PluginMetademandsFieldParameter();

if (isset($field->fields['type']) && $field->fields['type'] == 'dropdown_object'
    && isset($field->fields['item']) && ($field->fields['item'] == 'Group'
       || $field->fields['item'] == 'User')) {
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

if (isset($field->fields['item']) && isset($field->fields['type'])
    && (empty($field->fields['item']) || $field->fields['item'] === 0)) {
   $field->fields['item'] = $field->fields['type'];
}

if (isset($custom_values)) {
    $_POST['custom_values'] = $custom_values;
}

if (isset($_POST["add"])) {
   if (isset($_POST["custom_values"]) && is_array($_POST["custom_values"])) {
      if (isset($field->fields['type']) && $field->fields['type'] == 'dropdown_multiple') {
         $field->fields['item'] = 'other';
      }
      $_POST["custom_values"] = PluginMetademandsFieldParameter::_serialize($_POST["custom_values"]);

      if (isset($_POST["comment_values"])) {
         $_POST["comment_values"] = PluginMetademandsFieldParameter::_serialize($_POST["comment_values"]);
      }
      if (isset($_POST["default_values"])) {
         $_POST["default_values"] = PluginMetademandsFieldParameter::_serialize($_POST["default_values"]);
      }
      if (isset($_POST["informations_to_display"])) {
         $_POST["informations_to_display"] = PluginMetademandsFieldParameter::_serialize($_POST["informations_to_display"]);
      }
   }
    if ((!isset($_POST["custom_values"]) || empty($_POST["custom_values"])) && $field->fields['type'] == 'yesno') {
        $_POST["custom_values"] = 1;
    }

   // Check update rights for fields
    $fieldparameter->check(-1, CREATE, $_POST);
    $fieldparameter->add($_POST);

   Html::back();

} else if (isset($_POST["update"])) {

   if ((isset($_POST["custom_values"]) && is_array($_POST["custom_values"]) ||
       isset($_POST["default_values"]) && is_array($_POST["default_values"]))
       && ((isset($field->fields['item']) && $field->fields['item'] == 'other')
           || $field->fields['type'] == 'checkbox'
           || $field->fields['type'] == 'radio'
           || $field->fields['type'] == 'dropdown_multiple'
           || (isset($field->fields['item']) && ($field->fields['item'] == 'Group'
                                         || $field->fields['item'] == 'User'
                   || $field->fields['item'] == 'urgency'
                   || $field->fields['item'] == 'impact'))
           || $field->fields['type'] == 'number'
           || $field->fields['type'] == 'basket')) {
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

      $_POST["custom_values"]  = PluginMetademandsFieldParameter::_serialize($custom_values);
      $_POST["comment_values"] = PluginMetademandsFieldParameter::_serialize($comment_values);
      $_POST["default_values"] = PluginMetademandsFieldParameter::_serialize($default_values);

   } else if ($field->fields['type'] == 'link') {
      $_POST["custom_values"]  = PluginMetademandsFieldParameter::_serialize($_POST['custom_values']);
      $_POST["comment_values"] = '';
   } else if ($field->fields['type'] != 'yesno') {
       //used for default_values don't uncomment
//      $_POST["custom_values"]  = '';
      $_POST["comment_values"] = '';
   }
   if (isset($_POST["value"]) && is_array($_POST["value"])) {
      $_POST["value"] = PluginMetademandsFieldParameter::_serialize($_POST["value"]);
   }

   $informations_to_display = [];
   if (isset($_POST['informations_to_display'])) {
      $informations_to_display = $_POST['informations_to_display'];
   }
   $_POST["informations_to_display"] = PluginMetademandsFieldParameter::_serialize($informations_to_display);

   if (!isset($field->fields['item'])) {
      $field->fields['item'] = "";
   }
    if (isset($field->fields['type']) && $field->fields['type'] == 'dropdown_multiple'
        && isset($field->fields['item']) && $field->fields['item'] == 'User') {
        if (isset($_POST['user_group'])) {
            $custom_values['user_group'] = $_POST['user_group'];
            $_POST["custom_values"]  = PluginMetademandsFieldParameter::_serialize($custom_values);
        }
    }

   //    Check update rights for fields
    $fieldparameter->check(-1, UPDATE, $_POST);


   if ($field->fields['type'] == 'yesno') {
       unset($_POST['default_values']);
   }

    $fieldparameter->update($_POST);

    Html::back();

} else if (isset($_POST["delete_field_custom_values"])) {

   $crit = [
      'id' => $_POST['plugin_metademands_fields_id'],
   ];
    $fieldparameter->getFromDBByCrit($crit);

   $custom_values = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields["custom_values"]);

//   Toolbox::logInfo($custom_values);
   unset($custom_values[$_POST['id']]);

   $keys = range(1, count($custom_values));
   $values = array_values($custom_values);

    Toolbox::logInfo($keys);
    Toolbox::logInfo($values);
   if (count($keys) == count($values)) {
       $custom_values = array_combine($keys, $values);
       $fieldparameter->update([
           'id'            => $fieldparameter->getID(),
           'custom_values' => PluginMetademandsFieldParameter::_serialize($custom_values)
       ]);
   } elseif (count($values) == 0) {
       $custom_values = [];
       $fieldparameter->update([
           'id'            => $fieldparameter->getID(),
           'custom_values' => PluginMetademandsFieldParameter::_serialize($custom_values)
       ]);
   }

    $default_values = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields["default_values"]);

    unset($default_values[$_POST['id']]);

    $keys = range(1, count($default_values));
    $values = array_values($default_values);

    if (count($keys) == count($values)) {
        $default_values = array_combine($keys, $values);
        $fieldparameter->update([
            'id' => $fieldparameter->getID(),
            'default_values' => PluginMetademandsFieldParameter::_serialize($default_values)
        ]);
    } elseif (count($values) == 0) {
        $custom_values = [];
        $fieldparameter->update([
            'id'            => $fieldparameter->getID(),
            'default_values' => PluginMetademandsFieldParameter::_serialize($default_values)
        ]);
    }
    $comment_values = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields["comment_values"]);

    unset($comment_values[$_POST['id']]);

    $keys = range(1, count($comment_values));
    $values = array_values($comment_values);

    if (count($keys) == count($values)) {
        $comment_values = array_combine($keys, $values);
        $fieldparameter->update([
            'id' => $fieldparameter->getID(),
            'comment_values' => PluginMetademandsFieldParameter::_serialize($comment_values)
        ]);
    } elseif (count($values) == 0) {
        $custom_values = [];
        $fieldparameter->update([
            'id'            => $fieldparameter->getID(),
            'comment_values' => PluginMetademandsFieldParameter::_serialize($comment_values)
        ]);
    }

    Html::back();

} else {
//   $field->checkGlobal(READ);
//   Html::header(PluginMetademandsField::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");
//   Html::requireJs('tinymce');
//   $field->display(['id' => $_GET["id"]]);
//   Html::footer();
}
