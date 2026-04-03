<?php
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = [
    [
        'category' => 'freebet_wagering_multiplier',
        'value' => '10',
    ],
    [
        'category' => 'freebet_min_multiplier',
        'value' => '1.50',
    ]
];

foreach ($settings as $s) {
    $exists = DB::table('settings')->where('category', $s['category'])->first();
    if (!$exists) {
        DB::table('settings')->insert($s);
        echo "Created setting: {$s['category']} with value {$s['value']}\n";
    } else {
        echo "Setting already exists: {$s['category']} (Value: {$exists->value})\n";
    }
}

echo "Done!\n";
