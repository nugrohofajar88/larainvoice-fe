@extends('layouts.app')
@section('title', 'Detail Invoice')
@section('page-title', 'Detail Invoice')

@section('content')
@php $sisa = $invoice['grand_total'] - $invoice['paid']; @endphp
@php
    $paymentTypeMeta = function ($payment) {
        $type = $payment['payment_type'] ?? (!empty($payment['is_dp']) ? 'cicilan' : 'pelunasan');

        return match ($type) {
            'dp' => ['label' => 'DP', 'class' => 'badge-warning'],
            'cicilan' => ['label' => 'Cicilan', 'class' => 'badge-warning'],
            'refund' => ['label' => 'Refund', 'class' => 'badge-danger'],
            default => ['label' => 'Pelunasan', 'class' => 'badge-success'],
        };
    };
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="page-title font-mono">{{ $invoice['number'] }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ date('d F Y', strtotime($invoice['date'])) }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('sales-list.index', ['tab' => $invoice['production_status'] ?? 'pending']) }}" class="btn btn-outline px-5 py-2.5">Kembali</a>
        <a href="{{ route('invoice.print', $invoice['id']) }}" target="_blank" class="btn btn-outline px-5 py-2.5">Print Invoice</a>
        @if(in_array($invoice['production_status'] ?? '', ['pending', 'in-process', 'completed'], true))
            <a href="{{ route('invoice.spk', $invoice['id']) }}" target="_blank" class="btn btn-outline px-5 py-2.5">Print SPK</a>
        @endif
        <a href="{{ route('pembayaran.create', $invoice['id']) }}" class="btn btn-primary px-5 py-2.5">Catat Pembayaran</a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 space-y-5">
        <div class="card card-body grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div><p class="text-slate-500 text-xs mb-0.5">Pelanggan</p><p class="font-semibold">{{ $invoice['customer'] }}</p></div>
            <div><p class="text-slate-500 text-xs mb-0.5">Cabang</p><p class="font-semibold">{{ $invoice['branch'] }}</p></div>
            <div><p class="text-slate-500 text-xs mb-0.5">Mesin</p><p class="font-semibold font-mono">{{ $invoice['machine'] ?? '-' }}</p></div>
            <div><p class="text-slate-500 text-xs mb-0.5">Petugas</p><p class="font-semibold">{{ $invoice['petugas'] }}</p></div>
            <div><p class="text-slate-500 text-xs mb-0.5">Tanggal</p><p class="font-semibold">{{ date('d/m/Y', strtotime($invoice['date'])) }}</p></div>
            <div><p class="text-slate-500 text-xs mb-0.5">Status Produksi</p>
                @php
                    $pm = [
                        'pending' => ['Pending', 'badge-warning'],
                        'in-process' => ['In-progress', 'badge-info'],
                        'completed' => ['Completed', 'badge-success'],
                        'cancelled' => ['Cancelled', 'badge-danger'],
                    ];
                    [$pl, $pc] = $pm[$invoice['production_status']] ?? [$invoice['production_status'], 'badge-neutral'];
                @endphp
                <span class="{{ $pc }}">{{ $pl }}</span>
            </div>
            <div class="col-span-2 md:col-span-3">
                <p class="text-slate-500 text-xs mb-0.5">Catatan</p>
                <p class="font-semibold text-slate-700">{{ $invoice['notes'] ?: '-' }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-slate-800">Item Invoice</h2></div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>Tipe</th><th>Deskripsi</th><th class="text-right">Qty</th><th class="text-right">Harga</th><th class="text-right">Diskon</th><th class="text-right">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach($invoice['items'] as $item)
                        <tr>
                            <td><span class="{{ $item['type'] === 'Cutting' ? 'badge-brand' : 'badge-info' }}">{{ $item['type'] }}</span></td>
                            <td class="text-slate-700">{{ $item['desc'] }}</td>
                            <td class="text-right">{{ $item['qty'] }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($item['price']) }}</td>
                            <td class="text-right text-slate-500">{{ $item['discount'] }}%</td>
                            <td class="text-right font-semibold font-mono">Rp {{ number_format($item['subtotal']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 space-y-1.5 text-sm">
                <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>Rp {{ number_format($invoice['subtotal']) }}</span></div>
                <div class="flex justify-between text-red-600"><span>Diskon ({{ $invoice['discount_pct'] }}%)</span><span>- Rp {{ number_format($invoice['discount_amount']) }}</span></div>
                <div class="flex justify-between font-bold text-lg text-slate-900 pt-2 border-t"><span>Grand Total</span><span>Rp {{ number_format($invoice['grand_total']) }}</span></div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card card-body space-y-3">
            <h3 class="font-semibold text-slate-800 mb-2">Ringkasan Tagihan</h3>
            <div class="flex justify-between text-sm"><span class="text-slate-500">Grand Total</span><span class="font-semibold">Rp {{ number_format($invoice['grand_total']) }}</span></div>
            <div class="flex justify-between text-sm"><span class="text-slate-500">Terbayar</span><span class="font-semibold text-green-700">Rp {{ number_format($invoice['paid']) }}</span></div>
            <div class="border-t pt-2 flex justify-between font-bold text-base {{ $sisa > 0 ? 'text-red-600' : 'text-green-700' }}">
                <span>Sisa Tagihan</span><span>Rp {{ number_format($sisa) }}</span>
            </div>
            @php $pct = $invoice['grand_total'] > 0 ? round($invoice['paid'] / $invoice['grand_total'] * 100) : 0; @endphp
            <div class="w-full bg-slate-200 rounded-full h-2 mt-1">
                <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
            </div>
            <p class="text-xs text-center text-slate-500">{{ $pct }}% terbayar</p>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800">Riwayat Pembayaran</h3></div>
            <div class="card-body space-y-3">
                @if(empty($payments))
                    <p class="text-sm text-slate-400 text-center py-4">Belum ada pembayaran</p>
                @else
                    @foreach($payments as $pay)
                    @php($paymentBadge = $paymentTypeMeta($pay))
                    <div class="flex items-start justify-between pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-semibold">Rp {{ number_format($pay['amount']) }}</p>
                            <p class="text-xs text-slate-500">{{ $pay['method'] }} - {{ date('d/m/Y', strtotime($pay['date'])) }}</p>
                            <p class="text-xs text-slate-400">{{ $pay['note'] }}</p>
                        </div>
                        <span class="{{ $paymentBadge['class'] }}">{{ $paymentBadge['label'] }}</span>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
