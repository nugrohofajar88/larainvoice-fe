<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlateSizeController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/sizes';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data size.');
            }

            $resData = $response->json();
            $sizes = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'],
                $resData['per_page'],
                $resData['current_page'],
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('master.plat-size.index', compact('sizes'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate(['value' => 'required|string']);

        try {
            $payload = $this->getApiParams(['value' => $request->value]);
            $response = $this->apiClient()
                ->post($this->apiUrl, $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menyimpan data.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $error], $response->status());
                }
                return back()->withErrors([$error])->withInput();
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Ukuran plat berhasil disimpan.',
                ]);
            }

            return back()->with('success', 'Ukuran plat berhasil disimpan.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate(['value' => 'required|string']);

        try {
            $payload = $this->getApiParams(['value' => $request->value]);
            $response = $this->apiClient()
                ->put("{$this->apiUrl}/{$id}", $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui data.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $error], $response->status());
                }
                return back()->withErrors([$error])->withInput();
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Ukuran plat berhasil diperbarui.',
                ]);
            }

            return back()->with('success', 'Ukuran plat berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()
                ->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                return back()->with('error', 'Gagal menghapus data.');
            }

            return back()->with('success', 'Ukuran plat berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }
}
