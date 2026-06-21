<?php
ob_start();
include 'conn.php';
include 'js/fphp.php';
ob_clean();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['search_customer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$trn = $_POST['trn'] ?? '';

if (!$trn) {
    echo json_encode(['success' => false, 'message' => 'Missing trn']);
    exit;
}

// ── Area-based pole delete enforcement ────────────────────────────
$user_level = $_SESSION['user_level'] ?? '';

$rSql  = "SELECT edit_elements_pole, edit_poles_central, edit_poles_northern,
                 edit_poles_eastern, edit_poles_southeastern, edit_poles_southern
           FROM tbl_userrights WHERE level = ?";
$rStmt = sqlsrv_query($conn, $rSql, [[$user_level, SQLSRV_PARAM_IN]]);
$rRow  = sqlsrv_fetch_array($rStmt, SQLSRV_FETCH_ASSOC);

$area_col_map = [
    'Central Area Office'      => 'edit_poles_central',
    'Northern Area Office'     => 'edit_poles_northern',
    'Eastern Area Office'      => 'edit_poles_eastern',
    'Southeastern Area Office' => 'edit_poles_southeastern',
    'Southern Area Office'     => 'edit_poles_southern',
];

// Collect ALL enabled areas
$user_areas = [];
foreach ($area_col_map as $area_name => $col) {
    if (!empty($rRow[$col])) {
        $user_areas[] = $area_name;
    }
}

$canEditAll = !empty($rRow['edit_elements_pole']);
$canEditOwn = !empty($user_areas);

if (!$canEditAll && !$canEditOwn) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// If area-restricted, verify pole location via point-in-polygon
if (!$canEditAll && $canEditOwn) {
    // Fetch pole coordinates
    $poleStmt = sqlsrv_query($conn,
        "SELECT TRY_CAST(gps_lat AS FLOAT) AS lat, TRY_CAST(gps_long AS FLOAT) AS lng
         FROM [db_cdms].[dbo].[tbl_elements] WHERE trn = ?",
        [[$trn, SQLSRV_PARAM_IN]]
    );
    $poleRow = sqlsrv_fetch_array($poleStmt, SQLSRV_FETCH_ASSOC);
    $poleLat = (float)($poleRow['lat'] ?? 0);
    $poleLng = (float)($poleRow['lng'] ?? 0);

    if ($poleLat === 0.0 && $poleLng === 0.0) {
        echo json_encode(['success' => false, 'message' => 'Cannot verify pole location.']);
        exit;
    }

    // Build groups the same way api_boundaries.php does
    $cacheKey = 'locate_boundary_groups';
    if (isset($_SESSION[$cacheKey])) {
        $groups = $_SESSION[$cacheKey];
    } else {
        $bSql  = "SELECT BarangayName, Town, AreaOffice, Latitude, Longitude
                  FROM dbo.boundaries
                  WHERE AreaOffice IS NOT NULL
                    AND LTRIM(RTRIM(AreaOffice)) <> ''
                  ORDER BY Town, BarangayName, PointOrder";
        $bStmt = sqlsrv_query($conn, $bSql);
        $groups = [];
        while ($bRow = sqlsrv_fetch_array($bStmt, SQLSRV_FETCH_ASSOC)) {
            $key = trim($bRow['Town']) . '||' . trim($bRow['BarangayName']);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'AreaOffice' => trim((string)($bRow['AreaOffice'] ?? '')),
                    'ring'       => [],
                ];
            }
            $groups[$key]['ring'][] = [
                'lat' => (float)$bRow['Latitude'],
                'lng' => (float)$bRow['Longitude'],
            ];
        }
        sqlsrv_free_stmt($bStmt);
        $_SESSION[$cacheKey] = $groups;
    }

    if (!function_exists('pointInPolygon')) {
        function pointInPolygon(float $lat, float $lng, array $ring): bool {
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
    }

    // Normalize user areas for comparison
    $userAreasLower = array_map(fn($a) => strtolower(trim($a)), $user_areas);

    $inside = false;
    foreach ($groups as $g) {
        $groupArea = strtolower(trim($g['AreaOffice']));
        if (!in_array($groupArea, $userAreasLower)) continue;
        if (pointInPolygon($poleLat, $poleLng, $g['ring'])) {
            $inside = true;
            break;
        }
    }

    if (!$inside) {
        echo json_encode(['success' => false,
            'message' => 'You can only delete poles in your assigned area(s): ' .
                         implode(', ', $user_areas) . '.']);
        exit;
    }
}
// ─────────────────────────────────────────────────────────────────

try {
    $sql    = "DELETE FROM [db_cdms].[dbo].[tbl_elements] WHERE trn = ?";
    $params = [[$trn]];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        throw new Exception('Delete failed: ' . json_encode(sqlsrv_errors()));
    }

    echo json_encode(['success' => true, 'message' => 'Deleted successfully']);

} catch (Exception $e) {
    error_log('[delete_element] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>