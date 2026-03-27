<?php

namespace App\Services;

use App\Models\BalanceLog;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class UsageBillingService
{
    protected BalanceService $balanceService;
    protected TrafficPackageService $packageService;

    const PENDING_BLOCK_KEY = 'traffic:pending_block';

    public function __construct(BalanceService $balanceService, TrafficPackageService $packageService)
    {
        $this->balanceService = $balanceService;
        $this->packageService = $packageService;
    }

    /**
     * 是否启用按量计费模式
     */
    public static function isEnabled(): bool
    {
        return admin_setting('billing_mode', 'subscription') === 'usage';
    }

    /**
     * 获取每 GB 单价 (分)
     */
    public static function getPricePerGB(): int
    {
        return (int) admin_setting('usage_price_per_gb', 100);
    }

    /**
     * 获取最低余额阈值 (分)
     */
    public static function getMinBalance(): int
    {
        return (int) admin_setting('usage_min_balance', 0);
    }

    /**
     * 计算流量费用 (分)
     *
     * @param int $bytes 流量字节数
     * @param float $serverRate 服务器倍率
     * @return int 费用 (分)
     */
    public function calculateCost(int $bytes, float $serverRate = 1.0): int
    {
        $pricePerGB = self::getPricePerGB();
        $billedBytes = (int) ($bytes * $serverRate);
        // 按 GB 计算，不足 1GB 按比例
        $cost = (int) ceil($billedBytes / (1024 * 1024 * 1024) * $pricePerGB);
        return max(0, $cost);
    }

    /**
     * 对单个用户进行流量计费
     *
     * @param int $userId 用户ID
     * @param int $uploadBytes 上传字节
     * @param int $downloadBytes 下载字节
     * @param float $serverRate 服务器倍率
     */
    public function billTraffic(int $userId, int $uploadBytes, int $downloadBytes, float $serverRate = 1.0): void
    {
        $totalBytes = $uploadBytes + $downloadBytes;
        if ($totalBytes <= 0) {
            return;
        }

        // 1. 先从流量包消耗
        $billedBytes = (int) ($totalBytes * $serverRate);
        $remainingBytes = $this->packageService->consumeFromPackages($userId, $billedBytes);

        // 2. 剩余部分按 GB 单价从余额扣除
        if ($remainingBytes > 0) {
            $cost = $this->calculateCostFromBytes($remainingBytes);
            if ($cost > 0) {
                $gb = round($remainingBytes / (1024 * 1024 * 1024), 4);
                $newBalance = $this->balanceService->forceDebit(
                    $userId,
                    $cost,
                    BalanceLog::TYPE_TRAFFIC_DEDUCTION,
                    "流量扣费: {$gb}GB",
                    ['bytes' => $remainingBytes, 'rate' => $serverRate]
                );

                // 3. 余额低于阈值，标记待封禁
                if ($newBalance <= self::getMinBalance()) {
                    Redis::sadd(self::PENDING_BLOCK_KEY, $userId);
                }
            }
        }
    }

    /**
     * 批量计费 (从 TrafficFetchJob 调用)
     *
     * @param array $data [uid => [upload, download], ...]
     * @param float $serverRate 服务器倍率
     */
    public function batchBillTraffic(array $data, float $serverRate = 1.0): void
    {
        foreach ($data as $uid => $traffic) {
            if (!is_array($traffic) || count($traffic) < 2) {
                continue;
            }
            $this->billTraffic((int) $uid, (int) $traffic[0], (int) $traffic[1], $serverRate);
        }
    }

    /**
     * 从字节数直接计算费用 (已经乘过倍率)
     */
    private function calculateCostFromBytes(int $bytes): int
    {
        $pricePerGB = self::getPricePerGB();
        return (int) ceil($bytes / (1024 * 1024 * 1024) * $pricePerGB);
    }

    /**
     * 获取待封禁用户列表并清空
     */
    public function getPendingBlockUsers(): array
    {
        $userIds = Redis::smembers(self::PENDING_BLOCK_KEY);
        if (!empty($userIds)) {
            Redis::del(self::PENDING_BLOCK_KEY);
        }
        return array_map('intval', $userIds ?: []);
    }

    /**
     * 获取用户计费状态
     */
    public function getUserBillingStatus(User $user): array
    {
        $pricePerGB = self::getPricePerGB();
        $balance = $user->balance;

        // 预估可用流量 (GB)
        $estimatedTrafficGB = $pricePerGB > 0 ? round($balance / $pricePerGB, 2) : 0;

        // 活跃流量包
        $activePackages = $this->packageService->getActivePackages($user->id);
        $packageRemainingBytes = $activePackages->sum(fn($p) => $p->getRemainingBytes());

        return [
            'billing_mode' => 'usage',
            'balance' => $balance,
            'price_per_gb' => $pricePerGB,
            'estimated_traffic_gb' => $estimatedTrafficGB,
            'active_packages_count' => $activePackages->count(),
            'package_remaining_bytes' => $packageRemainingBytes,
            'package_remaining_gb' => round($packageRemainingBytes / (1024 * 1024 * 1024), 2),
            'min_balance' => self::getMinBalance(),
        ];
    }
}
