<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = new App\Services\OtaSearchService();
try {
    $res = $svc->search('DEL', 'BOM', '2026-04-20', 'economy');
    print_r(array_keys($res));
    echo "Count: " . count($res['flights']) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
