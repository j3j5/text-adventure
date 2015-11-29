<?php namespace App\Console\Commands;

use App\Libraries\TextAdventure;
use j3j5\TweeStory;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Log;

class CliGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'play:story';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Play from the CLI any of the stories.';

    private $story;

    private $textadventure;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $story_name = $this->argument('story');
        if(is_file(storage_path("app/$story_name.json"))) {
            $file = storage_path("app/$story_name.json");
            $this->story = new TweeStory($file);
        } else {
            throw new \RuntimeException("The story does NOT exist!");
        }

        $this->textadventure = new TextAdventure($this->story);
        $current = TRUE;
        while($current) {
            $current = $this->textadventure->getCurrent();
            $this->line($current->text);
            if(!empty($current->links) && is_array($current->links)) {

                $this->line('You can do:');
                $this->printLinks();

                $answer = $this->ask('What do you do?');

                $this->textadventure->processAnswer($answer);
            } else {
                // The End
                $current = FALSE;
            }
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['story', InputArgument::REQUIRED, 'The story to be loaded'],
        ];
    }

    /**
     * Print out all possible links for the current passage.
     *
     * @return void
     */
    private function printLinks() {
        foreach($this->textadventure->getCurrentLinks() AS $link) {
            $this->line($link['text']);
        }
    }

}
