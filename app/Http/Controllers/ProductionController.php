<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class ProductionController extends Controller
{
    private $apiUrl;
    private const STATUS_TABS = ['pending', 'in-process', 'completed', 'cancelled'];

    public function __construct()
    {
        $this->apiUrl = config('services.pioneer.api_url') . '/invoices';
    }

    public function index(Request $request)
    {
        try {
            $activeTab = $this->normalizeTab($request->input('tab', 'pending'));
            $params = $this->getApiParams(
                array_merge(
                    $request->only(['number', 'customer', 'machine', 'sort_by', 'sort_dir', 'page']),
                    ['production_status' => $activeTab]
                )
            );

            $response = $this->apiClient()->get($this->apiUrl, $params);

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat data produksi.');
            }

            $resData  = $this->decodeApiJson($response, []);
            $invoices = new LengthAwarePaginator(
                $resData['data'] ?? [],
                $resData['total'] ?? 0,
                $resData['per_page'] ?? 10,
                $resData['current_page'] ?? 1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $statusCounts = [];
            foreach (self::STATUS_TABS as $statusTab) {
                $countResponse = $this->apiClient()->get($this->apiUrl, $this->getApiParams([
                    'production_status' => $statusTab,
                    'per_page' => 1,
                ]));
                $statusCounts[$statusTab] = (int) $this->decodeApiValue($countResponse, 'total', 0);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }

        return view('production.index', compact('invoices', 'activeTab', 'statusCounts'));
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $payload = [
                'production_status' => $request->production_status,
            ];

            if ($request->filled('notes')) {
                $payload['notes'] = $request->input('notes');
            }

            $response = $this->apiClient()->patch("{$this->apiUrl}/{$id}/production-status", $payload);

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal memperbarui status produksi.');

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => $error,
                    ], $response->status() ?: 422);
                }

                return back()->withErrors([$error]);
            }

            $payload = $this->decodeApiValue($response, 'data', []);
            $targetTab = $this->normalizeTab($request->input('production_status'));

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Status produksi berhasil diperbarui.',
                    'data' => [
                        'id' => $id,
                        'production_status' => $payload['production_status'] ?? $targetTab,
                        'production_status_label' => $payload['production_status_label'] ?? ucfirst($targetTab),
                        'status' => $payload['status'] ?? null,
                        'notes' => $payload['notes'] ?? null,
                    ],
                    'meta' => [
                        'from_tab' => $this->normalizeTab($request->input('tab', 'pending')),
                        'to_tab' => $targetTab,
                    ],
                ]);
            }

            return redirect()
                ->route('sales-list.index', ['tab' => $this->normalizeTab($request->input('tab', 'pending'))])
                ->with('success', 'Status produksi berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function detail($id)
    {
        try {
            $response = $this->apiClient()->get("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Invoice tidak ditemukan atau gagal dimuat.',
                ], $response->status() ?: 404);
            }

            return response()->json($this->decodeApiJson($response, []));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadForm($id)
    {
        try {
            $response = $this->apiClient()->get("{$this->apiUrl}/{$id}");

            if ($response->failed()) {
                return redirect()
                    ->route('sales-list.index', ['tab' => 'pending'])
                    ->with('error', 'Invoice tidak ditemukan atau gagal dimuat.');
            }

            $invoice = $this->decodeApiJson($response, []);
            $status = $invoice['production_status'] ?? null;

            if (!in_array($status, ['pending', 'in-process'], true)) {
                return redirect()
                    ->route('sales-list.index', ['tab' => $this->normalizeTab((string) $status)])
                    ->with('error', 'Halaman file kerja hanya tersedia untuk transaksi Pending atau In-progress.');
            }

            $uploadableItems = collect($invoice['items'] ?? [])
                ->filter(fn ($item) => ($item['product_type'] ?? null) === 'cutting')
                ->values()
                ->all();

            return view('production.upload', compact('invoice', 'uploadableItems'));
        } catch (\Exception $e) {
            return redirect()
                ->route('sales-list.index', ['tab' => 'pending'])
                ->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }

    public function uploadFiles(Request $request, $id)
    {
        $request->validate([
            'invoice_item_id' => ['required', 'integer'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
        ]);

        try {
            $token = session('api_token');

            $client = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ]);

            foreach ($request->file('files', []) as $index => $file) {
                $client = $client->attach(
                    "files[$index]",
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                );
            }

            $response = $client->post("{$this->apiUrl}/{$id}/item-files", [
                'invoice_item_id' => $request->input('invoice_item_id'),
            ]);

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal upload file.');
                return back()->withErrors([$error])->withInput();
            }

            return redirect()
                ->route('sales-list.upload', $id)
                ->with('success', 'File berhasil diunggah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function downloadFile($id, $fileId)
    {
        try {
            $response = Http::withToken(session('api_token'))->get("{$this->apiUrl}/{$id}/item-files/{$fileId}/download");

            if ($response->failed()) {
                return back()->with('error', 'File gagal diunduh.');
            }

            $disposition = $response->header('Content-Disposition', '');
            $filename = 'lampiran-' . $fileId;

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

    private function normalizeTab(?string $tab): string
    {
        $tab = strtolower(trim((string) $tab));

        return in_array($tab, self::STATUS_TABS, true) ? $tab : 'pending';
    }
}
