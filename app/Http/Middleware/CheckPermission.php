<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use App\Helpers\AuthHelper;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $module   Optional module override
     * @param  string|null  $action   Optional action override
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $module = null, $action = null)
    {
        // 1. Bypass check if user is SuperAdmin
        if (AuthHelper::isSuperAdmin()) {
            return $next($request);
        }

        // 2. Identify Module Key if not provided
        if (!$module) {
            $module = $this->identifyModule($request);
        }

        // 3. Identify Action if not provided
        if (!$action) {
            $action = $this->identifyAction($request);
        }

        // 4. Default to 'read' (view) if action is unknown or for GET requests
        if (!$action) {
            $action = 'read';
        }

        // 5. Special Case: Some 'read' actions are mapped to 'view' in the session/API
        // But MenuHelper handles session internally.
        
        // 6. Check Permission
        if (!MenuHelper::hasPermission($module, $action)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => "Anda tidak memiliki akses ($action) ke modul ini ($module)."
                ], 403);
            }

            return abort(403, "Anda tidak memiliki akses ($action) ke modul ini.");
        }

        return $next($request);
    }

    /**
     * Map route names to permission keys
     */
    private function identifyModule(Request $request): string
    {
        $routeName = $request->route()->getName();
        
        $map = [
            'master.cabang'     => 'branch',
            'master.pengguna'   => 'user',
            'master.pelanggan'  => 'customer',
            'master.sales'      => 'sales',
            'master.mesin'      => 'machine',
            'master.component'  => 'component',
            'master.component-category' => 'component-category',
            'master.plat'       => 'plate',
            'master.plat-size'  => 'plate-size',
            'master.plat-material' => 'plate-material',
            'master.machine-type' => 'machine-type',
            'master.role'       => 'role',
            'master.menu'       => 'menu-setting',
            'master.harga-cutting' => 'cutting-price',
            'invoice'           => 'invoice',
            'machine-order'     => 'machine-order',
            'pembayaran'        => 'payment',
            'produksi'          => 'production',
            'sales-list'        => 'production',
            'laporan.ranking-pelanggan' => 'report-customer-ranking',
            'laporan.kpi-sales' => 'report-sales-kpi',
            'laporan.rekap-invoice' => 'report-invoice-recap',
            'laporan.rekap-pembayaran' => 'report-payment-recap',
            'laporan.rekap-penjualan-plat' => 'report-plate-sales-recap',
            'laporan.rekap-penjualan-jasa-cutting' => 'report-cutting-sales-recap',
            'laporan.piutang' => 'report-receivable',
            'laporan.stok' => 'report-stock',
            'laporan'           => 'report',
        ];

        foreach ($map as $prefix => $key) {
            if (str_starts_with($routeName, $prefix)) {
                return $key;
            }
        }

        return 'dashboard';
    }

    /**
     * Infer action index/show(view), store(create), update(edit), destroy(delete)
     */
    private function identifyAction(Request $request): string
    {
        $method = strtoupper($request->method());
        $routeName = $request->route()->getName();

        // 1. Explicitly check route name suffixes
        if (str_ends_with($routeName, '.index')) {
            return 'view'; 
        }
        if (str_ends_with($routeName, '.show')) {
            return 'detail'; 
        }
        if (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.store')) {
            return 'create';
        }
        if (str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.update')) {
            return 'edit';
        }
        if (str_ends_with($routeName, '.destroy')) {
            return 'delete';
        }

        // 2. Fallback to HTTP Methods
        return match ($method) {
            'POST'   => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default  => 'view'
        };
    }
}
