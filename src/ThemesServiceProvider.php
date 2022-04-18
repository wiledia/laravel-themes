<?php

namespace Wiledia\Themes;

use View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Wiledia\Themes\View\ThemeViewFinder;

class ThemesServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the service provider.
     *
     * @return null
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/themes.php' => config_path('themes.php')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/themes.php', 'wiledia.themes'
        );

        $this->registerServices();
        $this->registerNamespaces();
        $this->registerBladeDirectives();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return ['wiledia.themes', 'view.finder'];
    }

    /**
     * Register the package services.
     */
    protected function registerServices()
    {
        $this->app->singleton('wiledia.themes', function ($app) {
            $themes = [];
            $items = [];

            if ($path = config('themes.paths.absolute')) {
                if (file_exists($path) && is_dir($path)) {
                    $themes = $this->app['files']->directories($path);
                }
            }

            foreach ($themes as $theme) {
                $manifest = new Manifest($theme . '/theme.json');
                $items[] = $manifest;
            }

            return new Theme($items);
        });

        $this->app->singleton('view.finder', function ($app) {
            return new ThemeViewFinder($app['files'], $app['config']['view.paths'], null);
        });
    }

    /**
     * Register the theme namespaces.
     */
    protected function registerNamespaces()
    {
        $themes = app('wiledia.themes')->all();

        foreach ($themes as $theme) {
            app('view')->addNamespace($theme->get('slug'), app('wiledia.themes')->getAbsolutePath($theme->get('slug')) . '/views');
        }
    }

    /**
     * Register blade directives.
     */
    protected function registerBladeDirectives()
    {
        Blade::directive('theme', function ($expression) {
            return "<?php if (Theme::getCurrent() === {$expression}) : ?>";
        });

        Blade::directive('endtheme', function ($expression) {
            return '<?php endif; ?>';
        });
    }
}
