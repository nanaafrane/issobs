{{--
    Connection helper on every page (docs/offline-sync.md, "When the connection drops"): a
    "You're offline" panel instead of the old red banner, Save paused while offline, drafts of
    invoice / proforma / receipt / fuel / maintenance forms kept on this device, and the service
    worker that shows an offline page instead of the browser's error.
--}}
<script>
    window.ISSOBS_CONN = {
        sw: @json(asset('sw.js')),
        up: @json(url('up')),
        savedCheck: @json(Auth::check() ? route('forms.saved') : null),
        user: @json(Auth::id()),
        userName: @json(Auth::user()?->name),
        rendered: @json(now()->getTimestampMs()),
        saved: @json(session()->has('success') || session()->has('primary')),
        errors: @json(isset($errors) && $errors->any()),
    };
</script>
<script src="{{ asset('offline/drafts.js') }}"></script>
<script src="{{ asset('offline/connection.js') }}"></script>
