<?php
ob_start();
include 'conn.php';
$garbage = ob_get_clean();

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['search_customer'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$town    = $_POST['vtown']    ?? '';
$brgy    = $_POST['vbrgy']    ?? '';
$polyRaw = $_POST['vpolygon'] ?? '';

if (!$polyRaw) {
    echo json_encode(['error' => 'No polygon posted']);
    exit;
}

$rings = json_decode($polyRaw, true);
if (!$rings || !isset($rings[0])) {
    echo json_encode(['error' => 'Bad polygon JSON']);
    exit;
}

$allRings = [];
foreach ($rings as $ring) {
    $parsedRing = [];
    foreach ($ring as $p) {
        if (isset($p['lat'], $p['lng'])) {
            $parsedRing[] = ['lat' => floatval($p['lat']), 'lng' => floatval($p['lng'])];
        } elseif (isset($p[0], $p[1])) {
            $parsedRing[] = ['lat' => floatval($p[0]), 'lng' => floatval($p[1])];
        }
    }
    if (count($parsedRing) >= 3) {
        $allRings[] = $parsedRing;
    }
}

if (empty($allRings)) {
    echo json_encode(['error' => 'No valid polygon rings found']);
    exit;
}

foreach ($allRings as $ring) {
    if (count($ring) > 1000) {
        echo json_encode(['error' => 'Polygon too complex (max 1000 points)']);
        exit;
    }
    foreach ($ring as $p) {
        if ($p['lat'] < -90 || $p['lat'] > 90 || $p['lng'] < -180 || $p['lng'] > 180) {
            echo json_encode(['error' => 'Invalid coordinates detected']);
            exit;
        }
    }
}

$allLats = [];
$allLngs = [];
foreach ($allRings as $ring) {
    foreach ($ring as $p) {
        $allLats[] = $p['lat'];
        $allLngs[] = $p['lng'];
    }
}
$minLat = min($allLats);
$maxLat = max($allLats);
$minLng = min($allLngs);
$maxLng = max($allLngs);

function pointInPolygon($lat, $lng, $polygon) {
    $inside = false;
    $n = count($polygon);
    $j = $n - 1;
    for ($i = 0; $i < $n; $j = $i++) {
        $iLat = $polygon[$i]['lat'];
        $iLng = $polygon[$i]['lng'];
        $jLat = $polygon[$j]['lat'];
        $jLng = $polygon[$j]['lng'];
        if ((($iLat > $lat) !== ($jLat > $lat)) &&
            ($lng < ($jLng - $iLng) * ($lat - $iLat) / ($jLat - $iLat) + $iLng)) {
            $inside = !$inside;
        }
    }
    return $inside;
}

function filterTypeSQL($filter_type, &$params) {
    if (!$filter_type) return '';

    $f = strtolower($filter_type);

    if ($f === 'underbuilt') {
        return " AND LOWER(type) LIKE '%under built%'";
    }
    if ($f === 'transformer') {
        return " AND LOWER(type) = 'transformer'";
    }
    if ($f === 'primary') {
        return " AND LOWER(type) = 'primary pole'";
    }
    if ($f === 'secondary') {
        return " AND LOWER(type) = 'secondary pole'";
    }

    $params[] = '%' . $filter_type . '%';
    return " AND LOWER(type) LIKE ?";
}

function runQuery($conn, $sql, $params = []) {
    if ($conn instanceof PDO) {
        if (empty($params)) {
            $res = $conn->query($sql);
            return $res ? $res->fetchAll(PDO::FETCH_ASSOC) : [];
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        if (empty($params)) {
            $res = sqlsrv_query($conn, $sql);
        } else {
            $res = sqlsrv_query($conn, $sql, $params);
        }
        $rows = [];
        if ($res) {
            while ($r = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $r;
            }
        }
        return $rows;
    }
}

$filter_type = $_POST['filter_type'] ?? '';

$sqlElem = "SELECT 
                TRY_CAST(gps_lat AS FLOAT) AS gps_lat,
                TRY_CAST(gps_long AS FLOAT) AS gps_long,
                type
            FROM tbl_elements
            WHERE gps_lat IS NOT NULL 
              AND gps_long IS NOT NULL
              AND gps_lat <> ''
              AND gps_long <> ''
              AND ISNUMERIC(gps_lat) = 1
              AND ISNUMERIC(gps_long) = 1
              AND TRY_CAST(gps_lat AS FLOAT) BETWEEN ? AND ?
              AND TRY_CAST(gps_long AS FLOAT) BETWEEN ? AND ?";

$params = [$minLat, $maxLat, $minLng, $maxLng];
$sqlElem .= filterTypeSQL($filter_type, $params);

$elemRows = runQuery($conn, $sqlElem, $params);

$primary     = 0;
$secondary   = 0;
$underbuilt  = 0;
$transformer = 0;
$counted     = [];  // ← tracks already-counted coordinates

foreach ($elemRows as $r) {
    $lat = floatval($r['gps_lat']);
    $lng = floatval($r['gps_long']);

    // Skip bad coordinates
    if ($lat == 0.0 || $lng == 0.0) continue;
    if ($lat < 4 || $lat > 22 || $lng < 116 || $lng > 128) continue;

    // Skip if this exact coordinate was already counted
    $key = $lat . ',' . $lng;
    if (isset($counted[$key])) continue;

    $found = false;
    foreach ($allRings as $ring) {
        if (pointInPolygon($lat, $lng, $ring)) {
            $found = true;
            break;
        }
    }
    if (!$found) continue;

    $counted[$key] = true;  // ← mark as counted before incrementing

    $typeClean = strtolower(trim($r['type']));

    switch ($typeClean) {
        case 'primary pole':   $primary++;     break;
        case 'secondary pole': $secondary++;   break;
        case 'under built':    $underbuilt++;  break;
        case 'transformer':    $transformer++; break;
    }
}

$totalPoles = $primary + $secondary + $underbuilt;
$sampled_types = array_unique(array_column($elemRows, 'type'));

echo json_encode([
    'success'     => true,
    'filter_type' => $filter_type ?: 'All Types',
    'counts'      => [
        ['icon' => 'Primary poles',    'total' => $primary],
        ['icon' => 'Secondary poles',  'total' => $secondary],
        ['icon' => 'Underbuilt poles', 'total' => $underbuilt],
        ['icon' => 'Transformers',     'total' => $transformer],
        ['icon' => 'Total poles',      'total' => $totalPoles],
    ],
    'town' => $town,
    'brgy' => $brgy,
    '_debug' => [
        'rings_count'      => count($allRings),
        'polygon_points'   => array_sum(array_map('count', $allRings)),
        'total_candidates' => count($elemRows),
        'sampled_types'    => array_slice($sampled_types, 0, 20),
    ],
]);