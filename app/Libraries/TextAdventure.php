<?php namespace App\Libraries;

use Log;
use j3j5\TweeStory;

class TextAdventure {

    private $story;
    private $current_passage;
    private $allow_manual_navigation;

    public function __construct(TweeStory $story, $allow_manual_navigation = TRUE)
    {
        $this->story = $story;
        $this->current_passage = $this->story->getCurrentPassage();
        $this->allow_manual_navigation = $allow_manual_navigation;
    }

    public function processAnswer($answer)
    {
        $this->current_passage = $this->story->getCurrentPassage();
        $answer = $this->sanitizeAnswer($answer);
        if($this->allow_manual_navigation) {
            if($answer === 'undo') {
                $this->story->undo();
                return;
            }

            if($answer === 'redo') {
                $this->story->redo();
                return;
            }
        }

        // Is it one of the links?
        foreach($this->current_passage->links AS $link) {
            if( !empty($answer) && strcasecmp($answer, $link['text']) === 0 ) {
                $res = $this->story->followLink($link['link']);
                Log::debug("following link: {$link['link']}", ['next' => $res]);
                return;
            }
        }
    }

    /**
     * Process a message from Twitter and decide what tale to load and what to reply.
     *
     * @param Array $message
     *
     * @return Bool|Array FALSE in case there's no reply, an array containing the text
     *                      and other extra info.
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function processFromTwitter($message) {
        if(!isset($message['entities']['user_mentions']['screen_name'])) {
            Log::warning("This tweet is not a mention to any user.");
            Log::warning(print_r($message, TRUE));
            return;
        }

        // Depending on whom the mention is refered to, load a different tale.
        switch ($message['entities']['user_mentions']['screen_name']) {
            case "0003Julio":
                self::test($message);
                break;
            default:
                break;
        }

    }

    /**
     * Return current passage for the story.
     *
     * @return j3j5\TweePassage
     */
    public function getCurrent()
    {
        return $this->story->getCurrentPassage();
    }

    /**
     * Return all links for the current passage of the story.
     *
     * @return array|bool
     */
    public function getCurrentLinks()
    {
        return $this->getCurrent()->links;
    }

    /**
     * Sanitize the answer given by the user.
     *
     * @param string $answer
     *
     * @return string
     */
    private function sanitizeAnswer($answer)
    {
        $answer = str_replace('#', '', $answer);
        return $answer;
    }

}
