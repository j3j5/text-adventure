<?php

use j3j5\TwitterApio;

register_shutdown_function('shutdown');
// Catch Ctrl+C, kill and SIGTERM (Rollback)
pcntl_signal(SIGTERM, 'kill_shutdown');
pcntl_signal(SIGINT, 'kill_shutdown');

	if(empty($topic)) {
		$log->addError("You must provide a topic.");
		exit;
	}

	$topic = str_replace(' ', ',', $topic);
	$site_stream_config = array(
		'method' 	=>"https://stream.twitter.com/1.1/statuses/filter.json",
		'params' 	=> array(
							'follow'	=> "$topic",
						),
		'callback' => 'my_streaming_callback',
	);
	// Stats
	$max_replies = 200;
	$total_replies = 0;
	$total_msg = 0;
	$users_replied = array();
	$msg_used = array();

	// data
	$gifs = array(
		"http://media.giphy.com/media/BKqoHeHlbcRvW/giphy.gif",
		"http://media.giphy.com/media/BzuP1pEXp4vNm/giphy.gif",
		"http://media.giphy.com/media/HfaeC332y7P9u/giphy.gif",
		"http://media.giphy.com/media/mUN3Ws3ibf9tu/giphy.gif",
		"http://stream1.gifsoup.com/view/163412/patada-en-los-testiculos-o.gif",
		"http://33.media.tumblr.com/tumblr_lso313TZ2V1qk176yo1_400.gif",
		"https://33.media.tumblr.com/483a7487e3e13c922c02b34503450814/tumblr_njv70xjtJY1t0cscho1_400.gif",
		"http://forgifs.com/gallery/d/155006-3/Mascot-scares-girl.gif",
		"https://38.media.tumblr.com/ae2b69f9d7a77efdd81b5ace5e914230/tumblr_necpkuQQe61t1imo0o1_500.gif",
// 		"",
// 		"",
	);

	$keywords = array('arrima', 'afortunado', 'necias', 'abarca', 'follamos', 'maña');
	$text_patterns = array(
		$keywords[0] => "/((qui[eé]n|el que).*)?buen [aá]rbol se (arrima|junta.*)(.*buena sombra(.*le co[bv]ija)?)?/ui",
		$keywords[1] => "/afortunado en el juego/ui",
		$keywords[2] => "/a palabras necias/ui",
		$keywords[3] => "/quien mucho abarca/ui",
		$keywords[4] => "/o follamos todos .*? o /ui",
		$keywords[5] => "/(m[aá]s|\+) [vb]ale maña/ui",
		/*
			* dime de qué presumes y te diré de qué careces
			* no por mucho madrugar amanece 	más temprano
			* lo cortés no quita lo valiente
			*
			*/
	);

	foreach($keywords AS $word) {
		$msg_used[$word] = 0;
	}

	$log->addInfo("Starting tracking \"$topic\"...");

	$api = new TwitterApio($twitter_settings);
	$res = $api->streaming_request('POST', $site_stream_config['method'], $site_stream_config['params'], $site_stream_config['callback']);
	$log->addInfo("DONE");
	$log->addInfo(print_r($res, TRUE));
	$stream_start = microtime(TRUE);



/**
 * First callback.
 * The first message sent by the stream is information about it, like the
 * URL to control it. It sends that info to the 'control' script through the
 * 'started' queue and the it switches the callback to my_streaming_callback2.
 *
 * @param String $data
 * @param Int $lenght
 * @param ? $metrics
 *
 * @return FALSE
 *
 * @author Julio Foulquié <jfoulquie@gmail.com.com>
 */
function my_streaming_callback($data, $length, $metrics) {
	global $log, $twitter_settings, $api, $total_msg;

	$log->addInfo("Connected!, changing callbacks...");
	$json = json_decode($data, true);
	$log->addDebug(print_r($data, TRUE));
	$api->reconfigure(array_merge($twitter_settings, array('streaming_callback' => "my_streaming_callback2")));
	$total_msg++;

	return FALSE;
}

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
function my_streaming_callback2($data, $length, $metrics) {
	global	$twitter_settings, $log,
			$total_replies, $total_msg, $max_replies,
			$stream_start, $users_replied, $api, $gifs,
			$keywords, $text_patterns, $msg_used;

	if($length > 0) {

		// What are the metrics for?
		if(!empty($metrics)) {
			$log->addDebug("Total message: " . $metrics['messages']);
			$log->addDebug("Time since last message: " . ($metrics['interval_start'] - $metrics['start']));
// 			$log->addDebug("Metrics: " . print_r($data, TRUE));
		}

// 		$log->addDebug("Length: $length");

		$message = json_decode($data, TRUE);
		if(!empty($message)) {
			// Set flags
			$ignore = FALSE;
			$send = FALSE;

			// Set reply message
			$patada = "patada en los cojones " . $gifs[mt_rand(0,count($gifs) - 1)];
			$replies = array(
				$keywords[0] => "@{$message['user']['screen_name']} el que a buen árbol se arrima, " . $patada,
				$keywords[1] => "@{$message['user']['screen_name']} afortunado en el juego, " . $patada,
				$keywords[2] => "@{$message['user']['screen_name']} a palabras necias, " . $patada,
				$keywords[3] => "@{$message['user']['screen_name']} quien mucho abarca, " . $patada,
				$keywords[4] => "@{$message['user']['screen_name']} o follamos todos o " . $patada,
				$keywords[5] => "@{$message['user']['screen_name']} más vale maña que " . $patada,
			);

			// Ignore my own messages
			if($message['user']['id'] == $twitter_settings['user_id']) {
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
				if($message['lang'] == 'es') {
					$matches = array();
					// Which one of the keywords matched?
					if( preg_match('/(' . implode('|', $keywords) . ')/iu', $message['text'], $matches) ) {
						// normalize keyword
						$matches[1] = mb_strtolower($matches[1]);

						if(preg_match($text_patterns[$matches[1]], $message['text'])) {
							$send = TRUE;
						}
					} else {
						$log->addWarning("No keyword was found on the tweet: " . $message['text']);
					}
				} else {
					$log->addWarning("¿Pero aquí qué idioma habláis? ({$message['lang']}): " . $message['text']);
				}
			}

			// Set a limit on the amount of replies
			if($send && $total_replies < $max_replies) {
				// Did we tweet to this user already?
				if(!isset($users_replied[$message['user']['screen_name']])) {
					$log->addInfo("Sending reply: {$replies[$matches[1]]}");
					$api->post('statuses/update', array('status' => $replies[$matches[1]], 'in_reply_to_status_id' => $message['id'], 'possibly_sensitive' => TRUE));
					// Update stats
					$users_replied[$message['user']['screen_name']] = 1;
					$msg_used[$matches[1]]++;
					$total_replies++;
				} else {
					switch($users_replied[$message['user']['screen_name']]) {
						case 1:
						case 2:
						case 3:
						case 4:
						default:
							break;
					}
				}
			}
		} else {
			$log->addError("json_decode() failed for $data");
		}
	}

	$total_msg++;
	///TODO: Reset counters every now and then (1 a day??)

	return FALSE;
}

function shutdown() {
	global $log, $total_msg, $total_replies, $msg_used;

	$log->addInfo("Total messages processed: $total_msg");
	$log->addInfo("Total replies: " . print_r($total_replies, TRUE));
	$log->addInfo("Total messages: " . print_r($msg_used, TRUE));
}

/**
 * Method, that is executed, if script has been killed by
 * SIGINT: Ctrl+C
 * SIGTERM: kill
 *
 * @param int $signal
 *
 */
function kill_shutdown($signal) {
	global $log;
	if ($signal === SIGINT || $signal === SIGTERM) {
		$log->addInfo("Ouch, I've been killed again! :(");
		exit; // After this, stream_shutdown is called
	} else {
		$log->addError("Weird signal caught: $signal");
	}
}
