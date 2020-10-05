<?php
/*
************************************************
** API für Fremdsoftware Connector
** by Thomas Ziegler, Frozen-Media.de

*************************************************************************************************
getCategories --> Alle Kategorien ausgeben
Inputparameter:
    keine
Export:
    JSON Array meta(list)+result(list)
*************************************************************************************************
getErrorcodes --> Alle Errorcodes zu einer bestimmten Kategorie ausgeben
Inputparameter:
    cat(int): ID der Kategorie (Gerät) zu welcher alle Errorcodes angezeigt werden sollen
Export:
    JSON Array meta(list)+result(list)
*************************************************************************************************
getSpareparts --> Alle Ersatzteillisten zu einer bestimmten Kategorie ausgeben
Inputparameter:
    cat(int): ID der Kategorie (Gerät) zu welcher alle Errorcodes angezeigt werden sollen
Export:
    JSON Array meta(list)+result(list)
*************************************************************************************************
getPing --> Simple Ping Funktion mit einfacher Rückmeldung zum Check ob alles ok ist
Inputparameter:
    keine
Export:
    JSON Array meta(list) --> error=000 / rows=1 / message=1 

*/

header('Content-Type: application/json');

define ( 'dbHOST',              'dedi1912.your-server.de' );
define ( 'dbUSER',              'stoeru_3' );
define ( 'dbPASSWORD',          'F8v7NfUr6A5PCT6n' );
define ( 'dbDATABASE',          'sc_knowledge_dev' );

include("inc_HashPassword.php");


// Elementare Parameter für Userauthentifizierung
if (isset($_POST["user"]))    { $PAR_EMAIL        = $_POST["user"];   } else { $PAR_EMAIL = "";         }
if (isset($_POST["pass"]))    { $PAR_PASSWD       = $_POST["pass"];   } else { $PAR_PASSWD = "";        }

// action Parameter welche Funktion aufgerufen werden soll
if (isset($_POST["action"]))  { $API_ACTION       = $_POST["action"];  } else { $API_ACTION = "";       }

// optionale Parameter für verschiedenen Funktionen
if (isset($_POST["cat"]))     { $PAR_CAT          = $_POST["cat"];   } else { $PAR_CAT = "";           }


if ($PAR_EMAIL == "" OR $PAR_PASSWD == "") {
    exit();    
}


if ( $API_ACTION == "" ) { 
    $JSONResponse["meta"] = array("error" => "998", "rows" => 0, "message" => "nothing to do if you dont tell what to do :)");
    echo json_encode($JSONResponse); 
    exit();
    }

if ( !checkLogin($PAR_EMAIL, $PAR_PASSWD) ) {
    $JSONResponse["meta"] = array("error" => "999", "rows" => 0, "message" => "you are not allowed");
    echo json_encode($JSONResponse); 
    exit();
    }

            
ob_start("ob_gzhandler");

switch ($API_ACTION) {
    case "getCategories":                    
        echo getCategories();
        break;
    case "getErrorcodes":
        echo getErrorcodes($PAR_CAT);
        break;  
    case "getSpareparts":
        echo getSpareparts($PAR_CAT);
        break;             
    case "getPing";
        echo getPing();
        break;        
}
ob_end_flush();

exit();


// Login prüfen
function checkLogin($PAR_EMAIL, $PAR_PASSWD) {
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query ="SELECT id, username, password, first_name, last_name, active FROM kbp_user WHERE username like '" . $PAR_EMAIL ."'";        
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_array($result);
    
    $login = true;
    
    if ($row["active"] != '1') { $login = false; }
    
    $password_escaped = addslashes(stripslashes($PAR_PASSWD));
    $login = HashPassword::validate($password_escaped, $row["password"]);  // vergleich von PW wie in knowledgeDB
    
    if (strtolower($row["username"]) != strtolower($PAR_EMAIL)) { $login = false; }
    
    if ($login) {
        
        $user_id        = $row["id"];
        $date_login     = strtotime("now");
        $login_type     = "5";
        $user_ip        = ip2long($_SERVER["REMOTE_ADDR"]);
        $username       = $PAR_EMAIL;
        $output         = "API-CONNECT". ";". htmlentities($_SERVER['HTTP_USER_AGENT']);
        $exitcode       = 1;
        $active         = 1;
        
        $query ="INSERT INTO kbp_log_login (user_id, login_type, user_ip, username, output, exitcode, active) VALUES ('$user_id','$login_type','$user_ip','$username','$output','$exitcode','$active')";
        $result = mysqli_query($connection, $query);
    }
    return $login;
}


/// CATEGORY EXPORT
function getCategories() { 
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "select * from kbp_kb_category ".
                "WHERE active = 1 AND name not like '00_Zugangs%'";
    
    $result = mysqli_query($connection, $query);
    
    $JSONResponse = array("result" => array()); 

    while($row = mysqli_fetch_array($result)) {

        $JSONRow = array('id'            => $row["id"], 
                         'label'         => utf8_encode($row["name"]), 
                         'parent_id'     => $row["parent_id"],
                         'sort_order'    => $row["sort_order"]);
                            
        array_push($JSONResponse["result"], $JSONRow); 
    }

    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");  
       return json_encode($JSONResponse);         
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No Categories found!");
       return json_encode($JSONResponse);        
    }  
}



/// ENTRY EXPORT 
function getErrorcodes($PAR_CAT) { 
    
    if ($PAR_CAT == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "No category set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
             
    $query = "SELECT a.category_id, b.id, b.title, b.body, b.sort_order  FROM kbp_kb_entry_to_category a ".
             "INNER JOIN kbp_kb_entry b ON a.entry_id = b.id ".
             "INNER JOIN kbp_kb_custom_data c on b.id = c.entry_id ".
             "WHERE b.private = 0 AND b.active = 1 AND c.data = 1 AND c.field_id = 2 ".
             "AND a.category_id = ".$PAR_CAT;
             
    $query = $query." ORDER BY id ";
                    
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    

    while($row = mysqli_fetch_array($result)) {
        
        $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
        
        $JSONRow = array('id'             => $row["id"], 
                         'label'          => utf8_encode($row["title"]),
                         'content'        => stripslashes(utf8_encode($row["body"])),
                         'sort_order'     => $row["sort_order"]);     
        array_push($JSONResponse["result"], $JSONRow);   
    }

  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}

// Ersatzteillisten exportieren - 9
function getSpareparts($PAR_CAT) { 
    
    if ($PAR_CAT == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "No category set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
             
    $query = "SELECT a.category_id, b.id, b.title, b.body, b.sort_order  FROM kbp_kb_entry_to_category a ".
             "INNER JOIN kbp_kb_entry b ON a.entry_id = b.id ".
             "INNER JOIN kbp_kb_custom_data c on b.id = c.entry_id ".
             "WHERE b.private = 0 AND b.active = 1 AND c.data = 9 AND c.field_id = 2 ".
             "AND a.category_id = ".$PAR_CAT;
             
    $query = $query." ORDER BY id ";             
                 
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
        
    while($row = mysqli_fetch_array($result)) {
        
        $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
        
        $JSONRow = array('id'             => $row["id"], 
                         'label'          => utf8_encode($row["title"]),
                         'content'        => stripslashes(utf8_encode($row["body"])),
                         'sort_order'     => $row["sort_order"]);     
        array_push($JSONResponse["result"], $JSONRow);   
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}





// CHECK PING
function getPing() {
    
    $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "1");
    
    return json_encode($JSONResponse);
    
    
}

/////////////////////////////////////////////////////
/// Funktionen
/////////////////////////////////////////////////////

function parseImages($output, $baseUrl) {

    preg_match_all('/<img[^>]+>/i', $output, $match);

    if(!empty($match[0])) {
        
        $baseUrl = rtrim($baseUrl,"/"); // just in case 
        $initial_src = array();
        $new_src = array();
        
        foreach ($match[0] as $tag) {

            preg_match_all('/src="([^"]*)"/i', $tag, $image);

            $src = rawurldecode($image[1][0]);
            $initial_src[] = $src;
            $is_remote = (strpos($src, 'http://') !== false || strpos($src, 'https://') !== false);
            $is_embedded = (strpos($src, 'data:image') !== false);

            if($is_remote || $is_embedded) {
                $new_src[] = $src;
                continue;
            }

            $new_src[] = $baseUrl . $src;
        }
        
        $initial_src = array_unique($initial_src);
        $new_src = array_unique($new_src);
        $output = str_replace($initial_src, $new_src, $output);
    }

    return $output;

}

function imageToBase64 ( $file = NULL ) {

    if (file_exists ($file)) {
      $content = file_get_contents ( $file );
      return base64_encode( $content );
    } else {
      return "";
    }

    
} 

/**
 * reverse function for nl2br
 * @param $string - string with br's
 * @return string - string with \n
 */
function br2nl($string)
{
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

?>
