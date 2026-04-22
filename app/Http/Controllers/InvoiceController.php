<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Helpers\AuthHelper;

class InvoiceController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/invoices';
    }

    public function index(Request $request)
    {
        return redirect()->route('invoice.create');
    }

    public function create(Request $request)
    {
        try {
            $branches = AuthHelper::isSuperAdmin()
                ? $this->decodeApiValue($this->apiClient()->get(config('services.pioneer.api_url') . '/branches', ['per_page' => 9999]), 'data', [])
                : [];
        } catch (\Exception $e) {
            $branches = [];
        }

        return view('invoices.form', [
            'invoice' => null,
            'branches' => $branches,
            'isSuperAdmin' => AuthHelper::isSuperAdmin(),
            'initialBranchId' => AuthHelper::isSuperAdmin()
                ? $request->input('branch_id')
                : session('branch_id'),
        ]);
    }

    public function masterData(Request $request)
    {
        $branchId = $request->input('branch_id');
        $branchParams = $this->getApiParams(array_filter([
            'branch_id' => $branchId,
        ], fn ($value) => $value !== null && $value !== ''));

        $response = $this->apiClient()->get(config('services.pioneer.api_url') . '/invoices/master-data', $branchParams);

        if ($response->failed()) {
            return response()->json([
                'message' => $this->decodeApiValue($response, 'message', 'Gagal memuat data master invoice.'),
            ], $response->status());
        }

        return response()->json($this->decodeApiJson($response, [
            'customers' => [],
            'machines' => [],
            'plate_variants' => [],
            'components' => [],
            'cost_types' => [],
            'cutting_prices' => [],
        ]));
    }

    public function store(Request $request)
    {
        try {
            $payload = $request->except(['_token', '_method']);

            if (($payload['machine_id'] ?? '') === '') {
                $payload['machine_id'] = null;
            }

            $payload  = $this->getApiParams($payload);
            $response = $this->apiClient()->post($this->apiUrl, $payload);

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal menyimpan invoice.');
                return back()->withErrors([$error])->withInput();
            }

            return redirect()->route('sales-list.index')->with('success', 'Invoice berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        try {
            $invoice = $this->fetchInvoiceDetail($id);

            if (empty($invoice)) {
                return redirect()->route('invoice.index')->with('error', 'Invoice tidak ditemukan.');
            }
        } catch (\Exception $e) {
            return redirect()->route('invoice.index')->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }

        $payments = $invoice['payments'] ?? [];

        return view('invoices.show', compact('invoice', 'payments'));
    }

    public function edit($id)
    {
        return redirect()->route('sales-list.index')
            ->with('error', 'Invoice yang sudah dibuat tidak dapat diedit. Silakan cancel pesanan dan buat invoice baru.');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('sales-list.index')
            ->with('error', 'Invoice yang sudah dibuat tidak dapat diedit. Silakan cancel pesanan dan buat invoice baru.');
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                return back()->with('error', 'Gagal menghapus invoice.');
            }

            return redirect()->route('invoice.index')->with('success', 'Invoice berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function printInvoice($id)
    {
        try {
            $invoice = $this->fetchInvoiceDetail($id);

            if (empty($invoice)) {
                return redirect()->route('sales-list.index')->with('error', 'Invoice tidak ditemukan.');
            }
        } catch (\Exception $e) {
            return redirect()->route('sales-list.index')->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }

        return view('invoices.print', compact('invoice'));
    }

    public function printSpk($id)
    {
        try {
            $invoice = $this->fetchInvoiceDetail($id);

            if (empty($invoice)) {
                return redirect()->route('sales-list.index')->with('error', 'Invoice tidak ditemukan.');
            }
        } catch (\Exception $e) {
            return redirect()->route('sales-list.index')->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }

        return view('invoices.spk', compact('invoice'));
    }

    private function fetchInvoiceDetail($id): array
    {
        $invoiceResponse = $this->apiClient()->get("{$this->apiUrl}/{$id}");

        if ($invoiceResponse->failed()) {
            return [];
        }

        return $this->decodeApiJson($invoiceResponse, []);
    }
}
