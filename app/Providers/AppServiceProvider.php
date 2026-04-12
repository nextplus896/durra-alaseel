<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\Admin\Language;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;


ini_set('memory_limit', '-1');

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
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);

        // Register model observers
        User::observe(UserObserver::class);
        // Share active languages with all views (guarded against DB errors during migrations)
        try {
            $languages = Language::where('status', 1)->select(['id', 'name', 'code', 'dir'])->get();
        } catch (\Exception $e) {
            $languages = collect();
            Log::warning('Unable to load languages for view share: ' . $e->getMessage());
        }

        // If DB doesn't contain expected languages (or is empty), fall back to scanning lang/*.json
        // This ensures the selector can show locales like 'ar' and 'fr' even if DB rows are missing.
        try {
            $dbCodes = $languages->pluck('code')->map(fn($c) => strtolower($c))->toArray();
            $langFiles = File::files(base_path('lang'));
            $nameMap = [
                'ar' => 'Arabic',
                'fr' => 'French',
                'es' => 'Spanish',
                'en' => 'English'
            ];

            foreach ($langFiles as $file) {
                if ($file->getExtension() !== 'json') continue;

                $code = strtolower(pathinfo($file->getFilename(), PATHINFO_FILENAME));

                if (in_array($code, $dbCodes)) continue; // already present from DB

                $obj = new \stdClass();
                $obj->id = null;
                $obj->name = $nameMap[$code] ?? ucfirst($code);
                $obj->code = $code;
                $obj->dir = ($code === 'ar') ? 'rtl' : 'ltr';

                $languages->push($obj);
            }
        } catch (\Exception $e) {
            Log::warning('Unable to scan lang files for fallback languages: ' . $e->getMessage());
        }

        View::share('languages', $languages);
    }
}
