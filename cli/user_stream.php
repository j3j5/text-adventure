<?php

declare(ticks = 1);

use j3j5\TwitterApio;
require(dirname(__DIR__) . '/lib/TextAdventure.php');

register_shutdown_function('_shutdown');

// 	if(empty($user)) {
// 		$log->addError("You must provide a topic.");
// 		exit;
// 	}

	$site_stream_config = array(
		'method' 	=>"https://userstream.twitter.com/1.1/user.json",
		'params' 	=> array(
							'with'	=> "user",
						),
		'callback' => 'my_callback',
	);

	// Stats
	$max_replies = 200;
	$total_replies = 0;
	$total_msg = 0;
	$users_replied = array();
	$msg_used = array();


	$log->addInfo("Starting tracking ...");

	/**
	 * TODO: Atm, the token and secret are read from the config, they should be
	 *		read from a DB and be loaded through the 'user' parameter on the cli
	 * 		so this controller is fully flexible, TextAdventure library will handle
	 * 		the different tales based on the user the tweet is refered to.
	*/

	$api = new TwitterApio($twitter_settings);
	$res = $api->streaming_request('GET', $site_stream_config['method'], $site_stream_config['params'], $site_stream_config['callback']);
	$log->addInfo("DONE");
	$log->addInfo(print_r($res, TRUE));
	$stream_start = microtime(TRUE);


	/**
	* Callback for the stream.
	*
	* Each new event that comes to the stream is sent to this function.
	*
	* @param String $data JSON object containing the event information
	* @param Int $length The length of the string $data
	* @param Array $metrics Stream metrics
	*
	* @author Julio Foulquié <jfoulquie@gmail.com.com>

	* @return While returning FALSE the stream will keep running, in case of
	* 			returning TRUE the stream will close the connection.
	*/
	function my_callback($data, $length, $metrics) {
		global	$twitter_settings, $log,
				$total_replies, $total_msg, $max_replies,
				$stream_start, $users_replied, $api, $gifs,
				$keywords, $text_patterns, $msg_used;

		if($length > 0) {

			if(!empty($metrics)) {
				$log->addDebug("Total message: " . $metrics['messages']);
				$log->addDebug("Time since last message: " . ($metrics['interval_start'] - $metrics['start']));
			}

			$message = json_decode($data, TRUE);
			if(!empty($message)) {
				// Set flags
				$ignore = FALSE;
				$send = FALSE;

				// This is the welcome message when you connect to the stream, a list of your friends
				if(isset($message['friends'])){
					$log->addInfo("Connected!!");
					$ignore = TRUE;
				}

				// Ignore my own messages
				if(isset($message['user']) && $message['user']['id'] == $twitter_settings['user_id']) {
					$log->addDebug("I just tweeted this, ignore...");
					$ignore = TRUE;
				}

				// Ignore retweets
				if(isset($message['retweeted_status']) && !empty($message['retweeted_status'])) {
					$log->addDebug("This is a RT, ignore...");
					$log->addDebug("Original text: {$message['retweeted_status']['text']}");
					$ignore = TRUE;
				}

				// Process the message
				if(!$ignore) {
					$reply = TextAdventure::process($message);
					if(!empty($reply)) {
						$send = TRUE;
					}
				}

				// Set a limit on the amount of replies
				if($send && $total_replies < $max_replies) {
					$log->addInfo("Sending reply: {$reply['text']}");
					$api->post('statuses/update', array('status' => $reply['text'], 'in_reply_to_status_id' => $message['id'], 'possibly_sensitive' => TRUE));
				}
			} else {
				$log->addError("json_decode() failed for $data");
			}
		}

		$total_msg++;
		///TODO: Reset counters every now and then (1 a day??)

		return FALSE;
	}

	function _shutdown() {
		global $log, $total_msg, $total_replies, $msg_used;

		$log->addInfo("Total messages processed: $total_msg");
		$log->addInfo("Total replies: " . print_r($total_replies, TRUE));
		$log->addInfo("Total messages: " . print_r($msg_used, TRUE));
	}
