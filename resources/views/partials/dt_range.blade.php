
@once
<script>
window.DtRange = (function () {
    const pad = n => String(n).padStart(2, '0');
    // Local-date formatters. Never use toISOString(): it converts to UTC and can shift the day.
    const ymd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const ym  = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;

    const PRESETS = {
        today:     { label: 'Today',      calc: n => [n, n] },
        week:      { label: 'This week',  calc: n => {
            const mon = new Date(n);
            mon.setDate(n.getDate() - ((n.getDay() + 6) % 7)); // Monday start
            const sun = new Date(mon);
            sun.setDate(mon.getDate() + 6);
            return [mon, sun];
        } },
        month:     { label: 'This month', calc: n => [new Date(n.getFullYear(), n.getMonth(), 1), new Date(n.getFullYear(), n.getMonth() + 1, 0)] },
        lastmonth: { label: 'Last month', calc: n => [new Date(n.getFullYear(), n.getMonth() - 1, 1), new Date(n.getFullYear(), n.getMonth(), 0)] },
        year:      { label: 'This year',  calc: n => [new Date(n.getFullYear(), 0, 1), new Date(n.getFullYear(), 11, 31)] },
    };

    function mount(tableSelector, options) {
        const opts = Object.assign({
            label: 'Date', type: 'date',
            presets: ['today', 'week', 'month', 'year'],
            exportUrl: null, rangeBy: null, hint: true,
            navigate: u => { window.location.href = u; },   // overridable (used by tests)
        }, options || {});

        const tableEl = document.querySelector(tableSelector);
        if (!tableEl) {
            console.warn('[DtRange] table not found:', tableSelector);
            return null;
        }

        const key = tableEl.id || 'dt';
        const old = document.getElementById(key + '_range');
        if (old) old.remove();                       // safe to call twice

        const fmt = opts.type === 'month' ? ym : ymd;

        const presetHtml = opts.presets.filter(p => PRESETS[p]).map(p =>
            `<button type="button" class="btn btn-outline-secondary" data-preset="${p}">${PRESETS[p].label}</button>`
        ).join('');

        const byHtml = opts.rangeBy ? `
            <div class="col-auto">
                <label class="form-label small mb-0">Filter by</label>
                <select class="form-select form-select-sm" data-role="by">
                    ${opts.rangeBy.map(o => `<option value="${o.value}">${o.label}</option>`).join('')}
                </select>
            </div>` : '';

        const exportHtml = opts.exportUrl
            ? ' <button type="button" class="btn btn-sm btn-success" data-role="export"><i class="bx bx-spreadsheet me-1"></i> Export all (filtered)</button>'
            : '';

        const hintHtml = opts.hint ? ` ` : '';

        const bar = document.createElement('div');
        bar.id = key + '_range';
        bar.className = 'row g-2 align-items-end mb-3';
        bar.innerHTML = `
            ${byHtml}
            <div class="col-auto">
                <label class="form-label small mb-0">${opts.label} from</label>
                <input type="${opts.type}" class="form-control form-control-sm" data-role="from">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">to</label>
                <input type="${opts.type}" class="form-control form-control-sm" data-role="to">
            </div>
            <div class="col-auto">
                <div class="btn-group btn-group-sm" role="group">${presetHtml}</div>
                <a type="button" class="btn btn-sm btn-outline-danger" data-role="clear">Clear</a>${exportHtml}
            </div>
            ${hintHtml}`;

        // Insert above the table's scroll wrapper (mount BEFORE creating the DataTable).
        const anchor = tableEl.closest('.table-responsive') || tableEl;
        anchor.parentNode.insertBefore(bar, anchor);

        const $r = role => bar.querySelector(`[data-role="${role}"]`);
        const fromEl = $r('from'), toEl = $r('to'), byEl = $r('by');

        const api = {
            values: () => ({ from: fromEl.value, to: toEl.value, by: byEl ? byEl.value : '' }),

            // Use as ajax.data (or call inside your own ajax.data function).
            append: function (d) {
                d.from = fromEl.value;
                d.to = toEl.value;
                if (byEl) d.range_by = byEl.value;
                return d;
            },

            bind: function (table) {
                let timer = null;
                const redraw = () => { clearTimeout(timer); timer = setTimeout(() => table.draw(), 250); };

                // Listen for both: some browsers fire only `change` for <input type=date>.
                const isField = e => e.target === fromEl || e.target === toEl || (byEl && e.target === byEl);
                bar.addEventListener('input',  e => { if (isField(e)) redraw(); });
                bar.addEventListener('change', e => { if (isField(e)) redraw(); });

                bar.addEventListener('click', e => {
                    const preset = e.target.closest('[data-preset]');
                    if (preset) {
                        const [a, b] = PRESETS[preset.dataset.preset].calc(new Date());
                        fromEl.value = fmt(a);
                        toEl.value = fmt(b);
                        table.draw();
                        return;
                    }
                    if (e.target.closest('[data-role="clear"]')) {
                        fromEl.value = '';
                        toEl.value = '';
                        table.draw();
                        return;
                    }
                    if (e.target.closest('[data-role="export"]')) {
                        // Compact params only: the full DataTables payload (every column's
                        // metadata) can exceed URL length limits.
                        const p = (table.ajax.params && table.ajax.params()) || {};
                        const q = new URLSearchParams();
                        if (fromEl.value) q.set('from', fromEl.value);
                        if (toEl.value) q.set('to', toEl.value);
                        if (byEl) q.set('range_by', byEl.value);
                        if (p.search && p.search.value) q.set('search[value]', p.search.value);
                        if (p.order && p.order[0]) {
                            q.set('order[0][column]', p.order[0].column);
                            q.set('order[0][dir]', p.order[0].dir);
                        }
                        (p.columns || []).forEach((c, i) => {
                            const v = (c.columnControl && c.columnControl.search && c.columnControl.search.value)
                                   || (c.search && c.search.value) || '';
                            if (v) q.set(`columns[${i}][columnControl][search][value]`, v);
                        });
                        opts.navigate(opts.exportUrl + (opts.exportUrl.includes('?') ? '&' : '?') + q.toString());
                    }
                });
            },
        };

        return api;
    }

    return { mount: mount };
})();
</script>
@endonce
