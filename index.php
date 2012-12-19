<?php
require 'config.php';
require 'functions.php';
?><!DOCTYPE html>
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<title>FFMPEG via PHP</title>
<meta name="description" content="" />
<meta name="author" content="" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="./js/jquery.percentageloader-0.1.js"></script>
<script src="./js/jquery.timer.js"></script>
<script>jsNS = {'base_url' : '<?php echo BASE_URL; ?>', 'post_url' : '<?php echo POST_URL; ?>'}</script>
<link rel="stylesheet" href="style.css" />
<script src="./js/scripts.js"></script>
</head>
<body>
    <div id="header-container">
        <header class="wrapper">
            <h1 id="title">FFMPEG via PHP</h1>
        </header>
    </div>
    <div id="main" class="wrapper">
        <!-- Progress Bar -->
        <div id="progress"></div>
        <ul id="source_videos">
        <?php foreach(_source_files() as $file) { ?>
            <li><button data-filename="<?php echo $file; ?>" data-fkey="<?php echo hash('crc32', time() . $file, false); ?>">Convert It!</button> - <?php echo $file; ?> </li>
        <?php } ?>
        </ul>
        <p>
            <label>FFmpeg Params:</label>
            <textarea id="ffmpeg_params" rows="3" cols="120"><?php echo '-acodec libvo_aacenc -ac 2 -ab 128 -ar 22050 -s 1024x768 -vcodec libx264 -fpre "C:\ffmpeg\presets\libx264-ipod640.ffpreset" -b 1200k -f mp4 -threads 0'; ?></textarea>
        </p>
    </div>
</body>
</html><!--

sample commands ..

c:\ffmpeg\bin\ffmpeg.exe -i "C:\ffmpeg\FILE0055.MOV" -acodec libvo_aacenc -ac 2
-ab 128 -ar 22050 -s 1024x768 -vcodec libx264 -fpre "C:\ffmpeg\presets\libx264-i
pod640.ffpreset" -b 1200k -f mp4 -threads 0 FILE0055.mp4

c:\ffmpeg\bin\ffmpeg.exe -i "C:\ffmpeg\FILE0055.MOV" -fpre "C:\ffmpeg\presets\li
bx264-ipad.ffpreset" FILE0055.mp4

c:\ffmpeg\bin\ffmpeg.exe -i "C:\ffmpeg\GOD AT THE MOVIES - WEEK 1 - I LOST JESUS
.mp4" -vn -ar 44100 -ac 2 -f mp3 -ab 128000 "GOD AT THE MOVIES - WEEK 1 - I LOST
 JESUS.mp3" -->
