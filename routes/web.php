<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceScanController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BankDepositController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\EmployeeBulkUpdateController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProformaClientController;
use App\Http\Controllers\ProformaController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\MultiInvoiceReceiptController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\PayPriorityRuleController;
use App\Http\Controllers\SalaryReportController;
use App\Http\Controllers\PaymentRunController;
use App\Http\Controllers\SalaryTopUpsController;
use App\Http\Controllers\SendMoneyController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use App\Models\employee;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('dashboard/{board}', [App\Http\Controllers\DashboardController::class, 'show'])
    ->whereIn('board', ['accounts', 'hr', 'operations'])->name('dashboard.show');
Route::get('staffAdd', [HomeController::class, 'staffCreate']);
Route::get('staff', [HomeController::class, 'showAllStaffs']);
Route::get('staff/{id}', [HomeController::class, 'show']);
Route::get('profile/{id}', [HomeController::class, 'staffProfile']);
Route::patch('staffProfileImage/{id}', [HomeController::class, 'staffProfileImage']);
Route::get('staff/{id}/edit', [HomeController::class, 'edit']);
Route::patch('staff/{id}', [HomeController::class, 'update']);
Route::post('staff', [HomeController::class, 'store']);
Route::patch('staffPassword/{id}', [HomeController::class, 'staffPasswordReset']);



Route::resource('departments', DepartmentController::class);
Route::resource('role', RoleController::class);
Route::resource('field', FieldController::class);
Route::resource('client', ClientController::class);
Route::post('clientAproval', [ClientController::class, 'clientAproval']);
Route::get('clientPending', [ClientController::class, 'clientPending'])->name('client.pending');
Route::get('clientTerminated', [ClientController::class, 'clientTerminated'])->name('client.terminated');

Route::get('clientStatement/{client}', [ClientController::class, 'statementOfAccount']);
Route::post('clientStatement/{client}/email', [\App\Http\Controllers\ClientAccountController::class, 'email'])->name('client.statement.email');
Route::get('clientStatement/{client}/pdf', [\App\Http\Controllers\ClientAccountController::class, 'pdf'])->name('client.statement.pdf');
Route::get('receivables/aged', [\App\Http\Controllers\ClientAccountController::class, 'aged'])->name('receivables.aged');
Route::get('receivables/aged/{client}', [\App\Http\Controllers\ClientAccountController::class, 'agedClient'])->name('receivables.aged.client');
Route::get('clientAttachGuards/{id}', [ClientController::class, 'clientAttachGuards']);
Route::post('client/{client}/attach-employees', [ClientController::class, 'attachEmployees'])->name('client.attachEmployees');
Route::post('client/{client}/detach-employees', [ClientController::class, 'detachEmployees'])->name('client.detachEmployees');
Route::get('terminateClient/{id}', [ClientController::class, 'terminateClient'])->name('clients.terminateClient');
Route::get('reinstateClient/{id}', [ClientController::class, 'clientReinstate'])->name('clients.clientReinstate');
// Guard attendance (operations). See docs/guard-attendance.md.
Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
Route::get('attendance/review', [AttendanceController::class, 'review'])->name('attendance.review');
Route::post('attendance/review/{attendance}', [AttendanceController::class, 'decide'])->whereNumber('attendance')->name('attendance.decide');
Route::get('attendance/{client}', [AttendanceController::class, 'show'])->whereNumber('client')->name('attendance.show');
Route::get('attendance/{client}/export', [AttendanceController::class, 'export'])->whereNumber('client')->name('attendance.export');
Route::get('attendance/{client}/poster', [AttendanceController::class, 'poster'])->whereNumber('client')->name('attendance.poster');
Route::get('attendance/{client}/kiosk', [AttendanceController::class, 'kiosk'])->whereNumber('client')->name('attendance.kiosk');
Route::post('attendance/{client}/kiosk', [AttendanceController::class, 'kioskMark'])->whereNumber('client')->name('attendance.kioskMark');
Route::get('attendance/{client}/id-cards', [AttendanceController::class, 'idCards'])->whereNumber('client')->name('attendance.idCards');
Route::post('attendance/{client}/move', [AttendanceController::class, 'move'])->whereNumber('client')->name('attendance.move');
Route::post('attendance/{client}/mark', [AttendanceController::class, 'mark'])->whereNumber('client')->name('attendance.mark');
Route::post('attendance/{client}/fill', [AttendanceController::class, 'fill'])->whereNumber('client')->name('attendance.fill');
Route::post('attendance/{client}/shift', [AttendanceController::class, 'setShift'])->whereNumber('client')->name('attendance.shift');
Route::post('attendance/{client}/plan', [AttendanceController::class, 'savePlan'])->whereNumber('client')->name('attendance.plan');
Route::post('attendance/{client}/site', [AttendanceController::class, 'saveSite'])->whereNumber('client')->name('attendance.site');
Route::post('attendance/{client}/token', [AttendanceController::class, 'regenerateToken'])->whereNumber('client')->name('attendance.token');
Route::post('attendance/{client}/pins', [AttendanceController::class, 'issuePins'])->whereNumber('client')->name('attendance.pins');
Route::post('attendance/{client}/guard/{employee}/pin', [AttendanceController::class, 'resetPin'])->whereNumber(['client', 'employee'])->name('attendance.pin');
Route::post('attendance/{client}/guard/{employee}/forget-phone', [AttendanceController::class, 'forgetPhone'])->whereNumber(['client', 'employee'])->name('attendance.forgetPhone');
// The page the QR poster opens: no login (FWSS number + PIN), no CSRF (no session is used).
Route::get('attend/{token}', [AttendanceScanController::class, 'show'])->middleware('throttle:60,1')->name('attend.show');
Route::post('attend/{token}', [AttendanceScanController::class, 'store'])->middleware('throttle:20,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, VerifyCsrfToken::class])->name('attend.store');

Route::resource('category', CategoryController::class); 
Route::post('categoryAssign', [CategoryController::class, 'categoryAssign']); 
Route::post('categoryReAssign', [CategoryController::class, 'categoryReAssign']); 
Route::get('categorySearch', [CategoryController::class, 'categorySearch']); 
Route::get('categoryClients', [CategoryController::class, 'activeClientsByMonth'])->name('category.activeClientsByMonth');
Route::get('categoryClients/data', [CategoryController::class, 'clientsData'])->name('category.clientsData');
Route::get('categoryClients/ids', [CategoryController::class, 'clientIds'])->name('category.clientsIds');
Route::get('categoryClients/{client}/employees', [CategoryController::class, 'clientEmployees'])->whereNumber('client')->name('category.clientEmployees');
Route::post('categoryBulkAssign', [CategoryController::class, 'bulkAssign'])->name('category.bulkAssign');
Route::post('categoryFieldAssign', [CategoryController::class, 'fieldAssign'])->name('category.fieldAssign');

Route::resource('service', ServiceController::class);


Route::resource('transaction', TransactionController::class);
Route::get('invoice/export', [InvoiceController::class, 'export'])->name('invoice.export');
Route::get('invoice-report', [InvoiceController::class, 'report'])->name('invoice.report');
Route::get('invoice/field/{field}/details', [InvoiceController::class, 'fieldDetails']);
Route::get('invoice/client/{client}/details', [InvoiceController::class, 'clientDetails']);
Route::get('invoice/issuer/{issuer}/details', [InvoiceController::class, 'issuerDetails']);
Route::get('invoice/data', [InvoiceController::class, 'datatable'])->name('invoice.data');
Route::resource('invoice', InvoiceController::class)->middleware('once');
Route::get('invoice/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoice.duplicate');
Route::post('invoice/{invoice}/duplicate', [InvoiceController::class, 'storeDuplicate'])->name('invoice.storeDuplicate')->middleware('once');
Route::resource('proforma', ProformaController::class);
Route::resource('proformaClient', ProformaClientController::class);
Route::get('generate/{id}', [InvoiceController::class, 'generate']);
Route::get('printInvoice/{id}', [InvoiceController::class, 'printInvoice']);
Route::get('printProforma/{id}', [ProformaController::class, 'printProforma']);
Route::get('invoiceDashboard', [InvoiceController::class, 'dashboardViewAllInvoices']);
Route::get('invoiceOutstanding', [InvoiceController::class, 'dashboardInvoiceWithOutstanding']);
Route::get('partPaymentOutstanding', [InvoiceController::class, 'dashboardPartPaymentOutstanding']);
Route::get('invoiceSearch', [InvoiceController::class, 'invoiceSearch'])->name('invoice.invoiceSearch');
Route::get('searchOutstandingInvoices', [InvoiceController::class, 'searchOutstandingInvoices'])->name('invoice.searchOutstandingInvoices');
Route::get('searchPartPaymentOutstanding', [InvoiceController::class, 'searchPartPaymentOutstanding'])->name('invoice.searchPartPaymentOutstanding');

Route::get('receipt/export', [ReceiptController::class, 'export'])->name('receipt.export');
Route::get('receipt/data', [ReceiptController::class, 'datatable'])->name('receipt.data');
Route::get('receipt-report', [ReceiptController::class, 'report'])->name('receipt.report');
Route::get('receipt/field/{field}/details', [ReceiptController::class, 'fieldDetails']);
Route::get('receipt/client/{client}/details', [ReceiptController::class, 'clientDetails']);
Route::get('receipt/collector/{collector}/details', [ReceiptController::class, 'collectorDetails']);
// One receipt paying several invoices, and applying a receipt's leftover credit later.
Route::get('receipt-multi/create', [MultiInvoiceReceiptController::class, 'create'])->name('receipt.multi.create');
Route::post('receipt-multi', [MultiInvoiceReceiptController::class, 'store'])->name('receipt.multi.store')->middleware('once');
Route::post('receipt/{receipt}/apply-credit', [MultiInvoiceReceiptController::class, 'applyCredit'])->name('receipt.applyCredit')->middleware('once');
Route::get('receipt-voided', [ReceiptController::class, 'voided'])->name('receipt.voided');
Route::resource('receipt', ReceiptController::class)->middleware('once');
Route::get('receiptPending', [ReceiptController::class, 'PendingReceipts'])->name('receipt.pending');
Route::post('receiptChannels', [ReceiptController::class, 'receiptChannels']);

Route::get('receiptAllpayment', [ReceiptController::class, 'dashboardAllPayment']);
Route::get('receiptCashPayment', [ReceiptController::class, 'dashboardCashPayment']);
Route::get('receiptMoMoPayment', [ReceiptController::class, 'dashboardMoMoPayment']);
Route::get('receiptChequePayment', [ReceiptController::class, 'dashboardChequePayment']);
Route::get('receiptTransferPayment', [ReceiptController::class, 'dashboardTransferPayment']);
Route::get('receiptWHTPayment', [ReceiptController::class, 'dashboardWHTPayment']);
// Route::get('receiptWHT', [ReceiptController::class, 'dashboardWHTDeducted']);
Route::get('receiptCreate/{id}', [ReceiptController::class, 'receiptCreate']);
Route::get('searchReceiptsWHTPayment', [ReceiptController::class, 'searchReceiptsWHTPayment'])->name('receipt.searchReceiptsWHTPayment');
Route::get('receiptSearch', [ReceiptController::class, 'receiptSearch'])->name('receipt.receiptSearch');
Route::get('receiptListSearch', [ReceiptController::class, 'receiptListSearch'])->name('receipt.receiptListSearch');
// Route::get('receiptPendingSearch', [ReceiptController::class, 'receiptPendingSearch'])->name('receipt.receiptPendingSearch');

Route::resource('collections', CollectionController::class);

Route::resource('deposit', BankDepositController::class);

Route::resource('banks', BankController::class);

Route::get('/expense/list', [ExpenseController::class, 'list'])->name('expense.list');
Route::post('/expense/{expense}/approve', [ExpenseController::class, 'approve'])->name('expense.approve');
Route::post('/expense/{expense}/reject', [ExpenseController::class, 'reject'])->name('expense.reject');
Route::resource('expense', ExpenseController::class);
// Route::get('expenseCreate/{field_id}', [ExpenseController::class, 'CreateExpense'])->name('expense.CreateExpense');


Route::post('overtime/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
Route::post('overtime/{overtime}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');
Route::get('overtime-report', [OvertimeController::class, 'report'])->name('overtime.report');
Route::get('overtime/field/{field}/details', [OvertimeController::class, 'fieldDetails']);
Route::get('overtime/reason/{reason}/details', [OvertimeController::class, 'reasonDetails']);
Route::get('overtime/client/{client}/details', [OvertimeController::class, 'clientDetails']);
Route::get('overtime/employee/{employee}/details', [OvertimeController::class, 'employeeDetails']);
// select2 search endpoints (JSON), reused by both the create and edit rows
Route::get('overtime/lookup/employees', [OvertimeController::class, 'employeeOptions'])->name('overtime.lookup.employees');
Route::get('overtime/lookup/clients', [OvertimeController::class, 'clientOptions'])->name('overtime.lookup.clients');
Route::get('overtime/lookup/client-guards', [OvertimeController::class, 'clientGuards'])->name('overtime.lookup.clientGuards');
Route::get('overtime/lookup/available-guards', [OvertimeController::class, 'availableGuards'])->name('overtime.lookup.availableGuards');
// Opens the daily overtime page on the entry's date and field, scrolled to the entry (used by the attendance sheet).
Route::get('overtime/{id}/open', [OvertimeController::class, 'open'])->whereNumber('id')->name('overtime.open');
Route::resource('overtime', OvertimeController::class);

Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
Route::get('employees/data', [EmployeeController::class, 'datatable'])->name('employees.data');
// Bulk employee upload (must stay above Route::resource('employees')).
Route::get('employees/import', [EmployeeImportController::class, 'index'])->name('employees.import');
Route::get('employees/import/template', [EmployeeImportController::class, 'template'])->name('employees.import.template');
Route::post('employees/import/preview', [EmployeeImportController::class, 'preview'])->name('employees.import.preview');
Route::get('employees/import/{import}', [EmployeeImportController::class, 'show'])->whereNumber('import')->name('employees.import.show');
Route::post('employees/import/{import}/confirm', [EmployeeImportController::class, 'confirm'])->whereNumber('import')->name('employees.import.confirm');
Route::get('employees/import/{import}/errors', [EmployeeImportController::class, 'errors'])->whereNumber('import')->name('employees.import.errors');
// Bulk employee update (must stay above Route::resource('employees')).
Route::get('employees/bulk-update', [EmployeeBulkUpdateController::class, 'index'])->name('employees.bulk-update');
Route::get('employees/bulk-update/template', [EmployeeBulkUpdateController::class, 'template'])->name('employees.bulk-update.template');
Route::post('employees/bulk-update/preview', [EmployeeBulkUpdateController::class, 'preview'])->name('employees.bulk-update.preview');
Route::get('employees/bulk-update/{import}', [EmployeeBulkUpdateController::class, 'show'])->whereNumber('import')->name('employees.bulk-update.show');
Route::post('employees/bulk-update/{import}/confirm', [EmployeeBulkUpdateController::class, 'confirm'])->whereNumber('import')->name('employees.bulk-update.confirm');
Route::get('employees/bulk-update/{import}/errors', [EmployeeBulkUpdateController::class, 'errors'])->whereNumber('import')->name('employees.bulk-update.errors');
Route::resource('employees', EmployeeController::class); 
Route::get('employee-report', [EmployeeController::class, 'report'])->name('employee.report');
Route::get('employeesBank', [EmployeeController::class, 'employeesBank'])->name('employees.Bank');
Route::get('employeesBankView/{id}', [EmployeeController::class, 'employeesBankView' ]);
Route::get('employeesCash', [EmployeeController::class, 'employeesCash'])->name('employees.Cash');
Route::get('employeesCashView/{id}', [EmployeeController::class, 'employeesCashView' ]);
Route::post('employeesCashVerify', [EmployeeController::class, 'employeesCashVerify' ]);
// HR movements report (replaces the NRRIT page; the old URLs open it).
Route::get('employees-movements', [\App\Http\Controllers\EmployeeMovementController::class, 'index'])->name('employees.movements');
Route::get('employees-movements/export', [\App\Http\Controllers\EmployeeMovementController::class, 'export'])->name('employees.movements.export');
Route::put('employees-movements/{movement}', [\App\Http\Controllers\EmployeeMovementController::class, 'update'])->whereNumber('movement')->name('employees.movements.update');
Route::post('employees-movements/{movement}/undo', [\App\Http\Controllers\EmployeeMovementController::class, 'undo'])->whereNumber('movement')->name('employees.movements.undo');
Route::get('employeesnrrit', [\App\Http\Controllers\EmployeeMovementController::class, 'legacy']);
Route::get('employeesnrritView', [\App\Http\Controllers\EmployeeMovementController::class, 'legacy']);
Route::get('employeesPending', [EmployeeController::class, 'employeesPending']);
Route::post('employeesAproval', [EmployeeController::class, 'employeesAproval']);


Route::get('employeesPayInfo/{id}', [EmployeeController::class, 'EmpPayInfo'])->name('employees.PayInfo');
Route::get('employeesViewPayInfo/{id}', [EmployeeController::class, 'EmpViewPayInfo'])->name('employees.ViewPayInfo');
Route::put('employeesPayInfoUpdate/{id}', [EmployeeController::class, 'EmpPayInfoUpdate']);
Route::get('employeesSalary/{id}', [EmployeeController::class, 'EmpSalary']);
Route::get('employeesViewSalaryInfo', [EmployeeController::class, 'EmpViewSalaryInfo']);
Route::get('employeesGuardClient/{id}', [EmployeeController::class, 'GuardClient']);
Route::post('terminateEmployee/{id}', [EmployeeController::class, 'terminateEmployee'])->whereNumber('id')->name('employees.terminateEmployee');
Route::post('employeeReinstate/{id}', [EmployeeController::class, 'employeeReinstate'])->whereNumber('id')->name('employees.reinstate');
Route::post('employeesGuardReAassign', [EmployeeController::class, 'GuardReAassign'])->name('employees.GuardReAassign');

Route::get('salaries/employees-data', [SalaryController::class, 'employeesData'])->name('salaries.employeesData');
Route::get('salaries-report', [SalaryReportController::class, 'index'])->name('salaries.report');
Route::get('salary-payments', [PaymentRunController::class, 'index'])->name('salary-payments.index');
Route::get('salary-payments/cell', [PaymentRunController::class, 'cell'])->name('salary-payments.cell');
Route::get('salary-payments/export', [PaymentRunController::class, 'export'])->name('salary-payments.export');
Route::post('salary-payments/runs', [PaymentRunController::class, 'start'])->name('salary-payments.start');
Route::put('salary-payments/runs/{run}', [PaymentRunController::class, 'update'])->whereNumber('run')->name('salary-payments.update');
Route::post('salary-payments/runs/{run}/close', [PaymentRunController::class, 'close'])->whereNumber('run')->name('salary-payments.close');
Route::post('salary-payments/differences', [PaymentRunController::class, 'payDifferences'])->name('salary-payments.differences');
Route::get('salaries-month/data', [SalaryController::class, 'salariesMonthData'])->name('salaries.salariesMonthData');
Route::get('salaries-month/export', [SalaryController::class, 'salariesMonthExport'])->name('salaries.salariesMonthExport');
// Pay priority rules (Finance Manager; see PayPriorityRuleController)
Route::get('pay-priority', [PayPriorityRuleController::class, 'index'])->name('pay-priority.index');
Route::post('pay-priority/preview', [PayPriorityRuleController::class, 'preview'])->name('pay-priority.preview');
Route::post('pay-priority/rules', [PayPriorityRuleController::class, 'store'])->name('pay-priority.store');
Route::put('pay-priority/rules/{rule}', [PayPriorityRuleController::class, 'update'])->whereNumber('rule')->name('pay-priority.update');
Route::post('pay-priority/rules/{rule}/toggle', [PayPriorityRuleController::class, 'toggle'])->whereNumber('rule')->name('pay-priority.toggle');
Route::delete('pay-priority/rules/{rule}', [PayPriorityRuleController::class, 'destroy'])->whereNumber('rule')->name('pay-priority.destroy');
Route::post('pay-priority/aliases/preview', [PayPriorityRuleController::class, 'aliasPreview'])->name('pay-priority.aliases.preview');
Route::post('pay-priority/aliases', [PayPriorityRuleController::class, 'aliasStore'])->name('pay-priority.aliases.store');
Route::delete('pay-priority/aliases/{alias}', [PayPriorityRuleController::class, 'aliasDestroy'])->whereNumber('alias')->name('pay-priority.aliases.destroy');
Route::post('pay-priority/recompute', [PayPriorityRuleController::class, 'recompute'])->name('pay-priority.recompute');
// Absent deduction from guard attendance (Finance Manager; see AbsentDeductionController)
Route::get('absent-deductions', [App\Http\Controllers\AbsentDeductionController::class, 'index'])->name('absent-deductions.index');
Route::post('absent-deductions/settings', [App\Http\Controllers\AbsentDeductionController::class, 'saveSettings'])->name('absent-deductions.settings');
Route::post('absent-deductions/apply', [App\Http\Controllers\AbsentDeductionController::class, 'apply'])->name('absent-deductions.apply');
Route::resource('salaries', SalaryController::class);
Route::resource('salariestopups', SalaryTopUpsController::class);
Route::get('salariesCreate', [SalaryController::class, 'CreateSalaries']);
Route::get('printPayslip/{id}', [SalaryController::class, 'printPayslip']);
Route::post('salariesDeleteMultiple', [SalaryController::class, 'deleteMultiple'])->name('salaries.deletMultiple');
Route::get('salariesTransaction', [SalaryController::class, 'transactionSalary']);

Route::post('salariesBulkCash', [SalaryController::class, 'BulkCashStore']);
Route::get('salariesBulkCash', [SalaryController::class, 'BulkCash'] );
Route::get('salariesBulkCashm', [SalaryController::class, 'BulkCashMonth']);
Route::get('salariesBulkHisory', [SalaryController::class, 'BulkCashMonthHistory']);

Route::get('salariesBankMonth/{bank_id}/{month}', [SalaryController::class, 'bankMonth'])->name('salaries.bankMonth');
Route::get('salariesBankHoldMonth/{bank_id}/{month}', [SalaryController::class, 'bankholdMonth'])->name('salaries.bankholdMonth');
Route::get('salariesCashMonth/{field_id}/{month}', [SalaryController::class, 'cashMonth'])->name('salaries.cashMonth');
Route::get('salariesCashHoldMonth/{field_id}/{month}', [SalaryController::class, 'cashholdMonth'])->name('salaries.cashholdMonth');
Route::get('salariesTaxMonth/{field_id}/{month}', [SalaryController::class, 'TaxMonth'] );
Route::get('salariesPensionMonth/{field_id}/{month}',  [SalaryController::class, 'PensionMonth']);
Route::get('salariesOvertimeMonth/{field_id}/{month}',  [SalaryController::class, 'OvertimeMonth']);
Route::get('salariesIouMonth/{field_id}/{month}',  [SalaryController::class, 'IouMonth']);
Route::get('salariesBootMonth/{field_id}/{month}',  [SalaryController::class, 'BootMonth']);
Route::get('salariesAbsentMonth/{field_id}/{month}',  [SalaryController::class, 'AbsentMonth']);
Route::get('salariessDateMonth/{field_id}/{month}',  [SalaryController::class, 'sDateMonth']);
Route::get('salariesOdedMonth/{field_id}/{month}',  [SalaryController::class, 'oDedMonth']);
Route::get('salariesReprimandMonth/{field_id}/{month}',  [SalaryController::class, 'reprimandMonth']);
Route::get('salariesLoanMonth/{field_id}/{month}',  [SalaryController::class, 'loanMonth']);
Route::get('salariesClientMonth/{client_id}/{month}',  [SalaryController::class, 'ClientMonth']);
Route::get('salariesClientHoldMonth/{client_id}/{month}',  [SalaryController::class, 'ClientHoldMonth']);

Route::get('salariesMonth', [SalaryController::class, 'salariesMonth'])->name('salaries.salariesMonth');
Route::get('salariesInvPayroll', [SalaryController::class, 'InvToParoll']);
Route::get('invToPayroll', [SalaryController::class, 'InvToParollMonth'])->name('salaries.invToPayroll');
Route::post('uploadSalaries', [SalaryController::class, 'uploadSalaries'])->name('salaries.upload');
Route::get('invToPayrollInvoice/{client_id}/{month}', [InvoiceController::class, 'PayrollInvoice']);
Route::get('invToPayrollGuards/{client_id}/{month}', [SalaryController::class, 'PayrollGuards']);



Route::get('sendMoney', [SendMoneyController::class, 'sendMoney']);
Route::get('sendMoneyCheck/{ref}', [SendMoneyController::class, 'statusCheck']);
Route::post('payCashSalary', [SendMoneyController::class, 'payCashSalary']);
// Route::post('sendMoneyCallback', function(){});
// Route::post('/webhook-endpoint', function () { })->withoutMiddleware([VerifyCsrfToken::class]); 

Route::get('sendMoneyCallback', [SendMoneyController::class, 'sendMoneyCallback'])->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('exportMaster/{month}', [SalaryController::class, 'exportMaster']);
Route::get('exportBank/{month}/{bank_id}', [SalaryController::class, 'exportBank']);
Route::get('exportCategory/{month}/{category}', [SalaryController::class, 'exportCategory']);

// Route::get('clientAttachGuards', function(){
// //    ATTACHING CLIENTS
//     $clientId = 111;
//     $employee = employee::findOrFail(2238);
//     $response = $employee->clients()->attach($clientId);
//     dd($response);
//     return "You are Testing MANY TO MANY / " . $response;

// // RETRIEVING CLIENTS 
//     $employee = employee::findOrFail(2238);
//     $response = $employee->clients;
//     dd($response);
//     // foreach ($response as $key => $value) {
//     //     # code...
//     //     echo $value->name. " / ". $value->business_name ;
//     // }
// });