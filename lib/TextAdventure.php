<?php

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class TextAdventure {

	public static $log;

	public static function create_log_instance(){
		if(empty(self::$log)) {
			$min_log_level = Logger::DEBUG;
			self::$log = new Logger('text-adventure-lib');
			if(PHP_SAPI == 'cli') {
				self::$log->pushHandler(new StreamHandler("php://stdout", $min_log_level));
			} else {
				self::$log->pushHandler(new StreamHandler(dirname(__DIR__) . '/data/logs/text-adventure.log', $min_log_level));
			}
		}
	}

	/**
	 * Process a message from Twitter and decide what tale to load and what to reply.
	 *
	 * @param Array $message
	 *
	 * @return Bool|Array FALSE in case there's no reply, an array containing the text
	 * 						and other extra info.
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public static function process($message) {
		self::create_log_instance();
		if(!isset($message['entities']['user_mentions']['screen_name'])) {
			self::$log->addWarning("This tweet is not a mention to any user.");
			self::$log->addWarning(print_r($message, TRUE));
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

	/*
	 * $message['user']['screen_name'] --> Author of the tweet
	 * $message['text'] --> Text of the user
	 */
	private static function test($message) {
		///TODO: Check the state on which the player is and reply the proper answer.
		self::$log->addInfo(print_r($message, TRUE));
	}

}
