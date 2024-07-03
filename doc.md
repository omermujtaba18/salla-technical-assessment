# Product Import and Synchronization Service

### Local Environment Setup

1.  Start containers

        docker compose up -d

2.  Set environment variables

        cp .env.example .env

3.  Install dependencies

        composer install

### Testing synchronization with external services

Run `php artisan schedule:work` this would run the scheduler and execute the `php artisan app:sync-products ServiceA` command daily at 12am

If you would like to test the app:sync-products standalone you can run `php artisan app:sync-products ServiceA` directly

### Testing import:products without parallelization

1. Run `php artisan import:products products.csv`, you can optionally pass maxRowsToProcess as well

### Testing import:products with parallelization enabled (to be implemented)

1. Start workers with `php artisan queue:work`
2. Run `php artisan import:products products.csv` you can optionally pass maxRowsToProcess as well
