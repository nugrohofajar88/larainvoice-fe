<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CostTypeController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/cost-types';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data tipe biaya.');
            }

            $resData = $response->json();
            $costTypes = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? 0,
                $resData['per_page'] ?? 15,
                $resData['current_page'] ?? 1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('master.cost-type.index', compact('costTypes'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        try {
            $payload = $this->getApiParams($request->only(['name', 'description']));
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
                    'message' => 'Tipe biaya berhasil disimpan.',
                ]);
            }

            if ($request->input('submit_mode') === 'create_another') {
                return back()
                    ->with('success', 'Tipe biaya berhasil disimpan. Silakan lanjut input berikutnya.')
                    ->with('reopen_cost_type_create', true);
            }

            return back()->with('success', 'Tipe biaya berhasil disimpan.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        try {
            $payload = $this->getApiParams($request->only(['name', 'description']));
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
                    'message' => 'Tipe biaya berhasil diperbarui.',
                ]);
            }

            return back()->with('success', 'Tipe biaya berhasil diperbarui.');
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

            return back()->with('success', 'Tipe biaya berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }
}
