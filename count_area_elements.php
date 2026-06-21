<?php
ini_set('display_errors', 0);   // don't leak HTML errors into JSON stream
ini_set('log_errors', 1);
set_time_limit(120);             // was likely 30s default
ini_set('memory_limit', '512M');

if (session_status() === PHP_SESSION_NONE) session_start();

ob_start();
include 'conn.php';
ob_end_clean(); // discard any output from conn.php

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['search_customer'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

function toUtf8_ae(?string $s): string {
    if ($s === null) return '';
    return mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
}

function sqlsrv_err_ae(): string {
    $e = sqlsrv_errors();
    return $e ? $e[0]['message'] : 'unknown error';
}

function sendJson(mixed $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function normaliseIcon(string $raw): string {
    $v = strtolower(trim($raw));
    if ($v === 'primary'     || $v === 'ppole.png')   return 'primary';
    if ($v === 'secondary'   || $v === 'spole.png')   return 'secondary';
    if ($v === 'underbuilt'  || $v === 'upole.png')   return 'underbuilt';
    if ($v === 'transformer' || $v === 'xformer.png') return 'transformer';
    if (strpos($v, 'primary')   !== false) return 'primary';
    if (strpos($v, 'secondary') !== false) return 'secondary';
    if (strpos($v, 'under')     !== false) return 'underbuilt';
    if (strpos($v, 'transform') !== false) return 'transformer';
    return $v;
}

function pointInPolygon(float $lat, float $lng, array $ring): bool {
    $inside = false;
    $n = count($ring);
    $j = $n - 1;
    for ($i = 0; $i < $n; $j = $i++) {
        $iLat = (float)($ring[$i]['lat'] ?? 0);
        $iLng = (float)($ring[$i]['lng'] ?? 0);
        $jLat = (float)($ring[$j]['lat'] ?? 0);
        $jLng = (float)($ring[$j]['lng'] ?? 0);
        if ((($iLat > $lat) !== ($jLat > $lat)) &&
            ($lng < ($jLng - $iLng) * ($lat - $iLat) / ($jLat - $iLat) + $iLng)) {
            $inside = !$inside;
        }
    }
    return $inside;
}

$area     = trim($_POST['area_office'] ?? '');
$elemType = trim($_POST['filter_type'] ?? '');

/* ── Type filter ── */
$typeFilter = '';
$typeParams = [];
if ($elemType !== '') {
    $iconMap = [
        'primary'     => ['primary',     'ppole.png'],
        'secondary'   => ['secondary',   'spole.png'],
        'underbuilt'  => ['underbuilt',  'upole.png'],
        'transformer' => ['transformer', 'xformer.png'],
    ];
    $variants = $iconMap[strtolower($elemType)] ?? [strtolower($elemType)];
    $ph = implode(',', array_fill(0, count($variants), '?'));
    $typeFilter = " AND LOWER(LTRIM(RTRIM(e.icon))) IN ($ph)";
    foreach ($variants as $v) $typeParams[] = [$v, SQLSRV_PARAM_IN];
}

/* ── Bounding box from boundaries table ── */
$bboxParams = [];
if ($area !== '') {
    $bboxSql    = "SELECT MIN(CAST(Latitude AS FLOAT)) AS minLat,
                          MAX(CAST(Latitude AS FLOAT)) AS maxLat,
                          MIN(CAST(Longitude AS FLOAT)) AS minLng,
                          MAX(CAST(Longitude AS FLOAT)) AS maxLng
                   FROM dbo.boundaries
                   WHERE UPPER(LTRIM(RTRIM(AreaOffice))) = UPPER(?)";
    $bboxParams = [[$area, SQLSRV_PARAM_IN]];
} else {
    $bboxSql    = "SELECT MIN(CAST(Latitude AS FLOAT)) AS minLat,
                          MAX(CAST(Latitude AS FLOAT)) AS maxLat,
                          MIN(CAST(Longitude AS FLOAT)) AS minLng,
                          MAX(CAST(Longitude AS FLOAT)) AS maxLng
                   FROM dbo.boundaries";
}

$bboxStmt = $bboxParams
    ? sqlsrv_query($conn, $bboxSql, $bboxParams)
    : sqlsrv_query($conn, $bboxSql);

if (!$bboxStmt) sendJson(['error' => 'Bbox query failed: ' . sqlsrv_err_ae()]);

$bbox = sqlsrv_fetch_array($bboxStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($bboxStmt);

if (!$bbox || $bbox['minLat'] === null) {
    sendJson(['error' => 'No boundary data found for: ' . ($area ?: 'all areas')]);
}

$minLat = (float)$bbox['minLat'];
$maxLat = (float)$bbox['maxLat'];
$minLng = (float)$bbox['minLng'];
$maxLng = (float)$bbox['maxLng'];

/* ── Fetch poles within bounding box ── */
$elParams = array_merge(
    [
        [$minLat, SQLSRV_PARAM_IN],
        [$maxLat, SQLSRV_PARAM_IN],
        [$minLng, SQLSRV_PARAM_IN],
        [$maxLng, SQLSRV_PARAM_IN],
    ],
    $typeParams
);

$elSql = "
    SELECT e.trn,
           LOWER(LTRIM(RTRIM(e.icon))) AS icon,
           e.feeder, e.type,
           TRY_CAST(e.gps_lat  AS FLOAT) AS lat,
           TRY_CAST(e.gps_long AS FLOAT) AS lng
    FROM   tbl_elements e
    WHERE  e.gps_lat  IS NOT NULL AND LTRIM(RTRIM(e.gps_lat))  <> ''
      AND  e.gps_long IS NOT NULL AND LTRIM(RTRIM(e.gps_long)) <> ''
      AND  TRY_CAST(e.gps_lat  AS FLOAT) BETWEEN ? AND ?
      AND  TRY_CAST(e.gps_long AS FLOAT) BETWEEN ? AND ?
      $typeFilter
";

$elStmt = sqlsrv_query($conn, $elSql, $elParams);
if (!$elStmt) sendJson(['error' => 'Elements query failed: ' . sqlsrv_err_ae()]);

/* ── Load boundary rings for specific area (with session cache) ── */
$rings = [];
if ($area !== '') {
    $cacheKey = 'boundary_rings_' . md5($area);
    if (isset($_SESSION[$cacheKey])) {
        $rings = $_SESSION[$cacheKey];
    } else {
        $rSql  = "SELECT BarangayName, Town, Latitude, Longitude
                  FROM dbo.boundaries
                  WHERE UPPER(LTRIM(RTRIM(AreaOffice))) = UPPER(?)
                  ORDER BY Town, BarangayName, PointOrder";
        $rStmt = sqlsrv_query($conn, $rSql, [[$area, SQLSRV_PARAM_IN]]);
        if ($rStmt) {
            $groups = [];
            while ($row = sqlsrv_fetch_array($rStmt, SQLSRV_FETCH_ASSOC)) {
                $key = trim($row['Town']) . '||' . trim($row['BarangayName']);
                $groups[$key][] = ['lat' => (float)$row['Latitude'], 'lng' => (float)$row['Longitude']];
            }
            sqlsrv_free_stmt($rStmt);

            // Build rings with bounding boxes for cheap bbox pre-check
            foreach ($groups as $points) {
                $lats = array_column($points, 'lat');
                $lngs = array_column($points, 'lng');
                $rings[] = [
                    'points' => $points,
                    'minLat' => min($lats),
                    'maxLat' => max($lats),
                    'minLng' => min($lngs),
                    'maxLng' => max($lngs),
                ];
            }

            $_SESSION[$cacheKey] = $rings;
        }
    }
}

session_write_close(); // release session lock before heavy processing

/* ── Process poles ── */
$counts_map = [];
$poles      = [];
$grand      = 0;

while ($row = sqlsrv_fetch_array($elStmt, SQLSRV_FETCH_ASSOC)) {
    $lat = (float)$row['lat'];
    $lng = (float)$row['lng'];
    if ($lat === 0.0 && $lng === 0.0) continue;

    if ($area !== '' && !empty($rings)) {
        $inside = false;
        foreach ($rings as $ring) {
            // Cheap bbox check first — skip expensive polygon test if outside bounding box
            if ($lat < $ring['minLat'] || $lat > $ring['maxLat'] ||
                $lng < $ring['minLng'] || $lng > $ring['maxLng']) continue;
            if (pointInPolygon($lat, $lng, $ring['points'])) { $inside = true; break; }
        }
        if (!$inside) continue;
    }

    $icon = normaliseIcon($row['icon'] ?? '');
    $counts_map[$icon] = ($counts_map[$icon] ?? 0) + 1;
    $grand++;

    $poles[] = [
        'trn'    => $row['trn'],
        'lat'    => $lat,
        'lng'    => $lng,
        'icon'   => $icon,
        'feeder' => toUtf8_ae($row['feeder'] ?? ''),
        'type'   => toUtf8_ae($row['type']   ?? ''),
    ];
}
sqlsrv_free_stmt($elStmt);

ksort($counts_map);
$counts = [];
foreach ($counts_map as $icon => $total) $counts[] = ['icon' => $icon, 'total' => $total];
$counts[] = ['icon' => 'Total', 'total' => $grand];

sendJson([
    'success' => true,
    'area'    => $area ?: 'All Area Offices',
    'counts'  => $counts,
    'poles'   => $poles,
]);