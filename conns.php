<?php

// Showing PHP info for debugging purposes
//phpinfo();

// Test for MSSQL Connection
date_default_timezone_set("Asia/Manila");
$servername = "192.168.48.21\PANELCO3DB";
// $username = "TSD";
// $password = "HmUpLu3XT5r6W";
// $dbname = "p3mobiledb";

// $username = "rdonlyuserdev";
// $password = "p3P@w0rd0987!";
// $dbname = "p3ebsdb";

$servername = "localhost\SQLEXPRESS,1433";
$username = "sa";
$password = "";
$dbname = "pan3mobiledb";



// Connection options
$connsectionInfo = array("Database" => $dbname, "UID" => $username, "PWD" => $password);

// Establishing connection to MSSQL
$conns = sqlsrv_connect($servername, $connsectionInfo);

if ($conns === false) {
    //die(print_r("Cannot Connect to Server", true));
    die(print_r(sqlsrv_errors(), true));
}




?>
