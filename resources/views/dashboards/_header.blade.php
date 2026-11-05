{{-- Title, dashboard tabs, and the period / office filters (one row above everything). --}}
@use('App\Support\Dashboards\DashboardContext')
@php
  $offices = $ctx->officeOptions();
  $prevCtx = $ctx->shifted(1);
  $nextCtx = $ctx->next();
  $isCustom = $ctx->period === 'custom';
  $today = now()->toDateString();
@endphp
<div class="dash-head">
  <div>
    <h4>{{ $title }}</h4>
    <div class="sub">
      {{ $ctx->label() }} · {{ $ctx->scopeLabel() }}
      @if($ctx->isRunning()) · to date ({{ now()->format('j M') }}) @endif
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
    @if(count($boards) > 1)
      <nav class="dash-tabs" aria-label="Dashboards">
        @foreach($boards as $b)
          <a href="{{ route('dashboard.show', ['board' => $b] + $ctx->query()) }}" class="{{ $b === $ctx->board ? 'active' : '' }}"
             @if($b === $ctx->board) aria-current="page" @endif>{{ DashboardContext::LABELS[$b] }}</a>
        @endforeach
      </nav>
    @endif

    <form method="GET" action="{{ route('dashboard.show', ['board' => $ctx->board]) }}" class="dash-filters" id="dash-filters">
      <div class="btn-group btn-group-sm" role="group" aria-label="Move period">
        <a class="btn btn-outline-secondary" href="{{ route('dashboard.show', ['board' => $ctx->board] + $prevCtx->query()) }}"
           title="{{ $prevCtx->label() }}" aria-label="Previous period">‹</a>
        @if($nextCtx)
          <a class="btn btn-outline-secondary" href="{{ route('dashboard.show', ['board' => $ctx->board] + $nextCtx->query()) }}"
             title="{{ $nextCtx->label() }}" aria-label="Next period">›</a>
        @else
          <span class="btn btn-outline-secondary disabled" aria-disabled="true">›</span>
        @endif
      </div>

      @include('partials.period_picker', ['id' => 'dash', 'period' => $ctx->period, 'anchor' => $ctx->anchor, 'from' => $ctx->from, 'to' => $ctx->to,
          'labels' => false, 'wrap' => 'dash-pp', 'periods' => DashboardContext::PERIODS])

      @if($offices->count() > 1)
        <label class="visually-hidden" for="dash-field">Office</label>
        <select id="dash-field" name="field" class="form-select form-select-sm">
          <option value="">{{ $ctx->allowedIds === null ? 'All offices' : 'All my offices' }}</option>
          @foreach($offices as $o)
            <option value="{{ $o->id }}" @selected($ctx->fieldId === (int) $o->id)>{{ $o->parent_field_id ? '— ' : '' }}{{ $o->name }}</option>
          @endforeach
        </select>
      @endif
      <button class="btn btn-sm btn-primary">Apply</button>
    </form>
  </div>
</div>
<script>
  (function () {
    // The period picker submits by itself; the office list does too.
    var field = document.getElementById('dash-field');
    if (field) field.addEventListener('change', function () { var f = field.form; f.requestSubmit ? f.requestSubmit() : f.submit(); });
  })();
</script>
