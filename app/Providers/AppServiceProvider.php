<?php

namespace App\Providers;

use App\Services\AI\AiMessageGenerator;
use App\Services\AI\AiProvider;
use App\Services\AI\GeminiAiProvider;
use App\Services\AI\NullAiProvider;
use App\Services\RevealService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(RevealService::class);
        $this->app->bind(AiProvider::class, fn (): AiProvider => config('services.gemini.api_key')
                ? new GeminiAiProvider
                : new NullAiProvider);
        $this->app->singleton(AiMessageGenerator::class, function ($app): AiMessageGenerator {
            $provider = $app->make(AiProvider::class);
            $fallback = config('services.gemini.api_key')
                && (bool) $app->make(SettingsService::class)->get('ai_fallback_enabled', true)
                ? new NullAiProvider
                : null;

            return new AiMessageGenerator($provider, $fallback);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || env('VERCEL')) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view): void {
            $settings = app(SettingsService::class);

            $view->with('platformSettings', [
                'platform_name' => $settings->get('platform_name', config('app.name')),
                'tagline' => $settings->get('platform_tagline', 'Honour the teachers who shaped your journey.'),
                'school_name' => $settings->get('school_name', 'SAVVY MOTHER INTERNATIONAL SCHOOL'),
                'principal_name' => $settings->get('principal_name', 'Dr. Meera Khanna'),
                'certificate_text' => $settings->get('certificate_text', 'With gratitude for guiding, inspiring, and shaping the lives of students.'),
            ]);
        });
    }
}
