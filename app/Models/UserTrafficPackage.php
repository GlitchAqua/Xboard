<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTrafficPackage extends Model
{
    protected $table = 'v2_user_traffic_package';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];

    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 0;
    const STATUS_EXHAUSTED = 2;
    const STATUS_RENEW_PENDING = 3; // 等待续费(余额不足, 15天内有效)

    const RENEW_GRACE_DAYS = 15;

    protected $casts = [
        'traffic_bytes' => 'integer',
        'used_bytes' => 'integer',
        'expired_at' => 'timestamp',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'auto_renew' => 'boolean',
        'consumption_priority' => 'integer',
        'renewal_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function trafficPackage(): BelongsTo
    {
        return $this->belongsTo(TrafficPackage::class, 'traffic_package_id', 'id');
    }

    public function getRemainingBytes(): int
    {
        return max(0, $this->traffic_bytes - $this->used_bytes);
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->getRemainingBytes() <= 0) {
            return false;
        }
        // 永久包不检查过期时间
        if ($this->expired_at === null) {
            return true;
        }
        return $this->expired_at > time();
    }

    /**
     * 续费宽限期是否已过 (15天)
     */
    public function isRenewExpired(): bool
    {
        if (!$this->renew_failed_at) {
            return false;
        }
        return time() > ($this->renew_failed_at + self::RENEW_GRACE_DAYS * 86400);
    }

    /**
     * 续费宽限期剩余天数
     */
    public function getRenewGraceDaysLeft(): int
    {
        if (!$this->renew_failed_at) {
            return self::RENEW_GRACE_DAYS;
        }
        $left = ($this->renew_failed_at + self::RENEW_GRACE_DAYS * 86400 - time()) / 86400;
        return max(0, (int) ceil($left));
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->where('expired_at', '>', time())
                  ->orWhereNull('expired_at'); // 永久包
            });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
