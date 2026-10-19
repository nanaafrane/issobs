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

      <label class="visually-hidden" for="dash-period">Period</label>
      <select id="dash-period" name="period" class="form-select form-select-sm">
        @foreach(DashboardContext::PERIODS as $key => $label)
          <option value="{{ $key }}" @selected($ctx->period === $key)>{{ $label }}</option>
        @endforeach
      </select>

      <span class="dash-named" @if($isCustom) hidden @endif>
        <label class="visually-hidden" for="dash-date">Any day in the period</label>
        <input type="date" id="dash-date" name="date" class="form-control form-control-sm" value="{{ $ctx->anchor->toDateString() }}"
               max="{{ $today }}" title="Any day in the period" @disabled($isCustom)>
      </span>
      <span class="dash-range d-inline-flex gap-1 align-items-center" @unless($isCustom) hidden @endunless>
        <label class="visually-hidden" for="dash-from">From</label>
        <input type="date" id="dash-from" name="from" class="form-control form-control-sm" value="{{ $ctx->from->toDateString() }}" max="{{ $today }}" @disabled(! $isCustom)>
        <span class="small" style="color:var(--ink-3)">to</span>
        <label class="visually-hidden" for="dash-to">To</label>
        <input type="date" id="dash-to" name="to" class="form-control form-control-sm" value="{{ $ctx->to->toDateString() }}" max="{{ $today }}" @disabled(! $isCustom)>
      </span>

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
    var form = document.getElementById('dash-filters');
    var period = document.getElementById('dash-period');
    function sync() {
      var custom = period.value === 'custom';
      form.querySelector('.dash-named').hidden = custom;
      form.querySelector('.dash-range').hidden = !custom;
      document.getElementById('dash-date').disabled = custom;          // disabled inputs are not submitted
      document.getElementById('dash-from').disabled = !custom;
      document.getElementById('dash-to').disabled = !custom;
    }
    period.addEventListener('change', function () { sync(); if (period.value !== 'custom') form.submit(); });
    var field = document.getElementById('dash-field');
    if (field) field.addEventListener('change', function () { form.submit(); });
    document.getElementById('dash-date').addEventListener('change', function () { form.submit(); });
  })();
</script>
