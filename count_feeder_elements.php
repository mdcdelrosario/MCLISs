<?php
include 'conn.php';

header('Content-Type: application/json');

$feeder   = isset($_POST['vfeeder'])     ? trim($_POST['vfeeder'])     : '';
$elemType = isset($_POST['filter_type']) ? trim($_POST['filter_type']) : '';

$typeToIcon = [
    'primary'     => 'ppole.png',
    'secondary'   => 'spole.png',
    'underbuilt'  => 'upole.png',
    'transformer' => 'xformer.png',
];

$iconMap = [
    'ppole.png'   => 'primary',
    'spole.png'   => 'secondary',
    'upole.png'   => 'underbuilt',
    'xformer.png' => 'transformer',
    'primary'     => 'primary',
    'secondary'   => 'secondary',
    'underbuilt'  => 'underbuilt',
    'transformer' => 'transformer',
];

$params = [];
$where  = [];

if ($feeder) {
    $where[]  = 'feeder = ?';
    $params[] = $feeder;
}
if ($elemType) {
    $where[]  = 'icon = ?';
    $params[] = $typeToIcon[$elemType] ?? $elemType;
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        feeder,
        icon,
        COUNT(*) AS total
    FROM tbl_elements
    $whereSQL
    GROUP BY feeder, icon
    ORDER BY feeder ASC, icon ASC
";

$stmt = count($params)
    ? sqlsrv_query($conn, $sql, $params)
    : sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo json_encode(['error' => print_r(sqlsrv_errors(), true)]);
    exit;
}

$feeders = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $f    = $row['feeder'] ?? '—';
    $raw  = strtolower(trim($row['icon'] ?? ''));
    $type = $iconMap[$raw] ?? null;
    $t    = (int)$row['total'];

    if (!isset($feeders[$f])) {
        $feeders[$f] = [
            'feeder'      => $f,
            'primary'     => 0,
            'secondary'   => 0,
            'underbuilt'  => 0,
            'transformer' => 0,
            'total'       => 0,
        ];
    }

    if ($type) $feeders[$f][$type] += $t;
    $feeders[$f]['total'] += $t;
}

$counts = [];
foreach ($feeders as $f => $data) {
    if ($data['primary'])     $counts[] = ['icon' => 'primary',     'feeder' => $f, 'total' => $data['primary']];
    if ($data['secondary'])   $counts[] = ['icon' => 'secondary',   'feeder' => $f, 'total' => $data['secondary']];
    if ($data['underbuilt'])  $counts[] = ['icon' => 'underbuilt',  'feeder' => $f, 'total' => $data['underbuilt']];
    if ($data['transformer']) $counts[] = ['icon' => 'transformer', 'feeder' => $f, 'total' => $data['transformer']];
}

echo json_encode([
    'success' => true,
    'feeders' => array_values($feeders),
    'counts'  => $counts,
]);
?>