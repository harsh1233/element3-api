<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        //CURRENCY FORMAT 
        Blade::directive('currency_format', function ($expression) {
            return "<?php echo '&euro;'.' '.number_format($expression, 2, ',', ''); ?>";
        });

         //CURRENCY FORMAT WITH OUT EURO SIGN
        Blade::directive('currency_format_without_euro', function ($expression) {
            return "<?php echo number_format($expression, 2, ',', ''); ?>";
        });

    }
}
