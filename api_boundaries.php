<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

/* ── Use the project connection ── */
include 'conn.php';  
include 'js/fphp.php'; // gives us $conn (sqlsrv resource)

/* ── Helpers ── */
function qstr(string $key): ?string {
    $v = $_GET[$key] ?? null;
    return ($v !== null && $v !== '') ? trim($v) : null;
}

// Converts Windows-1252 encoded strings (from SQL Server) to proper UTF-8
// This fixes special characters like ñ in barangay names (e.g. Castaño, Sto. Niño)
function toUtf8(?string $s): string {
    if ($s === null) return '';
    return mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
}

function send(mixed $data): void {
    if (ob_get_level() > 0) ob_end_clean();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'JSON encode failed: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
    exit;
}


function fail(string $msg, int $code = 400): void {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sqlsrv_err_str(): string {
    $e = sqlsrv_errors();
    return $e ? $e[0]['message'] : 'unknown error';
}

function boundary_query($conn, string $sql, array $params = []) {
    return $params ? sqlsrv_query($conn, $sql, $params) : sqlsrv_query($conn, $sql);
}

/* ── Route ── */
$action = trim($_GET['action'] ?? '');
if (!$action) fail('action required');

if ($action === 'area_offices') {
    $sql  = "SELECT DISTINCT LTRIM(RTRIM([AreaOffice])) AS AreaOffice
               FROM dbo.boundaries
              WHERE [AreaOffice] IS NOT NULL
                AND LTRIM(RTRIM([AreaOffice])) <> ''
              ORDER BY AreaOffice";
    $stmt = sqlsrv_query($conn, $sql);
    if (!$stmt) fail('Query failed: ' . sqlsrv_err_str(), 500);

    $offices = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $office = $row['AreaOffice'] ?? reset($row);
        if ($office !== false && trim((string)$office) !== '') {
            $offices[] = toUtf8(trim((string)$office));
        }
    }
    sqlsrv_free_stmt($stmt);
    send($offices);
}

/* ════════════════════════════════
   ACTION: towns
════════════════════════════════ */
if ($action === 'towns') {
    $areaOffice = qstr('area_office');
    $where = '';
    $params = [];
    if ($areaOffice) {
        $where = " WHERE UPPER([AreaOffice]) = UPPER(?)";
        $params = [[$areaOffice, SQLSRV_PARAM_IN]];
    }

    $sql  = "SELECT DISTINCT Town FROM dbo.boundaries{$where} ORDER BY Town";
    $stmt = boundary_query($conn, $sql, $params);
    if (!$stmt) fail('Query failed: ' . sqlsrv_err_str(), 500);

    $towns = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $towns[] = toUtf8(trim($row['Town']));
    }
    sqlsrv_free_stmt($stmt);
    send($towns);
}

/* ════════════════════════════════
   ACTION: barangays
════════════════════════════════ */
if ($action === 'barangays') {
    $town = qstr('town') ?? fail('town required');
    $areaOffice = qstr('area_office');

    $sql    = "SELECT DISTINCT BarangayName FROM dbo.boundaries
                WHERE UPPER(Town) = UPPER(?)";
    $params = [[$town, SQLSRV_PARAM_IN]];
    if ($areaOffice) {
        $sql .= " AND UPPER([AreaOffice]) = UPPER(?)";
        $params[] = [$areaOffice, SQLSRV_PARAM_IN];
    }
    $sql .= " ORDER BY BarangayName";
    $stmt   = sqlsrv_query($conn, $sql, $params);
    if (!$stmt) fail('Query failed: ' . sqlsrv_err_str(), 500);

    $brgys = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $brgys[] = toUtf8(trim($row['BarangayName']));
    }
    sqlsrv_free_stmt($stmt);
    send($brgys);
}

/* ════════════════════════════════
   ACTION: geojson
════════════════════════════════ */
if ($action === 'geojson') {
    $town = qstr('town');
    $brgy = qstr('brgy');
    $areaOffice = qstr('area_office');
    if ($brgy && !$town) fail('town required for barangay');

    if ($brgy) {
    // Convert UTF-8 ñ back to Windows-1252 for DB matching
    $brgyDb = mb_convert_encoding($brgy, 'Windows-1252', 'UTF-8');

    $sql    = "SELECT BarangayName, Town, [AreaOffice], Latitude, Longitude
                 FROM dbo.boundaries
                WHERE UPPER(Town) = UPPER(?) AND UPPER(BarangayName) = UPPER(?)
                ";
    $params = [[$town, SQLSRV_PARAM_IN], [$brgyDb, SQLSRV_PARAM_IN]];

    } else {
        $sql    = "SELECT BarangayName, Town, [AreaOffice], Latitude, Longitude
                     FROM dbo.boundaries
                    WHERE 1 = 1
                    ";
        $params = [];
        if ($town) {
            $sql .= " AND UPPER(Town) = UPPER(?)";
            $params[] = [$town, SQLSRV_PARAM_IN];
        }
    }

    if ($areaOffice) {
        $sql .= " AND UPPER([AreaOffice]) = UPPER(?)";
        $params[] = [$areaOffice, SQLSRV_PARAM_IN];
    }
    $sql .= $brgy ? " ORDER BY PointOrder" : " ORDER BY Town, BarangayName, PointOrder";

    $stmt = boundary_query($conn, $sql, $params);
    if (!$stmt) fail('Query failed: ' . sqlsrv_err_str(), 500);

    $groups = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $name = toUtf8(trim($row['BarangayName']));
        $featureTown = toUtf8(trim($row['Town']));
        $featureAreaOffice = toUtf8(trim((string)($row['AreaOffice'] ?? '')));
        $key = $featureTown . '||' . $name;
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'name' => $name,
                'town' => $featureTown,
                'areaOffice' => $featureAreaOffice,
                'coords' => [],
            ];
        }
        $groups[$key]['coords'][] = [(float)$row['Longitude'], (float)$row['Latitude']];
    }
    sqlsrv_free_stmt($stmt);

    if (empty($groups)) fail('No boundary data found', 404);

    $features = [];
    foreach ($groups as $group) {
        $coords = $group['coords'];
        // Close polygon ring
        if ($coords[0] !== end($coords)) $coords[] = $coords[0];
        $features[] = [
            'type'       => 'Feature',
            'properties' => [
                'barangayName' => $group['name'],
                'town' => $group['town'],
                'areaOffice' => $group['areaOffice'],
            ],
            'geometry'   => ['type' => 'Polygon', 'coordinates' => [$coords]],
        ];
    }

    send(['type' => 'FeatureCollection', 'features' => $features]);
}

/* ════════════════════════════════
   ACTION: locate  (point-in-polygon)
════════════════════════════════ */
if ($action === 'locate') {
    $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
    $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lat === null) fail('lat required');
    if ($lng === false || $lng === null) fail('lng required');

    // Use session cache to avoid reloading 49k boundary rows every click
    $cacheKey = 'locate_boundary_groups';
    if (isset($_SESSION[$cacheKey])) {
        $groups = $_SESSION[$cacheKey];
    } else {
        $sql  = "SELECT BarangayName, Town, AreaOffice, Latitude, Longitude
                 FROM dbo.boundaries
                 WHERE AreaOffice IS NOT NULL
                   AND LTRIM(RTRIM(AreaOffice)) <> ''
                 ORDER BY Town, BarangayName, PointOrder";
        $stmt = sqlsrv_query($conn, $sql);
        if (!$stmt) fail('Query failed: ' . sqlsrv_err_str(), 500);

        $groups = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $key = trim($row['Town']) . '||' . trim($row['BarangayName']);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'BarangayName' => toUtf8(trim($row['BarangayName'])),
                    'Town'         => toUtf8(trim($row['Town'])),
                    'AreaOffice'   => toUtf8(trim((string)($row['AreaOffice'] ?? ''))),
                    'ring'         => [],
                ];
            }
            $groups[$key]['ring'][] = [
                'lat' => (float)$row['Latitude'],
                'lng' => (float)$row['Longitude'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        $_SESSION[$cacheKey] = $groups; // cache for this session
    }

    function pipLocate(float $lat, float $lng, array $ring): bool {
        $inside = false;
        $n = count($ring);
        if ($n < 3) return false;
        $j = $n - 1;
        for ($i = 0; $i < $n; $j = $i++) {
            $iLat = $ring[$i]['lat']; $iLng = $ring[$i]['lng'];
            $jLat = $ring[$j]['lat']; $jLng = $ring[$j]['lng'];
            if ((($iLat > $lat) !== ($jLat > $lat)) &&
                ($lng < ($jLng - $iLng) * ($lat - $iLat) / ($jLat - $iLat) + $iLng)) {
                $inside = !$inside;
            }
        }
        return $inside;
    }

    foreach ($groups as $g) {
        if (pipLocate($lat, $lng, $g['ring'])) {
            send([
                'BarangayName' => $g['BarangayName'],
                'Town'         => $g['Town'],
                'AreaOffice'   => $g['AreaOffice'],
            ]);
        }
    }

    send(null);
}

fail('Unknown action');
