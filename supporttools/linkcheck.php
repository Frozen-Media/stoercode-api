<?php

//ini_set("max_execution_time", 300);

set_time_limit(0); 
error_reporting(E_ALL); 

define ( 'dbHOST',          'dedi1912.your-server.de' );
define ( 'dbUSER',          'stoeru_3' );
define ( 'dbPASSWORD',      'F8v7NfUr6A5PCT6n' );
define ( 'dbDATABASE',      'sc_knowledge_dev' );
define ( 'EMAILTARGET',     'info@stoercode.de');
//define ( 'EMAILTARGET',     'thomas.ziegler@frozen-media.de');


//if (!isset($_REQUEST["von"])) { die; } else { $von= $_REQUEST["von"];}
//if (!isset($_REQUEST["bis"])) { die; } else { $bis= $_REQUEST["bis"]; }

$von = 16800;
$bis = 17000;
$anz = $bis - $von;

// SSL Zertifikatfehler ignorieren
stream_context_set_default( [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);


$connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
$query ="SELECT id, LEFT(body,10000) as body FROM kbp_kb_entry ORDER BY id LIMIT ".$von.",".$anz;        
$result = mysqli_query($connection, $query);

$linklist = generateLinkErrorArray($result);

//echo ini_get('max_execution_time'); 

$mailtext = "<h2>LINKCHECK SupportJob Ergebnisliste</h2><br>";
$mailtext .= "Datensatz von: ".$von." bis ".$bis."</br>"; 
$mailtext .= "Geprüfte Datensätze: ".mysqli_num_rows($result)."</br>"; 
$mailtext .= "Gefundene Links: ".count($linklist)."</br>";
$mailtext .= "Fehlerhafe Links: ".countErrorLinks($linklist)."</br>"."</br>";

$mailtext .= "<table>";
$mailtext .= "<tr><td>ID</td><td>LINK</td><td>STATUS</td></tr>";

foreach ($linklist as $link) {
    if ($link["STATUS"] <> "OK") {    
        $mailtext .= "<tr><td>".$link["ID"]."</td>";
        $mailtext .= "<td>".$link["LINK"]."</td>";
        $mailtext .= "<td>".$link["STATUS"]."</tr>"; 
    }
}    

$mailtext .= "</table>";

//emailText($mailtext);

echo $mailtext;


mysqli_close($connection);



// ************ FUNCTIONS ******************

function generateLinkErrorArray($resultset) {
    $output = null;
    while($row = mysqli_fetch_array($resultset)) {
        $links = parseLinks($row["body"]);
        if ($links) { 
            foreach ($links as $link) {
                //if (validateRemoteUrl($link)) {$status = "ok";} else { $status="error";}
                $status = validateRemoteUrl($link);
                $output[] = array(  'ID'     => $row["id"], 
                                    'LINK'   => $link, 
                                    'STATUS' => $status);
            }    
        }
    }
    return $output;
}

// **********
function countErrorLinks($linklist) {
    $i = 0;
    foreach ($linklist as $link) {
        if ($link["STATUS"] <> "OK") { $i = $i + 1;}
    } 
    return $i;   
}

// **********
function parseLinks($body) {

    $output = null;
    preg_match_all('<a href=\x22http(.+?)\x22>', $body, $match);  //alle links mit http anfangend, damit auch https
        
    if(!empty($match[1])) {                
        foreach ($match[1] as $tag) {
            if (!strpos($tag,"Login/index.php?")) {  // nur diejenigen die nicht auf einen Eintrag verlinken weil der passwortgeschützt ist und nicht erreichebar.
               $output[] = "http".$tag;    //http wieder anbauen und
            }
            
                   
        }
        return $output;
    } else {
        return $output;
    }
}

// **********
function contains($str, $needle) {
  return (strpos($str, $needle) !== false);
}
// **********
function validateRemoteUrl($url) {
  $headers = get_headers($url);
  if (isset($headers) && count($headers) > 0 && contains($headers[0], "200")) {
      return "OK";
  } else {
      return "ERROR: ".$headers[0];
  }
  //return (isset($headers) && count($headers) > 0 && contains($headers[0], "200"));
}

// **********
function emailText($message) {
    
    $mime_boundary = "-----=" . md5(uniqid(microtime(), true));
    $encoding = mb_detect_encoding($message, "utf-8, iso-8859-1, cp-1252");
            
    $to = EMAILTARGET;
    $subject = "LINKCHECK SupportJob Errorliste";
    $frommail = "noreply@stoercode.de";
    $from = "LINKCHECK SupportJob";

    $headers  = 'From: "'.addslashes($from).'" <'.$frommail.">\r\n";
    $headers .= "Reply-To: ".$frommail."\r\n";  
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"";
    
    $content = "If you can see this MIME than your client doesn't accept MIME types!\r\n\r\n";
    $content.= "--".$mime_boundary."\r\n";

    $content .= "Content-Type: text/html; charset=\"$encoding\"\r\n";
    $content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $content .= $message."\r\n";

    $content .= "--".$mime_boundary."--"; 

    // Send email

    $success = mail($to,$subject,$content,$headers);
       if (!$success) {
           return "ERROR";
       }else {
            return "OK";
       }        
    
}


?>
