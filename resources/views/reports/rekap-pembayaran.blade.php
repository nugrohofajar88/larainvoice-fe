@extends('layouts.app')
@section('title', 'Rekap Pembayaran')
@section('page-title', 'Rekap Pembayaran')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div><h1 class="page-title">Rekap Pembayaran</h1></div>
    <a href="{{ route('laporan.export.rekap-pembayaran', request()->query()) }}" class="btn-outline">Export Excel</a>
</div>
<div class="card card-body mb-5 grid grid-cols-1 md:grid-cols-4 gap-3">
    <div><label class="form-label">Cabang</label><select name="branch_id" form="payment-filter-form" class="form-select"><option value="">Semua cabang</option>@foreach($branches as $branch)<option value="{{ $branch['id'] }}" {{ (string) request('branch_id') === (string) $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>@endforeach</select></div>
    <div><label class="form-label">Tanggal Dari</label><input type="date" name="date_from" form="payment-filter-form" class="form-input" value="{{ request('date_from') }}"></div>
    <div><label class="form-label">Tanggal Sampai</label><input type="date" name="date_to" form="payment-filter-form" class="form-input" value="{{ request('date_to') }}"></div>
    <div class="flex items-end gap-2">
        <button type="submit" form="payment-filter-form" class="btn-primary">Terapkan</button>
        @if(request()->anyFilled(['invoice_number', 'method', 'branch_id', 'date_from', 'date_to']))
            <a href="{{ route('laporan.rekap-pembayaran') }}" class="btn-outline">Reset</a>
        @endif
    </div>
</div>
<div class="card overflow-hidden">
    <form id="payment-filter-form" action="{{ route('laporan.rekap-pembayaran') }}" method="GET">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>Invoice</th>
                        <th>Cabang</th>
                        <th class="text-right">Jumlah</th>
                        <th>Metode</th>
                        <th>Tipe</th>
                        <th>Tanggal</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="invoice_number" value="{{ request('invoice_number') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari invoice...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="method" value="{{ request('method') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari metode...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['invoice_number', 'method', 'branch_id', 'date_from', 'date_to']))
                                <a href="{{ route('laporan.rekap-pembayaran') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pay)
                    @php
                        $paymentType = $pay['payment_type'] ?? (!empty($pay['is_dp']) ? 'cicilan' : 'pelunasan');
                        $paymentTypeMeta = match ($paymentType) {
                            'dp' => ['label' => 'DP', 'class' => 'badge-warning'],
                            'cicilan' => ['label' => 'Cicilan', 'class' => 'badge-warning'],
                            'refund' => ['label' => 'Refund', 'class' => 'badge-danger'],
                            default => ['label' => 'Pelunasan', 'class' => 'badge-neutral'],
                        };
                    @endphp
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($payments->currentPage() - 1) * $payments->perPage() + $loop->iteration }}</span></td>
                        <td class="font-mono text-xs text-brand font-bold">{{ $pay['invoice_number'] }}</td>
                        <td class="text-slate-500 text-xs">{{ $pay['branch'] ?? '-' }}</td>
                        <td class="text-right font-mono font-semibold">Rp {{ number_format($pay['amount']) }}</td>
                        <td><span class="{{ $pay['method']==='Cash' ? 'badge-success' : 'badge-info' }} text-xs">{{ $pay['method'] }}</span></td>
                        <td><span class="{{ $paymentTypeMeta['class'] }} text-xs">{{ $paymentTypeMeta['label'] }}</span></td>
                        <td class="text-slate-500 text-xs">{{ date('d/m/Y', strtotime($pay['date'])) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Tidak ada data pembayaran ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($payments->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection

