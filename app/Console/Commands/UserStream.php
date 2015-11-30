<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use App\Libraries\TextAdventure;
use App\Story;
use App\User;
use j3j5\TweeStory;
use j3j5\TwitterApio;
use Log;

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

    private $api;
    private $username;

    private $story;
    private $story_db;

    // Stats
    private $max_replies = 200;
    private $total_replies = 0;
    private $total_msg = 0;
    private $users_replied = [];
    private $msg_used = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        app()->configure('twitter');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->username = $this->argument('username');
        $this->loadStory();
        $site_stream_config = [
            'method'    =>"https://userstream.twitter.com/1.1/user.json",
            'params'    => ['with'  => "user",],
            'callback' => [$this, 'message'],
        ];

        $this->line("Starting tracking ...");
        $config = config("twitter.{$this->username}");
        $this->api = new TwitterApio($config);
        $stream_start = microtime(TRUE);
        $res = $this->api->streaming_request('GET', $site_stream_config['method'], $site_stream_config['params'], $site_stream_config['callback']);
        Log::info("Stream is done!! after " . (microtime(TRUE) - $stream_start)/60 . " minutes.");
    }

    /**
    * Callback for the stream.
    *
    * Each new event that comes to the stream is sent to this function.
    *
    * @param String $data JSON object containing the event information
    * @param Int    $length The length of the string $data
    * @param Array  $metrics Stream metrics
    *
    * @author Julio Foulquié <jfoulquie@gmail.com.com>

    * @return While returning FALSE the stream will keep running, in case of
    *           returning TRUE the stream will close the connection.
    */
    public function message($data, $length, $metrics) {
        if($length > 0) {
            if(!empty($metrics)) {
                Log::debug("Total message: " . $metrics['messages']);
                Log::debug("Time since last message: " . ($metrics['interval_start'] - $metrics['start']));
            }

            $message = json_decode($data, TRUE);
            Log::debug("Message received!!", [$message]);
            if(!empty($message)) {
                // Set flags
                $ignore = FALSE;
                $send = FALSE;

                // This is the welcome message when you connect to the stream, a list of your friends
                if(isset($message['friends'])){
                    Log::info("Connected!!");
                    $ignore = TRUE;
                }

                // Ignore my own messages
                if(isset($message['user']) && $message['user']['screen_name'] == $this->username) {
                    Log::debug("I just tweeted this, ignore...");
                    $ignore = TRUE;
                }

                // Ignore retweets
                if(isset($message['retweeted_status']) && !empty($message['retweeted_status'])) {
                    Log::debug("This is a RT, ignore...");
                    Log::debug("Original text: {$message['retweeted_status']['text']}");
                    $ignore = TRUE;
                }

                // Process the message
                if(!$ignore) {
                    Log::info("processing answer!");
                    // Create the text adventure and don't allow manual navigation
                    $this->text_adventure = new TextAdventure($this->story, FALSE);

                    $player = User::firstOrCreate([
                        'username' => $message['user']['screen_name'],
                        'story_id' => $this->story_db->id
                    ]);

                    $reply = '@'.$player->username.' ';
                    if(!empty($player->current_passage)) {
                        Log::debug("The user has started this story, setting it to that point!");
                        $this->text_adventure->setStart($player->current_passage);
                    } else {
                        Log::debug("The user is starting this story!");
                        $reply .= "Hi! ";
                    }
                    // Check answer
                    $text = $this->sanitizeText($message['text']);
                    $player->last_answer = $text;
                    $this->text_adventure->processAnswer($text);
                    $current = $this->text_adventure->getCurrent();

                    // Update player
                    $player->current_passage = $current->title;
                    $player->save();

                    // Prepare answer
                    $reply .= $current->text;
                    if(!empty($current->links) && is_array($current->links)) {
                        $reply .= " You can do:\n";
                        $reply .= $this->printLinks();
                    }
                    if(!empty($reply)) {
                        $send = TRUE;
                    }
                    Log::debug("Reply: $reply");
                }

                // Set a limit on the amount of replies
                if($send && $this->total_replies < $this->max_replies) {
                    Log::info("Sending reply: \n\n$reply");
                    // Make sure reply isn't over the limit before posting
                    $reply = mb_substr($reply, 0, 139);
                    $this->api->post('statuses/update', array('status' => $reply, 'in_reply_to_status_id' => $message['id'], 'possibly_sensitive' => TRUE));
                }
            } else {
                Log::error("json_decode() failed for $data");
            }
        }

        $this->total_replies++;

        return false;
    }

    private function loadStory()
    {
        // Try to find the owner of the story
        $this->story_db = Story::whereOwner($this->username)->firstOrFail();
        if(!$this->story_db) {
            throw new \RuntimeException('There is no story for this username!!');
        }

        // Create the story out of the JSON
        $this->story = new TweeStory($this->story_db->json);
    }

    private function printLinks()
    {
        $text = '';
        foreach($this->text_adventure->getCurrentLinks() AS $link) {
            $text .= '#' . $link['text'] . "\n";
        }
        return $text;
    }

    private function sanitizeText($text)
    {
        $text = trim(str_replace("@".$this->username, '', $text));
        return $text;
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
