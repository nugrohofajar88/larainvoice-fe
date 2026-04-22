<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;

class PaymentRecapController extends Controller
{
    public function export()
    {
        request()->merge(['export' => 'csv']);

        return $this->index();
    }

    public function index()
    {
        $params = $this->getApiParams(array_filter(request()->only(['invoice_number', 'method', 'date_from', 'date_to', 'branch_id']), fn ($value) => $value !== null && $value !== ''));
        $allPayments = $this->fetchApiCollection(config('services.pioneer.api_url') . '/payments', $params);
        $payments = $this->paginateArray($allPayments, 10);

        if (request('export') === 'csv') {
            $rows = collect($allPayments)->map(fn ($pay) => [
                $pay['invoice_number'] ?? '-',
                $pay['branch'] ?? '-',
                (float) ($pay['amount'] ?? 0),
                $pay['method'] ?? '-',
                !empty($pay['is_dp']) ? 'Cicilan' : 'Pelunasan',
                $pay['date'] ?? '-',
                $pay['note'] ?? '',
            ]);

            return $this->streamCsvDownload('rekap-pembayaran.csv', ['Invoice', 'Cabang', 'Jumlah', 'Metode', 'Tipe', 'Tanggal', 'Keterangan'], $rows);
        }

        $branches = $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $this->getApiParams([]));

        return view('reports.rekap-pembayaran', compact('payments', 'branches'));
    }
}
