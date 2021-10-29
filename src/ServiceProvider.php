<?php

/**
 * This file is part of the TwigBridge package.
 *
 * @copyright Robert Crowe <hello@vivalacrowe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RTNatePHP\LaravelFileContentUpdates;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RTNatePHP\LaravelFileContentUpdates\Command\CleanCurrentMarkdownFiles;
use RTNatePHP\LaravelFileContentUpdates\Command\GenerateMarkdownFiles;
use RTNatePHP\LaravelFileContentUpdates\Command\UpdateFromMarkdownFiles;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates;
/**
 * Bootstrap Laravel TwigBridge.
 *
 * You need to include this `ServiceProvider` in your app.php file:
 *
 * <code>
 *     'providers' => [
 *         'TwigBridge\ServiceProvider'
 *     ];
 * </code>
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(ContentUpdates::class, function ($app)
        {
            return $app->make(ContentUpdateService::class);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommands();
        }
    }

    /**
     * Load the configuration files and allow them to be published.
     *
     * @return void
     */
    protected function loadConfiguration()
    {
        // $configPath = __DIR__ . '/../config/twigbridge.php';

        // if (! $this->isLumen()) {
        //     $this->publishes([$configPath => config_path('twigbridge.php')], 'config');
        // }

        // $this->mergeConfigFrom($configPath, 'twigbridge');
    }


    /**
     * Register console command bindings.
     *
     * @return void
     */
    protected function loadCommands()
    {
        $this->commands(
            [
                GenerateMarkdownFiles::class,
                CleanCurrentMarkdownFiles::class,
                UpdateFromMarkdownFiles::class
            ]
        );
    }

}
