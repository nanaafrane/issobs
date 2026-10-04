{{--
    DtCompact: send only what the server reads from a server-side DataTable.

    DataTables sends ~6 parameters for EVERY column on every request (plus ColumnControl's).
    On wide tables (employees: 28 columns, master salaries: 53) the GET URL passes the
    ~8 KB request-line limit of Apache / LiteSpeed (414 error), and the parameter count
    passes the 255-argument limit of common ModSecurity rules (403) - both show up as
    "DataTables warning: Ajax error". The controllers only read:
      columns[<index>][search][value]   (searched columns only, keyed by column index)
      order[0][column] / order[0][dir], search[value], start, length, draw, page filters
    so that is all we send. Usage:  ajax: { data: d => { ...; return DtCompact.request(d); } }
--}}
<script>
    window.DtCompact = window.DtCompact || {
        /** Shrink a DataTables request object in place and return it. */
        request: function (d) {
            const cols = {};
            (d.columns || []).forEach(function (c, i) {
                const v = (c && c.columnControl && c.columnControl.search && c.columnControl.search.value)
                       || (c && c.search && c.search.value) || '';
                if (v !== '') cols[i] = { search: { value: v } };
            });
            d.columns = cols;
            d.order = (d.order || []).slice(0, 1).map(o => ({ column: o.column, dir: o.dir }));
            d.search = { value: (d.search && d.search.value) || '' };
            return d;
        },
        /** Call fn(index, value) for every searched column, whether columns is an array or the compact object. */
        eachSearch: function (columns, fn) {
            Object.keys(columns || {}).forEach(function (i) {
                const c = columns[i] || {};
                const v = (c.columnControl && c.columnControl.search && c.columnControl.search.value) || (c.search && c.search.value) || '';
                if (v !== '') fn(i, v);
            });
        },
    };
</script>
