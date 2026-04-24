@extends('layouts.app')
@section('title', $invoice ? 'Edit Invoice' : 'Buat Invoice')
@section('page-title', $invoice ? 'Edit Invoice' : 'Buat Invoice Baru')

@php
    $branchOptions = collect($branches)->map(fn ($branch) => [
        'id' => $branch['id'],
        'name' => $branch['name'] ?? 'Cabang',
        'description' => $branch['city'] ?? '',
    ])->values();

    $initialItems = collect($invoice['items'] ?? [])->map(function ($item) {
        return [
            'product_type' => strtolower($item['product_type'] ?? ($item['type'] ?? 'cutting')),
            'cost_type_id' => $item['cost_type_id'] ?? null,
            'component_id' => $item['component_id'] ?? null,
            'plate_variant_id' => $item['plate_variant_id'] ?? null,
            'cutting_price_id' => $item['cutting_price_id'] ?? null,
            'pricing_mode' => $item['pricing_mode'] ?? null,
            'qty' => (int) ($item['qty'] ?? 1),
            'minutes' => (int) ($item['minutes'] ?? 0),
            'price' => (float) ($item['price'] ?? 0),
            'discount_pct' => (float) ($item['discount'] ?? 0),
        ];
    })->values();
@endphp

@section('content')
<div
    class="max-w-7xl mx-auto"
    x-data="invoiceWorkflow({
        isSuperAdmin: {{ $isSuperAdmin ? 'true' : 'false' }},
        initialStep: 1,
        initialInvoice: {{ \Illuminate\Support\Js::from($invoice) }},
        initialBranchId: {{ \Illuminate\Support\Js::from($initialBranchId ?? null) }},
        masterDataUrl: {{ \Illuminate\Support\Js::from(route('invoice.master-data')) }},
        initialItems: {{ \Illuminate\Support\Js::from($initialItems) }},
    })"
    x-init="init()"
>
    <form method="POST" action="{{ $invoice ? route('invoice.update', $invoice['id']) : route('invoice.store') }}" class="space-y-6" @submit="validateBeforeSubmit($event)">
        @csrf
        @if($invoice) @method('PUT') @endif

        <div class="card overflow-hidden">
            <div class="grid md:grid-cols-2">
                <div class="p-6 border-b md:border-b-0 md:border-r border-slate-100" :class="step === 1 ? 'bg-brand text-white' : 'bg-white'">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black" :class="step === 1 ? 'bg-white text-brand' : 'bg-brand/10 text-brand'">1</div>
                        <div>
                            <p class="font-bold text-lg">Transaksi</p>
                            <p class="text-sm" :class="step === 1 ? 'text-white/80' : 'text-slate-500'">Pilih pelanggan, mesin, dan item invoice.</p>
                        </div>
                    </div>
                </div>
                <div class="p-6" :class="step === 2 ? 'bg-brand text-white' : 'bg-white'">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black" :class="step === 2 ? 'bg-white text-brand' : 'bg-brand/10 text-brand'">2</div>
                        <div>
                            <p class="font-bold text-lg">Pembayaran</p>
                            <p class="text-sm" :class="step === 2 ? 'text-white/80' : 'text-slate-500'">Konfirmasi total dan input pembayaran awal bila ada.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="step === 1" class="space-y-6" style="display: none;">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="xl:col-span-2 card overflow-visible">
                        <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                            <h2 class="font-bold text-slate-900">Informasi Transaksi</h2>
                        </div>
                        <div class="card-body p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($isSuperAdmin)
                                <x-searchable-select
                                    name="branch_id"
                                    label="Cabang"
                                    :options="$branchOptions"
                                    :selected="$invoice['branch_id'] ?? request('branch_id')"
                                    placeholder="Cari cabang..."
                                    description-key="description"
                                    required
                                />
                            @endif

                            <div>
                                <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">Tanggal Transaksi</label>
                                <input type="date" name="transaction_date" class="form-input" value="{{ $invoice['date'] ?? date('Y-m-d') }}" required>
                            </div>

                            <div>
                                <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">Tipe Invoice</label>
                                <select name="invoice_type" x-model="invoiceType" @change="handleInvoiceTypeChanged()" class="form-input">
                                    <option value="sales">Penjualan Reguler</option>
                                    <option value="machine_order">Order Mesin</option>
                                    <option value="service_order">Order Jasa</option>
                                </select>
                            </div>

                            <div x-show="invoiceType === 'sales'" style="display: none;">
                                <x-searchable-select
                                    name="customer_id"
                                    label="Pelanggan"
                                    :options="[]"
                                    :selected="$invoice['customer_id'] ?? null"
                                    placeholder="Cari pelanggan..."
                                    description-key="description"
                                    required
                                />
                            </div>

                            <div x-show="invoiceType === 'sales'" style="display: none;">
                                <x-searchable-select
                                    name="machine_id"
                                    label="Mesin Produksi"
                                    :options="[]"
                                    :selected="$invoice['machine_id'] ?? null"
                                    placeholder="Cari mesin..."
                                    description-key="description"
                                />
                            </div>

                            <div x-show="invoiceType === 'machine_order'" style="display: none;">
                                <x-searchable-select
                                    name="machine_order_id"
                                    label="Nomor Order Mesin"
                                    :options="[]"
                                    :selected="null"
                                    placeholder="Cari nomor order mesin..."
                                    description-key="description"
                                />
                            </div>

                            <div x-show="invoiceType === 'service_order'" style="display: none;">
                                <x-searchable-select
                                    name="service_order_id"
                                    label="Nomor Order Jasa"
                                    :options="[]"
                                    :selected="null"
                                    placeholder="Cari nomor order jasa..."
                                    description-key="description"
                                />
                            </div>

                            <div x-show="invoiceType === 'sales'" class="md:col-span-2" style="display: none;">
                                <p class="text-xs text-slate-400 -mt-3">
                                    Mesin produksi bersifat opsional untuk semua jenis pesanan, termasuk plat dan jasa cutting.
                                </p>
                            </div>

                            <div x-show="invoiceType === 'machine_order' && selectedMachineOrder" class="md:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800" style="display: none;">
                                <div class="grid gap-2 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-emerald-600">Pelanggan</p>
                                        <p class="font-semibold" x-text="selectedMachineOrder?.customer_name || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-emerald-600">Mesin</p>
                                        <p class="font-semibold" x-text="selectedMachineOrder?.machine_name || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-emerald-600">Status Order</p>
                                        <p class="font-semibold" x-text="selectedMachineOrder?.status_label || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-emerald-600">Sisa Tagihan</p>
                                        <p class="font-semibold" x-text="currency(selectedMachineOrder?.remaining_total || 0)"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="invoiceType === 'service_order' && selectedServiceOrder" class="md:col-span-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-800" style="display: none;">
                                <div class="grid gap-2 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-sky-600">Pelanggan</p>
                                        <p class="font-semibold" x-text="selectedServiceOrder?.customer_name || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-sky-600">Jenis Order</p>
                                        <p class="font-semibold" x-text="selectedServiceOrder?.order_type_label || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-sky-600">Judul</p>
                                        <p class="font-semibold" x-text="selectedServiceOrder?.title || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-sky-600">Status</p>
                                        <p class="font-semibold" x-text="selectedServiceOrder?.status_label || '-'"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="masterDataLoading" class="md:col-span-2 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-700" style="display: none;">
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Sedang memuat data invoice</p>
                                        <p class="mt-1 text-xs text-blue-600">Pelanggan, mesin, plat, komponen, cutting price, order mesin, dan order jasa sedang disiapkan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="card overflow-hidden">
                            <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                                <h2 class="font-bold text-slate-900">Ringkasan</h2>
                            </div>
                            <div class="card-body p-6 space-y-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Jumlah item</span>
                                    <span class="font-semibold text-slate-900" x-text="items.length"></span>
                                </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Plat</span>
                                <span class="font-semibold text-slate-900" x-text="plateCount"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Component</span>
                                <span class="font-semibold text-slate-900" x-text="componentCount"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Biaya</span>
                                <span class="font-semibold text-slate-900" x-text="costTypeCount"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Cutting</span>
                                <span class="font-semibold text-slate-900" x-text="cuttingCount"></span>
                            </div>
                                <div class="border-t border-slate-100 pt-4 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Subtotal</span>
                                        <span class="font-mono font-semibold" x-text="currency(subtotal)"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4" x-show="invoiceType === 'sales'" style="display: none;">
                                        <label class="text-sm text-slate-500">Diskon global</label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="discount_pct" x-model.number="discountPct" @input="recalculateTotals()" min="0" max="100" class="form-input w-20 text-right">
                                            <span class="text-sm text-slate-400">%</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between text-sm text-red-600" x-show="invoiceType === 'sales'" style="display: none;">
                                        <span>Diskon</span>
                                        <span class="font-mono" x-text="currency(discountAmount)"></span>
                                    </div>
                                    <div class="flex justify-between text-lg font-black text-slate-900 border-t border-slate-100 pt-4">
                                        <span>Grand Total</span>
                                        <span class="font-mono" x-text="currency(grandTotal)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-body bg-brand/5 border border-brand/10">
                            <p class="text-sm font-semibold text-brand-dark">Status awal akan ditentukan otomatis.</p>
                            <p x-show="invoiceType === 'sales'" class="text-xs text-slate-500 mt-2" style="display: none;">
                                Item tanpa cutting akan masuk ke <strong>In-process</strong>, sedangkan invoice yang punya cutting akan masuk ke <strong>Pending</strong>.
                            </p>
                            <p x-show="invoiceType === 'machine_order'" class="text-xs text-slate-500 mt-2" style="display: none;">
                                Invoice order mesin dibuat dari snapshot machine order yang sudah selesai atau siap ditagihkan.
                            </p>
                            <p x-show="invoiceType === 'service_order'" class="text-xs text-slate-500 mt-2" style="display: none;">
                                Invoice order jasa dibuat dari order servis atau pelatihan yang sudah siap ditagihkan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card overflow-hidden">
                    <div class="card-header bg-slate-50/70 border-b border-slate-100 flex items-center justify-between py-4">
                        <div>
                            <h2 class="font-bold text-slate-900">Daftar Item</h2>
                            <p class="text-sm text-slate-500 mt-1" x-text="invoiceType === 'machine_order'
                                ? 'Item ditarik otomatis dari machine order yang dipilih.'
                                : (invoiceType === 'service_order'
                                    ? 'Item ditarik dari order jasa terpilih dan masih bisa disesuaikan.'
                                    : 'Tambah item dari master plat, component, tipe biaya, atau cutting price.')"></p>
                        </div>
                        <button type="button" x-show="invoiceType === 'sales'" @click="openItemModal()" class="btn btn-primary" style="display: none;">
                            <span>Tambah Item</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th class="w-28">Tipe</th>
                                    <th>Deskripsi</th>
                                    <th class="w-24 text-right">Qty</th>
                                    <th class="w-28 text-right">Menit</th>
                                    <th class="w-36 text-right">Harga</th>
                                    <th class="w-24 text-right">Disc %</th>
                                    <th class="w-40 text-right">Subtotal</th>
                                    <th class="w-16"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="item.product_type === 'plate'
                                                    ? 'badge-info'
                                                    : (item.product_type === 'component'
                                                        ? 'badge-success'
                                                        : (item.product_type === 'cost_type'
                                                            ? 'badge-warning'
                                                            : (item.product_type === 'machine_order'
                                                                ? 'badge-success'
                                                                : (item.product_type === 'service_order' ? 'badge-info' : 'badge-brand'))))"
                                                x-text="item.product_type === 'plate'
                                                    ? 'Plat'
                                                    : (item.product_type === 'component'
                                                        ? 'Component'
                                                        : (item.product_type === 'cost_type'
                                                            ? 'Biaya'
                                                            : (item.product_type === 'machine_order'
                                                                ? 'Order Mesin'
                                                                : (item.product_type === 'service_order' ? 'Order Jasa' : 'Cutting'))))"
                                            ></span>
                                        </td>
                                        <td>
                                            <div class="font-semibold text-slate-900" x-text="item.name"></div>
                                            <div class="text-xs text-slate-400" x-text="item.description"></div>
                                            <template x-if="invoiceType === 'sales'">
                                                <div>
                                                    <input type="hidden" :name="`items[${index}][product_type]`" :value="item.product_type">
                                                    <input type="hidden" :name="`items[${index}][cost_type_id]`" :value="item.cost_type_id || ''">
                                                    <input type="hidden" :name="`items[${index}][component_id]`" :value="item.component_id || ''">
                                                    <input type="hidden" :name="`items[${index}][plate_variant_id]`" :value="item.plate_variant_id || ''">
                                                    <input type="hidden" :name="`items[${index}][cutting_price_id]`" :value="item.cutting_price_id || ''">
                                                    <input type="hidden" :name="`items[${index}][pricing_mode]`" :value="item.pricing_mode || ''">
                                                </div>
                                            </template>
                                            <template x-if="invoiceType === 'service_order'">
                                                <div>
                                                    <input type="hidden" :name="`items[${index}][description]`" :value="[item.name, item.description].filter(Boolean).join(' - ')">
                                                    <input type="hidden" :name="`items[${index}][unit]`" :value="item.unit || 'jasa'">
                                                    <input type="hidden" :name="`items[${index}][source_component_id]`" :value="item.source_component_id || ''">
                                                </div>
                                            </template>
                                        </td>
                                        <td>
                                            <input type="number" min="1" class="form-input text-right" :name="`items[${index}][qty]`" x-model.number="item.qty" @input="recalculateItem(index)" :readonly="invoiceType === 'machine_order'">
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                min="0"
                                                class="form-input text-right"
                                                :name="`items[${index}][minutes]`"
                                                x-model.number="item.minutes"
                                                @input="recalculateItem(index)"
                                                :disabled="item.product_type !== 'cutting' || item.pricing_mode !== 'per-minute'"
                                                :readonly="invoiceType === 'machine_order'"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                min="0"
                                                class="form-input text-right"
                                                :class="!item.isPriceEditable ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                                                :name="`items[${index}][price]`"
                                                x-model.number="item.price"
                                                @input="recalculateItem(index)"
                                                :readonly="invoiceType === 'machine_order'"
                                            >
                                        </td>
                                        <td>
                                            <input type="number" min="0" max="100" class="form-input text-right" :name="`items[${index}][discount_pct]`" x-model.number="item.discount_pct" @input="recalculateItem(index)" :readonly="invoiceType === 'machine_order' || invoiceType === 'service_order'">
                                        </td>
                                        <td class="text-right font-mono font-semibold" x-text="currency(item.subtotal)"></td>
                                        <td class="text-center">
                                            <button type="button" x-show="invoiceType !== 'machine_order'" @click="removeItem(index)" class="text-red-500 hover:text-red-700" style="display: none;">Hapus</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="items.length === 0" class="px-6 py-16 text-center text-slate-400">
                        <span x-text="invoiceType === 'machine_order'
                            ? 'Pilih nomor order mesin terlebih dahulu untuk menampilkan item tagihan.'
                            : (invoiceType === 'service_order'
                                ? 'Pilih nomor order jasa terlebih dahulu untuk menampilkan item tagihan.'
                                : 'Belum ada item. Tambahkan plat, component, biaya, atau cutting dari tombol di atas.')"></span>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" class="btn btn-primary px-10" @click="goToPayment()">
                        Lanjut ke Pembayaran
                    </button>
                </div>
        </div>

        <div x-show="step === 2" class="grid grid-cols-1 xl:grid-cols-3 gap-6" style="display: none;">
                <div class="xl:col-span-2 space-y-6">
                    <div class="card overflow-hidden" x-show="invoiceType === 'sales'" style="display: none;">
                        <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                            <h2 class="font-bold text-slate-900">Pembayaran Awal</h2>
                        </div>
                        <div class="card-body p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">Jumlah Bayar</label>
                                <input type="hidden" name="payment[amount]" :value="payment.amount || 0">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    x-model="payment.amount_display"
                                    @input="handlePaymentAmountInput($event)"
                                    class="form-input"
                                    :disabled="isPayLater"
                                    :class="isPayLater ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                                    placeholder="0"
                                >
                                <p class="mt-2 text-xs text-slate-400" x-show="isPayLater">Invoice akan disimpan tanpa data pembayaran.</p>
                            </div>
                            <div>
                                <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">Tanggal Bayar</label>
                                <input
                                    type="date"
                                    name="payment[payment_date]"
                                    x-model="payment.payment_date"
                                    class="form-input"
                                    :disabled="isPayLater"
                                    :class="isPayLater ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                                >
                            </div>
                            <div>
                                <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">Metode Pembayaran</label>
                                <select name="payment[payment_method]" x-model="payment.payment_method" class="form-select" @change="syncPaymentFields()">
                                    <option value="Cash">Cash</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Pay Later">Pay Later</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">Jenis Pembayaran</label>
                                <select
                                    name="payment[is_dp]"
                                    x-model="payment.is_dp"
                                    class="form-select"
                                    :disabled="isPayLater"
                                    :class="isPayLater ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                                >
                                    <option value="1">Cicilan</option>
                                    <option value="0">Pelunasan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card overflow-hidden" x-show="invoiceType === 'machine_order'" style="display: none;">
                        <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                            <h2 class="font-bold text-slate-900">Konfirmasi Tagihan</h2>
                        </div>
                        <div class="card-body p-6">
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
                                <p class="font-semibold">Invoice mesin akan dibuat dari snapshot machine order terpilih.</p>
                                <p class="mt-2 text-xs text-emerald-700">
                                    Pembayaran lanjutan dicatat setelah invoice terbentuk melalui modul pembayaran, jadi form ini hanya dipakai untuk membuat tagihan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card overflow-hidden" x-show="invoiceType === 'service_order'" style="display: none;">
                        <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                            <h2 class="font-bold text-slate-900">Konfirmasi Tagihan</h2>
                        </div>
                        <div class="card-body p-6">
                            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-800">
                                <p class="font-semibold">Invoice order jasa akan dibuat dari order jasa terpilih.</p>
                                <p class="mt-2 text-xs text-sky-700">
                                    Jika item atau nominal perlu diubah, kembali ke langkah Transaksi sebelum invoice diterbitkan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card overflow-hidden">
                        <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                            <h2 class="font-bold text-slate-900">Konfirmasi Item</h2>
                        </div>
                        <div class="card-body p-6 space-y-3">
                            <template x-for="(item, index) in items" :key="`summary-${index}`">
                                <div class="flex items-start justify-between gap-4 pb-3 border-b border-slate-100 last:border-b-0">
                                    <div>
                                        <div class="font-semibold text-slate-900" x-text="item.name"></div>
                                        <div class="text-xs text-slate-400" x-text="item.description"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-slate-500" x-text="linePreview(item)"></div>
                                        <div class="font-mono font-semibold text-slate-900" x-text="currency(item.subtotal)"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card overflow-hidden">
                        <div class="card-header bg-slate-50/70 border-b border-slate-100 py-4">
                            <h2 class="font-bold text-slate-900">Total Akhir</h2>
                        </div>
                        <div class="card-body p-6 space-y-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Subtotal</span>
                                <span class="font-mono" x-text="currency(subtotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm text-red-600">
                                <span>Diskon</span>
                                <span class="font-mono" x-text="currency(discountAmount)"></span>
                            </div>
                            <div class="flex justify-between text-lg font-black text-slate-900 border-t border-slate-100 pt-4">
                                <span>Grand Total</span>
                                <span class="font-mono" x-text="currency(grandTotal)"></span>
                            </div>
                            <input type="hidden" name="grand_total" :value="grandTotal">
                            <div class="flex justify-between text-sm text-green-700" x-show="invoiceType === 'sales'" style="display: none;">
                                <span>Pembayaran awal</span>
                                <span class="font-mono" x-text="currency(payment.amount || 0)"></span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold text-slate-900">
                                <span>Sisa tagihan</span>
                                <span class="font-mono" x-text="invoiceType === 'machine_order' ? currency(grandTotal) : currency(Math.max(grandTotal - (payment.amount || 0), 0))"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="button" class="btn btn-outline" @click="step = 1">Kembali ke Transaksi</button>
                        <button type="submit" class="btn btn-primary">Simpan Invoice</button>
                        <a href="{{ route('invoice.index') }}" class="btn btn-outline text-center">Batal</a>
                    </div>
                </div>
        </div>
    </form>

    <div x-show="showItemModal" x-transition class="fixed inset-0 z-[80]" style="display: none;">
        <div class="absolute inset-0 bg-slate-950/50" @click="showItemModal = false"></div>
        <div class="relative h-full flex items-center justify-center p-4">
            <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">Tambah Item</h2>
                        <p class="text-sm text-slate-500 mt-1">Pilih dari katalog plat, component, tipe biaya, atau harga cutting.</p>
                    </div>
                    <button type="button" @click="showItemModal = false" class="text-slate-400 hover:text-slate-700">Tutup</button>
                </div>

                <div class="p-6 space-y-5">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex rounded-xl border border-slate-200 overflow-hidden">
                            <button type="button" class="px-4 py-2 text-sm font-semibold" @click="catalogTab = 'plate'" :class="catalogTab === 'plate' ? 'bg-brand text-white' : 'bg-white text-slate-600'">Plat</button>
                            <button type="button" class="px-4 py-2 text-sm font-semibold" @click="catalogTab = 'component'" :class="catalogTab === 'component' ? 'bg-brand text-white' : 'bg-white text-slate-600'">Component</button>
                            <button type="button" class="px-4 py-2 text-sm font-semibold" @click="catalogTab = 'cost_type'" :class="catalogTab === 'cost_type' ? 'bg-brand text-white' : 'bg-white text-slate-600'">Biaya</button>
                            <button type="button" class="px-4 py-2 text-sm font-semibold" @click="catalogTab = 'cutting'" :class="catalogTab === 'cutting' ? 'bg-brand text-white' : 'bg-white text-slate-600'">Cutting</button>
                        </div>
                        <input type="text" x-model="catalogQuery" class="form-input md:flex-1" placeholder="Cari item...">
                    </div>

                    <div class="max-h-[28rem] overflow-auto rounded-2xl border border-slate-200">
                        <table x-show="catalogTab === 'plate'" class="table-base min-w-full" style="display: none;">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th>Jenis Plat</th>
                                    <th>Size</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="catalogItem in filteredCatalog" :key="`${catalogTab}-${catalogItem.id}`">
                                    <tr class="cursor-pointer hover:bg-brand/5" @click="addCatalogItem(catalogItem)">
                                        <td class="font-semibold text-slate-900" x-text="catalogItem.plate_type_name"></td>
                                        <td class="text-slate-600" x-text="catalogItem.size_value"></td>
                                        <td class="text-right text-slate-600" x-text="Number(catalogItem.stock_qty || 0).toLocaleString('id-ID')"></td>
                                        <td class="text-right font-medium text-slate-900" x-text="currency(catalogItem.price)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <table x-show="catalogTab === 'component'" class="table-base min-w-full" style="display: none;">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th>Nama</th>
                                    <th>Ukuran/Size</th>
                                    <th class="text-right">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="catalogItem in filteredCatalog" :key="`${catalogTab}-${catalogItem.id}`">
                                    <tr class="cursor-pointer hover:bg-brand/5" @click="addCatalogItem(catalogItem)">
                                        <td class="font-semibold text-slate-900" x-text="catalogItem.component_name"></td>
                                        <td class="text-slate-600" x-text="catalogItem.type_size"></td>
                                        <td class="text-right font-medium text-slate-900" x-text="currency(catalogItem.price)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <table x-show="catalogTab === 'cost_type'" class="table-base min-w-full" style="display: none;">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th>Nama Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="catalogItem in filteredCatalog" :key="`${catalogTab}-${catalogItem.id}`">
                                    <tr class="cursor-pointer hover:bg-brand/5" @click="addCatalogItem(catalogItem)">
                                        <td class="font-semibold text-slate-900" x-text="catalogItem.name"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <table x-show="catalogTab === 'cutting'" class="table-base min-w-full" style="display: none;">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th>Jenis Plat</th>
                                    <th>Size</th>
                                    <th>Jenis Mesin</th>
                                    <th class="text-right">Easy</th>
                                    <th class="text-right">Medium</th>
                                    <th class="text-right">Difficult</th>
                                    <th class="text-right">Per Menit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="catalogItem in filteredCuttingRows" :key="`${catalogTab}-${catalogItem.id}`">
                                    <tr class="hover:bg-slate-50">
                                        <td class="font-semibold text-slate-900" x-text="catalogItem.plate_type_name"></td>
                                        <td class="text-slate-600" x-text="catalogItem.size_value"></td>
                                        <td class="text-slate-600" x-text="catalogItem.machine_type_name"></td>
                                        <td class="text-right">
                                            <button type="button" class="font-medium text-brand hover:underline" @click="addCatalogItem(catalogItem.easy)" @mouseenter="showTooltip($event, cuttingTooltipText(catalogItem, 'easy'))" @mousemove="moveTooltip($event)" @mouseleave="hideTooltip()" x-show="catalogItem.easy" x-text="currency(catalogItem.easy?.price || 0)"></button>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="font-medium text-brand hover:underline" @click="addCatalogItem(catalogItem.medium)" @mouseenter="showTooltip($event, cuttingTooltipText(catalogItem, 'medium'))" @mousemove="moveTooltip($event)" @mouseleave="hideTooltip()" x-show="catalogItem.medium" x-text="currency(catalogItem.medium?.price || 0)"></button>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="font-medium text-brand hover:underline" @click="addCatalogItem(catalogItem.difficult)" @mouseenter="showTooltip($event, cuttingTooltipText(catalogItem, 'difficult'))" @mousemove="moveTooltip($event)" @mouseleave="hideTooltip()" x-show="catalogItem.difficult" x-text="currency(catalogItem.difficult?.price || 0)"></button>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="font-medium text-brand hover:underline" @click="addCatalogItem(catalogItem['per-minute'])" @mouseenter="showTooltip($event, cuttingTooltipText(catalogItem, 'per-minute'))" @mousemove="moveTooltip($event)" @mouseleave="hideTooltip()" x-show="catalogItem['per-minute']" x-text="`${currency(catalogItem['per-minute']?.price || 0)}/menit`"></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div
                            x-show="(catalogTab === 'cutting' ? filteredCuttingRows.length : filteredCatalog.length) === 0"
                            class="px-4 py-8 text-center text-sm text-slate-400"
                            style="display: none;"
                        >
                            Tidak ada data yang cocok.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            x-show="tooltip.visible"
            x-transition.opacity.duration.100ms
            class="pointer-events-none fixed z-[90] max-w-xs rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white shadow-2xl"
            :style="`left:${tooltip.x}px; top:${tooltip.y}px;`"
            style="display: none;"
            x-text="tooltip.text"
        ></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function invoiceWorkflow(config) {
    return {
        step: config.initialStep || 1,
        isSuperAdmin: config.isSuperAdmin || false,
        showItemModal: false,
        catalogTab: 'plate',
        catalogQuery: '',
        masterDataUrl: config.masterDataUrl || '',
        initialBranchId: config.initialBranchId || '',
        invoiceType: config.initialInvoice?.invoice_type || 'sales',
        masterDataLoading: false,
        masterDataLoaded: false,
        masterDataBranchId: '',
        tooltip: {
            visible: false,
            text: '',
            x: 0,
            y: 0,
        },
        customerCatalog: [],
        machineCatalog: [],
        machineOrderCatalog: [],
        selectedMachineOrder: null,
        serviceOrderCatalog: [],
        selectedServiceOrder: null,
        plateCatalog: [],
        componentCatalog: [],
        costTypeCatalog: [],
        cuttingCatalog: [],
        items: [],
        discountPct: Number(config.initialInvoice?.discount_pct || 0),
        subtotal: 0,
        discountAmount: 0,
        grandTotal: 0,
        payment: {
            amount: 0,
            amount_display: '',
            payment_method: 'Cash',
            payment_date: config.initialInvoice?.date || {{ \Illuminate\Support\Js::from(date('Y-m-d')) }},
            is_dp: '1',
        },

        async init() {
            const initialItems = config.initialItems || [];
            this.items = initialItems.map((item) => this.normalizeInitialItem(item));
            this.syncPaymentFields();
            this.recalculateTotals();
            this.syncSearchableOptions();

            this.$nextTick(() => {
                this.registerBranchListener();
                this.registerMachineOrderListener();
                this.registerServiceOrderListener();
            });

            const initialBranchId = this.getSelectedBranchId();

            if (initialBranchId) {
                await this.loadMasterData(initialBranchId);
            }
        },

        get isPayLater() {
            return this.payment.payment_method === 'Pay Later';
        },

        get isMachineOrderInvoice() {
            return this.invoiceType === 'machine_order';
        },

        get isServiceOrderInvoice() {
            return this.invoiceType === 'service_order';
        },

        get currentCatalog() {
            if (this.catalogTab === 'plate') {
                return this.plateCatalog;
            }

            if (this.catalogTab === 'component') {
                return this.componentCatalog;
            }

            if (this.catalogTab === 'cost_type') {
                return this.costTypeCatalog;
            }

            return this.cuttingCatalog;
        },

        get filteredCatalog() {
            const keyword = this.catalogQuery.toLowerCase().trim();

            if (!keyword) {
                return this.currentCatalog;
            }

            return this.currentCatalog.filter((item) => {
                return [item.name, item.description]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(keyword));
            });
        },

        get filteredCuttingRows() {
            const keyword = this.catalogQuery.toLowerCase().trim();
            const grouped = new Map();

            this.cuttingCatalog.forEach((item) => {
                const rowId = item.cutting_price_id;

                if (!grouped.has(rowId)) {
                    grouped.set(rowId, {
                        id: rowId,
                        plate_type_name: item.plate_type_name || 'Plat',
                        size_value: item.size_value || '-',
                        machine_type_name: item.machine_type_name || '',
                        easy: null,
                        medium: null,
                        difficult: null,
                        'per-minute': null,
                    });
                }

                grouped.get(rowId)[item.pricing_mode] = item;
            });

            const rows = Array.from(grouped.values());

            if (!keyword) {
                return rows;
            }

            return rows.filter((row) => {
                return [
                    row.plate_type_name,
                    row.size_value,
                    row.machine_type_name,
                    row.easy?.name,
                    row.medium?.name,
                    row.difficult?.name,
                    row['per-minute']?.name,
                ].filter(Boolean).some((value) => String(value).toLowerCase().includes(keyword));
            });
        },

        cuttingTooltipText(catalogItem, mode) {
            const labels = {
                easy: 'easy',
                medium: 'medium',
                difficult: 'difficult',
                'per-minute': 'per menit',
            };

            return `Pilih ini untuk menambahkan cutting ${labels[mode] || mode} ${catalogItem.plate_type_name || 'plat'} ukuran ${catalogItem.size_value || '-'} dengan mesin ${catalogItem.machine_type_name || '-'}.`;
        },

        showTooltip(event, text) {
            this.tooltip.visible = true;
            this.tooltip.text = text;
            this.moveTooltip(event);
        },

        moveTooltip(event) {
            const offset = 14;
            this.tooltip.x = Math.min(event.clientX + offset, window.innerWidth - 280);
            this.tooltip.y = Math.max(event.clientY + offset, 12);
        },

        hideTooltip() {
            this.tooltip.visible = false;
        },

        get plateCount() {
            return this.items.filter((item) => item.product_type === 'plate').length;
        },

        get componentCount() {
            return this.items.filter((item) => item.product_type === 'component').length;
        },

        get costTypeCount() {
            return this.items.filter((item) => item.product_type === 'cost_type').length;
        },

        get cuttingCount() {
            return this.items.filter((item) => item.product_type === 'cutting').length;
        },

        async openItemModal() {
            if (this.isMachineOrderInvoice) {
                window.toast.error('Item order mesin ditarik otomatis dari nomor order yang dipilih.');
                return;
            }

            if (this.isServiceOrderInvoice) {
                window.toast.error('Item order jasa ditarik dari order jasa yang dipilih.');
                return;
            }

            const branchId = this.getSelectedBranchId();

            if (this.isSuperAdmin && !branchId) {
                window.toast.error('Pilih cabang terlebih dahulu sebelum menambah item.');
                return;
            }

            await this.ensureMasterDataLoaded(branchId);
            this.showItemModal = true;
        },

        registerBranchListener() {
            const branchInput = document.querySelector('input[name="branch_id"]');

            if (!branchInput) {
                return;
            }

            branchInput.addEventListener('change', async (event) => {
                await this.handleBranchChanged(event.target.value || '');
            });
        },

        getSelectedBranchId() {
            const branchInput = document.querySelector('input[name="branch_id"]');
            return branchInput?.value || this.initialBranchId || '';
        },

        dispatchSearchableUpdate(name, options = null, selected = undefined) {
            window.dispatchEvent(new CustomEvent('searchable-select:update', {
                detail: {
                    name,
                    options,
                    selected,
                },
            }));
        },

        syncSearchableOptions() {
            this.dispatchSearchableUpdate('customer_id', this.customerCatalog);
            this.dispatchSearchableUpdate('machine_id', this.machineCatalog);
            this.dispatchSearchableUpdate('machine_order_id', this.machineOrderCatalog);
            this.dispatchSearchableUpdate('service_order_id', this.serviceOrderCatalog);
        },

        clearSelectedField(name) {
            this.dispatchSearchableUpdate(name, null, '');
        },

        resetMasterData() {
            this.customerCatalog = [];
            this.machineCatalog = [];
            this.plateCatalog = [];
            this.componentCatalog = [];
            this.costTypeCatalog = [];
            this.cuttingCatalog = [];
            this.machineOrderCatalog = [];
            this.serviceOrderCatalog = [];
            this.selectedMachineOrder = null;
            this.selectedServiceOrder = null;
            this.masterDataLoaded = false;
            this.masterDataBranchId = '';
            this.syncSearchableOptions();
        },

        async handleBranchChanged(branchId) {
            const normalizedBranchId = branchId || '';

            if (!normalizedBranchId) {
                this.resetMasterData();
                this.items = [];
                this.recalculateTotals();
                this.clearSelectedField('customer_id');
                this.clearSelectedField('machine_id');
                this.clearSelectedField('machine_order_id');
                this.clearSelectedField('service_order_id');
                return;
            }

            if (String(normalizedBranchId) === String(this.masterDataBranchId) && this.masterDataLoaded) {
                return;
            }

            this.items = [];
            this.selectedMachineOrder = null;
            this.selectedServiceOrder = null;
            this.recalculateTotals();
            this.clearSelectedField('customer_id');
            this.clearSelectedField('machine_id');
            this.clearSelectedField('machine_order_id');
            this.clearSelectedField('service_order_id');
            await this.loadMasterData(normalizedBranchId);
        },

        async ensureMasterDataLoaded(branchId = '') {
            const normalizedBranchId = branchId || this.getSelectedBranchId();

            if (!normalizedBranchId && this.isSuperAdmin) {
                return;
            }

            if (this.masterDataLoaded && String(this.masterDataBranchId || '') === String(normalizedBranchId || '')) {
                return;
            }

            await this.loadMasterData(normalizedBranchId);
        },

        async loadMasterData(branchId = '') {
            this.masterDataLoading = true;

            try {
                const url = new URL(this.masterDataUrl, window.location.origin);

                if (branchId) {
                    url.searchParams.set('branch_id', branchId);
                }

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Gagal memuat data invoice.');
                }

                const data = await response.json();
                this.customerCatalog = this.mapCustomers(data.customers || []);
                this.machineCatalog = this.mapMachines(data.machines || []);
                this.plateCatalog = this.mapPlateVariants(data.plate_variants || []);
                this.componentCatalog = this.mapComponents(data.components || []);
                this.costTypeCatalog = this.mapCostTypes(data.cost_types || []);
                this.cuttingCatalog = this.mapCuttingPrices(data.cutting_prices || []);
                this.machineOrderCatalog = this.mapMachineOrders(data.machine_orders || []);
                this.serviceOrderCatalog = this.mapServiceOrders(data.service_orders || []);
                this.masterDataLoaded = true;
                this.masterDataBranchId = branchId || '';
                this.syncSearchableOptions();
            } catch (error) {
                this.resetMasterData();
                window.toast.error('Data invoice gagal dimuat. Silakan coba lagi.');
            } finally {
                this.masterDataLoading = false;
            }
        },

        mapCustomers(customers) {
            return customers.map((customer) => ({
                id: customer.id,
                name: customer.full_name || 'Tanpa Nama',
                description: [customer.phone_number, customer.branch?.name]
                    .filter(Boolean)
                    .join(' - '),
            }));
        },

        mapMachines(machines) {
            return machines.map((machine) => ({
                id: machine.id,
                name: machine.machine_number || 'Mesin',
                description: [machine.type?.name, machine.is_active ? 'Aktif' : 'Nonaktif']
                    .filter(Boolean)
                    .join(' - '),
            }));
        },

        mapMachineOrders(machineOrders) {
            return machineOrders.map((order) => {
                const previewItems = [
                    {
                        product_type: 'machine_order',
                        qty: 1,
                        minutes: 0,
                        price: Number(order.remaining_total || 0),
                        base_price: Number(order.remaining_total || 0),
                        discount_pct: 0,
                        subtotal: Number(order.remaining_total || 0),
                        name: `Tagihan ${order.order_number || 'Order Mesin'}`,
                        description: [
                            order.machine_name || 'Mesin',
                            `Total order ${this.currency(order.grand_total || 0)}`,
                            `Sudah dibayar ${this.currency(order.paid_total || 0)}`,
                        ].filter(Boolean).join(' - '),
                        isPriceEditable: false,
                    },
                ];

                return {
                    id: order.id,
                    name: order.order_number || 'Order Mesin',
                    description: [
                        order.customer_name,
                        order.machine_name,
                        `Sisa ${this.currency(order.remaining_total || 0)}`,
                    ].filter(Boolean).join(' - '),
                    order_number: order.order_number,
                    customer_id: order.customer_id,
                    customer_name: order.customer_name,
                    machine_id: order.machine_id,
                    machine_name: order.machine_name,
                    status: order.status,
                    status_label: this.formatMachineOrderStatus(order.status),
                    subtotal: Number(order.subtotal || 0),
                    additional_cost_total: Number(order.additional_cost_total || 0),
                    grand_total: Number(order.grand_total || 0),
                    paid_total: Number(order.paid_total || 0),
                    remaining_total: Number(order.remaining_total || 0),
                    previewItems,
                };
            });
        },

        mapServiceOrders(serviceOrders) {
            return serviceOrders.map((order) => {
                const serviceLabel = order.order_type === 'training' ? 'Pelatihan' : 'Servis';
                const previewItems = [
                    {
                        product_type: 'service_order',
                        qty: 1,
                        minutes: 0,
                        price: 0,
                        base_price: 0,
                        discount_pct: 0,
                        subtotal: 0,
                        unit: 'jasa',
                        source_component_id: null,
                        name: `${serviceLabel}: ${order.title || order.order_number || 'Order Jasa'}`,
                        description: [order.order_number, order.category, order.location].filter(Boolean).join(' - '),
                        isPriceEditable: true,
                    },
                ];

                if (order.order_type === 'service') {
                    (order.components || []).filter((component) => component.billable !== false).forEach((component) => {
                        previewItems.push({
                            product_type: 'service_order',
                            qty: Number(component.qty || 1),
                            minutes: 0,
                            price: 0,
                            base_price: 0,
                            discount_pct: 0,
                            subtotal: 0,
                            unit: 'pcs',
                            source_component_id: component.id,
                            name: `Komponen: ${component.component_name || 'Komponen'}`,
                            description: component.notes || '',
                            isPriceEditable: true,
                        });
                    });
                }

                return {
                    id: order.id,
                    name: order.order_number || 'Order Jasa',
                    description: [order.customer_name, serviceLabel, order.title].filter(Boolean).join(' - '),
                    order_number: order.order_number,
                    customer_id: order.customer_id,
                    customer_name: order.customer_name,
                    order_type: order.order_type,
                    order_type_label: serviceLabel,
                    title: order.title,
                    status: order.status,
                    status_label: this.formatServiceOrderStatus(order.status),
                    previewItems,
                };
            });
        },

        mapPlateVariants(variants) {
            return variants.map((variant) => ({
                id: variant.id,
                name: `${variant.plate_type_name || 'Plat'} - ${variant.size_value || '-'}`.trim(),
                plate_type_name: variant.plate_type_name || 'Plat',
                size_value: variant.size_value || '-',
                branch_id: variant.branch_id ?? null,
                is_active: variant.is_active,
                description: [
                    variant.price_sell != null ? `Rp ${Number(variant.price_sell || 0).toLocaleString('id-ID')}` : null,
                    `Stok ${Number(variant.qty || 0)}`,
                    variant.branch_name,
                ].filter(Boolean).join(' - '),
                product_type: 'plate',
                plate_variant_id: variant.id,
                cutting_price_id: null,
                pricing_mode: null,
                price: Number(variant.price_sell || 0),
                minutes: 0,
                qty: 1,
                discount_pct: 0,
                stock_qty: Number(variant.qty || 0),
            }));
        },

        mapComponents(components) {
            return components.map((component) => ({
                id: component.id,
                name: `${component.name || 'Component'}${component.type_size ? ` - ${component.type_size}` : ''}`.trim(),
                component_name: component.name || 'Component',
                type_size: component.type_size || '-',
                deleted_at: component.deleted_at ?? null,
                description: [
                    component.price_sell != null ? `Rp ${Number(component.price_sell || 0).toLocaleString('id-ID')}` : null,
                    `Stok ${Number(component.qty || 0)}`,
                    component.branch_name,
                ].filter(Boolean).join(' - '),
                product_type: 'component',
                component_id: component.id,
                plate_variant_id: null,
                cutting_price_id: null,
                pricing_mode: null,
                price: Number(component.price_sell || 0),
                minutes: 0,
                qty: 1,
                discount_pct: 0,
                stock_qty: Number(component.qty || 0),
            }));
        },

        mapCostTypes(costTypes) {
            return costTypes.map((costType) => ({
                id: costType.id,
                name: costType.name || 'Biaya',
                description: costType.description || 'Nominal diinput manual',
                product_type: 'cost_type',
                cost_type_id: costType.id,
                component_id: null,
                plate_variant_id: null,
                cutting_price_id: null,
                pricing_mode: null,
                price: 0,
                minutes: 0,
                qty: 1,
                discount_pct: 0,
                stock_qty: null,
            }));
        },

        mapCuttingPrices(cuttingPrices) {
            return cuttingPrices.flatMap((price) => {
                const baseName = `${price.machine_type?.name || 'Cutting'} - ${price.plate_type?.name || '-'} - ${price.size?.value || '-'}`.trim();
                const discount = Number(price.discount_pct || 0);

                return [
                    { mode: 'easy', label: 'Easy', price: Number(price.price_easy || 0), minutes: 0, helper: 'Harga fix' },
                    { mode: 'medium', label: 'Medium', price: Number(price.price_medium || 0), minutes: 0, helper: 'Harga fix' },
                    { mode: 'difficult', label: 'Difficult', price: Number(price.price_difficult || 0), minutes: 0, helper: 'Harga fix' },
                    { mode: 'per-minute', label: 'Per Menit', price: Number(price.price_per_minute || 0), minutes: 1, helper: 'Butuh input menit' },
                ].map((mode) => ({
                    id: `${price.id}-${mode.mode}`,
                    name: `${baseName} - ${mode.label}`,
                    machine_type_name: price.machine_type?.name || 'Cutting',
                    plate_type_name: price.plate_type?.name || '-',
                    size_value: price.size?.value || '-',
                    is_active: price.is_active,
                    machine_type_is_active: price.machine_type?.is_active,
                    description: [
                        `Rp ${Number(mode.price || 0).toLocaleString('id-ID')}${mode.mode === 'per-minute' ? '/menit' : ''}`,
                        mode.helper,
                        discount > 0 ? `Disc ${discount}%` : null,
                    ].filter(Boolean).join(' - '),
                    product_type: 'cutting',
                    plate_variant_id: null,
                    cutting_price_id: price.id,
                    pricing_mode: mode.mode,
                    price: mode.price,
                    minutes: mode.minutes,
                    qty: 1,
                    discount_pct: discount,
                    stock_qty: null,
                }));
            });
        },

        addCatalogItem(catalogItem) {
            const item = {
                product_type: catalogItem.product_type,
                cost_type_id: catalogItem.cost_type_id || null,
                component_id: catalogItem.component_id || null,
                plate_variant_id: catalogItem.plate_variant_id || null,
                cutting_price_id: catalogItem.cutting_price_id || null,
                pricing_mode: catalogItem.pricing_mode || null,
                qty: Number(catalogItem.qty || 1),
                minutes: catalogItem.product_type === 'cutting' ? Number(catalogItem.minutes || 0) : 0,
                price: Number(catalogItem.price || 0),
                base_price: Number(catalogItem.price || 0),
                discount_pct: Number(catalogItem.discount_pct || 0),
                name: catalogItem.name,
                description: catalogItem.description,
                stock_qty: catalogItem.stock_qty,
            };

            this.decorateItem(item);
            this.items.push(item);
            this.showItemModal = false;
            this.recalculateTotals();
        },

        normalizeInitialItem(item) {
            const productType = item.product_type === 'plate'
                ? 'plate'
                : (item.product_type === 'component'
                    ? 'component'
                    : (item.product_type === 'cost_type'
                        ? 'cost_type'
                        : (item.product_type === 'machine_order' ? 'machine_order' : 'cutting')));
            const source = productType === 'plate'
                ? this.plateCatalog.find((entry) => String(entry.plate_variant_id) === String(item.plate_variant_id))
                : (productType === 'component'
                    ? this.componentCatalog.find((entry) => String(entry.component_id) === String(item.component_id))
                    : (productType === 'cost_type'
                        ? this.costTypeCatalog.find((entry) => String(entry.cost_type_id) === String(item.cost_type_id))
                        : this.cuttingCatalog.find((entry) =>
                            String(entry.cutting_price_id) === String(item.cutting_price_id) &&
                            String(entry.pricing_mode || '') === String(item.pricing_mode || '')
                        )));

            const normalized = {
                product_type: productType,
                cost_type_id: item.cost_type_id || null,
                component_id: item.component_id || null,
                plate_variant_id: item.plate_variant_id || null,
                cutting_price_id: item.cutting_price_id || null,
                pricing_mode: item.pricing_mode || source?.pricing_mode || null,
                qty: Number(item.qty || 1),
                minutes: productType === 'cutting' ? Number(item.minutes || 0) : 0,
                price: Number(item.price || source?.price || 0),
                base_price: Number(source?.price || item.price || 0),
                discount_pct: Number(item.discount_pct || 0),
                name: source?.name || item.desc || 'Item',
                description: source?.description || item.desc || '',
                stock_qty: source?.stock_qty ?? null,
            };

            this.decorateItem(normalized);
            return normalized;
        },

        decorateItem(item) {
            item.isPriceEditable = Boolean(item.isPriceEditable)
                || item.product_type === 'cost_type'
                || (item.product_type === 'cutting' && item.pricing_mode === 'per-minute');

            if (!item.isPriceEditable) {
                item.price = Number(item.base_price ?? item.price ?? 0);
            }

            item.minutes = item.product_type === 'cutting' && item.pricing_mode === 'per-minute'
                ? Number(item.minutes || 0)
                : 0;

            item.effectiveUnitPrice = item.product_type === 'cutting' && item.pricing_mode === 'per-minute'
                ? Number(item.price || 0) * Math.max(Number(item.minutes || 0), 0)
                : Number(item.price || 0);

            item.subtotal = this.calculateItemSubtotal(item);
        },

        calculateItemSubtotal(item) {
            const qty = Math.max(Number(item.qty || 0), 0);
            const lineBase = qty * (
                item.product_type === 'cutting' && item.pricing_mode === 'per-minute'
                    ? Number(item.price || 0) * Math.max(Number(item.minutes || 0), 0)
                    : Number(item.price || 0)
            );
            const discount = lineBase * (Math.max(Number(item.discount_pct || 0), 0) / 100);

            return Math.max(lineBase - discount, 0);
        },

        recalculateItem(index) {
            const item = this.items[index];
            this.decorateItem(item);
            this.recalculateTotals();
        },

        removeItem(index) {
            this.items.splice(index, 1);
            this.recalculateTotals();
        },

        recalculateTotals() {
            this.items.forEach((item) => this.decorateItem(item));
            this.subtotal = this.items.reduce((total, item) => total + Number(item.subtotal || 0), 0);
            this.discountAmount = this.subtotal * (Math.max(Number(this.discountPct || 0), 0) / 100);
            this.grandTotal = Math.max(this.subtotal - this.discountAmount, 0);
        },

        handleInvoiceTypeChanged() {
            this.items = [];
            this.selectedMachineOrder = null;
            this.selectedServiceOrder = null;
            this.discountPct = 0;
            this.step = 1;

            if (this.isMachineOrderInvoice) {
                this.payment.payment_method = 'Pay Later';
                this.payment.amount = 0;
                this.payment.amount_display = '';
                this.payment.is_dp = '1';
                this.clearSelectedField('customer_id');
                this.clearSelectedField('machine_id');
                this.clearSelectedField('machine_order_id');
                this.clearSelectedField('service_order_id');
            } else if (this.isServiceOrderInvoice) {
                this.payment.payment_method = 'Pay Later';
                this.payment.amount = 0;
                this.payment.amount_display = '';
                this.payment.is_dp = '1';
                this.clearSelectedField('customer_id');
                this.clearSelectedField('machine_id');
                this.clearSelectedField('machine_order_id');
                this.clearSelectedField('service_order_id');
            } else {
                this.clearSelectedField('machine_order_id');
                this.clearSelectedField('service_order_id');
                this.syncPaymentFields();
            }

            this.recalculateTotals();
        },

        registerMachineOrderListener() {
            const machineOrderInput = document.querySelector('input[name="machine_order_id"]');

            if (!machineOrderInput) {
                return;
            }

            machineOrderInput.addEventListener('change', (event) => {
                this.handleMachineOrderChanged(event.target.value || '');
            });
        },

        registerServiceOrderListener() {
            const serviceOrderInput = document.querySelector('input[name="service_order_id"]');

            if (!serviceOrderInput) {
                return;
            }

            serviceOrderInput.addEventListener('change', (event) => {
                this.handleServiceOrderChanged(event.target.value || '');
            });
        },

        handleMachineOrderChanged(machineOrderId) {
            if (!machineOrderId) {
                this.selectedMachineOrder = null;
                this.items = [];
                this.discountPct = 0;
                this.recalculateTotals();
                return;
            }

            const selected = this.machineOrderCatalog.find((order) => String(order.id) === String(machineOrderId)) || null;
            this.selectedMachineOrder = selected;
            this.items = (selected?.previewItems || []).map((item) => ({ ...item }));
            this.discountPct = 0;
            this.recalculateTotals();
        },

        handleServiceOrderChanged(serviceOrderId) {
            if (!serviceOrderId) {
                this.selectedServiceOrder = null;
                this.items = [];
                this.discountPct = 0;
                this.recalculateTotals();
                return;
            }

            const selected = this.serviceOrderCatalog.find((order) => String(order.id) === String(serviceOrderId)) || null;
            this.selectedServiceOrder = selected;
            this.items = (selected?.previewItems || []).map((item) => ({ ...item }));
            this.discountPct = 0;
            this.recalculateTotals();
        },

        syncPaymentFields() {
            if (this.isMachineOrderInvoice) {
                this.payment.payment_method = 'Pay Later';
                this.payment.amount = 0;
                this.payment.amount_display = '';
                this.payment.payment_date = '';
                this.payment.is_dp = '1';
                return;
            }

            if (this.isServiceOrderInvoice) {
                this.payment.payment_method = 'Pay Later';
                this.payment.amount = 0;
                this.payment.amount_display = '';
                this.payment.payment_date = '';
                this.payment.is_dp = '1';
                return;
            }

            if (!this.isPayLater) {
                this.payment.payment_date = this.payment.payment_date || (config.initialInvoice?.date || {{ \Illuminate\Support\Js::from(date('Y-m-d')) }});
                this.payment.amount_display = this.formatNumberInput(this.payment.amount);
                return;
            }

            this.payment.amount = 0;
            this.payment.amount_display = '';
            this.payment.payment_date = '';
            this.payment.is_dp = '1';
        },

        handlePaymentAmountInput(event) {
            const numericValue = this.parseNumberInput(event.target.value);
            this.payment.amount = numericValue;
            this.payment.amount_display = this.formatNumberInput(numericValue);
        },

        goToPayment() {
            if (this.isMachineOrderInvoice) {
                const machineOrderInput = document.querySelector('input[name="machine_order_id"]');

                if (!machineOrderInput?.value) {
                    window.toast.error('Pilih nomor order mesin terlebih dahulu.');
                    return;
                }

                this.step = 2;
                return;
            }

            if (this.isServiceOrderInvoice) {
                const serviceOrderInput = document.querySelector('input[name="service_order_id"]');

                if (!serviceOrderInput?.value) {
                    window.toast.error('Pilih nomor order jasa terlebih dahulu.');
                    return;
                }

                if (this.items.length === 0) {
                    window.toast.error('Item order jasa belum tersedia.');
                    return;
                }

                this.step = 2;
                return;
            }

            if (this.items.length === 0) {
                window.toast.error('Tambahkan minimal satu item sebelum lanjut.');
                return;
            }

            const customerInput = document.querySelector('input[name="customer_id"]');

            if (!customerInput?.value) {
                window.toast.error('Pilih pelanggan terlebih dahulu.');
                return;
            }

            if (this.items.some((item) => item.product_type === 'cutting' && item.pricing_mode === 'per-minute' && !item.minutes)) {
                window.toast.error('Isi jumlah menit untuk item cutting per-menit.');
                return;
            }

            this.step = 2;
        },

        validateBeforeSubmit(event) {
            if (this.isMachineOrderInvoice) {
                const machineOrderInput = document.querySelector('input[name="machine_order_id"]');

                if (!machineOrderInput?.value) {
                    event.preventDefault();
                    window.toast.error('Pilih nomor order mesin terlebih dahulu.');
                    return false;
                }

                return true;
            }

            if (this.isServiceOrderInvoice) {
                const serviceOrderInput = document.querySelector('input[name="service_order_id"]');

                if (!serviceOrderInput?.value) {
                    event.preventDefault();
                    window.toast.error('Pilih nomor order jasa terlebih dahulu.');
                    return false;
                }

                if (this.items.length === 0) {
                    event.preventDefault();
                    window.toast.error('Tambahkan minimal satu item invoice order jasa.');
                    return false;
                }

                const invalidItem = this.items.find((item) => !String(item.description || item.name || '').trim() || !(Number(item.qty) > 0) || Number(item.price) < 0);
                if (invalidItem) {
                    event.preventDefault();
                    window.toast.error('Pastikan item order jasa memiliki deskripsi, qty, dan harga yang valid.');
                    return false;
                }

                return true;
            }

            const customerInput = document.querySelector('input[name="customer_id"]');

            if (!customerInput?.value) {
                event.preventDefault();
                window.toast.error('Pilih pelanggan terlebih dahulu.');
                return false;
            }

            if (this.items.length === 0) {
                event.preventDefault();
                window.toast.error('Tambahkan minimal satu item invoice.');
                return false;
            }

            if (this.items.some((item) => item.product_type === 'cutting' && !item.pricing_mode)) {
                event.preventDefault();
                window.toast.error('Mode harga cutting wajib dipilih.');
                return false;
            }

            if (this.items.some((item) => item.product_type === 'cutting' && item.pricing_mode === 'per-minute' && !item.minutes)) {
                event.preventDefault();
                window.toast.error('Isi jumlah menit untuk item cutting per-menit.');
                return false;
            }

            if (!this.isPayLater && Number(this.payment.amount || 0) <= 0) {
                event.preventDefault();
                window.toast.error('Jumlah bayar wajib lebih dari 0 jika metode pembayaran bukan Pay Later.');
                return false;
            }

            return true;
        },

        linePreview(item) {
            if (item.product_type === 'cutting' && item.pricing_mode === 'per-minute') {
                return `${item.qty} x ${this.currency(item.price)} x ${item.minutes || 0} menit`;
            }

            return `${item.qty} x ${this.currency(item.effectiveUnitPrice)}`;
        },

        currency(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        formatNumberInput(value) {
            const numericValue = Math.max(Number(value || 0), 0);

            if (!numericValue) {
                return '';
            }

            return numericValue.toLocaleString('id-ID');
        },

        parseNumberInput(value) {
            const normalized = String(value || '').replace(/[^\d]/g, '');

            return normalized ? Number(normalized) : 0;
        },

        formatMachineOrderStatus(status) {
            const normalized = String(status || '').toLowerCase();
            const labels = {
                draft: 'Draft',
                confirmed: 'Confirmed',
                in_production: 'In Production',
                ready: 'Ready',
                in_shipping: 'In Shipping',
                accepted: 'Accepted',
                completed: 'Completed',
                cancelled: 'Cancelled',
            };

            return labels[normalized] || String(status || '-');
        },

        formatServiceOrderStatus(status) {
            const normalized = String(status || '').toLowerCase();
            const labels = {
                draft: 'Draft',
                confirmed: 'Confirmed',
                in_progress: 'In Progress',
                completed: 'Completed',
                invoiced: 'Invoiced',
                cancelled: 'Cancelled',
            };

            return labels[normalized] || String(status || '-');
        },
    };
}
</script>
@endpush
