<?php 
include 'conn.php';
include 'js/fphp.php';
?>

<script>document.getElementById('id01').style.display='none'</script>

<?php

// Block guest users entirely
$currentUserLevel = strtolower($_SESSION['user_level'] ?? '');
if ($currentUserLevel === 'guest') {
    echo '<div style="padding:16px;color:#c62828;">
            <i class="fa fa-lock"></i> You do not have permission to view complaints.
          </div>';
    echo '<script>var xfeatures = []; searchMapCNR(xfeatures);</script>';
    return;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p_area   = $_POST['varea'] ?? '';
    $params   = [];
    $jmapdata = [];

    // Map session area flags → tbl_crew bit columns
    $area_col_map = [
        'central_area'      => 'central_area_office',
        'northern_area'     => 'northern_area_office',
        'eastern_area'      => 'eastern_area_office',
        'southeastern_area' => 'southeastern_area_office',
        'southern_area'     => 'southern_area_office',
    ];

    // Map Analytics dropdown value → tbl_crew bit column
    $dropdown_col_map = [
        'Central Area Office'      => 'central_area_office',
        'Northern Area Office'     => 'northern_area_office',
        'Eastern Area Office'      => 'eastern_area_office',
        'SouthEastern Area Office' => 'southeastern_area_office',
        'Southern Area Office'     => 'southern_area_office',
    ];

    // Pending date filter — unaccomplished records
    $date_filter = "(YEAR(aco_dt) < 2020 OR CONVERT(DATE, aco_dt) < CONVERT(DATE, req_dt))";

    if (!empty($_SESSION['is_crew']) && $_SESSION['is_crew'] == 1) {
        // Crew user: find their area from session flags, get crews in that area
        $user_area_col = null;
        foreach ($area_col_map as $sess_key => $db_col) {
            if (!empty($_SESSION[$sess_key])) {
                $user_area_col = $db_col;
                break;
            }
        }

        if ($user_area_col) {
            $crew_stmt  = sqlsrv_query($conn,
                "SELECT crew FROM tbl_crew WHERE {$user_area_col} = 1"
            );
            $crew_names = [];
            while ($crow = sqlsrv_fetch_array($crew_stmt, SQLSRV_FETCH_ASSOC)) {
                if (!empty($crow['crew'])) $crew_names[] = $crow['crew'];
            }

            if (!empty($crew_names)) {
                $placeholders = implode(',', array_fill(0, count($crew_names), '?'));
                $sql    = "SELECT * FROM tbl_cnr
                           WHERE crew IN ($placeholders)
                           AND $date_filter";
                $params = $crew_names;
            } else {
                echo '<div style="padding:12px;color:#999;">No complaints found for your area.</div>';
                echo '<script>var xfeatures = []; searchMapCNR(xfeatures);</script>';
                return;
            }
        } else {
            // No area assigned — show all pending
            $sql = "SELECT * FROM tbl_cnr WHERE $date_filter";
        }

    } elseif (!empty($p_area) && isset($dropdown_col_map[$p_area])) {
        // Non-crew with area dropdown selected
        $col        = $dropdown_col_map[$p_area];
        $crew_stmt  = sqlsrv_query($conn,
            "SELECT crew FROM tbl_crew WHERE {$col} = 1"
        );
        $crew_names = [];
        while ($crow = sqlsrv_fetch_array($crew_stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($crow['crew'])) $crew_names[] = $crow['crew'];
        }

        if (!empty($crew_names)) {
            $placeholders = implode(',', array_fill(0, count($crew_names), '?'));
            $sql    = "SELECT * FROM tbl_cnr
                       WHERE crew IN ($placeholders)
                       AND $date_filter";
            $params = $crew_names;
        } else {
            echo '<div style="padding:12px;color:#999;">No complaints found for this area.</div>';
            echo '<script>var xfeatures = []; searchMapCNR(xfeatures);</script>';
            return;
        }

    } else {
        // Admin / no filter — see all pending complaints
        $sql = "SELECT * FROM tbl_cnr WHERE $date_filter";
    }

    $stmt = sqlsrv_query($conn, $sql, $params ?: []);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $count = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $count++;

        $ulat       = $row["gps_lat"]   ?? 0;
        $ulng       = $row["gps_long"]  ?? 0;
        $utrn       = $row["trn"]       ?? '';
        $uaccountno = $row["accountno"] ?? '';

        echo "<a href=\"#\" onclick=\"setmapViewCNR($ulat, $ulng, '$utrn', $uaccountno);\">"
           . htmlspecialchars($row["name"] ?? '') . "</a><br>";
        echo htmlspecialchars($row["address"]   ?? '') . "<br>";
        echo htmlspecialchars($row["contactno"] ?? '') . "<br>";
        echo htmlspecialchars($row["request"]   ?? '') . "<br><br>";

        $jmapdata[] = [
            "position"  => ["lat" => floatval($ulat), "lng" => floatval($ulng)],
            "name"      => $row["name"],
            "address"   => $row["address"],
            "contactno" => $row["contactno"],
            "crew"      => $row["crew"],
            "cnr"       => $row["request"],
            "req_dt"    => $row["req_dt"] ? date_format($row["req_dt"], "m/d/Y h:i A") : '',
            "trn"       => $row["trn"],
        ];
    }

    echo "COUNT : " . $count;
}
?>

<script>
    var xfeatures = <?php echo json_encode($jmapdata, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS); ?>;
    searchMapCNR(xfeatures);
</script>