@extends('layouts.app')
@section('title', 'Catat Pembayaran')
@section('page-title', 'Catat Pembayaran')

@section('content')
@php
    $remaining = max(($invoice['remaining'] ?? (($invoice['grand_total'] ?? 0) - ($invoice['paid'] ?? 0))), 0);
    $payments = $invoice['payments'] ?? [];
    $isCancelled = ($invoice['production_status'] ?? null) === 'cancelled';
    $isPaidOff = $remaining <= 0;
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

<div
    class="max-w-5xl mx-auto space-y-6"
    x-data="paymentForm({
        remaining: {{ (int) round($remaining) }},
        initialAmount: {{ old('amount') ? (int) preg_replace('/\D/', '', (string) old('amount')) : (int) round($remaining) }},
        locked: {{ $isCancelled || $isPaidOff ? 'true' : 'false' }},
        initialMethod: {{ \Illuminate\Support\Js::from(old('method', 'Cash')) }},
        initialIsDp: {{ \Illuminate\Support\Js::from((string) old('is_dp', $remaining < ($invoice['grand_total'] ?? 0) ? '0' : '1')) }},
        initialDate: {{ \Illuminate\Support\Js::from(old('date', date('Y-m-d'))) }},
    })"
    x-init="init()"
>
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Pembayaran {{ $invoice['number'] }}</h1>
            <p class="text-sm text-slate-500 mt-1">Halaman ini hanya untuk pencatatan pembayaran. Data transaksi tidak dapat diubah lagi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('pembayaran.index') }}" class="btn btn-outline">Kembali ke Daftar Pembayaran</a>
            <a href="{{ route('invoice.print', $invoice['id']) }}" class="btn btn-outline" target="_blank">Print Invoice</a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 border-b border-slate-100 py-3">
                    <h3 class="font-bold text-slate-800 text-sm">Ringkasan Invoice</h3>
                </div>
                <div class="card-body p-5 space-y-4">
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Nomor Invoice</p>
                        <p class="font-mono font-bold text-slate-800">{{ $invoice['number'] }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Pelanggan</p>
                        <p class="font-semibold text-slate-700">{{ $invoice['customer'] ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Mesin</p>
                        <p class="font-semibold text-slate-700">{{ $invoice['machine'] ?: '-' }}</p>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Total Tagihan</span>
                            <span class="font-mono font-bold text-slate-900">Rp {{ number_format($invoice['grand_total'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-2">
                            <span class="text-slate-500">Sudah Dibayar</span>
                            <span class="font-mono font-bold text-emerald-700">Rp {{ number_format($invoice['paid'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="p-4 rounded-xl border {{ $isPaidOff ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100' }}">
                        <p class="text-[10px] uppercase font-bold tracking-widest {{ $isPaidOff ? 'text-emerald-500' : 'text-red-400' }}">Sisa Tagihan</p>
                        <p class="text-2xl font-black font-mono mt-1 {{ $isPaidOff ? 'text-emerald-700' : 'text-red-600' }}">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 border-b border-slate-100 py-3">
                    <h3 class="font-bold text-slate-800 text-sm">Riwayat Pembayaran</h3>
                </div>
                <div class="card-body p-5">
                    @if(empty($payments))
                        <p class="text-sm text-slate-400">Belum ada pembayaran tercatat.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($payments as $pay)
                                @php($paymentBadge = $paymentTypeMeta($pay))
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $pay['method'] ?: 'Cash' }}</p>
                                            <p class="text-xs text-slate-400">{{ $pay['date'] ?: '-' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-mono font-bold text-slate-900">Rp {{ number_format($pay['amount'] ?? 0, 0, ',', '.') }}</p>
                                            <span class="badge {{ $paymentBadge['class'] }}">
                                                {{ $paymentBadge['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                    @if(!empty($pay['note']))
                                        <p class="text-xs text-slate-500 mt-2">{{ $pay['note'] }}</p>
                                    @endif
                                    @if(!empty($pay['files']))
                                        <div class="mt-3 space-y-2">
                                            @foreach($pay['files'] as $file)
                                                <a
                                                    href="{{ route('pembayaran.files.download', ['paymentId' => $pay['id'], 'fileId' => $file['id']]) }}"
                                                    class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2 text-xs hover:border-slate-200 hover:bg-slate-50"
                                                >
                                                    <div class="min-w-0">
                                                        <p class="truncate font-medium text-slate-700">{{ $file['file_name'] }}</p>
                                                        <p class="text-[11px] text-slate-400">
                                                            {{ strtoupper($file['file_extension'] ?? '-') }} • {{ number_format(($file['file_size'] ?? 0) / 1024, 1) }} KB
                                                        </p>
                                                    </div>
                                                    <span class="shrink-0 font-medium text-brand">Unduh</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="xl:col-span-2 space-y-6">
            @if($isCancelled)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
                    Invoice ini sudah berstatus <strong>cancelled</strong>, jadi pembayaran baru tidak bisa ditambahkan.
                </div>
            @elseif($isPaidOff)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800">
                    Invoice ini sudah lunas. Riwayat pembayaran masih bisa dilihat, tetapi tidak perlu menambahkan pembayaran baru.
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('pembayaran.store', $invoice['id']) }}" enctype="multipart/form-data" class="space-y-6" @submit="validateBeforeSubmit($event)">
                @csrf

                <div class="card overflow-hidden">
                    <div class="card-header bg-slate-50 border-b border-slate-100 py-4">
                        <h2 class="font-bold text-slate-800">Form Pencatatan Pembayaran</h2>
                    </div>
                    <div class="card-body p-6 space-y-6">
                        <div>
                            <label class="form-label text-slate-700 font-semibold mb-2">Jumlah Bayar</label>
                            <input type="hidden" name="amount" :value="amount">
                            <div class="flex overflow-hidden rounded-xl border border-slate-300 focus-within:border-brand focus-within:ring-3 focus-within:ring-brand/10">
                                <span class="flex shrink-0 items-center border-r border-slate-200 bg-slate-50 px-4 font-mono text-sm font-semibold text-slate-500">Rp</span>
                                <input
                                    type="text"
                                    x-model="amountDisplay"
                                    @input="handleAmountInput($event)"
                                    @blur="normalizeAmount()"
                                    class="w-full border-0 px-4 py-3 text-lg font-mono font-bold text-slate-900 outline-none"
                                    placeholder="0"
                                    :disabled="locked"
                                    inputmode="numeric"
                                >
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span class="badge badge-neutral">Maksimal sisa: Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                                <span class="text-slate-400" x-show="!locked">Wajib lebih dari 0.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="form-label text-slate-700 font-semibold">Metode Pembayaran</label>
                                <select name="method" x-model="method" class="form-select" :disabled="locked">
                                    <option value="Cash">Cash</option>
                                    <option value="Transfer">Transfer</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-slate-700 font-semibold">Jenis Pembayaran</label>
                                <select name="is_dp" x-model="isDp" class="form-select" :disabled="locked">
                                    <option value="1">Cicilan</option>
                                    <option value="0">Pelunasan</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-slate-700 font-semibold">Tanggal Bayar</label>
                                <input type="date" name="date" x-model="paymentDate" class="form-input" :disabled="locked">
                            </div>
                        </div>

                        <div>
                            <label class="form-label text-slate-700 font-semibold">Catatan Internal</label>
                            <textarea name="note" class="form-input min-h-[100px]" placeholder="Contoh: Bukti transfer diterima melalui WhatsApp." :disabled="locked">{{ old('note') }}</textarea>
                        </div>

                        <div>
                            <label class="form-label text-slate-700 font-semibold">Upload Bukti Bayar</label>
                            <input
                                type="file"
                                name="proof_files[]"
                                class="form-input"
                                accept=".pdf,.png,.jpg,.jpeg"
                                multiple
                                :disabled="locked"
                            >
                            <p class="mt-2 text-xs text-slate-500">
                                Mendukung `pdf`, `png`, `jpg`, `jpeg` maksimal 20 MB per file.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm sticky bottom-4 z-10">
                    <a href="{{ route('pembayaran.index') }}" class="btn btn-outline px-8">Kembali</a>
                    <button type="submit" class="btn btn-primary px-10" :disabled="locked" :class="{ 'opacity-60 cursor-not-allowed': locked }">
                        Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function paymentForm(config) {
        return {
            remaining: Number(config.remaining || 0),
            amount: Number(config.initialAmount || 0),
            amountDisplay: '',
            locked: Boolean(config.locked),
            method: config.initialMethod || 'Cash',
            isDp: config.initialIsDp || '1',
            paymentDate: config.initialDate || '',

            init() {
                if (this.locked) {
                    this.amount = Math.min(this.amount, this.remaining);
                } else if (this.amount <= 0) {
                    this.amount = this.remaining;
                } else {
                    this.amount = Math.min(this.amount, this.remaining);
                }

                this.amountDisplay = this.formatNumber(this.amount);
            },

            handleAmountInput(event) {
                const rawValue = String(event.target.value || '').replace(/\D/g, '');
                const numericValue = rawValue === '' ? 0 : Number(rawValue);
                this.amount = Math.min(numericValue, this.remaining);
                this.amountDisplay = rawValue === '' ? '' : this.formatNumber(this.amount);
            },

            normalizeAmount() {
                this.amountDisplay = this.amount > 0 ? this.formatNumber(this.amount) : '';
            },

            formatNumber(value) {
                return new Intl.NumberFormat('id-ID').format(Number(value || 0));
            },

            validateBeforeSubmit(event) {
                if (this.locked) {
                    event.preventDefault();
                    return false;
                }

                if (Number(this.amount || 0) <= 0) {
                    event.preventDefault();
                    window.toast.error('Jumlah bayar harus lebih dari 0.');
                    return false;
                }

                return true;
            }
        };
    }
</script>
@endpush
