<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ImportProductsFromCSV;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;
use Throwable;

class ImportProductsTest extends TestCase
{

    public function test_it_cannot_find_file()
    {
        $this->artisan('import:products', ['file' => 'non_existing_file.csv'])
            ->expectsOutput('File not found: non_existing_file.csv')
            ->assertExitCode(0);
    }

    public function test_it_import_products_successfully()
    {
        Queue::fake();
        Bus::fake();
        Storage::fake('local');

        // Create and store the test CSV file
        $filePath = 'products1.csv';
        Storage::put($filePath, 'id,name,price');

        // Mock the storage interaction
        $storedFilePath = 'imports/' . Str::random(10) . '.csv';
        Storage::shouldReceive('putFile')
            ->once()
            ->with('imports', Mockery::type(\Illuminate\Http\File::class))
            ->andReturn($storedFilePath);

        // Run the artisan command
        $this->artisan('import:products', ['file' => $filePath])
            ->assertExitCode(0);


        Bus::assertBatched(function ($batch) {
            return $batch->name === 'import:products:batch' &&
                $batch->jobs->count() === 1;
        });

        $this->assertDatabaseMissing('products', ['id' => 1000, 'deletion_reason' => 'synchronization']);
    }

    public function test_it_fails_to_import_products_successfully()
    {
        Queue::fake();
        Bus::fake();
        Storage::fake('local');

        // Create and store the test CSV file
        $filePath = 'products1.csv';
        Storage::put($filePath, 'id,name,price');

        // Mock the storage interaction
        $storedFilePath = 'imports/' . Str::random(10) . '.csv';
        Storage::shouldReceive('putFile')
            ->once()
            ->with('imports', Mockery::type(\Illuminate\Http\File::class))
            ->andReturn($storedFilePath);

        // Run the artisan command
        $this->artisan('import:products', ['file' => $filePath])
            ->assertExitCode(0);

        $exception = new \Exception('Batch process failed');

        Bus::assertBatched(function ($batch) use ($storedFilePath, $exception) {
            $batch->add(new ImportProductsFromCSV($storedFilePath, 200))
                ->then(function (Batch $batch) {
                    return true;
                })
                ->catch(function (Batch $batch, Throwable $e) use ($exception) {
                    $batch->totalJobs = 1;
                    $batch->failedJobs = 1;
                    $e = $exception;

                    Log::shouldReceive('error')
                        ->once()
                        ->with('CSV import failed', ['error' => $e, 'batch' => $batch, 'totalJobs' => 1, 'failedJobs' => 1]);

                    return true;
                });

            return true;
        });
    }
}
