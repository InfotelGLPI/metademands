<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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

require_once(GLPI_ROOT . "/plugins/metademands/fpdf/fpdf.php");

/**
 * Class PluginMetaDemandsMetaDemandPdf
 */
class PluginMetaDemandsMetaDemandPdf extends FPDF {

   /* Constantes pour paramétrer certaines données. */
   var $line_height      = 10;     // Hauteur d'une ligne simple.
   var $multiline_height = 5;     // Hauteur d'un textarea
   var $linebreak_height = 5;     // Hauteur d'une break.
   var $bgcolor          = 'grey';
   var $value_width      = 45;
   var $pol_def          = 'Helvetica'; // Police par défaut;
   var $title_size       = 15;      // Taille du titre.
   var $subtitle_size    = 12;      // Taille du titre de bloc.
   var $font_size        = 8;      // Taille des champs.
   var $margin_top       = 10;      // Marge du haut.
   var $margin_bottom    = 10;      // Marge du bas.
   var $margin_left      = 10;       // Marge de gauche et de droite accessoirement.
   var $big_width_cell   = 210;     // Largeur d'une cellule qui prend toute la page.
   var $page_height      = 297;
   var $header_height    = 30;
   var $footer_height    = 10;
   var $page_width;
   var $fields;
   var $title;
   var $subtitle;

   /**
    * PluginMetaDemandsMetaDemandPdf constructor.
    *
    * @param $titre
    * @param $sousTitre
    */
   public function __construct($title, $subtitle) {
      parent::__construct('P', 'mm', 'A4');

      $this->title       = $title;
      $this->subtitle    = $subtitle;
      $this->page_width  = $this->big_width_cell - ($this->margin_left * 2);
      $this->title_width = $this->page_width;
      $quarter           = ($this->page_width / 4);
      //      $this->label_width        = $quarter;
      //      $this->value_width        = ($quarter * 3);
      //      $this->dateinterval_width = (($quarter * 3) / 2);
      $this->label_width = $quarter * 2;
      $this->value_width = $quarter * 2;
      // Set font size
      $this->SetFontSize($this->font_size);
      // Select our font family
      $this->SetFont('Helvetica', '');
   }

   /**
    * Fonctions permettant définir la couleur du texte
    */
   function SetFontGrey() {
      $this->SetTextColor(205, 205, 205);
   }

   function SetFontRed() {
      $this->SetTextColor(255, 0, 0);
   }

   function SetFontBlue() {
      $this->SetTextColor(153, 204, 255);
   }

   function SetFontDarkBlue() {
      $this->SetTextColor(0, 0, 255);
   }

   function SetFontBlack() {
      $this->SetTextColor(0, 0, 0);
   }

   /**
    * @param $color
    */
   function SetFontColor($color) {
      switch ($color) {
         case 'grey':
            $this->SetFontGrey();
            break;
         case 'red':
            $this->SetFontRed();
            break;
         case 'blue':
            $this->SetFontBlue();
            break;
         case 'darkblue':
            $this->SetFontDarkBlue();
            break;
         default:
            $this->SetFontBlack();
            break;
      }
   }

   /**
    * Fonctions permettant remplir la couleur d'une cellule
    */
   function SetBackgroundGrey() {
      $this->SetFillColor(225, 225, 215);
   }

   function SetBackgroundHardGrey() {
      $this->SetFillColor(192, 192, 192);
   }

   function SetBackgroundBlue() {
      $this->SetFillColor(185, 218, 255);
   }

   function SetBackgroundRed() {
      $this->SetFillColor(255, 0, 0);
   }

   function SetBackgroundYellow() {
      $this->SetFillColor(255, 255, 204);
   }

   function SetBackgroundWhite() {
      $this->SetFillColor(255, 255, 255);
   }

   /**
    * @param $color
    */
   function SetBackgroundColor($color) {
      switch ($color) {
         case 'grey':
            $this->SetBackgroundGrey();
            break;
         case 'hardgrey':
            $this->SetBackgroundHardGrey();
            break;
         case 'red':
            $this->SetBackgroundRed();
            break;
         case 'blue':
            $this->SetBackgroundBlue();
            break;
         case 'yellow':
            $this->SetBackgroundYellow();
            break;
         default :
            $this->SetBackgroundWhite();
            break;
      }
   }

   function Header() {
      $this->SetY($this->margin_top);
      $this->SetX($this->margin_left);

      $largeurCoteTitre = 35;
      $largeurCaseTitre = $this->big_width_cell - ($this->margin_left * 2) - ($largeurCoteTitre * 2);

      //Cellule contenant l'image
      $image  = '../pics/login_logo_glpi.png';
      $target = 20;
      list($width, $height, $type, $attr) = getimagesize($image);
      list($width, $height) = $this->imageResize($width, $height, $target);
      $this->CellTitleValue($largeurCoteTitre, 20, '', 'TBL', 'L', 'grey', 0, $this->font_size, 'black');
      $this->Image($image, $this->margin_left + 5, $this->margin_top + $height / 3, $width, $height); // x, y, w, h

      //Cellule contenant le titre
      $this->SetX($this->margin_left + $largeurCoteTitre);
      $this->CellTitleValue($largeurCaseTitre, 20, Toolbox::decodeFromUtf8($this->title), 'TBL', 'C', '', 1, $this->title_size, 'black');

      //Cellule ne contenant rien pour le moment
      $this->SetX($this->margin_left + $largeurCoteTitre + $largeurCaseTitre);
      $this->CellTitleValue($largeurCoteTitre, 5, '', 'TLR', 'C', 'grey', 0, $this->font_size, 'black');
      $this->SetY($this->GetY() + 5);
      $this->SetX($this->margin_left + $largeurCoteTitre + $largeurCaseTitre);
      $this->CellTitleValue($largeurCoteTitre, 5, Toolbox::decodeFromUtf8(__('Created on', 'metademands')), 'LR', 'C', 'grey', 0, $this->font_size, 'black');
      $this->SetY($this->GetY() + 5);
      $this->SetX($this->margin_left + $largeurCoteTitre + $largeurCaseTitre);
      $this->CellTitleValue($largeurCoteTitre, 10, Html::convDate(date('Y-m-d')), 'BLR', 'C', 'grey', 0, $this->font_size, 'black');
      $this->SetY($this->GetY() + 15);
   }


   /**
    * ImageResize
    *
    * @param int $width
    * @param int $height
    * @param int $target
    *
    * @return array
    */
   function imageResize($width, $height, $target) {
      if ($width > $height) {
         $percentage = ($target / $width);
      } else {
         $percentage = ($target / $height);
      }

      $width  = round($width * $percentage);
      $height = round($height * $percentage);

      return [$width, $height];
   }

   /**
    * Permet de dessiner une cellule.
    *
    * @param type   $w
    * @param type   $h
    * @param type   $value
    * @param string $border
    * @param string $align
    * @param string $color
    * @param bool   $bold
    * @param int    $size
    * @param string $fontColor
    */
   function CellTitleValue($w, $h, $value, $border = 'LRB', $align = 'L', $color = '', $bold = false, $size = 12, $fontColor = '') {
      if (empty($size)) {
         $size = $this->font_size;
      }
      $this->SetBackgroundColor($color);
      $this->SetFontNormal($fontColor, $bold, $size);
      $this->Cell($w, $h, $value, $border, 0, $align, 1);
   }

   /**
    * Permet de dessiner une cellule multiligne.
    *
    * @param type   $w
    * @param type   $h
    * @param type   $values
    * @param type   $label
    * @param type   $type
    * @param string $border
    * @param string $align
    * @param string $color
    * @param bool   $bold
    * @param int    $size
    * @param string $fontColor
    * @param string $link
    */
   function MultiCellValue($w, $h, $border = 'LRB', $align = 'C', $color = '', $bold = false, $size = 10, $fontColor = '', $type, $label, $values, $link = '') {

      if (empty($size)) {
         $size = $this->font_size;
      }
      $y = $this->GetY();
      $x = $this->GetX();

      //      $spaceleft = $this->h - $this->GetY() - $this->bMargin;    // Calculates the space available on the current page
      //      $spaceleft       = $this->h - $this->GetY() - $this->header_height - $this->footer_height;
      //
      //      $multiLabelHeight = $this->GetMultiCellHeight($w, $h, $label);    // Calculates what the height of your MultiCell would be
      //      $multiValueHeight = $this->GetMultiCellHeight($w, $h, $values);    // Calculates what the height of your MultiCell would be
      //
      //      if ($multiLabelHeight > $multiValueHeight) {
      //         $multiCellHeight = $multiLabelHeight;
      //      } else {
      //         $multiCellHeight = $multiValueHeight;
      //      }
      //      if ($multiCellHeight > $spaceleft) {
      //         $this->AddPage();   // Adds a page if there is not enough space available for the MultiCell
      //      }

      //Draw label
      $this->SetBackgroundColor($this->bgcolor);
      $this->SetFontNormal($fontColor, $bold, $size);

      if ($type == 'linebreak') {
         $this->SetBackgroundColor($color);
         $this->MultiCell($this->title_width, $h, $label, $border, $align, true);

      } else if ($type == 'title' || $type == 'textarea') {
         $this->MultiCell($this->title_width, $h, $label, $border, $align, true);

      } else {
         $this->MultiCell($this->label_width, $h, $label, $border, $align, true);
         $this->SetY($y);
         $this->SetX($x + $this->label_width);
      }

      if ($type != 'title' && $type != 'linebreak') {
         $this->SetBackgroundColor($color);
         $this->SetFontNormal($fontColor, $bold, $size);
         //Draw values
         if ($type == 'link') {
            $this->Cell($w, $h, $label, $border, 1, $align, true, $link);
         } else {
            $this->MultiCell($w, $h, $values, $border, $align, true);
         }
      }
   }


   /**
    * Redéfinit une fonte
    *
    * @param type $color
    * @param type $bold
    * @param type $size
    */
   function SetFontNormal($color, $bold, $size) {
      $this->SetFontColor($color);
      if ($bold) {
         $this->SetFont($this->pol_def, 'B', $size);
      } else {
         $this->SetFont($this->pol_def, '', $size);
      }
   }

   /**
    * @param $form
    * @param $fields
    */
   public function setFields($form, $fields) {

      $fielCount = 0;
      $rank      = 1;

      $newForm = [];
      foreach ($form as $key => $elt) {
         if (isset($fields['fields'][$key]) || $elt['type'] == 'title') {
            $newForm[$fielCount] = $elt;
            if ($rank != $elt['rank']) {
               $newForm[$fielCount] = ['type' => 'linebreak', 'rank' => $elt['rank'], 'id' => 0];
               $fielCount++;
               $newForm[$fielCount] = $elt;
            }
            $rank = $elt['rank'];

            $fielCount++;
         }
      }
      $dbu = new DbUtils();

      foreach ($newForm as $key => $elt) {

         if (isset($fields['fields'][$elt['id']])
             || $elt['type'] == 'title'
             || $elt['type'] == 'linebreak') {

            $y = $this->GetY();
            if (($y + $this->line_height) >= ($this->page_height - $this->header_height)) {
               $this->AddPage();
            }
            $label = "";
            if (!empty($elt['label'])) {
               $label = Html::resume_name(Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($elt['label'])), 45);
            }
            switch ($elt['type']) {
               case 'title':
                  // Draw line
                  $this->MultiCellValue($this->title_width, $this->line_height, 'LRBT', 'C', $this->bgcolor, 1, $this->subtitle_size, 'black', $elt['type'], $label, '');
                  break;

               case 'linebreak':
                  // Draw line
                  $this->MultiCellValue($this->title_width, $this->linebreak_height, 'TB', 'C', '', 0, '', 'black', $elt['type'], '', '');
                  break;

               case 'text':
                  $value = $fields['fields'][$elt['id']];
                  $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  // Draw line
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  break;

               case 'textarea':
                  $value = $fields["fields"][$elt['id']];
                  $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  // Draw line
                  $this->MultiCellValue($this->title_width, $this->multiline_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  break;

               case 'link':
                  $label = __('Link');
                  $value = $fields['fields'][$elt['id']];
                  if (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0) {
                     $value = "http://" . $value;
                  }
                  $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  // Draw line
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, '', $value);
                  break;

               case 'dropdown':
                  $value = " ";
                  switch ($elt['item']) {
                     case 'user':
                        $value = $dbu->getUserName($fields['fields'][$elt['id']]);
                        break;
                     case 'other':
                        if (!empty($elt['custom_values']) && isset ($elt['custom_values'])) {
                           $elt['custom_values'] = PluginMetademandsField::_unserialize($elt['custom_values']);
                           $value                = ($fields['fields'][$elt['id']] != 0) ? $elt['custom_values'][$fields['fields'][$elt['id']]] : ' ';
                        }
                        break;
                     //others
                     default:
                        $value = Dropdown::getDropdownName($dbu->getTableForItemType($elt['item']), $fields['fields'][$elt['id']]);
                        $value = ($value == '&nbsp;') ? ' ' : $value;
                        break;
                  }
                  $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  // Draw line
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  break;

               case 'yesno':
                  $value = __('No');
                  if ($fields['fields'][$elt['id']] == 2) {
                     $value = __('Yes');
                  }
                  $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  // Draw line
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  break;

               case 'dropdown_multiple':
                  $value = " ";
                  if (!empty($elt['custom_values'])) {
                     $custom_values = PluginMetademandsField::_unserialize($elt['custom_values']);
                     $values        = $fields['fields'][$elt['id']];
                     $parseValue    = [];

                     foreach ($values as $k => $v) {
                        array_push($parseValue, $custom_values[$v]);
                     }
                     $value = implode(', ', $parseValue);
                     $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                     // Draw line
                     $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  }
                  break;

               case 'checkbox':
                  $value = " ";
                  if (!empty($elt['custom_values'])) {
                     $custom_values   = PluginMetademandsField::_unserialize($elt['custom_values']);
                     $values          = PluginMetademandsField::_unserialize($fields['fields'][$elt['id']]);
                     $custom_checkbox = [];

                     foreach ($custom_values as $k => $v) {
                        $checked = isset($values[$k]) ? 1 : 0;
                        if ($checked) {
                           $custom_checkbox[] .= $v;
                        }
                     }
                     $value = implode(', ', $custom_checkbox);
                     $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                     // Draw line
                     $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  }
                  break;

               case 'radio' :
                  $value = " ";
                  if (!empty($elt['custom_values'])) {
                     $custom_values = PluginMetademandsField::_unserialize($elt['custom_values']);
                     $values        = PluginMetademandsField::_unserialize($fields['fields'][$elt['id']]);
                     foreach ($custom_values as $k => $v) {
                        if ($values == $k) {
                           $value = $custom_values[$k];
                        }
                     }
                     $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                     // Draw line
                     $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  }
                  break;

               case 'datetime':
                  $value = Html::convDate($fields['fields'][$elt['id']]);
                  $value = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  // Draw line
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  break;

               case 'datetime_interval':
                  $value  = Html::convDate($fields['fields'][$elt['id']]);
                  $value2 = Html::convDate($fields['fields'][$elt['id'] . "-2"]);
                  $value  = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value));
                  $value2 = Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($value2));
                  if (!empty($elt['label2'])) {
                     $label2 = Html::resume_name(Toolbox::stripslashes_deep(Toolbox::decodeFromUtf8($elt['label2'])), 45);
                  }
                  // Draw line
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label, $value);
                  $this->MultiCellValue($this->value_width, $this->line_height, 'LRBT', 'L', '', 0, '', 'black', $elt['type'], $label2, $value2);
                  break;
            }
         }
      }
   }


   /**
    * @param        $w
    * @param        $h
    * @param        $txt
    * @param null   $border
    * @param string $align
    * source : https://gist.github.com/johnballantyne/2989898e2196686388f6
    *
    * @return int
    */
   function GetMultiCellHeight($w, $h, $txt, $border = null, $align = 'J') {
      // Calculate MultiCell with automatic or explicit line breaks height
      // $border is un-used, but I kept it in the parameters to keep the call
      //   to this function consistent with MultiCell()
      $cw = &$this->CurrentFont['cw'];
      if ($w == 0)
         $w = $this->w - $this->rMargin - $this->x;
      $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
      $s    = str_replace("\r", '', $txt);
      $nb   = strlen($s);
      if ($nb > 0 && $s[$nb - 1] == "\n")
         $nb--;
      $sep    = -1;
      $i      = 0;
      $j      = 0;
      $l      = 0;
      $ns     = 0;
      $height = 0;
      while ($i < $nb) {
         // Get next character
         $c = $s[$i];
         if ($c == "\n") {
            // Explicit line break
            if ($this->ws > 0) {
               $this->ws = 0;
               $this->_out('0 Tw');
            }
            //Increase Height
            $height += $h;
            $i++;
            $sep = -1;
            $j   = $i;
            $l   = 0;
            $ns  = 0;
            continue;
         }
         if ($c == ' ') {
            $sep = $i;
            $ls  = $l;
            $ns++;
         }
         $l += $cw[$c];
         if ($l > $wmax) {
            // Automatic line break
            if ($sep == -1) {
               if ($i == $j)
                  $i++;
               if ($this->ws > 0) {
                  $this->ws = 0;
                  $this->_out('0 Tw');
               }
               //Increase Height
               $height += $h;
            } else {
               if ($align == 'J') {
                  $this->ws = ($ns > 1) ? ($wmax - $ls) / 1000 * $this->FontSize / ($ns - 1) : 0;
                  $this->_out(sprintf('%.3F Tw', $this->ws * $this->k));
               }
               //Increase Height
               $height += $h;
               $i      = $sep + 1;
            }
            $sep = -1;
            $j   = $i;
            $l   = 0;
            $ns  = 0;
         } else
            $i++;
      }
      // Last chunk
      if ($this->ws > 0) {
         $this->ws = 0;
         $this->_out('0 Tw');
      }
      //Increase Height
      $height += $h;

      return $height;
   }

   /**
    *
    */
   function Footer() {
      $this->SetY($this->page_height - $this->margin_top - $this->header_height);
   }

   /**
    * @param $form
    * @param $fields
    */
   public function drawPdf($form, $fields) {
      $this->AliasNbPages();
      $this->AddPage();
      $this->SetAutoPageBreak(false);
      $this->setFields($form, $fields);
   }

   /**
    * @param $idTicket
    * @param $entitiesId
    *
    * @return \Document_Item
    */
   public function addDocument($name, $tickets_id, $entities_id) {
      //Construction du chemin du fichier
      //      $filename = "metademand_" . $idTicket . ".pdf";
      $filename = $name . ".pdf";
      $this->Output(GLPI_DOC_DIR . "/_uploads/" . $filename, 'F');

      //Création du document
      $doc = new Document();
      //Construction des données
      $input                = [];
      $input["name"]        = addslashes($filename);
      $input["upload_file"] = $filename;
      $input["mime"]        = "application/pdf";
      $input["date_mod"]    = date("Y-m-d H:i:s");
      $input["users_id"]    = Session::getLoginUserID();
      $input["entities_id"] = $entities_id;
      $input["tickets_id"]  = $tickets_id;
      //entities_id
      //tickets_id
      //Initialisation du document
      $newdoc  = $doc->add($input);
      $docitem = new Document_Item();

      //entities_id
      $docitem->add(['itemtype' => "Ticket", "documents_id" => $newdoc, "items_id" => $tickets_id, "entities_id" => $entities_id]);
      return $docitem;
   }
}
