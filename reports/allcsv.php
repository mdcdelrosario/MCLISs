<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php
set_time_limit(0);

$p_route  = trim($_REQUEST['sroute']  ?? '');
$p_town   = trim($_REQUEST['stown']   ?? '');
$p_year   = trim($_REQUEST['syear']   ?? '');
$p_month  = trim($_REQUEST['smonth']  ?? '');
$p_day    = trim($_REQUEST['sday']    ?? '');
$p_qtag   = trim($_GET['qtag']        ?? '');

$p_month_padded = ($p_month != '') ? str_pad($p_month, 2, '0', STR_PAD_LEFT) : '';
$p_day_padded   = ($p_day   != '') ? str_pad($p_day,   2, '0', STR_PAD_LEFT) : '';

header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=CSV{$p_qtag}_{$p_town}_{$p_route}.csv");
header('Pragma: no-cache');
header('Expires: 0');

// 1. Column headers
$col_arr = [];
$sql  = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_cust' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);
$colno = 0;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $col_arr[] = $row["COLUMN_NAME"];
    echo '"' . $row["COLUMN_NAME"] . '",';
    $colno++;
}
sqlsrv_free_stmt($stmt);
echo "\n";

// 2. Date filter
$date_where  = "";
$date_params = [];

if ($p_year != '' && $p_month_padded != '' && $p_day_padded != '') {
    $date_where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
                  .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
                  .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
    $date_params[] = $p_year;
    $date_params[] = $p_month_padded;
    $date_params[] = $p_day_padded;
} elseif ($p_year != '' && $p_month_padded != '') {
    $date_where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
                  .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
    $date_params[] = $p_year;
    $date_params[] = $p_month_padded;
} elseif ($p_year != '') {
    $date_where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?";
    $date_params[] = $p_year;
} elseif ($p_month_padded != '' && $p_day_padded != '') {
    $date_where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
                  .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
    $date_params[] = $p_month_padded;
    $date_params[] = $p_day_padded;
} elseif ($p_month_padded != '') {
    $date_where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
    $date_params[] = $p_month_padded;
} elseif ($p_day_padded != '') {
    $date_where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
    $date_params[] = $p_day_padded;
}

// 3. Build WHERE
$filters            = "WHERE 1=1";
$params             = [];
$base_sqlrv_filters = "";

if ($p_route != '') {
    $base_sqlrv_filters .= " AND ACCOUNTNO LIKE ?";
    $params[]            = $p_route . '%';
}
if ($p_town !== '') {
    $base_sqlrv_filters .= " AND AREA = ?";
    $params[]            = $p_town;
}
if ($p_qtag == 'nogps') {
    $base_sqlrv_filters .= " AND (GPS_LAT IS NULL OR GPS_LAT = '') AND (GPS_LONG IS NULL OR GPS_LONG = '')";

} elseif ($p_qtag == 'disco') {
    $base_sqlrv_filters .= " AND [status] = 'D'";
} elseif ($p_qtag == 'btag') {
    $base_sqlrv_filters .= " AND btag IS NOT NULL AND btag != ''";
}

// If no filters at all, return empty
if ($p_route == '' && $p_town == '' && $p_qtag == '') {
    $filters .= " AND 1=2";
} else {
    $filters .= $base_sqlrv_filters;
    $filters .= $date_where;
    $params   = array_merge($params, $date_params);
}

// 4. Run query
$sql  = "SELECT * FROM tbl_cust $filters ORDER BY ACCOUNTNO";
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        for ($x = 0; $x < $colno; $x++) {
            $val = $row[$col_arr[$x]] ?? '';
            if ($val instanceof DateTime) {
                $val = $val->format('Y-m-d H:i:s');
            } else {
                $val = (string)$val;
            }
            echo '"' . str_replace('"', '""', $val) . '",';
        }
        echo "\n";
    }
    sqlsrv_free_stmt($stmt);
}

sqlsrv_close($conn);
?>