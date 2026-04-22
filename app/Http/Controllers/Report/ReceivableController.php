<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class ReceivableController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    public function index()
    {
        $filters = request()->only(['number', 'customer', 'branch_id']);

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

        $receivables = collect($invoices)
            ->map(function ($invoice) use ($paidByInvoice) {
                $invoice['paid'] = (float) ($paidByInvoice[$invoice['number']] ?? 0);
                $invoice['receivable_amount'] = max((float) $invoice['grand_total'] - (float) $invoice['paid'], 0);
                $invoice['days_outstanding'] = Carbon::parse($invoice['date'])->diffInDays(Carbon::now());

                return $invoice;
            })
            ->filter(fn ($invoice) => ($invoice['production_status'] ?? null) !== 'cancelled')
            ->filter(fn ($invoice) => (float) $invoice['receivable_amount'] > 0)
            ->values();

        $totalItems = $receivables->count();
        $totalAmount = (float) $receivables->sum('receivable_amount');

        $invoices = $this->paginateArray($receivables->all(), 10);

        if (request('export') === 'csv') {
            $rows = $receivables->map(fn ($inv) => [
                $inv['number'] ?? '-',
                $inv['customer'] ?? '-',
                $inv['branch'] ?? '-',
                $inv['date'] ?? '-',
                (float) ($inv['grand_total'] ?? 0),
                (float) ($inv['paid'] ?? 0),
                (float) ($inv['receivable_amount'] ?? 0),
                $inv['status'] ?? '-',
            ]);

            return $this->streamCsvDownload('piutang.csv', ['No Invoice', 'Pelanggan', 'Cabang', 'Tanggal', 'Total Tagihan', 'Terbayar', 'Sisa Piutang', 'Status Pembayaran'], $rows);
        }

        $branches = $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $this->getApiParams([]));

        return view('reports.piutang', compact('invoices', 'totalItems', 'totalAmount', 'branches'));
    }
}
