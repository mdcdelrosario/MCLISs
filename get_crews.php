<?php
/**
 * get_crews.php
 * Returns a JSON array of crew names matching the ?q= search term.
 * Supports fuzzy/partial matching — each word in the query is scored
 * against each word in the crew/member name using similar_text().
 * Falls back to substring LIKE for fast exact-prefix matches.
 */

include 'conn.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

/* ── helpers ─────────────────────────────────────────────────────────────── */

function safeQuery($conn, $sql, $params = []) {
    $stmt = empty($params)
        ? sqlsrv_query($conn, $sql)
        : sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        error_log('[get_crews] SQL ERROR: ' . print_r(sqlsrv_errors(), true));
        return false;
    }
    $rows = [];
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $r;
    }
    return $rows;
}

/**
 * Fuzzy score: how well does $query match $candidate? (0–100)
 *
 * - Splits both strings into words
 * - For every query-word, finds the best-matching candidate-word
 *   using similar_text() + substring/prefix bonuses
 * - Returns the average best-score across all query-words
 */
function fuzzyScore(string $query, string $candidate): float {
    $qWords = preg_split('/\s+/', strtolower(trim($query)));
    $cWords = preg_split('/\s+/', strtolower(trim($candidate)));

    if (empty($qWords) || $qWords === ['']) return 0.0;

    $totalScore = 0.0;

    foreach ($qWords as $qw) {
        if ($qw === '') continue;
        $best = 0.0;
        foreach ($cWords as $cw) {
            if ($cw === '') continue;

            // Character-level similarity (0–100)
            similar_text($qw, $cw, $pct);

            // Bonus: query word is a substring inside candidate word
            $subBonus  = (strpos($cw, $qw) !== false) ? 20.0 : 0.0;

            // Bonus: query word is a prefix of candidate word
            $prefBonus = (strpos($cw, $qw) === 0)     ? 10.0 : 0.0;

            $score = min(100.0, $pct + $subBonus + $prefBonus);
            if ($score > $best) $best = $score;
        }
        $totalScore += $best;
    }

    return $totalScore / count($qWords);
}

// Minimum score (0–100) required to surface a result.
// Lower = more suggestions; raise if too many false positives appear.
define('FUZZY_THRESHOLD', 45.0);

/* ── no query: return all crews ──────────────────────────────────────────── */
if ($q === '') {
    $rows = safeQuery($conn,
        "SELECT DISTINCT LTRIM(RTRIM(crew)) AS crew FROM tbl_crew
          WHERE crew IS NOT NULL AND LTRIM(RTRIM(crew)) <> ''
          ORDER BY crew"
    );
    if ($rows === false) { echo json_encode([]); exit; }

    echo json_encode(array_values(array_filter(
        array_map(fn($r) => trim($r['crew']), $rows)
    )));
    exit;
}

/* ── search mode ─────────────────────────────────────────────────────────── */
// Fetch everything into PHP and score with fuzzyScore().
// For very large tbl_person tables add a coarse LIKE pre-filter here;
// for typical utility-company sizes this is fast enough.

// 1. All distinct crew names
$allCrewRows = safeQuery($conn,
    "SELECT DISTINCT LTRIM(RTRIM(crew)) AS crew FROM tbl_crew
      WHERE crew IS NOT NULL AND LTRIM(RTRIM(crew)) <> ''
      ORDER BY crew"
);

// 2. All member name → crew mappings
$allMemberRows = safeQuery($conn,
    "SELECT DISTINCT
        LTRIM(RTRIM([name])) AS name,
        LTRIM(RTRIM([crew])) AS crew
      FROM tbl_person
      WHERE [name] IS NOT NULL AND LTRIM(RTRIM([name])) <> ''
        AND [crew] IS NOT NULL AND LTRIM(RTRIM([crew])) <> ''
      ORDER BY [name]"
);

if ($allCrewRows === false && $allMemberRows === false) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

// crew => best fuzzy score seen so far
$scored = [];

// Score crew names directly
foreach (($allCrewRows ?: []) as $row) {
    $crew  = trim($row['crew']);
    $score = fuzzyScore($q, $crew);
    if ($score >= FUZZY_THRESHOLD) {
        $scored[$crew] = max($scored[$crew] ?? 0.0, $score);
    }
}

// Score member names; a matching member surfaces its crew
foreach (($allMemberRows ?: []) as $row) {
    $name  = trim($row['name']);
    $crew  = trim($row['crew']);
    if ($crew === '') continue;
    $score = fuzzyScore($q, $name);
    if ($score >= FUZZY_THRESHOLD) {
        $scored[$crew] = max($scored[$crew] ?? 0.0, $score);
    }
}

// Sort best-match first, return crew names only
arsort($scored);
echo json_encode(array_values(array_keys($scored)));    