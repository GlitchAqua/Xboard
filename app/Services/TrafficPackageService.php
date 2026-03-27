<?php

namespace App\Services;

use App\Models\BalanceLog;
use App\Models\TrafficPackage;
use App\Models\User;
use App\Models\UserTrafficPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrafficPackageService
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * 购买流量包 (从余额扣款)
     */
    public function purchase(User $user, int $packageId, bool $autoRenew = false, int $priority = 0): UserTrafficPackage
    {
        $package = TrafficPackage::where('sell', true)->findOrFail($packageId);

        if ($user->balance < $package->price) {
            abort(500, '余额不足，请先充值');
        }

        return DB::transaction(function () use ($user, $package, $autoRenew, $priority) {
            $deducted = $this->balanceService->debit(
                $user->id,
                $package->price,
                BalanceLog::TYPE_PACKAGE_PURCHASE,
                "购买流量包: {$package->name}",
                null,
                ['package_id' => $package->id, 'traffic_bytes' => $package->traffic_bytes]
            );

            if (!$deducted) {
                abort(500, '扣费失败');
            }

            // 永久包: expired_at = null; 限时包: expired_at = now + days
            $expiredAt = $package->isPermanent()
                ? null
                : time() + ($package->validity_days * 86400);

            return UserTrafficPackage::create([
                'user_id' => $user->id,
                'traffic_package_id' => $package->id,
                'traffic_bytes' => $package->traffic_bytes,
                'used_bytes' => 0,
                'expired_at' => $expiredAt,
                'status' => UserTrafficPackage::STATUS_ACTIVE,
                'auto_renew' => $autoRenew,
                'consumption_priority' => $priority,
            ]);
        });
    }

    /**
     * 从用户的活跃流量包中消耗流量
     * 按用户优先级排序，相同优先级按到期时间排序(永久包排最后)
     *
     * @return int 未被流量包覆盖的剩余字节数
     */
    public function consumeFromPackages(int $userId, int $bytes): int
    {
        if ($bytes <= 0) {
            return 0;
        }

        $remaining = $bytes;

        $packages = UserTrafficPackage::forUser($userId)
            ->active()
            ->orderBy('consumption_priority', 'asc')
            ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expired_at', 'asc')
            ->get();

        foreach ($packages as $package) {
            if ($remaining <= 0) {
                break;
            }

            $available = $package->getRemainingBytes();
            if ($available <= 0) {
                continue;
            }

            $consume = min($remaining, $available);
            $package->used_bytes += $consume;

            if ($package->getRemainingBytes() <= 0) {
                $package->status = UserTrafficPackage::STATUS_EXHAUSTED;
            }

            $package->save();
            $remaining -= $consume;
        }

        return max(0, $remaining);
    }

    /**
     * 过期限时包处理 (不影响永久包)
     */
    public function expirePackages(): int
    {
        return UserTrafficPackage::where('status', UserTrafficPackage::STATUS_ACTIVE)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', time())
            ->update(['status' => UserTrafficPackage::STATUS_EXPIRED]);
    }

    /**
     * 自动续费处理
     */
    public function processAutoRenewals(): array
    {
        $renewed = 0;
        $failed = 0;
        $expired = 0;

        // 1. 刚耗尽且开启了自动续费的包 → 尝试续费
        $exhaustedPackages = UserTrafficPackage::where('status', UserTrafficPackage::STATUS_EXHAUSTED)
            ->where('auto_renew', true)
            ->get();

        foreach ($exhaustedPackages as $pkg) {
            if ($this->renewPackage($pkg)) {
                $renewed++;
            } else {
                $failed++;
            }
        }

        // 2. 等待续费中的包 → 检查是否超过15天 或 重试续费
        $pendingPackages = UserTrafficPackage::where('status', UserTrafficPackage::STATUS_RENEW_PENDING)
            ->where('auto_renew', true)
            ->get();

        foreach ($pendingPackages as $pkg) {
            if ($pkg->isRenewExpired()) {
                // 超过15天, 作废
                $pkg->status = UserTrafficPackage::STATUS_EXPIRED;
                $pkg->auto_renew = false;
                $pkg->save();
                $expired++;
            } else {
                // 重试续费 (余额可能已充足)
                if ($this->renewPackage($pkg)) {
                    $renewed++;
                }
            }
        }

        return compact('renewed', 'failed', 'expired');
    }

    /**
     * 续费单个流量包
     */
    public function renewPackage(UserTrafficPackage $pkg): bool
    {
        $template = TrafficPackage::find($pkg->traffic_package_id);
        if (!$template) {
            return false;
        }

        $user = User::find($pkg->user_id);
        if (!$user || $user->balance < $template->price) {
            // 余额不足, 进入等待续费状态
            if ($pkg->status !== UserTrafficPackage::STATUS_RENEW_PENDING) {
                $pkg->status = UserTrafficPackage::STATUS_RENEW_PENDING;
                $pkg->renew_failed_at = $pkg->renew_failed_at ?: time();
                $pkg->save();
            }
            return false;
        }

        return DB::transaction(function () use ($pkg, $template, $user) {
            $deducted = $this->balanceService->debit(
                $user->id,
                $template->price,
                BalanceLog::TYPE_PACKAGE_PURCHASE,
                "自动续费流量包: {$template->name} (第{$pkg->renewal_count}次续费)",
                null,
                ['package_id' => $template->id, 'renewal' => true]
            );

            if (!$deducted) {
                return false;
            }

            $pkg->used_bytes = 0;
            $pkg->traffic_bytes = $template->traffic_bytes;
            $pkg->status = UserTrafficPackage::STATUS_ACTIVE;
            $pkg->renewal_count += 1;
            $pkg->renew_failed_at = null;

            // 限时包重新计算到期时间
            if ($template->isTimeLimited()) {
                $pkg->expired_at = time() + ($template->validity_days * 86400);
            }

            $pkg->save();
            return true;
        });
    }

    /**
     * 切换自动续费
     */
    public function toggleAutoRenew(int $userId, int $packageId, bool $enabled): bool
    {
        $pkg = UserTrafficPackage::where('user_id', $userId)
            ->where('id', $packageId)
            ->whereIn('status', [
                UserTrafficPackage::STATUS_ACTIVE,
                UserTrafficPackage::STATUS_EXHAUSTED,
                UserTrafficPackage::STATUS_RENEW_PENDING,
            ])
            ->firstOrFail();

        $pkg->auto_renew = $enabled;
        return $pkg->save();
    }

    /**
     * 批量更新用户流量包优先级
     */
    public function updatePriority(int $userId, array $priorities): bool
    {
        foreach ($priorities as $item) {
            UserTrafficPackage::where('user_id', $userId)
                ->where('id', $item['id'])
                ->update(['consumption_priority' => $item['priority']]);
        }
        return true;
    }

    /**
     * 获取用户所有流量包 (活跃 + 等待续费)
     */
    public function getActivePackages(int $userId)
    {
        return UserTrafficPackage::forUser($userId)
            ->whereIn('status', [
                UserTrafficPackage::STATUS_ACTIVE,
                UserTrafficPackage::STATUS_RENEW_PENDING,
            ])
            ->where(function ($q) {
                $q->where('expired_at', '>', time())
                  ->orWhereNull('expired_at')
                  ->orWhere('status', UserTrafficPackage::STATUS_RENEW_PENDING);
            })
            ->with('trafficPackage')
            ->orderBy('consumption_priority', 'asc')
            ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expired_at', 'asc')
            ->get();
    }

    /**
     * 获取可购买的流量包列表
     */
    public function getAvailablePackages()
    {
        return TrafficPackage::where('show', true)
            ->where('sell', true)
            ->orderBy('sort', 'asc')
            ->get();
    }
}
