<?php
include 'conn.php';

$town = trim($_GET['vtown'] ?? '');
$brgy = trim($_GET['vbrgy'] ?? '');

if ($town === '') {
    echo '<div class="empty-state"><i class="fa fa-map-marker"></i> No town selected.</div>';
    exit;
}

$label = $brgy !== ''
    ? htmlspecialchars($brgy . ', ' . $town)
    : htmlspecialchars($town . ' — all barangays');

/* ── SQL: single barangay ── */
if ($brgy !== '') {
    $sql = "
        WITH pts AS (
            SELECT Town, BarangayName, PointOrder,
                   CAST(Latitude AS VARCHAR(25)) + ' ' + CAST(Longitude AS VARCHAR(25)) AS coord
            FROM dbo.boundaries
            WHERE Town = ? AND BarangayName = ?
        ),
        first_pts AS (
            SELECT p.Town, p.BarangayName, p.coord
            FROM pts p
            INNER JOIN (
                SELECT Town, BarangayName, MIN(PointOrder) AS min_ord
                FROM pts GROUP BY Town, BarangayName
            ) m ON p.Town = m.Town AND p.BarangayName = m.BarangayName
                   AND p.PointOrder = m.min_ord
        ),
        ring AS (
            SELECT p.Town, p.BarangayName,
                   CAST(STRING_AGG(p.coord, ',') WITHIN GROUP (ORDER BY p.PointOrder) AS VARCHAR(MAX)) AS coords,
                   MAX(f.coord) AS first_coord
            FROM pts p
            INNER JOIN first_pts f ON p.Town = f.Town AND p.BarangayName = f.BarangayName
            GROUP BY p.Town, p.BarangayName
        ),
        BoundaryPolygons AS (
            SELECT geography::STGeomFromText(
                'POLYGON((' + coords + ',' + first_coord + '))', 4326
            ) AS Shape
            FROM ring
            WHERE first_coord IS NOT NULL
        )
        SELECT
            c.ACCOUNTNO, c.NAME, c.ADDRESS, c.STATUS, c.TYPE,
            c.FEEDER, c.GPS_LAT, c.GPS_LONG, c.METERNO
        FROM tbl_cust c
        CROSS JOIN BoundaryPolygons bp
        WHERE
            c.GPS_LAT  IS NOT NULL AND c.GPS_LONG IS NOT NULL
            AND TRY_CONVERT(float, c.GPS_LAT) BETWEEN -90 AND 90
            AND TRY_CONVERT(float, c.GPS_LONG) BETWEEN -180 AND 180
            AND TRY_CONVERT(float, c.GPS_LAT)  <> 0
            AND bp.Shape.STContains(
                geography::Point(
                    CASE WHEN TRY_CONVERT(float, c.GPS_LAT) BETWEEN -90 AND 90 THEN TRY_CONVERT(float, c.GPS_LAT) ELSE NULL END,
                    CASE WHEN TRY_CONVERT(float, c.GPS_LONG) BETWEEN -180 AND 180 THEN TRY_CONVERT(float, c.GPS_LONG) ELSE NULL END,
                    4326
                )
            ) = 1
        ORDER BY c.NAME";
    $params = [$town, $brgy];

/* ── SQL: whole town (union of all barangay polygons) ── */
} else {
    $sql = "
        WITH pts AS (
            SELECT Town, BarangayName, PointOrder,
                   CAST(Latitude AS VARCHAR(25)) + ' ' + CAST(Longitude AS VARCHAR(25)) AS coord
            FROM dbo.boundaries
            WHERE Town = ?
        ),
        first_pts AS (
            SELECT p.Town, p.BarangayName, p.coord
            FROM pts p
            INNER JOIN (
                SELECT Town, BarangayName, MIN(PointOrder) AS min_ord
                FROM pts GROUP BY Town, BarangayName
            ) m ON p.Town = m.Town AND p.BarangayName = m.BarangayName
                   AND p.PointOrder = m.min_ord
        ),
        ring AS (
            SELECT p.Town, p.BarangayName,
                   CAST(STRING_AGG(p.coord, ',') WITHIN GROUP (ORDER BY p.PointOrder) AS VARCHAR(MAX)) AS coords,
                   MAX(f.coord) AS first_coord
            FROM pts p
            INNER JOIN first_pts f ON p.Town = f.Town AND p.BarangayName = f.BarangayName
            GROUP BY p.Town, p.BarangayName
        ),
        BrgyPolygons AS (
            SELECT geography::STGeomFromText(
                'POLYGON((' + coords + ',' + first_coord + '))', 4326
            ) AS Shape
            FROM ring
            WHERE first_coord IS NOT NULL
        ),
        TownShape AS (
            SELECT geography::UnionAggregate(Shape) AS Shape
            FROM BrgyPolygons
            WHERE Shape IS NOT NULL
        )
        SELECT
            c.ACCOUNTNO, c.NAME, c.ADDRESS, c.STATUS, c.TYPE,
            c.FEEDER, c.GPS_LAT, c.GPS_LONG, c.METERNO
        FROM tbl_cust c
        CROSS JOIN TownShape ts
        WHERE
            c.GPS_LAT  IS NOT NULL AND c.GPS_LONG IS NOT NULL
            AND TRY_CONVERT(float, c.GPS_LAT)  IS NOT NULL
            AND TRY_CONVERT(float, c.GPS_LONG) IS NOT NULL
            AND TRY_CONVERT(float, c.GPS_LAT)  <> 0
            AND TRY_CONVERT(float, c.GPS_LONG) > 100
            AND ts.Shape IS NOT NULL
            AND ts.Shape.STContains(
                geography::Point(
                    CASE WHEN TRY_CONVERT(float, c.GPS_LAT) BETWEEN -90 AND 90 THEN TRY_CONVERT(float, c.GPS_LAT) ELSE NULL END,
                    CASE WHEN TRY_CONVERT(float, c.GPS_LONG) BETWEEN -180 AND 180 THEN TRY_CONVERT(float, c.GPS_LONG) ELSE NULL END, 4326
                )
            ) = 1
        ORDER BY c.NAME";
    $params = [$town];
}

/* ── Execute ── */
$stmt = sqlsrv_query($conn, $sql, $params);
if (!$stmt) {
    $err = sqlsrv_errors();
    echo '<div class="empty-state" style="color:#c0392b;">
            <i class="fa fa-exclamation-circle"></i> Error: '
         . htmlspecialchars($err[0]['message'] ?? 'unknown') . '</div>';
    exit;
}

$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}
sqlsrv_free_stmt($stmt);
$count = count($rows);
?>

<!-- ── Header ── -->
<div style="padding:8px 10px 6px;font-size:11px;color:#777;
            border-bottom:1px solid #eee;display:flex;align-items:center;gap:6px;">
    <i class="fa fa-map-marker" style="color:#1565C0;font-size:13px;"></i>
    <strong><?= $label ?></strong> —
    <span style="color:#1565C0;font-weight:700;"><?= $count ?></span> customers
</div>

<!-- ── Results ── -->
<?php if ($count === 0): ?>
    <div class="empty-state">
        <i class="fa fa-users"></i> No GPS customers found in this area.
    </div>
<?php else: ?>
<div class="result-list">
<?php foreach ($rows as $r):
    $acct   = htmlspecialchars($r['ACCOUNTNO'] ?? '');
    $name   = htmlspecialchars($r['NAME']      ?? '');
    $addr   = htmlspecialchars($r['ADDRESS']   ?? '');
    $status = htmlspecialchars($r['STATUS']    ?? '');
    $meter  = htmlspecialchars($r['METERNO']   ?? '');

    /* Format account no: XX-XX-XXXX-XXXX */
    $raw   = $r['ACCOUNTNO'] ?? '';
    $facct = strlen($raw) >= 12
        ? substr($raw,0,2).'-'.substr($raw,2,2).'-'.substr($raw,4,4).'-'.substr($raw,8,4)
        : $acct;

    $badgeColor = match(strtoupper($status)) {
        'ACTIVE'       => '#2e7d32',
        'DISCONNECTED' => '#b71c1c',
        default        => '#78909c'
    };
?>
    <div class="result-item" onclick="loadCustInfo('<?= $acct ?>')" style="cursor:pointer;">
        <div class="ri-main" style="display:flex;align-items:center;justify-content:space-between;">
            <span class="ri-name" style="font-weight:600;font-size:12px;"><?= $name ?></span>
            <span style="font-size:9.5px;font-weight:700;padding:1px 6px;border-radius:50px;
                         background:<?= $badgeColor ?>22;color:<?= $badgeColor ?>;
                         border:1px solid <?= $badgeColor ?>44;">
                <?= $status ?>
            </span>
        </div>
        <div class="ri-acct" style="font-size:10.5px;color:#888;margin-top:1px;">
            <?= $facct ?> · <?= $meter ?>
        </div>
        <div class="ri-sub" style="font-size:10px;color:#aaa;margin-top:2px;
                                    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= $addr ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- ── Map markers ── -->
<script>
const brgyCust = [
<?php foreach ($rows as $r):
    $lat  = floatval($r['GPS_LAT']   ?? 0);
    $lng  = floatval($r['GPS_LONG']  ?? 0);
    $raw  = $r['ACCOUNTNO'] ?? '';
    $facct = strlen($raw) >= 12
        ? substr($raw,0,2).'-'.substr($raw,2,2).'-'.substr($raw,4,4).'-'.substr($raw,8,4)
        : $raw;
?>
    {
        position  : new google.maps.LatLng(<?= $lat ?>, <?= $lng ?>),
        accountno : '<?= addslashes($r['ACCOUNTNO'] ?? '') ?>',
        faccountno: '<?= addslashes($facct) ?>',
        name      : '<?= addslashes($r['NAME']    ?? '') ?>',
        meterno   : '<?= addslashes($r['METERNO'] ?? '') ?>',
        type      : '<?= addslashes($r['STATUS']  ?? '') ?>',
        btag      : '',
        ffdesc    : ''
    },
<?php endforeach; ?>
];
searchMap(brgyCust);
</script>
<?php endif; ?>