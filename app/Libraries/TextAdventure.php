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
        $this->sanitize($answer);
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

        Log::info("Processing answer: $answer");
        // Is it one of the links?
        foreach($this->current_passage->links AS $link) {
            $this->sanitize($link['text']);
            if( !empty($answer) && strcasecmp($answer, $link['text']) === 0 ) {
                $res = $this->story->followLink($link['link']);
                Log::debug("following link: {$link['link']}", ['next' => $res]);
                return;
            } else {
                Log::debug("There is no link to follow!!", ['link' => $link, 'answer' => $answer]);
            }
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

    public function setStart($last_passage)
    {
        return $this->story->moveTo($last_passage);
    }

    /**
     * Sanitize the answer given by the user.
     *
     * @param string $answer
     *
     * @return string
     */
    private function sanitize(&$answer)
    {
        $answer = trim(str_replace('#', '', $answer));
    }

}
