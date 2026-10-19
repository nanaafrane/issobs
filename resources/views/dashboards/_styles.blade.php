<style>
  /* Dashboard tokens: categorical slots 1-3 for series, a reserved status set, recessive ink for axes. */
  .dash {
    --ink: #22303e; --ink-2: #566a7f; --ink-3: #8592a3; --line: #e7e9ee; --surface: #fff; --wash: #f6f7f9;
    --s1: #2a78d6; --s2: #eb6834; --s3: #1baf7a;
    --good: #0f8a4f; --good-bg: #e6f4ec; --warn: #a86400; --warn-bg: #fdf3e1; --bad: #c62f2e; --bad-bg: #fbe9e9;
  }
  .dash .dash-head { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; justify-content: space-between; margin-bottom: 1.25rem; }
  .dash .dash-head h4 { margin: 0; font-weight: 700; color: var(--ink); }
  .dash .dash-head .sub { color: var(--ink-2); font-size: .875rem; }
  .dash .dash-tabs { display: inline-flex; background: var(--wash); border: 1px solid var(--line); border-radius: 8px; padding: 3px; gap: 2px; }
  .dash .dash-tabs a { padding: .35rem .85rem; border-radius: 6px; color: var(--ink-2); font-weight: 600; font-size: .85rem; }
  .dash .dash-tabs a.active { background: var(--surface); color: var(--ink); box-shadow: 0 1px 2px rgba(0,0,0,.08); }
  .dash .dash-filters { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
  .dash .dash-filters .form-control, .dash .dash-filters .form-select { width: auto; min-width: 9rem; }
  .dash [hidden] { display: none !important; }

  .dash .kpi { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 1rem 1.1rem; height: 100%; display: flex; flex-direction: column; gap: .2rem; }
  .dash .kpi .lbl { color: var(--ink-2); font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
  .dash .kpi .val { color: var(--ink); font-size: 1.4rem; font-weight: 700; line-height: 1.2; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .dash .kpi .note { color: var(--ink-3); font-size: .8rem; }
  .dash .kpi a.note:hover { color: var(--s1); }
  .dash .chip { display: inline-flex; align-items: center; gap: .2rem; font-size: .75rem; font-weight: 600; padding: .1rem .45rem; border-radius: 999px; width: fit-content; white-space: nowrap; }
  .dash .kpi .chip { white-space: normal; }
  .dash .chip.good { color: var(--good); background: var(--good-bg); }
  .dash .chip.warn { color: var(--warn); background: var(--warn-bg); }
  .dash .chip.bad  { color: var(--bad);  background: var(--bad-bg); }
  .dash .chip.flat { color: var(--ink-2); background: var(--wash); }

  .dash .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; height: 100%; }
  .dash .panel.h-auto { height: auto; }
  .dash .panel-h { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; padding: .9rem 1.1rem .4rem; }
  .dash .panel-h h6 { margin: 0; font-weight: 700; color: var(--ink); }
  .dash .panel-h .hint { color: var(--ink-3); font-size: .78rem; }
  .dash .panel-b { padding: .25rem 1.1rem 1rem; }
  .dash .legend { display: flex; flex-wrap: wrap; gap: .9rem; font-size: .8rem; color: var(--ink-2); padding: 0 1.1rem; }
  .dash .legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: .35rem; vertical-align: -1px; }

  .dash table.dt { width: 100%; font-size: .84rem; font-variant-numeric: tabular-nums; }
  .dash table.dt th { color: var(--ink-3); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; border-bottom: 1px solid var(--line); padding: .45rem .5rem; white-space: nowrap; }
  .dash table.dt td { color: var(--ink); border-bottom: 1px solid var(--line); padding: .5rem; }
  .dash table.dt tr:last-child td { border-bottom: 0; }
  .dash table.dt tfoot td { font-weight: 700; border-top: 1px solid var(--line); }
  .dash table.dt .num { text-align: right; white-space: nowrap; }
  .dash table.dt .muted { color: var(--ink-3); }
  .dash .neg { color: var(--bad); }

  .dash .bar-list { list-style: none; margin: 0; padding: 0; }
  .dash .bar-list li { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .15rem .75rem; margin-bottom: .6rem; font-size: .84rem; color: var(--ink); }
  .dash .bar-list .track { grid-column: 1 / -1; height: 6px; background: var(--wash); border-radius: 4px; overflow: hidden; }
  .dash .bar-list .fill { height: 100%; background: var(--s1); border-radius: 4px; }
  .dash .bar-list .v { color: var(--ink-2); font-variant-numeric: tabular-nums; }

  .dash .alert-list { list-style: none; margin: 0; padding: 0; }
  .dash .alert-list li { display: flex; justify-content: space-between; gap: .75rem; padding: .5rem 0; border-bottom: 1px solid var(--line); font-size: .85rem; }
  .dash .alert-list li:last-child { border-bottom: 0; }
  .dash .empty { color: var(--ink-3); font-size: .85rem; padding: 1rem 0; text-align: center; }
  .dash .chart { min-height: 260px; }
</style>
