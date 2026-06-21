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

$trn = $_GET['trn'] ?? '';

if (!$trn) {
    echo json_encode(['success' => false, 'message' => 'Missing trn']);
    exit;
}

try {
    $sql = "SELECT * FROM [db_cdms].[dbo].[tbl_elements] WHERE trn = ?";
    $params = [[$trn]];
    $stmt = sqlsrv_query($conn, $sql, $params); 
    
    if ($stmt === false) {
        throw new Exception('Query failed: ' . json_encode(sqlsrv_errors()));
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Element not found']);
    }
    
} catch (Exception $e) {
    error_log('[fetch_element] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>