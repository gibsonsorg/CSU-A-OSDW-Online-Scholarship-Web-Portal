<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::whereNotNull('id_document')->get();
echo count($users) . "\n";
foreach ($users as $user) {
    echo $user->id . ' | ' . $user->student_id . ' | ' . $user->id_document . "\n";
}
