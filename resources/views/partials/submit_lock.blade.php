{{--
    Stop a form being sent twice. Any form with a button marked data-loading:
    once the form really starts sending (after any "Kindly confirm?" dialog or
    page check has said yes), its data-loading buttons are disabled and show
    "Saving…", and pressing Enter or the button again does nothing. If the
    browser comes back to the page (Back button), the buttons work again.
    The server also refuses to save the same form twice (SubmissionGuard).
--}}
<script>
(function () {
    function lockable(form) {
        return form && form.querySelector && form.querySelector('[data-loading]');
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!lockable(form)) return;

        if (form.dataset.submitting === '1') {   // already on its way
            e.preventDefault();
            return;
        }

        // Let confirm dialogs and page checks decide first; only lock if the form is really going.
        setTimeout(function () {
            if (e.defaultPrevented) return;
            form.dataset.submitting = '1';
            form.querySelectorAll('[data-loading]').forEach(function (btn) {
                if (!btn.dataset.idleLabel) btn.dataset.idleLabel = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving…';
            });
        }, 0);
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitting="1"]').forEach(function (form) {
            delete form.dataset.submitting;
            form.querySelectorAll('[data-loading]').forEach(function (btn) {
                btn.disabled = false;
                if (btn.dataset.idleLabel) btn.innerHTML = btn.dataset.idleLabel;
            });
        });
    });
})();
</script>
