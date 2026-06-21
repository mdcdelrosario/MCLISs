<?php
/**
 * GET /api/ugreeting
 * Returns all rows from tbl_crew (same as old Laravel endpoint).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_response(['message' => 'Method not allowed. Use GET.'], 405);
}

$stmt = sqlsrv_query($conn, 'SELECT * FROM tbl_crew');

if ($stmt === false) {
    api_json_response([
        'message' => 'Database error.',
        'error'   => sqlsrv_errors(),
    ], 500);
}

api_json_response(api_rows_to_array($stmt));
