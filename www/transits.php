<?

if (isset($_POST['start'])) {
  $tstart = $_POST['start'];
  $starttime = strtotime($tstart);
} else {
  $tstart = date("Y-m-d H:i:s");
  $starttime = strtotime('now -1 day');
}

if (isset($_POST['end'])) {
  $tend = $_POST['end'];
  $endtime = strtotime($tend);
} else {
  $tend = date("Y-m-d H:i:s");
  $endtime = strtotime('now -10 min');
}

$username = "bill";
$password = "xxxxxx";
$hostname = "localhost";

$mysql = mysql_connect($hostname, $username, $password) or die ("Unable to connect to Mysql");
$db = mysql_select_db("billing", $mysql) or die ("Unable to connect to database");
$result = mysql_query ("SELECT transit from transit order by transit;");


?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transits Network Statistics</title>
  <script src="js/jquery.min.js"></script>
  <script src="js/jquery.redirect.js"></script>
  <script src="js/moment.min.js"></script>
  <script src="js/daterangepicker.js"></script>
  <link href="css/bootstrap.css" rel="stylesheet">
  <link href="css/daterangepicker.css" rel="stylesheet">
</head>
<body>
  <div class="container">
    <div class="navbar navbar-default" role="navigation">
      <div class="">
        <div class="navbar-header">
          <a class="navbar-brand" href="transits.php">Transits Network Statistics</a>
        </div>
        <div class="navbar-collapse collapse">
        </div>
      </div>
    </div>

  <table>
  <tr> 
   <td>
   <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 300px">
     <b class="caret"> </b>&nbsp;&nbsp;<span></span>
   </div>
   </td>

<script type="text/javascript">
var tstart = moment(new Date("<? echo $tstart ?>"));
var tend = moment(new Date("<? echo $tend ?>"));
$(function() {
    function cb(start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    }
    cb(tstart, tend);
    $('#reportrange').daterangepicker({
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);
});
</script>

<?  echo '<td><div style="background: #fff; padding: 5px 10px; border: 1px solid #ccc; width: 430px" >Period from : '.date("d/m/Y H:i:s",$starttime)." to ".date("d/m/Y H:i:s",$endtime)."</div></td>"; ?> 

</tr>
</table>
<hr/>
<table border='0px'>

<?
while ($row = mysql_fetch_array($result)) {
  $t = $row[0]."_flow";
  echo "<tr><td>";
  echo "<img src=\"http://billing/graph.php?start=$starttime&end=$endtime&cust=$row[0]&tri=$t\"/>";
  echo "</td></tr>";
  echo "<tr><td>&nbsp;</td></tr>";
}
?>

</table>
</div>
<script>
$('#reportrange').on('apply.daterangepicker', function(ev, picker) {
  var tstart = picker.startDate.format('YYYY-MM-DD 00:00:00');
  //var tend = picker.endDate.format('YYYY-MM-DD 23:59:59');
  var tend = picker.endDate.format('YYYY-MM-DD h:MM:ss');
  $.redirect('transits.php', {'start': tstart, 'end': tend});
});
</script>
</body>
</html>

