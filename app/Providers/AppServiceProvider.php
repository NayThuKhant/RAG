<?php

namespace App\Providers;

use App\Parsers\AiParser;
use App\Parsers\CsvParser;
use App\Parsers\HtmlParser;
use App\Parsers\ParserManager;
use App\Parsers\PdfParser;
use App\Parsers\PlainTextParser;
use App\Parsers\SpreadsheetParser;
use App\Parsers\WordParser;
use App\Services\TextTransformationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TextTransformationService::class);

        $this->app->singleton(ParserManager::class, fn () => new ParserManager(
            parsers: [
                new PlainTextParser,
                new HtmlParser,
                new CsvParser,
                new SpreadsheetParser,
                new WordParser,
                new PdfParser,
            ],
            mimeDetector: new ExtensionMimeTypeDetector,
            fallback: new AiParser,
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
