<?php

namespace App\Jobs;

use App\Services\UsageBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UsageBillingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected float $serverRate;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(array $data, float $serverRate)
    {
        $this->onQueue('usage_billing');
        $this->data = $data;
        $this->serverRate = $serverRate;
    }

    public function handle(): void
    {
        app(UsageBillingService::class)->batchBillTraffic($this->data, $this->serverRate);
    }
}
