<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "DELETION LOGS:\n";
print_r(App\Models\DeletionLog::get()->toArray());
