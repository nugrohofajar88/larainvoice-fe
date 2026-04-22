<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Invoice {{ $invoice['number'] }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; background: #f3f4f6; }
        .page { width: 960px; margin: 24px auto; background: #fff; border: 1px solid #d1d5db; padding: 18px; }
        .toolbar { width: 960px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 12px; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; border: 1px solid #d1d5db; color: #111827; background: #fff; }
        .btn-primary { background: #f97316; color: #fff; border-color: #f97316; }
        .header, .footer-meta { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
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
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; }
        th { background: #f9fafb; }
        .text-right { text-align: right; }
        .summary td { border-top: none; }
        .notes { margin-top: 22px; display: flex; justify-content: space-between; gap: 32px; }
        .notes ol { margin: 6px 0 0 18px; padding: 0; }
        .signature { text-align: center; min-width: 220px; }
        .signature .space { height: 70px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: auto; margin: 0; border: none; padding: 0; }
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
                    <th style="width:48px;">No</th>
                    <th>Deskripsi</th>
                    <th style="width:130px;">Harga</th>
                    <th style="width:90px;">Disc(%)</th>
                    <th style="width:90px;">Qty</th>
                    <th style="width:150px;">Jumlah</th>
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
                    <td colspan="5" class="text-right">DP</td>
                    <td class="text-right">({{ number_format($invoice['paid'], 0, ',', '.') }})</td>
                </tr>
                <tr class="summary">
                    <td colspan="5" class="text-right"><strong>Sisa Tagihan</strong></td>
                    <td class="text-right"><strong>{{ number_format($remaining, 0, ',', '.') }}</strong></td>
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
</body>
</html>
