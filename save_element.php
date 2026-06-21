<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
include 'conn.php';
include 'js/fphp.php';
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['search_customer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type   = $_POST['type']    ?? '';
$icon   = $_POST['icon']    ?? '';
$feeder = $_POST['feeder']  ?? '';
$uid    = $_POST['uid']     ?? '';   // ← coordinate-based uid from JS e.g. 16.0564693120.7793185
$trn    = $_POST['trn']     ?? 'POLE';

try {
    if ($trn === 'POLE') {
        $lat    = $_POST['gps_lat']  ?? '';
        $lng    = $_POST['gps_long'] ?? '';
        $poleId = $_POST['pole_id']  ?? '';

        if (empty($poleId)) {
            throw new Exception('Pole ID is required');
        }

        if (empty($uid)) {
            throw new Exception('UID is required');
        }

        // Check if pole ID already exists
        $checkSql  = "SELECT COUNT(*) as cnt FROM [db_cdms].[dbo].[tbl_elements] WHERE id = ?";
        $checkStmt = sqlsrv_query($conn, $checkSql, [[$poleId]]);
        if ($checkStmt === false) {
            throw new Exception('Failed to check pole ID: ' . json_encode(sqlsrv_errors()));
        }
        $checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if ($checkRow['cnt'] > 0) {
            throw new Exception('Pole ID "' . $poleId . '" already exists. Please use a different ID.');
        }

        // Get next trn (auto-increment)
        $maxResult = sqlsrv_query($conn, "SELECT ISNULL(MAX(trn), 0) + 1 AS next_trn FROM [db_cdms].[dbo].[tbl_elements]");
        if ($maxResult === false) {
            throw new Exception('Failed to get next trn: ' . json_encode(sqlsrv_errors()));
        }
        $next_trn = sqlsrv_fetch_array($maxResult, SQLSRV_FETCH_ASSOC)['next_trn'];

        $sql    = "INSERT INTO [db_cdms].[dbo].[tbl_elements] ([trn],[type],[id],[icon],[feeder],[gps_lat],[gps_long],[uid])
                   VALUES (?,?,?,?,?,?,?,?)";
        $params = [[$next_trn], [$type], [$poleId], [$icon], [$feeder], [$lat], [$lng], [$uid]];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Insert failed: ' . json_encode(sqlsrv_errors()));
        }

        echo json_encode(['success' => true, 'id' => $poleId, 'trn' => $next_trn]);

    } elseif ($trn === 'LINE') {
        $coords = json_decode($_POST['coords'] ?? '[]', true);
        if (!is_array($coords) || count($coords) < 2) {
            throw new Exception('Need at least 2 points');
        }

        $group_id = uniqid('L', true);
        $sql      = "INSERT INTO [db_cdms].[dbo].[tbl_elements] ([trn],[type],[id],[icon],[feeder],[gps_lat],[gps_long],[uid])
                     VALUES (?,?,?,?,?,?,?,?)";

        foreach ($coords as $pt) {
            $maxResult = sqlsrv_query($conn, "SELECT ISNULL(MAX(trn), 0) + 1 AS next_trn FROM [db_cdms].[dbo].[tbl_elements]");
            if ($maxResult === false) {
                throw new Exception('Failed to get next trn: ' . json_encode(sqlsrv_errors()));
            }
            $next_trn = sqlsrv_fetch_array($maxResult, SQLSRV_FETCH_ASSOC)['next_trn'];

            $pt_lat = (string)($pt['lat'] ?? '');
            $pt_lng = (string)($pt['lng'] ?? '');
            // Each line point gets its own uid
            $pt_uid = number_format((float)$pt_lat, 7, '.', '') .
                      number_format((float)$pt_lng, 7, '.', '');

            $params = [[$next_trn], [$type], [$group_id], [$icon], [$feeder], [$pt_lat], [$pt_lng], [$pt_uid]];
            $stmt   = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Insert failed: ' . json_encode(sqlsrv_errors()));
            }
        }

        echo json_encode(['success' => true, 'id' => $group_id]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown trn: ' . $trn]);
    }

} catch (Exception $e) {
    error_log('[save_element] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>