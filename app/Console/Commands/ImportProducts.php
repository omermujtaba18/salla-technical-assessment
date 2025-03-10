<?php

namespace App\Console\Commands;

use App\Jobs\ImportProductsFromCSV;
use App\Models\Products;
use Illuminate\Console\Command;
use Illuminate\Bus\Batch;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportProducts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'import:products {file : The CSV file to import} {--batchSize=200 : How many rows to process in a batch}';

    /**
     * @var string
     */
    protected $description = 'Imports products into database';

    /**
     * @return mixed
     */
    public function handle()
    {
        // Get the file as an input 
        $file = $this->argument('file');

        // Validate the file
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return;
        }

        $storedFilePath = Storage::putFile('imports', new File($file));
        $batchSize = $this->option('batchSize');

        Bus::batch(new ImportProductsFromCSV($storedFilePath, $batchSize))->then(function (Batch $batch) use ($storedFilePath) {
            $productIds = Cache::get("productsIds:{$storedFilePath}");

            // Delete products that are not in the imported csv and set their deletion reason to synchronization 
            if (is_array($productIds) && count($productIds) > 0) {
                Products::whereNotIn('id', $productIds)->update(['deletion_reason' => 'synchronization', 'deleted_at' => now()->toDateTimeString()]);
                Cache::forget("productsIds:{$storedFilePath}");
            }

            $executionTime = $batch->finishedAt->diff($batch->createdAt)->format('%H:%I:%S');
            Log::info("CSV import successful. Execution time: {$executionTime}", ['totalJobs' => $batch->totalJobs]);
        })->catch(function (Batch $batch, Throwable $e) {
            // Log::error('CSV import failed', ['error' => $e, 'batch' => $batch, 'totalJobs' => $batch->totalJobs, 'failedJobs' => $batch->failedJobs]);
        })->name('import:products:batch')->dispatch();
    }
}
