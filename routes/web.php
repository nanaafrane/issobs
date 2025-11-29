<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\BankDepositController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('staffAdd', [HomeController::class, 'staffCreate']);
Route::get('staff', [HomeController::class, 'showAllStaffs']);
Route::get('staff/{id}', [HomeController::class, 'show']);
Route::get('profile/{id}', [HomeController::class, 'staffProfile']);
Route::patch('staffProfileImage/{id}', [HomeController::class, 'staffProfileImage']);
Route::get('staff/{id}/edit', [HomeController::class, 'edit']);
Route::patch('staff/{id}', [HomeController::class, 'update']);
Route::post('staff', [HomeController::class, 'store']);
Route::patch('staffPassword/{id}', [HomeController::class, 'staffPasswordReset']);



Route::resource('department', DepartmentController::class);
Route::resource('role', RoleController::class);
Route::resource('field', FieldController::class);
Route::resource('client', ClientController::class);
Route::resource('service', ServiceController::class);

// Route::get('invoice/list', [InvoiceController::class, 'list']);
Route::resource('transaction', TransactionController::class);

Route::resource('invoice', InvoiceController::class);
Route::get('generate/{id}', [InvoiceController::class, 'generate']);
Route::get('printInvoice/{id}', [InvoiceController::class, 'printInvoice']);
Route::get('invoiceDashboard', [InvoiceController::class, 'dashboardViewAllInvoices']);
Route::get('invoiceOutstanding', [InvoiceController::class, 'dashboardInvoiceWithOutstanding']);
Route::get('partPaymentOutstanding', [InvoiceController::class, 'dashboardPartPaymentOutstanding']);

Route::resource('receipt', ReceiptController::class);
Route::get('receiptAllpayment', [ReceiptController::class, 'dashboardAllPayment']);
Route::get('receiptCashPayment', [ReceiptController::class, 'dashboardCashPayment']);
Route::get('receiptMoMoPayment', [ReceiptController::class, 'dashboardMoMoPayment']);
Route::get('receiptChequePayment', [ReceiptController::class, 'dashboardChequePayment']);
Route::get('receiptTransferPayment', [ReceiptController::class, 'dashboardTransferPayment']);
Route::get('receiptWHTPayment', [ReceiptController::class, 'dashboardWHTPayment']);
// Route::get('receiptWHT', [ReceiptController::class, 'dashboardWHTDeducted']);
Route::get('receiptCreate/{id}', [ReceiptController::class, 'receiptCreate']);

Route::resource('collections', CollectionController::class);

Route::resource('deposit', BankDepositController::class);

Route::resource('banks', BankController::class);

Route::resource('expense', ExpenseController::class);

Route::resource('employees', EmployeeController::class); 
Route::get('employeesPayInfo', [EmployeeController::class, 'EmpPayInfo']);
Route::get('employeesSalaryInfo', [EmployeeController::class, 'EmpSalaryInfo']);

Route::resource('salaries', SalaryController::class);
Route::get('salariesTransaction', [SalaryController::class, 'transactionSalary']);
Route::get('salariesInvPayroll', [SalaryController::class, 'InvToParoll']);