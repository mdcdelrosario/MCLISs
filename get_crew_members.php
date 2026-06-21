<?php
ob_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include 'conn.php';

$mode = trim($_GET['mode'] ?? '');

$AREA_KEYWORDS = [
    'cao'  => 'CAO',
    'nao'  => 'NAO',
    'eao'  => 'EAO',
    'seao' => 'SEAO',
    'sao'  => 'SAO',
];

$AREA_EXCLUSIONS = [
    'eao' => 'SEAO',
];

function safeQuery($conn, $sql, $params = []) {
    $stmt = empty($params)
        ? sqlsrv_query($conn, $sql)
        : sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        error_log('[get_crew_members] SQL ERROR: ' . print_r(sqlsrv_errors(), true));
        return false;
    }
    $rows = [];
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $r;
    }
    return $rows;
}

function sendJSON($data) {
    ob_end_clean();
    echo json_encode(array_values($data));
    exit;
}

function sendError($msg, $code = 400) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function fuzzyScore($query, $candidate) {
    $qWords = preg_split('/\s+/', strtolower(trim($query)));
    $cWords = preg_split('/\s+/', strtolower(trim($candidate)));

    if (empty($qWords) || $qWords === ['']) return 0.0;

    $totalScore = 0.0;
    $counted    = 0;

    foreach ($qWords as $qw) {
        if ($qw === '') continue;
        $best = 0.0;
        foreach ($cWords as $cw) {
            if ($cw === '') continue;
            similar_text($qw, $cw, $pct);
            $subBonus  = (strpos($cw, $qw) !== false) ? 20.0 : 0.0;
            $prefBonus = (strpos($cw, $qw) === 0)     ? 10.0 : 0.0;
            $score = min(100.0, $pct + $subBonus + $prefBonus);
            if ($score > $best) $best = $score;
        }
        $totalScore += $best;
        $counted++;
    }

    return $counted > 0 ? ($totalScore / $counted) : 0.0;
}

define('FUZZY_THRESHOLD', 45.0);

/* MODE: crew */
if ($mode === 'crew') {
    $crew = trim($_GET['crew'] ?? '');
    if ($crew === '') sendJSON([]);

    $rows = safeQuery($conn,
        "SELECT [name] FROM tbl_person
          WHERE LTRIM(RTRIM([crew])) = ?
            AND [name] IS NOT NULL
            AND LTRIM(RTRIM([name])) <> ''
          ORDER BY [name]",
        [$crew]
    );

    if ($rows === false) sendJSON([]);

    $names = [];
    foreach ($rows as $r) {
        $n = trim($r['name'] ?? '');
        if ($n !== '') $names[] = $n;
    }
    sendJSON($names);
}

/* MODE: area */
if ($mode === 'area') {
    global $AREA_KEYWORDS, $AREA_EXCLUSIONS;

    $areasRaw = trim($_GET['areas'] ?? '');
    if ($areasRaw === '') sendJSON([]);

    $requested  = array_filter(array_map('trim', explode(',', strtolower($areasRaw))));
    $areaBlocks = [];
    $params     = [];

    foreach ($requested as $code) {
        if (!isset($AREA_KEYWORDS[$code])) continue;
        $keyword = $AREA_KEYWORDS[$code];
        if (isset($AREA_EXCLUSIONS[$code])) {
            $excludeKw    = $AREA_EXCLUSIONS[$code];
            $areaBlocks[] = "([name] LIKE ? AND [name] NOT LIKE ?)";
            $params[]     = '%' . $keyword . '%';
            $params[]     = '%' . $excludeKw . '%';
        } else {
            $areaBlocks[] = "[name] LIKE ?";
            $params[]     = '%' . $keyword . '%';
        }
    }

    if (empty($areaBlocks)) sendJSON([]);

    $where = implode(' OR ', $areaBlocks);
    $rows = safeQuery($conn,
        "SELECT DISTINCT [name] FROM tbl_person
          WHERE ($where)
            AND [name] IS NOT NULL
            AND LTRIM(RTRIM([name])) <> ''
          ORDER BY [name]",
        $params
    );

    if ($rows === false) sendJSON([]);

    $names = [];
    foreach ($rows as $r) {
        $n = trim($r['name'] ?? '');
        if ($n !== '') $names[] = $n;
    }
    sendJSON($names);
}

/* MODE: search */
if ($mode === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 1) sendJSON([]);

    $rows = safeQuery($conn,
        "SELECT DISTINCT LTRIM(RTRIM([name])) AS name
          FROM tbl_person
          WHERE [name] IS NOT NULL AND LTRIM(RTRIM([name])) <> ''
          ORDER BY [name]"
    );

    if ($rows === false) sendJSON([]);

    $scored = [];
    foreach ($rows as $row) {
        $name  = trim($row['name'] ?? '');
        if ($name === '') continue;
        $score = fuzzyScore($q, $name);
        if ($score >= FUZZY_THRESHOLD) {
            $scored[$name] = $score;
        }
    }

    arsort($scored);
    $results = array_slice(array_keys($scored), 0, 20);
    sendJSON($results);
}

/* MODE: debug */
if ($mode === 'debug') {
    $rows = safeQuery($conn, "SELECT TOP 10 * FROM tbl_person");
    sendJSON($rows ?: []);
}

sendError('Invalid mode. Use: mode=crew|area|search|debug');