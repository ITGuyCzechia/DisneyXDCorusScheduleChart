<?php 
include_once 'dbh.inc.php';

$year = 2025;

function takePartBetween($from, $start, $end){
    $FirstCut = explode($start, $from);
    if(count($FirstCut) < 2){ // If start was not at string
        return "";
    }else{
        $OtherCut = explode($end, $FirstCut[1]);
        return $OtherCut[0];
    }
}

function loadSchedule($link){
    $context = stream_context_create(
        array(
            "http" => array(
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
             )
        )
    );

    $schedule = file_get_contents($link, false, $context);
    if($result === false){
        echo "<span style='color: red'>Mistake accured while loading schedule</span> from link <a href='".$link."'></a>.";
    }else{
        return $schedule;
    }
}

function putToDatabase($conn, $show, $episode, $date, $time, $duration){
    $myEpisode      = str_replace("'", "", $episode);
    $myShowName     = str_replace("'", "", $show);
    
    $select_sql = "SELECT date FROM disneyxdsche3669.DisneyXDScheduleChart 
    WHERE `name`='".$myShowName."' and `episode`='".$myEpisode."'
    and `date`='".$date."' and `time`='".$time."';";

    // If item is not in database, it will add new item.
    $select_result = mysqli_query($conn, $select_sql);
    $resultCheck = mysqli_num_rows($select_result);
    
    if($resultCheck == 0){
        $sql = "INSERT INTO disneyxdsche3669.DisneyXDScheduleChart (`name`, `episode`, `date`, `time`, `duration`) 
        VALUES ('".$myShowName."','".$myEpisode."','".$date."','".$time."','".$duration."');";

        $errorstatus = mysqli_query($conn, $sql);
        if(!$errorstatus){
            echo "Error: Page couldn't upload data to database. <br>";
            global $errorreached;
            $errorreached = $errorreached + 1;
        }
    }else{
         echo "Item was already in database. Item not inserted.<br>";
    }
}

function gapTime($datetime_1, $datetime_2){
    $start_datetime = new DateTime($datetime_1); 
    $diff = $start_datetime->diff(new DateTime($datetime_2)); 
    return $diff->i + ($diff->h*60); // "04 Aug 2025 05:35:08 am"
}

function countDuration($day, $year, $startTime, $endTime){
    $datetime_1 = $day." ".$year." ".$startTime;
    $datetime_2 = $day." ".$year." ".$endTime;
    
    // If endTime is from next day:
    if(str_contains($startTime, "pm") && str_contains($endTime, "am")){
        $datetime = new DateTime($day." ".$year);
        $datetime->modify('+1 day');
        $datetime_2 = $datetime->format('Y-m-d')." ".$endTime;
    }
    
    $result = gapTime($datetime_1, $datetime_2);
    return $result; // In minutes.
}

function takeStartTime($airing){
    return str_replace('</span><span class="amPm">', "", takePartBetween($airing, '<div class="fullSchedule-episodeStartTime">
									<span class="hourMinute">', '</span>
								</div>'));
}

function takeEndTime($airing){
    return str_replace('</span><span class="amPm">', "", takePartBetween($airing, '<div class="fullSchedule-episodeEndTime">
									<span class="hourMinute">', '</span>
								</div>'));
}

function takeShowName($airing){
    return takePartBetween($airing, '<div class="fullSchedule-episodeShowName">', '</div>');
}

function takeEpisodeName($airing){
    return takePartBetween($airing, '<div class="fullSchedule-episodeName">', '</div>');
}

function findDates($page){
    $days_result = array();
    $Section = explode('<div id="fullSchedule-navigationContainer" class="fullSchedule-navigationContainer">', $page);
    $daysSections = explode('<div class="fullSchedule-nav-date">', $Section[1]);
    array_shift($daysSections); // Delete first item without date 
    foreach($daysSections as $dayBox){
        $dayAndRest = explode("</div>", $dayBox);
        array_push($days_result, $dayAndRest[0]);
    }
    return $days_result;
}

function deleteWhitespace($string){
    return preg_replace('/\s+/', '', $string);
}

function convertDateToCzech($day, $year){
    $sections = explode(" ", $day);
    $Months = ["Jan" => 1, "Feb" => 2, "Mar" => 3, "Apr" => 4, "May" => 5, "Jun" => 6, 
    "Jul" => 7, "Aug" => 8, "Sep" => 9, "Oct" => 10, "Dec" => 11, "Nov" => 11, "Dec" => 12];
    $month = deleteWhitespace(strtr($sections[0], $Months));
    $day = deleteWhitespace($sections[1]);
    return $year."-".$month."-".$day;
}

function convertDateToCanada($day){
    $sections = explode(" ", $day);
    $Months = ["Jan" => 1, "Feb" => 2, "Mar" => 3, "Apr" => 4, "May" => 5, "Jun" => 6, 
    "Jul" => 7, "Aug" => 8, "Sep" => 9, "Oct" => 10, "Dec" => 11, "Nov" => 11, "Dec" => 12];
    $month = deleteWhitespace($sections[0]);
    $day = deleteWhitespace($sections[1]);
    return $month." ".$day;
}

$day    = date("d");
$duration = null;
$URLlink = "https://www.disneyxd.ca/schedule/";
echo "<a href='".$URLlink."'> ".$day."</a><br>";
$schedule = loadSchedule($URLlink);


// Take important part with schedule
$ScheduleArray = explode('<div id="fullSchedule-tableContainer" class="fullSchedule-tableContainer">', $schedule);
$CorusWebpageScheduleFooter = $ScheduleArray[1];
$datesAtSchedule = findDates($schedule);
print_r($datesAtSchedule);

// Split webpage to section of days
$SectionsOfDays = explode('<div class="fullSchedule-day', $CorusWebpageScheduleFooter);
array_shift($SectionsOfDays);
foreach($SectionsOfDays as $key=>$Day){
    // Split days to section of airings
    $date = convertDateToCzech($datesAtSchedule[$key], $year);
    echo "<br><b>".$date."</b><br>";
    $SectionsOfAirings = explode('<div class="fullSchedule-episode fullSchedule-episode--', $Day);
    foreach($SectionsOfAirings as $Airing){
        $name       = takeShowName($Airing);
        if(empty($name)){
            echo "<br>Name is empty, item was skipped. <br>";
            continue; // Empty items are skipped
        }
        $startTime  = takeStartTime($Airing);
        $endTime    = takeEndTime($Airing);
        $episode    = takeEpisodeName($Airing);
        $duration   = countDuration(convertDateToCanada($datesAtSchedule[$key]), 
                                    $year, $startTime, $endTime);

        echo "<br><b>".$name."</b>: (".$episode.") <br>".$startTime." - ".$endTime." (".$duration." min)<br>";
        putToDatabase($conn, $name, $episode, $date, $startTime, $duration);
    }
}


echo "<br>";
echo "End of page";

?>
</body>
</html>
