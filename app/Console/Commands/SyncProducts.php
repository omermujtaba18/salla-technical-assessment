<?php

namespace App\Console\Commands;

use App\Factories\ServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-products {service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products with external service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceName = $this->argument('service');

        try {
            $service = ServiceFactory::create($serviceName);
            $service->syncProducts();

            Log::info("Products synced successfully from {$serviceName}");
        } catch (\Exception $e) {
            // We can setup alerts/monitoring on these logs to get notified if something is wrong
            Log::error("Products failed to sync from {$serviceName}", ['service' => $serviceName, 'message' => $e->getMessage(), 'stack' => $e->getTrace()]);
        }
    }
}
