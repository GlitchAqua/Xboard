<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\User;
use App\Services\NodeSyncService;
use App\Services\UsageBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CheckTrafficExceeded extends Command
{
    protected $signature = 'check:traffic-exceeded';
    protected $description = '检查流量超标用户并通知节点';

    public function handle()
    {
        // 按量计费模式: 检查余额不足的用户
        if (UsageBillingService::isEnabled()) {
            $this->handleUsageMode();
            return;
        }

        // 订阅模式: 检查流量超标
        $this->handleSubscriptionMode();
    }

    private function handleUsageMode(): void
    {
        $billingService = app(UsageBillingService::class);
        $pendingBlockUserIds = $billingService->getPendingBlockUsers();

        if (empty($pendingBlockUserIds)) {
            return;
        }

        $exceededUsers = User::toBase()
            ->whereIn('id', $pendingBlockUserIds)
            ->where('banned', 0)
            ->select(['id', 'group_id'])
            ->get();

        $this->notifyNodes($exceededUsers);
        $this->info("Usage mode: blocked " . $exceededUsers->count() . " users with insufficient balance.");
    }

    private function handleSubscriptionMode(): void
    {
        $count = Redis::scard('traffic:pending_check');
        if ($count <= 0) {
            return;
        }

        $pendingUserIds = array_map('intval', Redis::spop('traffic:pending_check', $count));

        $exceededUsers = User::toBase()
            ->whereIn('id', $pendingUserIds)
            ->whereRaw('u + d >= transfer_enable')
            ->where('transfer_enable', '>', 0)
            ->where('banned', 0)
            ->select(['id', 'group_id'])
            ->get();

        $this->notifyNodes($exceededUsers);
        $this->info("Checked " . count($pendingUserIds) . " users, " . $exceededUsers->count() . " exceeded.");
    }

    private function notifyNodes($exceededUsers): void
    {
        if ($exceededUsers->isEmpty()) {
            return;
        }

        $groupedUsers = $exceededUsers->groupBy('group_id');

        foreach ($groupedUsers as $groupId => $users) {
            if (!$groupId) {
                continue;
            }

            $userIdsInGroup = $users->pluck('id')->toArray();
            $servers = Server::whereJsonContains('group_ids', (string) $groupId)->get();

            foreach ($servers as $server) {
                if (!NodeSyncService::isNodeOnline($server->id)) {
                    continue;
                }

                NodeSyncService::push($server->id, 'sync.user.delta', [
                    'action' => 'remove',
                    'users' => array_map(fn($id) => ['id' => $id], $userIdsInGroup),
                ]);
            }
        }
    }
}
