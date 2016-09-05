#!/usr/bin/php
<?php

require("include/common.php");

$starttime = strtotime("first day of previous month");
$endtime = strtotime("first day of this month");
$ptystart = date('d/m/Y',strtotime("first day of previous month"));
$ptyend = date('d/m/Y', strtotime("last day of previous month"));

$to = "compta@corp.com";
$from = "net-ops@corp.com"; 
$subject = "Monthly Network Billing Report"; 
$message = "Please find below billing network graph of all customers\n";
$message .= "from $ptystart to $ptyend.\n\n";
$message .= "Regards,\n\n";
$message .= "-- \n";
$message .= "Network Team";
$headers = "From: $from";

//clean the tmp dir
foreach(glob($imgpath.'*.*') as $f) { unlink($f); }

$mysql = mysql_connect($hostname, $username, $password) or die ("Unable to connect to Mysql");
$db = mysql_select_db("billing", $mysql) or die ("Unable to connect to database");
$result = mysql_query ("SELECT tri, customer from customers order by customer");

while ($row = mysql_fetch_array($result)) {
  $tri = $row[0];
  $cust = $row[1];
  $rrdfiles = array();
  if ($handle = opendir($rrdpath.$tri."/")) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry != "." && $entry != "..") {
         array_push($rrdfiles, $rrdpath.$tri."/".$entry);
      }
    }
    closedir($handle);
 }
 $title = strtoupper($tri)." - ".$cust;
 if (count($rrdfiles) > 1 ) {
   graphTwoRrd($title, $rrdfiles[0], $rrdfiles[1], $starttime, $endtime, true);
 }
 else {
   graphOneRrd($title, $rrdfiles[0], $starttime, $endtime, true);
 }
}

mysql_close($mysql);

// construct the mail
$semi_rand = md5(time()); 
$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 
$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" ." boundary=\"{$mime_boundary}\""; 

$message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n"
         . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" . 
           "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n"; 
$message .= "--{$mime_boundary}\n";

// preparing attachments
if ($handle = opendir($imgpath)) {
  while (false !== ($entry = readdir($handle))) {
    if ($entry != "." && $entry != "..") {
      $file = fopen($imgpath.$entry,"rb");
      $data = fread($file,filesize($imgpath.$entry));
      fclose($file);
      $data = chunk_split(base64_encode($data));
      $message .= "Content-Type: {\"application/octet-stream\"};\n" .  
                  " name=\"$entry\"\n" . 
                  "Content-Disposition: attachment;\n" . " filename=\"$entry\"\n" . 
                  "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
      $message .= "--{$mime_boundary}\n";
    }
  }
  closedir($handle);
}


$success = @mail($to, $subject, $message, $headers); 
if ($success) { 
  echo "mail sent to $to!"; 
} else { 
  echo "mail could not be sent!"; 
} 
 
