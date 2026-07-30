<?php

namespace App\Listeners;

use App\Events\SalesDriveLeadCreated;
use App\Services\DilovodService;
use Illuminate\Support\Facades\Log;

class CreateDilovodClientListener
{
    protected DilovodService $dilovodService;

    /**
     * Create the event listener.
     */
    public function __construct(DilovodService $dilovodService)
    {
        $this->dilovodService = $dilovodService;
    }

    /**
     * Handle the event.
     */
    public function handle(SalesDriveLeadCreated $event): void
    {
        $leadData = $event->leadData;

        $this->dilovodService->createClient([
            'fName' => $leadData['fName'] ?? '',
            'phone' => $leadData['phone'] ?? '',
        ]);
    }
}
