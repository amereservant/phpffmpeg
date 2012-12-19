<?php
defined('SOURCE_PATH') || die('SOURCE_PATH not defined!');

/**
 * Get Directory Files
 *
 * @param   string  $path   The full path of the directory we want the files of
 * @return  array           Either an empty array (if no files exist) or an array of files
 */
function _get_files( $path )
{
    $cont = scandir($path);
    $files = array();
    foreach($cont as $item)
    {
        if( $item == '.' || $item == '..' || is_dir($path . $item) )
            continue;
        $files[] = $item;
    }
    return $files;
}

/**
 * Get Source Files
 */
function _source_files()
{
    return _get_files(SOURCE_PATH);
}

/**
 * Get Converted Files
 */
function _converted_files()
{
    return _get_files(OUTPUT_PATH);
}

function json_response( $data, $error=false )
{
    if( $error )
    {
        header('HTTP/1.1 500 JSON Error');
    }
    else
    {
        header('HTTP/1.1 200 OK');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 01 Jan 1970 00:00:00 GMT');
        header('Content-type: application/json');
    }
    
    if(!is_array($data))
        echo json_encode(array($data), true);
    else
        echo json_encode($data, true);
    exit;
}

/**
 * Convert Seconds to Minutes
 */
function sec2min( $sec )
{
    return sprintf('%02d:%02d:%02d', floor($sec/3600), floor(($sec/60)%60), $sec%60);
}

class ffmpegConvert
{
    public $fkey;

    protected $logPath = '';

    protected $error = array();

    protected $warning = array();

    protected $ffmpegPath = '';

    protected $statusLog = '';

    public function __construct($fkey='')
    {
        $this->logPath      = LOG_PATH;
        $this->ffmpegPath   = FFMPEG_PATH;
        $this->_setFkey($fkey);
        
        register_shutdown_function(array(&$this, 'logErrors'));
        register_shutdown_function(array(&$this, 'logWarnings'));
    }

    private function _setFkey($fkey)
    {
        $this->fkey = $fkey;
        $this->_setStatusLogFkey();
    }
    
    private function _setStatusLogFkey()
    {
        $this->statusLog = $this->logPath . $this->fkey .'.ffmpeg.log';
    }
    
    protected function writeLog($msg, $file)
    {
        $logf = '['. date('d.m.y H:i:s') .'] '. $_SERVER['REMOTE_ADDR'] ." %s \n";

        // If the log file wasn't specified, append a log entry alerting us of it ...
        if( strlen($file) < 1 )
        {
            $filename       = $this->logPath . date('d-m-y') .'.error.log';
            $this->error[]  = 'writeLog() called without valid $file parameter! '.
                'LINE:'. __LINE__ .' FILE: '. __FILE__;
        }
        else
        {
            $filename = $this->logPath .$file;
        }

        $logStr       = sprintf($logf, $str);

        // Write the log file data
        $hdl = fopen($filename, 'a+');
        fwrite($hdl, $logstr);
        fclose($hdl);
        return;
    }

    public function exec( $inFile, $outFile, $params, $fkey='' )
    {
        if( strlen($fkey) < 1 )
            $fkey = time(); // Simply use UNIX timestamp

        $this->_setFkey($fkey);
        
        if( strlen($this->ffmpegPath) < 1 || !file_exists($this->ffmpegPath) )
            $this->error[] = 'Invalid FFMPEG Path `'. $this->ffmpegPath .'`!';

        if( strlen($inFile) < 1 || !file_exists($inFile) )
            $this->error[] = 'Invalid input file `'. $inFile .'`!';

        if( strlen($outFile) < 1 )
            $this->error[] = 'Invalid output file!';

        if( strlen($params) < 1 )
            $this->error[] = 'No parameters were given!  Please specify conversion parameters.';

        if( count($this->error) > 0 )
        {
            $progressf = '<span class="progress-error">ffmpeg: %s</span>';

            foreach($this->error as $error)
                sprintf($progressf, $error);

            return false;
        }
        
        $hdl = fopen($this->statusLog, 'w');
        fwrite($hdl, sprintf("%s\n%s\n%s\n", $inFile, $outFile, $params));
        fclose($hdl);

        $this->writeStatus("Sending FFMPEG exec command to {$_SERVER['HTTP_HOST']}...");
        
        $cmd    = ' -i "'. $inFile .'" '. $params .' "'. $outFile .'" 2> '. $this->statusLog;
        $pdata  = "cmd={$cmd}&ffmpegpw=". FFMPEG_PW;
        $fh     = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $errstr, 30);
        
        fputs($fh, 'POST '. EXEC_URL ." HTTP/1.0\n");
        fputs($fh, 'Host: '. $_SERVER['HTTP_HOST'] ."\n");
        fputs($fh, "Content-type: application/x-www-form-urlencoded\n");
        fputs($fh, "Content-length: ". strlen($pdata) ."\n");
        fputs($fh, "User-agent: FFmpeg PHP Progress script\n");
        fputs($fh, "Connection: close\n\n");
        fputs($fh, $pdata);
        fclose($fh);
        return;
    }

    public function validateStatus()
    {
        if( strlen($this->fkey) < 1 )
            $this->errors[] = 'The `$fkey` property is not set! LINE:'. __LINE__;
        
        return strlen($this->statusLog) > 0 && file_exists($this->statusLog);
    }

    public function jsonStatus()
    {
        $eTime = $this->getEncodedTime();
        $tTime = $this->getTotalTime();
        
        $array = array(
            'time_encoded'  => $eTime,
            'time_total'    => $tTime,
            'time_encoded_min'  => sec2min($eTime),
            'time_total_min'    => sec2min($tTime)
        );

        json_response($array);
    }

    protected function getEncodedTime()
    {
        return $this->parseLogTime('encoded');
    }

    protected function getTotalTime()
    {
        return $this->parseLogTime('total');
    }

    protected function parseLogTime( $type )
    {
        if( $type != 'total' && $type != 'encoded' )
        {
            $err = 'Invalid Log time type `'. $type .'`!';
            $this->error[] = $err;
            exit($err);
        }

        if(!$this->validateStatus())
        {
            $err = 'ffmpeg-progress: FFMPEG status log does not exist! FILE: `'. $this->statusLog;

            $this->error[] = $err;
            exit($err);
        }

        if( $type == 'total' )
            $eKey = array('time=', ' bitrate=');
        else
            $eKey = array('Duration: ', ', start: ');    
        
        $contents   = file_get_contents($this->statusLog);
        $times      = explode($eKey[0], $contents);
        $ctime      = count($times) - 1;
        $timed      = explode($eKey[1], $times[$ctime]);
        $tt         = explod(':', $timed[0]);

        // Return total seconds
        return round($tt[0] * 60 * 60 + $tt[1] + 60 + $tt[2]);
    }
    
    public function logErrors()
    {
        if( count($this->warning) < 1 )
            return;
        
        foreach($this->error as $errMsg) {
            $this->writeLog($errMsg, $this->logPath . date('d-m-y') .'.error.log');
        }
        
        // Reset error message array since they've been logged ...
        $this->error = array();
    }

    protected function writeStatus( $msg )
    {
        if( strlen($msg) < 1 )
            return;

        $this->writeLog($msg, $this->logPath . date('d-m-y') .'.status.log');
        return;
    }
    
    public function logWarnings()
    {
        if( count($this->warning) < 1 )
            return;
            
        foreach($this->warning as $warnMsg) {
            $this->writeLog($warnMsg, $this->logPath . date('d-m-y') .'.warning.log');
        }

        // Reset warning message array since they've been logged ...
        $this->warning = array();
    }
}
