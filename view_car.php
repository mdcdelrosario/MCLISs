<?php
include 'conn.php';
header('Content-Type: application/json');

$sql = "
    SELECT
        plateno,
        tracker_id,
        gps_lat,
        gps_long,
        gps_time,
        crew,
        central_area_office,
        northern_area_office,
        eastern_area_office,
        southeastern_area_office,
        southern_area_office,
        engineering,
        general_manager,
        admin,
        main_area_office
    FROM tbl_crew
    WHERE TRY_CAST(gps_lat  AS FLOAT) IS NOT NULL
      AND TRY_CAST(gps_long AS FLOAT) IS NOT NULL
      AND TRY_CAST(gps_lat  AS FLOAT) != 0
      AND TRY_CAST(gps_long AS FLOAT) != 0
";

$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

function getAreaLabel(array $row): string {
    $map = [
        'central_area_office'      => 'Central',
        'northern_area_office'     => 'Northern',
        'eastern_area_office'      => 'Eastern',
        'southeastern_area_office' => 'Southeastern',
        'southern_area_office'     => 'Southern',
        'main_area_office'         => 'Main',
        'engineering'              => 'Engineering',
        'general_manager'          => 'General Manager',
        'admin'                    => 'Admin',
    ];
    foreach ($map as $col => $label) {
        if ((int)($row[$col] ?? 0) === 1) {
            return $label;;
        }    
    }
    return '—';
}

function formatGpsTime($val): string {
    if ($val instanceof DateTime) return $val->format('M d, H:i');
    if (!empty($val)) {
        // Try to parse string datetime
        try {
            $dt = new DateTime($val);
            return $dt->format('M d, H:i');
        } catch (Exception $e) {
            return (string)$val;
        }
    }
    return '—';
}

$vehicles = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $plate = trim($row['plateno'] ?? '');
    if (!$plate) continue;

    if (!isset($vehicles[$plate])) {
        $vehicles[$plate] = [
            'plate'   => $plate,
            'lat'     => (float)($row['gps_lat']  ?? 0),
            'lng'     => (float)($row['gps_long'] ?? 0),
            'time'    => formatGpsTime($row['gps_time']),
            'area'    => getAreaLabel($row),
            'crew'    => [],
        ];
    }

    $name = trim($row['crew'] ?? '');
    if ($name && !in_array($name, $vehicles[$plate]['crew'])) {
        $vehicles[$plate]['crew'][] = $name;
    }
}

sqlsrv_free_stmt($stmt);
echo json_encode(array_values($vehicles), JSON_UNESCAPED_UNICODE);
?>