@props(['level' => 0, 'reason' => null])
{{-- Payment priority badge: "Pay first" (urgent) / "Pay early" (priority). Renders nothing for normal. --}}
{!! \App\Support\PayPriority::badge($level, $reason) !!}
