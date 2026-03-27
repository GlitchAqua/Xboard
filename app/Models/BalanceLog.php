<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceLog extends Model
{
    protected $table = 'v2_balance_log';
    protected $dateFormat = 'U';
    public $timestamps = false;
    protected $guarded = ['id'];

    const TYPE_RECHARGE = 'recharge';
    const TYPE_TRAFFIC_DEDUCTION = 'traffic_deduction';
    const TYPE_PACKAGE_PURCHASE = 'package_purchase';
    const TYPE_REFUND = 'refund';
    const TYPE_ADMIN_ADJUST = 'admin_adjust';
    const TYPE_COMMISSION_TRANSFER = 'commission_transfer';

    public static $typeMap = [
        self::TYPE_RECHARGE => '充值',
        self::TYPE_TRAFFIC_DEDUCTION => '流量扣费',
        self::TYPE_PACKAGE_PURCHASE => '流量包购买',
        self::TYPE_REFUND => '退款',
        self::TYPE_ADMIN_ADJUST => '管理员调整',
        self::TYPE_COMMISSION_TRANSFER => '佣金转入',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
