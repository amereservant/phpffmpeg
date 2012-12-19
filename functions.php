<?php
defined('SOURCE_PATH') || die('SOURCE_PATH not defined!');

/**
 * Get Directory Files
 *
 * Scans a given directory and returns an array containing any files in that directory.
 *
 * @param   string  $path   The full path of the directory we want the files of
 * @return  array           Either an empty array (if no files exist) or an array of files
 * @since   0.1
 */
function _get_files( $path )
{
    $cont = scandir($path);
    $files = array();
    
    foreach($cont as $item)
    {
        // Skip directories ...
        if( $item == '.' || $item == '..' || is_dir($path . $item) )
            continue;
        $files[] = $item;
    }
    return $files;
}

/**
 * Get Source Files
 *
 * Retrieves all files in the {@link SOURCE_PATH} directory, defined in config.php.
 *
 * @param   void
 * @return  array
 * @since   0.1
 */
function _source_files()
{
    return _get_files(SOURCE_PATH);
}

/**
 * Get Converted Files
 *
 * Retrieves all files in the {@link OUTPUT_PATH} directory, defined in config.php.
 *
 * @param   void
 * @return  array
 * @since   0.1
 */
function _converted_files()
{
    return _get_files(OUTPUT_PATH);
}

/**
 * JSON-encoded response
 *
 * Outputs a JSON-encoded array of data and sends the correct headers as well.
 * It will send an Error 500 if the second parameter is set to true, otherwise
 * it sends 200 OK header.
 *
 * This function also haults the script, preventing any additional output that might
 * cause errors.
 *
 * @param   mixed   $data   Data can be a string, integer, or an array to be encoded.
 * @param   bool    $error  Whether or not the response is an error or not.
 * @return  void
 * @since   0.1
 */
function json_response( $data, $error=false )
{
    if( $error )
        header('HTTP/1.1 500 JSON Error');
    else
        header('HTTP/1.1 200 OK');

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1970 00:00:00 GMT');
    header('Content-type: application/json');
    
    // Convert strings/integers into an array before outputting data...
    if(!is_array($data))
        echo json_encode(array($data), true);
    else
        echo json_encode($data, true);
    exit;
}

/**
 * Check $_POST Variable
 *
 * This checks to see if the given array key is set in the $_POST array and if not,
 * it returns the given default value.
 *
 * @param   string  $key        The key to check the $_POST array for
 * @param   mixed   $default    A default value to use if it's not set
 * @return  mixed
 * @since   0.1
 */
function _chkVal($key, $default=false) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

/**
 * Convert Seconds to Minutes
 *
 * Converts given seconds into HH:MM:SS format
 *
 * @param   integer $sec    The number of seconds to convert
 * @return  string          HH:MM:SS string
 * @since   0.1
 */
function sec2min( $sec )
{
    return sprintf('%02d:%02d:%02d', floor($sec/3600), floor(($sec/60)%60), $sec%60);
}

/**
 * FFMPEG Convert Video
 *
 * This is a class used to process requests for converting videos via FFMPEG,
 * although at this time it doesn't actually execute the command.
 *
 * It handles status logging, error logging, etc.
 *
 * @author      Amereservant <david@amereservant.com>
 * @copyright   2012 Amereservant
 * @license     http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @version     0.1
 * @link        http://myownhomeserver.com
 * @since       0.1
 */
class ffmpegConvert
{
   /**
    * File Key (Used mainly to identify the file log)
    *
    * This should only be set via the {@link _setFkey()} method!
    *
    * @var      string
    * @access   protected
    * @since    0.1
    */
    protected $fkey;

   /**
    * Log File(s) Path
    *
    * @var      string
    * @access   protected
    * @since    0.1
    */
    protected $logPath = '';

   /**
    * Errors
    *
    * @var      array
    * @access   protected
    * @since    0.1
    */
    protected $error = array();

   /**
    * FFMPEG Execution Path (includes directory/ffmpeg.exe)
    *
    * @var      string
    * @access   protected
    * @since    0.1
    */
    protected $ffmpegPath = '';

   /**
    * Progress Log (stores ffmpeg progress data only!)
    *
    * This is the file the output from ffmpeg.exe will be written to.
    * Nothing else should be written to this file or it can cause parse errors.
    *
    * This is set by the {@link _setProgressLogFkey()} method.
    *
    * @var      string
    * @access   protected
    * @since    0.1
    */
    protected $progressLog = '';

   /**
    * Constructor
    *
    * The {@link $fkey} can be set via this parameter (it's recommended).
    * This method sets the paths from CONSTANTS defined in config.php and registers
    * shutdown functions to automatically write log errors when
    * the script ends.
    *
    * @param    string  $fkey   The file key to identify the log/file by.
    * @return   void
    * @access   public
    * @since    0.1
    */
    public function __construct($fkey='')
    {
        $this->logPath      = LOG_PATH;
        $this->ffmpegPath   = FFMPEG_PATH;
        $this->_setFkey($fkey);
        
        register_shutdown_function(array(&$this, 'logErrors'));
    }

   /**
    * Set Fkey
    *
    * Sets the {@link $fkey} property and calls the {@link _setStatusLogFkey()} method
    * to set the Status Log file.
    * 
    * This method should be the ONLY way the {@link $fkey} property is set!
    *
    * @param    string  $fkey   The file key to assign to the {@link $fkey} property.
    * @return   void
    * @access   private
    * @since    0.1
    */
    private function _setFkey($fkey)
    {
        $this->fkey = $fkey;
        $this->_setProgressLog();
    }

   /**
    * Set Progress Log File
    *
    * Set's the {@link $progressLog} property based on the current {@link $fkey} value.
    *
    * @param    void
    * @return   void
    * @access   private
    * @since    0.1
    */
    private function _setProgressLog()
    {
        $this->progressLog = $this->fkey .'.ffmpeg.log';
    }

   /**
    * Execute FFMPEG Conversion
    *
    * This checks the input data for valid values, then via POST to the execution URL
    * (Defined as EXEC_URL in config.php), it initiates the ffmpeg command and begins
    * the video conversion.
    *
    * The script located at EXEC_URL actually calls PHP's exec() function...
    *
    * @param    string  $inFile     The input filename (without path)
    * @param    string  $outFile    The output filename (without path)
    * @param    string  $params     The ffmpeg parameters to use for converting the video
    * @return   void
    * @access   public
    * @since    0.1
    */
    public function exec( $inFile, $outFile, $params )
    {
        // Set an fkey if it hasn't already been set.  This probably should be removed...
        if( strlen($this->fkey) < 1 ) {
            $fkey = hash('crc32', time() . $inFile, false);
            $this->_setFkey($fkey);
        }

        // Verify the ffmpeg path is valid
        if( strlen($this->ffmpegPath) < 1 || !file_exists($this->ffmpegPath) )
            $this->error[] = 'Invalid FFMPEG Path `'. $this->ffmpegPath .'`!';

        // Verify the source file exists
        if( strlen($inFile) < 1 || !file_exists(SOURCE_PATH . $inFile) )
            $this->error[] = 'Invalid input file `'. $inFile .'`!';

        // Verify the output filename has been given
        if( strlen($outFile) < 1 )
            $this->error[] = 'Invalid output file!';

        // Verify the conversion parameters have been given
        if( strlen($params) < 1 )
            $this->error[] = 'No parameters were given!  Please specify conversion parameters.';

        // Check if there are any errors and stop if so...
        if( count($this->error) > 0 )
        {
            $this->logErrors();
            return false;
        }

        // Write status message updating us on where the script is at ...
        $this->writeStatus("Sending FFMPEG exec command to ". EXEC_URL ." ...");
        
        $cmd    = ' -i "'. SOURCE_PATH . $inFile .'" '. $params .' "'. OUTPUT_PATH . $outFile .'" 2> '. $this->logPath . $this->progressLog;

        // Write the execution command to the status log
        $this->writeStatus($cmd);

        $data   = array(
            'cmd'   => $cmd,
            'ffmpegpw'  => FFMPEG_PW,
            'fkey'      => $this->fkey
        );
        
        // Form the POST data string
        $pdata  = http_build_query($data);

        //<< NOTE: cURL doesn't work for this.  >>//
        
        $fh     = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $errstr, 30);
        // POST the data to the execution script
        fputs($fh, 'POST '. EXEC_URL ." HTTP/1.1\n");
        fputs($fh, 'Host: '. $_SERVER['HTTP_HOST'] ."\n");
        fputs($fh, "Content-type: application/x-www-form-urlencoded\n");
        fputs($fh, "Content-length: ". strlen($pdata) ."\n");
        fputs($fh, "User-agent: FFmpeg PHP Progress script\n");
        fputs($fh, "Connection: close\n\n");
        fputs($fh, $pdata);
        fclose($fh);
        return;
        
    }

   /**
    * Validate Progress
    *
    * This validates that the progress file DOES exist and the {@link $fkey} has
    * been set.
    *
    * @param    void
    * @return   bool        true if it does exist, false if not or fkey not set
    * @access   protected
    * @since    0.1
    */
    protected function validateProgress()
    {
        if( strlen($this->fkey) < 1 )
            $this->addError('The `$fkey` property is not set! LINE:'. __LINE__);
        
        return strlen($this->progressLog) > 0 && file_exists($this->logPath . $this->progressLog);
    }

   /**
    * JSON-encoded Status
    *
    * Retrieves the current encoding time and total time, then outputs the data
    * in a JSON-encoded array.
    *
    * This is used when polling for progress updates.
    *
    * @param    void
    * @return   void
    * @access   public
    * @since    0.1
    */
    public function jsonStatus()
    {
        // Get the current Encoded time
        $eTime = $this->getEncodedTime();
        // Get the total Length time
        $tTime = $this->getTotalTime();
        
        $array = array(
            'time_encoded'  => $eTime,
            'time_total'    => $tTime,
            'time_encoded_min'  => sec2min($eTime),
            'time_total_min'    => sec2min($tTime)
        );

        json_response($array);
    }

   /**
    * Get Encoded Time
    *
    * Sort of like "elapsed" time, this retrieves the number of seconds that have
    * been processed of the video so far.
    * This gets called by the {@link jsonStatus()} method.
    *
    * @param    void
    * @return   integer     Number of encoded seconds of the video
    * @access   protected
    * @since    0.1
    */
    protected function getEncodedTime()
    {
        return $this->parseLogTime('encoded');
    }

   /**
    * Get Total Time
    *
    * Retrieves the total number of seconds of the video's length.
    * This gets called by the {@link jsonStatus()} method.
    *
    * @param    void
    * @return   integer     Number of total seconds of the video
    * @access   protected
    * @since    0.1
    */
    protected function getTotalTime()
    {
        return $this->parseLogTime('total');
    }

   /**
    * Parse Log Time
    *
    * Parses the {@link $progressLog} time and returns the requested type of seconds.
    *
    * @param    string  $type   Either 'total' or 'encoded'
    * @return   integer         Number of seconds for requested value
    * @access   protected
    * @since    0.1
    */
    protected function parseLogTime( $type )
    {
        // Make sure a valid type is being requested
        if( $type != 'total' && $type != 'encoded' )
        {
            $err = 'Invalid Log time type `'. $type .'`!';
            $this->addError($err);
            exit($err);
        }

        // Validate the progress file
        if(!$this->validateProgress())
        {
            $err = 'ffmpeg-progress: FFMPEG progress log does not exist! FILE: `'. 
                $this->logPath . $this->progressLog .'`';

            $this->addError($err);
            exit($err);
        }

        // Determine the correct set of separation values
        if( $type == 'encoded' )
            $eKey = array('time=', ' bitrate=');
        else
            $eKey = array('Duration: ', ', start: ');    

        // Open and parse the log file
        $contents   = file_get_contents($this->logPath . $this->progressLog);
        $times      = explode($eKey[0], $contents);
        $ctime      = count($times) - 1;
        $timed      = explode($eKey[1], $times[$ctime]);
        $tt         = explode(':', $timed[0]);
        
        // Calculate total seconds ... cannot do $tt[0] * 3600 + $tt[1] * 60 + $tt[2]
        // since this was returning invalid values...
        $hsec  = $tt[0] * 3600;
        $msec  = $tt[1] * 60;
        $sec   = $tt[2];
        $ttsec = $hsec + $msec + $sec;

        // Return rounded seconds
        return round($ttsec);
    }

   /**
    * Log Errors
    *
    * Writes any current errors in the {@link $errors} property to the log and clears
    * the {@link $errors} property so duplicates won't be written.
    *
    * @param    void
    * @return   void
    * @access   public
    * @since    0.1
    */
    public function logErrors()
    {
        foreach($this->error as $errMsg) {
            $this->writeLog($errMsg, date('d-m-y') .'.error.log');
        }
        
        // Reset error message array since they've been logged ...
        $this->error = array();
    }

   /**
    * Write Status Message to Log
    *
    * Writes informative messages to a status log (NOT the progress log) that may
    * be helpful for debugging purposes.
    *
    * @param    string  The status message to write.
    * @return   void
    * @access   public
    * @since    0.1
    */
    public function writeStatus( $msg )
    {
        if( strlen($msg) < 1 )
            return;

        $this->writeLog($msg, date('d-m-y') .'.status.log');
        return;
    }

   /**
    * Add Error Message
    *
    * Adds an error message to the {@link $error} property so it will be added to
    * the error log when it is written.
    *
    * @param    string  $msg    The error message to add
    * @return   void
    * @access   public
    * @since    0.1
    */
    public function addError( $msg )
    {
        $this->error[] = $msg;
        $this->logErrors();
    }

   /**
    * Write Log Data
    *
    * Used to write the log data based on given params.
    * It's responsible for formatting the log file entry and then writing it to
    * the logfile specified by the $file parameter.
    *
    * @param    string  $msg    The message to write to the log file.
    * @param    string  $file   The log file's filename to write given message to.
    *                           This should NOT contain the path, just filename only.
    * @return   void
    * @access   protected
    * @since    0.1
    */
    protected function writeLog($msg, $file)
    {
        // Log file entry format
        $logf = '['. date('d.m.y H:i:s') .'] ['. $_SERVER['REMOTE_ADDR'] ."] %s \n";

        // If the log file wasn't specified, append a log entry alerting us of it ...
        if( strlen($file) < 4 )
        {
            $filename       = $this->logPath . date('d-m-y') .'.error.log';
            $this->addError('writeLog() called without valid $file parameter! '.
                'LINE:'. __LINE__ .' FILE: '. __FILE__);
        }
        else
        {
            $filename = $this->logPath . $file;
        }

        $logStr       = sprintf($logf, $msg);

        // Write the log file data
        $hdl = fopen($filename, 'a+');
        fwrite($hdl, $logStr);
        fclose($hdl);
        return;
    }
}
