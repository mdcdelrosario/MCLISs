<?php
session_start(); // Must be first!

header('Content-Type: application/json');

include '../conn.php';
include '../js/fphp.php';

// Check if session variable exists AND equals 1
if (!isset($_SESSION['utilities_personnel']) || $_SESSION['utilities_personnel'] != 1) {
    echo json_encode(['error' => 'Invalid User Rights']);
    exit;
}

// Main logic
$raw     = file_get_contents('php://input');
$records = json_decode($raw, true);

if (!is_array($records) || empty($records)) {
    echo json_encode(['error' => 'No data received']);
    exit;
}

$inserted = 0;
$skipped  = 0;

foreach ($records as $rec) {
    $name = strtoupper(trim($rec['name'] ?? ''));
    $crew = trim($rec['crew'] ?? '');

    if ($name === '') continue;

    $check = sqlsrv_query($conn,
        "SELECT COUNT(*) AS cnt FROM tbl_person WHERE LOWER(LTRIM(RTRIM(name))) = ?",
        [strtolower($name)]
    );

    if ($check === false) continue;

    $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
    if ($row && $row['cnt'] > 0) {
        $skipped++;
        continue;
    }

    $insert = sqlsrv_query($conn,
        "INSERT INTO tbl_person (name, crew) VALUES (?, ?)",
        [$name, $crew]
    );

    if ($insert !== false) {
        $inserted++;
    }
}

echo json_encode([
    'inserted' => $inserted,
    'skipped'  => $skipped
]);

sqlsrv_close($conn);