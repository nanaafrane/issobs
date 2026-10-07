{{-- Wires .js-inv-chip buttons to the CLIENT INVOICE column (index $col) of an initialised DataTable. Include AFTER the table is created. --}}
<script>
    (function () {
        const table = $('{{ $table ?? '#myTable' }}').DataTable();
        const col = {{ $col ?? 10 }};
        let active = '';

        function apply(value) {
            active = value;
            // CLIENT INVOICE cells carry data-search="inv-paid|inv-paid_late|inv-part|inv-overdue|inv-unpaid|inv-none|inv-noclient".
            table.column(col).search(value ? '^' + value + '$' : '', { regex: true, smart: false }).draw();
            $('.js-inv-chip').each(function () {
                const on = $(this).data('inv') === value;
                $(this).attr('aria-pressed', on ? 'true' : 'false').toggleClass('active', on);
            });
            $('.js-inv-chip-clear').toggleClass('d-none', !value);
        }

        $('.js-inv-chip').on('click', function () { apply(active === $(this).data('inv') ? '' : $(this).data('inv')); });
        $('.js-inv-chip-clear').on('click', () => apply(''));

        if (window.bootstrap) {
            document.querySelectorAll('{{ $table ?? '#myTable' }} .inv-flag, {{ $table ?? '#myTable' }} [data-bs-toggle="tooltip"]')
                .forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
        }
    })();
</script>
