<?php

namespace App\Providers;

use App\Settings\AiMatchingSettings;
use Illuminate\Support\ServiceProvider;
use OpenAI;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\OpenAI\Client::class, function () {
            try {
                $settings = app(AiMatchingSettings::class);
                $apiKey = $settings->openai_api_key;
            } catch (\Throwable) {
                $apiKey = null;
            }

            $apiKey = $apiKey ?: config('openai.api_key', env('OPENAI_API_KEY', ''));

            return OpenAI::client($apiKey ?: 'sk-placeholder');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
