@extends('layouts.app')
@section('title', 'Pembayaran')
@section('page-title', 'Pembayaran')

@section('content')
@php
    $statusMeta = [
        'belum' => ['label' => 'Belum Bayar', 'class' => 'badge-danger'],
        'dp' => ['label' => 'Cicilan', 'class' => 'badge-warning'],
        'lunas' => ['label' => 'Lunas', 'class' => 'badge-success'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Pembayaran Invoice</h1>
            <p class="text-sm text-slate-500 mt-1">Pilih invoice untuk melihat detail, mencetak tagihan, dan mencatat pembayaran tambahan.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('invoice.create') }}" class="btn btn-primary">Buat Transaksi Baru</a>
            <a href="{{ route('sales-list.index') }}" class="btn btn-outline">List Penjualan</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <form action="{{ route('pembayaran.index') }}" method="GET">
            <div class="p-5 border-b border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" name="number" value="{{ request('number') }}" class="form-input" placeholder="Cari nomor invoice...">
                <input type="text" name="customer" value="{{ request('customer') }}" class="form-input" placeholder="Cari pelanggan...">
                <input type="text" name="machine" value="{{ request('machine') }}" class="form-input" placeholder="Cari mesin...">
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary w-full justify-center">Filter</button>
                    @if(request()->anyFilled(['number', 'customer', 'machine']))
                        <a href="{{ route('pembayaran.index') }}" class="btn btn-outline w-full justify-center">Reset</a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="w-16">No</th>
                            <th class="w-44">Invoice</th>
                            <th>Pelanggan</th>
                            <th class="w-32">Mesin</th>
                            <th class="w-40">Total</th>
                            <th class="w-36">Status Invoice</th>
                            <th class="w-40">Status Bayar</th>
                            <th class="w-[22rem] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $item)
                            @php
                                $paymentMeta = $statusMeta[$item['status']] ?? ['label' => ucfirst($item['status']), 'class' => 'badge-neutral'];
                                $productionMeta = match($item['production_status'] ?? null) {
                                    'pending' => ['label' => 'Pending', 'class' => 'badge-warning'],
                                    'in-process' => ['label' => 'In-progress', 'class' => 'badge-info'],
                                    'completed' => ['label' => 'Completed', 'class' => 'badge-success'],
                                    'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-danger'],
                                    default => ['label' => ucfirst((string) ($item['production_status'] ?? '-')), 'class' => 'badge-neutral'],
                                };
                            @endphp
                            <tr
                                data-invoice-row="{{ $item['id'] }}"
                                data-invoice-number="{{ $item['number'] }}"
                                data-invoice-date="{{ $item['date'] }}"
                                data-invoice-customer="{{ $item['customer'] ?: '-' }}"
                                data-invoice-branch="{{ $item['branch'] ?: '-' }}"
                                data-invoice-machine="{{ $item['machine'] ?: '-' }}"
                                data-invoice-payment-status="{{ $paymentMeta['label'] }}"
                                data-invoice-production-status="{{ $productionMeta['label'] }}"
                                data-invoice-notes="{{ $item['notes'] ?? '-' }}"
                            >
                                <td class="text-slate-400 font-mono text-xs">{{ ($invoices->currentPage() - 1) * $invoices->perPage() + $loop->iteration }}</td>
                                <td>
                                    <div class="font-mono text-xs font-bold text-slate-900">{{ $item['number'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $item['date'] }}</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-900">{{ $item['customer'] ?: '-' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $item['branch'] ?: '-' }}</div>
                                </td>
                                <td class="font-mono text-xs">{{ $item['machine'] ?: '-' }}</td>
                                <td class="font-semibold text-slate-900">Rp {{ number_format($item['grand_total'] ?? 0, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $productionMeta['class'] }}">{{ $productionMeta['label'] }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <button type="button" class="btn btn-outline btn-sm js-payment-invoice-detail-trigger" data-invoice-id="{{ $item['id'] }}" data-detail-url="{{ route('sales-list.detail', $item['id']) }}">Detail</button>
                                        <a href="{{ route('invoice.print', $item['id']) }}" class="btn btn-outline btn-sm" target="_blank">Print Invoice</a>
                                        @if(($item['status'] ?? null) !== 'lunas' && ($item['production_status'] ?? null) !== 'cancelled')
                                            <a href="{{ route('pembayaran.create', $item['id']) }}" class="btn btn-primary btn-sm">Catat Pembayaran</a>
                                        @else
                                            <a href="{{ route('pembayaran.create', $item['id']) }}" class="btn btn-outline btn-sm">Lihat Pembayaran</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Tidak ada invoice ditemukan untuk menu pembayaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 px-4 js-payment-invoice-detail-modal" role="dialog" aria-modal="true" aria-labelledby="payment-invoice-detail-title">
        <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 id="payment-invoice-detail-title" class="text-lg font-bold text-slate-900">Detail Invoice</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan invoice ditampilkan dalam mode baca saja dari menu pembayaran.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 px-6 py-5 text-sm md:grid-cols-2">
                <div><p class="text-xs text-slate-500">Nomor Invoice</p><p class="font-semibold text-slate-900 js-payment-detail-number">-</p></div>
                <div><p class="text-xs text-slate-500">Tanggal</p><p class="font-semibold text-slate-900 js-payment-detail-date">-</p></div>
                <div><p class="text-xs text-slate-500">Pelanggan</p><p class="font-semibold text-slate-900 js-payment-detail-customer">-</p></div>
                <div><p class="text-xs text-slate-500">Cabang</p><p class="font-semibold text-slate-900 js-payment-detail-branch">-</p></div>
                <div><p class="text-xs text-slate-500">Mesin</p><p class="font-semibold text-slate-900 js-payment-detail-machine">-</p></div>
                <div><p class="text-xs text-slate-500">Status Pembayaran</p><p class="font-semibold text-slate-900 js-payment-detail-payment-status">-</p></div>
                <div class="md:col-span-2"><p class="text-xs text-slate-500">Status Invoice</p><p class="font-semibold text-slate-900 js-payment-detail-production-status">-</p></div>
                <div class="md:col-span-2"><p class="text-xs text-slate-500">Catatan</p><div class="mt-1 rounded-2xl bg-slate-50 px-4 py-3 text-slate-700 js-payment-detail-notes">-</div></div>
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">Detail Pesanan</p>
                        <span class="hidden text-xs text-slate-400 js-payment-detail-loading">Memuat detail...</span>
                    </div>
                    <div class="mt-2 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white js-payment-detail-items">
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">Detail item belum dimuat.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Ringkasan Nominal</p>
                    <div class="mt-2 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-5">
                        <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Subtotal</p><p class="mt-1 font-semibold text-slate-900 js-payment-detail-subtotal">Rp 0</p></div>
                        <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Diskon</p><p class="mt-1 font-semibold text-slate-900 js-payment-detail-discount">Rp 0</p></div>
                        <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Grand Total</p><p class="mt-1 font-semibold text-slate-900 js-payment-detail-grand-total">Rp 0</p></div>
                        <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Terbayar</p><p class="mt-1 font-semibold text-emerald-700 js-payment-detail-paid">Rp 0</p></div>
                        <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Sisa</p><p class="mt-1 font-semibold text-rose-600 js-payment-detail-remaining">Rp 0</p></div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end border-t border-slate-100 px-6 py-4">
                <button type="button" class="btn btn-outline js-payment-detail-close">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const pageRoot = document.querySelector('.space-y-6');
    const modal = document.querySelector('.js-payment-invoice-detail-modal');
    const closeButton = document.querySelector('.js-payment-detail-close');
    const loadingLabel = document.querySelector('.js-payment-detail-loading');
    const itemsBody = document.querySelector('.js-payment-detail-items');

    if (!pageRoot || !modal) {
        return;
    }

    const formatCurrency = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;

    const renderItems = (items = [], message = 'Tidak ada detail item.') => {
        if (!itemsBody) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            itemsBody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">${message}</td></tr>`;
            return;
        }

        itemsBody.innerHTML = items.map((item) => `
            <tr>
                <td class="px-4 py-3 text-slate-700">${item.type || '-'}</td>
                <td class="px-4 py-3 text-slate-700">${item.desc || '-'}</td>
                <td class="px-4 py-3 text-right text-slate-700">${item.qty ?? '-'}</td>
                <td class="px-4 py-3 text-right text-slate-700">${formatCurrency(item.price)}</td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">${formatCurrency(item.subtotal)}</td>
            </tr>
        `).join('');
    };

    const setSummary = (summary = {}) => {
        modal.querySelector('.js-payment-detail-subtotal').textContent = formatCurrency(summary.subtotal || 0);
        modal.querySelector('.js-payment-detail-discount').textContent = formatCurrency(summary.discount_amount || 0);
        modal.querySelector('.js-payment-detail-grand-total').textContent = formatCurrency(summary.grand_total || 0);
        modal.querySelector('.js-payment-detail-paid').textContent = formatCurrency(summary.paid || 0);
        modal.querySelector('.js-payment-detail-remaining').textContent = formatCurrency(summary.remaining || 0);
    };

    const openModal = (row) => {
        modal.querySelector('.js-payment-detail-number').textContent = row.dataset.invoiceNumber || '-';
        modal.querySelector('.js-payment-detail-date').textContent = row.dataset.invoiceDate || '-';
        modal.querySelector('.js-payment-detail-customer').textContent = row.dataset.invoiceCustomer || '-';
        modal.querySelector('.js-payment-detail-branch').textContent = row.dataset.invoiceBranch || '-';
        modal.querySelector('.js-payment-detail-machine').textContent = row.dataset.invoiceMachine || '-';
        modal.querySelector('.js-payment-detail-payment-status').textContent = row.dataset.invoicePaymentStatus || '-';
        modal.querySelector('.js-payment-detail-production-status').textContent = row.dataset.invoiceProductionStatus || '-';
        modal.querySelector('.js-payment-detail-notes').textContent = row.dataset.invoiceNotes || '-';
        renderItems([], 'Detail item belum dimuat.');
        setSummary();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    closeButton?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    pageRoot.querySelectorAll('.js-payment-invoice-detail-trigger').forEach((button) => {
        button.addEventListener('click', async () => {
            const invoiceId = button.dataset.invoiceId || '';
            const detailUrl = button.dataset.detailUrl || '';
            const row = pageRoot.querySelector(`[data-invoice-row="${invoiceId}"]`);

            if (!row) {
                return;
            }

            openModal(row);

            if (!detailUrl) {
                return;
            }

            loadingLabel?.classList.remove('hidden');

            try {
                const response = await fetch(detailUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Gagal memuat detail invoice.');
                }

                renderItems(payload.items || []);
                setSummary({
                    subtotal: payload.subtotal,
                    discount_amount: payload.discount_amount,
                    grand_total: payload.grand_total,
                    paid: payload.paid,
                    remaining: payload.remaining,
                });

                if (payload.notes) {
                    modal.querySelector('.js-payment-detail-notes').textContent = payload.notes;
                }
            } catch (error) {
                renderItems([], 'Gagal memuat detail item.');
                setSummary();
                window.toast?.error?.(error.message || 'Gagal memuat detail invoice.');
            } finally {
                loadingLabel?.classList.add('hidden');
            }
        });
    });
});
</script>
@endpush
