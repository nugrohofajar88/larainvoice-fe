<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/sales';
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'branch']);
        $sortBy = $request->input('sort_by', 'name');
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
                return back()->with('error', 'Gagal memuat data sales dari server.');
            }

            $resData = $response->json();

            // Format resource paginator API with meta
            $sales = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? ($resData['meta']['total'] ?? 0),
                $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15),
                $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $branches = $this->fetchBranches();

            return view('master.sales.index', compact('sales', 'branches'));

        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi ke backend bermasalah: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $payload = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255',
                'email' => 'nullable|email',
                'password' => 'required|string|min:6',
                'nik' => 'nullable|string|max:50',
                'branch_id' => 'required|integer',
            ]);

            if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
                $payload['branch_id'] = session('branch_id');
            }

            $response = $this->apiClient()
                ->post($this->apiUrl, $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menambahkan sales.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Sales berhasil ditambahkan.']);
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
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255',
                'email' => 'nullable|email',
                'password' => 'nullable|string|min:6',
                'nik' => 'nullable|string|max:50',
                'branch_id' => 'required|integer',
            ]);

            if (empty($payload['password'])) {
                unset($payload['password']);
            }

            if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
                $payload['branch_id'] = session('branch_id');
            }

            $response = $this->apiClient()
                ->put("{$this->apiUrl}/{$id}", $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui data sales.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Sales berhasil diperbarui.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke server: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()
                ->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus sales.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Sales berhasil dihapus.']);
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
            Log::warning('Gagal fetch branches untuk form sales: ' . $e->getMessage());
            return [];
        }
    }
}
