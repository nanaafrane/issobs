{{--
  One headline figure.
  @param string $label, string $value, ?string $title (full figure on hover), ?string $note,
         ?array $delta ['text','dir'], ?bool $upIsGood (default true), ?string $status good|warn|bad,
         ?string $statusText, ?string $href (drill-down), ?bool $neutral (change is neither good nor bad)
--}}
@php
  $upIsGood = $upIsGood ?? true;
  $deltaClass = null;
  if (! empty($delta)) {
      $deltaClass = ($delta['dir'] === 'flat' || ! empty($neutral)) ? 'flat' : ((($delta['dir'] === 'up') === $upIsGood) ? 'good' : 'bad');
  }
  $arrow = ['up' => '▲', 'down' => '▼', 'flat' => '■'];
  $icon = ['good' => '●', 'warn' => '▲', 'bad' => '■'];
@endphp
<div class="kpi">
  <span class="lbl">{{ $label }}</span>
  <span class="val" @isset($title) title="{{ $title }}" @endisset>{{ $value }}</span>
  @if(! empty($delta))
    <span class="chip {{ $deltaClass }}"><span aria-hidden="true">{{ $arrow[$delta['dir']] }}</span> {{ $delta['text'] }}</span>
  @endif
  @if(! empty($status))
    <span class="chip {{ $status }}"><span aria-hidden="true">{{ $icon[$status] }}</span> {{ $statusText }}</span>
  @endif
  @if(! empty($note))
    @if(! empty($href))
      <a class="note" href="{{ $href }}">{{ $note }} →</a>
    @else
      <span class="note">{{ $note }}</span>
    @endif
  @endif
</div>
