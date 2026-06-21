<?php
ob_start();
session_start();
include 'conn.php';
include 'js/fphp.php';

/* ── Session guard ── */
if (!isset($_SESSION['search_customer'])) {
    ob_end_clean();
    http_response_code(403);
    exit('Unauthorized');
}

/* ── Helpers ── */
function post($key) {
    return trim($_POST[$key] ?? '');
}

function abort($msg, $code = 400) {
    ob_end_clean();
    http_response_code($code);
    exit($msg);
}

function sqlsrv_err_str() {
    $e = sqlsrv_errors();
    return $e ? $e[0]['message'] : 'unknown error';
}

/* ── Point-in-polygon (ray-casting) — identical to search.php ── */
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

function pointInAnyPolygon(array $rings, float $lat, float $lng): bool {
    foreach ($rings as $ring) {
        if (pointInPolygon($ring, $lat, $lng)) return true;
    }
    return false;
}

/* ── Inputs ── */
$town    = post('vtown');
$brgy    = post('vbrgy');   // empty string for town-wide download — that's fine
$polyRaw = post('vpolygon');

if (!$town)    abort('Town is required.');
if (!$polyRaw) abort('Polygon data is required.');
if (!$polyRaw) abort('Polygon data is required.');

/* ── Parse polygon — same logic as search.php ── */
$polygonRings = [];
$decoded = json_decode($polyRaw, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
    abort('Invalid polygon data.');
}
// Support both a single ring [{lat,lng},...] and array of rings
if (isset($decoded[0]['lat'])) {
    $polygonRings = [$decoded];   // single ring
} else {
    $polygonRings = $decoded;     // array of rings
}

if (empty($polygonRings)) abort('No valid polygon.');

/* ── Bounding box (same pre-filter SQL as search.php BG tag) ── */
$allLat = []; $allLng = [];
foreach ($polygonRings as $ring) {
    foreach ($ring as $pt) {
        $allLat[] = (float)$pt['lat'];
        $allLng[] = (float)$pt['lng'];
    }
}
$minLat = min($allLat); $maxLat = max($allLat);
$minLng = min($allLng); $maxLng = max($allLng);

/* ── SQL — exact same bounding box query as search.php BG ── */
$sql = "SELECT
            GPS_LONG  AS [X1],
            GPS_LAT   AS [Y1],
            RIGHT('000000000000' + CAST(ACCOUNTNO AS VARCHAR(12)), 12) AS [Account Number],
            NAME,
            ADDRESS,
            TYPE      AS [CustomerType],
            RDG_DTIME AS [Period Covered],
            KWHU
        FROM tbl_cust
        WHERE RDG_DTIME > DATEADD(DAY, -1095, GETDATE())
        AND TRY_CONVERT(float, GPS_LAT)  BETWEEN ? AND ?
        AND TRY_CONVERT(float, GPS_LONG) BETWEEN ? AND ?";

$params = [$minLat, $maxLat, $minLng, $maxLng];
$stmt   = sqlsrv_query($conn, $sql, $params);
if (!$stmt) abort('Query failed: ' . sqlsrv_err_str(), 500);
/* ── Filename ── */
$safeTown = preg_replace('/[^A-Za-z0-9_\-]/', '_', $town);
$safeBrgy = $brgy ? '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $brgy) : '';
$date     = date('Ymd');
$filename = "customers_{$safeTown}{$safeBrgy}_{$date}.csv";
/* ── Discard buffer, send CSV headers ── */
ob_end_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/* ── Output CSV ── */
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

$out = fopen('php://output', 'w');

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

/* ── Ray-cast filter in PHP — mirrors search.php exactly ── */
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $fLat = (float)trim($row['Y1'] ?? '0');
    $fLng = (float)trim($row['X1'] ?? '0');

    // Skip points outside the polygon — same as search.php
    if (!pointInAnyPolygon($polygonRings, $fLat, $fLng)) continue;

    fputcsv($out, [
        $row['X1'],
        $row['Y1'],
        $row['Account Number'],
        $row['NAME'],
        $row['ADDRESS'],
        $row['CustomerType'],
        $row['Period Covered'] instanceof DateTime
            ? $row['Period Covered']->format('Y-m-d H:i:s')
            : $row['Period Covered'],
        $row['KWHU'],
    ]);
}

sqlsrv_free_stmt($stmt);
fclose($out);
exit;
?>