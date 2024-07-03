# Product Import and Synchronization Service

### Local Environment Setup

1.  Start containers

        docker compose up -d

2.  Set environment variables

        cp .env.example .env

3.  Install dependencies

        composer install

### Testing synchronization with external services

To run the scheduler and execute the `php artisan app:sync-products {service}` command daily at 12am, use:

    php artisan schedule:work

To test the app:sync-products command standalone, use:

    php artisan app:sync-products ServiceA

### Testing import:products Without Parallelization

To run the import:products command without parallelization:

    php artisan import:products products.csv --parallelizationEnabled=false

You can optionally pass the --maxRowsToProcess argument as well.

### Testing import:products With Parallelization Enabled (To Be Implemented)

1. Start workers

    php artisan queue:work

2. Run import:products Command

    php artisan import:products products.csv

    You can optionally pass the --maxRowsToProcess argument as well.
