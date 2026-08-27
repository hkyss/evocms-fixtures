<?php

declare(strict_types=1);

namespace hkyss\Fixtures;

use hkyss\Fixtures\Console\Commands\DropCommand;
use hkyss\Fixtures\Console\Commands\ListCommand;
use hkyss\Fixtures\Console\Commands\MakeCommand;
use Illuminate\Support\ServiceProvider;

class FixturesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fixtures.php', 'fixtures');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/fixtures.php' => $this->configTarget(),
        ], 'fixtures-config');

        if ($this->app->runningInConsole()) {
            $this->commands([MakeCommand::class, ListCommand::class, DropCommand::class]);
        }
    }

    private function configTarget(): string
    {
        if (!function_exists('config_path')) {
            return 'fixtures.php';
        }

        try {
            if ((new \ReflectionFunction('config_path'))->getNumberOfParameters() >= 2) {
                return config_path('fixtures.php', true);
            }
        } catch (\ReflectionException) {
            return config_path('fixtures.php');
        }

        return config_path('fixtures.php');
    }
}
