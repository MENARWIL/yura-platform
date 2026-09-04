<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Auth::shouldUse('web');

$roles = ['admin', 'profesor', 'tutor'];

foreach ($roles as $role) {
    $user = \App\Models\User::where('role', $role)->first();

    echo "--- ROLE: {$role} ---\n";
    if (! $user) {
        echo "no user\n\n";
        continue;
    }

    Illuminate\Support\Facades\Auth::setUser($user);

    $controller = new \App\Http\Controllers\DashboardController();
    $view = $controller->index();

    $data = $view->getData();
    echo json_encode([
        'role' => $role,
        'data_keys' => array_keys($data['data'] ?? $data),
        'data' => $data['data'] ?? $data,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

$student = \App\Models\Student::query()->first();
if ($student) {
    echo "--- EXPORT CHECK ---\n";
    $exportDir = __DIR__ . '/../storage/app/public/tmp_exports';
    if (! is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }

    $pdfPath = $exportDir . '/students_export_test.pdf';
    $excelPath = $exportDir . '/students_export_test.xlsx';

    try {
        $controller = new \App\Http\Controllers\StudentController();
        $pdfResponse = $controller->exportPdf();
        $pdfContent = $pdfResponse->getContent();
        file_put_contents($pdfPath, $pdfContent);
        echo "PDF generated: " . (file_exists($pdfPath) ? 'yes' : 'no') . "\n";
    } catch (Throwable $e) {
        echo "PDF error: " . $e->getMessage() . "\n";
    }

    try {
        $excelResponse = $controller->exportExcel();
        $excelContent = $excelResponse->getContent();
        file_put_contents($excelPath, $excelContent);
        echo "Excel generated: " . (file_exists($excelPath) ? 'yes' : 'no') . "\n";
    } catch (Throwable $e) {
        echo "Excel error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No students found for export smoke test.\n";
}
