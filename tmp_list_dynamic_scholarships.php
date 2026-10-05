<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\Scholarship::whereIn('status', ['active', 'inactive'])
    ->whereNotNull('short_information')
    ->where('type', 'non-academic')
    ->orderBy('name')
    ->get(['id', 'name']);

echo 'dynamic_non_academic_count=' . $items->count() . PHP_EOL;
foreach ($items as $item) {
    echo $item->id . "\t" . $item->name . PHP_EOL;
}
