<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;

class StockController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    public function index()
    {
        $branches = collect($this->fetchApiCollection(
            config('services.pioneer.api_url') . '/branches',
            $this->getApiParams([])
        ));

        $rawVariants = $this->fetchApiCollection(
            config('services.pioneer.api_url') . '/plate-variants',
            $this->getApiParams(array_filter([
                'branch_id' => request('branch_id'),
            ], fn ($value) => $value !== null && $value !== ''))
        );

        $branchSettings = $branches->keyBy('id');

        $plates = collect($rawVariants)->map(function ($item) {
            $minimumStock = (int) data_get($item, 'branch.setting.minimum_stock', 0);

            return [
                'id' => $item['id'],
                'branch_id' => $item['branch_id'] ?? null,
                'branch' => $item['branch_name'] ?? data_get($item, 'branch.name', '-'),
                'name' => trim(($item['plate_type_name'] ?? $item['plate_type']['name'] ?? 'Plat') . ' ' . ($item['size_value'] ?? $item['size']['value'] ?? '')),
                'type' => $item['plate_type_name'] ?? $item['plate_type']['name'] ?? '-',
                'size' => $item['size_value'] ?? $item['size']['value'] ?? '-',
                'price' => (float) ($item['price_sell'] ?? 0),
                'stock' => (float) ($item['qty'] ?? 0),
                'minimum_stock' => $minimumStock,
                'unit' => 'lembar',
            ];
        })->map(function ($item) use ($branchSettings) {
            $minimumStock = $item['minimum_stock'] ?: (int) data_get($branchSettings->get($item['branch_id']), 'setting.minimum_stock', 0);
            $item['minimum_stock'] = $minimumStock;
            $item['status'] = $item['stock'] <= 0
                ? 'Habis'
                : ($item['stock'] <= max($minimumStock, 0) ? 'Minimum' : 'Aman');

            return $item;
        });

        $filters = request()->only(['name', 'type', 'branch_id']);
        if (!empty(array_filter($filters))) {
            $plates = $plates->filter(function ($item) use ($filters) {
                $matchName = empty($filters['name']) || str_contains(strtolower($item['name']), strtolower($filters['name']));
                $matchType = empty($filters['type']) || str_contains(strtolower($item['type']), strtolower($filters['type']));
                $matchBranch = empty($filters['branch_id']) || (string) ($item['branch_id'] ?? '') === (string) $filters['branch_id'];

                return $matchName && $matchType && $matchBranch;
            });
        }

        $allPlates = $plates->values();
        $plates = $this->paginateArray($plates->values()->all(), 10);

        if (request('export') === 'csv') {
            $rows = $allPlates->map(fn ($p) => [
                $p['branch'] ?? '-',
                $p['name'] ?? '-',
                $p['type'] ?? '-',
                $p['size'] ?? '-',
                (float) ($p['price'] ?? 0),
                (float) ($p['stock'] ?? 0),
                (int) ($p['minimum_stock'] ?? 0),
                $p['status'] ?? '-',
            ]);

            return $this->streamCsvDownload('stok-plat.csv', ['Cabang', 'Nama Plat', 'Tipe', 'Ukuran', 'Harga', 'Stok', 'Min. Stok', 'Status'], $rows);
        }

        return view('reports.stok', compact('plates', 'branches'));
    }
}
