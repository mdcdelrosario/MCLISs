<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php
$today = date("m/d/yy, h:i:s");
set_time_limit(0);

$p_route  = trim($_REQUEST['sroute']  ?? '');
$p_town   = trim($_REQUEST['stown']   ?? '');
$p_year   = trim($_REQUEST['syear']   ?? '');
$p_month  = trim($_REQUEST['smonth']  ?? '');
$p_day    = trim($_REQUEST['sday']    ?? '');

$p_month_padded = ($p_month != '') ? str_pad($p_month, 2, '0', STR_PAD_LEFT) : '';
$p_day_padded   = ($p_day   != '') ? str_pad($p_day,   2, '0', STR_PAD_LEFT) : '';

header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=JAED{$p_town}{$p_route}.txt");
header('Pragma: no-cache');
header('Expires: 0');

$params = [];
$where  = " AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";

if ($p_year != '' && $p_month_padded != '' && $p_day_padded != '') {
    $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
             .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
             .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
    $params[] = $p_year; $params[] = $p_month_padded; $params[] = $p_day_padded;
} elseif ($p_year != '' && $p_month_padded != '') {
    $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
             .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
    $params[] = $p_year; $params[] = $p_month_padded;
} elseif ($p_year != '') {
    $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?";
    $params[] = $p_year;
} elseif ($p_month_padded != '' && $p_day_padded != '') {
    $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
             .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
    $params[] = $p_month_padded; $params[] = $p_day_padded;
} elseif ($p_month_padded != '') {
    $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
    $params[] = $p_month_padded;
} elseif ($p_day_padded != '') {
    $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
    $params[] = $p_day_padded;
}

if ($p_route != '') {
    array_unshift($params, $p_route . '%');
    $sql = "SELECT * FROM tbl_cust WHERE ACCOUNTNO LIKE ? $where";
} elseif ($p_town != '') {
    array_unshift($params, $p_town);
    $sql = "SELECT * FROM tbl_cust WHERE AREA LIKE ? $where";
} else {
    $sql = "SELECT * FROM tbl_cust WHERE 1=2";
}

echo "Version,212\n\n";
echo "WGS 1984 (GPS),217, 6378137, 298.257223563, 0, 0, 0\n";
echo "USER GRID,0,0,0,0,0\n\n";

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // ✅ Removed the GPS_LAT/GPS_LONG empty check — include ALL records
        $gps_lat  = $row['GPS_LAT']  ?? '0';
        $gps_long = $row['GPS_LONG'] ?? '0';
        $meterno  = $row['METERNO']  ?? '';

        echo "w,d,WAYPOINT,";
        echo $gps_lat . ",";
        echo $gps_long . ",";
        echo "AN" . trim($row['AREA']) . trim($row['ACCOUNTNO']) . ",";
        echo $today . ",";
        echo "0,2,48,0,13,";
        echo trim($meterno) . "\n"; // ✅ METERNO appended
    }
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);
?>