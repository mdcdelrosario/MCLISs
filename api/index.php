<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'    => 'ok',
    'service'   => 'PANELCO3 Crew API',
    'endpoints' => [
        'POST /api/uptcrewloc' => 'Update crew GPS location',
        'GET  /api/ugreeting'  => 'List all crew records',
    ],
]);
