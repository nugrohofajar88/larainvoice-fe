<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class SalesKpiController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    public function index()
    {
        $payload = $this->fetchKpiPayload((int) request('page', 1), 10);
        $summary = $payload['summary'] ?? [];
        $sales = new LengthAwarePaginator(
            collect($payload['data'] ?? []),
            (int) ($payload['total'] ?? 0),
            (int) ($payload['per_page'] ?? 10),
            (int) ($payload['current_page'] ?? 1),
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
        $branches = $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $this->getApiParams([]));

        if (request('export') === 'csv') {
            $exportPayload = $this->fetchKpiPayload(1, 100);
            $rows = collect($exportPayload['data'] ?? [])->map(fn ($item) => [
                $item['name'] ?? '-',
                $item['branch'] ?? '-',
                (int) ($item['total_invoice'] ?? 0),
                (float) ($item['total_omzet'] ?? 0),
                (float) ($item['target'] ?? 0),
                round((float) ($item['achievement_pct'] ?? 0), 2) . '%',
            ]);

            return $this->streamCsvDownload('kpi-sales.csv', ['Sales', 'Cabang', 'Total Invoice', 'Total Omzet', 'Target', 'Pencapaian'], $rows);
        }

        return view('reports.kpi-sales', compact('sales', 'summary', 'branches'));
    }

    private function fetchKpiPayload(int $page, int $perPage): array
    {
        $response = $this->apiClient()->get(
            config('services.pioneer.api_url') . '/reports/sales-kpi',
            $this->getApiParams(array_filter([
                'page' => $page,
                'per_page' => $perPage,
                'period_type' => request('period_type', 'monthly'),
                'year' => request('year', now()->year),
                'month' => request('month', now()->month),
                'name' => request('name'),
                'branch_id' => request('branch_id'),
            ], fn ($value) => $value !== null && $value !== ''))
        );

        return $this->decodeApiJson($response, []);
    }
}
