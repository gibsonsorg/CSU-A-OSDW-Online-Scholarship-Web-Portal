<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = new App\Models\User([
    'name' => 'Test User',
    'email' => 'jseb0967@gmail.com',
]);

Illuminate\Support\Facades\Mail::to($user->email)->send(new App\Mail\ApprovalMail($user));
echo "MAIL_SENT\n";
