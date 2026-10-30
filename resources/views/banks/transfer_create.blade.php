<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .mv-side { border-top: 3px solid; }
        .mv-side.out { border-top-color: #ff3e1d; }
        .mv-side.in { border-top-color: #71dd37; }
        .mv-side .card-header { display: flex; justify-content: space-between; align-items: center; }
        .mv-step { display: inline-flex; width: 1.6rem; height: 1.6rem; border-radius: 50%; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; margin-right: .4rem; color: #fff; }
        .out .mv-step { background: #ff3e1d; } .in .mv-step { background: #56ca00; }
        .mv-row { border: 1px solid #e7e7ef; border-radius: .6rem; padding: .75rem; margin-bottom: .65rem; background: #fff; position: relative; }
        .mv-row.mv-bad { border-color: #ff3e1d; box-shadow: 0 0 0 .15rem rgba(255, 62, 29, .12); }
        .mv-row .mv-x { position: absolute; top: .35rem; right: .5rem; border: 0; background: none; color: #a1acb8; font-size: 1.1rem; line-height: 1; }
        .mv-row .mv-x:hover { color: #ff3e1d; }
        .mv-pills { display: flex; flex-wrap: wrap; gap: .3rem; }
        .mv-pill { border: 1px solid #d9dee3; background: #fff; border-radius: 2rem; padding: .2rem .7rem; font-size: .8rem; cursor: pointer; color: #566a7f; }
        .mv-pill.on { background: #696cff; border-color: #696cff; color: #fff; }
        .mv-pill i { margin-right: .2rem; }
        .mv-hint { font-size: .75rem; color: #8592a3; }
        .mv-after { font-size: .75rem; }
        .mv-presets .btn { font-size: .8rem; }
        .mv-bar { position: sticky; bottom: 0; z-index: 5; background: #fff; border-top: 1px solid #e7e7ef; box-shadow: 0 -4px 14px rgba(67, 89, 113, .08); padding: .7rem 1rem; border-radius: .5rem .5rem 0 0; }
        .mv-bar .lbl { font-size: .72rem; color: #8592a3; text-transform: uppercase; letter-spacing: .03em; }
        .mv-bar .val { font-size: 1.15rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .mv-flow { display: flex; align-items: center; gap: .5rem; font-size: .85rem; padding: .3rem 0; border-bottom: 1px dashed #eceef1; }
        .mv-flow:last-child { border-bottom: 0; }
        .mv-flow .arrow { color: #8592a3; flex: 1; text-align: center; font-size: .75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mv-arrow-mid { display: none; }
        @media (min-width: 1200px) { .mv-arrow-mid { display: flex; align-items: flex-start; justify-content: center; padding-top: 4.5rem; font-size: 1.8rem; color: #c3c8cf; } }
    </style>
    @endsection

    @section('content')
    @php
        $oldSources = old('sources');
        $oldDestinations = old('destinations');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('money.transfers') }}" class="small"><i class="bx bx-arrow-back"></i> Transfers</a>
        <h3 class="text-primary mt-1 mb-1"><i class="bx bx-transfer me-1"></i> Move money between our accounts</h3>
        <p class="text-muted small mb-3">Out of one or more accounts, into one or more accounts — by bank transfer, cheque (deposited, or cashed and shared out), cash withdrawal or cash.
            It is our own money changing place: not income or spending. The preview shows exactly what each account's statement will show before you save.</p>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="mb-3 mv-presets">
            <div class="small text-muted mb-1">What are you doing? <span class="mv-hint">(fills the form — change anything after)</span></div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" data-preset="fund"><i class="bx bx-wallet"></i> Fund Main Cash with a cheque</button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-preset="share"><i class="bx bx-git-branch"></i> Cash a cheque and share it</button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-preset="bank"><i class="bx bx-transfer-alt"></i> Bank → bank transfer</button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-preset="chequedeposit"><i class="bx bx-receipt"></i> Deposit a cheque into another bank</button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-preset="cashin"><i class="bx bx-building-house"></i> Pay cash into a bank</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-preset="blank">Start blank</button>
            </div>
        </div>

        <form method="POST" action="{{ route('money.transfers.store') }}" id="mvForm" autocomplete="off">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}

            <div class="card mb-3"><div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-6"><label class="form-label small mb-0" for="mvDate">Date</label>
                        <input type="date" name="date" id="mvDate" class="form-control form-control-sm" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-9 col-6"><label class="form-label small mb-0" for="mvNotes">Purpose / notes</label>
                        <input name="notes" id="mvNotes" class="form-control form-control-sm" maxlength="500" value="{{ old('notes') }}" placeholder="e.g. October imprest; Kumasi and Tema fuel float"></div>
                </div>
            </div></div>

            <div class="row g-3">
                <div class="col-xl">
                    <div class="card mv-side out h-100">
                        <div class="card-header py-2"><h6 class="mb-0"><span class="mv-step">1</span>Money comes out of</h6><span class="mv-num fw-semibold text-danger" id="mvOutHead"></span></div>
                        <div class="card-body pt-0">
                            <div id="mvSources"></div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="mvAddSource"><i class="bx bx-plus"></i> Another account / cheque</button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-auto mv-arrow-mid"><i class="bx bx-right-arrow-alt"></i></div>
                <div class="col-xl">
                    <div class="card mv-side in h-100">
                        <div class="card-header py-2"><h6 class="mb-0"><span class="mv-step">2</span>Money goes into</h6><span class="mv-num fw-semibold text-success" id="mvInHead"></span></div>
                        <div class="card-body pt-0">
                            <div id="mvDestinations"></div>
                            <button type="button" class="btn btn-sm btn-outline-success" id="mvAddDest"><i class="bx bx-plus"></i> Another account</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header py-2"><h6 class="mb-0"><span class="mv-step bg-primary">3</span>Check: what each statement will show</h6></div>
                <div class="card-body pt-0">
                    <div id="mvProblem" class="alert alert-danger py-2 small d-none"></div>
                    <div id="mvWarn" class="alert alert-warning py-2 small d-none"></div>
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="table-responsive"><table class="table table-sm mb-0">
                                <thead><tr><th>Account</th><th>Date</th><th>Line on its statement</th><th class="mv-num">Out</th><th class="mv-num">In</th><th class="mv-num">Balance after</th></tr></thead>
                                <tbody id="mvLines"><tr><td colspan="6" class="text-muted small py-3 text-center">Fill in both sides to see the preview.</td></tr></tbody>
                            </table></div>
                        </div>
                        <div class="col-lg-4">
                            <div class="small text-muted mb-1">Who funded what</div>
                            <div id="mvPairs" class="small text-muted">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mv-bar mt-3 d-flex flex-wrap align-items-center gap-4">
                <div><div class="lbl">Out</div><div class="val text-danger" id="mvOut">0.00</div></div>
                <div><div class="lbl">In</div><div class="val text-success" id="mvIn">0.00</div></div>
                <div><div class="lbl">Difference</div><div class="val" id="mvDiff">—</div></div>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <span class="small text-muted" id="mvStatus"></span>
                    <button type="submit" class="btn btn-primary" id="mvSave" disabled>Save</button>
                </div>
            </div>
        </form>
    </div>
    @endsection

    @section('scripts')
    <script>
    (function () {
        const ACCOUNTS = @json($accountsJs);
        const LEAVE = @json(\App\Services\Banking\MovePlan::LEAVE);
        const ICON = { transfer: 'bx-transfer-alt', cheque: 'bx-receipt', withdrawal: 'bx-money-withdraw', cash: 'bx-money' };
        const PREVIEW = @json(route('money.transfers.preview'));
        const byId = id => ACCOUNTS.find(a => String(a.id) === String(id));
        // Presets: the bank holding the most money, and Main Cash (else the first cash account).
        const firstBank = (not) => ACCOUNTS.filter(a => !a.cash && !(not || []).includes(a.id)).sort((x, y) => y.balance - x.balance)[0];
        const firstCash = () => ACCOUNTS.find(a => a.cash && /main\s*cash/i.test(a.name)) || ACCOUNTS.find(a => a.cash);
        const money = v => (Math.round((+v || 0) * 100) / 100).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        const blankOut = () => ({ bank_id: '', how: '', reference: '', amount: '', date: '', showDate: false });
        const blankIn = () => ({ bank_id: '', how: '', cheque: '', reference: '', amount: '', date: '', showDate: false });
        let S = {
            sources: @json($oldSources ? array_values($oldSources) : null),
            destinations: @json($oldDestinations ? array_values($oldDestinations) : null),
        };
        if (!S.sources) {
            S.sources = [Object.assign(blankOut(), { bank_id: @json($fromId) || '' })];
            S.destinations = [Object.assign(blankIn(), { bank_id: @json($toId) || '' })];
        }
        S.sources.forEach(r => { r.showDate = !!r.date; });
        S.destinations.forEach(r => { r.showDate = !!r.date; });
        let lastPreview = null, previewOk = false;

        // ---------- what each row may be
        function leaveFor(r) { const a = byId(r.bank_id); return a ? a.leave : []; }
        function arriveFor(r) { const a = byId(r.bank_id); return a ? a.arrive : []; }
        function arriveLabel(k, a) { return k === 'transfer' ? 'Transfer received' : k === 'cheque' ? 'Cheque deposited' : (a && a.cash ? 'Cash received' : 'Cash deposited'); }
        function chequeText(s) { return `Cheque ${s.reference || '(no. not yet typed)'} · ${byId(s.bank_id)?.name || ''} · ${money(s.amount)}`; }
        function chequeLabel(s) { return esc(chequeText(s)); }
        function cheques() { return S.sources.map((s, i) => ({ s, i })).filter(x => x.s.how === 'cheque'); }
        function fixRow(r, out) {
            const opts = out ? leaveFor(r) : arriveFor(r);
            if (opts.length && !opts.includes(r.how)) r.how = opts.length === 1 ? opts[0] : (out ? (r.how || '') : '');
            if (out && opts.length > 1 && !r.how) r.how = 'cheque';
            if (!out && opts.length > 1 && !r.how) r.how = guessArrival();
            if (!out && r.how === 'cheque') {
                const cs = cheques();
                if (!cs.some(c => String(c.i) === String(r.cheque))) r.cheque = cs.length ? String(cs[0].i) : '';
                const c = cs.find(c => String(c.i) === String(r.cheque));
                r.amount = c ? c.s.amount : '';
            }
        }
        function guessArrival() {   // the most natural way money from the sources lands in a bank
            const hows = S.sources.map(s => s.how).filter(Boolean);
            if (hows.length && hows.every(h => h === 'transfer')) return 'transfer';
            return 'cash';
        }

        // ---------- rendering
        function accountSelect(r, side, i) {
            return `<select class="form-select form-select-sm mv-f" data-side="${side}" data-i="${i}" data-k="bank_id" name="${side}[${i}][bank_id]">
                <option value="">Choose the account…</option>
                ${ACCOUNTS.map(a => `<option value="${a.id}" ${String(a.id) === String(r.bank_id) ? 'selected' : ''}>${esc(a.name)}${a.cash ? ' (cash)' : ''} · ${money(a.balance)}</option>`).join('')}
            </select>`;
        }
        function pills(r, side, i, opts, label) {
            if (!opts.length) return '';
            return `<div class="mv-pills mt-2">${opts.map(k => `<button type="button" class="mv-pill ${r.how === k ? 'on' : ''}" data-side="${side}" data-i="${i}" data-how="${k}"><i class="bx ${ICON[k] || 'bx-money'}"></i>${esc(label(k))}</button>`).join('')}</div>
                <input type="hidden" name="${side}[${i}][how]" value="${esc(r.how)}">`;
        }
        function dateBit(r, side, i) {
            return r.showDate
                ? `<div class="mt-2"><label class="mv-hint">${side === 'sources' ? 'Left the account on' : 'Arrived on'}</label><input type="date" class="form-control form-control-sm mv-f" data-side="${side}" data-i="${i}" data-k="date" name="${side}[${i}][date]" value="${esc(r.date)}" max="{{ now()->toDateString() }}"></div>`
                : `<a href="#" class="mv-hint mv-showdate" data-side="${side}" data-i="${i}">different day?</a>`;
        }
        function balanceAfter(r, out) {
            const a = byId(r.bank_id);
            if (!a) return '';
            const amt = +r.amount || 0;
            const after = a.balance + (out ? -amt : amt);
            const bad = out && after < -0.004;
            const note = bad ? (a.cash ? ' (not enough cash)' : ' (below zero — check the balance)') : '';
            return `<span class="mv-after ${bad ? (a.cash ? 'text-danger fw-semibold' : 'text-warning fw-semibold') : 'text-muted'}">${esc(a.name)}: ${money(a.balance)} → ${money(after)}${note}</span>`;
        }

        function render() {
            S.sources.forEach(r => fixRow(r, true));
            S.destinations.forEach(r => fixRow(r, false));
            document.getElementById('mvSources').innerHTML = S.sources.map((r, i) => `
                <div class="mv-row" data-row="sources.${i}">
                    ${S.sources.length > 1 ? `<button type="button" class="mv-x" data-del="sources" data-i="${i}" title="Remove">&times;</button>` : ''}
                    ${accountSelect(r, 'sources', i)}
                    ${pills(r, 'sources', i, leaveFor(r), k => LEAVE[k])}
                    <div class="row g-2 mt-1">
                        <div class="col-6">${r.how === 'cheque' || r.how === 'transfer' || r.how === 'withdrawal'
                            ? `<input class="form-control form-control-sm mv-f" data-side="sources" data-i="${i}" data-k="reference" name="sources[${i}][reference]" maxlength="60" value="${esc(r.reference)}" placeholder="${r.how === 'cheque' ? 'Cheque no. (required)' : 'Reference (optional)'}">`
                            : `<input type="hidden" name="sources[${i}][reference]" value="${esc(r.reference)}">`}</div>
                        <div class="col-6"><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end mv-f" data-side="sources" data-i="${i}" data-k="amount" name="sources[${i}][amount]" value="${esc(r.amount)}" placeholder="Amount GH₵"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mt-1 gap-2"><span data-after="sources.${i}">${balanceAfter(r, true)}</span>${dateBit(r, 'sources', i)}</div>
                </div>`).join('');

            const cs = cheques();
            document.getElementById('mvDestinations').innerHTML = S.destinations.map((r, i) => {
                const a = byId(r.bank_id);
                const chequeBits = r.how === 'cheque'
                    ? `<div class="col-6"><select class="form-select form-select-sm mv-f" data-side="destinations" data-i="${i}" data-k="cheque" name="destinations[${i}][cheque]">
                            ${cs.length ? cs.map(c => `<option value="${c.i}" ${String(c.i) === String(r.cheque) ? 'selected' : ''}>${chequeLabel(c.s)}</option>`).join('') : '<option value="">No cheque on the left yet</option>'}
                        </select></div>
                        <div class="col-6"><input class="form-control form-control-sm text-end" value="${r.amount ? money(r.amount) : ''}" disabled title="A cheque is deposited whole"><input type="hidden" name="destinations[${i}][amount]" value="${esc(r.amount)}"></div>`
                    : `<div class="col-6"><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end mv-f" data-side="destinations" data-i="${i}" data-k="amount" name="destinations[${i}][amount]" value="${esc(r.amount)}" placeholder="Amount GH₵"></div>
                       <div class="col-6"><button type="button" class="btn btn-sm btn-outline-secondary w-100 mv-rest" data-i="${i}" title="Put everything not yet placed here">Put the rest here</button></div>`;
                return `
                <div class="mv-row" data-row="destinations.${i}">
                    ${S.destinations.length > 1 ? `<button type="button" class="mv-x" data-del="destinations" data-i="${i}" title="Remove">&times;</button>` : ''}
                    ${accountSelect(r, 'destinations', i)}
                    ${pills(r, 'destinations', i, arriveFor(r), k => arriveLabel(k, a))}
                    <div class="row g-2 mt-1">${chequeBits}
                        ${a && !a.cash ? `<div class="col-12"><input class="form-control form-control-sm mv-f" data-side="destinations" data-i="${i}" data-k="reference" name="destinations[${i}][reference]" maxlength="60" value="${esc(r.reference)}" placeholder="Deposit slip / reference (optional)"></div>` : ''}
                    </div>
                    <div class="d-flex justify-content-between align-items-start mt-1 gap-2"><span data-after="destinations.${i}">${balanceAfter(r, false)}</span>${dateBit(r, 'destinations', i)}</div>
                </div>`;
            }).join('');
            totals();
        }

        function totals() {
            const out = S.sources.reduce((t, r) => t + (+r.amount || 0), 0);
            const inn = S.destinations.reduce((t, r) => t + (+r.amount || 0), 0);
            const diff = Math.round((out - inn) * 100) / 100;
            document.getElementById('mvOut').textContent = money(out);
            document.getElementById('mvIn').textContent = money(inn);
            document.getElementById('mvOutHead').textContent = out ? 'GH₵ ' + money(out) : '';
            document.getElementById('mvInHead').textContent = inn ? 'GH₵ ' + money(inn) : '';
            const d = document.getElementById('mvDiff');
            if (!out && !inn) { d.textContent = '—'; d.className = 'val'; }
            else if (Math.abs(diff) < 0.005) { d.innerHTML = '<i class="bx bx-check-circle"></i> balanced'; d.className = 'val text-success'; }
            else { d.textContent = (diff > 0 ? money(diff) + ' still to place' : money(-diff) + ' too much in'); d.className = 'val text-warning'; }
            S.sources.forEach((r, i) => { const el = document.querySelector(`[data-after="sources.${i}"]`); if (el) el.innerHTML = balanceAfter(r, true); });
            S.destinations.forEach((r, i) => { const el = document.querySelector(`[data-after="destinations.${i}"]`); if (el) el.innerHTML = balanceAfter(r, false); });
            schedulePreview();
        }

        // ---------- preview (the server's own plan)
        let timer = null, seq = 0;
        function schedulePreview() { clearTimeout(timer); setSave(false, 'checking…'); timer = setTimeout(preview, 350); }
        async function preview() {
            const my = ++seq;
            const body = { date: document.getElementById('mvDate').value, notes: document.getElementById('mvNotes').value,
                sources: S.sources.map(({ showDate, ...r }) => r), destinations: S.destinations.map(({ showDate, ...r }) => r) };
            let res;
            try {
                const r = await fetch(PREVIEW, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('#mvForm input[name=_token]').value }, body: JSON.stringify(body) });
                res = await r.json();
            } catch (e) { if (my === seq) setSave(false, 'Preview unavailable — you can still save.', true); return; }
            if (my !== seq) return;
            lastPreview = res;
            document.querySelectorAll('.mv-row.mv-bad').forEach(el => el.classList.remove('mv-bad'));
            const prob = document.getElementById('mvProblem');
            if (res.message) { prob.textContent = res.message; prob.classList.remove('d-none'); } else prob.classList.add('d-none');
            const m = (res.field || '').match(/^(sources|destinations)\.(\d+)/);
            if (m) document.querySelector(`[data-row="${m[1]}.${m[2]}"]`)?.classList.add('mv-bad');
            const lines = res.lines || [];
            document.getElementById('mvLines').innerHTML = lines.length ? lines.map(l => `<tr>
                    <td class="fw-semibold">${esc(l.account)}${l.cash ? ' <span class="badge bg-label-success">cash</span>' : ''}</td><td class="text-nowrap small">${esc(l.date)}</td>
                    <td class="small">${esc(l.narration)}</td><td class="mv-num text-danger">${l.out ? money(l.out) : ''}</td><td class="mv-num text-success">${l.in ? money(l.in) : ''}</td>
                    <td class="mv-num ${l.after < 0 ? 'text-danger fw-bold' : ''}">${money(l.after)}</td></tr>`).join('')
                : '<tr><td colspan="6" class="text-muted small py-3 text-center">Fill in both sides to see the preview.</td></tr>';
            document.getElementById('mvPairs').innerHTML = (res.pairs || []).length ? res.pairs.map(p => `<div class="mv-flow d-block">
                    <div class="d-flex justify-content-between"><span><strong>${esc(p.from)}</strong> <i class="bx bx-right-arrow-alt text-muted"></i> <strong>${esc(p.to)}</strong></span><span class="mv-num">${money(p.amount)}</span></div>
                    <div class="mv-hint">${esc(p.how)}</div></div>`).join('') : '—';
            const w = document.getElementById('mvWarn');
            w.innerHTML = (res.warnings || []).map(esc).join('<br>'); w.classList.toggle('d-none', !(res.warnings || []).length);
            previewOk = !!res.ok;
            setSave(previewOk, previewOk ? '' : '');
        }
        function setSave(ok, status, allow) {
            const b = document.getElementById('mvSave');
            const total = lastPreview && lastPreview.ok ? lastPreview.total : 0;
            b.disabled = !(ok || allow);
            b.textContent = ok && total ? 'Move GH₵ ' + money(total) : 'Save';
            document.getElementById('mvStatus').textContent = status || '';
        }

        // ---------- events
        const form = document.getElementById('mvForm');
        form.addEventListener('input', e => {
            const el = e.target.closest('.mv-f');
            if (el && el.tagName === 'INPUT') { S[el.dataset.side][el.dataset.i][el.dataset.k] = el.value; if (el.dataset.side === 'sources') syncChequeAmounts(); totals(); }
            else if (e.target.id === 'mvDate' || e.target.id === 'mvNotes') schedulePreview();
        });
        form.addEventListener('change', e => {
            const el = e.target.closest('.mv-f');
            if (el && el.tagName === 'SELECT') { S[el.dataset.side][el.dataset.i][el.dataset.k] = el.value; render(); }
        });
        form.addEventListener('click', e => {
            const pill = e.target.closest('.mv-pill');
            if (pill) { S[pill.dataset.side][pill.dataset.i].how = pill.dataset.how; render(); return; }
            const del = e.target.closest('[data-del]');
            if (del) { S[del.dataset.del].splice(+del.dataset.i, 1); render(); return; }
            const show = e.target.closest('.mv-showdate');
            if (show) { e.preventDefault(); const r = S[show.dataset.side][show.dataset.i]; r.showDate = true; r.date = r.date || document.getElementById('mvDate').value; render(); return; }
            const rest = e.target.closest('.mv-rest');
            if (rest) {
                const i = +rest.dataset.i;
                const out = S.sources.reduce((t, r) => t + (+r.amount || 0), 0);
                const others = S.destinations.reduce((t, r, j) => t + (j === i ? 0 : (+r.amount || 0)), 0);
                S.destinations[i].amount = Math.max(0, Math.round((out - others) * 100) / 100) || '';
                render();
            }
        });
        function syncChequeAmounts() {
            document.querySelectorAll('select[data-k="cheque"]').forEach(sel => Array.from(sel.options).forEach(o => { const c = S.sources[o.value]; if (c) o.textContent = chequeText(c); }));
            S.destinations.forEach(d => { if (d.how === 'cheque') { const c = S.sources[d.cheque]; d.amount = c ? c.amount : ''; } });
            S.destinations.forEach((d, i) => {
                if (d.how !== 'cheque') return;
                const box = document.querySelector(`[data-row="destinations.${i}"] input[disabled]`);
                const hid = document.querySelector(`input[name="destinations[${i}][amount]"]`);
                if (box) box.value = d.amount ? money(d.amount) : '';
                if (hid) hid.value = d.amount;
            });
        }
        document.getElementById('mvAddSource').addEventListener('click', () => { S.sources.push(blankOut()); render(); });
        document.getElementById('mvAddDest').addEventListener('click', () => { S.destinations.push(blankIn()); render(); });
        form.addEventListener('submit', e => {
            if (document.getElementById('mvSave').disabled) { e.preventDefault(); return; }
            document.getElementById('mvSave').disabled = true;
            document.getElementById('mvSave').textContent = 'Saving…';
        });

        // ---------- presets
        document.querySelectorAll('[data-preset]').forEach(btn => btn.addEventListener('click', () => {
            const bank = firstBank(), cash = firstCash(), bank2 = firstBank(bank ? [bank.id] : []);
            const P = {
                fund: { sources: [{ bank_id: bank?.id, how: 'cheque' }], destinations: [{ bank_id: cash?.id, how: 'cash' }] },
                share: { sources: [{ bank_id: bank?.id, how: 'cheque' }], destinations: [{ bank_id: cash?.id, how: 'cash' }, { bank_id: bank2?.id, how: 'cash' }] },
                bank: { sources: [{ bank_id: bank?.id, how: 'transfer' }], destinations: [{ bank_id: bank2?.id, how: 'transfer' }] },
                chequedeposit: { sources: [{ bank_id: bank?.id, how: 'cheque' }], destinations: [{ bank_id: bank2?.id, how: 'cheque', cheque: '0' }] },
                cashin: { sources: [{ bank_id: cash?.id, how: 'cash' }], destinations: [{ bank_id: bank?.id, how: 'cash' }] },
                blank: { sources: [{}], destinations: [{}] },
            }[btn.dataset.preset];
            S.sources = P.sources.map(r => Object.assign(blankOut(), r, { bank_id: r.bank_id || '' }));
            S.destinations = P.destinations.map(r => Object.assign(blankIn(), r, { bank_id: r.bank_id || '' }));
            render();
            document.querySelector('#mvSources input[data-k="amount"]')?.focus();
        }));

        render();
    })();
    </script>
    @endsection
</x-sales-dashboard>
