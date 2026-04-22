<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\AuthHelper;
// use App\Services\MockDataService;

class CustomerController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/customers';
    }

    public function index(Request $request)
    {
        $filters = $request->only(['full_name', 'branch', 'phone_number']);
        $sortBy = $request->input('sort_by', 'full_name');
        $sortDir = $request->input('sort_dir', 'asc');
        $page = $request->input('page', 1);

        try {
            $params = $this->getApiParams(array_merge(array_filter($filters, fn($v) => $v !== null && $v !== ''), [
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'page' => $page,
            ]));

            $response = $this->apiClient()->get($this->apiUrl, $params);

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data pelanggan dari server.');
            }

            $resData = $response->json();
            
            $customers = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? 0,
                $resData['per_page'] ?? 15,
                $resData['current_page'] ?? 1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Fetch reference data for the modal
            $branches = $this->fetchBranches();
            $sales = $this->fetchSales();

            return view('master.pelanggan.index', compact('customers', 'branches', 'sales'));

        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi ke backend bermasalah: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $payload = $request->validate([
                'full_name' => 'required|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'branch_id' => 'required|integer',
                'sales_id' => 'nullable|integer',
                'phone_number' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
            ]);

            if (!AuthHelper::isSuperAdmin()) {
                $payload['branch_id'] = session('branch_id');
            }

            $response = $this->apiClient()->post($this->apiUrl, $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menambahkan pelanggan.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Pelanggan berhasil ditambahkan.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke server: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payload = $request->validate([
                'full_name' => 'required|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'branch_id' => 'required|integer',
                'sales_id' => 'nullable|integer',
                'phone_number' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
            ]);

            if (!AuthHelper::isSuperAdmin()) {
                $payload['branch_id'] = session('branch_id');
            }

            $response = $this->apiClient()->put("{$this->apiUrl}/{$id}", $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui data pelanggan.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Pelanggan berhasil diperbarui.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke server: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus pelanggan.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Pelanggan berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    private function fetchBranches(): array
    {
        try {
            $response = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/branches', $this->getApiParams([
                    'sort_by' => 'name',
                    'sort_dir' => 'asc',
                    'per_page' => 999,
                ]));
            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Gagal fetch branches untuk form pelanggan: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchSales(): array
    {
        try {
            $response = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/sales', $this->getApiParams([
                    'per_page' => 999,
                ]));
            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Gagal fetch sales untuk form pelanggan: ' . $e->getMessage());
            return [];
        }
    }
}
