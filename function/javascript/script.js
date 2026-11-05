var STORAGE_KEY = 'sidebar_open_groups';

// Save / load helpers
function getSaved() {
    try {
        return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
    } catch (e) {
        return [];
    }
}

function saveOpen() {
    var open = [];
    document.querySelectorAll('.sidebtn').forEach(function (group, i) {
        if (group.classList.contains('open')) open.push(i);
    });
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(open));
    } catch (e) {}
}

// Page load e saved group gulo khola
var saved = getSaved();
document.querySelectorAll('.sidebtn').forEach(function (group, i) {
    if (saved.indexOf(i) !== -1) group.classList.add('open');
});

// Cholte thaka page (.active) er group khola thakbe
document.querySelectorAll('.sidebtn subbtn.active').forEach(function (item) {
    item.closest('.sidebtn').classList.add('open');
});
saveOpen();

// Title e click korle toggle + save
document.addEventListener('click', function (e) {
    var title = e.target.closest('.sidebtn > main');
    if (title) {
        title.parentElement.classList.toggle('open');
        saveOpen();
    }
});


function limitText(data) {
    const inputID = document.getElementById(data.id);
    const inputLimit = data.limit;

    if (inputID.value.length > inputLimit) {
        inputID.value = inputID.value.slice(0, inputLimit);
    }
}

// Table search — searchInput e lekhle table er row filter hobe
function searchTable() {
    var input = document.getElementById('searchInput');
    if (!input) return;
    var filter = input.value.toLowerCase();
    document.querySelectorAll('table tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    });
}

// data-digits field e shudhu digit (maxlength obdhi)
document.addEventListener('input', function (e) {
    var el = e.target;
    if (el.matches && el.matches('[data-digits]')) {
        el.value = el.value.replace(/\D/g, '');
        if (el.maxLength > 0) el.value = el.value.slice(0, el.maxLength);
    }
});


// <select data-filter-by="f_branch_id"> — option er data-branch dekhe filter kore (branch -> area/center/officer)
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-filter-by]').forEach(function (sel) {
        var src = document.getElementById(sel.getAttribute('data-filter-by'));
        if (!src) return;
        function apply() {
            var v = src.value;
            Array.prototype.forEach.call(sel.options, function (o) {
                if (!o.value) return;
                var show = !v || o.getAttribute('data-branch') === v;
                o.hidden = !show;
                o.disabled = !show;
            });
            if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled) sel.value = '';
        }
        src.addEventListener('change', apply);
        apply();
    });
});
