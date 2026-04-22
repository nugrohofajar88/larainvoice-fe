<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class SalesItemRecapController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    abstract protected function productType(): string;

    abstract protected function viewTitle(): string;

    abstract protected function routeName(): string;

    public function index()
    {
        $payload = $this->fetchPayload((int) request('page', 1), 10);
        $summary = $payload['summary'] ?? [];
        $rows = new LengthAwarePaginator(
            collect($payload['data'] ?? []),
            (int) ($payload['total'] ?? 0),
            (int) ($payload['per_page'] ?? 10),
            (int) ($payload['current_page'] ?? 1),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        if (request('export') === 'csv') {
            $allRows = $this->fetchAllRows();
            $exportRows = $allRows->map(fn ($row) => [
                $row['number'] ?? '-',
                $row['date'] ?? '-',
                $row['customer'] ?? '-',
                $row['branch'] ?? '-',
                $row['description'] ?? '-',
                (int) ($row['qty'] ?? 0),
                (int) ($row['minutes'] ?? 0),
                (float) ($row['price'] ?? 0),
                (float) ($row['subtotal'] ?? 0),
                $row['production_status'] ?? '-',
            ]);

            return $this->streamCsvDownload(
                str($this->productType())->slug('-') . '-sales-recap.csv',
                ['No Invoice', 'Tanggal', 'Pelanggan', 'Cabang', 'Deskripsi Item', 'Qty', 'Menit', 'Harga', 'Subtotal', 'Status Produksi'],
                $exportRows
            );
        }

        return view('reports.rekap-penjualan-item', [
            'rows' => $rows,
            'title' => $this->viewTitle(),
            'routeName' => $this->routeName(),
            'totalRows' => (int) ($summary['total_rows'] ?? 0),
            'totalQty' => (int) ($summary['total_qty'] ?? 0),
            'totalCancel' => (int) ($summary['total_cancel'] ?? 0),
            'totalOmzet' => (float) ($summary['total_omzet'] ?? 0),
            'branches' => $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $this->getApiParams([])),
            'showMinutes' => $this->productType() === 'cutting',
        ]);
    }

    private function fetchPayload(int $page, int $perPage): array
    {
        $response = $this->apiClient()->get(
            config('services.pioneer.api_url') . '/reports/sales-recap/' . $this->productType(),
            $this->getApiParams(array_filter([
                'page' => $page,
                'per_page' => $perPage,
                'number' => request('number'),
                'customer' => request('customer'),
                'description' => request('description'),
                'branch_id' => request('branch_id'),
                'date_from' => request('date_from'),
                'date_to' => request('date_to'),
            ], fn ($value) => $value !== null && $value !== ''))
        );

        return $this->decodeApiJson($response, []);
    }

    private function fetchAllRows()
    {
        $page = 1;
        $rows = collect();

        do {
            $payload = $this->fetchPayload($page, 100);
            $rows = $rows->merge($payload['data'] ?? []);
            $lastPage = (int) ($payload['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage);

        return $rows;
    }
}
