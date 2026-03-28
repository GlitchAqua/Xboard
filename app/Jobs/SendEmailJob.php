<?php

namespace App\Jobs;

use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $params;

    public $tries = 0;
    public $maxExceptions = 3;
    public $timeout = 10;

    public function retryUntil()
    {
        return now()->addHours(24);
    }

    public function middleware(): array
    {
        if ($this->queue === 'send_email_mass') {
            return [(new RateLimitedWithRedis('mass-email'))->releaseAfter(5)];
        }
        return [];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params, $queue = 'send_email')
    {
        $this->onQueue($queue);
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailLog = MailService::sendEmail($this->params);
        if ($mailLog['error']) {
            $this->release();
        }
    }
}
