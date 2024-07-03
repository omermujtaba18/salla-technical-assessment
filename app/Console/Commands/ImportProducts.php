<?php

namespace App\Console\Commands;

use App\Models\ProductCurrency;
use App\Models\Products;
use App\Models\ProductStatus;
use Illuminate\Console\Command;
use App\Rules\ProductValidator;

class ImportProducts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'import:products {file : The CSV file to import} {maxRowsToProcess : The maximum number of rows to process}';

    /**
     * @var string
     */
    protected $description = 'Imports products into database';

    /**
     * The ProductValidator instance.
     *
     * @var ProductValidator
     */
    protected $validator;

    /**
     * Create a new command instance.
     *
     * @param ProductValidator $validator
     * @return void
     */
    public function __construct(ProductValidator $validator)
    {
        parent::__construct();
        $this->validator = $validator;
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        // Get the file as an input 
        $file = $this->argument('file');

        // Added maxRowsToProcess to assist in testing
        $maxRowsToProcess = (int)$this->argument('maxRowsToProcess');

        // Validate the file
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return;
        }

        $startTime = microtime(true);
        $contents = file_get_contents($file);
        $lines = explode("\n", $contents);

        $i = 0;

        foreach ($lines as $line) {

            if ($i % 1000 == 0) {
                print("Processing... Processed {$i} products \n");
            }

            // Skip the header row
            if ($i == 0) {
                $i++;
                continue;
            }

            $fields = explode(",", $line);
            $fields = str_getcsv($line);

            // Ensure the array has the expected number of elements
            if (count($fields) < 8) {
                print("Insufficient columns in CSV line: {$i} \n");
                $i++;
                continue;

                // We can also throw an exception in this scenario and ask the user to fix the csv first
                // throw new Exception("Insufficient columns in CSV line: {$i}");
            }

            $data = [
                'id' => $fields[0],
                'name' => $fields[1],
                'sku' => $fields[2],
                'price' => $fields[3],
                'currency' => $fields[4],
                'variations' => $fields[5],
                'quantity' => $fields[6],
                'status' => $fields[7],
            ];

            $data = $this->validator->validate($data);

            // SKU is unique so if it exists already set it to null and incomplete_import
            if ($data['sku'] && Products::withTrashed()->where('sku', $data['sku'])->first()) {
                $data['sku'] = null;
                $data['incomplete_import'] = true;
            }

            $currency = $data['currency'] ? ProductCurrency::firstOrCreate(['currency' => $data['currency']], ['currency' => $data['currency']]) : null;
            $status = $data['status'] ? ProductStatus::firstOrCreate(['status' => $data['status']], ['status' => $data['status']]) : null;

            $isProductDeleted = $data['status'] === 'deleted';

            $product = Products::withTrashed()->updateOrCreate(['id' => $data['id']],  [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'product_currency_id' => $currency ? $currency->id : null,
                'quantity' => $data['quantity'],
                'product_status_id' => $status ? $status->id : null,
                'incomplete_import' => $data['incomplete_import'] ?? false,
                'deletion_reason' => $isProductDeleted ? 'synchronization' : null
            ]);

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

            if (isset($maxRowsToProcess) && $i === $maxRowsToProcess) {
                break;
            }

            $i++;
        }

        $startDateTime = (new \DateTime())->setTimestamp($startTime);

        // Delete products that are not in the current file run
        Products::where('updated_at', '<', $startDateTime)->update(['deletion_reason' => 'synchronization', 'deleted_at' => now()->toDateTimeString()]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $formattedTime = gmdate('H:i:s', (int)$executionTime);

        print("Execution Time: {$formattedTime} \n");

        die("Updated {$i} products.\n");
    }
}
