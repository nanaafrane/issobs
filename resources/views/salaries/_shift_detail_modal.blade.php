{{--
    "Details" from a WORKED cell (App\Support\SalaryShifts::worked): where the guard worked,
    his double duties and his overtime that month, without opening the attendance sheet.
--}}
@once
<style>#shiftDetail td:first-child, #shiftDetail td:nth-child(2) { white-space: nowrap; } #shiftDetail::backdrop { background: rgba(15, 23, 42, .45); }</style>
<dialog id="shiftDetail" style="border:0;border-radius:.75rem;padding:0;width:min(760px,96vw);max-height:88vh;box-shadow:0 20px 50px rgba(0,0,0,.25)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid #eceef1;position:sticky;top:0;background:#fff">
        <div>
            <div style="font-weight:600;font-size:1.05rem" data-f="name"></div>
            <div class="text-muted small" data-f="month"></div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-close>Close</button>
    </div>
    <div style="padding:1rem 1.25rem;overflow:auto">
        <h6 class="mb-2">Clients worked <span class="text-muted fw-normal small">· shifts present</span></h6>
        <table class="table table-sm mb-4"><thead><tr><th>Client</th><th class="text-end">Day</th><th class="text-end">Night</th><th class="text-end">Total</th></tr></thead><tbody data-f="clients"></tbody></table>

        <h6 class="mb-2">Double duties <span class="text-muted fw-normal small">· present on day and night of one date</span></h6>
        <table class="table table-sm mb-4"><thead><tr><th>Date</th><th>Day shift at</th><th>Night shift at</th></tr></thead><tbody data-f="doubles"></tbody></table>

        <h6 class="mb-2">Overtime <span class="text-muted fw-normal small">· entries he worked this month</span></h6>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Shift</th><th>Client</th><th>Type</th><th>Covering</th><th>Pay by</th><th class="text-end">GH₵</th><th>Status</th></tr></thead><tbody data-f="ot"></tbody></table></div>
    </div>
</dialog>
<script>
(function () {
    var dlg = document.getElementById('shiftDetail');
    if (!dlg || !dlg.showModal) { return; }
    function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function rows(list, cols, empty, span) {
        if (!list.length) { return '<tr><td colspan="' + span + '" class="text-muted">' + empty + '</td></tr>'; }
        return list.map(function (r) { return '<tr>' + cols.map(function (c) { return '<td' + (c.end ? ' class="text-end"' : '') + '>' + esc(c.v(r)) + '</td>'; }).join('') + '</tr>'; }).join('');
    }
    var stateColor = { 'Approved': '#067647', 'In review': '#a15c00', 'Rejected': '#667085' };
    document.addEventListener('click', function (e) {
        var b = e.target.closest('.shift-detail-btn');
        if (b) {
            var d = JSON.parse(b.dataset.detail || '{}');
            dlg.querySelector('[data-f=name]').textContent = d.name || 'Guard';
            dlg.querySelector('[data-f=month]').textContent = d.month || '';
            dlg.querySelector('[data-f=clients]').innerHTML = rows(d.clients || [], [
                { v: function (r) { return r.name; } }, { v: function (r) { return r.day; }, end: 1 },
                { v: function (r) { return r.night; }, end: 1 }, { v: function (r) { return r.day + r.night; }, end: 1 }
            ], 'No shifts present this month.', 4);
            dlg.querySelector('[data-f=doubles]').innerHTML = rows(d.doubles || [], [
                { v: function (r) { return r.date; } }, { v: function (r) { return r.day; } }, { v: function (r) { return r.night; } }
            ], 'No double duty this month.', 3);
            var ot = dlg.querySelector('[data-f=ot]');
            ot.innerHTML = rows(d.ot || [], [
                { v: function (r) { return r.date; } }, { v: function (r) { return r.shift; } }, { v: function (r) { return r.client; } },
                { v: function (r) { return r.type; } }, { v: function (r) { return r.for; } }, { v: function (r) { return r.pay; } },
                { v: function (r) { return r.amount; }, end: 1 }, { v: function (r) { return r.state; } }
            ], 'No overtime this month.', 8);
            (d.ot || []).forEach(function (r, i) {
                var cell = ot.rows[i] && ot.rows[i].cells[7];
                if (cell) { cell.style.color = stateColor[r.state] || ''; cell.style.fontWeight = '600'; }
            });
            dlg.showModal();
            return;
        }
        if (e.target.closest('#shiftDetail [data-close]') || e.target === dlg) { dlg.close(); }
    });
})();
</script>
@endonce
