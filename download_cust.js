<?php
/* ════════════════════════════════════════════════
   download_cust.php
════════════════════════════════════════════════ */

include 'conn.php';
include 'js/fphp.php';

/* ── Session guard ── */
if (!isset($_SESSION['search_customer'])) {
    http_response_code(403);
    exit('Unauthorized');
}

/* ── Helpers ── */
function post(string $key): string {
    return trim($_POST[$key] ?? '');
}

function abort(string $msg, int $code = 400): void {
    http_response_code($code);
    exit($msg);
}

function sqlsrv_err_str(): string {
    $e = sqlsrv_errors();
    return $e ? $e[0]['message'] : 'unknown error';
}

/* ── Inputs ── */
$town    = post('vtown');
$brgy    = post('vbrgy');
$polyRaw = post('vpolygon');

if (!$town)    abort('Town is required.');
if (!$polyRaw) abort('Polygon data is required.');

/* ── Parse polygon ── */
$rings = json_decode($polyRaw, true);
if (!is_array($rings) || empty($rings)) {
    abort('Invalid polygon data.');
}

/* ── Convert to WKT ── */
function ringToWkt(array $ring): string {
    $pts = [];

    foreach ($ring as $p) {
        if (!isset($p['lat'], $p['lng'])) continue;
        $pts[] = $p['lng'] . ' ' . $p['lat']; // lng lat (correct)
    }

    if (count($pts) < 3) {
        return '';
    }

    // Close polygon
    if ($pts[0] !== end($pts)) {
        $pts[] = $pts[0];
    }

    return '(' . implode(',', $pts) . ')';
}

$validRings = [];

foreach ($rings as $r) {
    $w = ringToWkt($r);
    if ($w !== '') {
        $validRings[] = $w;
    }
}

if (empty($validRings)) {
    abort('No valid polygon rings.');
}

if (count($validRings) === 1) {
$wkt = 'POLYGON ' . $validRings[0];
} else {
    $polys = array_map(function ($r) {
        return '(' . $r . ')';
    }, $validRings);

    $wkt = 'MULTIPOLYGON(' . implode(',', $polys) . ')';
}

/* ── Query (FIXED) ── */
$sql = "
    SELECT
        c.X1,
        c.Y1,
        c.AccountNumber AS [Account Number],
        c.Name,
        c.Address,
        c.CustomerType,
        c.periodCovered AS [Period Covered],
        c.KWHU
    FROM
        tbl_cust c
    WHERE
        c.geom IS NOT NULL
        AND c.geom.STSrid = 4326
        AND c.geom.STIntersects(geography::STGeomFromText(?, 4326)) = 1
";

$params = [$wkt];
$stmt   = sqlsrv_query($conn, $sql, $params);

if (!$stmt) {
    abort('Query failed: ' . sqlsrv_err_str(), 500);
}

/* ── Filename ── */
$safeTown = preg_replace('/[^A-Za-z0-9_\-]/', '_', $town);
$safeBrgy = $brgy ? '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $brgy) : '';
$date     = date('Ymd');
$filename = "customers_{$safeTown}{$safeBrgy}_{$date}.csv";

/* ── Headers ── */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/* ── Output ── */
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$out = fopen('php://output', 'w');

/* ── CSV Header ── */
fputcsv($out, [
    'X1(Longitude)',
    'Y1(Latitude)',
    'Account Number',
    'Name',
    'Address',
    'CustomerType',
    'Period Covered (rdg_dtime)',
    'KWHU',
]);

/* ── Data ── */
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    fputcsv($out, [
        $row['X1'],
        $row['Y1'],
        $row['Account Number'],
        $row['Name'],
        $row['Address'],
        $row['CustomerType'],
        $row['Period Covered'],
        $row['KWHU'],
    ]);
}

sqlsrv_free_stmt($stmt);
fclose($out);

exit;
?>
