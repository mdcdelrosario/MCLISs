<?php
session_start();

// Check if there's search data to export
if (!isset($_SESSION['last_search']) || empty($_SESSION['last_search']['results'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<h2>No data to export</h2>";
    echo "<p>Please perform a search first before exporting.</p>";
    echo "<p><a href='javascript:window.close()'>Close this window</a></p>";
    echo "</body></html>";
    exit;
}

// Get search data from session
$searchData = $_SESSION['last_search'];

// Prepare data for Python script
$exportData = json_encode([
    'title' => $searchData['title'],
    'count' => $searchData['count'],
    'results' => $searchData['results']
], JSON_UNESCAPED_UNICODE);

// Create temporary file for data
$tempDataFile = tempnam(sys_get_temp_dir(), 'cust_data_');
file_put_contents($tempDataFile, $exportData);

// Generate output filename with timestamp
$timestamp = date('Y-m-d_His');
$safeTown = preg_replace('/[^a-zA-Z0-9_-]/', '_', $searchData['town'] ?? 'customers');
$safeBarangay = preg_replace('/[^a-zA-Z0-9_-]/', '_', $searchData['brgy'] ?? 'all');
$filename = "Customers_{$safeTown}_{$safeBarangay}_{$timestamp}.xlsx";
$outputPath = sys_get_temp_dir() . '/' . $filename;

// Execute Python script to create Excel file
$pythonScript = __DIR__ . '/create_customer_excel.py';
$escapedData = escapeshellarg($exportData);
$escapedOutput = escapeshellarg($outputPath);

$command = "python3 {$pythonScript} {$escapedData} {$escapedOutput} 2>&1";
$output = shell_exec($command);

// Clean up temp data file
@unlink($tempDataFile);

// Parse Python script output
$result = json_decode($output, true);

if ($result && $result['status'] === 'success' && file_exists($outputPath)) {
    // File created successfully - send for download
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($outputPath));
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Output file
    readfile($outputPath);
    
    // Clean up
    @unlink($outputPath);
    exit;
    
} else {
    // Error occurred
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Export Error</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto; }
        .error-box { background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; padding: 20px; }
        h2 { color: #991b1b; margin-top: 0; }
        pre { background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
        a { color: #1e40af; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
    </style></head><body>";
    
    echo "<div class='error-box'>";
    echo "<h2>Export Failed</h2>";
    echo "<p>There was an error generating the Excel file.</p>";
    
    if ($result && isset($result['message'])) {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($result['message']) . "</p>";
    }
    
    echo "<details><summary>Debug Information</summary><pre>" . htmlspecialchars($output) . "</pre></details>";
    echo "<p style='margin-top: 20px;'><a href='javascript:window.close()'>Close this window</a></p>";
    echo "</div>";
    
    echo "</body></html>";
    
    // Clean up
    @unlink($outputPath);
}
?>