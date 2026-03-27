<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficPackage extends Model
{
    protected $table = 'v2_traffic_package';
    protected $dateFormat = 'U';

    const TYPE_TIME_LIMITED = 'time-limited';
    const TYPE_PERMANENT = 'permanent';

    protected $fillable = [
        'name',
        'description',
        'traffic_bytes',
        'price',
        'validity_days',
        'group_id',
        'speed_limit',
        'show',
        'sell',
        'sort',
        'type',
    ];

    protected $casts = [
        'show' => 'boolean',
        'sell' => 'boolean',
        'traffic_bytes' => 'integer',
        'price' => 'integer',
        'validity_days' => 'integer',
        'group_id' => 'integer',
        'speed_limit' => 'integer',
        'sort' => 'integer',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function userPackages(): HasMany
    {
        return $this->hasMany(UserTrafficPackage::class, 'traffic_package_id', 'id');
    }

    public function getTrafficGBAttribute(): float
    {
        return round($this->traffic_bytes / (1024 * 1024 * 1024), 2);
    }

    public function getPriceYuanAttribute(): float
    {
        return round($this->price / 100, 2);
    }

    public function isTimeLimited(): bool
    {
        return $this->type === self::TYPE_TIME_LIMITED;
    }

    public function isPermanent(): bool
    {
        return $this->type === self::TYPE_PERMANENT;
    }
}
