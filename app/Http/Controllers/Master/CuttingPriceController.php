<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CuttingPriceController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/cutting-prices';
    }

    public function index(Request $request)
    {
        try {
            // Fetch main data
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data harga cutting.');
            }

            $prices = $response->json('data') ?? [];

            // Fetch Reference Data for Add New form
            $machineTypes = $this->apiClient()->get(config('services.pioneer.api_url') . '/machine-types', ['per_page' => 9999])->json('data') ?? [];
            $plateTypes   = $this->apiClient()->get(config('services.pioneer.api_url') . '/plate-types', ['per_page' => 9999])->json('data') ?? [];
            $sizes        = $this->apiClient()->get(config('services.pioneer.api_url') . '/sizes', ['per_page' => 9999])->json('data') ?? [];

            return view('master.harga-cutting.index', compact('prices', 'machineTypes', 'plateTypes', 'sizes'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function getMulti(Request $request)
    {
        try {
            $response = $this->apiClient()->get("{$this->apiUrl}/multi", [
                'machine_type_id' => $request->machine_type_id,
                'plate_type_id' => $request->plate_type_id
            ]);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function batchUpdate(Request $request)
    {
        try {
            $response = $this->apiClient()->put("{$this->apiUrl}/batch", [
                'items' => $request->items
            ]);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $response = $this->apiClient()->post($this->apiUrl, $request->all());

            if ($response->failed()) {
                return response()->json($response->json(), $response->status());
            }

            return response()->json(['message' => 'Harga cutting berhasil ditambahkan']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
