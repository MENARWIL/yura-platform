<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$users = [
    ['admin@demo.com', 'Admin Demo', 'admin'],
    ['profesor@demo.com', 'Profesor Demo', 'profesor'],
    ['gestion@demo.com', 'Gestion Demo', 'academic'],
    ['padre@demo.com', 'Padre Demo', 'padre'],
    ['tutor@demo.com', 'Tutor Demo', 'tutor'],
];

foreach ($users as [$email, $name, $role]) {
    User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make('Demo123!'),
            'role' => $role,
            'rol' => $role,
            'estado' => 'activo',
            'status' => 'activo',
        ]
    );
    echo $email . " -> ok\n";
}

foreach ($users as [$email, $name, $role]) {
    $ok = Auth::attempt(['email' => $email, 'password' => 'Demo123!']);
    echo $email . ' => ' . ($ok ? 'TRUE' : 'FALSE') . PHP_EOL;
}
