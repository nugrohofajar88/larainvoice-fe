<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MachineController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/machines';
    }

    public function index(Request $request)
    {
        try {
            $response = $this->apiClient()
                ->get($this->apiUrl, $this->getApiParams($request->all()));

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data mesin.');
            }

            $resData = $this->decodeApiJson($response, []);
            $machines = new \Illuminate\Pagination\LengthAwarePaginator(
                $resData['data'] ?? [],
                $resData['total'] ?? ($resData['meta']['total'] ?? 0),
                $resData['per_page'] ?? ($resData['meta']['per_page'] ?? 15),
                $resData['current_page'] ?? ($resData['meta']['current_page'] ?? 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $branches = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/branches', ['per_page' => 9999])
                ->json('data') ?? [];

            $machineTypes = $this->apiClient()
                ->get(config('services.pioneer.api_url') . '/machine-types', ['per_page' => 9999])
                ->json('data') ?? [];

            $components = $this->fetchApiCollection(
                config('services.pioneer.api_url') . '/components',
                $this->getApiParams([])
            );

            return view('master.mesin.index', compact('machines', 'branches', 'machineTypes', 'components'));
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_number' => 'required|string|max:255',
            'machine_type_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'base_price' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:20480',
            'components' => 'nullable|array',
            'components.*.component_id' => 'required|integer',
            'components.*.qty' => 'required|integer|min:1',
        ]);

        if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
            $validated['branch_id'] = session('branch_id');
        }

        try {
            $response = $this->forwardStoreRequest($request, $validated);

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal menyimpan data mesin.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Mesin berhasil ditambahkan.']);
        } catch (\Illuminate\Validation\ValidationException $v) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $v->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'machine_number' => 'required|string|max:255',
            'machine_type_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'base_price' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:20480',
            'components' => 'nullable|array',
            'components.*.component_id' => 'required|integer',
            'components.*.qty' => 'required|integer|min:1',
        ]);

        if (!\App\Helpers\AuthHelper::isSuperAdmin()) {
            $validated['branch_id'] = session('branch_id');
        }

        try {
            $response = $this->forwardUpdateRequest($request, $validated, $id);

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memperbarui data mesin.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Mesin berhasil diperbarui.']);
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
                $error = $this->decodeApiValue($response, 'message', 'Gagal menghapus data mesin.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'Mesin berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    public function downloadFile($id, $fileId)
    {
        try {
            $response = Http::withToken(session('api_token'))
                ->get("{$this->apiUrl}/{$id}/files/{$fileId}/download");

            if ($response->failed()) {
                return back()->with('error', 'File mesin gagal diunduh.');
            }

            $disposition = $response->header('Content-Disposition', '');
            $filename = 'machine-file-' . $fileId;

            if (preg_match('/filename=\"?([^\";]+)\"?/i', $disposition, $matches)) {
                $filename = $matches[1];
            }

            return response($response->body(), 200, [
                'Content-Type' => $response->header('Content-Type', 'application/octet-stream'),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function destroyFile($id, $fileId)
    {
        try {
            $response = $this->apiClient()->delete("{$this->apiUrl}/{$id}/files/{$fileId}");

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'File mesin gagal dihapus.');
                return response()->json(['message' => $error], $response->status());
            }

            return response()->json(['message' => 'File mesin berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi gagal: ' . $e->getMessage()], 500);
        }
    }

    private function flattenPayload(array $validated): array
    {
        $payload = $validated;
        unset($payload['files']);

        foreach ($validated['components'] ?? [] as $index => $component) {
            $payload["components[$index][component_id]"] = $component['component_id'];
            $payload["components[$index][qty]"] = $component['qty'];
        }

        unset($payload['components']);

        return $payload;
    }

    private function forwardStoreRequest(Request $request, array $validated)
    {
        if (!$request->hasFile('files')) {
            return $this->apiClient()->post($this->apiUrl, $validated);
        }

        $client = Http::withToken(session('api_token'))->withHeaders([
            'Accept' => 'application/json',
        ]);

        foreach ($request->file('files', []) as $index => $file) {
            $client = $client->attach(
                "files[$index]",
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        return $client->post($this->apiUrl, $this->flattenPayload($validated));
    }

    private function forwardUpdateRequest(Request $request, array $validated, $id)
    {
        if (!$request->hasFile('files')) {
            return $this->apiClient()->put("{$this->apiUrl}/{$id}", $validated);
        }

        $client = Http::withToken(session('api_token'))->withHeaders([
            'Accept' => 'application/json',
        ]);

        foreach ($request->file('files', []) as $index => $file) {
            $client = $client->attach(
                "files[$index]",
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        $payload = array_merge(
            $this->flattenPayload($validated),
            ['_method' => 'PUT']
        );

        return $client->post("{$this->apiUrl}/{$id}", $payload);
    }
}
