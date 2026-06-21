<?php
include '../js/fphp.php'; 
include '../conn.php'; 

$p_fr_date = '';
$p_to_date = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p_fr_date = isset($_POST['frcnrdate']) ? $_POST['frcnrdate'] : date('Y-m-d');
    $p_to_date = isset($_POST['tocnrdate']) ? $_POST['tocnrdate'] : date('Y-m-d');
} elseif (isset($_GET['frcnrdate']) && isset($_GET['tocnrdate'])) {
    $p_fr_date = $_GET['frcnrdate'];
    $p_to_date = $_GET['tocnrdate'];
}

$today = date('Y-m-d H:i:s');
$filename = 'CNR_' . $p_fr_date . '_to_' . $p_to_date . '_' . date('Ymd_His') . '.csv';

// Send CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');

// Get column headers
$col_arr = [];
$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_cnr' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);

$output = fopen('php://output', 'w');
if ($stmt) {
    // Write header row
    $headers = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $col_arr[] = $row["COLUMN_NAME"];
        $headers[] = $row["COLUMN_NAME"];
    }
    fputcsv($output, $headers);
    sqlsrv_free_stmt($stmt);
    
    // Write data rows
    $sql = "SELECT * FROM tbl_cnr WHERE req_dt BETWEEN ? AND ? ORDER BY req_dt DESC";
    $params = [$p_fr_date, $p_to_date];
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt) {
        $colno = count($col_arr);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data_row = [];
            foreach ($col_arr as $col) {
                $val = $row[$col] ?? '';
                if ($val instanceof DateTime) {
                    $data_row[] = $val->format('Y-m-d H:i:s');
                } else {
                    $data_row[] = (string)$val;
                }
            }
            fputcsv($output, $data_row);
        }
        sqlsrv_free_stmt($stmt);
    }
} else {
    // Error case - empty CSV
    fputcsv($output, ['No data found']);
}

fclose($output);
sqlsrv_close($conn);
exit;
?>

