<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/components';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data komponen.');
            }

            $resData = $response->json();
            $components = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? ($resData['meta']['total'] ?? 0),
                $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15),
                $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $componentReferences = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams([
                    'per_page' => 9999,
                    'sort_by' => 'name',
                    'sort_dir' => 'asc',
                ]))
                ->json('data') ?? [];

            $branches = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/branches', ['per_page' => 9999])
                ->json('data') ?? [];

            $suppliers = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/suppliers', $this->getApiParams(['per_page' => 9999]))
                ->json('data') ?? [];

            $componentCategories = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/component-categories', ['per_page' => 9999])
                ->json('data') ?? [];

            return view('master.component.index', compact('components', 'componentReferences', 'branches', 'suppliers', 'componentCategories'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'existing_component_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'type_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric',
            'supplier_id' => 'nullable|integer',
            'component_category_id' => 'nullable|integer',
            'branch_id' => 'required|integer',
            'qty' => 'required|integer',
            'price_buy' => 'nullable|numeric|min:0',
            'price_sell' => 'required|numeric|min:0',
        ]);

        if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
            $validated['branch_id'] = session('branch_id');
        }

        try {
            $response = $this->apiClient()->post($this->apiUrl, $validated);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menyimpan data komponen.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Komponen berhasil ditambahkan.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric',
            'supplier_id' => 'nullable|integer',
            'component_category_id' => 'nullable|integer',
            'branch_id' => 'required|integer',
            'qty' => 'required|integer',
            'price_buy' => 'nullable|numeric|min:0',
            'price_sell' => 'required|numeric|min:0',
        ]);

        if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
            $validated['branch_id'] = session('branch_id');
        }

        try {
            $response = $this->apiClient()->put("{$this->apiUrl}/{$id}", $validated);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui data komponen.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Komponen berhasil diperbarui.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus data komponen.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Komponen berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }
}
