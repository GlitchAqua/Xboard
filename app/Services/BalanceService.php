<?php

namespace App\Services;

use App\Models\BalanceLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * 入账 (充值、退款、佣金转入等)
     */
    public function credit(int $userId, int $amount, string $type, ?string $description = null, ?string $orderNo = null, ?array $metadata = null): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $amount, $type, $description, $orderNo, $metadata) {
            $user = User::lockForUpdate()->find($userId);
            if (!$user) {
                return false;
            }

            $balanceBefore = $user->balance;
            $user->balance = $user->balance + $amount;
            $user->save();

            BalanceLog::create([
                'user_id' => $userId,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => $type,
                'description' => $description,
                'related_order_no' => $orderNo,
                'metadata' => $metadata,
                'created_at' => time(),
            ]);

            return true;
        });
    }

    /**
     * 扣费 (流量扣费、购买流量包等)
     */
    public function debit(int $userId, int $amount, string $type, ?string $description = null, ?string $orderNo = null, ?array $metadata = null): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $amount, $type, $description, $orderNo, $metadata) {
            $user = User::lockForUpdate()->find($userId);
            if (!$user) {
                return false;
            }

            if ($user->balance < $amount) {
                return false;
            }

            $balanceBefore = $user->balance;
            $user->balance = $user->balance - $amount;
            $user->save();

            BalanceLog::create([
                'user_id' => $userId,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => $type,
                'description' => $description,
                'related_order_no' => $orderNo,
                'metadata' => $metadata,
                'created_at' => time(),
            ]);

            return true;
        });
    }

    /**
     * 强制扣费 (允许余额变为负数，用于流量扣费场景)
     */
    public function forceDebit(int $userId, int $amount, string $type, ?string $description = null, ?array $metadata = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($userId, $amount, $type, $description, $metadata) {
            $user = User::lockForUpdate()->find($userId);
            if (!$user) {
                return 0;
            }

            $balanceBefore = $user->balance;
            $user->balance = $user->balance - $amount;
            $user->save();

            BalanceLog::create([
                'user_id' => $userId,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => $type,
                'description' => $description,
                'metadata' => $metadata,
                'created_at' => time(),
            ]);

            return $user->balance;
        });
    }

    /**
     * 查询余额日志
     */
    public function getLogs(int $userId, int $page = 1, int $limit = 20)
    {
        return BalanceLog::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }
}
