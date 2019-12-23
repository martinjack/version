<?php

namespace SyntaxEvolution\Version\Package;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use SyntaxEvolution\Version\Package\Console\Commands\Absorb;
use SyntaxEvolution\Version\Package\Console\Commands\Build;
use SyntaxEvolution\Version\Package\Console\Commands\Major;
use SyntaxEvolution\Version\Package\Console\Commands\Minor;
use SyntaxEvolution\Version\Package\Console\Commands\Patch;
use SyntaxEvolution\Version\Package\Console\Commands\Refresh;
use SyntaxEvolution\Version\Package\Console\Commands\Show;
use SyntaxEvolution\Version\Package\Console\Commands\Version as VersionCommand;
use SyntaxEvolution\Version\Package\Support\Config;
use SyntaxEvolution\Version\Package\Support\Constants;
use PragmaRX\Yaml\Package\Yaml;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The package config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Console commands to be instantiated.
     *
     * @var array
     */
    protected $commandList = [
        'syntaxevolution.version.command' => VersionCommand::class,

        'syntaxevolution.version.build.command' => Build::class,

        'syntaxevolution.version.show.command' => Show::class,

        'syntaxevolution.version.major.command' => Major::class,

        'syntaxevolution.version.minor.command' => Minor::class,

        'syntaxevolution.version.patch.command' => Patch::class,

        'syntaxevolution.version.refresh.command' => Refresh::class,

        'syntaxevolution.version.absorb.command' => Absorb::class,
    ];

    /**
     * Boot Service Provider.
     */
    public function boot()
    {
        $this->publishConfiguration();

        $this->registerBlade();
    }

    /**
     * Get the config file path.
     *
     * @return string
     */
    protected function getConfigFile()
    {
        return config_path('version.yml');
    }

    /**
     * Get the original config file.
     *
     * @return string
     */
    protected function getConfigFileStub()
    {
        return __DIR__.'/../config/version.yml';
    }

    /**
     * Load config.
     */
    protected function loadConfig()
    {
        $this->config = new Config(new Yaml());

        $this->config->setConfigFile($this->getConfigFile());

        $this->config->loadConfig();
    }

    /**
     * Configure config path.
     */
    protected function publishConfiguration()
    {
        $this->publishes([
            $this->getConfigFileStub() => $this->getConfigFile(),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerService();

        $this->loadConfig();

        $this->registerCommands();
    }

    /**
     * Register Blade directives.
     */
    protected function registerBlade()
    {
        Blade::directive(
            $this->config->get('blade_directive', 'version'),
            function ($format = Constants::DEFAULT_FORMAT) {
                return "<?php echo app('syntaxevolution.version')->format($format); ?>";
            }
        );
    }

    /**
     * Register command.
     *
     * @param $name
     * @param $commandClass string
     */
    protected function registerCommand($name, $commandClass)
    {
        $this->app->singleton($name, function () use ($commandClass) {
            return new $commandClass();
        });

        $this->commands($name);
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands()
    {
        collect($this->commandList)->each(function ($commandClass, $key) {
            $this->registerCommand($key, $commandClass);
        });
    }

    /**
     * Register service service.
     */
    protected function registerService()
    {
        $this->app->singleton('syntaxevolution.version', function () {
            $version = new Version($this->config);

            $version->setConfigFileStub($this->getConfigFileStub());

            return $version;
        });
    }
}
