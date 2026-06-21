<?php
include '../js/fphp.php';
include '../conn.php';

$userId = $_GET['userid'] ?? $_POST['userid'] ?? null;

if (!$userId) {
    die("❌ User ID not provided.");
}
?>

<style>
/* Hide the parent container's background */
#div_dentry {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* rest of your existing styles below... */
@import url('https://fonts.googleapis.com/css2?family=Lora:wght@400&family=Inter:wght@300;400;500&display=swap');

.cp-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.cp-modal {
    font-family: 'Inter', sans-serif;
    width: 420px;
    background: #fff;
    border-radius: 3px;
    border: 1px solid #dde3df;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    overflow: hidden;
    animation: cpFadeIn 0.18s ease;
}
@keyframes cpFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.cp-top-bar { height: 3px; background: #1a2e24; }
.cp-head {
    padding: 1.5rem 1.75rem 1.2rem;
    border-bottom: 1px solid #eaeaea;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.cp-title { font-family: 'Lora', serif; font-size: 18px; font-weight: 400; color: #111; margin-bottom: 3px; }
.cp-sub { font-size: 11px; font-weight: 300; color: #999; letter-spacing: 0.07em; text-transform: uppercase; }
.cp-badge {
    font-size: 10px; font-weight: 500; letter-spacing: 0.07em; text-transform: uppercase;
    background: #edf4f0; color: #2d6a4f; padding: 4px 9px;
    border-radius: 2px; border: 1px solid #b7d8c8; white-space: nowrap; flex-shrink: 0;
}
.cp-close {
    background: none; border: none; cursor: pointer;
    color: #aaa; font-size: 18px; line-height: 1;
    padding: 0; margin-left: 4px; flex-shrink: 0;
    transition: color 0.15s;
}
.cp-close:hover { color: #333; }
.cp-body { padding: 1.4rem 1.75rem; display: flex; flex-direction: column; gap: 1rem; }
.cp-section-label {
    font-size: 10px; font-weight: 500; letter-spacing: 0.1em;
    text-transform: uppercase; color: #bbb;
    padding-bottom: 6px; border-bottom: 1px solid #eee;
}
.cp-field { display: flex; flex-direction: column; gap: 5px; }
.cp-field label { font-size: 11px; font-weight: 500; letter-spacing: 0.07em; text-transform: uppercase; color: #777; }
.cp-field input {
    font-family: 'Inter', sans-serif; font-size: 13px;
    width: 100%; background: #f8f8f8; border: 1px solid #ddd;
    border-radius: 2px; padding: 9px 11px; color: #111; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.cp-field input:focus {
    border-color: #2d6a4f; box-shadow: 0 0 0 3px rgba(45,106,79,0.08); background: #fff;
}
.cp-bar-wrap { height: 2px; background: #eee; border-radius: 1px; margin-top: 3px; overflow: hidden; }
.cp-bar { height: 100%; width: 0%; border-radius: 1px; transition: width 0.3s, background 0.3s; }
.cp-bar-row { display: flex; justify-content: space-between; margin-top: 4px; }
.cp-hint { font-size: 11px; color: #bbb; }
.cp-bar-lbl { font-size: 11px; }
.cp-match { font-size: 11px; margin-top: 2px; min-height: 14px; }
.cp-foot {
    padding: 1rem 1.75rem 1.4rem;
    display: flex; justify-content: flex-end; gap: 8px;
    border-top: 1px solid #eee;
}
.cp-btn-cancel {
    font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 400;
    background: transparent; border: 1px solid #ccc; border-radius: 2px;
    padding: 8px 16px; color: #777; cursor: pointer; transition: background 0.15s;
}
.cp-btn-cancel:hover { background: #f5f5f5; }
.cp-btn-save {
    font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500; letter-spacing: 0.03em;
    background: #1a2e24; border: none; border-radius: 2px;
    padding: 8px 20px; color: #c8e6d6; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background 0.15s, transform 0.1s;
}
.cp-btn-save:hover { background: #2d4a39; }
.cp-btn-save:active { transform: scale(0.98); }
</style>

<div class="cp-overlay" id="cp-overlay">
    <form id="changePassForm" autocomplete="off">
        <input type="hidden" name="userid" value="<?= htmlspecialchars($userId) ?>">

        <div class="cp-modal">
            <div class="cp-top-bar"></div>

            <div class="cp-head">
                <div>
                    <div class="cp-title">Change Password</div>
                    <div class="cp-sub">Account credentials</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="cp-badge">Secure Form</span>
                    <button type="button" class="cp-close" onclick="cpClose()">&#x2715;</button>
                </div>
            </div>

            <div class="cp-body">
                <div class="cp-section-label">New credentials</div>

                <div class="cp-field">
                    <label for="new">New Password</label>
                    <input type="password" id="new" name="new" placeholder="Enter new password" required oninput="cpStrength(this.value)">
                    <div class="cp-bar-wrap"><div class="cp-bar" id="cp-sbar"></div></div>
                    <div class="cp-bar-row">
                        <span class="cp-hint">Min. 8 characters recommended</span>
                        <span class="cp-bar-lbl" id="cp-slabel"></span>
                    </div>
                </div>

                <div class="cp-field">
                    <label for="confirm">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm" placeholder="Re-enter password" required oninput="cpMatchCheck()">
                    <span class="cp-match" id="cp-mlabel"></span>
                </div>
            </div>

            <div class="cp-foot">
                <button type="button" class="cp-btn-cancel" onclick="cpClose()">Cancel</button>
                <button type="button" class="cp-btn-save" onclick="SaveData('utility/user_change_password_save.php','changePassForm','','','');cpClose();">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Update Password
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function cpClose() {
    document.getElementById('cp-overlay').style.display = 'none';
    $('#div_dentry').hide();
}

document.getElementById('cp-overlay').addEventListener('click', function(e) {
    if (e.target === this) cpClose();
});

function cpStrength(v) {
    const bar = document.getElementById('cp-sbar');
    const lbl = document.getElementById('cp-slabel');
    if (!v) { bar.style.width = '0%'; lbl.textContent = ''; return; }
    let s = 0;
    if (v.length >= 8) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    const L = [
        { w: '18%', c: '#c0392b', t: 'Weak' },
        { w: '45%', c: '#d68910', t: 'Fair' },
        { w: '72%', c: '#1e8449', t: 'Good' },
        { w: '100%', c: '#1a5c36', t: 'Strong' }
    ];
    const l = L[Math.min(s - 1, 3)];
    bar.style.width = l.w; bar.style.background = l.c;
    lbl.textContent = l.t; lbl.style.color = l.c;
    cpMatchCheck();
}

function cpMatchCheck() {
    const n = document.getElementById('new').value;
    const c = document.getElementById('confirm').value;
    const m = document.getElementById('cp-mlabel');
    if (!c) { m.textContent = ''; return; }
    m.textContent = n === c ? 'Passwords match' : 'Passwords do not match';
    m.style.color = n === c ? '#1e8449' : '#c0392b';
}
</script>