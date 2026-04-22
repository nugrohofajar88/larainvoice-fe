<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$url = config('services.pioneer.api_url') . '/roles';
echo "Testing URL: $url\n";

try {
    // Tambahkan Accept: application/json supaya tidak redirect ke login
    $response = Http::withHeaders(['Accept' => 'application/json'])->get($url);
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
