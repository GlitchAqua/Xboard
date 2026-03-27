<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UsageBillingService;
use Illuminate\Console\Command;

class CheckLowBalance extends Command
{
    protected $signature = 'check:low-balance';
    protected $description = '检查余额不足的用户并发送提醒';

    public function handle()
    {
        if (!UsageBillingService::isEnabled()) {
            return;
        }

        $threshold = (int) admin_setting('usage_low_balance_threshold', 500);
        $minBalance = UsageBillingService::getMinBalance();

        $users = User::where('balance', '>', $minBalance)
            ->where('balance', '<=', $threshold)
            ->where('banned', 0)
            ->where('remind_traffic', 1)
            ->get();

        $count = 0;
        foreach ($users as $user) {
            // TODO: 接入邮件/Telegram通知
            $count++;
        }

        if ($count > 0) {
            $this->info("Found {$count} users with low balance (threshold: {$threshold}).");
        }
    }
}
