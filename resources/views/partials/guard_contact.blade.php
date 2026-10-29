{{-- A guard's phone as a tap-to-call link (and WhatsApp), for operations to reach them quickly. $emp: employee or null. --}}
@if($emp?->phone_number)
    @php($digits = preg_replace('/\D+/', '', $emp->phone_number))
    @php($intl = str_starts_with($digits, '0') ? '233' . substr($digits, 1) : $digits)
    <span class="guard-contact text-nowrap" style="font-size:.72rem">
        <a href="tel:{{ $digits }}" class="text-muted" title="Call {{ $emp->name }}"><i class="bx bx-phone"></i> {{ $emp->phone_number }}</a>
        <a href="https://wa.me/{{ $intl }}" target="_blank" rel="noopener" class="text-success ms-1" title="WhatsApp {{ $emp->name }}"><i class="bx bxl-whatsapp"></i></a>
    </span>
@endif
