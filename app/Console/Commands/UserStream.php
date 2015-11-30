<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class UserStream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'twitter:userstream';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initiate a user stream for twitter';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        dd($this->argument('username'));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('username', InputArgument::REQUIRED, 'The twitter user who the command will listen the stream of.'),
        );
    }
}
