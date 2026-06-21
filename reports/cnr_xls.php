<?php
include '../js/fphp.php';
include '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_fr_date = isset($_POST['frcnrdate']) ? $_POST['frcnrdate'] : date('Y-m-d');
    $p_to_date = isset($_POST['tocnrdate']) ? $_POST['tocnrdate'] : date('Y-m-d');
} else {
    $p_fr_date = isset($_GET['frcnrdate']) ? $_GET['frcnrdate'] : date('Y-m-d');
    $p_to_date = isset($_GET['tocnrdate']) ? $_GET['tocnrdate'] : date('Y-m-d');
}

$filename = 'CNR_' . $p_fr_date . '_to_' . $p_to_date . '_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// ── Fetch column names ─────────────────────────────────────────────────────
$col_arr   = [];
$stmt_cols = sqlsrv_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_cnr' ORDER BY ORDINAL_POSITION"
);
if ($stmt_cols) {
    while ($row = sqlsrv_fetch_array($stmt_cols, SQLSRV_FETCH_ASSOC))
        $col_arr[] = $row['COLUMN_NAME'];
    sqlsrv_free_stmt($stmt_cols);
}

// ── Build HTML table (Excel reads this fine) ───────────────────────────────
echo '<table border="1">';

// Header
echo '<tr>';
foreach ($col_arr as $col)
    echo '<th style="background-color:#1E6B3C; color:#FFFFFF; font-weight:bold;">' . htmlspecialchars($col) . '</th>';
echo '</tr>';

// Data rows
$stmt = sqlsrv_query($conn,
    "SELECT * FROM tbl_cnr WHERE req_dt BETWEEN ? AND ? ORDER BY req_dt DESC",
    [$p_fr_date, $p_to_date]
);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo '<tr>';
        foreach ($col_arr as $col) {
            $val = $row[$col] ?? '';
            if ($val instanceof DateTime) $val = $val->format('Y-m-d H:i:s');
            echo '<td>' . htmlspecialchars((string)$val) . '</td>';
        }
        echo '</tr>';
    }
    sqlsrv_free_stmt($stmt);
} else {
    echo '<tr><td colspan="' . count($col_arr) . '">No data found.</td></tr>';
}

echo '</table>';

sqlsrv_close($conn);
exit;
?>