<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/menus';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()->get($this->apiUrl);

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat daftar menu.');
            }

            $tree = $response->json();
            $menuList = [];
            $this->flattenMenus($tree, $menuList);

            // Fetch tree list for parent selection
            $menuTree = $tree;

            return view('master.menu.index', [
                'menus' => $menuList, 
                'menuTree' => $menuTree
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    /**
     * Recursive helper to flatten the tree for table display
     */
    private function flattenMenus($tree, &$list, $level = 0)
    {
        foreach ($tree as $item) {
            $children = $item['children'] ?? [];
            $item['level'] = $level;
            
            // To prevent large nested arrays in the flattened list
            $temp = $item;
            unset($temp['children']);
            $list[] = $temp;

            if (!empty($children)) {
                $this->flattenMenus($children, $list, $level + 1);
            }
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'parent_id' => 'nullable',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'sort_order' => 'required|integer',
            'is_active' => 'required|boolean',
        ]);

        try {
            $response = $this->apiClient()
                ->post($this->apiUrl, $validated);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menyimpan menu.';
                return back()->withErrors([$error])->withInput();
            }

            return back()->with('success', 'Menu berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'parent_id' => 'nullable',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'sort_order' => 'required|integer',
            'is_active' => 'required|boolean',
        ]);

        try {
            $response = $this->apiClient()
                ->put("{$this->apiUrl}/{$id}", $validated);

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal mengupdate menu.';
                return back()->withErrors([$error])->withInput();
            }

            return back()->with('success', 'Menu berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $response = $this->apiClient()
                ->delete("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                $error = $response->json('message') ?? 'Gagal menghapus menu.';
                return back()->with('error', $error);
            }

            return back()->with('success', 'Menu berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }
}
