<?php include 'conn.php';?>
<?php include 'js/fphp.php';?>

<script>document.getElementById('id01').style.display='none'</script>

<?php
    $p_search = "999999999";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $p_search = htmlspecialchars($_POST['vsearch']   ?? '', ENT_QUOTES, 'UTF-8');
        $p_tag    = htmlspecialchars($_POST['vtag']      ?? '', ENT_QUOTES, 'UTF-8');
        $p_area   = htmlspecialchars($_POST['varea']     ?? '', ENT_QUOTES, 'UTF-8');
        $p_sff    = htmlspecialchars($_POST['sff']       ?? '', ENT_QUOTES, 'UTF-8');
        $p_btag   = htmlspecialchars($_POST['btag']      ?? '', ENT_QUOTES, 'UTF-8');
        $p_poly   = $_POST['vpolygon'] ?? '';   // JSON polygon sent by JS

        /* ══════════════════════════════════════════════════════════════
           POINT-IN-POLYGON  (Ray-casting algorithm)
           Accepts a polygon as an array of {lat, lng} objects and a
           point {lat, lng}. Returns true if the point is inside.
        ══════════════════════════════════════════════════════════════ */
        function pointInPolygon(array $polygon, float $lat, float $lng): bool {
            $count  = count($polygon);
            $inside = false;
            $j      = $count - 1;

            for ($i = 0; $i < $count; $j = $i++) {
                $xi = (float)$polygon[$i]['lng'];
                $yi = (float)$polygon[$i]['lat'];
                $xj = (float)$polygon[$j]['lng'];
                $yj = (float)$polygon[$j]['lat'];

                $intersect = (($yi > $lat) !== ($yj > $lat))
                          && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

                if ($intersect) $inside = !$inside;
            }

            return $inside;
        }

        /**
         * Tests a point against an array of polygon rings (multi-polygon).
         * Returns true if the point is inside ANY of the rings.
         */
        function pointInAnyPolygon(array $rings, float $lat, float $lng): bool {
            foreach ($rings as $ring) {
                if (pointInPolygon($ring, $lat, $lng)) return true;
            }
            return false;
        }

        /* ── Parse polygon JSON posted from JS ── */
        $polygonRings = [];   // array of rings; each ring = [{lat,lng}, ...]
        if ($p_tag === 'BG' && $p_poly !== '') {
            $decoded = json_decode($p_poly, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Support both a single ring [{lat,lng},...] and
                // multiple rings [[{lat,lng},...], [{lat,lng},...]]
                if (isset($decoded[0]['lat'])) {
                    // Single ring sent directly
                    $polygonRings = [$decoded];
                } else {
                    // Array of rings
                    $polygonRings = $decoded;
                }
            }
        }

        /* ══════════════════════════════════════════════════════════════
           QUERIES
        ══════════════════════════════════════════════════════════════ */
        $sql    = "SELECT * FROM tbl_cust WHERE 1=2";   // safe default
        $params = [];

        if ($p_tag == "NS" && strlen($p_search) >= 4) {
            $sql = "SELECT * FROM tbl_cust 
                    WHERE (accountno LIKE ? OR name LIKE ?) 
                    AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())
                    AND TRY_CONVERT(float, GPS_LONG) > 100";
            $params = ["%$p_search%", "%" . strtoupper(str_replace(" ", "%", $p_search)) . "%"];
        }

        if ($p_tag == "NL") {
            $sql = "SELECT * FROM tbl_cust 
                    WHERE RDG_DTIME > DATEADD(DAY, -1095, GETDATE()) 
                    AND TRY_CONVERT(float, GPS_LONG) < 100";
        }

        if ($p_tag == "HL") {
            $sql = "SELECT * FROM tbl_cust 
                    WHERE (TRY_CONVERT(float, kwhu) > (TRY_CONVERT(float, avecons) * 1.5)
                       OR TRY_CONVERT(float, kwhu) < (TRY_CONVERT(float, avecons) * 0.5)) 
                    AND TRY_CONVERT(float, avecons) > 20 
                    AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
            $params = [];
            if (!empty($p_area)) {
                $sql .= " AND area = ?";
                $params[] = $p_area;
            }
        }

        if ($p_tag == "TK") {
            $sql = "SELECT TOP 100 * FROM tbl_cust 
                    WHERE RDG_DTIME > DATEADD(DAY, -1095, GETDATE()) 
                    AND TRY_CONVERT(float, kwhu) IS NOT NULL";
            $params = [];
            if (!empty($p_area)) {
                $sql .= " AND area = ?";
                $params[] = $p_area;
            }
            $sql .= " ORDER BY TRY_CONVERT(float, kwhu) DESC";
        }

        if ($p_tag == "TD") {
            $sql = "SELECT TOP 100 * FROM tbl_cust 
                    WHERE RDG_DTIME > DATEADD(DAY, -1095, GETDATE()) 
                    AND TRY_CONVERT(float, demand) IS NOT NULL";
            $params = [];
            if (!empty($p_area)) {
                $sql .= " AND area = ?";
                $params[] = $p_area;
            }
            $sql .= " ORDER BY TRY_CONVERT(float, demand) DESC";
        }

        if ($p_tag == "FF") {
            $sql = "SELECT * FROM tbl_cust 
                    WHERE FFDESC = ? 
                    AND area = ? 
                    AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
            $params = [$p_sff, $p_area];
        }

        if ($p_tag == "DC") {
            $sql = "SELECT * FROM tbl_cust 
                    WHERE status != 'A' 
                    AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
            $params = [];
            if (!empty($p_area)) {
                $sql .= " AND area = ?";
                $params[] = $p_area;
            }
        }

        // TG — fixed (area optional)
        if ($p_tag == "TG") {
            if (!empty($p_btag)) {
                if (!empty($p_area)) {
                    $sql = "SELECT * FROM tbl_cust 
                            WHERE btag LIKE ? 
                            AND area = ? 
                            AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
                    $params = ["%$p_btag%", $p_area];
                } else {
                    // No area selected — search all areas
                    $sql = "SELECT * FROM tbl_cust 
                            WHERE btag LIKE ? 
                            AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
                    $params = ["%$p_btag%"];
                }
            }
        }

        // ATG — fixed (area optional)
        if ($p_tag == "ATG") {
            if (!empty($p_area)) {
                $sql = "SELECT * FROM tbl_cust 
                        WHERE btag != '' 
                        AND area = ? 
                        AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
                $params = [$p_area];
            } else {
                // No area selected — show all tagged across all areas
                $sql = "SELECT * FROM tbl_cust 
                        WHERE btag != '' 
                        AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())";
                $params = [];
            }
        }

        // Add near the top with the other POST vars
$p_feeder_search = htmlspecialchars($_POST['vfeeder_search'] ?? '', ENT_QUOTES, 'UTF-8');

// Add the FD query block with the others
if ($p_tag == "FD") {
    if (empty($p_feeder_search)) {
        echo '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> No feeder specified.</div>';
        exit;
    }
    $sql = "SELECT * FROM tbl_cust
            WHERE feeder = ?
            AND RDG_DTIME > DATEADD(DAY, -1095, GETDATE())
            AND TRY_CONVERT(float, GPS_LONG) > 100";
    $params = [$p_feeder_search];
}

        /* ══════════════════════════════════════════════════════════════
           BG — Boundary / Barangay / Town spatial search
           Fetch all customers that have a valid GPS location, then
           filter in PHP using ray-cast point-in-polygon.
        ══════════════════════════════════════════════════════════════ */
        if ($p_tag == "BG") {
            if (empty($polygonRings)) {
                // Polygon coords were not sent — show a friendly error
                echo '<div style="color:#c62828;padding:8px;">'
                   . '<i class="fa fa-exclamation-triangle"></i> '
                   . 'Boundary data not available. '
                   . 'Please select a Town first so the boundary can load, then try again.'
                   . '</div>';
                exit;
            }

            // Pull every customer that has a valid GPS coordinate.
            // The bounding box of the polygon is used as a fast pre-filter
            // so we don't drag every single row through ray-casting.
            $allLat = []; $allLng = [];
            foreach ($polygonRings as $ring) {
                foreach ($ring as $pt) {
                    $allLat[] = (float)$pt['lat'];
                    $allLng[] = (float)$pt['lng'];
                }
            }
            $minLat = min($allLat); $maxLat = max($allLat);
            $minLng = min($allLng); $maxLng = max($allLng);

            // Bounding-box pre-filter in SQL, exact ray-cast done in PHP below
            $sql = "SELECT * FROM tbl_cust
                    WHERE RDG_DTIME > DATEADD(DAY, -1095, GETDATE())
                    AND TRY_CONVERT(float, GPS_LAT)  BETWEEN ? AND ?
                    AND TRY_CONVERT(float, GPS_LONG) BETWEEN ? AND ?";
            $params = [$minLat, $maxLat, $minLng, $maxLng];
        }

        /* ── Execute ── */
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $count    = 0;
        $jmapdata = "";

        if ($stmt !== false && sqlsrv_has_rows($stmt)) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

                $ulat = trim($row["GPS_LAT"]  ?? '');
                $ulng = trim($row["GPS_LONG"] ?? '');
                $uacct = $row["ACCOUNTNO"];
                $btag  = $row["BTAG"];
                $mapBtag = $btag;
                if ($mapBtag !== null && $mapBtag !== "" && preg_match('#^iconsx/[a-zA-Z0-9_\-\.]+\.png$#', $mapBtag)) {
                    $tagIconFile = __DIR__ . '/' . $mapBtag;
                    if (is_file($tagIconFile)) {
                        $mapBtag .= '?v=' . filemtime($tagIconFile);
                    }
                }

                if ($ulat == "") $ulat = "0";
                if ($ulng == "") $ulng = "0";

                $fLat = (float)$ulat;
                $fLng = (float)$ulng;

                /* ── Exact polygon filter for BG searches ── */
                if ($p_tag === 'BG' && !empty($polygonRings)) {
                    if (!pointInAnyPolygon($polygonRings, $fLat, $fLng)) {
                        continue;   // outside the polygon — skip
                    }
                }

                $count++;

                $facct = substr($row["ACCOUNTNO"],0,2) . "-"
                       . substr($row["ACCOUNTNO"],2,2) . "-"
                       . substr($row["ACCOUNTNO"],4,4) . "-"
                       . substr($row["ACCOUNTNO"],8,4);

                $range = "";
                $avecons = (float)($row["AVECONS"] ?? 0);
                $kwhu = (float)($row["KWHU"] ?? 0);
                $demand = (float)($row["DEMAND"] ?? 0);

                if ($avecons > 20) {
                    if ($kwhu > ($avecons * 1.5)) {
                        $range = "<font color='red'>HIGH : "
                               . (int)(($kwhu / $avecons) * 100)
                               . "%</font>";
                    }
                    if ($kwhu < ($avecons * 0.5)) {
                        $range = "<font color='red'>LOW : "
                               . (int)(($kwhu / $avecons) * 100)
                               . "%</font>";
                    }
                }

                if ($p_tag == "TK") {
                    $range = "<font color='blue'>KWHr Used : " . $kwhu . "</font>";
                }
                if ($p_tag == "TD") {
                    $range = "<font color='blue'>Demand Used : " . $demand . "</font>";
                }

                echo $facct . "<br>";
                echo "<a href=\"#\" onclick=\"setmapView($ulat,$ulng,'$uacct');\">"
                   . $row["NAME"] . "</a><br>";
                echo $row["ADDRESS"] . "<br>";
                echo $row["METERNO"] . "<br>";

                if ($row["FFDESC"] != "") {
                    echo "<font color='red'>" . $row["FFDESC"] . "</font><br>";
                }
                if ($range != "") {
                    echo $range . "<br>";
                }
                echo "<br>";

                $jmapdata .= "{"
                    . " position: new google.maps.LatLng($ulat, $ulng),"
                    . " type: \""     . $row["STATUS"]  . "\","
                    . " btag: \""     . $mapBtag        . "\","
                    . " accountno: \"" . $row["ACCOUNTNO"] . "\","
                    . " faccountno: \"" . $facct          . "\","
                    . " name: \""     . str_replace('"','', str_replace('','', $row["NAME"])) . "\","
                    . " meterno: \""  . $row["METERNO"] . "\","
                    . " ffdesc: \""   . $row["FFDESC"]  . " " . $range . "\","
                    . "},";
            }

            echo "COUNT : " . $count;
        }
    }
?>

<script>
    xfeatures = [
        <?php echo $jmapdata?>
    ];
    searchMap(xfeatures);
</script>
