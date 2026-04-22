<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlateMaterialController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/plate-types';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data jenis material.');
            }

            $resData = $response->json();
            $materials = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'],
                $resData['per_page'],
                $resData['current_page'],
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('master.plat-material.index', compact('materials'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        try {
            $payload = $this->getApiParams(['name' => $request->name]);
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
                    'message' => 'Jenis material berhasil disimpan.',
                ]);
            }

            return back()->with('success', 'Jenis material berhasil disimpan.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string']);

        try {
            $payload = $this->getApiParams(['name' => $request->name]);
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
                    'message' => 'Jenis material berhasil diperbarui.',
                ]);
            }

            return back()->with('success', 'Jenis material berhasil diperbarui.');
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

            return back()->with('success', 'Jenis material berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }
}
