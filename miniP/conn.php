<?php

$sname = "localhost";
$uname = "root";
$password = "10886@vrosa";
$db_name = "farmer_market_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if(!$conn) {
    echo "connection failed";
}




