<?php
/**
 * Process Requests
 *
 * This file processes ALL AJAX requests, which currently consists of the initial 
 * `convert` request to begin encoding the video, and `status` request that retrieves
 * the current encoding status so the progress bar can be updated.
 *
 * The $outfile variable should be changed to an input value, but is set this way
 * for now for testing purposes.
 *
 * @ver     0.1
 */
require 'config.php';
require 'functions.php';

//<<-- CHECK FOR ERRORS -->>//
$type       = _chkVal('type', '');
$fkey       = _chkVal('fkey', '');
$infile     = _chkVal('filename', '');
$outfile    = 'testing.mp4';
$params     = _chkVal('params', '');

// Check Request Type
$validTypes = array('convert', 'status');

if( !in_array($type, $validTypes) )
    json_response(array('fkey' => $fkey, 'msg' => 'Invalid process type!'), true);

// $fkey will always be 8 characters.
// It's created with PHP's hash() function using 'crc32' algorithm in index.php
if( strlen($fkey) != 8 )
    json_response(array_merge(array('fkey' => '', 'msg' => 'Invalid fkey given!')), true);

// Filename should be at least 5 (1 character + 4 character extension. EX : i.mp4)
if( $type == 'convert' && ( strlen($infile) < 5) )
    json_response(array('fkey' => $fkey, 'msg' => 'Invalid input filename given!'), true);

// Filename should be at least 5 (1 character + 4 character extension. EX : i.mp4)
if( $type == 'convert' && ( strlen($outfile) < 5) )
    json_response(array('fkey' => $fkey, 'msg' => 'Invalid output filename given!'), true);

if( $type == 'convert' && (strlen($params) < 1) )
    json_response(array('fkey' => $fkey, 'msg' => 'Invalid parameters given!'), true);

//<<-- END OF ERROR CHECK -->>//


$ffmpegConvert = new ffmpegConvert($fkey);


//<<-- PROCESS REQUEST -->>//

// Start the video conversion
if( $type == 'convert' )
{
    $ffmpegConvert->exec( $infile, $outfile, $params, $fkey );
    // Add 2 second delay to give the server time to start writing the status log,
    // otherwise $ffmpegConvert->jsonStatus() will trigger an error...
    sleep(2);
    $ffmpegConvert->jsonStatus();
}

// Check on video conversion progress
if( $_POST['type'] == 'status' )
{
    $ffmpegConvert->jsonStatus();
}

//<<-- END OF PROCESS REQUEST -->>//

// Shouldn't get to this, but if so, let's send a message for debugging reasons....
json_response(array('msg' => 'Unhandled request type!'), true);
