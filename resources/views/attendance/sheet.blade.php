<x-hr-dashboard>

  @section('css')
    @include('attendance._styles')
  @endsection

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  @php
      $t = $sheet->totals;
      $plan = $sheet->plan;
      $times = \App\Support\Attendance\ShiftClock::times($plan);
      $m = $month->format('Y-m');
      $today = now()->toDateString();
      $clientName = $client->business_name ?: $client->name;
      $awayInfo = fn (array $away) => collect($away)->map(fn ($r) => [
          'client' => $r->client?->business_name ?: $r->client?->name, 'client_id' => $r->client_id, 'status' => $r->status,
          'moved_here' => (int) $r->moved_from_client_id === (int) $client->id,
          'cancellable' => (int) $r->moved_from_client_id === (int) $client->id && ! $r->overtime_id && $r->source !== 'scan',
          'overtime_id' => $r->overtime_id,
      ])->all();
      $cellJson = function (array $cell) {
          return collect($cell)->map(fn ($r) => [
              'status' => $r->status, 'source' => $r->source,
              'time' => $r->checked_in_at?->format('H:i'),
              'distance' => $r->distance_m, 'flags' => $r->flags, 'note' => $r->note,
              'reason' => $r->reason, 'overtime_id' => $r->overtime_id,
          ])->all();
      };
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">
      @include('attendance._flash')

      @if($newPin)
          <div class="alert alert-primary d-flex align-items-center gap-3" role="alert">
              <i class="bx bx-key fs-3"></i>
              <div>New attendance PIN for <strong>{{ $newPin['name'] }}</strong> (FWSS {{ $newPin['id'] }}):
                  <span class="fs-4 fw-bold ms-2 font-monospace">{{ $newPin['pin'] }}</span>
                  <div class="small">Give it to the guard now. It is not shown again; reset it if it is lost.</div></div>
          </div>
      @endif

      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
          <div>
              <a href="{{ route('attendance.index', ['month' => $m]) }}" class="small"><i class="bx bx-chevron-left"></i> All clients</a>
              <h4 class="mb-0 mt-1">{{ $clientName }}</h4>
              <div class="text-muted small">{{ $client->branch }} {{ $client->address ? '· ' . $client->address : '' }} · {{ $month->format('F Y') }} time sheet</div>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-end">
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('attendance.show', ['client' => $client->id, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}" title="Previous month"><i class="bx bx-chevron-left"></i></a>
              <form method="get"><input type="month" name="month" class="form-control form-control-sm" value="{{ $m }}" onchange="this.form.submit()"></form>
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('attendance.show', ['client' => $client->id, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}" title="Next month"><i class="bx bx-chevron-right"></i></a>
              <a class="btn btn-sm btn-outline-success" href="{{ route('attendance.export', ['client' => $client->id, 'month' => $m]) }}"><i class="bx bx-download"></i> Excel</a>
              <a class="btn btn-sm btn-outline-primary" href="{{ route('attendance.kiosk', $client->id) }}" title="Open on the supervisor's phone at the site"><i class="bx bx-mobile-alt"></i> Supervisor kiosk</a>
              <a class="btn btn-sm btn-primary" target="_blank" href="{{ route('attendance.poster', $client->id) }}"><i class="bx bx-qr"></i> QR poster</a>
          </div>
      </div>

      <div class="row g-3 mb-3">
          <div class="col-6 col-lg-2"><div class="att-metric"><div class="k">Required</div><div class="v">{{ $plan ? $t['required'] : '–' }}</div>
              <div class="small text-muted">{{ $plan ? $t['required_day'] . ' day · ' . $t['required_night'] . ' night' : 'No shift plan' }}</div></div></div>
          <div class="col-6 col-lg-2"><div class="att-metric"><div class="k">Posted</div><div class="v">{{ $t['posted'] }}</div>
              <div class="small text-muted">{{ $t['posted_by_shift']['day'] }} day · {{ $t['posted_by_shift']['night'] }} night · {{ $t['posted_by_shift']['rotate'] }} rotate</div></div></div>
          <div class="col-6 col-lg-2"><div class="att-metric {{ $t['shortage'] ? 'bad' : '' }}"><div class="k">Total shortages</div><div class="v">{{ $plan ? ($t['shortage'] ?: 'Nil') : '–' }}</div></div></div>
          <div class="col-6 col-lg-2"><div class="att-metric {{ $t['uncovered_guard_days'] ? 'warn' : '' }}"><div class="k">Uncovered shifts</div><div class="v">{{ $plan ? $t['uncovered_guard_days'] : '–' }}</div>
              <div class="small text-muted">of {{ $t['required_guard_days'] }} so far</div></div></div>
          <div class="col-6 col-lg-2"><div class="att-metric"><div class="k">Absent days</div><div class="v">{{ $t['absent'] }}</div></div></div>
          <div class="col-6 col-lg-2"><div class="att-metric {{ $t['pending'] ? 'warn' : '' }}"><div class="k">Awaiting review</div><div class="v">{{ $t['pending'] }}</div>
              @if($t['pending'])<a class="small" href="{{ route('attendance.review') }}">Review</a>@endif</div></div>
      </div>

      @if($t['posted_by_shift']['unset'])
          <div class="alert alert-warning py-2 small">{{ $t['posted_by_shift']['unset'] }} guard(s) have no shift set. Pick Day, Night or Rotate in the Shift column so scans and shortages are worked out correctly.</div>
      @endif

      <div class="card mb-4">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
              <div class="att-legend small">
                  <span class="att-present">P</span>Present
                  <span class="att-absent ms-2">A</span>Absent
                  <span class="att-leave ms-2">OFF</span>Off Duty
                  <span class="att-leave ms-2">S</span>Sick
                  <span class="att-leave ms-2">PS</span>Pass
                  <span class="att-terminated ms-2">T</span>Terminated
                  <span class="att-moved ms-2">M</span>Moved to another client
                  <span class="att-pending ms-2">?</span>Awaiting review (<em>S?</em> = overtime entry not yet approved)
                  <span class="text-muted ms-2">Click a day to change it. OFF, S and PS count as paid days; T is neither paid nor absent.</span>
              </div>
              <div class="d-flex gap-2">
                  <button type="button" id="att-refresh" class="btn btn-sm btn-outline-primary d-none" onclick="location.reload()"><i class="bx bx-refresh"></i> Refresh totals</button>
                  <form method="post" action="{{ route('attendance.pins', $client->id) }}" target="_blank" class="d-flex gap-1">
                      @csrf
                      <select name="mode" class="form-select form-select-sm" style="width:auto">
                          <option value="missing">Guards without a PIN</option>
                          <option value="all">All guards (replace PINs)</option>
                      </select>
                      <a class="btn btn-sm btn-warning text-white" onclick="return this.form.mode.value !== 'all' || confirm('Replace the PIN of every guard at this client? Their old PINs stop working.');"><i class="bx bx-key"></i> Issue PINs &amp; print slips</a>
                  </form>
                  <form method="post" action="{{ route('attendance.fill', $client->id) }}" onsubmit="return confirm('Mark every unmarked day up to today as Present for every guard with a Day or Night shift?');">
                      @csrf <input type="hidden" name="month" value="{{ $m }}">
                      <a class="btn btn-sm btn-outline-secondary" @disabled($month->isFuture())><i class="bx bx-check-double"></i> Fill blanks as present</a>
                  </form>
              </div>
          </div>
          <div class="table-responsive" style="max-height: 70vh;">
              <table class="att-grid" id="att-grid">
                  <thead>
                      <tr>
                          <th class="sticky-col text-start">Guard</th>
                          <th>Shift</th>
                          <th title="Attendance PIN the guard types with their FWSS number">PIN</th>
                          <th class="tot" title="Present shifts">Wkd</th>
                          <th class="tot" title="Present + Off + Sick + Pass (paid days)">Paid</th>
                          <th class="tot" title="Marked absent">Abs</th>
                          <th class="tot" title="Days so far with nothing recorded">Blank</th>
                          @foreach($sheet->days as $d)
                              <th class="{{ in_array($d['weekday'], ['Sat', 'Sun']) ? 'we' : '' }} {{ $d['date']->toDateString() === $today ? 'today' : '' }}">
                                  <div class="fw-normal text-muted" style="font-size:.65rem">{{ substr($d['weekday'], 0, 2) }}</div>{{ $d['day'] }}</th>
                          @endforeach
                      </tr>
                  </thead>
                  <tbody>
                  @forelse($sheet->rows as $i => $row)
                      @php($e = $row['employee'])
                      <tr class="{{ $row['former'] ? 'former' : '' }}" data-employee="{{ $e->id }}" data-name="{{ $e->name }}" data-shift="{{ $row['shift'] }}">
                          <td class="sticky-col">
                            <div class="d-flex align-items-start justify-content-between gap-1">
                            <div class="overflow-hidden">
                              <div class="fw-semibold text-truncate" title="{{ $e->name }}">{{ sprintf('%02d', $i + 1) }}. {{ $e->name }}</div>
                              <div class="text-muted" style="font-size:.7rem">FWSS {{ $e->id }} @if($e->phone_number)· {{ $e->phone_number }}@endif
                                  @if($row['ot_cover'])<span class="badge bg-label-info" title="Posted elsewhere; covered shifts here on overtime">O.T cover</span>@elseif($row['relief'])<span class="badge bg-label-info" title="Posted at {{ $e->client?->business_name ?: $e->client?->name }}; moved here for some shifts">Relief</span>@elseif($row['former'])<span class="badge bg-label-secondary">moved</span>@endif
                                  @if($row['moved'])<span class="badge bg-label-info" title="Shifts this month worked at another client (M)">{{ $row['moved'] }} M</span>@endif
                                  @unless($row['former'])
                                      @if($e->attendance_pin)<i class="bx bx-key text-success" title="Has a PIN"></i>@else<i class="bx bx-key text-muted" title="No PIN yet"></i>@endif
                                      @if($e->attendance_device)<i class="bx bx-mobile text-success" title="Phone registered"></i>@endif
                                  @endunless
                              </div>
                            </div>
                              @unless($row['former'])
                              <div class="dropdown flex-shrink-0">
                                  <!-- <button class="btn btn-sm p-0 px-1" data-bs-toggle="dropdown" aria-label="Guard actions"><i class="bx bx-dots-vertical-rounded"></i></button> -->
                                  <div class="dropdown-menu dropdown-menu-end">
                                      <form method="post" action="{{ route('attendance.pin', [$client->id, $e->id]) }}" onsubmit="return confirm('Give {{ addslashes($e->name) }} a new PIN? The old one stops working.');">@csrf
                                          <button class="dropdown-item"><i class="bx bx-key me-1"></i> {{ $e->attendance_pin ? 'Reset PIN' : 'Issue PIN' }}</button></form>
                                      @if($e->attendance_device)
                                      <form method="post" action="{{ route('attendance.forgetPhone', [$client->id, $e->id]) }}">@csrf
                                          <button class="dropdown-item"><i class="bx bx-mobile-vibration me-1"></i> Forget registered phone</button></form>
                                      @endif
                                      <form method="post" action="{{ route('attendance.fill', $client->id) }}">@csrf
                                          <input type="hidden" name="month" value="{{ $m }}"><input type="hidden" name="employee_id" value="{{ $e->id }}">
                                          <button class="dropdown-item" @disabled($month->isFuture())><i class="bx bx-check-double me-1"></i> Fill this guard's blanks as present</button></form>
                                  </div>
                              </div>
                              @endunless
                            </div>
                          </td>
                          <td>
                              @unless($row['former'])
                              <select class="form-select form-select-sm py-0 att-shift" style="width:5.8rem;font-size:.75rem" data-employee="{{ $e->id }}">
                                  <option value="">—</option>
                                  @foreach(\App\Support\Attendance\Roster::SHIFTS as $k => $label)
                                      <option value="{{ $k }}" @selected($row['shift'] === $k)>{{ $label }}</option>
                                  @endforeach
                              </select>
                              @endunless
                          </td>
                          <td>
                              @unless($row['former'])
                              <form method="post" action="{{ route('attendance.pin', [$client->id, $e->id]) }}" class="m-0"
                                    @if($e->attendance_pin) onsubmit="return confirm('Give {{ addslashes($e->name) }} a new PIN? The old one stops working.');" @endif>@csrf
                                  @if($e->attendance_pin)
                                      <button class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.72rem" title="Guard has a PIN. Click to replace it."><i class="bx bx-check text-success"></i> Reset</button>
                                  @else
                                      <button class="btn btn-xs btn-sm btn-warning py-0 px-2" style="font-size:.72rem"><i class="bx bx-key"></i> Issue</button>
                                  @endif
                              </form>
                              @endunless
                          </td>
                          <td class="tot">{{ $row['worked'] }}</td>
                          <td class="tot">{{ $row['paid'] }}</td>
                          <td class="tot {{ $row['absent'] ? 'text-danger' : '' }}">{{ $row['absent'] }}</td>
                          <td class="tot text-muted">{{ $row['unmarked'] ?: '' }}</td>
                          @foreach($sheet->days as $n => $d)
                              @php($cell = $row['cells'][$n] ?? [])
                              @php($away = $cell ? [] : ($row['away'][$n] ?? []))
                              @php($awayJson = $awayInfo($away))
                              <td class="cell {{ $away ? 'att-moved' : \App\Support\Attendance\MonthSheet::css($cell) }} {{ $d['future'] ? 'future' : '' }} {{ in_array($d['weekday'], ['Sat', 'Sun']) ? 'we' : '' }}"
                                  data-date="{{ $d['date']->toDateString() }}" data-recs='@json($cellJson($cell))'
                                  @if($away) title="{{ \App\Support\Attendance\MonthSheet::awayTitle($away) }}" data-away='@json($awayJson)' @endif
                                  >{{ $away ? \App\Support\Attendance\MonthSheet::AWAY_CODE : \App\Support\Attendance\MonthSheet::code($cell) }}</td>
                          @endforeach
                      </tr>
                  @empty
                      <tr><td colspan="{{ count($sheet->days) + 7 }}" class="text-center text-muted py-4">No guards are posted to this client. Attach guards from the client page.</td></tr>
                  @endforelse
                  </tbody>
                  @if($sheet->rows)
                  <tfoot>
                      @foreach(['day' => $t['required_day'], 'night' => $t['required_night']] as $shift => $req)
                      <tr>
                          <td class="sticky-col text-start">{{ ucfirst($shift) }} present{{ $plan ? ' / ' . $req . ' required' : '' }}</td>
                          <td colspan="6"></td>
                          @foreach($sheet->days as $n => $d)
                              @php($have = $sheet->cover[$n][$shift])
                              <td class="{{ ! $d['future'] && $plan && $have < $req ? 'short' : '' }}">{{ $d['future'] ? '' : $have }}</td>
                          @endforeach
                      </tr>
                      @endforeach
                  </tfoot>
                  @endif
              </table>
          </div>
      </div>

      <div class="row g-4 att-side">
          {{-- Shift plan --}}
          <div class="col-lg-6">
              <div class="card h-100">
                  <div class="card-header pb-2"><h6 class="mb-0"><i class="bx bx-time-five me-1"></i> Shift plan</h6>
                      <div class="small text-muted">How many guards this client needs on each shift. A change applies from the month you choose; earlier months keep their old plan.</div></div>
                  <div class="card-body">
                      <form method="post" action="{{ route('attendance.plan', $client->id) }}">
                          @csrf
                          <div class="row g-2">
                              <div class="col-6"><label class="form-label small mb-0">Day guards</label>
                                  <input type="number" min="0" name="day_guards" class="form-control form-control-sm" value="{{ old('day_guards', $plan->day_guards ?? 0) }}" required></div>
                              <div class="col-6"><label class="form-label small mb-0">Night guards</label>
                                  <input type="number" min="0" name="night_guards" class="form-control form-control-sm" value="{{ old('night_guards', $plan->night_guards ?? 0) }}" required></div>
                              <div class="col-3"><label class="form-label small mb-0">Day starts</label><input type="time" name="day_start" class="form-control form-control-sm" value="{{ $times['day_start'] }}"></div>
                              <div class="col-3"><label class="form-label small mb-0">Day ends</label><input type="time" name="day_end" class="form-control form-control-sm" value="{{ $times['day_end'] }}"></div>
                              <div class="col-3"><label class="form-label small mb-0">Night starts</label><input type="time" name="night_start" class="form-control form-control-sm" value="{{ $times['night_start'] }}"></div>
                              <div class="col-3"><label class="form-label small mb-0">Night ends</label><input type="time" name="night_end" class="form-control form-control-sm" value="{{ $times['night_end'] }}"></div>
                              <div class="col-5"><label class="form-label small mb-0">Applies from</label><input type="month" name="effective_month" class="form-control form-control-sm" value="{{ $m }}" required></div>
                              <div class="col-7"><label class="form-label small mb-0">Note</label><input type="text" name="plan_note" class="form-control form-control-sm" placeholder="e.g. client added a night post" maxlength="255"></div>
                          </div>
                          <button class="btn btn-sm btn-primary mt-3">Save plan</button>
                      </form>
                      @if($plans->isNotEmpty())
                          <hr>
                          <div class="small fw-semibold mb-1">History</div>
                          <table class="table table-sm small mb-0">
                              @foreach($plans as $p)
                                  <tr @class(['table-primary' => $plan && $p->id === $plan->id])>
                                      <td>From {{ $p->effective_month->format('M Y') }}</td>
                                      <td>{{ $p->day_guards }} day · {{ $p->night_guards }} night</td>
                                      <td class="text-muted">{{ $p->day_start }}–{{ $p->day_end }} / {{ $p->night_start }}–{{ $p->night_end }}</td>
                                      <td class="text-muted">{{ $p->note }} {{ $p->user ? '· ' . $p->user->name : '' }}</td>
                                  </tr>
                              @endforeach
                          </table>
                      @endif
                  </div>
              </div>
          </div>

          {{-- Site and QR --}}
          <div class="col-lg-6">
              <div class="card h-100">
                  <div class="card-header pb-2"><h6 class="mb-0"><i class="bx bx-qr-scan me-1"></i> QR sign-in at the site</h6>
                      <div class="small text-muted">Guards scan the poster, enter their FWSS number and PIN. Sign-ins far from the site, from a different phone, or outside the guard's shift wait for review.</div></div>
                  <div class="card-body">
                      <form method="post" action="{{ route('attendance.site', $client->id) }}">
                          @csrf
                          <div class="row g-2 align-items-end">
                              <div class="col-4"><label class="form-label small mb-0">Latitude</label><input type="text" name="latitude" id="site-lat" class="form-control form-control-sm" value="{{ old('latitude', $client->latitude) }}" placeholder="5.6037"></div>
                              <div class="col-4"><label class="form-label small mb-0">Longitude</label><input type="text" name="longitude" id="site-lng" class="form-control form-control-sm" value="{{ old('longitude', $client->longitude) }}" placeholder="-0.1870"></div>
                              <div class="col-4"><label class="form-label small mb-0">Radius (m)</label><input type="number" name="attendance_radius_m" min="30" max="5000" class="form-control form-control-sm" value="{{ old('attendance_radius_m', $client->attendance_radius_m ?: 200) }}"></div>
                          </div>
                          <div class="d-flex flex-wrap gap-2 mt-2">
                              <button type="button" class="btn btn-sm btn-outline-secondary" id="site-here"><i class="bx bx-current-location"></i> Use my location (stand at the post)</button>
                              <button class="btn btn-sm btn-primary">Save site</button>
                          </div>
                          <div class="small text-muted mt-1" id="site-here-msg">
                              @if($client->latitude !== null)<a target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $client->latitude }},{{ $client->longitude }}">View on map</a>@else Not set: distance is not checked until it is. @endif
                          </div>
                      </form>
                      <hr>
                      <div class="d-flex flex-wrap gap-2 align-items-center">
                          <a class="btn btn-sm btn-primary" target="_blank" href="{{ route('attendance.poster', $client->id) }}"><i class="bx bx-printer"></i> Print QR poster</a>
                          @if($client->attendance_token)
                          <form method="post" action="{{ route('attendance.token', $client->id) }}" onsubmit="return confirm('Make a new QR code? The poster at the site stops working until you replace it.');">@csrf
                              <button class="btn btn-sm btn-outline-danger"><i class="bx bx-revision"></i> Replace QR code</button></form>
                          <span class="small text-muted">Replace it if a photo of the poster is being passed around.</span>
                          @endif
                      </div>
                      <hr>
                      <div class="small fw-semibold mb-1"><i class="bx bx-mobile-alt"></i> Guards without a smartphone</div>
                      <div class="small text-muted mb-2">A supervisor opens the kiosk on their own phone at the site (logged in) and taps each guard, or scans the guard's ID card.
                          Their location is recorded; marks made away from the site wait for review.</div>
                      <div class="d-flex flex-wrap gap-2">
                          <a class="btn btn-sm btn-outline-primary" href="{{ route('attendance.kiosk', $client->id) }}"><i class="bx bx-mobile-alt"></i> Open supervisor kiosk</a>
                          <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('attendance.idCards', $client->id) }}"><i class="bx bx-id-card"></i> Print guard ID cards</a>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  {{-- Cell editor --}}
  <div id="att-pop">
      <div class="d-flex justify-content-between align-items-start mb-1">
          <div><div class="fw-semibold small" id="pop-name"></div><div class="text-muted" style="font-size:.72rem" id="pop-date"></div></div>
          <button type="button" class="btn-close btn-sm" id="pop-close" aria-label="Close"></button>
      </div>
      <div class="btn-group btn-group-sm w-100 mb-2" role="group">
          <input type="radio" class="btn-check" name="pop-shift" id="pop-day" value="day"><label class="btn btn-outline-secondary" for="pop-day">Day</label>
          <input type="radio" class="btn-check" name="pop-shift" id="pop-night" value="night"><label class="btn btn-outline-secondary" for="pop-night">Night</label>
      </div>
      <div id="pop-info" class="small mb-2"></div>
      <div class="d-flex flex-wrap gap-1">
          <a type="button" class="btn btn-success text-white" data-status="present">P</a>
          <a type="button" class="btn btn-danger text-white" data-status="absent">A</a>
          <a type="button" class="btn btn-outline-primary " data-status="off">Off Duty</a>
          <a type="button" class="btn btn-outline-primary " data-status="sick">Sick</a>
          <a type="button" class="btn btn-outline-primary " data-status="pass">Pass</a>
          <a type="button" class="btn btn-outline-secondary " data-status="terminated">Terminated</a>
          <a type="button" class="btn btn-outline-secondary " data-status="clear">Clear</a>
      </div>
      <div id="pop-move" class="mt-2 pt-2 border-top">
          <div class="small fw-semibold mb-1">Moved to another client for this shift</div>
          <div class="d-flex gap-1">
              <select id="pop-move-to" class="form-select form-select-sm">
                  <option value="">Choose client…</option>
                  @foreach($moveTargets as $t)<option value="{{ $t['id'] }}">{{ $t['name'] }}</option>@endforeach
              </select>
              <button type="button" class="btn btn-sm btn-info text-white" id="pop-move-go">Move</button>
          </div>
          <div class="text-muted" style="font-size:.7rem">Recorded as present there; shows <strong>M</strong> here. No absence, no deduction.</div>
      </div>
      <div id="pop-err" class="text-danger small mt-1"></div>
  </div>
  @endsection

  @section('scripts')
  <script>
  (function () {
      const csrf = @json(csrf_token());
      const markUrl = @json(route('attendance.mark', $client->id));
      const shiftUrl = @json(route('attendance.shift', $client->id));
      const CODES = @json(\App\Models\Attendance::CODES);
      const LABELS = @json(\App\Models\Attendance::LABELS);
      const overtimeUrl = @json(url('overtime'));
      const moveUrl = @json(route('attendance.move', $client->id));
      const sheetUrl = @json(url('attendance'));
      const pop = document.getElementById('att-pop');
      let current = null;   // {td, employee, date}

      function post(url, body) {
          return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) })
              .then(r => r.json().catch(() => ({})).then(j => { if (!r.ok) throw new Error(j.message || 'Could not save (' + r.status + ')'); return j; }));
      }
      function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

      function render(td, recs) {
          td.dataset.recs = JSON.stringify(recs);
          const shifts = ['day', 'night'].filter(s => recs[s]);
          td.textContent = shifts.map(s => CODES[recs[s].status] || '?').join('/');
          td.classList.remove('att-present', 'att-absent', 'att-leave', 'att-pending', 'att-terminated');
          const st = shifts.map(s => recs[s].status);
          if (st.includes('pending')) td.classList.add('att-pending');
          else if (st.includes('absent')) td.classList.add('att-absent');
          else if (st.includes('terminated')) td.classList.add('att-terminated');
          else if (st.includes('present')) td.classList.add('att-present');
          else if (st.length) td.classList.add('att-leave');
          document.getElementById('att-refresh').classList.remove('d-none');
      }

      function showInfo() {
          const recs = JSON.parse(current.td.dataset.recs || '{}');
          const shift = document.querySelector('input[name=pop-shift]:checked').value;
          const r = recs[shift];
          let how = 'marked by operations';
          if (r && r.source === 'scan') how = 'scanned' + (r.time ? ' at ' + esc(r.time) : '') + (r.distance != null ? ', ' + esc(r.distance) + ' m from site' : '');
          if (r && r.source === 'kiosk') how = 'marked on the supervisor kiosk';
          if (r && r.overtime_id) how = 'from <a href="' + overtimeUrl + '/' + encodeURIComponent(r.overtime_id) + '/open">overtime entry #' + esc(r.overtime_id) + '</a>' + (r.reason ? ' (' + esc(r.reason) + ')' : '')
              + '<div class="text-muted mt-1">Change it on the overtime entry: approving it confirms this shift, deleting or rejecting it puts the sheet back.</div>';
          const away = JSON.parse(current.td.dataset.away || '{}')[shift];
          let html = r ? '<span class="badge bg-label-secondary">' + esc(LABELS[r.status] || r.status) + '</span> ' + how
              : '<span class="text-muted">Nothing recorded for this shift.</span>';
          if (!r && away) {
              html = '<span class="badge bg-label-info">' + (away.moved_here ? 'Moved' : 'Elsewhere') + '</span> '
                  + (away.moved_here ? 'Moved to ' : 'At ') + '<a href="' + sheetUrl + '/' + away.client_id + '?month=' + current.date.slice(0, 7) + '">' + esc(away.client) + '</a>: '
                  + esc((LABELS[away.status] || away.status).toLowerCase()) + (away.overtime_id ? ' (overtime #' + esc(away.overtime_id) + ')' : '') + '.'
                  + '<div class="text-muted mt-1">Not an absence here, so no deduction.</div>'
                  + (away.cancellable ? '<button type="button" class="btn btn-sm btn-outline-danger mt-1" id="pop-move-cancel">Cancel move</button>' : '');
          }
          const canMove = !r && !away && !current.former && !current.td.classList.contains('future');
          document.getElementById('pop-move').style.display = canMove ? '' : 'none';
          if (r && r.flags && r.flags.length) html += '<div class="text-warning mt-1">' + r.flags.map(esc).join('<br>') + '</div>';
          if (r && r.note) html += '<div class="text-muted mt-1">' + esc(r.note) + '</div>';
          document.getElementById('pop-info').innerHTML = html;
          const cancel = document.getElementById('pop-move-cancel');
          cancel && cancel.addEventListener('click', () => {
              post(moveUrl, { employee_id: current.employee, date: current.date, shift, action: 'cancel' })
                  .then(() => location.reload()).catch(err => { document.getElementById('pop-err').textContent = err.message; });
          });
      }

      document.getElementById('pop-move-go').addEventListener('click', function () {
          const to = document.getElementById('pop-move-to').value;
          if (!to) { document.getElementById('pop-err').textContent = 'Choose the client the guard was moved to.'; return; }
          const shift = document.querySelector('input[name=pop-shift]:checked').value;
          post(moveUrl, { employee_id: current.employee, date: current.date, shift, to_client_id: to })
              .then(() => location.reload()).catch(err => { document.getElementById('pop-err').textContent = err.message; });
      });

      document.getElementById('att-grid').addEventListener('click', function (ev) {
          const td = ev.target.closest('td.cell');
          if (!td) return;
          const tr = td.closest('tr');
          current = { td, employee: tr.dataset.employee, date: td.dataset.date, former: tr.classList.contains('former') };
          const recs = JSON.parse(td.dataset.recs || '{}');
          const own = tr.dataset.shift;
          const awayRecs = JSON.parse(td.dataset.away || '{}');
          const shift = recs.day && !recs.night ? 'day' : recs.night && !recs.day ? 'night'
              : awayRecs.day && !awayRecs.night ? 'day' : awayRecs.night && !awayRecs.day ? 'night' : (own === 'night' ? 'night' : 'day');
          document.getElementById('pop-' + shift).checked = true;
          document.getElementById('pop-name').textContent = tr.dataset.name;
          document.getElementById('pop-date').textContent = new Date(td.dataset.date + 'T00:00').toDateString();
          document.getElementById('pop-err').textContent = '';
          showInfo();
          const rect = td.getBoundingClientRect();
          pop.style.display = 'block';
          let left = rect.left + window.scrollX - 100;
          left = Math.max(8, Math.min(left, window.scrollX + document.documentElement.clientWidth - pop.offsetWidth - 8));
          pop.style.left = left + 'px';
          pop.style.top = (rect.bottom + window.scrollY + 4) + 'px';
      });
      document.querySelectorAll('input[name=pop-shift]').forEach(i => i.addEventListener('change', showInfo));
      document.getElementById('pop-close').onclick = () => { pop.style.display = 'none'; };
      document.addEventListener('keydown', e => { if (e.key === 'Escape') pop.style.display = 'none'; });

      pop.querySelectorAll('[data-status]').forEach(btn => btn.addEventListener('click', function () {
          const status = this.dataset.status;
          const shift = document.querySelector('input[name=pop-shift]:checked').value;
          const td = current.td;
          post(markUrl, { employee_id: current.employee, date: current.date, shift, status })
              .then(() => {
                  const recs = JSON.parse(td.dataset.recs || '{}');
                  if (status === 'clear') delete recs[shift];
                  else recs[shift] = Object.assign({}, recs[shift] || { source: 'manual' }, { status, flags: null });
                  render(td, recs);
                  pop.style.display = 'none';
              })
              .catch(err => { document.getElementById('pop-err').textContent = err.message; });
      }));

      document.querySelectorAll('.att-shift').forEach(sel => sel.addEventListener('change', function () {
          const tr = this.closest('tr');
          post(shiftUrl, { employee_id: this.dataset.employee, shift: this.value || null })
              .then(() => { tr.dataset.shift = this.value; this.classList.add('is-valid'); setTimeout(() => this.classList.remove('is-valid'), 1200); })
              .catch(err => { alert(err.message); });
      }));

      const here = document.getElementById('site-here');
      here && here.addEventListener('click', function () {
          const msg = document.getElementById('site-here-msg');
          if (!navigator.geolocation) { msg.textContent = 'This browser cannot share its location.'; return; }
          msg.textContent = 'Getting location…';
          navigator.geolocation.getCurrentPosition(p => {
              document.getElementById('site-lat').value = p.coords.latitude.toFixed(7);
              document.getElementById('site-lng').value = p.coords.longitude.toFixed(7);
              msg.textContent = 'Location filled in (accuracy about ' + Math.round(p.coords.accuracy) + ' m). Press Save site.';
          }, e => { msg.textContent = 'Could not get location: ' + e.message; }, { enableHighAccuracy: true, timeout: 20000 });
      });
  })();
  </script>
  @endsection
</x-hr-dashboard>
