<style>
    .att-metric { background: #fff; border: 1px solid #e7e7ef; border-radius: .5rem; padding: .7rem .9rem; height: 100%; }
    .att-metric .k { font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #8592a3; }
    .att-metric .v { font-size: 1.4rem; font-weight: 600; color: #384551; }
    .att-metric.bad .v { color: #e5383b; }
    .att-metric.warn .v { color: #c77c02; }
    .att-overview td, .att-overview th { vertical-align: middle; white-space: nowrap; }

    .att-grid { border-collapse: separate; border-spacing: 0; font-size: .78rem; }
    .att-grid th, .att-grid td { border-right: 1px solid #eceef1; border-bottom: 1px solid #eceef1; padding: .25rem .35rem; white-space: nowrap; background: #fff; }
    .att-grid thead th { position: sticky; top: 0; z-index: 2; background: #f5f5f9; text-align: center; font-weight: 600; }
    .att-grid .sticky-col { position: sticky; left: 0; z-index: 1; min-width: 210px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; }
    .att-grid thead .sticky-col { z-index: 3; }
    .att-grid td.cell { width: 2.1rem; min-width: 2.1rem; text-align: center; cursor: pointer; font-weight: 600; user-select: none; }
    .att-grid td.cell:hover { outline: 2px solid #696cff; outline-offset: -2px; }
    .att-grid td.cell.future { background: #fafafa; color: #b8bec6; }
    .att-grid th.we, .att-grid td.we { background-image: linear-gradient(rgba(105,108,255,.05), rgba(105,108,255,.05)); }
    .att-grid th.today { background: #e7e7ff; }
    .att-present { color: #1b7a3a; background-color: #e9f7ee !important; }
    .att-absent { color: #c62828; background-color: #fdecec !important; }
    .att-leave { color: #5f5fb3; background-color: #efeffd !important; }
    .att-terminated { color: #fff; background-color: #6b7280 !important; }
    .att-moved { color: #0e7490; background-color: #e0f2fe !important; font-style: italic; }
    .att-pending { color: #8a5a00; background-color: #fff4d6 !important; }
    .att-grid tfoot td { background: #f8f9fa; font-weight: 600; text-align: center; }
    .att-grid tfoot td.short { color: #c62828; }
    .att-grid tr.former td { opacity: .6; }
    .att-grid .tot { text-align: right; min-width: 2.6rem; }
    .att-legend span { display: inline-block; border-radius: .25rem; padding: 0 .4rem; margin-right: .35rem; font-weight: 600; font-size: .78rem; }

    #att-pop { position: absolute; z-index: 1080; display: none; background: #fff; border: 1px solid #d9dee3; border-radius: .5rem; box-shadow: 0 .4rem 1.2rem rgba(0,0,0,.15); padding: .6rem; width: 240px; }
    #att-pop .btn { padding: .2rem .45rem; font-size: .78rem; }
    .att-side .card-body { font-size: .85rem; }
</style>
