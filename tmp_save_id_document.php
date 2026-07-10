<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = new App\Models\User();
$user->name = 'Tmp Test';
$user->student_id = '99-99999';
$user->email = 'tmp_test_' . time() . '@student.local';
$user->email_verified_at = now();
$user->password = bcrypt('password');
$user->role = 1;
$user->account_status = 'pending';
$user->id_document = 'ids/test-id.png';
$user->save();

echo 'Saved id_document: ' . $user->id_document . PHP_EOL;
echo 'Retrieved id_document: ' . App\Models\User::find($user->id)->id_document . PHP_EOL;
