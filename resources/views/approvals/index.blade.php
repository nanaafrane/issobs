@use('App\Support\Dashboards\Fmt')
@use('App\Support\Approvals\ApprovalInbox')
<x-sales-dashboard>

  @section('side_nav')
    @include('dashboards.nav.' . $nav)
  @endsection

  @section('css')
    @include('dashboards._styles')
    <style>
      .dash .ap-nav { display: flex; flex-wrap: wrap; gap: .5rem; }
      .dash .ap-nav a { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .7rem; border: 1px solid var(--line); border-radius: 999px; color: var(--ink); background: var(--surface); font-size: .84rem; font-weight: 600; }
      .dash .ap-nav a:hover { border-color: var(--s1); }
      .dash .ap-nav .n { font-variant-numeric: tabular-nums; color: var(--ink-2); }
      .dash .ap-sec { scroll-margin-top: 5rem; }
      .dash .ap-actions { display: flex; gap: .35rem; justify-content: flex-end; flex-wrap: nowrap; }
      .dash .ap-actions form { margin: 0; }
      .dash table.dt td .sub { display: block; color: var(--ink-3); font-size: .78rem; }
    </style>
  @endsection

  @section('content')
  <div class="container-xxl flex-grow-1 container-p-y dash">
    @include('flash-messages')

    <div class="dash-head">
      <div>
        <h4>Waiting for your approval</h4>
        <div class="sub">
          @if($summary['total'])
            {{ Fmt::int($summary['total']) }} {{ \Illuminate\Support\Str::plural('item', $summary['total']) }}, oldest first · every date, not just this month
          @else
            Nothing is waiting for you right now.
          @endif
        </div>
      </div>
    </div>

    @if(! $summary['can'])
      <div class="panel h-auto"><div class="panel-b"><div class="empty">Your role does not approve anything.</div></div></div>
    @else
      @php($open = $sections->filter(fn ($s) => $s['count'] > 0))
      @if($open->isNotEmpty())
        <nav class="ap-nav mb-3" aria-label="Sections">
          @foreach($open as $s)
            <a href="#{{ str_replace('.', '-', $s['key']) }}">{{ $s['label'] }} · {{ $s['stage'] }} <span class="n">{{ $s['count'] }}</span></a>
          @endforeach
        </nav>
      @endif

      @foreach($open as $s)
        @include('approvals._section', ['s' => $s, 'actions' => true])
      @endforeach

      @if($watch->isNotEmpty())
        <h6 class="mt-4 mb-1" style="color:var(--ink)">At branch review</h6>
        <p class="small mb-3" style="color:var(--ink-2)">The branch decides these first; you can still decide one yourself, for example when it has waited too long.</p>
        @foreach($watch as $s)
          @include('approvals._section', ['s' => $s, 'actions' => true])
        @endforeach
      @endif
    @endif
  </div>
  @endsection

</x-sales-dashboard>
