<?php
date_default_timezone_set("Asia/Manila");


$servername = "localhost\\SQLEXPRESS";
$username   = "sa";
$password   = "Admin@1234";
$dbname     = "db_cdms";

$connectionInfo = [
    "Database" => $dbname,
    "UID" => $username,
    "PWD" => $password,
    "Encrypt" => false,
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($servername, $connectionInfo);


if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Session setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cred_user'])) {
    $_SESSION['cred_user'] = "";
    $_SESSION['user_level'] = "0";
}

$url = $_SERVER['REQUEST_URI'];

// ✅ Fix 2: No binding params for SELECT *
$sql  = "SELECT * FROM tbl_users";
$stmt = sqlsrv_prepare($conn, $sql);

if (sqlsrv_execute($stmt)) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Process rows...
    }
}

// ✅ Fix 3: Guard empty session before using it as a filter
if (strpos($url, 'login') === false) {
    if (empty($_SESSION['cred_user'])) {
        header("Location: login.php");
        exit();
    }

    $sql  = "SELECT * FROM tbl_users WHERE [user] = ?";
    $stmt = sqlsrv_prepare($conn, $sql, array(&$_SESSION['cred_user']));

    if (sqlsrv_execute($stmt)) {
        if (!sqlsrv_has_rows($stmt)) {
            header("Location: login.php");
            exit();
        }
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Process user data...
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}