<?php
require 'config.php';
require 'functions.php';

//<<-- CHECK FOR ERRORS -->>//

// Check Request Type
if( !isset($_POST['type']) )
    json_response(array('fkey' => $_POST['fkey'], 'msg' => 'Invalid process type!'), true);

$validTypes = array('convert', 'status');

if( !in_array($_POST['type'], $validTypes) )
    json_response(array('fkey' => $_POST['fkey'], 'msg' => 'Invalid process type!'), true);

if( !isset($_POST['fkey']) || strlen($_POST['fkey']) < 8 )
    json_response(array_merge($_POST, array('fkey' => '', 'msg' => 'Invalid fkey given!')), true);
    
if( $_POST['type'] == 'convert' && (!isset($_POST['filename']) || strlen($_POST['filename']) < 5) )
    json_response(array('fkey' => $_POST['fkey'], 'msg' => 'Invalid filename given!'), true);

//<<-- END OF ERROR CHECK -->>//



//<<-- PROCESS REQUEST -->>//
if( $_POST['type'] == 'convert' )
{
    /* !! TEMPORARY TESTING !! */
    $eTime = 0;
    $tTime = 400;
    
    $array = array(
        'time_encoded'  => $eTime,
        'time_total'    => $tTime,
        'time_encoded_min'  => sec2min($eTime),
        'time_total_min'    => sec2min($tTime),
        'fkey'              => $_POST['fkey']
    );

    json_response($array);
    
    //json_response(array('fkey' => $_POST['fkey']), false);
}

if( $_POST['type'] == 'status' )
{
    $tTime = 400;
    $array = array(
        'time_encoded'  => $_POST['tmpTime'] + 15,
        'time_total'    => $tTime,
        'time_encoded_min'  => sec2min($_POST['tmpTime'] + 5),
        'time_total_min'    => sec2min($tTime)
    );

    json_response($array);
}

json_response(array('msg' => 'Unhandled request type!'), true);


//var_dump($_POST);
