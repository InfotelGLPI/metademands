<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

header("Content-type: text/css; charset: UTF-8");
include('../../../../inc/includes.php');
Session::checkLoginUser();
$color = "#3a5693";
$hover = "#ff9c10";
?>

a .far, a .fas, .btn-linkstyled .fa, .btn-linkstyled .far, .btn-linkstyled .fas {
color: unset;
}

/* Wrapper Style */


div[class="btnsc-normal"] {
   margin: 0 10px 10px 0;
   height: 200px !important;
   cursor: pointer;
   transition: all .4s ease;
   user-drag: element;
   text-align: center;
   -moz-border-radius: 10px;
   width: 250px;
   float: left;
   list-style-type: none;
   padding: 4px 15px 15px 15px;
   overflow: auto;
   transition: all .4s ease;
   user-drag: element;
   border: solid #CCC 1px;
   background-color: #FFF;
}

div[class="readonly-btnsc-normal"] {
    margin: 0 10px 10px 0;
    height: 200px !important;
    user-drag: element;
    text-align: center;
    -moz-border-radius: 10px;
    width: 250px;
    float: left;
    list-style-type: none;
    padding: 4px 15px 15px 15px;
    overflow: auto;
    user-drag: element;
    border: solid #CCC 1px;
    background-color: #FFF;
}

div[class="btnsc-normal-type"] {
    margin: 0 10px 10px 0;
    /*height: 260px !important;*/
    cursor: pointer;
    transition: all .4s ease;
    user-drag: element;
    text-align: center;
    -moz-border-radius: 10px;
    width: 250px;
    float: left;
    list-style-type: none;
    padding: 4px 15px 15px 15px;
    overflow: auto;
    transition: all .4s ease;
    user-drag: element;
    border: solid #CCC 1px;
    background-color: #FFF;
}

@media (max-width: 768px) {
div[class^="btnsc"] {
width: 240px;
}
}

div[class^="btnsc"]:hover {
   opacity: 0.9;
}

div[class^="btnsc"]:active {
   transform: scale(.98, .98);
}

.fa-menu-md {
   margin-top: 20px;
}

/* Hide all steps by default: */
.tab-sc {
display: none;
}
