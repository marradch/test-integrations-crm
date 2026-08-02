<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Prices\UpdateImportFromPriceService;

class UpdateImportFromPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-import-from-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Оновлює файл імпорту на основі прайс-листа.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new UpdateImportFromPriceService();
        //try {
            $outputFilePath = $service->update();
            $this->info("Файл оновлено та збережено: {$outputFilePath}");
        //} catch (\Exception $e) {
        //    $this->error("Ошибка при обновлении файла: " . $e->getMessage());
        //}
    }
}
