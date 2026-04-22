<?php

namespace App\Http\Controllers\Report;

class CuttingSalesRecapController extends SalesItemRecapController
{
    protected function productType(): string
    {
        return 'cutting';
    }

    protected function viewTitle(): string
    {
        return 'Rekap Penjualan Jasa Cutting';
    }

    protected function routeName(): string
    {
        return 'laporan.rekap-penjualan-jasa-cutting';
    }
}
