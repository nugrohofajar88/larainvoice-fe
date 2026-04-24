@extends('layouts.app')
@section('title', 'List Penjualan')
@section('page-title', 'List Penjualan')

@php
    $tabMeta = [
        'pending' => ['label' => 'Pending', 'badge' => 'badge-warning', 'count' => $statusCounts['pending'] ?? 0],
        'in-process' => ['label' => 'In-progress', 'badge' => 'badge-info', 'count' => $statusCounts['in-process'] ?? 0],
        'completed' => ['label' => 'Completed', 'badge' => 'badge-success', 'count' => $statusCounts['completed'] ?? 0],
        'cancelled' => ['label' => 'Cancelled', 'badge' => 'badge-danger', 'count' => $statusCounts['cancelled'] ?? 0],
    ];
@endphp

@section('content')
<div class="space-y-6" data-active-tab="{{ $activeTab }}">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">List Penjualan</h1>
            <p class="text-sm text-slate-500 mt-1">Pantau pesanan berdasarkan status produksi dan akses dokumen operasional.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('invoice.create') }}" class="btn btn-primary">Buat Transaksi Baru</a>
            <a href="{{ route('pembayaran.index') }}" class="btn btn-outline">Menu Pembayaran</a>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-2.5">
        @foreach($tabMeta as $key => $meta)
            <a
                href="{{ route('sales-list.index', array_merge(request()->except(['tab', 'page']), ['tab' => $key])) }}"
                class="card relative overflow-hidden border px-3.5 py-2.5 transition-all {{ $activeTab === $key ? 'border-brand bg-brand/5 shadow-lg shadow-brand/10' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70' }}"
                aria-current="{{ $activeTab === $key ? 'page' : 'false' }}"
                data-tab-card="{{ $key }}"
            >
                <span class="absolute inset-x-0 top-0 h-0.5 {{ $activeTab === $key ? 'bg-brand' : 'bg-slate-100' }}"></span>
                <div class="space-y-0.5">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] {{ $activeTab === $key ? 'text-brand' : 'text-slate-400' }}">{{ $meta['label'] }}</p>
                            @if($activeTab === $key)
                                <span class="inline-flex items-center rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Aktif</span>
                            @endif
                        </div>
                        <p class="text-xl font-black leading-none text-slate-900 mt-1" data-tab-count="{{ $key }}">{{ $meta['count'] }}</p>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <form action="{{ route('sales-list.index') }}" method="GET" class="p-5 border-b border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="text" name="number" value="{{ request('number') }}" class="form-input" placeholder="Cari nomor invoice...">
            <input type="text" name="customer" value="{{ request('customer') }}" class="form-input" placeholder="Cari pelanggan...">
            <input type="text" name="machine" value="{{ request('machine') }}" class="form-input" placeholder="Cari mesin...">
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary w-full justify-center">Filter</button>
                @if(request()->anyFilled(['number', 'customer', 'machine']))
                    <a href="{{ route('sales-list.index', ['tab' => $activeTab]) }}" class="btn btn-outline w-full justify-center">Reset</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">No</th>
                        <th class="w-44">Invoice</th>
                        <th>Pelanggan</th>
                        <th class="w-32">Mesin</th>
                        <th class="w-36">Bayar</th>
                        <th class="w-36">Status</th>
                        <th class="w-[32rem] text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody data-invoice-table-body>
                    @forelse($invoices as $item)
                        @php
                            $statusLabel = $item['production_status_label'] ?? ucfirst($item['production_status']);
                            $statusClass = match($item['production_status']) {
                                'pending' => 'badge-warning',
                                'in-process' => 'badge-info',
                                'completed' => 'badge-success',
                                'cancelled' => 'badge-danger',
                                default => 'badge-neutral',
                            };

                            $paymentLabel = match($item['status']) {
                                'lunas' => ['Lunas', 'badge-success'],
                                'dp' => ['Cicilan', 'badge-warning'],
                                default => ['Belum', 'badge-danger'],
                            };

                            $actions = [];

                            $actions[] = [
                                'label' => 'Detail',
                                'modal' => 'invoice-detail',
                                'variant' => 'outline',
                            ];

                            if ($item['production_status'] !== 'cancelled') {
                                $actions[] = [
                                    'label' => 'Print Invoice',
                                    'href' => route('invoice.print', $item['id']),
                                    'variant' => 'outline',
                                ];
                            }

                            if (($item['status'] ?? null) !== 'lunas' && ($item['production_status'] ?? null) !== 'cancelled') {
                                $actions[] = [
                                    'label' => 'Bayar',
                                    'href' => route('pembayaran.create', $item['id']),
                                    'variant' => 'primary',
                                ];
                            }

                            if (in_array($item['production_status'], ['pending', 'in-process'], true)) {
                                $actions[] = [
                                    'label' => 'Print SPK',
                                    'href' => route('invoice.spk', $item['id']),
                                    'variant' => 'outline',
                                ];
                            }

                            if ($item['production_status'] === 'pending') {
                                $actions[] = [
                                    'label' => 'Upload File',
                                    'href' => route('sales-list.upload', $item['id']),
                                    'variant' => 'outline',
                                ];
                                $actions[] = [
                                    'label' => 'Process',
                                    'status' => 'in-process',
                                    'variant' => 'primary',
                                ];
                                $actions[] = [
                                    'label' => 'Cancel',
                                    'status' => 'cancelled',
                                    'variant' => 'danger',
                                ];
                            }

                            if ($item['production_status'] === 'in-process') {
                                $actions[] = [
                                    'label' => 'File Kerja',
                                    'href' => route('sales-list.upload', $item['id']),
                                    'variant' => 'outline',
                                ];
                                $actions[] = [
                                    'label' => 'Completed',
                                    'status' => 'completed',
                                    'variant' => 'primary',
                                ];
                                $actions[] = [
                                    'label' => 'Cancel',
                                    'status' => 'cancelled',
                                    'variant' => 'danger',
                                ];
                            }
                        @endphp
                        <tr
                            data-invoice-row="{{ $item['id'] }}"
                            data-invoice-number="{{ $item['number'] }}"
                            data-invoice-date="{{ $item['date'] }}"
                            data-invoice-customer="{{ $item['customer'] }}"
                            data-invoice-branch="{{ $item['branch'] }}"
                            data-invoice-machine="{{ $item['machine'] ?: '-' }}"
                            data-invoice-payment-status="{{ $paymentLabel[0] }}"
                            data-invoice-production-status="{{ $statusLabel }}"
                            data-invoice-notes="{{ $item['notes'] ?: '-' }}"
                        >
                            <td class="text-slate-400 font-mono text-xs">{{ ($invoices->currentPage() - 1) * $invoices->perPage() + $loop->iteration }}</td>
                            <td>
                                <div class="font-mono text-xs font-bold text-slate-900">{{ $item['number'] }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item['date'] }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $item['customer'] }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item['branch'] }}</div>
                            </td>
                            <td class="font-mono text-xs">{{ $item['machine'] ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $paymentLabel[1] }}">{{ $paymentLabel[0] }}</span>
                                @if($activeTab === 'completed')
                                    <div class="mt-1 text-[10px] font-medium {{ ($item['remaining'] ?? 0) > 0 ? 'text-red-500' : 'text-slate-400' }}">
                                        Sisa: Rp {{ number_format($item['remaining'] ?? 0, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="py-3">
                                <div class="flex items-center justify-end gap-1 overflow-x-auto whitespace-nowrap py-0.5">
                                    @foreach($actions as $action)
                                        @if(isset($action['href']))
                                            <a
                                                href="{{ $action['href'] }}"
                                                class="{{ $action['variant'] === 'primary' ? 'btn btn-primary' : ($action['variant'] === 'danger' ? 'btn btn-danger' : 'btn btn-outline') }} btn-sm px-2 py-1 text-[10px] leading-none whitespace-nowrap shrink-0"
                                                @if(str_contains($action['label'], 'Print')) target="_blank" @endif
                                            >
                                                {{ $action['label'] }}
                                            </a>
                                        @elseif(($action['modal'] ?? null) === 'invoice-detail')
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-sm px-2 py-1 text-[10px] leading-none whitespace-nowrap shrink-0 js-invoice-detail-trigger"
                                                data-invoice-id="{{ $item['id'] }}"
                                                data-detail-url="{{ route('sales-list.detail', $item['id']) }}"
                                            >
                                                {{ $action['label'] }}
                                            </button>
                                        @else
                                            <form action="{{ route('sales-list.update-status', $item['id']) }}" method="POST" class="shrink-0 js-status-form" data-invoice-id="{{ $item['id'] }}" data-target-status="{{ $action['status'] }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="production_status" value="{{ $action['status'] }}">
                                                <input type="hidden" name="notes" value="">
                                                <input type="hidden" name="tab" value="{{ $activeTab }}">
                                                <button type="submit" class="{{ $action['variant'] === 'primary' ? 'btn btn-primary' : 'btn btn-danger' }} btn-sm px-2 py-1 text-[10px] leading-none whitespace-nowrap js-status-submit" data-default-label="{{ $action['label'] }}">
                                                        {{ $action['label'] }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                Tidak ada invoice pada status {{ $tabMeta[$activeTab]['label'] }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 px-4 js-cancel-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 id="cancel-modal-title" class="text-lg font-bold text-slate-900">Catatan Cancel Invoice</h2>
                <p class="mt-1 text-sm text-slate-500">Admin bisa menambahkan alasan atau keterangan pembatalan sebelum invoice di-cancel.</p>
            </div>
            <div class="px-6 py-5">
                <label for="cancel-notes" class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                <textarea id="cancel-notes" class="form-input min-h-32 w-full resize-y js-cancel-notes" placeholder="Contoh: Customer menunda produksi, revisi desain, atau pembayaran tidak jadi dilanjutkan."></textarea>
                <p class="mt-2 text-xs text-slate-400">Catatan ini akan disimpan pada invoice yang dibatalkan.</p>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button type="button" class="btn btn-outline js-cancel-modal-close">Tutup</button>
                <button type="button" class="btn btn-danger js-cancel-modal-submit">Simpan & Cancel</button>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/40 px-4 py-6 js-invoice-detail-modal" role="dialog" aria-modal="true" aria-labelledby="invoice-detail-title">
        <div class="mx-auto flex min-h-full w-full max-w-2xl items-center justify-center">
        <div class="flex max-h-[90vh] w-full flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 id="invoice-detail-title" class="text-lg font-bold text-slate-900">Detail Invoice</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan invoice ditampilkan dalam mode baca saja agar informasi tetap cepat diakses dari daftar.</p>
            </div>
            <div class="grid flex-1 grid-cols-1 gap-4 overflow-y-auto px-6 py-5 text-sm md:grid-cols-2">
                <div>
                    <p class="text-xs text-slate-500">Nomor Invoice</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-number">-</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Tanggal</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-date">-</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Pelanggan</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-customer">-</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Cabang</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-branch">-</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Mesin</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-machine">-</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Status Pembayaran</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-payment-status">-</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Status Produksi</p>
                    <p class="font-semibold text-slate-900 js-invoice-detail-production-status">-</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Catatan</p>
                    <div class="mt-1 rounded-2xl bg-slate-50 px-4 py-3 text-slate-700 js-invoice-detail-notes">-</div>
                </div>
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">Detail Pesanan</p>
                        <span class="hidden text-xs text-slate-400 js-invoice-detail-loading">Memuat detail...</span>
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
                                <tbody class="divide-y divide-slate-100 bg-white js-invoice-detail-items">
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">Detail item belum dimuat.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Ringkasan Nominal</p>
                    <div class="mt-2 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-5">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Subtotal</p>
                            <p class="mt-1 font-semibold text-slate-900 js-invoice-detail-subtotal">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Diskon</p>
                            <p class="mt-1 font-semibold text-slate-900 js-invoice-detail-discount">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Grand Total</p>
                            <p class="mt-1 font-semibold text-slate-900 js-invoice-detail-grand-total">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Terbayar</p>
                            <p class="mt-1 font-semibold text-emerald-700 js-invoice-detail-paid">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Sisa</p>
                            <p class="mt-1 font-semibold text-rose-600 js-invoice-detail-remaining">Rp 0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end border-t border-slate-100 px-6 py-4">
                <button type="button" class="btn btn-outline js-invoice-detail-close">Tutup</button>
            </div>
        </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const pageRoot = document.querySelector('[data-active-tab]');

    if (!pageRoot) {
        return;
    }

    const activeTab = pageRoot.dataset.activeTab || 'pending';
    const tableBody = pageRoot.querySelector('[data-invoice-table-body]');
    const cancelModal = pageRoot.querySelector('.js-cancel-modal');
    const cancelNotesField = pageRoot.querySelector('.js-cancel-notes');
    const cancelModalClose = pageRoot.querySelector('.js-cancel-modal-close');
    const cancelModalSubmit = pageRoot.querySelector('.js-cancel-modal-submit');
    const invoiceDetailModal = pageRoot.querySelector('.js-invoice-detail-modal');
    const invoiceDetailClose = pageRoot.querySelector('.js-invoice-detail-close');
    const invoiceDetailLoading = pageRoot.querySelector('.js-invoice-detail-loading');
    const invoiceDetailItems = pageRoot.querySelector('.js-invoice-detail-items');
    let pendingCancelForm = null;

    const setButtonLoading = (button, loading) => {
        if (!button) {
            return;
        }

        const defaultLabel = button.dataset.defaultLabel || button.textContent.trim();

        button.disabled = loading;
        button.classList.toggle('opacity-70', loading);
        button.classList.toggle('cursor-wait', loading);
        button.textContent = loading ? 'Loading...' : defaultLabel;
    };

    const adjustTabCount = (tabKey, delta) => {
        const countNode = pageRoot.querySelector(`[data-tab-count="${tabKey}"]`);

        if (!countNode) {
            return;
        }

        const current = Number((countNode.textContent || '0').replace(/[^\d-]/g, '')) || 0;
        countNode.textContent = String(Math.max(current + delta, 0));
    };

    const ensureEmptyState = () => {
        if (!tableBody) {
            return;
        }

        const rows = tableBody.querySelectorAll('tr[data-invoice-row]');
        const emptyRow = tableBody.querySelector('[data-empty-row]');

        if (rows.length === 0 && !emptyRow) {
            const tr = document.createElement('tr');
            tr.setAttribute('data-empty-row', '');
            tr.innerHTML = `<td colspan="7" class="py-12 text-center text-slate-400">Tidak ada invoice pada status {{ $tabMeta[$activeTab]['label'] }}.</td>`;
            tableBody.appendChild(tr);
        }
    };

    const openCancelModal = (form) => {
        pendingCancelForm = form;

        if (!cancelModal) {
            return;
        }

        cancelModal.classList.remove('hidden');
        cancelModal.classList.add('flex');

        if (cancelNotesField) {
            cancelNotesField.value = form.querySelector('input[name="notes"]')?.value || '';
            cancelNotesField.focus();
        }
    };

    const closeCancelModal = () => {
        pendingCancelForm = null;

        if (!cancelModal) {
            return;
        }

        cancelModal.classList.add('hidden');
        cancelModal.classList.remove('flex');

        if (cancelNotesField) {
            cancelNotesField.value = '';
        }
    };

    const formatCurrency = (value) => {
        const amount = Number(value || 0);

        return `Rp ${amount.toLocaleString('id-ID')}`;
    };

    const renderInvoiceDetailItems = (items = [], message = 'Tidak ada detail item.') => {
        if (!invoiceDetailItems) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            invoiceDetailItems.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">${message}</td></tr>`;
            return;
        }

        invoiceDetailItems.innerHTML = items.map((item) => `
            <tr>
                <td class="px-4 py-3 text-slate-700">${item.type || '-'}</td>
                <td class="px-4 py-3 text-slate-700">${item.desc || '-'}</td>
                <td class="px-4 py-3 text-right text-slate-700">${item.qty ?? '-'}</td>
                <td class="px-4 py-3 text-right text-slate-700">${formatCurrency(item.price)}</td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">${formatCurrency(item.subtotal)}</td>
            </tr>
        `).join('');
    };

    const setInvoiceDetailSummary = (summary = {}) => {
        if (!invoiceDetailModal) {
            return;
        }

        const subtotal = Number(summary.subtotal || 0);
        const discountAmount = Number(summary.discount_amount || 0);
        const grandTotal = Number(summary.grand_total || 0);
        const paid = Number(summary.paid || 0);
        const remaining = Number(summary.remaining || 0);

        invoiceDetailModal.querySelector('.js-invoice-detail-subtotal').textContent = formatCurrency(subtotal);
        invoiceDetailModal.querySelector('.js-invoice-detail-discount').textContent = formatCurrency(discountAmount);
        invoiceDetailModal.querySelector('.js-invoice-detail-grand-total').textContent = formatCurrency(grandTotal);
        invoiceDetailModal.querySelector('.js-invoice-detail-paid').textContent = formatCurrency(paid);
        invoiceDetailModal.querySelector('.js-invoice-detail-remaining').textContent = formatCurrency(remaining);
    };

    const openInvoiceDetailModal = (row) => {
        if (!invoiceDetailModal || !row) {
            return;
        }

        invoiceDetailModal.querySelector('.js-invoice-detail-number').textContent = row.dataset.invoiceNumber || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-date').textContent = row.dataset.invoiceDate || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-customer').textContent = row.dataset.invoiceCustomer || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-branch').textContent = row.dataset.invoiceBranch || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-machine').textContent = row.dataset.invoiceMachine || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-payment-status').textContent = row.dataset.invoicePaymentStatus || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-production-status').textContent = row.dataset.invoiceProductionStatus || '-';
        invoiceDetailModal.querySelector('.js-invoice-detail-notes').textContent = row.dataset.invoiceNotes || '-';
        renderInvoiceDetailItems([], 'Detail item belum dimuat.');
        setInvoiceDetailSummary();

        invoiceDetailModal.classList.remove('hidden');
        invoiceDetailModal.classList.add('flex');
    };

    const closeInvoiceDetailModal = () => {
        if (!invoiceDetailModal) {
            return;
        }

        invoiceDetailModal.classList.add('hidden');
        invoiceDetailModal.classList.remove('flex');
    };

    const submitStatusUpdate = async (form) => {
        const submitButton = form.querySelector('.js-status-submit');
        const invoiceId = form.dataset.invoiceId || '';
        const targetStatus = form.dataset.targetStatus || '';
        const row = pageRoot.querySelector(`[data-invoice-row="${invoiceId}"]`);

        setButtonLoading(submitButton, true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Gagal memperbarui status produksi.');
            }

            if (row) {
                row.remove();
            }

            adjustTabCount(activeTab, -1);
            adjustTabCount(targetStatus, 1);
            ensureEmptyState();

            window.toast.success(payload.message || 'Status produksi berhasil diperbarui.');
        } catch (error) {
            window.toast.error(error.message || 'Gagal memperbarui status produksi.');
        } finally {
            setButtonLoading(submitButton, false);
        }
    };

    cancelModalClose?.addEventListener('click', closeCancelModal);
    cancelModal?.addEventListener('click', (event) => {
        if (event.target === cancelModal) {
            closeCancelModal();
        }
    });
    invoiceDetailClose?.addEventListener('click', closeInvoiceDetailModal);
    invoiceDetailModal?.addEventListener('click', (event) => {
        if (event.target === invoiceDetailModal) {
            closeInvoiceDetailModal();
        }
    });

    cancelModalSubmit?.addEventListener('click', async () => {
        if (!pendingCancelForm) {
            closeCancelModal();
            return;
        }

        const notesInput = pendingCancelForm.querySelector('input[name="notes"]');

        if (notesInput) {
            notesInput.value = (cancelNotesField?.value || '').trim();
        }

        const formToSubmit = pendingCancelForm;
        closeCancelModal();
        await submitStatusUpdate(formToSubmit);
    });

    pageRoot.querySelectorAll('.js-status-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const targetStatus = form.dataset.targetStatus || '';

            if (targetStatus === 'cancelled') {
                openCancelModal(form);
                return;
            }

            await submitStatusUpdate(form);
        });
    });

    pageRoot.querySelectorAll('.js-invoice-detail-trigger').forEach((button) => {
        button.addEventListener('click', async () => {
            const invoiceId = button.dataset.invoiceId || '';
            const detailUrl = button.dataset.detailUrl || '';
            const row = pageRoot.querySelector(`[data-invoice-row="${invoiceId}"]`);
            openInvoiceDetailModal(row);

            if (!detailUrl) {
                return;
            }

            if (invoiceDetailLoading) {
                invoiceDetailLoading.classList.remove('hidden');
            }

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

                renderInvoiceDetailItems(payload.items || []);
                setInvoiceDetailSummary({
                    subtotal: payload.subtotal,
                    discount_amount: payload.discount_amount,
                    grand_total: payload.grand_total,
                    paid: payload.paid,
                    remaining: payload.remaining,
                });

                if (payload.notes) {
                    invoiceDetailModal.querySelector('.js-invoice-detail-notes').textContent = payload.notes;
                }
            } catch (error) {
                renderInvoiceDetailItems([], 'Gagal memuat detail item.');
                setInvoiceDetailSummary();
                window.toast.error(error.message || 'Gagal memuat detail invoice.');
            } finally {
                if (invoiceDetailLoading) {
                    invoiceDetailLoading.classList.add('hidden');
                }
            }
        });
    });
});
</script>
@endpush
