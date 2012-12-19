(function($){
    var fkey, topLoader, startstopTimer, startstopCurrent = 0;;
    var fkeys=[];
    // Padding function
    function pad(number, length) {
	    var str = '' + number;
	    while (str.length < length) {str = '0' + str;}
	    return str;
    }

    $(function(){
        topLoader = $("#progress").percentageLoader({
            width           : 150, 
            height          : 150,
            controllable    : false,
            value           : '00:00:00',
            progress        : 0, 
            onProgressUpdate : function(val) {
                topLoader.setValue(Math.round(val * 100.0));
            }
        });
    });
    
    function convertVideo(el){
        var filename = el.data('filename');
            fkey     = el.data('fkey');
        var params   = $('#ffmpeg_params').val();
        fkeys.push(fkey);
        
        $.ajax(jsNS.post_url, {
            type    : 'POST',
            dataType : 'json',
            async   : false,
            data    : { 
                'filename'  : filename,
                'fkey'      : fkey,
                'type'      : 'convert',
                'params'    : params
            },
            success : function(data){
                // Delay start of polling so server can write status log file....
                startPolling(data);
                
                //console.log(jQuery.ajax.data);
            },
            error   : function(){
                alert('Request failed!');
            }
        });
    }

    function pollStatus(fkey){ // Delete tmpTime!!!
        var statusData;
        
        $.ajax(jsNS.post_url, {
            type    : 'POST',
            dataType : 'json',
            async   : false,
            data    : { 'fkey' : fkey, 'type' : 'status' },
            success : function(data){
                statusData = data;
            },
            error   : function(){
                alert('Polling failed!');
                statusData = false;
            }
        });
        return statusData;
    }
    
    function startPolling(data){
        var currentTime, totalTime, hrCurrentTime, hrTotalTime, statData, intPoll, timer, count;
        count = 0;

        currentTime = data.time_encoded;
        totalTime   = data.time_total;
        
        timer = $.timer(function() {
		    var min     = parseInt(startstopCurrent/6000);
		    var sec     = parseInt(startstopCurrent/100)-(min*60);
		    var micro   = pad(startstopCurrent-(sec*100)-(min*6000),2);
		    var output  = "00"; if(min > 0) {output = pad(min,2);}
		    topLoader.setValue(output+":"+pad(sec,2)+":"+micro);
		    startstopCurrent+=7;
	    }, 70, true);
        timer.play();
        intPoll = setInterval(function(){
            if( currentTime < totalTime ) {
                statData = pollStatus(fkey, currentTime);
                //console.log(statData);
                if( !statData ){
                    console.log(fkey);
                    alert('Bad data!');
                    console.log(statData);
                    clearInterval(intPoll);
                    return false;
                }
                currentTime = statData.time_encoded;
                totalTime   = statData.time_total;
                hrCurrentTime = statData.time_encoded_min;
                hrTotalTime   = statData.time_total_min;
                
                topLoader.setProgress(currentTime / totalTime);
                
                //topLoader.setValue(hrCurrentTime);
                
            }
            else {
                timer.stop();
                alert('Finished!');
                clearInterval(intPoll);
            }
        },1000);   
    }

    $(document).ready(function(){
        $('#source_videos button').click(function(){
            convertVideo($(this));
        });
    });
})(jQuery);
