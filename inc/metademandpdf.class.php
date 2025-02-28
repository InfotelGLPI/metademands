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
define('EURO',chr(128));
/**
 * Class PluginMetaDemandsMetaDemandPdf
 */
#[AllowDynamicProperties]
class PluginMetaDemandsMetaDemandPdf extends Fpdf\Fpdf
{

    /* Constantes pour paramétrer certaines données. */
    var $line_height = 6;     // Hauteur d'une ligne simple.
    var $multiline_height = 6;     // Hauteur d'un textarea
    var $linebreak_height = 6;     // Hauteur d'une break.
    var $bgcolor = 'grey';
    var $value_width = 45;
    var $pol_def = 'Helvetica'; // Police par défaut;
    var $title_size = 13;      // Taille du titre.
    var $subtitle_size = 12;      // Taille du titre de bloc.
    var $font_size = 10;      // Taille des champs.
    var $margin_top = 10;      // Marge du haut.
    var $margin_bottom = 10;      // Marge du bas.
    var $margin_left = 10;       // Marge de gauche et de droite accessoirement.
    var $big_width_cell = 210;     // Largeur d'une cellule qui prend toute la page.
    var $page_height = 297;
    var $header_height = 30;
    var $footer_height = 10;
    var $page_width;
    var $fields;
    var $title;
    var $subtitle;

    /**
     * PluginMetaDemandsMetaDemandPdf constructor.
     *
     * @param $title
     * @param $subtitle
     */
    public function __construct($title, $subtitle, $id)
    {
        parent::__construct('P', 'mm', 'A4');

        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->id = $id;
        $this->page_width = $this->big_width_cell - ($this->margin_left * 2);
        $this->title_width = $this->page_width;
        $quarter = ($this->page_width / 4);
        $this->label_width = $quarter;
        $this->value_width = ($quarter * 3);

        //      $this->label_width = $quarter * 2;
        //      $this->value_width = $quarter * 2;
        // Set font size
        $this->SetFontSize($this->font_size);
        // Select our font family
        $this->SetFont('Helvetica', '');
    }

    /**
     * Fonctions permettant définir la couleur du texte
     */
    function SetFontGrey()
    {
        $this->SetTextColor(205, 205, 205);
    }

    function SetFontRed()
    {
        $this->SetTextColor(255, 0, 0);
    }

    function SetFontBlue()
    {
        $this->SetTextColor(153, 204, 255);
    }

    function SetFontDarkBlue()
    {
        $this->SetTextColor(0, 0, 255);
    }

    function SetFontBlack()
    {
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * @param $color
     */
    function SetFontColor($color)
    {
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
    function SetBackgroundGrey()
    {
        $this->SetFillColor(225, 225, 215);
    }

    function SetBackgroundHardGrey()
    {
        $this->SetFillColor(192, 192, 192);
    }

    function SetBackgroundBlue()
    {
        $this->SetFillColor(185, 218, 255);
    }

    function SetBackgroundRed()
    {
        $this->SetFillColor(255, 0, 0);
    }

    function SetBackgroundYellow()
    {
        $this->SetFillColor(255, 255, 204);
    }

    function SetBackgroundWhite()
    {
        $this->SetFillColor(255, 255, 255);
    }

    /**
     * @param $color
     */
    function SetBackgroundColor($color)
    {
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

    function Header()
    {

        $this->SetXY($this->margin_left, $this->margin_top);

        $largeurCoteTitre = 35;
        $largeurCaseTitre = $this->big_width_cell - ($this->margin_left * 2) - ($largeurCoteTitre * 2);

        //Cellule contenant l'image
        $image = '../pics/login_logo_glpi.png';
        $target = 20;
        list($width, $height, $type, $attr) = getimagesize($image);
        list($width, $height) = $this->imageResize($width, $height, $target);

        if (!Plugin::isPluginActive('orderfollowup')) {
            $this->CellTitleValue($largeurCoteTitre, 20, "", 'TBL', 'L', 'grey', 0, $this->font_size, 'black');
            $this->Image(
                $image,
                $this->margin_left + 5,
                $this->margin_top + $height / 3,
                $width,
                $height
            ); // x, y, w, h
        } else {
            $this->CellTitleValue($largeurCoteTitre, 20, "ID : ".$this->id, 'TBL', 'C', 'grey', 0, $this->font_size, 'black');
        }
        if (Plugin::isPluginActive('orderfollowup')) {
            $largeurCaseTitre += 85;
        }

        //Cellule contenant le titre
        $title = str_replace("’", "'", $this->title);
        $subtitle = Toolbox::stripTags($this->subtitle);
        $this->SetX($this->margin_left + $largeurCoteTitre);
        $this->CellTitleValue($largeurCaseTitre, 5, '', 'TLR', 'C', '', 0, $this->font_size, 'black');
        $this->SetY($this->GetY() + 5);


        $this->SetX($this->margin_left + $largeurCoteTitre);

        $this->CellTitleValue($largeurCaseTitre, 5, Toolbox::decodeFromUtf8(Toolbox::stripslashes_deep($title)), 'LR', 'C', '', 1, $this->title_size, 'black');
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->margin_left + $largeurCoteTitre);
        $this->CellTitleValue($largeurCaseTitre, 10, Toolbox::decodeFromUtf8(Toolbox::stripslashes_deep($subtitle)), 'BLR', 'C', '', 0, $this->font_size, 'black');
        $this->SetY($this->GetY() - 10);


        //Date
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
    function imageResize($width, $height, $target)
    {
        if ($width > $height) {
            $percentage = ($target / $width);
        } else {
            $percentage = ($target / $height);
        }

        $width = round($width * $percentage);
        $height = round($height * $percentage);

        return [$width, $height];
    }

    /**
     * Permet de dessiner une cellule.
     *
     * @param type $w
     * @param type $h
     * @param type $value
     * @param string $border
     * @param string $align
     * @param string $color
     * @param bool $bold
     * @param int $size
     * @param string $fontColor
     */
    function CellTitleValue($w, $h, $value, $border = 'LRB', $align = 'L', $color = '', $bold = false, $size = 12, $fontColor = '')
    {
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
     * @param type $w
     * @param type $h
     * @param type $values
     * @param type $label
     * @param type $type
     * @param string $border
     * @param string $align
     * @param string $color
     * @param bool $bold
     * @param int $size
     * @param string $fontColor
     * @param string $link
     */
    function MultiCellValue($w, $h, $type, $label, $values, $border = 'LRB', $align = 'C', $color = '', $bold = false, $size = 10, $fontColor = '', $link = '')
    {
        if (empty($size)) {
            $size = $this->font_size;
        }
        $y = $this->GetY();
        $x = $this->GetX();

        //Draw label
        $this->SetBackgroundColor($this->bgcolor);
        $this->SetFontNormal($fontColor, $bold, $size);
        //Calculate label
        //      $width = $this->GetStringWidth($label) + ($this->cMargin * 2);
        //      if ($width < $this->label_width) {
        $width = $this->label_width;
        //      }
        if ($type == 'linebreak') {
            $this->SetBackgroundColor($color);
            $this->MultiCell($this->title_width, $h, $label, $border, $align, true);

        } else if ($type == 'title' || $type == 'title-block' || $type == 'textarea' || $type == 'signature') {
            $this->MultiCell($this->title_width, $h, $label, $border, $align, true);

        } else {
            $this->MultiCell($width, $h, $label, $border, $align, true);
            $this->SetXY($x + $width, $y);
        }

        if ($type != 'title' && $type != 'title-block' && $type != 'linebreak') {
            $this->SetBackgroundColor($color);
            $this->SetFontNormal($fontColor, $bold, $size);
            //Draw values
            if ($type == 'link') {
                $this->Cell($w, $h, $label, $border, 1, $align, true, $link);
            } elseif ($type == 'textarea') {
                $this->MultiCell($w, $h, $values, $border, $align, true);
            } elseif ($type == 'signature') {
                $this->MultiCell($w, $h + 10, $this->Image($values, $x, $y + 8, 33.78), $border, $align, false);
            } else {
                $width_values = $w;
                //            if ($width != $this->label_width) {
                //               $width_values = $w - $width;
                //            }
                //fix php8 fpdf


                if (is_null($values)) {
                    $values = "";
                }
                $this->MultiCell($width_values, $h, $values, $border, $align, true);
            }
        }
    }

    function BasicTable($header, $data, $color = '')
    {
        $this->SetBackgroundColor($this->bgcolor);
        $w = array(20, 80, 80, 15, 30, 30, 20); //275
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
        $this->Ln();
        // Color and font restoration
        $this->SetBackgroundColor($color);
        // Data
        $fill = false;
        $total = 0;
        foreach($data as $row)
        {
            $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
            $row[1] = Toolbox::substr($row[1], 0, 40) . "...";
            $row[2] = Toolbox::substr($row[2], 0, 40) . "...";
            $this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
            $this->Cell($w[2],6,$row[2],'LR',0,'L',$fill);
            $this->Cell($w[3],6,$row[3],'LR',0,'L',$fill);
            $row[4] = Toolbox::substr($row[4], 0, 20);
            $this->Cell($w[4],6,$row[4],'LR',0,'L',$fill);
            $this->Cell($w[5],6,$row[5]." ".EURO,'LR',0,'L',$fill);
            $this->Cell($w[6],6,$row[6]." ".EURO,'LR',0,'L',$fill);
            $this->Ln();
            $fill = !$fill;

            $total += $row[6];
        }
        // Closing line
        $this->Cell(array_sum($w),0,'','T');

        $this->Ln();
        $this->Cell(195,6,"",0,0,'C',true);
        $grandtotal = __('Grand total (HT)', 'orderfollowup');
        $this->SetBackgroundColor($this->bgcolor);
        $this->Cell(60,6,Toolbox::decodeFromUtf8($grandtotal),1,0,'C',true);
        $this->SetBackgroundColor($color);
        $this->Cell(20,6,Html::formatNumber($total, false, 2)." ".EURO,1,0,'L',$fill);

    }

    function BasicTableFreeInputs($header, $data, $color = '')
    {
        $this->SetBackgroundColor($this->bgcolor);
        $w = array(30, 80, 100, 15, 30, 20);//190
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
        $this->Ln();
        // Color and font restoration
        $this->SetBackgroundColor($color);
        // Data
        $fill = false;
        $total = 0;
        foreach($data as $row)
        {
            $row[0] = Toolbox::substr($row[0], 0, 15);
            $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
            $r1 = Toolbox::substr($row[1], 0, 40);
            $r2 = Toolbox::substr($row[2], 0, 50);
            $this->Cell($w[1],6,$r1,'LR',0,'L',$fill);
            $this->Cell($w[2],6,$r2,'LR',0,'L',$fill);
            $this->Cell($w[3],6,$row[3],'LR',0,'L',$fill);
            $row[4] = Toolbox::substr($row[4], 0, 20);
            $this->Cell($w[4],6,$row[4]." ".EURO,'LR',0,'L',$fill);
            $this->Cell($w[5],6,$row[5]." ".EURO,'LR',0,'L',$fill);
            $this->Ln();
            $fill = !$fill;

            $total += $row[5];
        }
        // Closing line
        $this->Cell(array_sum($w),0,'','T');

        $this->Ln();
        $this->Cell(195,6,"",0,0,'C',true);
        $grandtotal = __('Grand total (TTC)', 'orderfollowup');
        $this->SetBackgroundColor($this->bgcolor);
        $this->Cell(60,6,Toolbox::decodeFromUtf8($grandtotal),1,0,'C',true);
        $this->SetBackgroundColor($color);
        $this->Cell(20,6,Html::formatNumber($total, false, 2)." ".EURO,1,0,'L',$fill);


        $this->Ln();
        $this->Cell(195,6,"",0,0,'C',true);
        $grandtotalHT = __('Grand total (HT)', 'orderfollowup')." ".__('(if VAT 20%)', 'orderfollowup');
        $this->SetBackgroundColor($this->bgcolor);
        $this->Cell(60,6,Toolbox::decodeFromUtf8($grandtotalHT),1,0,'C',true);
        $this->SetBackgroundColor($color);
        $totalHT = $total / 1.2;
        $this->Cell(20,6,Html::formatNumber($totalHT, false, 2)." ".EURO,1,0,'L',$fill);


    }

    /**
     * Redéfinit une fonte
     *
     * @param type $color
     * @param type $bold
     * @param type $size
     */
    function SetFontNormal($color, $bold, $size)
    {
        $this->SetFontColor($color);
        if ($bold) {
            $this->SetFont($this->pol_def, 'B', $size);
        } else {
            $this->SetFont($this->pol_def, '', $size);
        }
    }

    /**
     * @param      $form
     * @param      $fields
     * @param bool $with_basket
     */
    public function setFields($form, $field_forms, $metademands_id, $parent_tickets_id, $with_basket = false)
    {
        global $PLUGIN_HOOKS;

        $nb = count($field_forms);

        for ($i = 0; $i < $nb; $i++) {
            if ($with_basket == false) {
                $fields = $field_forms[$i]['fields'] ?? [];
            } else {
                $fields = $field_forms[$i]['basket'] ?? [];
            }

            $fielCount = 0;
            $rank = 1;

            $newForm = [];
            $widths = [];
            $fields['tickets_id'] = $parent_tickets_id;

            foreach ($form as $key => $elt) {
                if ($with_basket && $elt['is_basket'] == false) {
                    continue;
                }
                if (isset($fields[$key])
                    || $elt['type'] == 'title'
                    || $elt['type'] == 'title-block'
                    || $elt['type'] == 'upload'
                    || $elt['type'] == 'radio') {
                    $newForm[$fielCount] = $elt;
                    if ($rank != $elt['rank']) {
                        $newForm[$fielCount] = ['type' => 'linebreak',
                            'rank' => $elt['rank'],
                            'id' => 0];
                        $fielCount++;
                        $newForm[$fielCount] = $elt;
                    }
                    $rank = $elt['rank'];

                    $fielCount++;
                }

                if (!empty($elt['name'])) {
                    $widths[] = $this->GetStringWidth($elt['name']);
                }
            }
            if (count($widths) > 0) {
                $max_width = max($widths);
                $this->label_width = $max_width;
                $this->value_width = $this->page_width - $max_width;
            }

            $dbu = new DbUtils();

            if ($i > 0) {
                $this->MultiCellValue($this->title_width, $this->linebreak_height, 'linebreak', '', '', 'TB', 'C', '', 0, '', 'black');
            }

            foreach ($newForm as $key => $elt) {

                $meta = new PluginMetademandsMetademand();
                $meta->getFromDB($metademands_id);
                if ($meta->getField('hide_no_field') == 1) {
                    if ($elt['type'] == 'radio' && $fields[$elt['id']] === "") {
                        continue;
                    }
                    if ($elt['type'] == 'number' && $fields[$elt['id']] == "0") {
                        continue;
                    }
                    if ($elt['type'] == 'range' && $fields[$elt['id']] == "0") {
                        continue;
                    }
                    if ($elt['type'] == 'checkbox' && ($fields[$elt['id']] == "" || $fields[$elt['id']] == "0")) {
                        continue;
                    }
                    if ($elt['type'] == 'yesno' && $fields[$elt['id']] != "2") {
                        continue;
                    }
                    if ($elt['type'] == 'dropdown_meta' && $fields[$elt['id']] == "0") {
                        continue;
                    }
                }

                if (isset($fields[$elt['id']])
                    || $elt['type'] == 'title'
                    || $elt['type'] == 'title-block'
                    || $elt['type'] == 'upload'
                    || $elt['type'] == 'linebreak'
                    || $elt['type'] == 'radio') {

                    $y = $this->GetY();
                    if (($y + $this->line_height) >= ($this->page_height - $this->header_height)) {
                        if (Plugin::isPluginActive('orderfollowup')) {
                            $this->AddPage("L");
                        } else {
                            $this->AddPage("P");
                        }
                    }

                    $label = "";
                    if (!empty($elt['name'])) {
                        if (empty($label = PluginMetademandsField::displayField($elt['id'], 'name'))) {
                            $label = Toolbox::stripslashes_deep($elt['name']);
                        }
                        $label = str_replace("’", "'", $label);
                        $label = Toolbox::stripslashes_deep($label);
                        if ($label != null) {
                            $label = Toolbox::decodeFromUtf8($label);
                        }
                    }

                    switch ($elt['type']) {
                        case 'title':
                        case 'title-block':
                            // Draw line
                        $this->MultiCellValue($this->title_width, $this->line_height, $elt['type'], $label, '', 'LRBT', 'C', $this->bgcolor, 1, $this->subtitle_size, 'black');
                            break;

                        case 'linebreak':
                            // Draw line
                            $this->MultiCellValue($this->title_width, $this->linebreak_height, $elt['type'], '', '', 'TB', 'C', '', 0, '', 'black');
                            break;

                        case 'text':
                        case 'tel':
                        case 'email':
                        case 'url':
                        case 'number':
                        case 'range':
                            $value = $fields[$elt['id']];
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);
                                // Draw line
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'textarea':
                            $value = $fields[$elt['id']];
                            $value = Glpi\RichText\RichText::getTextFromHtml($value);
                            $value = Toolbox::stripslashes_deep($value);

                            $value = (Toolbox::addslashes_deep($value));
                            $value = Html::cleanPostForTextArea($value);

                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);

                                // Draw line
                                $this->MultiCellValue($this->title_width, $this->multiline_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'link':
                            //                  $label = __('Link');
                            //                  $value = $fields[$elt['id']];
                            //                  if (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0) {
                            //                     $value = "http://" . $value;
                            //                  }
                            //                  $value = Toolbox::decodeFromUtf8(Toolbox::stripslashes_deep($value));
                            //                  // Draw line
                            //                  $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, '', $value, 'LRBT', 'L', '', 0, '', 'black');
                            break;

                        case 'upload':
                            if ($with_basket == false) {
                                if (isset($fields['_filename'])) {
                                    $values = $fields['_filename'];
                                    $prefixes = $fields['_prefix_filename'];
                                    $value = [];
                                    foreach ($values as $k => $v) {
                                        $name = $values[$k];
                                        $prefix = $prefixes[$k];
                                        $valid_name = str_replace($prefix, "", $name);
                                        $value[] .= $valid_name;
                                    }
                                    $value = implode(', ', $value);
                                    $value = Toolbox::stripslashes_deep($value);
                                    if ($value != null) {
                                        $value = Toolbox::decodeFromUtf8($value);

                                        // Draw line
                                        $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                    }
                                }
                            } else {
                                $value = [];
                                if (!empty($fields[$elt['id']])) {
                                    $files = json_decode($fields[$elt['id']], 1);
                                    foreach ($files as $file) {
                                        $name = $file['_filename'];
                                        $prefix = $file['_prefix_filename'];
                                        $valid_name = str_replace($prefix, "", $name);
                                        $value[] .= $valid_name;

                                    }
                                }
                                $value = implode(', ', $value);
                                $value = Toolbox::stripslashes_deep($value);
                                if ($value != null) {
                                    $value = Toolbox::decodeFromUtf8($value);

                                    // Draw line
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }
                            }
                            break;

                        case 'dropdown':
                        case 'dropdown_object':
                        case 'dropdown_meta':
                            $value = " ";
                            switch ($elt['item']) {
                                case 'User':
                                    $information = ['full_name'];
                                    if (isset($elt['informations_to_display'])) {
                                        $information = json_decode($elt['informations_to_display']);
                                        $information = empty($information) ? ['full_name'] : $information;
                                    }
                                    $item = new $elt["item"]();
                                    $dataItems = "";
                                    if ($item->getFromDB($fields[$elt['id']])) {

                                        if (in_array('full_name', $information)) {
                                            $dataItems .= " " . $elt["item"]::getFriendlyNameById($fields[$elt['id']]) . " ";
                                        }
                                        if (in_array('realname', $information)) {
                                            $dataItems .= " " . $item->fields["realname"] . " ";
                                        }
                                        if (in_array('firstname', $information)) {
                                            $dataItems .= " " . $item->fields["firstname"] . " ";
                                        }
                                        if (in_array('name', $information)) {
                                            $dataItems .= " " . $item->fields["name"] . " ";
                                        }
                                        if (in_array('email', $information)) {
                                            $dataItems .= " " . $item->getDefaultEmail() . " ";
                                        }


                                    }
                                    if (empty($dataItems)) {
                                        $value = $dbu->getUserName($fields[$elt['id']], 0, true);
                                    } else {
                                        $value = $dataItems;
                                    }

                                    break;
                                case 'ITILCategory_Metademands':
                                    $value = Dropdown::getDropdownName($dbu->getTableForItemType('ITILCategory'), $fields[$elt['id']]);
                                    $value = ($value == '&nbsp;') ? ' ' : Toolbox::stripTags($value);
                                    break;
                                case 'mydevices':
                                    $dbu = new DbUtils();
                                    $splitter = explode("_", $fields[$elt['id']]);
                                    if (count($splitter) == 2) {
                                        $itemtype = $splitter[0];
                                        $items_id = $splitter[1];
                                    }
                                    if (isset($itemtype) && isset($items_id)) {
                                        $value = Dropdown::getDropdownName($dbu->getTableForItemType($itemtype),
                                            $items_id);
                                    }
                                    break;
                                case 'urgency':
                                    $value = Ticket::getUrgencyName($fields[$elt['id']]);
                                    break;
                                case 'impact':
                                    $value = Ticket::getImpactName($fields[$elt['id']]);
                                    break;
                                case 'priority':
                                    $value = Ticket::getPriorityName($fields[$elt['id']]);
                                    break;
                                case 'other':
                                    if (!empty($elt['custom_values']) && isset ($elt['custom_values'])) {
                                        $custom_values = PluginMetademandsFieldParameter::_unserialize($elt['custom_values']);
                                        foreach ($custom_values as $k => $val) {
                                            if (!empty($ret = PluginMetademandsField::displayField($elt["id"], "custom" . $k))) {
                                                $custom_values[$k] = $ret;
                                            }
                                        }
                                        $value = ($fields[$elt['id']] != 0) ? $custom_values[$fields[$elt['id']]] : ' ';
                                    }
                                    break;
                                //others
                                default:
                                    $value = Dropdown::getDropdownName($dbu->getTableForItemType($elt['item']), $fields[$elt['id']]);
                                    $value = ($value == '&nbsp;') ? ' ' : Toolbox::stripTags($value);
                                    break;
                            }
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);
                                // Draw line
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'yesno':
                            $value = __('No');
                            if ($fields[$elt['id']] == 2) {
                                $value = __('Yes');
                            }
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);

                                // Draw line
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'dropdown_multiple':
                            $value = " ";

                            if (!empty($elt['custom_values']) && $elt['item'] != 'User') {
                                $custom_values = PluginMetademandsFieldParameter::_unserialize($elt['custom_values']);
                                foreach ($custom_values as $k => $val) {
                                    if ($elt['item'] != "other") {
                                        $custom_values[$k] = $elt["item"]::getFriendlyNameById($k);
                                    } else {
                                        if (!empty($ret = PluginMetademandsField::displayField($elt["id"], "custom" . $k))) {
                                            $custom_values[$k] = $ret;
                                        }
                                    }
                                }
                                $values = $fields[$elt['id']];
                                $parseValue = [];
                                if (!empty($values) && !is_array($values)) {
                                    $values = json_decode($values);
                                }
                                if (is_array($values) && count($values)) {
                                    foreach ($values as $k => $v) {
                                        if (isset($custom_values[$v])) {
                                            array_push($parseValue, $custom_values[$v]);
                                        }
                                    }
                                }
                                $value = implode(', ', $parseValue);
                                $value = Toolbox::stripslashes_deep($value);
                                if ($value != null) {
                                    $value = Toolbox::decodeFromUtf8($value);

                                    // Draw line
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }
                            } else if ($elt['item'] == 'User') {
                                $values = $fields[$elt['id']];
                                $parseValue = [];
                                if (!empty($values) && !is_array($values)) {
                                    $values = json_decode($values);
                                }

                                $information = ['full_name'];
                                if (isset($elt['informations_to_display'])) {
                                    $information = json_decode($elt['informations_to_display']);
                                    $information = empty($information) ? ['full_name'] : $information;
                                }
                                $item = new $elt["item"]();
                                foreach ($values as $k => $v) {
                                    $dataItems = "";
                                    if ($item->getFromDB($v)) {

                                        if (in_array('full_name', $information)) {
                                            $dataItems .= " " . $elt["item"]::getFriendlyNameById($v) . " ";
                                        }
                                        if (in_array('realname', $information)) {
                                            $dataItems .= " " . $item->fields["realname"] . " ";
                                        }
                                        if (in_array('firstname', $information)) {
                                            $dataItems .= " " . $item->fields["firstname"] . " ";
                                        }
                                        if (in_array('name', $information)) {
                                            $dataItems .= " " . $item->fields["name"] . " ";
                                        }
                                        if (in_array('email', $information)) {
                                            $dataItems .= " " . $item->getDefaultEmail() . " ";
                                        }
                                        if (!empty($dataItems)) {
                                            $dataItems .= PHP_EOL;
                                        }

                                    }
                                    //                              array_push($parseValue, $custom_values[$v]);
                                    if (!empty($dataItems)) {
                                        array_push($parseValue, $dataItems);
                                    }
                                }
                                $value = implode("", $parseValue);
                                $value = Toolbox::stripslashes_deep($value);
                                if ($value != null) {
                                    $value = Toolbox::decodeFromUtf8($value);

                                    // Draw line
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }

                            }
                            break;

                        case 'checkbox':
                            $value = " ";
                            if (!empty($elt['custom_values'])) {
                                $custom_values = PluginMetademandsFieldParameter::_unserialize($elt['custom_values']);
                                foreach ($custom_values as $k => $val) {
                                    if (!empty($ret = PluginMetademandsField::displayField($elt["id"], "custom" . $k))) {
                                        $custom_values[$k] = $ret;
                                    }
                                }
                                $values = PluginMetademandsFieldParameter::_unserialize($fields[$elt['id']]);
                                $custom_checkbox = [];

                                foreach ($custom_values as $k => $v) {
                                    $checked = isset($values[$k]) ? 1 : 0;
                                    if ($checked) {
                                        $custom_checkbox[] .= $v;
                                    }
                                }
                                $value = implode(', ', $custom_checkbox);
                                $value = Toolbox::stripslashes_deep($value);
                                if ($value != null) {
                                    $value = Toolbox::decodeFromUtf8($value);

                                    // Draw line
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }
                            }
                            break;

                        case 'radio' :
                            $value = " ";
                            if (!empty($elt['custom_values'])) {
                                $custom_values = PluginMetademandsFieldParameter::_unserialize($elt['custom_values']);
                                foreach ($custom_values as $k => $val) {
                                    if (!empty($ret = PluginMetademandsField::displayField($elt["id"], "custom" . $k))) {
                                        $custom_values[$k] = $ret;
                                    }
                                }
                                if (isset($fields[$elt['id']]) && $fields[$elt['id']] != NULL) {
                                    $values = PluginMetademandsFieldParameter::_unserialize($fields[$elt['id']]);
                                    foreach ($custom_values as $k => $v) {
                                        if ($values == $k) {
                                            $value = $custom_values[$k];
                                        }
                                    }
                                }
                                $value = Toolbox::stripslashes_deep($value);
                                if ($value != null && $fields[$elt['id']] != null) {
                                    $value = Toolbox::decodeFromUtf8($value);
                                    // Draw line
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }
                            }
                            break;

                        case 'date':
                            $value = Html::convDate($fields[$elt['id']]);
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);

                                // Draw line
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'time':
                            $value = $fields[$elt['id']];
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);

                                // Draw line
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'datetime':
                            $value = Html::convDateTime($fields[$elt['id']]);
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);

                                // Draw line
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'date_interval':
                            $value = Html::convDate($fields[$elt['id']]);
                            $value2 = Html::convDate($fields[$elt['id'] . "-2"]);
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);
                            }
                            $value2 = Toolbox::stripslashes_deep($value2);
                            if ($value2 != null) {
                                $value2 = Toolbox::decodeFromUtf8($value2);
                            }
                            if (!empty($elt['label2'])) {
                                //                        $label2 = Html::resume_name(Toolbox::decodeFromUtf8(Toolbox::stripslashes_deep($elt['label2'])), 30);
                                if (empty($label2 = PluginMetademandsField::displayField($elt['id'], 'label2'))) {
                                    $label2 = Toolbox::stripslashes_deep($elt['label2']);
                                }
                                $label2 = str_replace("’", "'", $label2);
                                $label2 = Toolbox::stripslashes_deep($label2);
                                if ($label2 != null) {
                                    $label2 = Toolbox::decodeFromUtf8($label2);
                                }
                                $label2 = (Toolbox::addslashes_deep($label2));
                                $label2 = Html::cleanPostForTextArea($label2);

                                $label2 = Glpi\RichText\RichText::getTextFromHtml($label2);
                            }
                            // Draw line
                            $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label2, $value2, 'LRBT', 'L', '', 0, '', 'black');
                            break;

                        case 'datetime_interval':
                            $value = Html::convDateTime($fields[$elt['id']]);
                            $value2 = Html::convDateTime($fields[$elt['id'] . "-2"]);
                            $value = Toolbox::stripslashes_deep($value);
                            if ($value != null) {
                                $value = Toolbox::decodeFromUtf8($value);
                            }
                            $value2 = Toolbox::stripslashes_deep($value2);
                            if ($value2 != null) {
                                $value2 = Toolbox::decodeFromUtf8($value2);
                            }
                            if (!empty($elt['label2'])) {
                                //                        $label2 = Html::resume_name(Toolbox::decodeFromUtf8(Toolbox::stripslashes_deep($elt['label2'])), 30);
                                if (empty($label2 = PluginMetademandsField::displayField($elt['id'], 'label2'))) {
                                    $label2 = Toolbox::stripslashes_deep($elt['label2']);
                                }
                                $label2 = str_replace("’", "'", $label2);
                                $label2 = Toolbox::stripslashes_deep($label2);
                                if ($label2 != null) {
                                    $label2 = Toolbox::decodeFromUtf8($label2);
                                }
                                $label2 = (Toolbox::addslashes_deep($label2));
                                $label2 = Html::cleanPostForTextArea($label2);

                                $label2 = Glpi\RichText\RichText::getTextFromHtml($label2);
                            }
                            // Draw line
                            $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label2, $value2, 'LRBT', 'L', '', 0, '', 'black');
                            break;
                        case 'basket':
                            if (Plugin::isPluginActive('orderfollowup')) {
                                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $elt['plugin_metademands_metademands_id']])) {
                                    $items = PluginMetademandsBasket::displayFieldPDF($elt,$fields, $label);
                                    if (count($items)) {
                                        foreach ($items as $id => $values) {
                                            foreach ($values as $label => $value) {
                                                $header[$id][] = $label;
                                                $data[$id][] = $value;
                                            }
                                        }
                                        $header = end($header);
                                        $this->BasicTable($header,$data);
                                    }
                                } else {
                                    $value = PluginMetademandsBasket::displayFieldPDF($elt,$fields, $label);
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }

                            } else {
                                $value = PluginMetademandsBasket::displayFieldPDF($elt,$fields, $label);
                                $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            }
                            break;

                        case 'signature' ;
                            $value = GLPI_PICTURE_DIR . '/' . $fields[$elt['id']];
                            $this->MultiCellValue($this->title_width, $this->multiline_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                            break;
                        default:

                        case 'free_input':
                            if (Plugin::isPluginActive('orderfollowup')) {
                                $ordermaterialmeta = new PluginOrderfollowupMetademand();
                                if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $elt['plugin_metademands_metademands_id']])) {
                                    $items = PluginOrderfollowupFreeinput::displayFieldPDF($elt,$fields, $label);
                                    if (count($items)) {
                                        foreach ($items as $id => $values) {
                                            foreach ($values as $label => $value) {
                                                $header[$id][] = $label;
                                                $data[$id][] = $value;
                                            }
                                        }
                                        $header = end($header);
                                        $this->BasicTableFreeInputs($header,$data, '');
                                    }
                                }
                            }
                            break;

                            if (isset($PLUGIN_HOOKS['metademands'])) {
                                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                                    $value = self::displayPluginFieldPDF($plug, $elt, $fields, $label);
                                    $this->MultiCellValue($this->value_width, $this->line_height, $elt['type'], $label, $value, 'LRBT', 'L', '', 0, '', 'black');
                                }
                            }
                            break;
                    }
                }
            }
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    static function displayPluginFieldPDF($plug, $elt, $fields, $label)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'displayFieldPDF'])) {
                    return $item->displayFieldPDF($elt, $fields, $label);
                }
            }
        }
    }

    /**
     *
     */
    function Footer()
    {
        $this->SetY($this->page_height - $this->margin_top - $this->header_height);
    }

    /**
     * @param $form
     * @param $fields
     */
    public function drawPdf($form, $fields, $metademands_id, $parent_tickets_id, $with_basket = false)
    {
        $this->AliasNbPages();
        if (Plugin::isPluginActive('orderfollowup')) {
            $this->AddPage("L");
        } else {
            $this->AddPage("P");
        }

        $this->SetAutoPageBreak(false);
        $this->setFields($form, $fields, $metademands_id, $parent_tickets_id, $with_basket);
    }

    /**
     * @param $string
     *
     * @return string
     */
    /**
     * @param $string
     *
     * @return string
     */
    static function cleanTitle($string)
    {
        $string = str_replace(array('[\', \']'), '', $string);
        $string = preg_replace('/\[.*\]/U', '', $string);
        $string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $string);
        $string = htmlentities($string, ENT_COMPAT, 'utf-8');
        $string = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $string);
        $string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string); // pour les ligatures e.g. '&oelig;'
        $string = preg_replace('#&[^;]+;#', '', $string); // supprime les autres caractères
        $string = preg_replace(array('/[^a-z0-9]/i', '/[-]+/'), '-', $string);
        return strtolower(trim($string, '-'));
    }

    /**
     * @param $name
     * @param $tickets_id
     * @param $entities_id
     *
     * @return \Document_Item
     */
    public function addDocument($name, $itemtype, $items_id, $entities_id)
    {
        //Construction du chemin du fichier
        //      $filename = "metademand_" . $idTicket . ".pdf";
        $filename = $name . ".pdf";
        $this->Output(GLPI_DOC_DIR . "/_uploads/" . $filename, 'F');

        //Création du document
        $doc = new Document();
        //Construction des données
        $input = [];
        $input["name"] = addslashes($filename);
        $input["upload_file"] = $filename;
        $input["mime"] = "application/pdf";
        $input["date_mod"] = date("Y-m-d H:i:s");
        $input["users_id"] = Session::getLoginUserID();
        $input["entities_id"] = $entities_id;
        if ($itemtype == 'Ticket') {
            $input["tickets_id"] = $items_id;
        }
        //Initialisation du document
        $newdoc = $doc->add($input);
        $docitem = new Document_Item();

        //entities_id
        $docitem->add(['itemtype' => $itemtype,
            "documents_id" => $newdoc,
            "items_id" => $items_id,
            "entities_id" => $entities_id]);
        return $docitem;
    }
}
