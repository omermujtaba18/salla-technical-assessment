<?

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ServiceA;


class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ServiceA::class, function ($app) {
            return new ServiceA();
        });
    }

    public function boot()
    {
        //
    }
}
