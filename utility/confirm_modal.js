/* ============================================================
   confirm_modal.js  —  Reusable confirmation dialog
   Usage:
     showConfirm({
       title:   'Delete User',           // optional, default: 'Confirm'
       message: 'Are you sure you want to delete this user?',
       onConfirm: function() {
         // your action here
       }
     });
   ============================================================ */

function showConfirm(options) {
    var title   = options.title   || 'Confirm';
    var message = options.message || 'Are you sure?';
    var onConfirm = options.onConfirm || function() {};

    /* Set content */
    document.getElementById('cm-title').textContent   = title;
    document.getElementById('cm-message').textContent = message;

    /* Wire confirm button */
    var confirmBtn = document.getElementById('cm-confirm-btn');
    /* Clone to remove old listeners */
    var newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener('click', function() {
        hideConfirm();
        onConfirm();
    });

    /* Show overlay */
    document.getElementById('cm-overlay').style.display = 'flex';
}

function hideConfirm() {
    document.getElementById('cm-overlay').style.display = 'none';
}

/* Close on backdrop click */
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('cm-overlay').addEventListener('click', function(e) {
        if (e.target === this) hideConfirm();
    });

    /* Close on ESC */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') hideConfirm();
    });
});