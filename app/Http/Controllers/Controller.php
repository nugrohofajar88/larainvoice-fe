<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Helpers\AuthHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Controller
{
    /**
     * Helper untuk memanggil HTTP Client dengan API token
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function apiClient()
    {
        $token = session('api_token');
        if (!$token) {
            \Log::warning('apiClient called but api_token is missing from session.');
        }
        return Http::withToken($token)->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Backend kadang mengembalikan prefix komentar HTML sebelum JSON.
     * Bersihkan dulu agar decoding tidak menghasilkan null.
     */
    protected function decodeApiJson(\Illuminate\Http\Client\Response $response, $default = null)
    {
        $body = (string) $response->body();
        $body = preg_replace('/^\s*<!--.*?-->\s*/s', '', $body);

        if ($body === '' || $body === null) {
            return $default;
        }

        $decoded = json_decode($body, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    protected function decodeApiValue(\Illuminate\Http\Client\Response $response, ?string $path = null, $default = null)
    {
        $decoded = $this->decodeApiJson($response, null);

        if (!is_array($decoded)) {
            return $default;
        }

        return $path === null ? $decoded : data_get($decoded, $path, $default);
    }

    /**
     * Memfilter param sebelum dikirim ke API backend
     * Aturan ketat: Jika user bukan SuperAdmin, param wajib dilampirkan branch_id miliknya sendiri.
     *
     * @param array $params
     * @return array
     */
    protected function getApiParams(array $params = []): array
    {
        if (!AuthHelper::isSuperAdmin()) {
            $params['branch_id'] = session('branch_id');
        }
        return $params;
    }

    protected function fetchApiCollection(string $url, array $params = [], int $perPage = 100, int $maxPages = 20): array
    {
        $page = 1;
        $items = [];

        do {
            $response = $this->apiClient()->get($url, array_merge($params, [
                'page' => $page,
                'per_page' => $perPage,
            ]));

            if ($response->failed()) {
                break;
            }

            $payload = $this->decodeApiJson($response, []);
            $data = $payload['data'] ?? [];

            if (!is_array($data) || empty($data)) {
                break;
            }

            $items = array_merge($items, $data);

            $lastPage = (int) ($payload['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= $maxPages);

        return $items;
    }

    protected function paginateArray(array $items, int $perPage = 10): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage() ?: 1;
        $collection = collect($items);

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    protected function streamCsvDownload(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
