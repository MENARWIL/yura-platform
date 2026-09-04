<?php

foreach (['admin','profesor','tutor'] as $r) {
    $user = \App\Models\User::where('role', $r)->first();
    echo "--- ROLE: $r ---\n";
    if (! $user) {
        echo "no user\n\n";
        continue;
    }

    auth()->setUser($user);

    $controller = new \App\Http\Controllers\DashboardController();
    $view = $controller->index();

    if (method_exists($view, 'getData')) {
        $d = $view->getData();
        $out = $d['data'] ?? $d;
        print_r($out);
    } else {
        print_r($view);
    }

    echo "\n\n";
}
