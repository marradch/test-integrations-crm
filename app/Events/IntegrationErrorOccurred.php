<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationErrorOccurred
{
    use Dispatchable, SerializesModels;

    public string $serviceName;
    public string $errorMessage;
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(string $serviceName, string $errorMessage, array $payload = [])
    {
        $this->serviceName = $serviceName;
        $this->errorMessage = $errorMessage;
        $this->payload = $payload;
    }
}
