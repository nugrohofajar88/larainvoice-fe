<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthCheck
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('authenticated')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (empty(session('menus')) && session('api_token')) {
            try {
                $response = Http::withToken(session('api_token'))
                    ->acceptJson()
                    ->get(config('services.pioneer.api_url') . '/menus');

                if ($response->successful() && is_array($response->json())) {
                    session(['menus' => $response->json()]);
                }
            } catch (\Throwable $e) {
                \Log::warning('AuthCheck could not hydrate menus: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
