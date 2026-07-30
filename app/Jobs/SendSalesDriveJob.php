<?php

namespace App\Jobs;

use App\Services\SalesDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSalesDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $name;
    public string $phone;

    /**
     * Create a new job instance.
     */
    public function __construct(string $name, string $phone)
    {
        $this->name = $name;
        $this->phone = $phone;
    }

    /**
     * Execute the job.
     */
    public function handle(SalesDriveService $service)
    {
        // send minimal payload
        $payload = [
            'fName' => $this->name,
            'phone' => $this->phone,
        ];

        $service->send($payload);
    }
}
