{{-- Side menu for the bank / accounts screens. Replaces the menu pasted into banks/index, show and deposit_*. --}}
@php
    $user = Auth::user();
    $canViewInvoices = $user?->hasRole(['Invoice', 'Finance Manager', 'Director']) ?? false;
    $canViewReceipts = $user?->hasRole(['Finance Manager', 'Manager', 'Admin Assistant']) ?? false;
    $canViewAccounts = $user?->hasRole(['Finance Manager', 'Director', 'Invoice', 'Manager', 'Officer']) ?? false;
    $canViewPayroll  = ($user?->hasPermission('Accounts') ?? false) && ($user?->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) ?? false);
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="#" class="app-brand-link">
            <span class="app-brand-logo demo"><img width="70px" src="{{ asset('img/icons/brands/issobs.png') }}" alt=""></span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">ISSOBS</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>
    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item">
            <a href="{{ url('home') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile text-primary me-2"></i>
                <div class="text-truncate"><strong>Dashboard</strong></div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text text-primary">Transactions</span></li>
        <li class="menu-item">
            <a href="{{ url('transaction') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-transfer-alt bg-primary"></i>
                <div class="text-truncate">Transactions</div>
            </a>
        </li>
        @if($canViewInvoices)
        <li class="menu-item">
            <a href="{{ url('invoice') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
                <div class="text-truncate">Invoices</div>
            </a>
        </li>
        @endif
        @if($canViewReceipts)
        <li class="menu-item">
            <a href="{{ url('receipt') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                <div class="text-truncate">Receipts</div>
            </a>
        </li>
        @endif

        <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item">
            <a href="{{ url('client') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bxs-user-detail text-info me-2"></i>
                <div class="text-truncate">Clients</div>
            </a>
        </li>

        @if($canViewAccounts)
        <li class="menu-header small text-uppercase"><span class="menu-header-text text-danger">Accounts</span></li>
        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-chart text-danger me-2"></i>
                <div class="text-truncate"><strong>Accounts</strong></div>
            </a>
            <ul class="menu-sub">
                <li @class(['menu-item', 'active' => request()->is('collections*')])>
                    <a href="{{ url('collections') }}" class="menu-link"><div class="text-truncate">Collections</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('deposit*')])>
                    <a href="{{ url('deposit') }}" class="menu-link"><div class="text-truncate">Bank Deposit</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('banks*')])>
                    <a href="{{ url('banks') }}" class="menu-link"><div class="text-truncate">Banks</div></a>
                </li>
            </ul>
        </li>
        <li class="menu-item">
            <a href="{{ url('expense') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-credit-card text-secondary me-2"></i>
                <div class="text-truncate">Expense</div>
            </a>
        </li>
        @endif

        @if($canViewPayroll)
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Payroll</span></li>
        <li class="menu-item">
            <a href="{{ url('salariesTransaction') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-money-withdraw text-primary me-2"></i>
                <div class="text-truncate">Payroll</div>
            </a>
        </li>
        @endif
    </ul>
</aside>
