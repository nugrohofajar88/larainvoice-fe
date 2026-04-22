<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MachineOrderController;
use App\Http\Controllers\Master\BranchController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\MachineController;
use App\Http\Controllers\Master\PlateController;
use App\Http\Controllers\Master\SalesController;
use App\Http\Controllers\Master\PlateSizeController;
use App\Http\Controllers\Master\PlateMaterialController;
use App\Http\Controllers\Master\MachineTypeController;
use App\Http\Controllers\Master\MenuController;
use App\Http\Controllers\Master\CuttingPriceController;
use App\Http\Controllers\Master\ComponentCategoryController;
use App\Http\Controllers\Master\ComponentController;
use App\Http\Controllers\Master\CostTypeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Report\CustomerRankingController;
use App\Http\Controllers\Report\SalesKpiController;
use App\Http\Controllers\Report\InvoiceRecapController;
use App\Http\Controllers\Report\PaymentRecapController;
use App\Http\Controllers\Report\ReceivableController;
use App\Http\Controllers\Report\StockController;
use App\Http\Controllers\Report\PlateSalesRecapController;
use App\Http\Controllers\Report\CuttingSalesRecapController;

// ============================================================
// AUTH
// ============================================================
Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

// ============================================================
// AUTHENTICATED ROUTES
// ============================================================
Route::middleware(['auth.check'])->prefix('laporan/export')->name('laporan.export.')->group(function () {
    Route::get('ranking-pelanggan', [CustomerRankingController::class, 'export'])->name('ranking-pelanggan');
    Route::get('kpi-sales', [SalesKpiController::class, 'export'])->name('kpi-sales');
    Route::get('rekap-invoice', [InvoiceRecapController::class, 'export'])->name('rekap-invoice');
    Route::get('rekap-pembayaran', [PaymentRecapController::class, 'export'])->name('rekap-pembayaran');
    Route::get('rekap-penjualan-plat', [PlateSalesRecapController::class, 'export'])->name('rekap-penjualan-plat');
    Route::get('rekap-penjualan-jasa-cutting', [CuttingSalesRecapController::class, 'export'])->name('rekap-penjualan-jasa-cutting');
    Route::get('piutang', [ReceivableController::class, 'export'])->name('piutang');
    Route::get('stok', [StockController::class, 'export'])->name('stok');
});

Route::middleware(['auth.check', 'permission'])->group(function () {

    // Root redirect
    Route::get('/', fn() => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Master Data ─────────────────────────────────────────
    Route::prefix('master')->name('master.')->group(function () {

        // Cabang
        Route::resource('cabang',       BranchController::class)->names('cabang');

        // Pengguna
        Route::resource('pengguna',     UserController::class)->names('pengguna');

        // Role
        Route::resource('role',         RoleController::class)->names('role');

        // Pelanggan
        Route::resource('pelanggan',    CustomerController::class)->names('pelanggan');

        // Sales
        Route::resource('sales',        SalesController::class)->names('sales');

        // Mesin
        Route::get('mesin/{id}/files/{fileId}', [MachineController::class, 'downloadFile'])->name('mesin.files.download');
        Route::delete('mesin/{id}/files/{fileId}', [MachineController::class, 'destroyFile'])->name('mesin.files.destroy');
        Route::resource('mesin',        MachineController::class)->names('mesin');

        // Plat (Varian)
        Route::get('plat/multi',        [PlateController::class, 'getMulti'])->name('plat.multi');
        Route::put('plat/batch',        [PlateController::class, 'batchUpdate'])->name('plat.batch');
        Route::resource('plat',         PlateController::class)->names('plat');

        // Pengaturan / Master Lainnya
        Route::resource('plat-size',    PlateSizeController::class)->names('plat-size');
        Route::resource('plat-material',PlateMaterialController::class)->names('plat-material');
        Route::resource('machine-type', MachineTypeController::class)->names('machine-type');
        Route::resource('menu',         MenuController::class)->names('menu');
        Route::resource('component-category', ComponentCategoryController::class)->names('component-category');
        Route::resource('cost-type', CostTypeController::class)->only(['index', 'store', 'update', 'destroy'])->names('cost-type');
        Route::resource('component', ComponentController::class)->names('component');

        // Harga Cutting
        Route::get('harga-cutting/multi',   [CuttingPriceController::class, 'getMulti'])->name('harga-cutting.multi');
        Route::put('harga-cutting/batch',   [CuttingPriceController::class, 'batchUpdate'])->name('harga-cutting.batch');
        Route::get('harga-cutting',     [CuttingPriceController::class, 'index'])->name('harga-cutting.index');
        Route::post('harga-cutting',    [CuttingPriceController::class, 'store'])->name('harga-cutting.store');
    });

    // ── Invoice ──────────────────────────────────────────────
    Route::prefix('transaksi/invoice')->name('invoice.')->group(function () {
        Route::get('/',           [InvoiceController::class, 'index'])->name('index');
        Route::get('/buat',       [InvoiceController::class, 'create'])->name('create');
        Route::get('/master-data', [InvoiceController::class, 'masterData'])->name('master-data');
        Route::post('/',          [InvoiceController::class, 'store'])->name('store');
        Route::get('/{id}',       [InvoiceController::class, 'show'])->name('show');
        Route::get('/{id}/print', [InvoiceController::class, 'printInvoice'])->name('print');
        Route::get('/{id}/spk',   [InvoiceController::class, 'printSpk'])->name('spk');
        Route::get('/{id}/edit',  [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{id}',    [InvoiceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('transaksi/machine-order')->name('machine-order.')->group(function () {
        Route::get('/', [MachineOrderController::class, 'index'])->name('index');
        Route::get('/master-data', [MachineOrderController::class, 'masterData'])->name('master-data');
        Route::get('/{id}', [MachineOrderController::class, 'show'])->name('show');
        Route::post('/', [MachineOrderController::class, 'store'])->name('store');
        Route::put('/{id}', [MachineOrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [MachineOrderController::class, 'destroy'])->name('destroy');
    });

    // ── Produksi ─────────────────────────────────────────────
    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/',              [ProductionController::class, 'index'])->name('index');
        Route::get('/{id}/detail',   [ProductionController::class, 'detail'])->name('detail');
        Route::put('/{id}/status',   [ProductionController::class, 'updateStatus'])->name('update-status');
        Route::get('/{id}/upload',   [ProductionController::class, 'uploadForm'])->name('upload');
        Route::post('/{id}/upload',  [ProductionController::class, 'uploadFiles'])->name('upload.store');
        Route::get('/{id}/files/{fileId}', [ProductionController::class, 'downloadFile'])->name('files.download');
    });

    Route::prefix('transaksi/list-penjualan')->name('sales-list.')->group(function () {
        Route::get('/',              [ProductionController::class, 'index'])->name('index');
        Route::get('/{id}/detail',   [ProductionController::class, 'detail'])->name('detail');
        Route::put('/{id}/status',   [ProductionController::class, 'updateStatus'])->name('update-status');
        Route::get('/{id}/upload',   [ProductionController::class, 'uploadForm'])->name('upload');
        Route::post('/{id}/upload',  [ProductionController::class, 'uploadFiles'])->name('upload.store');
        Route::get('/{id}/files/{fileId}', [ProductionController::class, 'downloadFile'])->name('files.download');
    });

    // ── Pembayaran ───────────────────────────────────────────
    Route::prefix('pembayaran')->name('pembayaran.')->group(function () {
        Route::get('/',                   [PaymentController::class, 'index'])->name('index');
        Route::get('/{invoice_id}/bayar', [PaymentController::class, 'create'])->name('create');
        Route::post('/{invoice_id}',      [PaymentController::class, 'store'])->name('store');
        Route::get('/file/{paymentId}/{fileId}', [PaymentController::class, 'downloadFile'])->name('files.download');
    });

    // ── Laporan ──────────────────────────────────────────────
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('ranking-pelanggan', [CustomerRankingController::class, 'index'])->name('ranking-pelanggan');
        Route::get('kpi-sales',         [SalesKpiController::class, 'index'])->name('kpi-sales');
        Route::get('rekap-invoice',     [InvoiceRecapController::class, 'index'])->name('rekap-invoice');
        Route::get('rekap-pembayaran',  [PaymentRecapController::class, 'index'])->name('rekap-pembayaran');
        Route::get('rekap-penjualan-plat', [PlateSalesRecapController::class, 'index'])->name('rekap-penjualan-plat');
        Route::get('rekap-penjualan-jasa-cutting', [CuttingSalesRecapController::class, 'index'])->name('rekap-penjualan-jasa-cutting');
        Route::get('piutang',           [ReceivableController::class, 'index'])->name('piutang');
        Route::get('stok',              [StockController::class, 'index'])->name('stok');
    });
});
