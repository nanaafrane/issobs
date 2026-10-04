{{--
    DtCompact: send only what the server reads from a server-side DataTable.

    DataTables sends ~6 parameters for EVERY column on every request (plus ColumnControl's).
    On wide tables (employees: 28 columns, master salaries: 53) the GET URL passes the
    ~8 KB request-line limit of Apache / LiteSpeed (414) and the 255-argument limit of
    common ModSecurity rules (403) - both show up as "DataTables warning: Ajax error".
    The controllers only read:
      columns[<index>][search][value]   (searched columns only, keyed by column index)
      order[0][column] / order[0][dir], search[value], start, length, draw, page filters

    Usage - replace the table's `ajax: { url, type, data, dataSrc }` object with:
        ajax: DtCompact.ajax({ url: ..., data: function (d) { ...filters...; }, dataSrc: ... })

    Why a function: DataTables calls `ajax.data` BEFORE its "preXhr" event, and ColumnControl
    adds the column search values IN "preXhr". Compacting inside `ajax.data` therefore drops
    the column searches. An ajax *function* is called after "preXhr", so the values are there.
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
        /**
         * Build a DataTables `ajax` function: runs opts.data (page filters), lets ColumnControl
         * add its searches (preXhr has already run), compacts, then sends a GET request.
         * opts: { url: string|fn, data: fn(d), dataSrc: fn(json) -> rows (optional) }
         */
        ajax: function (opts) {
            return function (data, callback) {
                if (typeof opts.data === 'function') {
                    const r = opts.data(data);
                    if (r && r !== data) Object.assign(data, r);
                }
                window.DtCompact.request(data);
                const url = typeof opts.url === 'function' ? opts.url() : opts.url;

                return $.ajax({
                    url: url, type: 'GET', data: data, dataType: 'json', cache: false,
                    success: function (json) {
                        if (typeof opts.dataSrc === 'function') {
                            json = Object.assign({}, json, { data: opts.dataSrc(json) });
                        }
                        callback(json);
                    },
                    error: function (xhr, status) {
                        if (status === 'abort') return;
                        callback({ draw: data.draw, data: [], recordsTotal: 0, recordsFiltered: 0,
                                   error: 'Could not load the table (HTTP ' + xhr.status + '). Please try again.' });
                    },
                });
            };
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
