<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class SalesDriveLeadCreated
{
    use SerializesModels;

    public array $leadData;

    public function __construct(array $leadData)
    {
        $this->leadData = $leadData;
    }
}
