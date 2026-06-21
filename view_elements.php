<?php 
include 'conn.php';
include 'js/fphp.php';

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

// ── JSON mode ─────────────────────────────────────────────────────────────────
if (!empty($_POST['return_json'])) {
    header('Content-Type: application/json');

    $p_feeder    = $_POST['vfeeder']     ?? '';
    $filter_type = $_POST['filter_type'] ?? '';
    $rows        = [];

    if ($p_feeder === 'ALL' || $p_feeder === '') {
        $sql = "SELECT id, trn, feeder, type, icon, gps_lat, gps_long
                FROM tbl_elements
                WHERE gps_lat IS NOT NULL AND gps_lat <> ''
                  AND gps_long IS NOT NULL AND gps_long <> ''";
        $params = [];
        $sql .= filterTypeSQL($filter_type, $params);
        $stmt = empty($params)
            ? sqlsrv_query($conn, $sql)
            : sqlsrv_query($conn, $sql, $params);
    } else {
        $sql    = "SELECT id, trn, feeder, type, icon, gps_lat, gps_long
                   FROM tbl_elements
                   WHERE feeder = ?
                     AND gps_lat IS NOT NULL AND gps_lat <> ''
                     AND gps_long IS NOT NULL AND gps_long <> ''";
        $params = [$p_feeder];
        $sql   .= filterTypeSQL($filter_type, $params);
        $stmt   = sqlsrv_query($conn, $sql, $params);
    }

    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'id'       => $row['id'],
                'trn'      => $row['trn'],
                'feeder'   => $row['feeder'],
                'type'     => $row['type'],
                'icon'     => $row['icon'],
                'gps_lat'  => $row['gps_lat'],
                'gps_long' => $row['gps_long'],
            ];
        }
    }

    echo json_encode($rows);
    exit;
}

// ── Polygon filter ────────────────────────────────────────────────────────────
$polygonFilter = null;
if (!empty($_POST['vpolygon'])) {
    $polygonFilter = json_decode($_POST['vpolygon'], true);
    if (!empty($polygonFilter) && is_array($polygonFilter)) {
        if (isset($polygonFilter[0]) && is_array($polygonFilter[0])) {
            if (isset($polygonFilter[0][0]) && is_array($polygonFilter[0][0])) {
                // already multi-ring — leave as-is
            } else {
                $polygonFilter = [$polygonFilter];
            }
        }
    }
}

// ── HTML / JS output ──────────────────────────────────────────────────────────
echo "<script>document.getElementById('id01').style.display='none'</script>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['vfeeder']) || !empty($polygonFilter)) {

        $p_feeder    = $_POST['vfeeder']     ?? '';
        $filter_type = $_POST['filter_type'] ?? '';

        // Bounding box from polygon
        $bbox = null;
        if (!empty($polygonFilter)) {
            $allPoints = [];
            foreach ($polygonFilter as $ring) {
                foreach ($ring as $p) {
                    if (isset($p['lat'], $p['lng'])) {
                        $allPoints[] = $p;
                    }
                }
            }
            if (!empty($allPoints)) {
                $lats = array_column($allPoints, 'lat');
                $lngs = array_column($allPoints, 'lng');
                $bbox = [
                    'minLat' => min($lats), 'maxLat' => max($lats),
                    'minLng' => min($lngs), 'maxLng' => max($lngs),
                ];
            }
        }

        $base   = "SELECT gps_lat, gps_long, trn, icon, id, type, feeder
                   FROM tbl_elements
                   WHERE gps_lat IS NOT NULL AND gps_lat <> ''
                     AND gps_long IS NOT NULL AND gps_long <> ''";
        $params = [];

        if (!empty($p_feeder)) {
            $base    .= " AND feeder = ?";
            $params[] = $p_feeder;
        }

        if (!empty($bbox)) {
            $base    .= " AND TRY_CAST(gps_lat  AS FLOAT) BETWEEN ? AND ?
                          AND TRY_CAST(gps_long AS FLOAT) BETWEEN ? AND ?";
            $params[] = $bbox['minLat'];
            $params[] = $bbox['maxLat'];
            $params[] = $bbox['minLng'];
            $params[] = $bbox['maxLng'];
        }

        $base .= filterTypeSQL($filter_type, $params);

        $stmt = empty($params)
            ? sqlsrv_query($conn, $base)
            : sqlsrv_query($conn, $base, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $jmapdata  = "";
        $coordsArr = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $ulat = !empty($row["gps_lat"])  ? $row["gps_lat"]  : "0";
            $ulng = !empty($row["gps_long"]) ? $row["gps_long"] : "0";

            if (!empty($polygonFilter)) {
                $found = false;
                $lat   = (float)$ulat;
                $lng   = (float)$ulng;
                foreach ($polygonFilter as $ring) {
                    if (pointInPolygon($lat, $lng, $ring)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) continue;
            }

            $jmapdata .= "{";
            $jmapdata .= "     position: new google.maps.LatLng(" . $ulat . ", " . $ulng . "),";
            $jmapdata .= "     icon: \""   . addslashes($row["icon"])   . "\",";
            $jmapdata .= "     id: \""     . addslashes($row["id"])     . "\",";
            $jmapdata .= "     type: \""   . addslashes($row["type"])   . "\",";
            $jmapdata .= "     feeder: \"" . addslashes($row["feeder"]) . "\",";
            $jmapdata .= "     trn: "      . intval($row["trn"])        . ",";
            $jmapdata .= "},";

            if ((float)$ulat !== 0.0 && (float)$ulng !== 0.0) {
                $coordsArr[] = ['lat' => (float)$ulat, 'lng' => (float)$ulng];
            }
        }
    }
}
?>

<script>
    xfeatures = [
        <?php echo rtrim($jmapdata ?? '', ","); ?>
    ];
    viewElements(xfeatures);

    window.__feederCoords = <?php echo json_encode($coordsArr ?? []); ?>;
</script>