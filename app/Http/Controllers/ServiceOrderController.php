<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceOrderController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/service-orders';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data order jasa.');
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
            $typeSummary = collect($summaryOrders)->countBy(fn ($item) => $item['order_type'] ?? 'service')->all();

            $branches = AuthHelper::isSuperAdmin()
                ? $this->fetchApiCollection(config('services.pioneer.api_url') . '/branches', $params)
                : [];

            return view('service-orders.index', compact('orders', 'branches', 'statusSummary', 'typeSummary'));
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

            $baseUrl = config('services.pioneer.api_url');
            return response()->json([
                'customers' => $this->fetchApiCollection($baseUrl . '/customers', $params),
                'users' => $this->fetchApiCollection($baseUrl . '/users', $params),
                'components' => $this->fetchApiCollection($baseUrl . '/components', $params),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat master data order jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $response = $this->apiClient()->post($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal menyimpan order jasa.');
                return response()->json(['message' => $error, 'errors' => $this->decodeApiValue($response, 'errors', [])], $response->status());
            }

            return response()->json([
                'message' => 'Order jasa berhasil disimpan.',
                'data' => $this->decodeApiValue($response, 'data', []),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $response = $this->apiClient()->put("{$this->apiUrl}/{$id}", $this->getApiParams($request->all()));

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memperbarui order jasa.');
                return response()->json(['message' => $error, 'errors' => $this->decodeApiValue($response, 'errors', [])], $response->status());
            }

            return response()->json([
                'message' => 'Order jasa berhasil diperbarui.',
                'data' => $this->decodeApiValue($response, 'data', []),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $response = $this->apiClient()->patch("{$this->apiUrl}/{$id}/status", $this->getApiParams($request->all()));

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memperbarui status order jasa.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json([
                'message' => $this->decodeApiValue($response, 'message', 'Status berhasil diperbarui.'),
                'data' => $this->decodeApiValue($response, 'data', []),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal menghapus order jasa.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Order jasa berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $response = $this->apiClient()->get("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memuat detail order jasa.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json($this->decodeApiJson($response, []));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function createInvoice(Request $request, $id)
    {
        try {
            $response = $this->apiClient()->post("{$this->apiUrl}/{$id}/create-invoice", $this->getApiParams($request->all()));

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal membuat invoice dari order jasa.');
                return response()->json(['message' => $error, 'errors' => $this->decodeApiValue($response, 'errors', [])], $response->status());
            }

            return response()->json([
                'message' => 'Invoice berhasil dibuat dari order jasa.',
                'data' => $this->decodeApiValue($response, 'data', []),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }
}
