<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessProductInBatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportProductsFromCSV implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $batchSize;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $batchSize = 200)
    {
        $this->filePath = $filePath;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Validate the file
        if (!Storage::exists($this->filePath)) {
            Log::warning("File: {$this->filePath} does not exist");
            return;
        }

        // Get the file contents and split it into lines
        $contents = Storage::get($this->filePath);
        $lines = explode("\n", $contents);

        $i = 0;
        $productIds = [];

        Log::info("ImportProductsFromCSV::Starting... Processed {$i} lines");

        foreach ($lines as $line) {
            if ($i % 1000 == 0) {
                Log::info("ImportProductsFromCSV::Processing... Processed {$i} lines");
            }

            // Skip the header row
            if ($i == 0) {
                $i++;
                continue;
            }

            $productData = explode(",", $line);
            $productData = str_getcsv($line);

            // Ensure the array has the expected number of elements
            if (count($productData) < 8) {
                Log::warning("ImportProductsFromCSV::Warning... Insufficient columns in CSV line: {$i} \n");
                $i++;
                continue;

                // We can also throw an exception in this scenario
                // throw new Exception("Insufficient columns in CSV line: {$i}");
            }

            // Add the product id to the productIds cache to be processed later
            array_push($productIds, $productData[0]);

            // Add the current row to the current batch
            $currentBatch[] = $productData;

            // If the current batch is full, dispatch the ProcessProductInBatch job and set current batch empty
            if (count($currentBatch) >= $this->batchSize) {
                $this->batch()->add(new ProcessProductInBatch($currentBatch));
                $currentBatch = [];
            }

            $i++;
        }

        // Add remaining products to last ProcessProductInBatch job
        if (count($currentBatch) > 0) {
            $this->batch()->add(new ProcessProductInBatch($currentBatch));
        }

        Cache::add("productsIds:{$this->filePath}", $productIds, 600);

        Log::info("ImportProductsFromCSV::Finished... Processed {$i} lines");
    }
}
