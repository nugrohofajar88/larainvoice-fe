<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class CustomerRankingController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    public function index()
    {
        $filters = request()->only(['name', 'branch']);

        $customers = collect($this->fetchApiCollection(
            config('services.pioneer.api_url') . '/customers',
            $this->getApiParams([])
        ));

        $invoices = collect($this->fetchApiCollection(
            config('services.pioneer.api_url') . '/invoices',
            $this->getApiParams([])
        ));

        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd = Carbon::now()->endOfMonth();
        $previousStart = Carbon::now()->subMonth()->startOfMonth();
        $previousEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentTotals = $invoices
            ->filter(fn ($invoice) => Carbon::parse($invoice['date'])->between($currentStart, $currentEnd))
            ->groupBy('customer')
            ->map(fn ($items) => (float) collect($items)->sum('grand_total'))
            ->sortDesc();

        $previousTotals = $invoices
            ->filter(fn ($invoice) => Carbon::parse($invoice['date'])->between($previousStart, $previousEnd))
            ->groupBy('customer')
            ->map(fn ($items) => (float) collect($items)->sum('grand_total'))
            ->sortDesc();

        $currentRanks = $currentTotals->keys()->values();
        $previousRanks = $previousTotals->keys()->values();

        $rankedCustomers = $customers->map(function ($customer) use ($currentTotals, $previousTotals, $currentRanks, $previousRanks) {
            $name = $customer['full_name'] ?? '-';
            $currentRank = $currentRanks->search($name);
            $previousRank = $previousRanks->search($name);

            return [
                'id' => $customer['id'],
                'name' => $name,
                'branch' => $customer['branch']['name'] ?? '-',
                'ranking_now' => $currentRank === false ? 999 : ($currentRank + 1),
                'ranking_last' => $previousRank === false ? 999 : ($previousRank + 1),
                'total_current' => $currentTotals->get($name, 0),
                'total_previous' => $previousTotals->get($name, 0),
            ];
        })->filter(fn ($item) => $item['ranking_now'] !== 999 || $item['ranking_last'] !== 999);

        if (!empty(array_filter($filters))) {
            $rankedCustomers = $rankedCustomers->filter(function ($item) use ($filters) {
                $matchName = empty($filters['name']) || str_contains(strtolower($item['name']), strtolower($filters['name']));
                $matchBranch = empty($filters['branch']) || str_contains(strtolower($item['branch']), strtolower($filters['branch']));

                return $matchName && $matchBranch;
            });
        }

        $allCustomers = $rankedCustomers->sortBy('ranking_now')->values();
        $customers = $this->paginateArray($allCustomers->all(), 10);

        if (request('export') === 'csv') {
            $rows = $allCustomers->map(fn ($item) => [
                $item['name'] ?? '-',
                $item['branch'] ?? '-',
                $item['ranking_now'] ?? '-',
                $item['ranking_last'] ?? '-',
                $item['total_current'] ?? 0,
                $item['total_previous'] ?? 0,
            ]);

            return $this->streamCsvDownload('ranking-pelanggan.csv', ['Pelanggan', 'Cabang', 'Ranking Sekarang', 'Ranking Sebelumnya', 'Total Bulan Ini', 'Total Bulan Lalu'], $rows);
        }

        return view('reports.ranking-pelanggan', compact('customers'));
    }
}
