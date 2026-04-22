<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print SPK {{ $invoice['number'] }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; background: #f3f4f6; }
        .page { width: 960px; margin: 24px auto; background: #fff; border: 1px solid #d1d5db; padding: 18px; }
        .toolbar { width: 960px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 12px; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; border: 1px solid #d1d5db; color: #111827; background: #fff; }
        .btn-primary { background: #f97316; color: #fff; border-color: #f97316; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
        .brand { font-size: 24px; font-weight: 700; letter-spacing: 0.6px; margin-bottom: 10px; color: #111827; }
        .brand-logo { max-width: 260px; max-height: 88px; width: auto; height: auto; display: block; }
        .subtle { color: #4b5563; line-height: 1.45; }
        .branch-meta { font-size: 13px; }
        .branch-meta strong { font-size: 17px; color: #111827; }
        .divider { margin: 14px 0 18px; border-top: 2px solid #f97316; }
        .title { text-align: center; margin: 6px 0 18px; }
        .title h1 { margin: 0; letter-spacing: 8px; font-size: 24px; }
        .title p { margin: 4px 0 0; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; }
        th { background: #f9fafb; }
        .text-right { text-align: right; }
        .signature { margin-top: 70px; display: flex; justify-content: flex-end; }
        .signature-box { min-width: 240px; text-align: center; }
        .signature-space { height: 72px; }
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
            <h1>SURAT PERINTAH KERJA</h1>
            <p>No. Invoice : {{ $invoice['number'] }}</p>
        </div>

        <div class="subtle">
            <div><strong>Kepada Yth.</strong></div>
            <div>Operator</div>
        </div>

        <p class="subtle" style="margin-top:20px; font-style:italic;">Dengan ini kami memohon kepada Saudara untuk melakukan pekerjaan dengan rincian sebagai berikut:</p>

        <table>
            <thead>
                <tr>
                    <th style="width:48px;">No</th>
                    <th>Deskripsi</th>
                    <th style="width:120px;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice['items'] as $index => $item)
                    <tr>
                        <td class="text-right">{{ $index + 1 }}</td>
                        <td>{{ $item['desc'] }}</td>
                        <td class="text-right">
                            {{ ($item['product_type'] ?? '') === 'cutting' && ($item['pricing_mode'] ?? '') === 'per-minute' ? number_format($item['minutes'] ?? 0, 0, ',', '.') : number_format($item['qty'] ?? 0, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature">
            <div class="signature-box">
                <div>{{ $branch['city'] ?? 'Malang' }}, {{ $issuedDate }}</div>
                <div class="signature-space"></div>
                <div><u>{{ $setting['invoice_header_name'] ?? ($invoice['petugas'] ?? 'Administrator') }}</u></div>
                <div>{{ $setting['invoice_header_position'] ?? 'Administrator' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
