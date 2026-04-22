<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    private string $paymentApiUrl;
    private string $invoiceApiUrl;

    public function __construct()
    {
        $baseApiUrl = config('services.pioneer.api_url');
        $this->paymentApiUrl = $baseApiUrl . '/payments';
        $this->invoiceApiUrl = $baseApiUrl . '/invoices';
    }

    public function index(Request $request)
    {
        try {
            $params = $this->getApiParams(
                array_merge(
                    $request->only(['number', 'customer', 'machine', 'sort_by', 'sort_dir', 'page']),
                    ['per_page' => 10]
                )
            );

            $response = $this->apiClient()->get($this->invoiceApiUrl, $params);

            if ($response->failed()) {
                return back()->with('error', 'Gagal memuat daftar invoice untuk pembayaran.');
            }

            $resData = $this->decodeApiJson($response, []);
            $invoices = new LengthAwarePaginator(
                $resData['data'] ?? [],
                $resData['total'] ?? 0,
                $resData['per_page'] ?? 10,
                $resData['current_page'] ?? 1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }

        return view('payments.index', compact('invoices'));
    }

    public function create($invoiceId)
    {
        try {
            $invoiceResponse = $this->apiClient()
                ->get("{$this->invoiceApiUrl}/{$invoiceId}");

            if ($invoiceResponse->failed()) {
                return redirect()->route('pembayaran.index')->with('error', 'Invoice tidak ditemukan.');
            }

            $invoice  = $this->decodeApiJson($invoiceResponse, []);
        } catch (\Exception $e) {
            return redirect()->route('pembayaran.index')->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }

        return view('payments.form', compact('invoice'));
    }

    public function store(Request $request, $invoiceId)
    {
        try {
            $payload  = $this->getApiParams(array_merge(
                $request->except(['_token', '_method', 'proof_files']),
                ['invoice_id' => $invoiceId]
            ));

            $client = Http::withToken(session('api_token'))->withHeaders([
                'Accept' => 'application/json',
            ]);

            foreach ($request->file('proof_files', []) as $index => $file) {
                $client = $client->attach(
                    "proof_files[$index]",
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                );
            }

            $response = $client->post($this->paymentApiUrl, $payload);

            if ($response->failed()) {
                $error = $this->decodeApiValue($response, 'message', 'Gagal mencatat pembayaran.');
                return back()->withErrors([$error])->withInput();
            }

            return redirect()->route('pembayaran.create', $invoiceId)->with('success', 'Pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function downloadFile($paymentId, $fileId)
    {
        try {
            $response = Http::withToken(session('api_token'))
                ->get("{$this->paymentApiUrl}/{$paymentId}/files/{$fileId}/download");

            if ($response->failed()) {
                return back()->with('error', 'File bukti bayar gagal diunduh.');
            }

            $disposition = $response->header('Content-Disposition', '');
            $filename = 'bukti-bayar-' . $fileId;

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
}
