<?php

namespace App\Helpers;

class AuthHelper
{
    /**
     * Mengecek apakah user memiliki role tertinggi (Administrator / Admin Pusat)
     * Dapat menerima parameter berupa object, array, atau jika kosong akan mengecek session.
     *
     * @param mixed $user
     * @return bool
     */
    public static function isSuperAdmin($user = null): bool
    {
        // 1. Jika parameter kosong, ambil role dari session login
        if (is_null($user)) {
            $roleName = session('role');
            return in_array(strtolower((string) $roleName), ['administrator', 'admin pusat']);
        }

        // 2. Jika parameter berbentuk Object (misal dari Database Eloquent)
        if (is_object($user) && isset($user->role)) {
            $roleName = is_object($user->role) ? $user->role->name : $user->role;
            return in_array(strtolower((string) $roleName), ['administrator', 'admin pusat']);
        }

        // 3. Jika parameter berbentuk Array (misal dari respon API Backend)
        if (is_array($user) && isset($user['role'])) {
            $roleName = is_array($user['role']) ? ($user['role']['name'] ?? '') : $user['role'];
            return in_array(strtolower((string) $roleName), ['administrator', 'admin pusat']);
        }

        return false;
    }
}
