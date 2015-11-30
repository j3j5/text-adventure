<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\UserStream;
use App\Console\Commands\CliGame;

class CommandServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.twitter.stream', function()
        {
            return new UserStream;
        });

        $this->commands(
            'command.twitter.stream'
        );

        $this->app->singleton('command.play.story', function()
        {
            return new CliGame;
        });

        $this->commands(
            'command.play.story'
        );
    }
}
