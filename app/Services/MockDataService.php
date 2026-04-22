<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class MockDataService
{
    // ============================================================
    // AUTH / USER
    // ============================================================
    public static function users(): array
    {
        return [
            ['id' => 1, 'name' => 'Administrator', 'username' => 'admin', 'password' => 'admin123', 'role' => 'super-admin', 'role_label' => 'Super Admin', 'branch_id' => null, 'branch' => 'Semua Cabang'],
            ['id' => 2, 'name' => 'Budi Santoso', 'username' => 'cabang1', 'password' => 'cabang123', 'role' => 'admin-cabang', 'role_label' => 'Admin Cabang', 'branch_id' => 1, 'branch' => 'Jakarta'],
            ['id' => 3, 'name' => 'Citra Dewi', 'username' => 'sales1', 'password' => 'sales123', 'role' => 'sales', 'role_label' => 'Sales', 'branch_id' => 1, 'branch' => 'Jakarta'],
            ['id' => 4, 'name' => 'Deni Prasetyo', 'username' => 'operator1', 'password' => 'op123', 'role' => 'operator', 'role_label' => 'Operator Produksi', 'branch_id' => 2, 'branch' => 'Surabaya'],
            ['id' => 5, 'name' => 'Eva Kurniawati', 'username' => 'kasir1', 'password' => 'kasir123', 'role' => 'kasir', 'role_label' => 'Kasir', 'branch_id' => 2, 'branch' => 'Surabaya'],
            ['id' => 6, 'name' => 'Budi Santoso', 'username' => 'cabang1', 'password' => 'cabang123', 'role' => 'admin-cabang', 'role_label' => 'Admin Cabang', 'branch_id' => 1, 'branch' => 'Jakarta'],
            ['id' => 7, 'name' => 'Citra Dewi', 'username' => 'sales1', 'password' => 'sales123', 'role' => 'sales', 'role_label' => 'Sales', 'branch_id' => 1, 'branch' => 'Jakarta'],
            ['id' => 8, 'name' => 'Deni Prasetyo', 'username' => 'operator1', 'password' => 'op123', 'role' => 'operator', 'role_label' => 'Operator Produksi', 'branch_id' => 2, 'branch' => 'Surabaya'],
            ['id' => 9, 'name' => 'Eva Kurniawati', 'username' => 'kasir1', 'password' => 'kasir123', 'role' => 'kasir', 'role_label' => 'Kasir', 'branch_id' => 2, 'branch' => 'Surabaya'],
            ['id' => 10, 'name' => 'Budi Santoso', 'username' => 'cabang1', 'password' => 'cabang123', 'role' => 'admin-cabang', 'role_label' => 'Admin Cabang', 'branch_id' => 1, 'branch' => 'Jakarta'],
            ['id' => 11, 'name' => 'Citra Dewi', 'username' => 'sales1', 'password' => 'sales123', 'role' => 'sales', 'role_label' => 'Sales', 'branch_id' => 1, 'branch' => 'Jakarta'],
            ['id' => 12, 'name' => 'Deni Prasetyo', 'username' => 'operator1', 'password' => 'op123', 'role' => 'operator', 'role_label' => 'Operator Produksi', 'branch_id' => 2, 'branch' => 'Surabaya'],
            ['id' => 13, 'name' => 'Eva Kurniawati', 'username' => 'kasir1', 'password' => 'kasir123', 'role' => 'kasir', 'role_label' => 'Kasir', 'branch_id' => 2, 'branch' => 'Surabaya'],
        ];
    }

    public static function findUser(string $username, string $password): ?array
    {
        foreach (self::users() as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                return $user;
            }
        }
        return null;
    }

    // ============================================================
    // BRANCHES
    // ============================================================
    public static function branches(): array
    {
        return [
            ['id' => 1, 'name' => 'Pioneer CNC Jakarta', 'city' => 'Jakarta', 'address' => 'Jl. Industri No. 12, Jakarta Barat', 'phone' => '021-55501234', 'bank' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'PT Pioneer CNC Jakarta'],
            ['id' => 2, 'name' => 'Pioneer CNC Surabaya', 'city' => 'Surabaya', 'address' => 'Jl. Rungkut Industri No. 5, Surabaya', 'phone' => '031-87654321', 'bank' => 'Mandiri', 'account_number' => '0987654321', 'account_name' => 'PT Pioneer CNC Surabaya'],
            ['id' => 3, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 4, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 5, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 6, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 7, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 8, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 9, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 10, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 11, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 12, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
            ['id' => 13, 'name' => 'Pioneer CNC Bandung', 'city' => 'Bandung', 'address' => 'Jl. Soekarno-Hatta No. 88, Bandung', 'phone' => '022-44443333', 'bank' => 'BNI', 'account_number' => '1122334455', 'account_name' => 'PT Pioneer CNC Bandung'],
        ];
    }

    // ============================================================
    // CUSTOMERS
    // ============================================================
    public static function customers(): array
    {
        return [
            ['id' => 1, 'name' => 'PT Maju Jaya Teknik', 'branch_id' => 1, 'branch' => 'Jakarta', 'sales' => 'Citra Dewi', 'phone' => '0812-1111-2222', 'email' => 'info@majujaya.com', 'ranking_last' => 1, 'ranking_now' => 1],
            ['id' => 2, 'name' => 'CV Sinar Logam', 'branch_id' => 1, 'branch' => 'Jakarta', 'sales' => 'Citra Dewi', 'phone' => '0813-2222-3333', 'email' => 'sinarlogam@gmail.com', 'ranking_last' => 3, 'ranking_now' => 2],
            ['id' => 3, 'name' => 'PT Karya Mandiri Abadi', 'branch_id' => 2, 'branch' => 'Surabaya', 'sales' => null, 'phone' => '0814-3333-4444', 'email' => 'kma@kma.co.id', 'ranking_last' => 2, 'ranking_now' => 3],
            ['id' => 4, 'name' => 'Bengkel Las Jaya', 'branch_id' => 2, 'branch' => 'Surabaya', 'sales' => null, 'phone' => '0815-4444-5555', 'email' => null, 'ranking_last' => 4, 'ranking_now' => 4],
            ['id' => 5, 'name' => 'CV Metal Prima', 'branch_id' => 3, 'branch' => 'Bandung', 'sales' => null, 'phone' => '0816-5555-6666', 'email' => 'metalprima@gmail.com', 'ranking_last' => 5, 'ranking_now' => 5],
        ];
    }

    // ============================================================
    // MACHINES
    // ============================================================
    public static function machines(): array
    {
        return [
            ['id' => 1, 'number' => 'CNC-JKT-001', 'type' => 'CNC Router', 'branch_id' => 1, 'branch' => 'Jakarta', 'status' => 'aktif'],
            ['id' => 2, 'number' => 'CNC-JKT-002', 'type' => 'Laser CO2', 'branch_id' => 1, 'branch' => 'Jakarta', 'status' => 'aktif'],
            ['id' => 3, 'number' => 'CNC-SBY-001', 'type' => 'Plasma Cutting', 'branch_id' => 2, 'branch' => 'Surabaya', 'status' => 'aktif'],
            ['id' => 4, 'number' => 'CNC-SBY-002', 'type' => 'CNC Router', 'branch_id' => 2, 'branch' => 'Surabaya', 'status' => 'maintenance'],
            ['id' => 5, 'number' => 'CNC-BDG-001', 'type' => 'Laser Fiber', 'branch_id' => 3, 'branch' => 'Bandung', 'status' => 'aktif'],
        ];
    }

    // ============================================================
    // PLATES CONFIGURATION
    // ============================================================
    public static function jenisPlat(): array
    {
        return [
            ['id' => 1, 'name' => 'Besi'],
            ['id' => 2, 'name' => 'Stainless'],
            ['id' => 3, 'name' => 'Aluminium'],
            ['id' => 4, 'name' => 'Galvanis'],
        ];
    }

    public static function sizePlat(): array
    {
        return [
            ['id' => 1, 'name' => '1mm'],
            ['id' => 2, 'name' => '2mm'],
            ['id' => 3, 'name' => '3mm'],
            ['id' => 4, 'name' => '5mm'],
            ['id' => 5, 'name' => '8mm'],
            ['id' => 10, 'name' => '10mm'],
        ];
    }

    public static function plates(): array
    {
        return [
            ['id' => 1, 'jenis_id' => 1, 'size_id' => 1, 'name' => 'Plat Besi 1mm', 'type' => 'Besi', 'size' => '1mm', 'dimension' => '120x240cm', 'price' => 180000, 'stock' => 45, 'unit' => 'lembar'],
            ['id' => 2, 'jenis_id' => 1, 'size_id' => 2, 'name' => 'Plat Besi 2mm', 'type' => 'Besi', 'size' => '2mm', 'dimension' => '120x240cm', 'price' => 320000, 'stock' => 30, 'unit' => 'lembar'],
            ['id' => 3, 'jenis_id' => 1, 'size_id' => 3, 'name' => 'Plat Besi 3mm', 'type' => 'Besi', 'size' => '3mm', 'dimension' => '120x240cm', 'price' => 480000, 'stock' => 22, 'unit' => 'lembar'],
            ['id' => 4, 'jenis_id' => 2, 'size_id' => 1, 'name' => 'Plat Stainless 1mm', 'type' => 'Stainless', 'size' => '1mm', 'dimension' => '120x240cm', 'price' => 450000, 'stock' => 15, 'unit' => 'lembar'],
            ['id' => 5, 'jenis_id' => 2, 'size_id' => 2, 'name' => 'Plat Stainless 2mm', 'type' => 'Stainless', 'size' => '2mm', 'dimension' => '120x240cm', 'price' => 780000, 'stock' => 10, 'unit' => 'lembar'],
            ['id' => 6, 'jenis_id' => 3, 'size_id' => 2, 'name' => 'Plat Aluminium 2mm', 'type' => 'Aluminium', 'size' => '2mm', 'dimension' => '120x240cm', 'price' => 360000, 'stock' => 28, 'unit' => 'lembar'],
        ];
    }

    // ============================================================
    // INVOICES
    // ============================================================
    public static function invoices(): array
    {
        return [
            [
                'id' => 1,
                'number' => 'INV/11/001/04/2026',
                'customer' => 'PT Maju Jaya Teknik',
                'customer_id' => 1,
                'branch' => 'Jakarta',
                'branch_id' => 1,
                'machine' => 'CNC-JKT-001',
                'petugas' => 'Citra Dewi',
                'date' => '2026-04-11',
                'subtotal' => 2500000,
                'discount_pct' => 5,
                'discount_amount' => 125000,
                'grand_total' => 2375000,
                'paid' => 2375000,
                'status' => 'lunas',
                'production_status' => 'selesai',
                'items' => [
                    ['type' => 'Cutting', 'desc' => 'Cutting Besi 2mm Easy', 'qty' => 10, 'price' => 150000, 'discount' => 0, 'subtotal' => 1500000],
                    ['type' => 'Plat', 'desc' => 'Plat Besi 2mm', 'qty' => 3, 'price' => 320000, 'discount' => 0, 'subtotal' => 960000],
                    ['type' => 'Cutting', 'desc' => 'Cutting Stainless Medium', 'qty' => 1, 'price' => 40000, 'discount' => 0, 'subtotal' => 40000],
                ],
            ],
            [
                'id' => 2,
                'number' => 'INV/10/002/04/2026',
                'customer' => 'CV Sinar Logam',
                'customer_id' => 2,
                'branch' => 'Jakarta',
                'branch_id' => 1,
                'machine' => 'CNC-JKT-002',
                'petugas' => 'Citra Dewi',
                'date' => '2026-04-10',
                'subtotal' => 1800000,
                'discount_pct' => 0,
                'discount_amount' => 0,
                'grand_total' => 1800000,
                'paid' => 900000,
                'status' => 'dp',
                'production_status' => 'diproses',
                'items' => [
                    ['type' => 'Cutting', 'desc' => 'Laser CO2 Aluminium 2mm', 'qty' => 5, 'price' => 200000, 'discount' => 0, 'subtotal' => 1000000],
                    ['type' => 'Plat', 'desc' => 'Plat Aluminium 2mm', 'qty' => 2, 'price' => 360000, 'discount' => 0, 'subtotal' => 720000],
                    ['type' => 'Cutting', 'desc' => 'Engraving text', 'qty' => 2, 'price' => 40000, 'discount' => 0, 'subtotal' => 80000],
                ],
            ],
            [
                'id' => 3,
                'number' => 'INV/09/003/04/2026',
                'customer' => 'PT Karya Mandiri Abadi',
                'customer_id' => 3,
                'branch' => 'Surabaya',
                'branch_id' => 2,
                'machine' => 'CNC-SBY-001',
                'petugas' => 'Deni Prasetyo',
                'date' => '2026-04-09',
                'subtotal' => 3200000,
                'discount_pct' => 10,
                'discount_amount' => 320000,
                'grand_total' => 2880000,
                'paid' => 0,
                'status' => 'belum-bayar',
                'production_status' => 'waiting',
                'items' => [
                    ['type' => 'Cutting', 'desc' => 'Plasma Cutting Besi 5mm', 'qty' => 20, 'price' => 160000, 'discount' => 0, 'subtotal' => 3200000],
                ],
            ],
            [
                'id' => 4,
                'number' => 'INV/08/004/04/2026',
                'customer' => 'Bengkel Las Jaya',
                'customer_id' => 4,
                'branch' => 'Surabaya',
                'branch_id' => 2,
                'machine' => 'CNC-SBY-001',
                'petugas' => 'Deni Prasetyo',
                'date' => '2026-04-08',
                'subtotal' => 750000,
                'discount_pct' => 0,
                'discount_amount' => 0,
                'grand_total' => 750000,
                'paid' => 750000,
                'status' => 'lunas',
                'production_status' => 'selesai',
                'items' => [
                    ['type' => 'Cutting', 'desc' => 'Plasma Cutting Besi 3mm', 'qty' => 5, 'price' => 150000, 'discount' => 0, 'subtotal' => 750000],
                ],
            ],
            [
                'id' => 5,
                'number' => 'INV/07/005/04/2026',
                'customer' => 'CV Metal Prima',
                'customer_id' => 5,
                'branch' => 'Bandung',
                'branch_id' => 3,
                'machine' => 'CNC-BDG-001',
                'petugas' => 'Administrator',
                'date' => '2026-04-07',
                'subtotal' => 5500000,
                'discount_pct' => 8,
                'discount_amount' => 440000,
                'grand_total' => 5060000,
                'paid' => 2000000,
                'status' => 'dp',
                'production_status' => 'confirmed',
                'items' => [
                    ['type' => 'Cutting', 'desc' => 'Laser Fiber Stainless 1mm', 'qty' => 12, 'price' => 320000, 'discount' => 0, 'subtotal' => 3840000],
                    ['type' => 'Plat', 'desc' => 'Plat Stainless 1mm', 'qty' => 3, 'price' => 450000, 'discount' => 0, 'subtotal' => 1350000],
                    ['type' => 'Cutting', 'desc' => 'Engraving logo', 'qty' => 1, 'price' => 310000, 'discount' => 0, 'subtotal' => 310000],
                ],
            ],
        ];
    }

    // ============================================================
    // PAYMENTS
    // ============================================================
    public static function payments(): array
    {
        return [
            ['id' => 1, 'invoice_id' => 1, 'invoice_number' => 'INV/11/001/04/2026', 'amount' => 2375000, 'method' => 'Transfer', 'is_dp' => false, 'date' => '2026-04-11', 'note' => 'Pelunasan'],
            ['id' => 2, 'invoice_id' => 2, 'invoice_number' => 'INV/10/002/04/2026', 'amount' => 900000, 'method' => 'Cash', 'is_dp' => true, 'date' => '2026-04-10', 'note' => 'DP 50%'],
            ['id' => 3, 'invoice_id' => 5, 'invoice_number' => 'INV/07/005/04/2026', 'amount' => 2000000, 'method' => 'Transfer', 'is_dp' => true, 'date' => '2026-04-07', 'note' => 'DP Awal'],
        ];
    }

    // ============================================================
    // CUTTING PRICES
    // ============================================================
    public static function cuttingPrices(): array
    {
        return [
            ['machine_type' => 'CNC Router', 'plate_type' => 'Besi', 'size' => '< 1m²', 'easy' => 75000, 'medium' => 120000, 'difficult' => 180000, 'per_minute' => 5000],
            ['machine_type' => 'CNC Router', 'plate_type' => 'Besi', 'size' => '1-2m²', 'easy' => 90000, 'medium' => 145000, 'difficult' => 210000, 'per_minute' => 5000],
            ['machine_type' => 'CNC Router', 'plate_type' => 'Aluminium', 'size' => '< 1m²', 'easy' => 95000, 'medium' => 150000, 'difficult' => 220000, 'per_minute' => 6000],
            ['machine_type' => 'Laser CO2', 'plate_type' => 'Aluminium', 'size' => '< 1m²', 'easy' => 120000, 'medium' => 190000, 'difficult' => 280000, 'per_minute' => 8000],
            ['machine_type' => 'Laser CO2', 'plate_type' => 'Stainless', 'size' => '< 1m²', 'easy' => 180000, 'medium' => 260000, 'difficult' => 380000, 'per_minute' => 10000],
            ['machine_type' => 'Plasma Cutting', 'plate_type' => 'Besi', 'size' => '< 1m²', 'easy' => 100000, 'medium' => 160000, 'difficult' => 240000, 'per_minute' => 7000],
            ['machine_type' => 'Plasma Cutting', 'plate_type' => 'Besi', 'size' => '1-2m²', 'easy' => 130000, 'medium' => 200000, 'difficult' => 300000, 'per_minute' => 7000],
            ['machine_type' => 'Laser Fiber', 'plate_type' => 'Stainless', 'size' => '< 1m²', 'easy' => 200000, 'medium' => 320000, 'difficult' => 480000, 'per_minute' => 12000],
            ['machine_type' => 'Laser Fiber', 'plate_type' => 'Aluminium', 'size' => '< 1m²', 'easy' => 170000, 'medium' => 270000, 'difficult' => 400000, 'per_minute' => 11000],
        ];
    }

    // ============================================================
    // DASHBOARD STATS
    // ============================================================
    public static function dashboardStats(): array
    {
        return [
            'total_invoice_bulan_ini' => 47,
            'total_omzet_bulan_ini' => 124750000,
            'total_piutang' => 7940000,
            'produksi_berjalan' => 8,
            'pelanggan_aktif' => 23,
            'invoice_lunas' => 38,
            'invoice_dp' => 6,
            'invoice_belum_bayar' => 3,
        ];
    }

    public static function revenueChart(): array
    {
        return [
            'labels' => ['Nov 2025', 'Des 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026'],
            'data' => [87500000, 102300000, 95800000, 118200000, 134600000, 124750000],
        ];
    }

    public static function productionStatusChart(): array
    {
        return [
            'confirmed' => 3,
            'waiting' => 2,
            'diproses' => 3,
            'selesai' => 39,
        ];
    }

    /**
     * Helper to sort arrays based on key and direction
     */
    public static function sortArray(array $items, string $sortBy, string $sortDir = 'asc'): array
    {
        $items = collect($items);
        
        $sorted = $items->sortBy(function ($item) use ($sortBy) {
            return $item[$sortBy] ?? null;
        }, SORT_NATURAL | SORT_FLAG_CASE);

        if (strtolower($sortDir) === 'desc') {
            $sorted = $sorted->reverse();
        }

        return $sorted->values()->all();
    }

    /**
     * Helper to filter arrays based on query parameters
     */
    public static function filterArray(array $items, array $filters): array
    {
        return array_filter($items, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (empty($value))
                    continue;

                // Skip if key doesn't exist in item
                if (!isset($item[$key]))
                    continue;

                $itemValue = strtolower((string) $item[$key]);
                $searchValue = strtolower((string) $value);

                if (!str_contains($itemValue, $searchValue)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Helper to paginate arrays like Laravel models
     */
    public static function paginateArray($items, $perPage = 10)
    {
        $page = Paginator::resolveCurrentPage() ?: 1;
        $items = collect($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }
}
