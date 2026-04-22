<?php

namespace App\Http\Controllers\Report;

class PlateSalesRecapController extends SalesItemRecapController
{
    protected function productType(): string
    {
        return 'plate';
    }

    protected function viewTitle(): string
    {
        return 'Rekap Penjualan Plat';
    }

    protected function routeName(): string
    {
        return 'laporan.rekap-penjualan-plat';
    }
}
