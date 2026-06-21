<?php
include '../js/fphp.php';
include '../conn.php';

/* ── Always respond as JSON (except VIEW which returns HTML) ── */
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$trn    = trim($_POST['trn']    ?? $_GET['trn']    ?? '');

/* ── Read rights from session ── */
$user_level   = $_SESSION['user_level'] ?? 'guest';
$user_id      = (int)($_SESSION['user_id'] ?? 0);
$can_edit_cnr = !empty($_SESSION['edit_complaints_request']);
$is_crew      = !empty($_SESSION['is_crew']);

/* ── Resolve crew name from session ── */
$crewName = '';
if ($is_crew && $user_id > 0) {
    $cStmt = sqlsrv_query($conn,
        "SELECT TOP 1 name FROM users WHERE id = ?", [$user_id]);
    if ($cStmt && $cRow = sqlsrv_fetch_array($cStmt, SQLSRV_FETCH_ASSOC)) {
        $crewName = trim($cRow['name'] ?? '');
    }
}

/* ── Helpers ── */
function jsonOut(bool $ok, string $msg, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra));
    exit;
}

function dbError(): string {
    $errs = sqlsrv_errors();
    error_log('complaint_action.php DB Error: ' . print_r($errs, true));
    return 'A database error occurred. Please try again.';
}

/* ── Early guard ── */
if (empty($action)) {
    jsonOut(false, 'No action specified.');
}

/* ════════════════════════════════════════
   VIEW — returns HTML
════════════════════════════════════════ */
if ($action === 'view') {
    if (!$trn) { echo '<p style="color:#9b2335">No record specified.</p>'; exit; }

    $stmt = sqlsrv_query($conn, "SELECT * FROM tbl_cnr WHERE trn = ?", [$trn]);
    $row  = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
    if (!$row) { echo '<p style="color:#9b2335">Record not found.</p>'; exit; }

    $f  = fn($k) => htmlspecialchars($row[$k] ?? '', ENT_QUOTES);
    $dt = function($k) use ($row) {
        if (!($row[$k] instanceof DateTime)) return null;
        return ((int)$row[$k]->format('Y') >= 2020) ? $row[$k]->format('M d, Y') : null;
    };

    $members = array_filter([
        $row['member1'] ?? '', $row['member2'] ?? '', $row['member3'] ?? '',
        $row['member4'] ?? '', $row['member5'] ?? ''
    ]);
    $memberDisplay = !empty($members)
        ? htmlspecialchars(implode(', ', $members), ENT_QUOTES)
        : '—';

    echo '
    <div class="detail-section">
        <div class="detail-section-title">Complaint Info</div>
        <div class="detail-grid">
            <div class="detail-field">
                <div class="df-label">TRN</div>
                <div class="df-value mono">' . $f('trn') . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Account No</div>
                <div class="df-value mono">' . $f('accountno') . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Customer Name</div>
                <div class="df-value">' . $f('name') . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Area</div>
                <div class="df-value">' . $f('area') . '</div>
            </div>
            <div class="detail-field full">
                <div class="df-label">Address</div>
                <div class="df-value">' . $f('address') . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Request Type</div>
                <div class="df-value">' . $f('request') . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Date Filed</div>
                <div class="df-value">' . ($dt('req_dt') ?? '—') . '</div>
            </div>
            <div class="detail-field full">
                <div class="df-label">Description</div>
                <div class="df-value">' . $f('req_desc') . '</div>
            </div>
        </div>
    </div>';

    /* Build aco display before echoing Resolution section */
    $acoDate    = $dt('aco_dt');
    $acoDisplay = $acoDate
        ? '<span style="color:#1e6b3c;font-weight:600;">' . $acoDate . '</span>'
        : '<span style="display:inline-flex;align-items:center;gap:5px;
                padding:3px 10px;background:#fef9ec;border:1px solid #f5e4b0;
                border-radius:20px;font-size:11px;font-weight:600;color:#8a6300;">
                &#9679; Not yet completed
           </span>';

    echo '
    <div class="detail-section">
        <div class="detail-section-title">Resolution</div>
        <div class="detail-grid">
            <div class="detail-field full">
                <div class="df-label">Findings</div>
                <div class="df-value">' . ($f('findings') ?: '—') . '</div>
            </div>
            <div class="detail-field full">
                <div class="df-label">Action Taken</div>
                <div class="df-value">' . ($f('action') ?: '—') . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Date Completed</div>
                <div class="df-value">' . $acoDisplay . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Encoder</div>
                <div class="df-value">' . ($f('encoder') ?: '—') . '</div>
            </div>
        </div>
    </div>';

    $crewDisplay = !empty($row['crew'])
        ? '<span class="crew-chip">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>' . $f('crew') . '</span>'
        : '<span class="unassigned-chip">Unassigned</span>';

    echo '
    <div class="detail-section">
        <div class="detail-section-title">Assigned Crew</div>
        <div class="detail-grid">
            <div class="detail-field">
                <div class="df-label">Crew Lead</div>
                <div class="df-value">' . $crewDisplay . '</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Members</div>
                <div class="df-value">' . $memberDisplay . '</div>
            </div>
        </div>
    </div>';

    exit;
}

/* ════════════════════════════════════════
   GET SINGLE ROW — for edit modal
════════════════════════════════════════ */
if ($action === 'get_row') {
    if (!$trn) jsonOut(false, 'No record specified.');

    $stmt = sqlsrv_query($conn,
        "SELECT trn, name, accountno, area, address, contactno,
                req_dt, request, req_desc, crew, findings, action,
                member1, member2, member3, member4, member5
         FROM tbl_cnr WHERE trn = ?", [$trn]);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $row['req_dt'] = ($row['req_dt'] instanceof DateTime)
            ? $row['req_dt']->format('Y-m-d') : '';
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        header('Content-Type: application/json');
        echo json_encode([]);
    }
    exit;
}

/* ════════════════════════════════════════
   GET REQUEST TYPES — for edit modal dropdown
════════════════════════════════════════ */
if ($action === 'get_request_types') {
    $stmt  = sqlsrv_query($conn,
        "SELECT DISTINCT request FROM tbl_request ORDER BY request");
    $types = [];
    while ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $types[] = ['request' => htmlspecialchars(trim($row['request']), ENT_QUOTES)];
    }
    header('Content-Type: application/json');
    echo json_encode($types);
    exit;
}

/* ════════════════════════════════════════
   GET CREW LIST — from tbl_crew
════════════════════════════════════════ */
if ($action === 'get_crew_list') {
    $stmt = sqlsrv_query($conn,
        "SELECT crew FROM tbl_crew ORDER BY crew");

    $list = [];
    while ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $list[] = [
            'name' => htmlspecialchars(trim($row['crew'] ?? ''), ENT_QUOTES),
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($list);
    exit;
}
if ($action === 'get_crew_members') {
    $crew = trim($_GET['crew'] ?? $_POST['crew'] ?? '');
    if (!$crew) { 
        header('Content-Type: application/json'); 
        echo json_encode(['member1'=>'','member2'=>'','member3'=>'','member4'=>'','member5'=>'']); 
        exit; 
    }

    $stmt = sqlsrv_query($conn,
        "SELECT [name] FROM tbl_person
          WHERE LTRIM(RTRIM([crew])) = ?
            AND [name] IS NOT NULL
            AND LTRIM(RTRIM([name])) <> ''
          ORDER BY [name]",
        [$crew]);

    $members = ['member1'=>'','member2'=>'','member3'=>'','member4'=>'','member5'=>''];
    $i = 1;
    while ($stmt && $i <= 5) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) break;
        $members['member' . $i] = trim($row['name'] ?? '');
        $i++;
    }

    header('Content-Type: application/json');
    echo json_encode($members);
    exit;
}

/* ════════════════════════════════════════
   EDIT — admin OR crew
════════════════════════════════════════ */
if ($action === 'edit') {
    if (!$can_edit_cnr && !$is_crew) jsonOut(false, 'Unauthorized.');
    if (!$trn) jsonOut(false, 'No record specified.');

    $name         = trim($_POST['name']         ?? '');
    $accountno    = trim($_POST['accountno']    ?? '');
    $area         = trim($_POST['area']         ?? '');
    $address      = trim($_POST['address']      ?? '');
    $contactno    = trim($_POST['contactno']    ?? '');
    $req_dt       = trim($_POST['req_dt']       ?? '');
    $request      = trim($_POST['request']      ?? '');
    $req_desc     = trim($_POST['req_desc']     ?? '');
    $findings     = trim($_POST['findings']     ?? '');
    $action_taken = trim($_POST['action_taken'] ?? '');
    $crew         = trim($_POST['crew']         ?? '');

    /* Members come from the edit form */
    $member1 = trim($_POST['member1'] ?? '') ?: null;
    $member2 = trim($_POST['member2'] ?? '') ?: null;
    $member3 = trim($_POST['member3'] ?? '') ?: null;
    $member4 = trim($_POST['member4'] ?? '') ?: null;
    $member5 = trim($_POST['member5'] ?? '') ?: null;

    $stmt = sqlsrv_query($conn,
        "UPDATE tbl_cnr SET
            name      = ?,
            accountno = ?,
            area      = ?,
            address   = ?,
            contactno = ?,
            req_dt    = ?,
            request   = ?,
            req_desc  = ?,
            findings  = ?,
            action    = ?,
            crew      = ?,
            member1   = ?,
            member2   = ?,
            member3   = ?,
            member4   = ?,
            member5   = ?
         WHERE trn = ?",
        [$name, $accountno, $area, $address, $contactno,
         $req_dt, $request, $req_desc, $findings, $action_taken,
         $crew ?: null,
         $member1, $member2, $member3, $member4, $member5,
         $trn]
    );

    $stmt !== false
        ? jsonOut(true,  'Complaint updated.')
        : jsonOut(false, dbError());
}

/* ════════════════════════════════════════
   DELETE — admin OR crew
════════════════════════════════════════ */
if ($action === 'delete') {
    if (!$can_edit_cnr && !$is_crew) jsonOut(false, 'Unauthorized.');
    if (!$trn) jsonOut(false, 'No record specified.');

    $stmt = sqlsrv_query($conn,
        "DELETE FROM tbl_cnr WHERE trn = ?", [$trn]);

    if ($stmt === false) jsonOut(false, dbError());

    $affected = sqlsrv_rows_affected($stmt);
    $affected > 0
        ? jsonOut(true,  'Complaint deleted.')
        : jsonOut(false, 'Record not found or already deleted.');
}

/* ════════════════════════════════════════
   ASSIGN — admin OR crew
════════════════════════════════════════ */
if ($action === 'assign') {
    if (!$can_edit_cnr && !$is_crew) jsonOut(false, 'Unauthorized.');
    if (!$trn) jsonOut(false, 'No record specified.');

    $crewAssigned = trim($_POST['crew_name'] ?? '');
    if (!$crewAssigned) jsonOut(false, 'No crew member selected.');

    $stmt = sqlsrv_query($conn,
        "UPDATE tbl_cnr SET crew=? WHERE trn=?",
        [$crewAssigned, $trn]);

    $stmt !== false
        ? jsonOut(true,  'Crew assigned.')
        : jsonOut(false, dbError());
}

/* ════════════════════════════════════════
   CHANGE CREW — admin OR crew
════════════════════════════════════════ */
if ($action === 'change_crew') {
    if (!$can_edit_cnr && !$is_crew) jsonOut(false, 'Unauthorized.');
    if (!$trn) jsonOut(false, 'No record specified.');

    $newCrew = trim($_POST['crew_name'] ?? '');

    if ($is_crew && !$can_edit_cnr) {
        if ($newCrew !== $crewName && $newCrew !== '') {
            jsonOut(false, 'You can only assign complaints to yourself.');
        }
    }

    $stmt = sqlsrv_query($conn,
        "UPDATE tbl_cnr SET crew=? WHERE trn=?",
        [$newCrew ?: null, $trn]);

    $stmt !== false
        ? jsonOut(true,  'Crew updated.')
        : jsonOut(false, dbError());
}

/* ════════════════════════════════════════
   ACCEPT — crew only
════════════════════════════════════════ */
if ($action === 'accept') {
    if (!$is_crew) jsonOut(false, 'Unauthorized.');
    if (!$trn)     jsonOut(false, 'No record specified.');
    if (!$crewName) jsonOut(false, 'Could not resolve your name. Please re-login.');

    $stmt = sqlsrv_query($conn,
        "UPDATE tbl_cnr SET crew=? WHERE trn=? AND (crew IS NULL OR crew='')",
        [$crewName, $trn]);

    if ($stmt === false) jsonOut(false, dbError());

    $affected = sqlsrv_rows_affected($stmt);
    $affected > 0
        ? jsonOut(true,  'Complaint accepted.')
        : jsonOut(false, 'This complaint has already been accepted by someone else.');
}

/* ════════════════════════════════════════
   DONE — admin OR assigned crew
════════════════════════════════════════ */
if ($action === 'done') {
    if (!$can_edit_cnr && !$is_crew) jsonOut(false, 'Unauthorized.');
    if (!$trn) jsonOut(false, 'No record specified.');

    if ($is_crew && !$can_edit_cnr) {
        $chk    = sqlsrv_query($conn, "SELECT TOP 1 crew FROM tbl_cnr WHERE trn=?", [$trn]);
        $chkRow = $chk ? sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC) : null;
        if (!$chkRow || trim($chkRow['crew'] ?? '') !== $crewName) {
            jsonOut(false, 'You are not assigned to this complaint.');
        }
    }

    $stmt = sqlsrv_query($conn,
    "UPDATE tbl_cnr SET aco_dt=GETDATE() 
     WHERE trn=? AND (aco_dt IS NULL OR YEAR(aco_dt) < 2020)",
    [$trn]);

    if ($stmt === false) jsonOut(false, dbError());

    $affected = sqlsrv_rows_affected($stmt);
    $affected > 0
        ? jsonOut(true,  'Complaint marked as done.')
        : jsonOut(false, 'Complaint is already marked as done.');
}

/* ════════════════════════════════════════
   FALLBACK
════════════════════════════════════════ */
jsonOut(false, 'Unknown action: ' . htmlspecialchars($action));
?>