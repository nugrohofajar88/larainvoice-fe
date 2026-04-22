<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Daftar role dengan filter & paginasi.
     */
    public function index()
    {
        $filters = request()->only(['name']);
        $sortBy  = request('sort_by', 'name');
        $sortDir = request('sort_dir', 'asc');
        $page    = request('page', 1);

        try {
            $params = array_merge(array_filter($filters, fn($v) => $v !== null && $v !== ''), [
                'sort_by'  => $sortBy,
                'sort_dir' => $sortDir,
                'page'     => $page,
            ]);

            $response = Http::withToken(session('api_token'))
                ->get(config('services.pioneer.api_url') . '/roles', $params);

            if ($response->failed()) {
                $status = $response->status();
                $body   = $response->body();
                
                \Log::error('Gagal fetch roles', [
                    'status' => $status,
                    'body'   => substr($body, 0, 1000), // Ambil 1000 karakter pertama jika error HTML
                ]);

                // Coba ambil pesan dari JSON, fallback ke status code
                $resJson = $response->json();
                $error   = $resJson['message'] ?? ($resJson['error'] ?? "Server API mengembalikan error (Status: {$status})");
                
                return back()->with('error', $error);
            }

            $resData = $response->json();

            // FLATKAN permission tree supaya perhitungan stats di index.blade.php tetap jalan
            // (karena blade index menghitung total & count secara linear)
            foreach ($resData['data'] as &$role) {
                if (!empty($role['permissions'])) {
                    $role['permissions'] = $this->flattenPermissions($role['permissions']);
                }
            }

            $roles = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'],
                $resData['total'],
                $resData['per_page'],
                $resData['current_page'],
                ['path' => request()->url(), 'query' => request()->query()]
            );

        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi ke backend bermasalah: ' . $e->getMessage());
        }

        return view('master.role.index', compact('roles'));
    }

    /**
     * Form tambah role baru.
     * Module list diambil dinamis dari API.
     */
    public function create()
    {
        $modules = $this->fetchTemplate();
        return view('master.role.form', ['role' => null, 'modules' => $modules]);
    }

    /**
     * Simpan role baru ke API.
     */
    public function store(Request $request)
    {
        try {
            $payload = $this->preparePayload($request->all());

            Log::info('Creating Role. URL: ' . config('services.pioneer.api_url') . '/roles', ['payload' => $payload]);

            $response = Http::withToken(session('api_token'))
                ->timeout(10)
                ->post(config('services.pioneer.api_url') . '/roles', $payload);

            Log::info('Create Role Response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if ($response->failed()) {
                $error  = $response->json('message') ?? 'Gagal menambahkan role.';
                $errors = $response->json('errors') ?? [];
                return back()->withErrors([$error])->withInput()->with('api_errors', $errors);
            }

            return redirect()->route('master.role.index')->with('success', 'Role berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal terhubung ke server: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Form edit role — data + permissions langsung dari API.
     * Module list diambil dari permissions role itu sendiri (dinamis, tidak hardcode).
     */
    public function edit($id)
    {
        try {
            $response = Http::withToken(session('api_token'))
                ->get(config('services.pioneer.api_url') . "/roles/{$id}");

            if ($response->failed()) {
                return redirect()->route('master.role.index')->with('error', 'Role tidak ditemukan.');
            }

            $role    = $response->json();
            // Urutkan permissions berdasarkan sort_order dari API
            $modules = $this->sortByOrder($role['permissions'] ?? []);

            return view('master.role.form', compact('role', 'modules'));
        } catch (\Exception $e) {
            return redirect()->route('master.role.index')->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    /**
     * Update role ke API.
     */
    public function update(Request $request, $id)
    {
        try {
            $payload = $this->preparePayload($request->all(), (int) $id);

            Log::info("Updating Role {$id}. URL: " . config('services.pioneer.api_url') . "/roles/{$id}", ['payload' => $payload]);

            $response = Http::withToken(session('api_token'))
                ->timeout(10)
                ->put(config('services.pioneer.api_url') . "/roles/{$id}", $payload);

            Log::info('Update Role Response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if ($response->failed()) {
                $error  = $response->json('message') ?? 'Gagal memperbarui role.';
                $errors = $response->json('errors') ?? [];
                return back()->withErrors([$error])->withInput()->with('api_errors', $errors);
            }

            return redirect()->route('master.role.index')->with('success', 'Role berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal terhubung ke server: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hapus role via API.
     */
    public function destroy($id)
    {
        try {
            $response = Http::withToken(session('api_token'))
                ->delete(config('services.pioneer.api_url') . "/roles/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus role.';
                return back()->with('error', $error);
            }

            return redirect()->route('master.role.index')->with('success', 'Role berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Urutkan array permissions berdasarkan field sort_order (ascending).
     * Jika sort_order tidak ada, default ke 0 (muncul paling awal).
     */
    private function sortByOrder(array $permissions): array
    {
        usort($permissions, fn($a, $b) =>
            ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0)
        );

        // Sort children recursively jika ada
        foreach ($permissions as &$perm) {
            if (!empty($perm['children'])) {
                $perm['children'] = $this->sortByOrder($perm['children']);
            }
        }

        return $permissions;
    }

    /**
     * Ambil template daftar modul & permission dari endpoint khusus.
     * Digunakan saat membuat role baru (form create).
     * Endpoint: GET /roles/permissions/template
     *
     * Response dapat berupa:
     *   - Array langsung: [ { id, module, view, create, edit, delete }, ... ]
     *   - Object pagination: { data: [ ... ] }
     */
    private function fetchTemplate(): array
    {
        try {
            $response = Http::withToken(session('api_token'))
                ->get(config('services.pioneer.api_url') . '/roles/permissions/template');

            if ($response->successful()) {
                $body = $response->json();

                // Tangani dua kemungkinan format response
                $items = isset($body['data']) ? $body['data'] : (array) $body;

                // Petakan secara rekursif supaya children tetap terjaga
                $items = $this->mapPermissions($items);

                return $this->sortByOrder($items);
            }
        } catch (\Exception $e) {
            Log::warning('Gagal fetch template permission: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Helper rekursif untuk memastikan setiap item (termasuk children) 
     * memiliki field permission dasar yang dibutuhkan frontend.
     */
    private function mapPermissions(array $items): array
    {
        return array_map(function($perm) {
            $mapped = [
                'id'         => $perm['id']         ?? null,
                'name'       => $perm['name']       ?? '',
                'module'     => $perm['module']      ?? '',
                'parent_id'  => $perm['parent_id']  ?? null,
                'sort_order' => $perm['sort_order'] ?? 0,
                'view'       => $perm['view']       ?? false,
                'create'     => $perm['create']     ?? false,
                'edit'       => $perm['edit']       ?? false,
                'delete'     => $perm['delete']     ?? false,
                'detail'     => $perm['detail']     ?? false,
            ];

            // Rekursi untuk children
            if (!empty($perm['children'])) {
                $mapped['children'] = $this->mapPermissions($perm['children']);
            } else {
                $mapped['children'] = [];
            }

            return $mapped;
        }, $items);
    }

    /**
     * Bangun payload untuk dikirim ke API.
     *
     * Format POST (create, $roleId = null):
     *   { name, permissions: [{ id, module, view, create, edit, delete }, ...] }
     *
     * Format PUT (update, $roleId = int):
     *   { id, name, permissions: [{ id, view, create, edit, delete }, ...] }
     *   — module TIDAK disertakan saat update (API identify via permission id)
     *   — role id disertakan di level atas payload
     */
    private function preparePayload(array $data, ?int $roleId = null): array
    {
        $isUpdate      = $roleId !== null;
        $modules       = $data['modules'] ?? [];
        $permissionIds = $data['permission_ids'] ?? [];
        $actions       = ['view', 'create', 'edit', 'delete', 'detail'];

        $payload = [
            'name'        => trim($data['name'] ?? ''),
            'permissions' => [],
        ];

        // PUT: sertakan role id di top-level
        if ($isUpdate) {
            $payload['id'] = $roleId;
        }

        foreach ($modules as $module) {
            $permId = (int) ($permissionIds[$module] ?? 0);

            if ($isUpdate) {
                // PUT: hanya id permission + boolean aksi, TANPA module
                $perm = ['id' => $permId];
            } else {
                // POST: sertakan id (menu id) + module + boolean aksi
                $perm = [
                    'id'     => $permId ?: null,
                    'module' => $module,
                ];
            }

            foreach ($actions as $action) {
                $perm[$action] = isset($data['permissions'][$module][$action]);
            }

            $payload['permissions'][] = $perm;
        }

        return $payload;
    }

    /**
     * Helper untuk meratakan tree permissions menjadi array flat.
     * Digunakan agar index.blade.php bisa menghitung total module aktif.
     */
    private function flattenPermissions(array $items): array
    {
        $flattened = [];
        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            // Buat salinan item tanpa children untuk daftar flat
            $flatItem = $item;
            unset($flatItem['children']);
            $flattened[] = $flatItem;

            if (!empty($children)) {
                $flattened = array_merge($flattened, $this->flattenPermissions($children));
            }
        }
        return $flattened;
    }
}
