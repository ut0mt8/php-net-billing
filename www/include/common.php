<?php

$username = "bill";
$password = "xxxxx";
$hostname = "localhost";

$rrdpath = '/data/tools/billing/rrd/';
$imgpath = '/data/tools/billing/tmp/';

$globalOptions = 
'--rigid '.
'--alt-autoscale '.
'--slope-mode '.
'--width=550 '.
'--height=140 '.
'--base=1000 '.
'--font TITLE:8: '.
'--font AXIS:7:a '. 
'--font LEGEND:7: '.
'--font UNIT:6: '.
'--vertical-label=Mbits/s '.
'--units-exponent=6 '.
'--watermark "IguaneSolutions Billing (@)" '; 


function sum_arrays($array1, $array2) {
  $array = array();
  foreach($array1 as $index => $value) {
    $array[$index] = isset($array2[$index]) ? $array2[$index] + $value : $value;
  }
  return $array;
}

function replaceNan($value) {
  return ( is_nan($value) ? 0 : $value );
}

function filterNan($value) {
  return (! is_nan($value));
}

function get95th($rrdfile, $ds, $starttime, $endtime) {
  $opts = array("AVERAGE", "--start", $starttime, "--end", $endtime);
  $arr_rrd =  rrd_fetch($rrdfile, $opts);
  $arr_val = array_values($arr_rrd['data'][$ds]);
  $arr_res = array_map("replaceNan", $arr_val);
  sort($arr_res);  
  return floor($arr_res[round((95/100) * count($arr_res) - 0.5)]*8);
}

function getMax($rrdfile, $ds, $starttime, $endtime) {
  $opts = array("MAX",  "--start", $starttime, "--end", $endtime);
  $arr_rrd =  rrd_fetch($rrdfile, $opts);
  $arr_val = array_values($arr_rrd['data'][$ds]);
  $arr_res = array_map("replaceNan", $arr_val);
  return floor(max($arr_res)*8);
}

function getAvg($rrdfile, $ds, $starttime, $endtime) {
  $opts = array("AVERAGE", "--start", $starttime, "--end", $endtime);
  $arr_rrd =  rrd_fetch($rrdfile, $opts);
  $arr_val = array_values($arr_rrd['data'][$ds]);
  $arr_res = array_filter($arr_val,"filterNan");
  $avg = array_sum($arr_res)/count($arr_res); 
  return floor($avg*8);
}

function get95thtwo($rrdfile1, $rrdfile2, $ds, $starttime, $endtime) {
  $opts = array("AVERAGE", "--start", $starttime, "--end", $endtime);
  $arr_rrd1 =  rrd_fetch($rrdfile1, $opts);
  $arr_rrd2 =  rrd_fetch($rrdfile2, $opts);
  $arr_val1 = array_values($arr_rrd1['data'][$ds]);
  $arr_val2 = array_values($arr_rrd2['data'][$ds]);
  $arr_sum = sum_arrays($arr_val1,$arr_val2);
  $arr_res = array_map("replaceNan", $arr_sum);
  sort($arr_res);  
  return floor($arr_res[round((95/100) * count($arr_res) - 0.5)]*8);
}

function getMaxtwo($rrdfile1, $rrdfile2, $ds, $starttime, $endtime) {
  $opts = array("MAX", "--start", $starttime, "--end", $endtime);
  $arr_rrd1 =  rrd_fetch($rrdfile1, $opts);
  $arr_rrd2 =  rrd_fetch($rrdfile2, $opts);
  $arr_val1 = array_values($arr_rrd1['data'][$ds]);
  $arr_val2 = array_values($arr_rrd2['data'][$ds]);
  $arr_sum = sum_arrays($arr_val1,$arr_val2);
  $arr_res = array_map("replaceNan", $arr_sum);
  return floor(max($arr_res)*8);
}

function getAvgtwo($rrdfile1, $rrdfile2, $ds, $starttime, $endtime) {
  $opts = array("AVERAGE", "--start", $starttime, "--end", $endtime);
  $arr_rrd1 =  rrd_fetch($rrdfile1, $opts);
  $arr_rrd2 =  rrd_fetch($rrdfile2, $opts);
  $arr_val1 = array_values($arr_rrd1['data'][$ds]);
  $arr_val2 = array_values($arr_rrd2['data'][$ds]);
  $arr_sum = sum_arrays($arr_val1,$arr_val2);
  $arr_res = array_filter($arr_sum,"filterNan");
  $avg = array_sum($arr_res)/count($arr_res); 
  return floor($avg*8);
}

function graphOneRrd($title, $rrdfile, $starttime, $endtime, $save = false, $inverse = false) {
  global $globalOptions;
  global $imgpath;

  if (! is_readable($rrdfile)) {
    echo "$rrdfle not found. \n";
    exit(1);
  }

  if ($save) {
     $filestrp = preg_replace('/\s+/', '_', $title);
     $filename = $imgpath.$filestrp.".png";
  } else {
     $filename = "-";
  }
  
  $inpct = get95th($rrdfile, 'in', $starttime, $endtime);
  $outpct = get95th($rrdfile, 'out', $starttime, $endtime);
  $pctbps = ($inpct > $outpct ? $inpct : $outpct);
  $pctkbps = round(($pctbps/1000));
  $pctmbps = round(($pctkbps/1000), 2);

  $graphOptions = 
  "--start=$starttime ".
  "--end=$endtime ".
  "--title=\"$title Network Traffic\" ";

  if ($inverse) {
    $graphOptions .=
    "DEF:inbytes=$rrdfile:out:AVERAGE ".
    "DEF:outbytes=$rrdfile:in:AVERAGE ";
    if ($inpct > $outpct) {
      $pctall = 'out'; 
      $pctbps = -$pctbps;
    } else {
      $pctall = 'in'; 
      $pctbps = $pctbps;
    }
  } else {
    $graphOptions .=
    "DEF:inbytes=$rrdfile:in:AVERAGE ".
    "DEF:outbytes=$rrdfile:out:AVERAGE ";
    if ($inpct > $outpct) {
      $pctall = 'in'; 
      $pctbps = $pctbps;
    } else {
      $pctall = 'out'; 
      $pctbps = -$pctbps;
    }
  }

  $graphOptions .= 
  'CDEF:inbits=inbytes,8,* '.
  'CDEF:outbits=outbytes,8,* '.
  'CDEF:inmbits=inbits,1000000,/ '.
  'CDEF:outmbits=outbits,1000000,/ '.
  'CDEF:outbitsinv=outbytes,-8,* '.
  'VDEF:inavg=inmbits,AVERAGE '.
  'VDEF:outavg=outmbits,AVERAGE '.
  'VDEF:inmax=inmbits,MAXIMUM '.
  'VDEF:outmax=outmbits,MAXIMUM '.
  'LINE:0#101010: '.
  'COMMENT:" \\n" '.
  'AREA:inbits#D2FFC4: '.
  'LINE:inbits#30FF30:"Inbound \t" '.
  'GPRINT:inavg:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:inmax:"Maximum\: %8.2lf Mbits/s\\n" '.
  'AREA:outbitsinv#C0F7FE: '.
  'LINE:outbitsinv#8080FF:"Outbound \t" '.
  'GPRINT:outavg:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:outmax:"Maximum\: %8.2lf Mbits/s\\n" '.
  'LINE:0#909090: '.
  'COMMENT:" \\n" '.
  "HRULE:$pctbps#FF3030:\"95th percentile ($pctall) \:\" ".
  "COMMENT:\"$pctmbps Mbits/s\\n\" ".
  'COMMENT:" " ';

  if (!$save) {
    header("Content-Type: image/png");
  } else {
    print("/usr/bin/rrdtool graph ".$filename." -a PNG ".$globalOptions.$graphOptions); 
  }
  passthru("/usr/bin/rrdtool graph ".$filename." -a PNG ".$globalOptions.$graphOptions); 
}

function graphTwoRrd($title, $rrdfilea, $rrdfileb, $starttime, $endtime, $save = false, $inverse = false) {
  global $globalOptions;
  global $imgpath;

  if (! is_readable($rrdfilea)) {
    echo "$rrdfilea not found. \n";
    exit(1);
  }
  if (! is_readable($rrdfileb)) {
    echo "$rrdfileb not found. \n";
    exit(1);
  }

  if ($save) {
     $filestrp = preg_replace('/\s+/', '_', $title);
     $filename = $imgpath.$filestrp.".png";
  } else {
     $filename = "-";
  }

  /* compute 95th in */
  $inpct = get95thtwo($rrdfilea, $rrdfileb, 'in', $starttime, $endtime);
  $outpct = get95thtwo($rrdfilea, $rrdfileb, 'out', $starttime, $endtime);
  $pctbps = ($inpct > $outpct ? $inpct : $outpct);
  $pctkbps = round(($pctbps/1000));
  $pctmbps = round(($pctkbps/1000), 2);

  $graphOptions = 
  "--start=$starttime ".
  "--end=$endtime ".
  "--title=\"$title Network Traffic\" ";

  if ($inverse) {
    $graphOptions .=
    "DEF:inbytesa=$rrdfilea:out:AVERAGE ".
    "DEF:inbytesb=$rrdfileb:out:AVERAGE ".
    "DEF:outbytesa=$rrdfilea:in:AVERAGE ".
    "DEF:outbytesb=$rrdfileb:in:AVERAGE ";
    if ($inpct > $outpct) {
      $pctall = 'out'; 
      $pctbps = -$pctbps;
    } else {
      $pctall = 'in'; 
      $pctbps = $pctbps;
    }
  } else {
    $graphOptions .=
    "DEF:inbytesa=$rrdfilea:in:AVERAGE ".
    "DEF:inbytesb=$rrdfileb:in:AVERAGE ".
    "DEF:outbytesa=$rrdfilea:out:AVERAGE ".
    "DEF:outbytesb=$rrdfileb:out:AVERAGE ";
    if ($inpct > $outpct) {
      $pctall = 'in'; 
      $pctbps = $pctbps;
    } else {
      $pctall = 'out'; 
      $pctbps = -$pctbps;
    }
  }

  $graphOptions .= 
  'CDEF:inbitsa=inbytesa,8,* '.
  'CDEF:inbitsb=inbytesb,8,* '.
  'CDEF:inbitsall=inbitsa,inbitsb,+ '.
  'CDEF:outbitsa=outbytesa,8,* '.
  'CDEF:outbitsb=outbytesb,8,* '.
  'CDEF:outbitsall=outbitsa,outbitsb,+ '.
  'CDEF:inmbitsa=inbitsa,1000000,/ '.
  'CDEF:inmbitsb=inbitsb,1000000,/ '.
  'CDEF:inmbitsall=inbitsall,1000000,/ '.
  'CDEF:outmbitsa=outbitsa,1000000,/ '.
  'CDEF:outmbitsb=outbitsb,1000000,/ '.
  'CDEF:outmbitsall=outbitsall,1000000,/ '.
  'CDEF:outbitsinva=outbytesa,-8,* '.
  'CDEF:outbitsinvb=outbytesb,-8,* '.
  'CDEF:outbitsinvall=outbitsinva,outbitsinvb,+ '.
  'VDEF:inavga=inmbitsa,AVERAGE '.
  'VDEF:inavgb=inmbitsb,AVERAGE '.
  'VDEF:inavgall=inmbitsall,AVERAGE '.
  'VDEF:outavga=outmbitsa,AVERAGE '.
  'VDEF:outavgb=outmbitsb,AVERAGE '.
  'VDEF:outavgall=outmbitsall,AVERAGE '.
  'VDEF:inmaxa=inmbitsa,MAXIMUM '.
  'VDEF:inmaxb=inmbitsb,MAXIMUM '.
  'VDEF:inmaxall=inmbitsall,MAXIMUM '.
  'VDEF:outmaxa=outmbitsa,MAXIMUM '.
  'VDEF:outmaxb=outmbitsb,MAXIMUM '.
  'VDEF:outmaxall=outmbitsall,MAXIMUM '.

  'LINE:0#101010: '.
  'COMMENT:" \\n" '.
  'AREA:inbitsa#D2FFC4:"Inbound 1\t" '.
  'GPRINT:inavga:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:inmaxa:"Maximum\: %8.2lf Mbits/s\\n" '.
  'AREA:inbitsb#C0FF97:"Inbound 2\t":STACK '.
  'GPRINT:inavgb:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:inmaxb:"Maximum\: %8.2lf Mbits/s\\n" '.
  'LINE:inbitsall#30FF30:"Inbound All\t" '.
  'GPRINT:inavgall:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:inmaxall:"Maximum\: %8.2lf Mbits/s\\n" '.
  'AREA:outbitsinva#C0F7FE:"Outbound 1\t" '.
  'GPRINT:outavga:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:outmaxa:"Maximum\: %8.2lf Mbits/s\\n" '.
  'AREA:outbitsinvb#A8E4FF:"Outbound 2\t":STACK '.
  'GPRINT:outavgb:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:outmaxb:"Maximum\: %8.2lf Mbits/s\\n" '.
  'LINE:outbitsinvall#8080FF:"Outbound All\t" '.
  'GPRINT:outavgall:"Average\: %8.2lf Mbits/s\t" '.
  'GPRINT:outmaxall:"Maximum\: %8.2lf Mbits/s\\n" '.
  'LINE:0#909090: '.
  'COMMENT:" \\n" '.
  "HRULE:$pctbps#FF3030:\"95th percentile ($pctall) \:\" ".
  "COMMENT:\"$pctmbps Mbits/s\\n\" ".
  'COMMENT:" " ';

  if (!$save) {
    header("Content-Type: image/png");
  } else {
    print("/usr/bin/rrdtool graph ".$filename." -a PNG ".$globalOptions.$graphOptions); 
  }
  passthru("/usr/bin/rrdtool graph ".$filename." -a PNG ".$globalOptions.$graphOptions); 
}

function textOneRrd($title, $rrdfile, $starttime, $endtime, $inverse) {
  global $globalOptions;

  if (! is_readable($rrdfile)) {
    echo "$rrdfle not found. \n";
    exit(1);
  }

  $inavg = getAvg($rrdfile, 'in', $starttime, $endtime);
  $outavg = getAvg($rrdfile, 'out', $starttime, $endtime);
  $inmax = getMax($rrdfile, 'in', $starttime, $endtime);
  $outmax = getMax($rrdfile, 'out', $starttime, $endtime);
  $inpct = get95th($rrdfile, 'in', $starttime, $endtime);
  $outpct = get95th($rrdfile, 'out', $starttime, $endtime);
  $pctbps = ($inpct > $outpct ? $inpct : $outpct);
  
  $data = array(
     'title' => "$title",
     'avg_in' => "$inavg",
     'avg_out' => "$outavg",
     'max_in' => "$inmax",
     'max_out' => "$outmax",
     'pct_in' => "$inpct",
     'pct_out' => "$outpct",
     'pct' => "$pctbps",
  );

  header('Content-Type: application/json');
  echo json_encode($data);
}

function textTwoRrd($title, $rrdfilea, $rrdfileb, $starttime, $endtime, $inverse) {
  global $globalOptions;

  if (! is_readable($rrdfilea)) {
    echo "$rrdfilea not found. \n";
    exit(1);
  }
  if (! is_readable($rrdfileb)) {
    echo "$rrdfileb not found. \n";
    exit(1);
  }

  $inavg = getAvgtwo($rrdfilea, $rrdfileb, 'in', $starttime, $endtime);
  $outavg = getAvgtwo($rrdfilea, $rrdfileb, 'out', $starttime, $endtime);
  $inmax = getMaxtwo($rrdfilea, $rrdfileb, 'in', $starttime, $endtime);
  $outmax = getMaxtwo($rrdfilea, $rrdfileb, 'out', $starttime, $endtime);
  $inpct = get95thtwo($rrdfilea, $rrdfileb, 'in', $starttime, $endtime);
  $outpct = get95thtwo($rrdfilea, $rrdfileb, 'out', $starttime, $endtime);
  $pctbps = ($inpct > $outpct ? $inpct : $outpct);

  $data = array(
     'title' => "$title",
     'avg_in' => "$inavg",
     'avg_out' => "$outavg",
     'max_in' => "$inmax",
     'max_out' => "$outmax",
     'pctin' => "$inpct",
     'pctout' => "$outpct",
     'pct' => "$pctbps",
  );

  header('Content-Type: application/json');
  echo json_encode($data);
}

