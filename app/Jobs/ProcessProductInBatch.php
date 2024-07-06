<?php

namespace App\Jobs;

use App\Models\Products;
use App\Rules\ProductValidator;
use DateTime;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessProductInBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lines;
    protected $validator;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 36000;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductValidator $productValidator): void
    {

        Log::info("ProcessProductInBatch::Started...");

        $this->validator = $productValidator;

        foreach ($this->lines as $line) {
            // Ensure the array has the expected number of elements
            if (count($line) < 8) {
                Log::warning("Insufficient columns in CSV line: {$line}");
                continue;
                // We can also throw an exception in this scenario and ask the user to fix the csv first
                // throw new Exception("Insufficient columns in CSV line: {$i}");
            }

            $data = [
                'id' => $line[0],
                'name' => $line[1],
                'sku' => $line[2],
                'price' => $line[3],
                'currency' => $line[4],
                'variations' => $line[5],
                'quantity' => $line[6],
                'status' => $line[7],
            ];

            $data = $productValidator->validate($data);


            DB::transaction(function () use ($data) {
                // SKU is unique so if it exists already set it to null and incomplete_import
                if ($data['sku'] && Products::withTrashed()->where('sku', $data['sku'])->first()) {
                    $data['sku'] = null;
                    $data['incomplete_import'] = true;
                }


                $isProductDeleted = $data['status'] === 'deleted';

                $product = Products::withTrashed()->updateOrCreate(['id' => $data['id']],  [
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'price' => $data['price'],
                    'quantity' => $data['quantity'],
                    'incomplete_import' => $data['incomplete_import'] ?? false,
                    'deletion_reason' => $isProductDeleted ? 'synchronization' : null
                ]);


                // Note: A better could be to store the currency and status on the store level
                $data['currency'] && $product->currency()->firstOrCreate(['currency' => $data['currency']]);
                $data['status'] && $product->status()->firstOrCreate(['status' => $data['status']]);

                $isProductDeleted ? $product->delete() : $product->restore();

                if (isset($data['variations'])) {
                    $decodedVariations = json_decode("{$data['variations']}", true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Mark the product as incomplete_product since variations are invalid
                        $product->incomplete_import = true;
                        $product->save();
                    } else {
                        $product->variation_types()->delete();
                        foreach ($decodedVariations as $variation) {
                            if (isset($variation['name'])) {
                                $productVariationType = $product->variation_types()->create(['variation_type_name' => $variation['name']]);
                                $values = array_filter(array_map('trim', explode(",", $variation['value'])));
                                if (isset($values) && is_array($values)) {
                                    foreach ($values as $value) {
                                        $productVariationType->product_variations()->create(['product_variation_name' => $value]);
                                    }
                                }
                            }
                        }
                    }
                }

                Log::info("Sleeping to simulate time consuming tasks");

                // Pausing script execution for two seconds simulating time consuming tasks
                sleep(120);

                Log::info("I'm awake now. Time consuming tasks have finished");
            }, 3);
        }

        Log::info("ProcessProductInBatch::Finished...");
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // Handle the failure, log it or notify someone, etc.
        Log::error("ProcessProductInBatch::Failed... " . $exception->getMessage());
    }
}
