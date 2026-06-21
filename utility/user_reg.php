<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<script>
$(function() {
    $('#fname').focus();
});

function showErr(id, msg) {
    var el = document.getElementById('err-' + id);
    if (!el) return;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
    var input = document.getElementById(id);
    if (input) input.style.borderColor = msg ? '#e74c3c' : '';
}

function clearErr(id) { showErr(id, ''); }

function validateField(id) {
    var val = $('#' + id).val().trim();
    switch(id) {
        case 'fname':
        case 'lname':
            if (val.length === 0)       showErr(id, 'This field is required.');
            else if (val.length < 2)    showErr(id, 'Must be at least 2 characters.');
            else                        clearErr(id);
            break;
        case 'uname':
            if (val.length === 0)       showErr(id, 'Username is required.');
            else if (val.length < 3)    showErr(id, 'Must be at least 3 characters.');
            else if (!/^[a-z0-9]+$/i.test(val)) showErr(id, 'Letters and numbers only.');
            else                        clearErr(id);
            break;
        case 'init':
            if (val.length === 0)       showErr(id, 'Initial is required.');
            else                        clearErr(id);
            break;
        case 'pass':
            if (val.length === 0)       showErr(id, 'Password is required.');
            else if (val.length < 8)    showErr(id, 'Password must be at least 8 characters.');
            else                        clearErr(id);
            // re-validate confirm if already filled
            if ($('#passconf').val().length > 0) validateField('passconf');
            break;
        case 'passconf':
            if (val.length === 0)       showErr(id, 'Please confirm your password.');
            else if (val !== $('#pass').val()) showErr(id, 'Passwords do not match.');
            else                        clearErr(id);
            break;
    }
    checkInputs();
}

function checkInputs() {
    var fname    = $('#fname').val().trim();
    var lname    = $('#lname').val().trim();
    var uname    = $('#uname').val().trim();
    var init     = $('#init').val().trim();
    var pass     = $('#pass').val();
    var passconf = $('#passconf').val();

    var valid = fname.length >= 2
             && lname.length >= 2
             && uname.length >= 3
             && /^[a-z0-9]+$/i.test(uname)
             && init.length > 0
             && pass.length >= 8
             && pass === passconf;

    $('#btnreg').prop('disabled', !valid);

    // Password strength
    var len = pass.length;
    var bar = $('#pw-bar');
    var lbl = $('#pw-label');
    if (len === 0) {
        bar.css({ width: '0%', background: '#eee' });
        lbl.text('').css('color', '#aaa');
    } else if (len < 8) {
        bar.css({ width: '33%', background: '#e74c3c' });
        lbl.text('Too short').css('color', '#e74c3c');
    } else if (len < 12) {
        bar.css({ width: '66%', background: '#f39c12' });
        lbl.text('Fair').css('color', '#f39c12');
    } else {
        bar.css({ width: '100%', background: '#1e6b3c' });
        lbl.text('Strong').css('color', '#1e6b3c');
    }
}

function closeReg() {
    $('#div_reg_user').empty();
    $('#div_dentry').hide();
}

function togglePass(id, iconEl) {
    var inp = document.getElementById(id);
    if (inp.type === 'password') {
        inp.type = 'text';
        iconEl.className = 'fa fa-eye-slash';
    } else {
        inp.type = 'password';
        iconEl.className = 'fa fa-eye';
    }
}

function registerUser() {
    // Run all validations before submitting
    ['fname','lname','uname','init','pass','passconf'].forEach(function(id) {
        validateField(id);
    });

    if ($('#btnreg').prop('disabled')) return;

    $('#btnreg').prop('disabled', true).html(
        '<i class="fa fa-spinner fa-spin"></i> Registering...'
    );

    $.ajax({
        url:  'utility/user_create.php',
        type: 'POST',
        data: $('#fdatareg').serialize(),
        success: function(response) {
            var res = response.trim();
            if (res.indexOf('✅') !== -1) {
                showmsgboxJS('Success', 'User successfully registered!', function() {
                    closeReg();
                    PageLink('utility/user_mgmt.php', 'main');
                });
            } else if (res.indexOf('Username already exists') !== -1) {
                showErr('uname', 'Username already taken. Please choose another.');
                restoreBtn();
            } else if (res.indexOf('Invalid User Rights') !== -1) {
                showmsgboxJS('Error', 'You do not have permission to register users.');
                restoreBtn();
            } else {
                showmsgboxJS('Error', 'Registration failed: ' + res);
                restoreBtn();
            }
        },
        error: function(xhr) {
            showmsgboxJS('Error', 'Server error ' + xhr.status + '. Please try again.');
            restoreBtn();
        }
    });
}

function restoreBtn() {
    $('#btnreg').prop('disabled', false).html(
        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">' +
        '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>' +
        '<circle cx="9" cy="7" r="4"/>' +
        '<line x1="19" y1="8" x2="19" y2="14"/>' +
        '<line x1="22" y1="11" x2="16" y2="11"/></svg> Register User'
    );
}
</script>

<style>
.field-err {
    display: none;
    font-size: 11px;
    color: #e74c3c;
    margin-top: 3px;
}
.field-err::before {
    content: '⚠ ';
}
</style>

<div style="font-family:'Inter',sans-serif;">

  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 14px;border-bottom:1px solid #e4e9e4;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:34px;height:34px;border-radius:8px;background:#edf7f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1e6b3c" stroke-width="1.8">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="19" y1="8" x2="19" y2="14"/>
          <line x1="22" y1="11" x2="16" y2="11"/>
        </svg>
      </div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#111;">Register User</div>
        <div style="font-size:11px;color:#aaa;">Fill in the form to create a new account</div>
      </div>
    </div>
    <button onclick="closeReg()" type="button"
      style="width:28px;height:28px;border-radius:6px;border:1px solid #e4e9e4;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#aaa;"
      onmouseover="this.style.background='#fff5f5';this.style.color='#c0392b';"
      onmouseout="this.style.background='#fff';this.style.color='#aaa';">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>

  <!-- Form -->
  <form id="fdatareg" autocomplete="off">
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">

      <!-- First & Last name -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">First Name</label>
          <input class="uf-input input-capitalize"
            onkeypress="return /[a-zA-Z ]/i.test(event.key)"
            oninput="validateField('fname')"
            onblur="validateField('fname')"
            type="text" placeholder="e.g. Juan" name="fname" id="fname" required>
          <span class="field-err" id="err-fname"></span>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Last Name</label>
          <input class="uf-input input-capitalize"
            onkeypress="return /[a-zA-Z ]/i.test(event.key)"
            oninput="validateField('lname')"
            onblur="validateField('lname')"
            type="text" placeholder="e.g. Dela Cruz" name="lname" id="lname" required>
          <span class="field-err" id="err-lname"></span>
        </div>
      </div>

      <!-- Username & Restriction -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Username</label>
          <input class="uf-input"
            onkeypress="return /[a-z0-9]/i.test(event.key)"
            oninput="validateField('uname')"
            onblur="validateField('uname')"
            type="text" placeholder="e.g. jdelacruz" name="uname" id="uname" required>
          <span class="field-err" id="err-uname"></span>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Restriction Code</label>
          <input class="uf-input"
            type="text" placeholder="Optional" name="urestrict" id="urestrict">
        </div>
      </div>

      <!-- Initial & Level -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Initial</label>
          <input class="uf-input"
            oninput="this.value=this.value.toUpperCase(); validateField('init');"
            onblur="validateField('init')"
            type="text" placeholder="e.g. JDC" name="init" id="init" maxlength="10" required>
          <span class="field-err" id="err-init"></span>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Account Level</label>
          <select name="lvl" id="lvl" class="uf-select">
            <?php
              $sql  = "SELECT level FROM tbl_userrights";
              $stmt = sqlsrv_query($conn, $sql);
              if ($stmt) {
                  while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                      $lvl = htmlspecialchars($row['level']);
                      echo "<option value=\"$lvl\">$lvl</option>";
                  }
                  sqlsrv_free_stmt($stmt);
              }
              sqlsrv_close($conn);
            ?>
          </select>
        </div>
      </div>

      <!-- Password -->
      <div style="display:flex;flex-direction:column;gap:5px;">
        <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Password</label>
        <div style="position:relative;">
          <input class="uf-input" type="password" placeholder="Minimum 8 characters"
            name="pass" id="pass" required style="padding-right:38px;"
            oninput="validateField('pass')"
            onblur="validateField('pass')">
          <button type="button" onclick="togglePass('pass', this.querySelector('i'))"
            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#aaa;padding:0;">
            <i class="fa fa-eye"></i>
          </button>
        </div>
        <div style="height:4px;background:#eee;border-radius:2px;overflow:hidden;margin-top:2px;">
          <div id="pw-bar" style="height:100%;width:0%;background:#eee;border-radius:2px;transition:width .25s,background .25s;"></div>
        </div>
        <div id="pw-label" style="font-size:10.5px;color:#aaa;margin-top:1px;"></div>
        <span class="field-err" id="err-pass"></span>
      </div>

      <!-- Confirm Password -->
      <div style="display:flex;flex-direction:column;gap:5px;">
        <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Confirm Password</label>
        <div style="position:relative;">
          <input class="uf-input" type="password" placeholder="Repeat password"
            name="passconf" id="passconf" required style="padding-right:38px;"
            oninput="validateField('passconf')"
            onblur="validateField('passconf')">
          <button type="button" onclick="togglePass('passconf', this.querySelector('i'))"
            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#aaa;padding:0;">
            <i class="fa fa-eye"></i>
          </button>
        </div>
        <span class="field-err" id="err-passconf"></span>
      </div>

    </div>

    <!-- Footer -->
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:14px 20px 18px;border-top:1px solid #f0f2f0;">
      <button type="button" class="uf-btn-cancel" onclick="closeReg()">Cancel</button>
      <button type="button" class="uf-btn-save" id="btnreg" disabled onclick="registerUser()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="19" y1="8" x2="19" y2="14"/>
          <line x1="22" y1="11" x2="16" y2="11"/>
        </svg>
        Register User
      </button>
    </div>
  </form>

</div>