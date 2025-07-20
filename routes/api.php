<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth',  'role:admin'])->group(function () {
    Route::get('SoldroductAPI',[\App\Http\Controllers\SellDtlsController::class,'SoldProductAPI']);
    Route::post('NewCustomer', [\App\Http\Controllers\CustomerController::class, 'store'])->name('NewCustomer');
    Route::put('UpdateCustomer/{customerId}', [\App\Http\Controllers\CustomerController::class, 'UpdateCustomer']);
    Route::get('customer/{customerId}/ledger', [\App\Http\Controllers\CustomerController::class, 'ledger']);
    Route::get('customer/{customerId}', [\App\Http\Controllers\CustomerController::class, 'CustomerById']);
    Route::get('customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers');
    Route::get('TotalDue', [\App\Http\Controllers\CustomerController::class, 'TotalDue']);
    Route::get('Product',[\App\Http\Controllers\ProductController::class,'index']);
    Route::get('AllProduct',[\App\Http\Controllers\ProductController::class,'AllProduct']);

    Route::get('ProductValue',[\App\Http\Controllers\ProductController::class,'ProductValue']);
    Route::post('AddProduct',[\App\Http\Controllers\ProductController::class,'Create']);
    Route::get('ProductCat',[\App\Http\Controllers\ProductCat::class,'index'])->name('ProductCat');
    Route::post('AddProductCat',[\App\Http\Controllers\ProductCat::class,'Create'])->name('AddProductCat');
    Route::get('ProductBrand',[\App\Http\Controllers\BrandController::class,'index']) ->name('ProductBrand');
    Route::post('AddProductBrand',[\App\Http\Controllers\BrandController::class,'Create'])  ->name('AddProductBrand');
    Route::get('ProductUnit',[\App\Http\Controllers\ProductUnitController::class,'index']);
    Route::post('AddProductUnit',[\App\Http\Controllers\ProductUnitController::class,'Create']);

    Route::get('VendorList',[\App\Http\Controllers\VendorController::class,'index']);
    Route::post('NewVendor',[\App\Http\Controllers\VendorController::class,'Create']);
    Route::get('TotalDebt', [\App\Http\Controllers\VendorController::class, 'TotalDebt']);
    Route::put('UpdateVendor/{vendorId}', [\App\Http\Controllers\VendorController::class, 'UpdateVendor']);
    Route::get('vendor/{vendorId}/ledger', [\App\Http\Controllers\VendorController::class, 'ledger']);
    Route::get('vendor/{vendorId}', [\App\Http\Controllers\VendorController::class, 'VendorById']);

    Route::get('PurchaseExpList',[\App\Http\Controllers\PurchaseExpenseListController::class,'index']);
    Route::post('AddPurchaseExpList',[\App\Http\Controllers\PurchaseExpenseListController::class,'Create']);

    Route::post('Purchase',[\App\Http\Controllers\PurchaseMemoController::class,'store']);
    Route::post('DebtPay',[\App\Http\Controllers\PurchaseMemoController::class,'Debt']);
    Route::get('PurchaseMemoDetails/{PurchaseMemoID}',[\App\Http\Controllers\PurchaseMemoController::class,'getPurMemoDetails']);
    Route::get('GetPurMemo/{Date}',[\App\Http\Controllers\PurchaseMemoController::class,'getPurMemo']);
    Route::get('GetPurPaid/{Date}',[\App\Http\Controllers\PurchaseMemoController::class,'getPurPaid']);

    Route::post('sell',[\App\Http\Controllers\SellDtlsController::class,'store']);
    Route::post('DuePay',[\App\Http\Controllers\SellDtlsController::class,'Due']);
    Route::get('TotalBillByDate/{date}',[\App\Http\Controllers\SellDtlsController::class,'TotalBillByDate']);
    Route::get('TotalPayByDate/{date}',[\App\Http\Controllers\SellDtlsController::class,'TotalPayByDate']);
    Route::get('SoldProduct/{Date}',[\App\Http\Controllers\SellDtlsController::class,'soldProductsByDate']);
    Route::get('GetMemo/{Date}',[\App\Http\Controllers\SellDtlsController::class,'getSellMemo']);
    Route::get('GetMemoPaid/{Date}',[\App\Http\Controllers\SellDtlsController::class,'getSellPaid']);
    Route::get('sellMemoDetails/{sellMemoID}',[\App\Http\Controllers\SellDtlsController::class,'getSellMemoDetails']);

    Route::get('Account',[\App\Http\Controllers\AccountController::class,'index']);
    Route::post('NewAccount',[\App\Http\Controllers\AccountController::class,'store']);
    Route::post('BalanceTransfer',[\App\Http\Controllers\AccountController::class,'transfer']);
    Route::post('CashDeclare',[\App\Http\Controllers\AccountController::class,'Declare']);

    Route::get('ExpAccount',[\App\Http\Controllers\ExpenseController::class,'ExpIndex']);
    Route::post('AddExpAccount',[\App\Http\Controllers\ExpenseController::class,'ExpStore']);
    Route::get('ExpView',[\App\Http\Controllers\ExpenseController::class,'ExpListIndex']);
    Route::post('AddExp',[\App\Http\Controllers\ExpenseController::class,'ExpListStore']);
    Route::get('TotalExpByDate/{date}',[\App\Http\Controllers\ExpenseController::class,'TotalExpByDate']);
    Route::get('BalanceSheet',[\App\Http\Controllers\BalanceSheetController::class,'index']);
});


Route::middleware(['auth',  'role:salesman'])->group(function () {
    Route::post('sell',[\App\Http\Controllers\SellDtlsController::class,'store']);
    Route::post('DuePay',[\App\Http\Controllers\SellDtlsController::class,'Due']);
    Route::get('TotalBillByDate/{date}',[\App\Http\Controllers\SellDtlsController::class,'TotalBillByDate']);
    Route::get('TotalPayByDate/{date}',[\App\Http\Controllers\SellDtlsController::class,'TotalPayByDate']);
    Route::get('SoldProduct/{Date}',[\App\Http\Controllers\SellDtlsController::class,'soldProductsByDate']);
    Route::get('GetMemo/{Date}',[\App\Http\Controllers\SellDtlsController::class,'getSellMemo']);
    Route::get('GetMemoPaid/{Date}',[\App\Http\Controllers\SellDtlsController::class,'getSellPaid']);
    Route::get('sellMemoDetails/{sellMemoID}',[\App\Http\Controllers\SellDtlsController::class,'getSellMemoDetails']);

    Route::get('customer/{customerId}/ledger', [\App\Http\Controllers\CustomerController::class, 'ledger']);
    Route::get('customer/{customerId}', [\App\Http\Controllers\CustomerController::class, 'CustomerById']);
    Route::get('customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers');
    Route::get('TotalDue', [\App\Http\Controllers\CustomerController::class, 'TotalDue']);
    Route::get('Product',[\App\Http\Controllers\ProductController::class,'index']);
    Route::get('AllProduct',[\App\Http\Controllers\ProductController::class,'AllProduct']);
    Route::get('SoldroductAPI',[\App\Http\Controllers\SellDtlsController::class,'SoldProductAPI']);
    Route::get('TotalExpByDate/{date}',[\App\Http\Controllers\ExpenseController::class,'TotalExpByDate']);
    Route::get('BalanceSheet',[\App\Http\Controllers\BalanceSheetController::class,'index']);

    Route::get('ProductValue',[\App\Http\Controllers\ProductController::class,'ProductValue']);

    Route::get('ProductCat',[\App\Http\Controllers\ProductCat::class,'index'])->name('ProductCat');
    Route::get('ProductBrand',[\App\Http\Controllers\BrandController::class,'index']) ->name('ProductBrand');
    Route::get('ProductUnit',[\App\Http\Controllers\ProductUnitController::class,'index']);


});

