<?php
include 'conn.php';
include 'js/fphp.php';

if (!isset($_SESSION['search_customer'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

header('Content-Type: application/json');

/* ── Validate request ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$file = $_FILES['kml_file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errCodes = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (server limit).',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit).',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension.',
    ];
    $msg = $errCodes[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Upload error.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

/* ── Validate file extension ── */
$originalName = basename($file['name']);
$ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($ext !== 'kml') {
    echo json_encode(['success' => false, 'message' => 'Only .kml files are allowed.']);
    exit();
}

$baseName = pathinfo($originalName, PATHINFO_FILENAME);
$pattern  = '/^feeder(\d+)_(primary[123]?|secondary|underbuilt)$/i';

if (!preg_match($pattern, $baseName, $matches)) {
    echo json_encode([
        'success' => false,
        'message' =>
            'Filename must follow the convention: feeder{ID}_{type}.kml' . "\n" .
            'Allowed types: primary1, primary2, primary3, secondary, underbuilt' . "\n" .
            'Examples: feeder11_primary1.kml, feeder65_underbuilt.kml, feeder95_secondary.kml',
    ]);
    exit();
}

$feederId = $matches[1];
$lineType = strtolower($matches[2]);
$safeFile = "feeder{$feederId}_{$lineType}.kml";

/* ── Validate KML content (basic XML check) ── */
$tmpPath = $file['tmp_name'];
$content = file_get_contents($tmpPath);

if ($content === false) {
    echo json_encode(['success' => false, 'message' => 'Could not read uploaded file.']);
    exit();
}

$xml = @simplexml_load_string($content);
if ($xml === false) {
    echo json_encode(['success' => false, 'message' => 'File is not valid XML/KML.']);
    exit();
}

/* ── Save to data/ folder ── */
$dataDir  = __DIR__ . '/data/';
$destPath = $dataDir . $safeFile;

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

if (!move_uploaded_file($tmpPath, $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file to server.']);
    exit();
}

echo json_encode([
    'success'  => true,
    'filename' => $safeFile,
    'path'     => 'data/' . $safeFile,
    'feeder'   => $feederId,
    'lineType' => $lineType,
    'message'  => "Saved as {$safeFile}",
]);