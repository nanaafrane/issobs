<x-hr-dashboard>

  @section('css')
    <style>
        .pp-rule-row td { vertical-align: top; }
        .pp-client-row td { background: #f5f5f9; font-weight: 600; }
        .pp-loc { display: inline-block; background: #eef0ff; color: #3b3ea6; border-radius: .3rem; padding: 0 .4rem; margin: 0 .2rem .2rem 0; font-size: .78rem; }
        .pp-off td:not(:last-child) { opacity: .5; }
        .pp-preview { background: #f8f9fa; border-radius: .4rem; padding: .6rem .8rem; font-size: .85rem; min-height: 2.6rem; }
        .pp-preview.pp-busy { opacity: .6; }
        .pp-metric { background: #f5f5f9; border-radius: .5rem; padding: .6rem .9rem; }
        .pp-metric .v { font-size: 1.35rem; font-weight: 600; }
    </style>
  @endsection

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  @php
      $levelBadge = fn ($l) => (int) $l === \App\Support\PayPriority::URGENT
          ? '<span class="badge bg-danger"><i class="bx bxs-bolt"></i> Pay first</span>'
          : '<span class="badge bg-warning text-dark"><i class="bx bx-time-five"></i> Pay early</span>';
      $byClient = $rules->groupBy('client_id');
      $actionLabels = ['created' => 'Added', 'updated' => 'Changed', 'enabled' => 'Turned on', 'disabled' => 'Turned off', 'deleted' => 'Deleted',
          'alias_added' => 'Spelling', 'alias_removed' => 'Spelling removed', 'recomputed' => 'Recalculated'];
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">

      @foreach(['success' => 'success', 'primary' => 'primary', 'error' => 'danger'] as $key => $color)
          @if(session($key))
              <div class="alert alert-{{ $color }} alert-dismissible" role="alert">{{ session($key) }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          @endif
      @endforeach
      @if($errors->any())
          <div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
      @endif

      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div>
              <h3 class="mb-1"><i class="bx bxs-bolt text-danger"></i> Pay priority rules</h3>
              <div class="text-muted small" style="max-width:52rem">
                  A rule flags a client's staff as <strong>Pay first</strong> or <strong>Pay early</strong>. Every condition on a rule must match;
                  for an "or", add a second rule. When several rules match, the higher level wins. Locations match whole words, ignoring case.
                  Saving recalculates employees and unpaid salaries at once; paid salaries never change.
              </div>
          </div>
          <div class="d-flex gap-2">
              <form method="POST" action="{{ route('pay-priority.recompute') }}" onsubmit="return confirm('Recalculate every employee and unpaid salary now?\n\nOnly needed after data was changed outside the app.')">
                  @csrf
                  <button class="btn btn-outline-secondary" type="submit"><i class="bx bx-refresh"></i> Recalculate everyone</button>
              </form>
              <button class="btn btn-primary" type="button" id="ppAdd"><i class="bx bx-plus"></i> Add rule</button>
          </div>
      </div>

      <div class="row g-3 mb-4">
          <div class="col-md-3 col-6"><div class="pp-metric"><div class="small text-muted">Rules on</div><div class="v">{{ $rules->where('active', true)->count() }} <span class="fs-6 text-muted">of {{ $rules->count() }}</span></div></div></div>
          <div class="col-md-3 col-6"><div class="pp-metric"><div class="small text-muted">Active staff: Pay first</div><div class="v text-danger">{{ number_format($flagged[\App\Support\PayPriority::URGENT] ?? 0) }}</div></div></div>
          <div class="col-md-3 col-6"><div class="pp-metric"><div class="small text-muted">Active staff: Pay early</div><div class="v text-warning">{{ number_format($flagged[\App\Support\PayPriority::PRIORITY] ?? 0) }}</div></div></div>
          <div class="col-md-3 col-6"><div class="pp-metric"><div class="small text-muted">Clients with rules</div><div class="v">{{ count($scope) - count($noRuleClients) }} <span class="fs-6 text-muted">of {{ count($scope) }} Category A</span></div></div></div>
      </div>

      {{-- Rules --}}
      <div class="card mb-4">
          <div class="table-responsive">
              <table class="table table-sm mb-0" id="ppRules">
                  <thead><tr><th>Level</th><th>Applies to</th><th>Reason shown on badge</th><th class="text-end" title="Active employees this rule matches on its own">Matches</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                  <tbody>
                  @forelse($byClient as $clientId => $clientRules)
                      <tr class="pp-client-row"><td colspan="6">{{ $clients[$clientId]['name'] ?? 'Client ' . $clientId }} <span class="text-muted fw-normal small">#{{ $clientId }}</span>
                          @if(! in_array((int) $clientId, $scope, true)) <span class="badge bg-label-danger ms-1" title="Not on the default Category A list: these rules are ignored">ignored &mdash; not Category A</span> @endif
                      </td></tr>
                      @foreach($clientRules as $rule)
                          @php $locs = $rule->locations ?: []; @endphp
                          <tr class="pp-rule-row {{ $rule->active ? '' : 'pp-off' }}" data-rule-id="{{ $rule->id }}">
                              <td>{!! $levelBadge($rule->level) !!}</td>
                              <td>
                                  @if($rule->gender)<span class="badge bg-label-dark me-1">{{ ucfirst($rule->gender) }} staff</span>@endif
                                  @if($rule->field_id)<span class="badge bg-label-dark me-1">{{ $fields[$rule->field_id] ?? 'Field ' . $rule->field_id }} office</span>@endif
                                  @forelse($locs as $l)<span class="pp-loc">{{ $l }}</span>@empty<span class="text-muted small">any location</span>@endforelse
                              </td>
                              <td class="small">{{ $rule->reason ?: '' }}@unless($rule->reason)<span class="text-muted">automatic</span>@endunless</td>
                              <td class="text-end">{{ number_format($matchCounts[$rule->id] ?? 0) }}</td>
                              <td>{!! $rule->active ? '<span class="badge bg-label-success">On</span>' : '<span class="badge bg-label-secondary">Off</span>' !!}</td>
                              <td class="text-end text-nowrap">
                                  <button type="button" class="btn btn-sm btn-outline-primary js-edit" data-rule="{{ json_encode(['id' => $rule->id, 'client_id' => $rule->client_id, 'level' => $rule->level, 'gender' => $rule->gender, 'field_id' => $rule->field_id, 'locations' => $locs, 'reason' => $rule->reason]) }}">Edit</button>
                                  <form method="POST" action="{{ route('pay-priority.toggle', $rule) }}" class="d-inline js-confirm-preview"
                                        data-preview="{{ json_encode(['action' => $rule->active ? 'disable' : 'enable', 'rule_id' => $rule->id]) }}"
                                        data-title="{{ $rule->active ? 'Turn off' : 'Turn on' }} this rule for {{ $clients[$clientId]['name'] ?? 'client ' . $clientId }}?">
                                      @csrf
                                      <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $rule->active ? 'Turn off' : 'Turn on' }}</button>
                                  </form>
                                  <form method="POST" action="{{ route('pay-priority.destroy', $rule) }}" class="d-inline js-confirm-preview"
                                        data-preview="{{ json_encode(['action' => 'delete', 'rule_id' => $rule->id]) }}"
                                        data-title="Delete this rule for {{ $clients[$clientId]['name'] ?? 'client ' . $clientId }}? (Turning it off keeps it for later.)">
                                      @csrf @method('DELETE')
                                      <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                  </form>
                              </td>
                          </tr>
                      @endforeach
                  @empty
                      <tr><td colspan="6" class="text-center text-muted py-4">No rules yet. Add one to flag a client's staff for early payment.</td></tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
          <div class="card-body py-2 small text-muted">
              "Matches" counts active employees that a rule matches on its own. Someone matched by two rules is paid at the higher level.
          </div>
      </div>

      <div class="row g-3 mb-4">
          {{-- Category A clients without a rule --}}
          <div class="col-lg-5">
              <div class="card h-100">
                  <div class="card-header pb-2"><strong>Category A clients without a rule</strong></div>
                  <div class="card-body pt-0">
                      @forelse($noRuleClients as $id)
                          <span class="badge bg-label-secondary me-1 mb-1">{{ $clients[$id]['name'] ?? 'Client ' . $id }} <span class="opacity-75">#{{ $id }}</span></span>
                      @empty
                          <div class="small text-muted">Every default Category A client has a rule.</div>
                      @endforelse
                      <div class="small text-muted mt-2">
                          Only default Category A clients can have pay rules. That list is in the code
                          (<code>category::DEFAULT_A_CLIENT_IDS</code>) and also decides which clients are pre-ticked for Category A each month,
                          so adding a client there does both.
                      </div>
                  </div>
              </div>
          </div>

          {{-- Spelling variants --}}
          <div class="col-lg-7">
              <div class="card h-100">
                  <div class="card-header pb-2"><strong>Location spellings</strong>
                      <div class="small text-muted fw-normal">How a spelling found in employee locations is read before matching rules, e.g. "C/Coast" as CAPE COAST.</div></div>
                  <div class="card-body pt-0">
                      <form method="POST" action="{{ route('pay-priority.aliases.store') }}" class="row g-2 align-items-end mb-3 js-confirm-preview" id="ppAliasForm"
                            data-preview-url="{{ route('pay-priority.aliases.preview') }}" data-title="Read this spelling the new way?">
                          @csrf
                          <div class="col"><label class="form-label small mb-0" for="ppAliasFrom">Spelling in the data</label><input class="form-control form-control-sm" name="from" id="ppAliasFrom" maxlength="60" required placeholder="e.g. C COAST"></div>
                          <div class="col"><label class="form-label small mb-0" for="ppAliasTo">Read as</label><input class="form-control form-control-sm" name="to" id="ppAliasTo" maxlength="60" required placeholder="e.g. CAPE COAST"></div>
                          <div class="col-auto"><button class="btn btn-sm btn-outline-primary" type="submit">Add spelling</button></div>
                      </form>
                      <div class="table-responsive" style="max-height:15rem">
                          <table class="table table-sm mb-0 small">
                              <thead><tr><th>Spelling in the data</th><th>Read as</th><th></th></tr></thead>
                              <tbody>
                              @forelse($aliases as $a)
                                  <tr><td>{{ $a->from }}</td><td>{{ $a->to }}</td><td class="text-end">
                                      <form method="POST" action="{{ route('pay-priority.aliases.destroy', $a->id) }}" class="d-inline js-confirm-preview"
                                            data-preview-url="{{ route('pay-priority.aliases.preview') }}" data-preview="{{ json_encode(['remove' => $a->id]) }}" data-title="Remove the spelling {{ $a->from }} → {{ $a->to }}?">
                                          @csrf @method('DELETE')
                                          <button class="btn btn-sm btn-link text-danger p-0" type="submit">Remove</button>
                                      </form></td></tr>
                              @empty
                                  <tr><td colspan="3" class="text-muted">No spellings yet.</td></tr>
                              @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      {{-- Locations no rule matches --}}
      <div class="card mb-4">
          <div class="card-header pb-2"><strong>Locations no rule matches</strong>
              <div class="small text-muted fw-normal">Active staff of clients with location rules whose location matches none of that client's locations. A misspelling of a rule location shows up here: add it under Location spellings.</div></div>
          <div class="table-responsive" style="max-height:20rem">
              <table class="table table-sm mb-0 small">
                  <thead><tr><th>Client</th><th>Location as stored</th><th>Read as</th><th class="text-end">Staff</th><th></th></tr></thead>
                  <tbody>
                  @forelse($unmatched as $u)
                      <tr><td>{{ $clients[$u['client_id']]['name'] ?? 'Client ' . $u['client_id'] }}</td><td>{{ $u['location'] !== '' ? $u['location'] : '(blank)' }}</td>
                          <td class="text-muted">{{ $u['normalised'] }}</td><td class="text-end">{{ $u['people'] }}</td>
                          <td class="text-end">@if($u['normalised'] !== '')<button type="button" class="btn btn-sm btn-link p-0 js-alias-from" data-from="{{ $u['normalised'] }}">Add spelling</button>@endif</td></tr>
                  @empty
                      <tr><td colspan="5" class="text-muted py-3 text-center">Every location of these clients' staff matches a rule.</td></tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
      </div>

      {{-- History --}}
      <div class="card mb-4">
          <div class="card-header pb-2"><strong>Change history</strong> <span class="small text-muted fw-normal">last 50</span></div>
          <div class="table-responsive" style="max-height:24rem">
              <table class="table table-sm mb-0 small">
                  <thead><tr><th>When</th><th>Who</th><th>What</th><th>Change</th><th class="text-end">Staff moved</th></tr></thead>
                  <tbody>
                  @forelse($history as $h)
                      <tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($h->created_at)->format('d M Y, H:i') }}</td><td>{{ $h->user_name ?? '—' }}</td>
                          <td><span class="badge bg-label-dark">{{ $actionLabels[$h->action] ?? $h->action }}</span></td><td>{{ $h->summary }}</td>
                          <td class="text-end">{{ $h->employees_changed ?? '' }}</td></tr>
                  @empty
                      <tr><td colspan="5" class="text-muted py-3 text-center">No changes recorded yet.</td></tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
      </div>
  </div>

  {{-- Add / edit rule --}}
  <div class="modal fade" id="ppModal" tabindex="-1" aria-labelledby="ppModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-lg">
          <form class="modal-content" method="POST" id="ppForm" action="{{ route('pay-priority.store') }}">
              @csrf
              <input type="hidden" name="_method" id="ppMethod" value="POST">
              <div class="modal-header"><h5 class="modal-title" id="ppModalTitle">Add rule</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
              <div class="modal-body">
                  <div class="row g-3">
                      <div class="col-md-7">
                          <label class="form-label" for="ppClient">Client</label>
                          <select class="form-select" name="client_id" id="ppClient" required>
                              <option value="">Choose a Category A client…</option>
                              @foreach(collect($clients)->sortBy('name') as $id => $c)
                                  <option value="{{ $id }}">{{ $c['name'] }} (#{{ $id }}){{ ($c['status'] ?? '') !== 'Active' ? ' — ' . ($c['status'] ?: 'inactive') : '' }}</option>
                              @endforeach
                          </select>
                      </div>
                      <div class="col-md-5">
                          <span class="form-label d-block">Level</span>
                          <div class="btn-group w-100" role="group" aria-label="Level">
                              <input type="radio" class="btn-check" name="level" id="ppLevel2" value="2"><label class="btn btn-outline-danger" for="ppLevel2"><i class="bx bxs-bolt"></i> Pay first</label>
                              <input type="radio" class="btn-check" name="level" id="ppLevel1" value="1" checked><label class="btn btn-outline-warning" for="ppLevel1"><i class="bx bx-time-five"></i> Pay early</label>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <label class="form-label" for="ppGender">Staff</label>
                          <select class="form-select" name="gender" id="ppGender"><option value="">Everyone</option><option value="female">Female only</option><option value="male">Male only</option></select>
                      </div>
                      <div class="col-md-8">
                          <label class="form-label" for="ppField">Field office</label>
                          <select class="form-select" name="field_id" id="ppField"><option value="">Any field office</option>
                              @foreach($fields as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                          </select>
                      </div>
                      <div class="col-12">
                          <label class="form-label" for="ppLocations">Locations <span class="text-muted small">one per line or separated by commas; leave empty for any location</span></label>
                          <textarea class="form-control" name="locations" id="ppLocations" rows="3" placeholder="CAPE COAST&#10;TAKORADI"></textarea>
                      </div>
                      <div class="col-12">
                          <label class="form-label" for="ppReason">Reason shown on the badge <span class="text-muted small">optional; written automatically when empty</span></label>
                          <input class="form-control" name="reason" id="ppReason" maxlength="190">
                      </div>
                      <div class="col-12">
                          <div class="pp-preview" id="ppPreview" aria-live="polite">Choose a client to see who this rule would move.</div>
                      </div>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary" id="ppSave">Save rule</button>
              </div>
          </form>
      </div>
  </div>
  @endsection

  @section('scripts')
  <script>
  (function () {
      const PREVIEW_URL = @json(route('pay-priority.preview'));
      const STORE_URL = @json(route('pay-priority.store'));
      const UPDATE_URL = @json(route('pay-priority.update', ['rule' => '__ID__']));
      const TOKEN = @json(csrf_token());
      const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

      async function preview(url, data) {
          const body = new FormData();
          Object.entries(data).forEach(([k, v]) => body.append(k, v == null ? '' : v));
          const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' }, body });
          const json = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(json.message || Object.values(json.errors || {}).flat()[0] || 'Could not preview this change.');
          return json;
      }

      function previewText(p) {
          let t = p.summary;
          if (p.unpaid_salaries) t += '\n' + p.unpaid_salaries + ' unpaid salary row(s) follow them.';
          if (p.samples && p.samples.length) {
              t += '\n\n' + p.samples.slice(0, 8).map(s => '• ' + s.name + ' (' + (s.location || 'no location') + '): ' + s.from + ' → ' + s.to).join('\n');
              if (p.changed > 8) t += '\n• … and ' + (p.changed - 8) + ' more';
          }
          return t;
      }

      function previewHtml(p) {
          let h = '<strong>' + esc(p.summary) + '</strong>';
          if (p.unpaid_salaries) h += ' <span class="text-muted">' + p.unpaid_salaries + ' unpaid salary row(s) follow them.</span>';
          if (p.samples && p.samples.length) {
              h += '<ul class="mb-0 mt-1 ps-3">' + p.samples.map(s => '<li>' + esc(s.name) + ' <span class="text-muted">(' + esc(s.location || 'no location') + ')</span>: '
                  + esc(s.from) + ' → <strong>' + esc(s.to) + '</strong></li>').join('') + '</ul>';
              if (p.changed > p.samples.length) h += '<div class="text-muted">… and ' + (p.changed - p.samples.length) + ' more.</div>';
          }
          return h;
      }

      // ---- Add / edit dialog with live preview ----
      const modalEl = document.getElementById('ppModal');
      const form = document.getElementById('ppForm');
      const box = document.getElementById('ppPreview');
      let editingId = null, timer = null, seq = 0;

      function formData() {
          return {
              client_id: form.elements.client_id.value, level: form.querySelector('[name=level]:checked').value,
              gender: form.elements.gender.value, field_id: form.elements.field_id.value, locations: form.elements.locations.value, reason: form.elements.reason.value,
          };
      }

      function refresh() {
          clearTimeout(timer);
          if (!form.elements.client_id.value) { box.textContent = 'Choose a client to see who this rule would move.'; return; }
          timer = setTimeout(async () => {
              const mine = ++seq;
              box.classList.add('pp-busy');
              try {
                  const p = await preview(PREVIEW_URL, Object.assign(formData(), editingId ? { action: 'update', rule_id: editingId } : { action: 'create' }));
                  if (mine === seq) box.innerHTML = previewHtml(p);
              } catch (e) {
                  if (mine === seq) box.innerHTML = '<span class="text-danger">' + esc(e.message) + '</span>';
              } finally { if (mine === seq) box.classList.remove('pp-busy'); }
          }, 350);
      }

      function openForm(rule) {
          editingId = rule ? rule.id : null;
          document.getElementById('ppModalTitle').textContent = rule ? 'Edit rule' : 'Add rule';
          form.action = rule ? UPDATE_URL.replace('__ID__', rule.id) : STORE_URL;
          document.getElementById('ppMethod').value = rule ? 'PUT' : 'POST';
          form.elements.client_id.value = rule ? rule.client_id : '';
          form.querySelector('[name=level][value="' + (rule ? rule.level : 1) + '"]').checked = true;
          form.elements.gender.value = rule && rule.gender ? rule.gender : '';
          form.elements.field_id.value = rule && rule.field_id ? rule.field_id : '';
          form.elements.locations.value = rule && rule.locations ? rule.locations.join('\n') : '';
          form.elements.reason.value = rule && rule.reason ? rule.reason : '';
          bootstrap.Modal.getOrCreateInstance(modalEl).show();
          refresh();
      }

      document.getElementById('ppAdd').addEventListener('click', () => openForm(null));
      document.querySelectorAll('.js-edit').forEach(b => b.addEventListener('click', () => openForm(JSON.parse(b.dataset.rule))));
      form.addEventListener('input', refresh);
      form.addEventListener('change', refresh);
      form.addEventListener('submit', () => { document.getElementById('ppSave').disabled = true; });

      // ---- Turn on / off, delete, spellings: preview, confirm, then submit ----
      document.querySelectorAll('.js-confirm-preview').forEach(f => f.addEventListener('submit', async function (e) {
          if (f.dataset.confirmed === '1') return;
          e.preventDefault();
          const btn = f.querySelector('[type=submit]');
          btn.disabled = true;
          try {
              const data = f.dataset.preview ? JSON.parse(f.dataset.preview)
                  : Object.fromEntries(Array.from(new FormData(f)).filter(([k]) => k !== '_token' && k !== '_method'));
              const p = await preview(f.dataset.previewUrl || PREVIEW_URL, data);
              if (confirm(f.dataset.title + '\n\n' + previewText(p))) {
                  f.dataset.confirmed = '1';
                  f.submit();
                  return;
              }
          } catch (err) {
              alert(err.message);
          }
          btn.disabled = false;
      }));

      // "Add spelling" from the unmatched list fills the spelling form.
      document.querySelectorAll('.js-alias-from').forEach(b => b.addEventListener('click', () => {
          document.getElementById('ppAliasFrom').value = b.dataset.from;
          const to = document.getElementById('ppAliasTo');
          to.value = '';
          to.focus();
          if (to.scrollIntoView) to.scrollIntoView({ block: 'center' });
      }));
  })();
  </script>
  @endsection
</x-hr-dashboard>
