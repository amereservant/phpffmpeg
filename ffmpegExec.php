<?php
/**
 * FFMPEG Execution
 *
 * This file handles the actual exec() command to trigger the ffmpeg.exe conversion.
 * It gets called by the {@link ffmpegConvert::exec()} command in the functions.php file.
 *
 * @version 0.1
 */
require 'config.php';
require 'functions.php';

$cmd        = _chkVal('cmd', '');
$ffmpegpw   = _chkVal('ffmpegpw');
$fkey       = _chkVal('fkey', '');

$ffmpegConvert = new ffmpegConvert($fkey);


//<<-- CHECK FOR ERRORS -->>//

// Check the command string is valid
if( strlen($cmd) < 1 )
{
    $ffmpegConvert->addError('Invalid ffmpeg command given! LINE:'. __LINE__);
    json_response(array('fkey' => $fkey, 'msg' => 'Invalid ffmpeg command given!  Cannot execute command.'), true);
}

// Check password matches the value in config.php
if( $ffmpegpw !== FFMPEG_PW )
{
    $ffmpegConvert->addError('Invalid ffmpeg password given!  IP `'.$_SERVER["REMOTE_ADDR"].
        '` tried entering the password `'. $ffmpegpw .'`! LINE:'. __LINE__);
    json_response(array('fkey' => $fkey, 'msg' => 'Invalid ffmpeg password given!'), true);
}

//<<-- END OF ERROR CHECK -->>//

// Add status log data showing us the execution is beginning
$ffmpegConvert->writeStatus('ffmpeg: Executing command '. FFMPEG_PATH .' '. $cmd);
// Execute the command ... FINALLY!
exec(FFMPEG_PATH .' '. $cmd);

