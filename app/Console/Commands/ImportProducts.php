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
    protected $signature = 'import:products {file : The CSV file to import}';

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
            if ($data['sku'] && Products::where('sku', $data['sku'])->first()) {
                $data['sku'] = null;
                $data['incomplete_import'] = true;
            }

            $currency = $data['currency'] ? ProductCurrency::updateOrCreate(['currency' => $data['currency']], ['currency' => $data['currency']]) : null;
            $status = $data['status'] ? ProductStatus::updateOrCreate(['status' => $data['status']], ['status' => $data['status']]) : null;

            Products::updateOrCreate(['id' => $data['id']],  [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'product_currency_id' => $currency->id,
                'variations' => $data['variations'],
                'quantity' => $data['quantity'],
                'product_status_id' => $status->id,
                'incomplete_import' => $data['incomplete_import'] ?? false
            ]);

            $i++;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $formattedTime = gmdate('H:i:s', (int)$executionTime);

        print("Execution Time: {$formattedTime} \n");

        die("Updated {$i} products.\n");
    }
}
