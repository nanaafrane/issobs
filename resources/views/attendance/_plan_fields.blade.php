{{--
    Guards required per shift, on the client create / edit forms.
    $plan: the plan in force now (edit) or null (create). $editing: show "Applies from".
--}}
@php
    $times = \App\Support\Attendance\ShiftClock::times($plan ?? null);
    $editing = $editing ?? false;
@endphp
<div class="row mt-4 g-3">
    <div class="col-12">
        <div class="form-label mb-0">{{ __('Guards required per shift') }}</div>
        <div class="small text-muted">
            Day only, night only, or both: put 0 for a shift the client does not need. Guards sign in against this plan.
            @if($editing && ! $plan && $client->guards)<br>No shift plan yet. The old record says <strong>{{ $client->guards }}</strong> guard(s): split them into day and night.@endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <label for="day_guards" class="form-label">{{ __('Day guards') }}</label>
        <input type="number" min="0" max="500" id="day_guards" name="day_guards" class="form-control @error('day_guards') is-invalid @enderror"
               value="{{ old('day_guards', $plan->day_guards ?? '') }}" @unless($editing) required @endunless>
        @error('day_guards')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
    </div>
    <div class="col-6 col-md-3">
        <label for="night_guards" class="form-label">{{ __('Night guards') }}</label>
        <input type="number" min="0" max="500" id="night_guards" name="night_guards" class="form-control @error('night_guards') is-invalid @enderror"
               value="{{ old('night_guards', $plan->night_guards ?? '') }}" @unless($editing) required @endunless>
        @error('night_guards')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">{{ __('Day shift') }}</label>
        <div class="input-group">
            <input type="time" name="day_start" class="form-control" value="{{ old('day_start', $times['day_start']) }}">
            <input type="time" name="day_end" class="form-control" value="{{ old('day_end', $times['day_end']) }}">
        </div>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">{{ __('Night shift') }}</label>
        <div class="input-group">
            <input type="time" name="night_start" class="form-control" value="{{ old('night_start', $times['night_start']) }}">
            <input type="time" name="night_end" class="form-control" value="{{ old('night_end', $times['night_end']) }}">
        </div>
    </div>
    @if($editing)
    <div class="col-6 col-md-3">
        <label for="effective_month" class="form-label">{{ __('Plan applies from') }}</label>
        <input type="month" id="effective_month" name="effective_month" class="form-control @error('effective_month') is-invalid @enderror"
               value="{{ old('effective_month', now()->format('Y-m')) }}">
        @error('effective_month')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
    </div>
    @endif
</div>
