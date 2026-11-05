{{--
  Period picker: the period list and the calendar that fits it.
    Monthly / Quarterly / Half-year / Yearly -> a month picker (any month in the period)
    Daily / Weekly                           -> a date picker (any day in the period)
    Date range                               -> From and To dates
  Submits ?period=…&month=YYYY-MM, ?period=…&date=Y-m-d or ?period=custom&from=…&to=…
  (inactive inputs are disabled, so only the one that applies is sent).

  @include('partials.period_picker', [
      'id' => 'rcpt',                 // prefix for element ids
      'period' => $period, 'anchor' => $anchor, 'from' => $from, 'to' => $to,
      'labels' => true,               // labels above (report forms) or visually hidden (dashboard)
      'wrap' => 'col-auto',           // class around each control
      'size' => 'sm',
  ])
--}}
@php
    $id = $id ?? 'pp';
    $labels = $labels ?? true;
    $wrap = $wrap ?? 'col-auto';
    $periods = $periods ?? \App\Support\ReceiptReport::PERIODS;
    $kind = \App\Support\PeriodPicker::calendarFor($period);
    $today = now();
    $lbl = fn ($for, $text) => '<label class="' . ($labels ? 'form-label small mb-0' : 'visually-hidden') . '" for="' . $id . '-' . $for . '">' . e($text) . '</label>';
@endphp
<div class="{{ $wrap }}">
    {!! $lbl('period', 'Period') !!}
    <select name="period" id="{{ $id }}-period" class="form-select form-select-sm" data-period-select>
        @foreach($periods as $val => $label)
            <option value="{{ $val }}" data-calendar="{{ \App\Support\PeriodPicker::calendarFor($val) }}" @selected($period === $val)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="{{ $wrap }}" data-calendar-group="month" @if($kind !== 'month') hidden @endif>
    {!! $lbl('month', 'Month in period') !!}
    <input type="month" name="month" id="{{ $id }}-month" class="form-control form-control-sm" value="{{ $anchor->format('Y-m') }}"
           max="{{ $today->format('Y-m') }}" placeholder="YYYY-MM" pattern="\d{4}-\d{2}" title="Any month in the period" @disabled($kind !== 'month')>
</div>
<div class="{{ $wrap }}" data-calendar-group="date" @if($kind !== 'date') hidden @endif>
    {!! $lbl('date', 'Date in period') !!}
    <input type="date" name="date" id="{{ $id }}-date" class="form-control form-control-sm" value="{{ $anchor->format('Y-m-d') }}"
           max="{{ $today->toDateString() }}" title="Any day in the period" @disabled($kind !== 'date')>
</div>
<div class="{{ $wrap }}" data-calendar-group="range" @if($kind !== 'range') hidden @endif>
    {!! $lbl('from', 'From') !!}
    <input type="date" name="from" id="{{ $id }}-from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}" max="{{ $today->toDateString() }}" @disabled($kind !== 'range')>
</div>
<div class="{{ $wrap }}" data-calendar-group="range" @if($kind !== 'range') hidden @endif>
    {!! $lbl('to', 'To') !!}
    <input type="date" name="to" id="{{ $id }}-to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}" max="{{ $today->toDateString() }}" @disabled($kind !== 'range')>
</div>
@once
<script>
    // Period pickers: show the calendar that fits the period, send only that input, submit on change.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-period-select]').forEach(function (sel) {
            var form = sel.form;
            if (!form || form.dataset.periodPicker === '1') return;
            form.dataset.periodPicker = '1';
            var submit = function () { form.requestSubmit ? form.requestSubmit() : form.submit(); };
            var groups = form.querySelectorAll('[data-calendar-group]');
            function show() {
                var kind = sel.options[sel.selectedIndex].dataset.calendar;
                groups.forEach(function (g) {
                    var on = g.dataset.calendarGroup === kind;
                    g.hidden = !on;
                    g.querySelectorAll('input').forEach(function (i) { i.disabled = !on; });   // disabled inputs are not sent
                });
                return kind;
            }
            sel.addEventListener('change', function () { if (show() !== 'range') submit(); });
            groups.forEach(function (g) {
                g.querySelectorAll('input').forEach(function (i) {
                    i.addEventListener('change', function () {
                        if (g.dataset.calendarGroup !== 'range') { if (i.value) submit(); return; }
                        var f = form.querySelector('[data-calendar-group="range"] input[name="from"]'), t = form.querySelector('[data-calendar-group="range"] input[name="to"]');
                        if (i === t && f.value && t.value) submit();
                    });
                });
            });
            window.addEventListener('pageshow', show);   // back button restores the page as it was
        });
    });
</script>
@endonce
