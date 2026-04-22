<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComponentCategoryController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/component-categories';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data kategori komponen.');
            }

            $resData = $response->json();
            $componentCategories = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? 0,
                $resData['per_page'] ?? 15,
                $resData['current_page'] ?? 1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('master.component-category.index', compact('componentCategories'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        try {
            $payload = $this->getApiParams(['name' => $request->name]);
            $response = $this->apiClient()->post($this->apiUrl, $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menyimpan data.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $error], $response->status());
                }
                return back()->withErrors([$error])->withInput();
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Kategori komponen berhasil disimpan.',
                ]);
            }

            if ($request->input('submit_mode') === 'create_another') {
                return back()
                    ->with('success', 'Kategori komponen berhasil disimpan. Silakan lanjut input berikutnya.')
                    ->with('reopen_component_category_create', true);
            }

            return back()->with('success', 'Kategori komponen berhasil disimpan.');
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
            $response = $this->apiClient()->put("{$this->apiUrl}/{$id}", $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui data.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $error], $response->status());
                }
                return back()->withErrors([$error])->withInput();
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Kategori komponen berhasil diperbarui.',
                ]);
            }

            return back()->with('success', 'Kategori komponen berhasil diperbarui.');
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
            $response = $this->apiClient()->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus data.';
                return back()->with('error', $error);
            }

            return back()->with('success', 'Kategori komponen berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }
}
