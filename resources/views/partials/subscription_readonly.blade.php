{{--
    While the subscription is locked: grey out everything that would save or change
    data, so people see straight away that it won't work. This is only the visible
    side; the server refuses the request anyway (ReadOnlyWhenUnpaid).

    Switched off for: GET forms (search/filter), the logout form, the billing page,
    and anything marked data-readonly-allow.
--}}
@php $sub = app(\App\Support\Subscription::class); @endphp
@if ($sub->locked() && ! $sub->bypasses(auth()->user()))
<style>
    .subscription-readonly { opacity: .55; cursor: not-allowed !important; }
</style>
<script>
(function () {
    var MSG = 'Read-only: the subscription is overdue. Saving and changes are switched off until payment is made.';
    var PAY = @json(url('billing'));

    function allowed(el) {
        return !!(el.closest && el.closest('[data-readonly-allow], #logout-form, form[action$="/billing/pay"], form[action$="/logout"]'));
    }

    function writes(form) {
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        var spoof = form.querySelector('input[name="_method"]');
        if (spoof) method = spoof.value.toLowerCase();
        return method !== 'get';
    }

    function disable(el) {
        if (el.dataset.readonlyDone) return;
        el.dataset.readonlyDone = '1';
        el.classList.add('subscription-readonly', 'disabled');
        el.setAttribute('aria-disabled', 'true');
        el.setAttribute('title', MSG);
        if ('disabled' in el) el.disabled = true;
    }

    // Links and buttons that only open a "new"/"edit" screen or modal.
    function opensWriteScreen(el) {
        var href = (el.getAttribute('href') || '').split('?')[0];
        if (/\/(create|edit)$/i.test(href) || /^\/?staffAdd$/i.test(href)) return true;
        var target = el.getAttribute('data-bs-target') || el.getAttribute('href') || '';
        if (el.getAttribute('data-bs-toggle') === 'modal' && target.charAt(0) === '#') {
            var modal = document.querySelector(target);
            if (modal) {
                var forms = modal.querySelectorAll('form');
                for (var i = 0; i < forms.length; i++) if (writes(forms[i])) return true;
            }
        }
        return false;
    }

    function sweep(root) {
        root.querySelectorAll('form').forEach(function (form) {
            if (!writes(form) || allowed(form)) return;
            form.querySelectorAll('button:not([type="button"]), input[type="submit"], [data-loading]').forEach(disable);
        });
        root.querySelectorAll('a[href], button[data-bs-toggle="modal"]').forEach(function (el) {
            if (!allowed(el) && opensWriteScreen(el)) disable(el);
        });
    }

    // Block anything that still gets through: a submitted form, or a click on a disabled link.
    document.addEventListener('submit', function (e) {
        if (writes(e.target) && !allowed(e.target)) { e.preventDefault(); e.stopImmediatePropagation(); alert(MSG); }
    }, true);
    document.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('.subscription-readonly');
        if (el) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);

    // AJAX saves (jQuery and fetch): stop non-GET calls before they are sent.
    if (window.jQuery) {
        jQuery.ajaxPrefilter(function (options, original, xhr) {
            var m = (options.type || options.method || 'GET').toUpperCase();
            if (['GET', 'HEAD', 'OPTIONS'].indexOf(m) === -1) { xhr.abort(); alert(MSG); }
        });
    }
    if (window.fetch) {
        var realFetch = window.fetch;
        window.fetch = function (input, init) {
            var m = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
            if (['GET', 'HEAD', 'OPTIONS'].indexOf(m) === -1) {
                alert(MSG);
                return Promise.reject(new Error('subscription-locked'));
            }
            return realFetch.apply(this, arguments);
        };
    }

    function start() {
        sweep(document);
        // Rows and modals added later (DataTables, etc.)
        var queued = false;
        new MutationObserver(function () {
            if (queued) return;
            queued = true;
            setTimeout(function () { queued = false; sweep(document); }, 150);
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
</script>
@endif
