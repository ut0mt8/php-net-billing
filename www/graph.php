<?php

require("include/common.php");

/* ugly wrapper */
if (isset($_GET['start']) && isset($_GET['end']) && isset($_GET['cust']) && isset($_GET['tri'])) {
  $tri = $_GET['tri'];
  $rrdfiles = array();
  if ($handle = opendir($rrdpath.$tri."/")) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry != "." && $entry != "..") {
         array_push($rrdfiles, $rrdpath.$tri."/".$entry);
      }
    }
    closedir($handle);
 }
 $title = strtoupper($_GET['tri'])." - ".$_GET['cust'];
 if (count($rrdfiles) > 1 ) {
   graphTwoRrd($title, $rrdfiles[0], $rrdfiles[1], $_GET['start'], $_GET['end']);
 }
 else {
   graphOneRrd($title, $rrdfiles[0], $_GET['start'], $_GET['end']);
 }
}

