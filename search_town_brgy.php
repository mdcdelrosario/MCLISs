<?php
include 'conn.php';

$town = trim($_GET['vtown'] ?? '');
$brgy = trim($_GET['vbrgy'] ?? '');

if ($town === '') die('No town');

echo "<div style='padding:10px;font-size:12px;'>";
echo "<h3>" . htmlspecialchars($town) . ($brgy ? ' - ' . htmlspecialchars($brgy) : '') . "</h3>";

$like_town = '%' . strtoupper($town) . '%';
if ($brgy) {
    $like_brgy = '%' . strtoupper($brgy) . '%';
    $sql = "SELECT ACCOUNTNO, NAME, ADDRESS, STATUS, METERNO FROM tbl_cust WHERE (UPPER(ADDRESS) LIKE ? OR UPPER(NAME) LIKE ?) AND UPPER(ADDRESS) LIKE ? ORDER BY NAME AND GPS_LAT BETWEEN -90 AND 90 AND GPS_LONG BETWEEN -180 AND 180";
    $params = [$like_town . ' ' . $like_brgy, $like_brgy . ' ' . $like_town, $like_town];
} else {
    $sql = "SELECT ACCOUNTNO, NAME, ADDRESS, STATUS, METERNO FROM tbl_cust WHERE UPPER(ADDRESS) LIKE ? OR UPPER(NAME) LIKE ? ORDER BY NAME AND GPS_LAT BETWEEN -90 AND 90 AND GPS_LONG BETWEEN -180 AND 180";
    $params = [$like_town, $like_town];
}

$stmt = sqlsrv_query($conn, $sql, $params);
if (!$stmt) {
    echo 'Error: ' . print_r(sqlsrv_errors(), true);
    exit;
}

$count = 0;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $count++;
    echo "<div onclick=\"loadCustInfo('{$row['ACCOUNTNO']}')\" style='padding:8px;border-bottom:1px solid #eee;cursor:pointer;'>
        <b>{$row['NAME']}</b> ({$row['ACCOUNTNO']})<br>
        {$row['ADDRESS']} - {$row['METERNO']} ({$row['STATUS']})
    </div>";
}
echo "<p><b>Total: $count customers</b></p>";

sqlsrv_free_stmt($stmt);

// Map markers
if(count){
    $sql_map = "SELECT ACCOUNTNO, GPS_LAT, GPS_LONG FROM tbl_cust WHERE (UPPER(ADDRESS) LIKE ? OR UPPER(NAME) LIKE ?) AND UPPER(ADDRESS) LIKE ? AND GPS_LAT BETWEEN -90 AND 90 AND GPS_LONG BETWEEN -180 AND 180";
    $stmt_map = sqlsrv_query($conn, $sql_map, $params);
    if ($stmt_map) {
        echo "<script>var mapMarkers = [];</script>";
        while ($row_map = sqlsrv_fetch_array($stmt_map, SQLSRV_FETCH_ASSOC)) {
            $lat = $row_map['GPS_LAT'];
            $long = $row_map['GPS_LONG'];
            if (is_numeric($lat) && is_numeric($long)) {
                echo "<script>mapMarkers.push({account: '{$row_map['ACCOUNTNO']}', lat: {$lat}, long: {$long}});</script>";
            }
        }
        sqlsrv_free_stmt($stmt_map);
    } else {
        echo 'Error fetching map data: ' . print_r(sqlsrv_errors(), true);
    }
}
?>

