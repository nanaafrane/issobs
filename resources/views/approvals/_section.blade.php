@use('App\Support\Dashboards\Fmt')
@use('App\Support\Approvals\ApprovalInbox')
@php
  $anchor = str_replace('.', '-', $s['key']);
  $hasAmount = $s['rows']->contains(fn ($r) => $r['amount'] !== null);
  $inline = $s['rows']->contains(fn ($r) => $r['approve']);
  $pageUrl = $s['rows']->first()['open'] ?? null;
@endphp
<section class="panel h-auto mb-3 ap-sec" id="{{ $anchor }}">
  <div class="panel-h">
    <h6>{{ $s['label'] }} <span class="fw-normal" style="color:var(--ink-2)">· {{ $s['stage'] }}</span></h6>
    <span class="hint">
      {{ Fmt::int($s['count']) }} waiting
      @if($s['count'] > $s['rows']->count()) · showing the oldest {{ $s['rows']->count() }} @endif
      @if(! $inline && $pageUrl) · <a href="{{ $pageUrl }}">approve on the pending page →</a> @endif
    </span>
  </div>
  <div class="panel-b table-responsive">
    <table class="dt">
      <thead>
        <tr>
          <th>Item</th>
          <th>Office</th>
          <th>Date</th>
          @if($hasAmount)<th class="num">Amount</th>@endif
          <th>Waiting</th>
          <th class="num"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($s['rows'] as $r)
          @php($days = ApprovalInbox::ageDays($r['since']))
          <tr>
            <td>
              {{ $r['title'] }}
              @if($r['detail'])<span class="sub">{{ $r['detail'] }}</span>@endif
            </td>
            <td>{{ $r['office'] ?? '—' }}</td>
            <td class="muted" style="white-space:nowrap">{{ $r['date'] ? \Carbon\Carbon::parse($r['date'])->format('j M Y') : '—' }}</td>
            @if($hasAmount)<td class="num">{{ $r['amount'] !== null ? Fmt::money($r['amount']) : '—' }}</td>@endif
            <td>@if($days !== null)<span class="chip {{ ApprovalInbox::ageTone($days) }}">{{ ApprovalInbox::ageLabel($days) }}</span>@endif</td>
            <td>
              <div class="ap-actions">
                <a class="btn btn-sm btn-outline-secondary" href="{{ $r['open'] }}">Open</a>
                @if($actions && $r['approve'])
                  <form method="POST" action="{{ $r['approve'] }}">
                    @csrf
                    <button class="btn btn-sm btn-success" onclick="return confirm('Approve this?')">Approve</button>
                  </form>
                  <form method="POST" action="{{ $r['reject'] }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this?')">Reject</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>
