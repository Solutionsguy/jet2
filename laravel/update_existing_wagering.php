<?php
use App\Models\Wallet;
use App\Models\Setting;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the current multiplier from settings
$multiplierSetting = Setting::where('category', 'freebet_wagering_multiplier')->first();
$multiplier = $multiplierSetting ? floatval($multiplierSetting->value) : 10;

echo "Using wagering multiplier: $multiplier\n";

// Find users who have freebet but NO wagering set yet
$wallets = Wallet::where('freebet_amount', '>', 0)
                 ->where('wagering_remaining', 0)
                 ->get();

$count = 0;
foreach ($wallets as $wallet) {
    $target = $wallet->freebet_amount * $multiplier;
    $wallet->wagering_remaining = $target;
    $wallet->initial_wagering_target = $target;
    $wallet->save();
    $count++;
    echo "Updated User ID: {$wallet->userid} | Freebet: {$wallet->freebet_amount} | Target: $target\n";
}

echo "\nFinished! Total users updated: $count\n";
