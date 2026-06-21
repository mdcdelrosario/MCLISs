<?php
ob_start();
include 'conn.php';
include 'js/fphp.php';
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['search_customer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$trn    = $_POST['trn']    ?? '';
$type   = $_POST['type']   ?? '';
$feeder = $_POST['feeder'] ?? '';
$icon   = $_POST['icon']   ?? '';
$lat    = $_POST['gps_lat']  ?? '';
$lng    = $_POST['gps_long'] ?? '';

if (!$trn) {
    echo json_encode(['success' => false, 'message' => 'Missing trn']);
    exit;
}

try {
    $sql = "UPDATE [db_cdms].[dbo].[tbl_elements] 
            SET [type] = ?, [feeder] = ?, [icon] = ?, [gps_lat] = ?, [gps_long] = ?
            WHERE trn = ?";
    $params = [[$type], [$feeder], [$icon], [$lat], [$lng], [$trn]];
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        throw new Exception('Update failed: ' . json_encode(sqlsrv_errors()));
    }
    
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
    
} catch (Exception $e) {
    error_log('[update_element] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>