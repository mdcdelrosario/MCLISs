<?php
/**
 * POST /api/uptcrewloc
 * Accepts JSON from the mobile crew tracker app.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json_response(['message' => 'Method not allowed. Use POST.'], 405);
}

$body = api_read_json_body();

$trackerId  = trim((string) ($body['tracker_id'] ?? ''));
$gpsLong    = $body['gps_long'] ?? null;
$gpsLat     = $body['gps_lat'] ?? null;
$gpsTime    = trim((string) ($body['gps_time'] ?? ''));
$deviceTime = trim((string) ($body['device_time'] ?? ''));

$errors = [];
if ($trackerId === '') {
    $errors['tracker_id'] = 'tracker_id is required.';
}
if (!is_numeric($gpsLong)) {
    $errors['gps_long'] = 'gps_long must be numeric.';
}
if (!is_numeric($gpsLat)) {
    $errors['gps_lat'] = 'gps_lat must be numeric.';
}
if ($gpsTime === '') {
    $errors['gps_time'] = 'gps_time is required.';
}
if ($deviceTime === '') {
    $errors['device_time'] = 'device_time is required.';
}

if (!empty($errors)) {
    api_json_response(['message' => 'Validation failed.', 'errors' => $errors], 422);
}

$sql = "UPDATE tbl_crew
        SET gps_long = ?, gps_lat = ?, gps_time = ?, device_time = ?
        WHERE tracker_id = ?";

$params = [
    (float) $gpsLong,
    (float) $gpsLat,
    $gpsTime,
    $deviceTime,
    $trackerId,
];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    api_json_response([
        'message' => 'Database error.',
        'error'   => sqlsrv_errors(),
    ], 500);
}

$updated = sqlsrv_rows_affected($stmt);

if ($updated > 0) {
    api_json_response(['message' => 'Crew location updated successfully.']);
}

api_json_response(['message' => 'Crew not found or no changes made.'], 404);
