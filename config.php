<?php
/**
 * Configuration File
 */
define('DS', DIRECTORY_SEPARATOR);

define('BASE_PATH', realpath(dirname(__FILE__)) . DS);

$_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
  
define('BASE_URL', $_protocol ."://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

// Videos to convert path
define('SOURCE_PATH', BASE_PATH . 'source'. DS);

// Converted videos output path
define('OUTPUT_PATH', BASE_PATH .'output'. DS);

// Logs Path
define('LOG_PATH', BASE_PATH .'logs'. DS);

// POST URL for Javascript Queries
define('POST_URL', BASE_URL .'process.php');

// Execution Script URL (Where the ffmpeg command will be posted to)
define('EXEC_URL', BASE_URL .'ffmpegExec.php');

// FFMPEG Path (ffmpeg.exe)
define('FFMPEG_PATH', 'C:\\ffmpeg\\bin\\ffmpeg.exe');

// FFMPEG Password
define('FFMPEG_PW', sha1('123abc'));

if( !file_exists(SOURCE_PATH) )
    mkdir(SOURCE_PATH, 0755, true);

if( !file_exists(OUTPUT_PATH) )
    mkdir(OUTPUT_PATH, 0755, true);

if( !file_exists(LOG_PATH) )
    mkdir(LOG_PATH, 0755, true);
