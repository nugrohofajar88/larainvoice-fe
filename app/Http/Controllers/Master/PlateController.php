<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlateController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url');
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['plate_type_id', 'size_id', 'branch_id']);
            $sortBy  = $request->input('sort_by', 'id');
            $sortDir = $request->input('sort_dir', 'asc');
            $page    = $request->input('page', 1);

            $params = $this->getApiParams(array_merge(
                array_filter($filters, fn($v) => $v !== null && $v !== ''),
                ['sort_by' => $sortBy, 'sort_dir' => $sortDir, 'page' => $page]
            ));

            $response = $this->apiClient()->get("{$this->apiUrl}/plate-variants", $params);

            if ($response->failed()) {
                Log::error("API Failure in PlateController@index [{$response->status()}]: " . $response->body());
                return back()->with('error', 'Gagal memuat data plat (Server Error ' . $response->status() . ').');
            }

            $resData = $response->json();
            $data    = $resData['data'] ?? $resData; // Fallback jika data tidak nested
            $total   = $resData['total'] ?? ($resData['meta']['total'] ?? 0);
            $perPage = $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15);
            $current = $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1);

            $plates  = new \Illuminate\Pagination\LengthAwarePaginator(
                $data,
                $total,
                $perPage,
                $current,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Fetch references for modal — per_page=9999 agar semua data terambil
            $plateTypes = $this->fetchRef('/plate-types');
            $sizes      = $this->fetchRef('/sizes');
            $branches   = $this->fetchRef('/branches');
            $branchSettings = collect($branches)->keyBy('id');

            $plates->setCollection(
                $plates->getCollection()->map(function ($item) use ($branchSettings) {
                    $item['minimum_stock'] = (int) (
                        data_get($item, 'minimum_stock')
                        ?? data_get($item, 'branch.setting.minimum_stock')
                        ?? data_get($branchSettings->get(data_get($item, 'branch_id')), 'setting.minimum_stock')
                        ?? 0
                    );

                    return $item;
                })
            );

            return view('master.plat.index', compact('plates', 'plateTypes', 'sizes', 'branches'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function getMulti(Request $request)
    {
        try {
            $branchId = $request->input('branch_id');
            $plateTypeId = $request->input('plate_type_id');
            
            $response = $this->apiClient()->get("{$this->apiUrl}/plate-variants/multi", [
                'branch_id' => $branchId,
                'plate_type_id' => $plateTypeId
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function batchUpdate(Request $request)
    {
        try {
            $response = $this->apiClient()->put("{$this->apiUrl}/plate-variants/batch", [
                'items' => $request->input('items')
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'plate_type_id' => 'required|integer',
                'size_id'       => 'required|integer',
                'branch_id'     => 'required|integer',
                'qty'           => 'required|integer|min:0',
                'price_buy'     => 'nullable|numeric|min:0',
                'price_sell'    => 'required|numeric|min:0',
                'is_active'     => 'required',
            ]);

            $payload = $this->buildPayload($validated);

            if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
                $payload['branch_id'] = (int) session('branch_id');
            }

            $response = $this->apiClient()->post("{$this->apiUrl}/plate-variants", $payload);

            if ($response->failed()) {
                return response()->json(
                    ['message' => $response->json('message') ?? 'Gagal menyimpan plat.'],
                    $response->status()
                );
            }

            return response()->json(['message' => 'Varian plat berhasil ditambahkan.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'plate_type_id' => 'required|integer',
                'size_id'       => 'required|integer',
                'branch_id'     => 'required|integer',
                'qty'           => 'required|integer|min:0',
                'price_buy'     => 'nullable|numeric|min:0',
                'price_sell'    => 'required|numeric|min:0',
                'is_active'     => 'required',
            ]);

            $payload = $this->buildPayload($validated);

            if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
                $payload['branch_id'] = (int) session('branch_id');
            }

            $response = $this->apiClient()->put("{$this->apiUrl}/plate-variants/{$id}", $payload);

            if ($response->failed()) {
                return response()->json(
                    ['message' => $response->json('message') ?? 'Gagal memperbarui plat.'],
                    $response->status()
                );
            }

            return response()->json(['message' => 'Varian plat berhasil diperbarui.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()->delete("{$this->apiUrl}/plate-variants/{$id}");

            if ($response->failed()) {
                return response()->json(
                    ['message' => $response->json('message') ?? 'Gagal menghapus plat.'],
                    $response->status()
                );
            }

            return response()->json(['message' => 'Varian plat berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Cast dan normalise payload sebelum dikirim ke API backend.
     */
    private function buildPayload(array $validated): array
    {
        return [
            'plate_type_id' => (int)  $validated['plate_type_id'],
            'size_id'       => (int)  $validated['size_id'],
            'branch_id'     => (int)  $validated['branch_id'],
            'qty'           => (int)  $validated['qty'],
            'price_buy'     => isset($validated['price_buy']) ? (int) $validated['price_buy'] : null,
            'price_sell'    => (int)  $validated['price_sell'],
            'is_active'     => filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Fetch reference data (plate-types, sizes, branches) — ambil SEMUA dengan per_page besar.
     */
    private function fetchRef(string $endpoint): array
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl . $endpoint, $this->getApiParams(['per_page' => 9999]));
            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning("Gagal fetch ref {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
}
