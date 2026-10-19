{{-- Title, dashboard tabs, and the month / office filters (one row above everything). --}}
@use('App\Support\Dashboards\DashboardContext')
@php($offices = $ctx->officeOptions())
<div class="dash-head">
  <div>
    <h4>{{ $title }}</h4>
    <div class="sub">
      {{ $ctx->month->format('F Y') }} · {{ $ctx->scopeLabel() }}
      @if($ctx->isCurrentMonth()) · month to date ({{ now()->format('j M') }}) @endif
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 align-items-center">
    @if(count($boards) > 1)
      <nav class="dash-tabs" aria-label="Dashboards">
        @foreach($boards as $b)
          <a href="{{ route('dashboard.show', ['board' => $b] + $ctx->query()) }}" class="{{ $b === $ctx->board ? 'active' : '' }}"
             @if($b === $ctx->board) aria-current="page" @endif>{{ DashboardContext::LABELS[$b] }}</a>
        @endforeach
      </nav>
    @endif

    <form method="GET" action="{{ route('dashboard.show', ['board' => $ctx->board]) }}" class="dash-filters">
      <label class="visually-hidden" for="dash-month">Month</label>
      <input type="month" id="dash-month" name="month" class="form-control form-control-sm"
             value="{{ $ctx->month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()">
      @if($offices->count() > 1)
        <label class="visually-hidden" for="dash-field">Office</label>
        <select id="dash-field" name="field" class="form-select form-select-sm" onchange="this.form.submit()">
          @if($ctx->allowedIds === null)
            <option value="">All offices</option>
          @else
            <option value="">All my offices</option>
          @endif
          @foreach($offices as $o)
            <option value="{{ $o->id }}" @selected($ctx->fieldId === (int) $o->id)>{{ $o->parent_field_id ? '— ' : '' }}{{ $o->name }}</option>
          @endforeach
        </select>
      @endif
      <noscript><button class="btn btn-sm btn-primary">Apply</button></noscript>
    </form>
  </div>
</div>
