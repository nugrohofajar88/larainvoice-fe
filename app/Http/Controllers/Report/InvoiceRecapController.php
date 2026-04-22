<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;

class InvoiceRecapController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    public function index()
    {
        $filters = request()->only(['number', 'customer', 'date_from', 'date_to', 'branch_id']);

        $invoices = $this->fetchApiCollection(
            config('services.pioneer.api_url') . '/invoices',
            $this->getApiParams(array_filter($filters, fn ($value) => $value !== null && $value !== ''))
        );

        $payments = $this->fetchApiCollection(
            config('services.pioneer.api_url') . '/payments',
            $this->getApiParams(array_filter([
                'branch_id' => request('branch_id'),
            ], fn ($value) => $value !== null && $value !== ''))
        );

        $paidByInvoice = collect($payments)->groupBy('invoice_number')->map(fn ($items) => collect($items)->sum('amount'));

        $allInvoices = collect($invoices)->map(function ($invoice) use ($paidByInvoice) {
            $invoice['paid'] = (float) ($paidByInvoice[$invoice['number']] ?? 0);

            return $invoice;
        })->values()->all();

        $totalCount = count($allInvoices);
        $totalOmzet = array_sum(array_column($allInvoices, 'grand_total'));
        $avgOmzet = $totalCount > 0 ? $totalOmzet / $totalCount : 0;

        $invoices = $this->paginateArray($allInvoices, 10);

        if (request('export') === 'csv') {
            $rows = collect($allInvoices)->map(fn ($inv) => [
                $inv['number'] ?? '-',
                $inv['customer'] ?? '-',
                $inv['branch'] ?? '-',
                $inv['date'] ?? '-',
                (float) ($inv['grand_total'] ?? 0),
                (float) ($inv['paid'] ?? 0),
                $inv['status'] ?? '-',
                $inv['production_status_label'] ?? '-',
            ]);

            return $this->streamCsvDownload('rekap-invoice.csv', ['No Invoice', 'Pelanggan', 'Cabang', 'Tanggal', 'Grand Total', 'Terbayar', 'Status Pembayaran', 'Status Produksi'], $rows);
        }

        $branches = $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $this->getApiParams([]));

        return view('reports.rekap-invoice', compact('invoices', 'totalCount', 'totalOmzet', 'avgOmzet', 'branches'));
    }
}
