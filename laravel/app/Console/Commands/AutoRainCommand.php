<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RainGiveaway;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoRainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rain:auto {--amount=} {--winners=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically drops a rain giveaway based on admin settings and interval';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("Checking for auto-rain...");
        // 1. Check if enabled in settings (unless forced)
        $isEnabled = setting('auto_rain_enabled') ?? '0';
        if ($isEnabled !== '1' && !$this->option('force')) {
            return 0;
        }

        // 2. Interval Check (unless forced)
        if (!$this->option('force')) {
            $interval = setting('auto_rain_interval') ?? 'hourly';
            $lastRainAt = setting('last_auto_rain_at');
            
            $requiredMinutes = $this->getIntervalMinutes($interval);
            
            if ($lastRainAt) {
                $lastTime = Carbon::parse($lastRainAt);
                $now = Carbon::now();
                
                $minutesDiff = $now->diffInMinutes($lastTime);
                
                // If the diff is negative (clock issue) or less than required, skip
                if ($minutesDiff < $requiredMinutes && $now->greaterThan($lastTime)) {
                    // Not enough time passed
                    return 0;
                }
            }
        }

        $this->info('Starting auto-rain process...');

        // 3. Check for active rains
        $activeRain = RainGiveaway::where('status', 'active')->first();
        if ($activeRain) {
            // Check if active rain is OLD (stuck) - more than 30 minutes
            // If it's old, mark it as completed to allow new rains to drop
            $activeTime = Carbon::parse($activeRain->created_at);
            if (now()->diffInMinutes($activeTime) > 30) {
                $activeRain->update(['status' => 'completed', 'completed_at' => now()]);
                Log::info("Auto-Rain: Force-completed stuck rain ID {$activeRain->id}");
                $this->info("Completed stuck rain ID: {$activeRain->id}");
            } else {
                $this->warn('An active rain already exists. Skipping...');
                return 0;
            }
        }

        // 4. Find an admin user to be the creator
        // Use is_superadmin or any admin
        $admin = User::where('isadmin', '1')->orderBy('id', 'asc')->first();
        if (!$admin) {
            $this->error('No admin user found to create rain.');
            return 1;
        }

        // 5. Get parameters from settings or options
        $amountPerUser = floatval($this->option('amount') ?: (setting('auto_rain_amount') ?: 10));
        $numWinners = intval($this->option('winners') ?: (setting('auto_rain_winners') ?: 10));
        $totalAmount = $amountPerUser * $numWinners;

        try {
            // 6. Create the rain giveaway
            $rain = RainGiveaway::create([
                'created_by' => $admin->id,
                'total_amount' => $totalAmount,
                'amount_per_user' => $amountPerUser,
                'num_winners' => $numWinners,
                'status' => 'active',
                'started_at' => now()
            ]);

            // 7. Post to chat
            ChatMessage::create([
                'user_id' => $admin->id,
                'username' => 'SUPPORT',
                'message' => '__RAIN_CARD__' . $rain->id,
                'avatar' => $admin->image ?? null,
                'is_admin' => true,
                'is_approved' => true // System messages must be approved
            ]);

            // 8. Update last rain timestamp
            Setting::updateOrCreate(
                ['category' => 'last_auto_rain_at'],
                ['value' => now()->toDateTimeString(), 'status' => '1']
            );

            Log::info("Auto-Rain triggered: ID {$rain->id}, Total {$totalAmount}");
            $this->info("Successfully created auto-rain ID: {$rain->id}");
            
            return 0;
        } catch (\Exception $e) {
            Log::error("Auto-Rain failed: " . $e->getMessage());
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Map interval strings to minutes
     */
    private function getIntervalMinutes($interval)
    {
        switch ($interval) {
            case 'every_30_mins': return 30;
            case 'hourly': return 60;
            case 'every_2_hours': return 120;
            case 'every_6_hours': return 360;
            case 'every_12_hours': return 720;
            case 'daily': return 1440;
            default: return 60;
        }
    }
}
