<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\MockDataService;

class BranchController extends Controller
{
    public function index()
    {
        $filters = request()->only(['name', 'city', 'phone']);
        $sortBy = request('sort_by', 'id');
        $sortDir = request('sort_dir', 'asc');
        $page = request('page', 1);

        try {
            $params = $this->getApiParams(array_merge($filters, [
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'page' => $page
            ]));

            $response = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/branches', $params);

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data cabang dari server.');
            }

            $resData = $response->json();

            // Convert API pagination to Laravel Paginator for Blade compatibility
            $branches = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? ($resData['meta']['total'] ?? 0),
                $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15),
                $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1),
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('master.cabang.index', compact('branches'));

        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi ke backend bermasalah: ' . $e->getMessage());
        }
    }

    public function store(\Illuminate\Http\Request $request)
    {
        try {
            $payload = $this->preparePayload($request->all());

            $response = $this->apiClient()
                ->timeout(10)
                ->post(config('services.pioneer.api_url') . '/branches', $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menambahkan cabang.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Cabang berhasil ditambahkan.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke server: ' . $e->getMessage()], 500);
        }
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        try {
            $payload = $this->preparePayload($request->all());

            $response = $this->apiClient()
                ->timeout(10)
                ->put(config('services.pioneer.api_url') . "/branches/{$id}", $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui cabang.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Cabang berhasil diperbarui.']);
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
                ->delete(config('services.pioneer.api_url') . "/branches/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus cabang.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Cabang berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    private function preparePayload(array $data): array
    {
        // Only allowed fields in API request
        $allowedFields = ['name', 'city', 'address', 'phone', 'email', 'website', 'bank_accounts', 'setting'];
        $cleanData = array_intersect_key($data, array_flip($allowedFields));

        // 1. Sanitize Bank Accounts
        if (isset($cleanData['bank_accounts']) && is_array($cleanData['bank_accounts'])) {
            foreach ($cleanData['bank_accounts'] as $key => $bank) {
                // Remove empty ID or cast to integer
                if (empty($bank['id'])) {
                    unset($cleanData['bank_accounts'][$key]['id']);
                } else {
                    $cleanData['bank_accounts'][$key]['id'] = (int) $bank['id'];
                }

                // Cast is_default to boolean (server expects true/false in JSON)
                // Handle various types of input (checkbox "1", boolean true, etc.)
                if (isset($bank['is_default'])) {
                    $cleanData['bank_accounts'][$key]['is_default'] = filter_var($bank['is_default'], FILTER_VALIDATE_BOOLEAN);
                } else {
                    $cleanData['bank_accounts'][$key]['is_default'] = false;
                }
            }
            // Ensure array indices are numeric and clean
            $cleanData['bank_accounts'] = array_values($cleanData['bank_accounts']);
        }

        // 2. Sanitize Setting
        if (isset($cleanData['setting']) && is_array($cleanData['setting'])) {
            // Remove null values to let backend use defaults or avoid constraint issues
            $cleanData['setting'] = array_filter($cleanData['setting'], function ($value) {
                return !is_null($value);
            });

            if (isset($cleanData['setting']['minimum_stock'])) {
                $cleanData['setting']['minimum_stock'] = (int) $cleanData['setting']['minimum_stock'];
            }
            if (isset($cleanData['setting']['sales_commission_percentage'])) {
                $cleanData['setting']['sales_commission_percentage'] = (float) $cleanData['setting']['sales_commission_percentage'];
            }
        }

        return $cleanData;
    }
}
