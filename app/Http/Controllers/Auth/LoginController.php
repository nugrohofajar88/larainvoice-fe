<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MockDataService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (session('authenticated')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        try {
            $apiUrl = config('services.pioneer.api_url');
            $response = \Illuminate\Support\Facades\Http::acceptJson()->post($apiUrl . '/login', [
                'username' => $request->username,
                'password' => $request->password,
            ]);

            if ($response->failed()) {
                $errorMessage = $this->decodeApiValue($response, 'message', 'Username atau password salah.');
                return back()->withErrors([$errorMessage])->withInput();
            }

            $data = $this->decodeApiJson($response);

            if (!is_array($data) || !isset($data['token'], $data['user'])) {
                return back()->withErrors(['Backend mengembalikan response login yang tidak valid.'])->withInput();
            }

            session([
                'authenticated' => true,
                'api_token' => $data['token'],
                'user' => $data['user'],
                'user_name' => $data['user']['name'],
                'username' => $data['user']['username'],
                'role' => $data['user']['role']['name'] ?? 'user',
                'role_label' => ucwords($data['user']['role']['name'] ?? 'User'),
                'branch_id' => $data['user']['branch_id'],
                'menus' => $data['menus'],
                'permissions' => $data['permissions'],
            ]);

            return redirect()->route('dashboard')->with('success', 'Selamat datang, ' . $data['user']['name'] . '!');

        } catch (\Exception $e) {
            return back()->withErrors(['Gagal terhubung ke server backend: ' . $e->getMessage()])->withInput();
        }
    }

    public function logout(Request $request)
    {
        try {
            $apiUrl = config('services.pioneer.api_url');
            $token = session('api_token');

            if ($token) {
                \Illuminate\Support\Facades\Http::withToken($token)
                    ->timeout(3)
                    ->post($apiUrl . '/logout');
            }
        } catch (\Exception $e) {
            \Log::error('Logout API failed: ' . $e->getMessage());
        }

        $request->session()->flush();
        return redirect()->route('login')->with('success', 'Anda berhasil keluar.');
    }
}
