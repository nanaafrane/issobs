{{-- Small ApexCharts wrapper so every dashboard chart shares one look: thin marks, recessive axes,
     fixed series colours (slot 1 blue, 2 orange, 3 aqua), money-aware tooltips, no dual axes. --}}
<script>
  (function () {
    if (!window.ApexCharts) {
      var s = document.createElement('script');
      s.src = @json(asset('vendor/libs/apex-charts/apexcharts.js'));
      s.onload = function () { document.dispatchEvent(new Event('dash:charts')); };
      document.head.appendChild(s);
    } else {
      document.addEventListener('DOMContentLoaded', function () { document.dispatchEvent(new Event('dash:charts')); });
    }

    var C = { s1: '#2a78d6', s2: '#eb6834', s3: '#1baf7a', ink2: '#566a7f', ink3: '#8592a3', line: '#e7e9ee', bad: '#c62f2e' };
    var money = function (v) { return v == null ? '—' : 'GH₵ ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var short = function (v) {
      var a = Math.abs(v);
      if (a >= 1e6) return (v / 1e6).toFixed(1) + 'M';
      if (a >= 1e3) return (v / 1e3).toFixed(0) + 'K';
      return String(Math.round(v));
    };

    function base(type, opts) {
      return Object.assign({
        chart: { type: type, height: opts.height || 280, toolbar: { show: false }, fontFamily: 'inherit', foreColor: C.ink2, animations: { enabled: false } },
        legend: { show: false },
        dataLabels: { enabled: false },
        grid: { borderColor: C.line, strokeDashArray: 0, xaxis: { lines: { show: false } }, padding: { left: 6, right: 22 } },
        xaxis: { axisBorder: { color: C.line }, axisTicks: { show: false }, labels: { style: { colors: C.ink3, fontSize: '11px' }, hideOverlappingLabels: true, rotate: 0, trim: true } },
        yaxis: { labels: { style: { colors: C.ink3, fontSize: '11px' }, formatter: opts.money ? short : function (v) { return v == null ? '' : Math.round(v); } } },
        tooltip: { shared: true, intersect: false, y: { formatter: opts.money ? money : function (v) { return v == null ? '—' : v.toLocaleString(); } } },
        states: { hover: { filter: { type: 'darken', value: 0.9 } } },
      }, opts.extra || {});
    }

    window.Dash = {
      colors: C, money: money,
      /** Grouped bars over categories (one y-scale). series: [{name, data}] */
      bars: function (el, labels, series, opts) {
        opts = opts || {};
        var o = base('bar', opts);
        o.series = series;
        o.colors = opts.colors || [C.s1, C.s2, C.s3];
        o.xaxis.categories = labels;
        o.plotOptions = { bar: { horizontal: !!opts.horizontal, columnWidth: opts.width || '58%', barHeight: '62%', borderRadius: 4, borderRadiusApplication: 'end', distributed: !!opts.distributed } };
        o.stroke = { show: true, width: 2, colors: ['transparent'] };
        if (opts.horizontal) { o.xaxis.labels.formatter = opts.money ? short : function (v) { return Math.round(v); }; o.yaxis.labels.formatter = function (v) { return v; }; o.tooltip.shared = false; o.tooltip.intersect = true; }
        new ApexCharts(document.querySelector(el), o).render();
      },
      /** Lines over time. series: [{name, data, type?}] — bars mixed in share the same y-scale. */
      lines: function (el, labels, series, opts) {
        opts = opts || {};
        var o = base('line', opts);
        o.series = series;
        o.colors = opts.colors || [C.s1, C.s2, C.s3];
        o.xaxis.categories = labels;
        o.stroke = { width: series.map(function (s) { return s.type === 'column' ? 0 : 2; }), curve: 'straight', dashArray: series.map(function (s) { return s.dashed ? 5 : 0; }) };
        o.markers = { size: 0, hover: { size: 5 } };
        o.plotOptions = { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } };
        if (opts.min != null) o.yaxis.min = opts.min;
        new ApexCharts(document.querySelector(el), o).render();
      },
      /** Share of a whole (≤ 5 parts). */
      donut: function (el, labels, values, opts) {
        opts = opts || {};
        var o = base('donut', opts);
        o.series = values; o.labels = labels;
        o.colors = opts.colors || [C.s1, C.s2, C.s3, '#eda100', '#6250d6'];
        o.stroke = { width: 2, colors: ['#fff'] };
        o.legend = { show: true, position: 'bottom', fontSize: '12px', markers: { radius: 3 } };
        o.tooltip = { y: { formatter: opts.money ? money : undefined } };
        o.plotOptions = { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: opts.totalLabel || 'Total', formatter: function (w) { var t = w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); return opts.money ? 'GH₵ ' + short(t) : t.toLocaleString(); } }, value: { formatter: function (v) { return opts.money ? 'GH₵ ' + short(Number(v)) : v; } } } } } };
        delete o.xaxis; delete o.yaxis; delete o.grid;
        new ApexCharts(document.querySelector(el), o).render();
      },
    };
  })();
</script>
