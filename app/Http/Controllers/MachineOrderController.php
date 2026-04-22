<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MachineOrderController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/machine-orders';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data machine order.');
            }

            $resData = $this->decodeApiJson($response, []);
            $orders = new LengthAwarePaginator(
                $resData['data'] ?? [],
                $resData['total'] ?? ($resData['meta']['total'] ?? 0),
                $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15),
                $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $params = $this->getApiParams([]);
            $summaryOrders = $this->fetchApiCollection($this->apiUrl, $params);
            $statusSummary = collect($summaryOrders)->countBy(fn ($item) => $item['status'] ?? 'draft')->all();
            $costTypes = $this->fetchApiCollection(config('services.pioneer.api_url') . '/cost-types', $params);
            $branches = AuthHelper::isSuperAdmin()
                ? $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $params)
                : [];

            return view('machine-orders.index', compact('orders', 'costTypes', 'branches', 'statusSummary'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function masterData(Request $request)
    {
        try {
            $params = $this->getApiParams(array_filter([
                'branch_id' => $request->branch_id,
            ], fn ($value) => $value !== null && $value !== ''));

            return response()->json([
                'customers' => $this->fetchApiCollection(config('services.pioneer.api_url') . '/customers', $params),
                'machines' => $this->fetchApiCollection(config('services.pioneer.api_url') . '/machines', $params),
                'components' => $this->fetchApiCollection(config('services.pioneer.api_url') . '/components', $params),
                'sales' => $this->fetchApiCollection(config('services.pioneer.api_url') . '/sales', $params),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat master data machine order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $response = $this->apiClient()->post($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal menyimpan machine order.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json([
                'message' => 'Machine order berhasil disimpan.',
                'data' => $this->decodeApiValue($response, 'data', []),
            ]);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $response = $this->apiClient()->put("{$this->apiUrl}/{$id}", $this->getApiParams($request->all()));

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memperbarui machine order.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json([
                'message' => 'Machine order berhasil diperbarui.',
                'data' => $this->decodeApiValue($response, 'data', []),
            ]);
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
                $error = $this->decodeApiValue($response, 'message', 'Gagal menghapus machine order.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Machine order berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $response = $this->apiClient()->get("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memuat detail machine order.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json($this->decodeApiJson($response, []));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }
}
