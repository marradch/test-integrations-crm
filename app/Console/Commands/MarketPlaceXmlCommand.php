<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Prices\MarketPlaceXMLGeneratorService;

class MarketPlaceXmlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:market-place-xml-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new MarketPlaceXMLGeneratorService();
        $service->generateXML();
        echo "XML файл успішно згенеровано та збережено\n";
    }
}
