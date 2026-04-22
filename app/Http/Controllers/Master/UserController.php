<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\AuthHelper;

class UserController extends Controller
{
    /**
     * Daftar pengguna dengan filter, sort & paginasi.
     * Filter yang didukung: name, username, role_name, branch_name
     */
    public function index()
    {
        $filters = request()->only(['name', 'username', 'role_name', 'branch_name']);
        $sortBy = request('sort_by', 'name');
        $sortDir = request('sort_dir', 'asc');
        $page = request('page', 1);

        try {
            $params = $this->getApiParams(array_merge(array_filter($filters, fn($v) => $v !== null && $v !== ''), [
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'page' => $page,
            ]));

            $response = $this->apiClient()->get(config('services.pioneer.api_url') . '/users', $params);

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data pengguna dari server.');
            }

            $resData = $response->json();

            $users = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'] ?? ($resData['meta']['total'] ?? 0),
                $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15),
                $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1),
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // Fetch references for modal form
            $branches = $this->fetchBranches();
            $roles = $this->fetchRoles();

            return view('master.pengguna.index', compact('users', 'branches', 'roles'));

        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi ke backend bermasalah: ' . $e->getMessage());
        }
    }

    /**
     * Form tambah pengguna — branches diambil dari API.
     */
    public function create()
    {
        $branches = $this->fetchBranches();
        $roles = $this->fetchRoles();
        return view('master.pengguna.form', ['user' => null, 'branches' => $branches, 'roles' => $roles]);
    }

    /**
     * Simpan pengguna baru ke API.
     */
    public function store(Request $request)
    {
        try {
            $payload = $this->preparePayload($request->all());

            $response = $this->apiClient()
                ->timeout(10)
                ->post(config('services.pioneer.api_url') . '/users', $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menambahkan pengguna.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Pengguna berhasil ditambahkan.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke server: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payload = $this->preparePayload($request->all());

            if (empty($payload['password'])) {
                unset($payload['password']);
            }

            $response = $this->apiClient()
                ->timeout(10)
                ->put(config('services.pioneer.api_url') . "/users/{$id}", $payload);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal memperbarui pengguna.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Pengguna berhasil diperbarui.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke server: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Hapus pengguna via API.
     */
    public function destroy($id)
    {
        try {
            $response = $this->apiClient()
                ->delete(config('services.pioneer.api_url') . "/users/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus pengguna.';
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Pengguna berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Ambil daftar cabang dari API untuk dropdown form.
     * Fallback ke array kosong jika API gagal.
     */
    private function fetchBranches(): array
    {
        try {
            $response = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/branches', $this->getApiParams([
                    'sort_by' => 'name',
                    'sort_dir' => 'asc',
                    'per_page' => 100,
                ]));
            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Gagal fetch branches untuk form pengguna: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil daftar role dari API untuk dropdown form.
     * Fallback ke array kosong jika API gagal.
     */
    private function fetchRoles(): array
    {
        try {
            $response = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/roles', [
                    'sort_by' => 'name',
                    'sort_dir' => 'asc',
                    'per_page' => 100,
                ]);
            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Gagal fetch roles untuk form pengguna: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Bersihkan & siapkan payload untuk dikirim ke API.
     */
    private function preparePayload(array $data): array
    {
        $allowed = ['name', 'username', 'email', 'password', 'role_id', 'branch_id'];
        $payload = array_intersect_key($data, array_flip($allowed));

        // Cast ke integer
        if (isset($payload['role_id'])) {
            $payload['role_id'] = (int) $payload['role_id'];
        }
        if (isset($payload['branch_id'])) {
            $payload['branch_id'] = $payload['branch_id'] !== '' ? (int) $payload['branch_id'] : null;
        }

        if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
            $payload['branch_id'] = session('branch_id');
        }

        return $payload;
    }
}
