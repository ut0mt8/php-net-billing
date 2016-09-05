<?php

$username = 'bill';
$password = 'azKp4ragcq3HPiwq4AKo';
$hostname = 'localhost';
$database = 'billing';

$mysql = mysqli_connect($hostname, $username, $password, $database) or die ('Unable to connect to Mysql');
$data = array();
$result = $mysql->query('select tri, customer, site from customers order by tri;');
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

