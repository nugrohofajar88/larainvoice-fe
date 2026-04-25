<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Invoice {{ $invoice['number'] }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; background: #f3f4f6; }
        .toolbar { width: 960px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 12px; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; border: 1px solid #d1d5db; color: #111827; background: #fff; }
        .btn-primary { background: #f97316; color: #fff; border-color: #f97316; }
        .page { width: 960px; margin: 24px auto; background: #fff; border: 1px solid #d1d5db; padding: 18px; page-break-after: always; }
        .page:last-of-type { page-break-after: auto; }
        .header, .footer-meta, .section-head, .payment-summary { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
        .brand { font-size: 24px; font-weight: 700; letter-spacing: 0.6px; margin-bottom: 10px; color: #111827; }
        .brand-logo { max-width: 260px; max-height: 88px; width: auto; height: auto; display: block; }
        .subtle { color: #4b5563; line-height: 1.45; }
        .branch-meta { font-size: 13px; }
        .branch-meta strong { font-size: 17px; color: #111827; }
        .divider { margin: 14px 0 18px; border-top: 2px solid #f97316; }
        .title { text-align: center; margin: 6px 0 18px; }
        .title h1 { margin: 0; letter-spacing: 8px; font-size: 28px; }
        .title p { margin: 4px 0 0; font-weight: 700; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
        .callout { text-align: center; color: #f97316; font-weight: 700; font-size: 18px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .card { border: 1px solid #d1d5db; border-radius: 10px; padding: 12px 14px; background: #fff; }
        .card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin-bottom: 6px; }
        .card-value { font-size: 20px; font-weight: 700; color: #111827; }
        .section-title { font-size: 18px; font-weight: 700; margin: 0; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .pill-danger { background: #fee2e2; color: #b91c1c; }
        .pill-warning { background: #fef3c7; color: #b45309; }
        .pill-success { background: #dcfce7; color: #15803d; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; vertical-align: top; }
        th { background: #f9fafb; text-align: left; }
        .text-right { text-align: right; }
        .summary td { border-top: none; }
        .notes { margin-top: 22px; display: flex; justify-content: space-between; gap: 32px; }
        .notes ol { margin: 6px 0 0 18px; padding: 0; }
        .signature { text-align: center; min-width: 220px; }
        .signature .space { height: 70px; }
        .muted { color: #6b7280; font-size: 12px; }
        .stack { display: flex; flex-direction: column; gap: 14px; }
        .list-plain { margin: 0; padding-left: 18px; }
        .detail-table th { width: 220px; background: #fff; }
        .detail-table td { background: #fff; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: auto; margin: 0; border: none; padding: 0 4px 0 0; box-sizing: border-box; }
            table { width: calc(100% - 2px); }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('sales-list.index', ['tab' => $invoice['production_status'] ?? 'pending']) }}" class="btn">Kembali</a>
        <button onclick="window.print()" class="btn btn-primary">Print / Save PDF</button>
    </div>

    @php
        $branch = $invoice['branch_detail'] ?? [];
        $setting = $invoice['branch_setting'] ?? [];
        $bankAccounts = collect($invoice['bank_accounts'] ?? [])->sortByDesc(fn ($item) => $item['is_default'] ?? false)->values();
        $remaining = $invoice['remaining'] ?? max(($invoice['grand_total'] ?? 0) - ($invoice['paid'] ?? 0), 0);
        $issuedDate = !empty($invoice['date']) ? \Carbon\Carbon::parse($invoice['date'])->translatedFormat('d F Y') : now()->translatedFormat('d F Y');
        $logoPath = public_path('images/logo.png');
        $logoSrc = file_exists($logoPath) ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;
        $payments = collect($invoice['payments'] ?? []);
        $sourceDetail = $invoice['source_detail'] ?? null;
        $paymentStatus = $invoice['payment_status'] ?? 'unpaid';
        $paymentStatusLabel = $invoice['payment_status_label'] ?? 'Belum Lunas';
        $paymentPillClass = match ($paymentStatus) {
            'paid' => 'pill-success',
            'partial' => 'pill-warning',
            default => 'pill-danger',
        };
    @endphp

    <div class="page">
        <div class="header">
            <div>
                <div class="brand">PIONEER CNC INDONESIA</div>
                <div class="subtle branch-meta">
                    <div><strong>{{ $branch['name'] ?? '-' }}</strong></div>
                    <div>{{ $branch['address'] ?? '-' }}</div>
                    <div>HP. {{ $branch['phone'] ?? '-' }}</div>
                    <div>{{ $branch['website'] ?? '-' }}</div>
                    <div>{{ $branch['email'] ?? '-' }}</div>
                </div>
            </div>
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="Logo Pioneer CNC" class="brand-logo">
            @endif
        </div>

        <div class="divider"></div>

        <div class="title">
            <h1>INVOICE</h1>
            <p>No : {{ $invoice['number'] }}</p>
        </div>

        <div class="meta-grid">
            <div class="subtle">
                <div><strong>Kepada Yth.</strong></div>
                <div>{{ $invoice['customer'] ?? '-' }}</div>
                <div>{{ $branch['city'] ?? '' }}</div>
            </div>
            <div class="callout">
                PELAYANAN DILUAR JAM KERJA<br>
                TIDAK DILAYANI
            </div>
        </div>

        <p class="subtle" style="font-style:italic;">Dengan ini kami memohon kepada Saudara untuk melakukan pembayaran dengan rincian sebagai berikut:</p>

        <table>
            <thead>
                <tr>
                    <th style="width:34px;">No</th>
                    <th>Deskripsi</th>
                    <th style="width:105px;">Harga</th>
                    <th style="width:64px;">Disc</th>
                    <th style="width:64px;">Qty</th>
                    <th style="width:120px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice['items'] as $index => $item)
                    <tr>
                        <td class="text-right">{{ $index + 1 }}</td>
                        <td>{{ $item['desc'] }}</td>
                        <td class="text-right">{{ number_format($item['price'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($item['discount'], 2, ',', '.') }}</td>
                        <td class="text-right">
                            {{ ($item['product_type'] ?? '') === 'cutting' && ($item['pricing_mode'] ?? '') === 'per-minute' ? number_format($item['minutes'] ?? 0, 0, ',', '.') : number_format($item['qty'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="text-right">{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary">
                    <td colspan="5" class="text-right"><strong>Total</strong></td>
                    <td class="text-right">{{ number_format($invoice['subtotal'], 0, ',', '.') }}</td>
                </tr>
                <tr class="summary">
                    <td colspan="5" class="text-right">Disc.({{ number_format($invoice['discount_pct'], 2, ',', '.') }}%)</td>
                    <td class="text-right">{{ number_format($invoice['discount_amount'], 0, ',', '.') }}</td>
                </tr>
                <tr class="summary">
                    <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                    <td class="text-right"><strong>{{ number_format($invoice['grand_total'], 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            <div style="flex:1;">
                <div><strong>Terbilang : {{ $remaining <= 0 ? 'LUNAS' : 'BELUM LUNAS' }}</strong></div>
                <div style="margin-top:12px;"><strong>Catatan:</strong></div>
                @if($bankAccounts->isNotEmpty())
                    <ol>
                        <li>Mohon pembayaran ditransfer ke rekening berikut ini:</li>
                        @foreach($bankAccounts as $account)
                            <div>{{ $account['bank_name'] }}</div>
                            <div>No Rek. {{ $account['account_number'] }}</div>
                            <div>a.n. {{ $account['account_holder'] }}</div>
                        @endforeach
                        <li>Pembayaran baru dianggap sah setelah cek/giro dicairkan.</li>
                    </ol>
                @else
                    <p class="subtle">{{ $setting['invoice_footer_note'] ?? 'Terima kasih atas kepercayaan Anda.' }}</p>
                @endif
            </div>
            <div class="signature">
                <div>{{ $branch['city'] ?? 'Malang' }}, {{ $issuedDate }}</div>
                <div class="space"></div>
                <div><u>{{ $setting['invoice_header_name'] ?? ($invoice['petugas'] ?? 'Administrator') }}</u></div>
                <div>{{ $setting['invoice_header_position'] ?? 'Admin Keuangan' }}</div>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <div>
                <div class="brand">PIONEER CNC INDONESIA</div>
                <div class="subtle branch-meta">
                    <div><strong>{{ $branch['name'] ?? '-' }}</strong></div>
                    <div>{{ $branch['address'] ?? '-' }}</div>
                    <div>HP. {{ $branch['phone'] ?? '-' }}</div>
                    <div>{{ $branch['website'] ?? '-' }}</div>
                    <div>{{ $branch['email'] ?? '-' }}</div>
                </div>
            </div>
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="Logo Pioneer CNC" class="brand-logo">
            @endif
        </div>

        <div class="divider"></div>

        <div class="title">
            <h1 style="font-size:22px; letter-spacing:3px;">LAMPIRAN</h1>
            <p>Riwayat Pembayaran - {{ $invoice['number'] }}</p>
        </div>

        <div class="section-head" style="margin-bottom:14px;">
            <div></div>
            <span class="pill {{ $paymentPillClass }}">{{ $paymentStatusLabel }}</span>
        </div>

        @if($payments->isEmpty())
            <div class="card">
                <div class="subtle">Belum ada riwayat pembayaran untuk invoice ini.</div>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width:48px;">No</th>
                        <th style="width:120px;">Tanggal</th>
                        <th>Metode</th>
                        <th>Jenis</th>
                        <th>Petugas</th>
                        <th>Keterangan</th>
                        <th style="width:150px;" class="text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $index => $payment)
                        <tr>
                            <td class="text-right">{{ $index + 1 }}</td>
                            <td>{{ !empty($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->translatedFormat('d M Y') : '-' }}</td>
                            <td>{{ $payment['method'] ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment['payment_type'] ?? '-')) }}</td>
                            <td>{{ $payment['handled_by'] ?? '-' }}</td>
                            <td>{{ $payment['note'] ?? '-' }}</td>
                            <td class="text-right">{{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(!empty($sourceDetail))
        <div class="page">
            <div class="header">
                <div>
                    <div class="brand">PIONEER CNC INDONESIA</div>
                    <div class="subtle branch-meta">
                        <div><strong>{{ $branch['name'] ?? '-' }}</strong></div>
                        <div>{{ $branch['address'] ?? '-' }}</div>
                        <div>HP. {{ $branch['phone'] ?? '-' }}</div>
                        <div>{{ $branch['website'] ?? '-' }}</div>
                        <div>{{ $branch['email'] ?? '-' }}</div>
                    </div>
                </div>
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo Pioneer CNC" class="brand-logo">
                @endif
            </div>

            <div class="divider"></div>

            <div class="title">
                <h1 style="font-size:22px; letter-spacing:3px;">LAMPIRAN</h1>
                <p>Detail Order - {{ $invoice['number'] }}</p>
            </div>

            <div class="section-head" style="margin-bottom:14px;">
                <div></div>
                <span class="pill pill-success">{{ $sourceDetail['status_label'] ?? '-' }}</span>
            </div>

            @if(($sourceDetail['type'] ?? null) === 'machine_order')
                <table class="detail-table" style="margin-bottom:14px;">
                    <tbody>
                        <tr><th>Nomor Order</th><td>{{ $sourceDetail['order_number'] ?? '-' }}</td></tr>
                        <tr><th>Pelanggan</th><td>{{ $sourceDetail['customer_name'] ?? '-' }}</td></tr>
                        <tr><th>Mesin</th><td>{{ $sourceDetail['machine_name'] ?? '-' }}</td></tr>
                        <tr><th>Sales</th><td>{{ $sourceDetail['sales_name'] ?? '-' }}</td></tr>
                        <tr><th>Tanggal Order</th><td>{{ !empty($sourceDetail['order_date']) ? \Carbon\Carbon::parse($sourceDetail['order_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Estimasi Mulai</th><td>{{ !empty($sourceDetail['estimated_start_date']) ? \Carbon\Carbon::parse($sourceDetail['estimated_start_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Estimasi Selesai</th><td>{{ !empty($sourceDetail['estimated_finish_date']) ? \Carbon\Carbon::parse($sourceDetail['estimated_finish_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Selesai Aktual</th><td>{{ !empty($sourceDetail['actual_finish_date']) ? \Carbon\Carbon::parse($sourceDetail['actual_finish_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Qty</th><td>{{ number_format($sourceDetail['qty'] ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><th>Subtotal Order</th><td>{{ number_format($sourceDetail['subtotal'] ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><th>Biaya Tambahan</th><td>{{ number_format($sourceDetail['additional_cost_total'] ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><th>Grand Total Order</th><td>{{ number_format($sourceDetail['grand_total'] ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><th>Petugas Produksi</th><td>@if(empty($sourceDetail['assignments']))-@else @foreach($sourceDetail['assignments'] as $assignment){{ $loop->first ? '' : '; ' }}{{ $assignment['user_name'] ?? '-' }}@if(!empty($assignment['role'])) - {{ ucfirst($assignment['role']) }}@endif@if(!empty($assignment['notes'])) ({{ $assignment['notes'] }})@endif @endforeach @endif</td></tr>
                        <tr><th>Catatan Order</th><td>{{ $sourceDetail['notes'] ?: '-' }}</td></tr>
                    </tbody>
                </table>

                <div style="margin-top:14px; font-weight:700;">Komponen Mesin</div>
                @if(empty($sourceDetail['components']))
                    <div class="muted" style="margin-top:6px;">Tidak ada komponen tercatat.</div>
                @else
                    <table style="margin-top:6px;">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th style="width:70px;" class="text-right">Qty</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sourceDetail['components'] as $component)
                                <tr>
                                    <td>{{ $component['name'] }}{{ !empty($component['type_size']) ? ' - ' . $component['type_size'] : '' }}</td>
                                    <td class="text-right">{{ number_format($component['qty'] ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $component['notes'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <div style="margin-top:14px; font-weight:700;">Biaya Tambahan Order</div>
                @if(empty($sourceDetail['costs']))
                    <div class="muted" style="margin-top:6px;">Tidak ada biaya tambahan order.</div>
                @else
                    <table style="margin-top:6px;">
                        <thead>
                            <tr>
                                <th>Biaya</th>
                                <th>Keterangan</th>
                                <th style="width:70px;" class="text-right">Qty</th>
                                <th style="width:110px;" class="text-right">Harga</th>
                                <th style="width:120px;" class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sourceDetail['costs'] as $cost)
                                <tr>
                                    <td>{{ $cost['name'] ?? '-' }}</td>
                                    <td>{{ $cost['description'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($cost['qty'] ?? 0, 2, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($cost['price'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($cost['total'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @else
                <table class="detail-table" style="margin-bottom:14px;">
                    <tbody>
                        <tr><th>Nomor Order</th><td>{{ $sourceDetail['order_number'] ?? '-' }}</td></tr>
                        <tr><th>Tipe Order</th><td>{{ $sourceDetail['order_type_label'] ?? '-' }}</td></tr>
                        <tr><th>Pelanggan</th><td>{{ $sourceDetail['customer_name'] ?? '-' }}</td></tr>
                        <tr><th>Judul</th><td>{{ $sourceDetail['title'] ?? '-' }}</td></tr>
                        <tr><th>Kategori</th><td>{{ $sourceDetail['category'] ?? '-' }}</td></tr>
                        <tr><th>Lokasi</th><td>{{ $sourceDetail['location'] ?? '-' }}</td></tr>
                        <tr><th>Tanggal Order</th><td>{{ !empty($sourceDetail['order_date']) ? \Carbon\Carbon::parse($sourceDetail['order_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Mulai Rencana</th><td>{{ !empty($sourceDetail['planned_start_date']) ? \Carbon\Carbon::parse($sourceDetail['planned_start_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Mulai Aktual</th><td>{{ !empty($sourceDetail['actual_start_date']) ? \Carbon\Carbon::parse($sourceDetail['actual_start_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Selesai Aktual</th><td>{{ !empty($sourceDetail['actual_finish_date']) ? \Carbon\Carbon::parse($sourceDetail['actual_finish_date'])->translatedFormat('d M Y') : '-' }}</td></tr>
                        <tr><th>Durasi</th><td>{{ !empty($sourceDetail['duration_days']) ? number_format($sourceDetail['duration_days'], 0, ',', '.') . ' hari' : '-' }}</td></tr>
                        <tr><th>Petugas</th><td>@if(empty($sourceDetail['assignments']))-@else @foreach($sourceDetail['assignments'] as $assignment){{ $loop->first ? '' : '; ' }}{{ $assignment['user_name'] ?? '-' }}@if(!empty($assignment['role'])) - {{ ucfirst($assignment['role']) }}@endif@if(!empty($assignment['notes'])) ({{ $assignment['notes'] }})@endif @endforeach @endif</td></tr>
                        <tr><th>Deskripsi Pekerjaan</th><td>{{ $sourceDetail['description'] ?? '-' }}</td></tr>
                        <tr><th>Catatan Order</th><td>{{ $sourceDetail['notes'] ?: '-' }}@if(!empty($sourceDetail['completion_notes']))<br><strong>Catatan penyelesaian:</strong> {{ $sourceDetail['completion_notes'] }}@endif</td></tr>
                    </tbody>
                </table>

                @if(($sourceDetail['order_type'] ?? null) === 'service')
                    <div style="margin-top:14px; font-weight:700;">Komponen Terpakai</div>
                    @if(empty($sourceDetail['components']))
                        <div class="muted" style="margin-top:6px;">Tidak ada komponen tercatat.</div>
                    @else
                        <table style="margin-top:6px;">
                            <thead>
                                <tr>
                                    <th>Komponen</th>
                                    <th style="width:70px;" class="text-right">Qty</th>
                                    <th>Catatan</th>
                                    <th style="width:70px;">Tagih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sourceDetail['components'] as $component)
                                    <tr>
                                        <td>{{ $component['name'] }}{{ !empty($component['type_size']) ? ' - ' . $component['type_size'] : '' }}</td>
                                        <td class="text-right">{{ number_format($component['qty'] ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $component['notes'] ?? '-' }}</td>
                                        <td>{{ !empty($component['billable']) ? 'Ya' : 'Tidak' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            @endif
        </div>
    @endif
</body>
</html>
