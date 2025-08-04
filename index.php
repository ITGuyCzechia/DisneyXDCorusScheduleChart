<?php 
include_once 'dbh.inc.php';

function printFinalResults($conn, $option){
    $sql_preliminary_most_often = "SELECT
  name,
  COUNT(name) AS `value_occurrence` 

FROM
  disneyxdsche3669.DisneyXDScheduleChart 

GROUP BY 
  name

ORDER BY 
  `value_occurrence` ";

    if(strcmp($option, "most") == 0){
        $sql_preliminary_most_often = $sql_preliminary_most_often."DESC LIMIT 10;";
        echo "<br><br><br><table><th colspan='2'>Most often at schedule:</th>";
    }else{
        $sql_preliminary_most_often = $sql_preliminary_most_often."ASC  LIMIT 16;";
        echo "<br><br><br><table><th colspan='2'>Least often at schedule:</th>";
    }
    $result_nabidk = mysqli_query($conn, $sql_preliminary_most_often);
    // For checking if program can use result, it will count number of them.
    // Zero means: "Noo, dont go to that array!"
    $resultCheck_nabidka = mysqli_num_rows($result_nabidk);
                        
    if($resultCheck_nabidka > 0){
         $i = 1;
         while($row = mysqli_fetch_assoc($result_nabidk)){
                $portal = $row['name'];
                echo '<tr><td>'.$i.'.</td><td>'.$portal.'</td><td><i>('.$row['value_occurrence'].')</i></td></tr>';
                $i++;
         }
         echo "</table>";
    }
}

?>

<html>
    <head>
        <title>Disney XD Schedule Chart</title>
    </head>
    <body>
        <h1>Disney XD Schedule Chart</h1>
        <h3>Canada</h3>

<?php
printFinalResults($conn, "most");
printFinalResults($conn, "least");
?>

  <br><a href="https://www.toplist.cz"><script language="JavaScript" type="text/javascript" charset="utf-8">
  <!--
document.write('<img src="https://toplist.cz/count.asp?id=1701110&http='+
encodeURIComponent(document.referrer)+'&t='+encodeURIComponent(document.title)+'&l='+encodeURIComponent(document.URL)+
'&wi='+encodeURIComponent(window.screen.width)+'&he='+encodeURIComponent(window.screen.height)+'&cd='+
encodeURIComponent(window.screen.colorDepth)+'" width="88" height="31" border=0 alt="TOPlist" />');
//--></script><noscript><img src="https://toplist.cz/count.asp?id=1701110&njs=1" border="0" alt="TOPlist" width="88" height="31" /></noscript></a>
 </body>
</html>
