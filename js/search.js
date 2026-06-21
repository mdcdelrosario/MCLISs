function tableSearch(input, tableId, colIndexes) {
    var filter = input.value.trim().toLowerCase();
    var table  = document.getElementById(tableId);
    if (!table) return;

    var rows    = table.querySelectorAll('tbody tr');
    var visible = 0;

    rows.forEach(function(row) {
        if (row.classList.contains('ts-no-results')) return;

        var text = '';
        if (colIndexes && colIndexes.length) {
            colIndexes.forEach(function(i) {
                var td = row.cells[i];
                if (!td) return;
                // grab input values and select text too
                td.querySelectorAll('input[type="text"], input[type="hidden"]').forEach(function(el) {
                    text += el.value + ' ';
                });
                td.querySelectorAll('select').forEach(function(el) {
                    var opt = el.options[el.selectedIndex];
                    if (opt) text += opt.text + ' ';
                });
                // also grab plain text (for static cells)
                text += td.textContent + ' ';
            });
        } else {
            row.querySelectorAll('input[type="text"]').forEach(function(el) {
                text += el.value + ' ';
            });
            row.querySelectorAll('select').forEach(function(el) {
                var opt = el.options[el.selectedIndex];
                if (opt) text += opt.text + ' ';
            });
            text += row.textContent + ' ';
        }

        if (text.toLowerCase().indexOf(filter) > -1) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    var noRow    = table.querySelector('.ts-no-results');
    var colCount = table.querySelector('thead tr') ? table.querySelector('thead tr').cells.length : 1;

    if (visible === 0 && filter !== '') {
        if (!noRow) {
            noRow = document.createElement('tr');
            noRow.className = 'ts-no-results';
            noRow.innerHTML = '<td colspan="' + colCount + '"><div class="um-empty" style="padding:28px 20px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><p>No results for &ldquo;' + escHtml(input.value) + '&rdquo;</p></div></td>';
            table.querySelector('tbody').appendChild(noRow);
        } else {
            noRow.style.display = '';
            noRow.querySelector('p').innerHTML = 'No results for &ldquo;' + escHtml(input.value) + '&rdquo;';
        }
    } else if (noRow) {
        noRow.style.display = 'none';
    }
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}