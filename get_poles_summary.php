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

// Accept multiple polygons with town/barangay labels
$polygonsRaw = $_POST['polygons'] ?? ''; // Array of {town, brgy, polygon}

if (!$polygonsRaw) {
    echo json_encode(['error' => 'No polygons posted']);
    exit;
}

$polygons = json_decode($polygonsRaw, true);

if (!$polygons || !is_array($polygons)) {
    echo json_encode(['error' => 'Invalid polygons format']);
    exit;
}

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

// Get ALL poles
$sql = "SELECT 
            trn,
            type,
            TRY_CAST(gps_lat AS FLOAT) AS gps_lat,
            TRY_CAST(gps_long AS FLOAT) AS gps_long
        FROM tbl_elements
        WHERE gps_lat IS NOT NULL 
          AND gps_long IS NOT NULL
          AND gps_lat <> ''
          AND gps_long <> ''
          AND ISNUMERIC(gps_lat) = 1
          AND ISNUMERIC(gps_long) = 1";

if ($conn instanceof PDO) {
    $allPoles = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $res = sqlsrv_query($conn, $sql);
    $allPoles = [];
    if ($res) {
        while ($r = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
            $allPoles[] = $r;
        }
    }
}

// Count poles for each town/barangay polygon
$summary = [];

foreach ($polygons as $area) {
    $town = $area['town'] ?? 'Unknown';
    $brgy = $area['brgy'] ?? 'Unknown';
    $polygon = $area['polygon'] ?? [];
    
    if (count($polygon) < 3) continue;
    
    // Normalize polygon
    $normalizedPolygon = [];
    foreach ($polygon as $p) {
        if (isset($p['lat'], $p['lng'])) {
            $normalizedPolygon[] = ['lat' => floatval($p['lat']), 'lng' => floatval($p['lng'])];
        } elseif (isset($p[0], $p[1])) {
            $normalizedPolygon[] = ['lat' => floatval($p[0]), 'lng' => floatval($p[1])];
        }
    }
    
    $counts = [
        'primary' => 0,
        'secondary' => 0,
        'underbuilt' => 0,
        'transformer' => 0
    ];
    
    foreach ($allPoles as $pole) {
        $lat = floatval($pole['gps_lat']);
        $lng = floatval($pole['gps_long']);
        
        if (pointInPolygon($lat, $lng, $normalizedPolygon)) {
            $typeClean = strtolower(trim($pole['type']));
            
            if (strpos($typeClean, 'primary') !== false) {
                $counts['primary']++;
            } elseif (strpos($typeClean, 'secondary') !== false) {
                $counts['secondary']++;
            } elseif (strpos($typeClean, 'under') !== false) {
                $counts['underbuilt']++;
            } elseif (strpos($typeClean, 'transformer') !== false) {
                $counts['transformer']++;
            }
        }
    }
    
    $summary[] = [
        'town' => $town,
        'barangay' => $brgy,
        'primary_count' => $counts['primary'],
        'secondary_count' => $counts['secondary'],
        'underbuilt_count' => $counts['underbuilt'],
        'transformer_count' => $counts['transformer'],
        'total_poles' => $counts['primary'] + $counts['secondary'] + $counts['underbuilt']
    ];
}

echo json_encode([
    'success' => true,
    'summary' => $summary
]);