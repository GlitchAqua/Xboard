<?php

namespace App\Console\Commands;

use App\Services\TrafficPackageService;
use Illuminate\Console\Command;

class ExpireTrafficPackages extends Command
{
    protected $signature = 'expire:traffic-packages';
    protected $description = '过期流量包 + 自动续费处理';

    public function handle()
    {
        $service = app(TrafficPackageService::class);

        // 1. 过期限时包
        $expiredCount = $service->expirePackages();
        if ($expiredCount > 0) {
            $this->info("Expired {$expiredCount} time-limited packages.");
        }

        // 2. 自动续费处理
        $result = $service->processAutoRenewals();
        if ($result['renewed'] || $result['failed'] || $result['expired']) {
            $this->info("Auto-renew: {$result['renewed']} renewed, {$result['failed']} pending, {$result['expired']} grace-expired.");
        }
    }
}
