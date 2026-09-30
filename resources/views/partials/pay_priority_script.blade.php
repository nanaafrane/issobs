{{-- Wires .js-pay-chip buttons to the PAY column (index $col) of an initialised DataTable. Include AFTER the table is created. --}}
<script>
    (function () {
        const table = $('{{ $table ?? '#myTable' }}').DataTable();
        const col = {{ $col ?? 2 }};
        let active = '';

        function apply(value) {
            active = value;
            // PAY cells carry data-search="pay-urgent|pay-priority|pay-normal"; match exactly.
            table.column(col).search(value ? '^' + value + '$' : '', { regex: true, smart: false }).draw();
            $('.js-pay-chip').each(function () {
                const on = $(this).data('pay') === value;
                $(this).attr('aria-pressed', on ? 'true' : 'false')
                    .toggleClass('active', on);
            });
            $('.js-pay-chip-clear').toggleClass('d-none', !value);
        }

        $('.js-pay-chip').on('click', function () { apply(active === $(this).data('pay') ? '' : $(this).data('pay')); });
        $('.js-pay-chip-clear').on('click', () => apply(''));
    })();
</script>
