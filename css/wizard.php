<?php
header("Content-type: text/css; charset: UTF-8");
include('../../../inc/includes.php');
$color = "#3a5693";
$hover = "#ff9c10";
?>

/* Wrapper Style */


div[class^="btnsc"] {
   float: left;
   margin: 0 10px 10px 0;
   height: 175px !important;
   position: relative;
   cursor: pointer;
   transition: all .4s ease;
   user-drag: element;
   /*border: solid 1px dashed;*/
   text-align: center;
   /*line-height:100px;*/
   /*min-height: 250px !important;*/
   /*padding: 20px !important;*/
   border-radius: 10px !important;
   -moz-border-radius: 10px;
   -webkit-border-radius: 10px !important;
   margin: 2px;
   border-color: #CCC;
   border-style: dashed;
width: 200px;
}


div[class^="btnsc"]:hover {
   opacity: 0.7;
}

div[class^="btnsc"]:active {
   transform: scale(.98, .98);
}

.fa-menu-md {
   margin-top: 20px;
}
