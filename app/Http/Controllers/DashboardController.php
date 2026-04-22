<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $forceRefresh = $request->boolean('refresh');
        $selectedBranchId = AuthHelper::isSuperAdmin()
            ? ($request->integer('branch_id') ?: null)
            : (session('branch_id') ? (int) session('branch_id') : null);

        $branches = [];
        $selectedBranch = null;
        $stats = [
            'total_invoice_bulan_ini' => 0,
            'total_omzet_bulan_ini' => 0,
            'total_piutang' => 0,
            'produksi_berjalan' => 0,
            'pelanggan_aktif' => 0,
            'invoice_lunas' => 0,
            'invoice_dp' => 0,
            'invoice_belum_bayar' => 0,
        ];
        $revenueChart = ['labels' => [], 'data' => []];
        $prodChart = ['pending' => 0, 'in_process' => 0, 'completed' => 0, 'cancelled' => 0];
        $invoices = [];
        $customers = [];
        $generatedAt = null;
        $cacheTtlSeconds = null;

        try {
            $shouldFetchBranches = AuthHelper::isSuperAdmin() || $selectedBranchId;

            if ($shouldFetchBranches) {
                $branchParams = $this->getApiParams(['per_page' => 9999]);
                if (AuthHelper::isSuperAdmin() && !$selectedBranchId) {
                    unset($branchParams['branch_id']);
                }

                $branchResponse = $this->apiClient()->get(
                    config('services.pioneer.api_url') . '/branches',
                    $branchParams
                );

                if ($branchResponse->successful()) {
                    $branches = $this->decodeApiValue($branchResponse, 'data', []);
                    $selectedBranch = $selectedBranchId
                        ? collect($branches)->firstWhere('id', $selectedBranchId)
                        : null;
                }
            }

            $params = $this->getApiParams([]);
            if (AuthHelper::isSuperAdmin() && $selectedBranchId) {
                $params['branch_id'] = $selectedBranchId;
            }
            if ($forceRefresh) {
                $params['refresh'] = 1;
            }

            $response = $this->apiClient()->get(
                config('services.pioneer.api_url') . '/dashboard/summary',
                $params
            );

            if ($response->failed()) {
                return view('dashboard.index', compact(
                    'stats',
                    'invoices',
                    'revenueChart',
                    'prodChart',
                    'customers',
                    'branches',
                    'selectedBranchId',
                    'selectedBranch',
                    'generatedAt',
                    'cacheTtlSeconds'
                ))->with('error', 'Gagal memuat dashboard dari server backend.');
            }

            $payload = $this->decodeApiJson($response, []);

            $stats = data_get($payload, 'stats', $stats);
            $revenueChart = data_get($payload, 'revenue_chart', $revenueChart);
            $prodChart = data_get($payload, 'production_chart', $prodChart);
            $invoices = data_get($payload, 'recent_invoices', $invoices);
            $customers = data_get($payload, 'top_customers', $customers);
            $generatedAt = data_get($payload, 'generated_at');
            $cacheTtlSeconds = data_get($payload, 'cache_ttl_seconds');

        } catch (\Exception $e) {
            return view('dashboard.index', compact(
                'stats',
                'invoices',
                'revenueChart',
                'prodChart',
                'customers',
                'branches',
                'selectedBranchId',
                'selectedBranch',
                'generatedAt',
                'cacheTtlSeconds'
            ))->with('error', 'Koneksi ke backend bermasalah: ' . $e->getMessage());
        }

        return view('dashboard.index', compact(
            'stats',
            'invoices',
            'revenueChart',
            'prodChart',
            'customers',
            'branches',
            'selectedBranchId',
            'selectedBranch',
            'generatedAt',
            'cacheTtlSeconds'
        ));
    }
}
