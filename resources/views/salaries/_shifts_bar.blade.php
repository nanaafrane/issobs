{{--
    Shift counts above a salary table, with buttons that filter the table's SHIFT column.
    Needs $shiftInfo = \App\Support\SalaryShifts::forSalaries($salaries).
--}}
@php
    $shiftCounts = \App\Support\SalaryShifts::counts($shiftInfo);
    $shiftTotal = array_sum($shiftCounts);
@endphp
@if($shiftTotal)
<div class="d-flex flex-wrap align-items-center gap-2 mb-2 shift-bar" data-table="#myTable">
    <span class="small fw-semibold me-1">Shifts:</span>
    <button type="button" class="btn btn-sm btn-outline-secondary py-0 shift-filter active" data-shift="">All <span class="badge bg-white text-dark">{{ $shiftTotal }}</span></button>
    @foreach(['day', 'night', 'rotate'] as $sk)
        @if($shiftCounts[$sk])
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 shift-filter" data-shift="{{ \App\Support\SalaryShifts::LOOK[$sk][0] }}">{!! \App\Support\SalaryShifts::badge($sk) !!} <span class="badge bg-white text-dark">{{ $shiftCounts[$sk] }}</span></button>
        @endif
    @endforeach
    @if($shiftCounts['none'])
        <button type="button" class="btn btn-sm btn-outline-warning py-0 shift-filter" data-shift="No shift">{!! \App\Support\SalaryShifts::badge(null) !!} <span class="badge bg-white text-dark">{{ $shiftCounts['none'] }}</span></button>
        <span class="small text-muted">Set missing shifts on the attendance sheet (Shift column).</span>
    @endif
</div>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        document.querySelectorAll('.shift-bar').forEach(function (bar) {
            var sel = bar.dataset.table, table = document.querySelector(sel);
            if (!table) { return; }
            var idx = Array.prototype.findIndex.call(table.querySelectorAll('thead th'), function (th) { return th.textContent.trim().toUpperCase() === 'SHIFT'; });
            if (idx < 0) { return; }
            var api = null;
            if (window.DataTable && DataTable.isDataTable && DataTable.isDataTable(sel)) { api = new DataTable.Api(sel); }
            else if (window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(sel)) { api = jQuery(sel).DataTable(); }
            bar.querySelectorAll('.shift-filter').forEach(function (b) {
                b.addEventListener('click', function () {
                    bar.querySelectorAll('.shift-filter').forEach(function (x) { x.classList.toggle('active', x === b); });
                    var want = b.dataset.shift;
                    if (api) {
                        api.column(idx).search(want ? '^\\s*' + want : '', true, false).draw();
                    } else {
                        table.querySelectorAll('tbody tr').forEach(function (tr) {
                            var cell = tr.children[idx];
                            tr.style.display = !want || (cell && cell.textContent.trim().indexOf(want) === 0) ? '' : 'none';
                        });
                    }
                });
            });
        });
    }, 0);
});
</script>
@endonce
@endif
@include('salaries._shift_detail_modal')
