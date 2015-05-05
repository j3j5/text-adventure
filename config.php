<?php
require __DIR__ . '/vendor/autoload.php';

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

define("APP_ROOT", __DIR__);

$local_config_file = __DIR__ . '/config.local.php';

/**
 * Load here common styles and scripts for the page, you can add more on a per controller basis later
 */

// $css_files['header'][] = "//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css";
// $css_files['header'][] = "//netdna.bootstrapcdn.com/font-awesome/3.0.2/css/font-awesome.css";

// $js_files['footer'][] = "//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js";
// $js_files['footer'][] = "//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js";
// $js_files['footer'][] = "/js/global.js";

/**
 * Config variables.
 *
 */

$twitter_settings = array(
	'consumer_key'		=> 'YOUR_CONSUMER_KEY',
	'consumer_secret'	=> 'YOUR_CONSUMER_SECRET',
	'token'				=> 'YOUR_TOKEN',
	'secret'			=> 'YOUR_SECRET',
	'user_agent'		=> 'Text-Adventure'
);

$minimum_log_level = Logger::DEBUG;
$log = new Logger('text-adventure');

// Load local config
if(is_file($local_config_file)) {
	include($local_config_file);
}
