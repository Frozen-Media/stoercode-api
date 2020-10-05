<?php
/*
************************************************
** API für Native Mobile App V1.5.0
** by Thomas Ziegler, Frozen-Media.de
************************************************
Verschiedene Funktionen zur Datenbereitstellung
können mit dem POST Parameter "action" abgefragt
werden.
************************************************
- getNavigationElements
  Alle aktiven Kategorien werden als JSON exportiert
  Rückgabe: id, label, parent_id, icon, sort_order 
  
- getCategoryPictures
  liefert UNIQUE(nicht doppelt) alle Kategorienbilder als TXT und Base64 aus
  die in aktiven Kategorien verwendet werden.
  Rückgabe: icon, base64
  
- getNavigationToErrorcode
  Zurordnungstabelle für Entrys zu Categorien
  Es werden nur Sätze zu aktiven Kategorien exportiert als JSON
  Rückgabe: entry_id, category_id, sort_order 
  
- getErrorcodeEnc
  zusätzlicher POST Parameter möglich "since=YYYY-MM-DD"
  Alle Entries (Codes) werden exportiert als JSON die nach dem
  mit "since" angegebenen Datum geändert wurden. Mit "until" kann
  zusätzlich noch ein begrenzender Bereich für bis Datum angegeben werden.
  Wird nichts angegeben, werden alle aktiven exportiert.
  limit; offset als optionale parameter hinzugefügt - entspechen SQL LIMIT und OFFSET
  Rückgabe: id, label, content, sort_order 
  
- getZugangscodesEnc
  Zugangscodes analog getErrorcodeEnc
  kbp_kb_custom_data.data = 7
  
- getTelefoneEnc 
  Telefonnummern analog getErrorcodeEnc 
  kbp_kb_custom_data.data = 8
  
- getHerstellerhinweiseEnc
  Herstellerhinweise analog getErrorcodeEnc
  kbp_kb_custom_data.data = 13
  
- getAnleitungenEnc
  Anleitungen analog getErrorcodeEnc
  kbp_kb_custom_data.data = 6
    
- getDocumentsEnc
  Diverse Dokumentencodes analog getErrorcodeEnc
  kbp_kb_custom_data.data = 9,10,11,12,14,15,16,17,18,19   !! 9 Muss irgengwann noch raus!!

- getSparepartsEnc
  Ersatzteillisten analog get ErrorcodesEnc
  kbp_kb_custom_data.data = 9
  
- checkLogin
  Prüfung des Passworts.
  
- sendEMailToSupport
  Email an Support schicken  mit Anlagen
  
- getPing
  Liefert einen Status das die API Online ist. Wir z.B. für Feedback benutzt ob Handy Online ist.

- getCompanyLogo
  Liefert zur angegebenen Email (Login) das Company Logo.

*/

header('Content-Type: application/json');

define ( 'dbHOST',              'dedi1912.your-server.de' );
define ( 'dbUSER',              'stoeru_3' );
define ( 'dbPASSWORD',          'F8v7NfUr6A5PCT6n' );
define ( 'dbDATABASE',          'sc_knowledge_dev' );
define ( 'APIKEY' ,             '1872533ughsadlhnvb9231864kj2h34g23jh34h2jk334');  // API Key für Aufruf

define ( 'ENC_KEY' ,            '23adEjcNc8sjua!dzjk43g)j532_fdZh');   // http://blog.nikoroberts.com/post/45834708375/php-mcrypt-and-c-encryptor

//define ( 'PATH_CATPIC',         '../kb_icons/');  // Pfad zu Kategoriebildern/Icons z.B. ../kb_icons/
define ( 'PATH_CATPIC',         '../Login/cat_logo_app/');  // Pfad zu Kategoriebildern/Icons z.B. ../kb_icons/
define ( 'SUPPORTMAIL',         'tz@frozen-media.de');  //Email des Supports für Mailempfänger mit komma getrennt.

//define ( 'PATH_COMPANY_CSS',    '../Login/custom_company/css/');    // Pfad zu den Company Logo CSS Dateien
//define ( 'PATH_COMPANY_LOGO',   '../Login/custom_company/logos/');  // Pfad zu den Company Logo LOGO Dateien
define ( 'PATH_COMPANY_LOGO',   '../Login/custom_company/logos-app/');  // Pfad zu den Company Logo LOGO Dateien

define ( 'APIMODE', 'PROD'); // PROD, TEST -- Einstellungen für Produktiv und Testmodus (z.B. es wird nur POST verwendet)

include("inc_enc.php");
include("inc_HashPassword.php");

if ( APIMODE == "TEST" ) {

    if (isset($_REQUEST["key"]))     { $API_KEY         = $_REQUEST["key"];     } else { $API_KEY = "";          } 
    if (isset($_REQUEST["action"]))  { $API_ACTION      = $_REQUEST["action"];  } else { $API_ACTION = "";       }
                                                       
    if (isset($_REQUEST["email"]))   { $PAR_EMAIL       = $_REQUEST["email"];   } else { $PAR_EMAIL  = "";       }
    if (isset($_REQUEST["passwd"]))  { $PAR_PASSWD      = $_REQUEST["passwd"];  } else { $PAR_PASSWD = "";       }
    if (isset($_REQUEST["uuid"]))    { $PAR_UUID        = $_REQUEST["uuid"];    } else { $PAR_UUID = "";         }

    if (isset($_REQUEST["since"]))   { $PAR_SINCE       = $_REQUEST["since"];   } else { $PAR_SINCE  = "";       }
    if (isset($_REQUEST["until"]))   { $PAR_UNTIL       = $_REQUEST["until"];   } else { $PAR_UNTIL  = "";       }
    if (isset($_REQUEST["offset"]))  { $PAR_LIMITOFFSET = $_REQUEST["offset"];  } else { $PAR_LIMITOFFSET  = ""; }
    if (isset($_REQUEST["limit"]))   { $PAR_LIMIT       = $_REQUEST["limit"];   } else { $PAR_LIMIT  = "";       }
    if (isset($_REQUEST["special"])) { $PAR_SPECIAL     = $_REQUEST["special"]; } else { $PAR_SPECIAL  = "";     }
    if (isset($_REQUEST["iv"]))      { $ENC_IV          = $_REQUEST["iv"];      } else { $ENC_IV  = "";          }
    
    if (isset($_REQUEST["emailto"])) { $PAR_EMAILTO     = $_REQUEST["emailto"]; } else { $PAR_EMAILTO = "";      }
    if (isset($_REQUEST["summary"])) { $PAR_SUMMARY     = $_REQUEST["summary"]; } else { $PAR_SUMMARY = "";      }
    if (isset($_REQUEST["mailtext"])){ $PAR_MAILTEXT    = $_REQUEST["mailtext"];} else { $PAR_MAILTEXT = "";     }
    if (isset($_REQUEST["att01"]))   { $PAR_ATT01       = $_REQUEST["att01"];   } else { $PAR_ATT01 = "";        }
    if (isset($_REQUEST["att02"]))   { $PAR_ATT02       = $_REQUEST["att02"];   } else { $PAR_ATT02 = "";        }
    if (isset($_REQUEST["att03"]))   { $PAR_ATT03       = $_REQUEST["att03"];   } else { $PAR_ATT03 = "";        }
    if (isset($_REQUEST["att04"]))   { $PAR_ATT04       = $_REQUEST["att04"];   } else { $PAR_ATT04 = "";        }
    if (isset($_REQUEST["att05"]))   { $PAR_ATT05       = $_REQUEST["att05"];   } else { $PAR_ATT05 = "";        }    
  
} else {
    
    if (isset($_POST["key"]))     { $API_KEY          = $_POST["key"];     } else { $API_KEY = "";          } 
    if (isset($_POST["action"]))  { $API_ACTION       = $_POST["action"];  } else { $API_ACTION = "";       }

    if (isset($_POST["email"]))   { $PAR_EMAIL        = $_POST["email"];   } else { $PAR_EMAIL = "";        }
    if (isset($_POST["passwd"]))  { $PAR_PASSWD       = $_POST["passwd"];  } else { $PAR_PASSWD = "";       }
    if (isset($_POST["uuid"]))    { $PAR_UUID         = $_POST["uuid"];    } else { $PAR_UUID = "";         }

    if (isset($_POST["since"]))   { $PAR_SINCE        = $_POST["since"];   } else { $PAR_SINCE = "";        }
    if (isset($_POST["until"]))   { $PAR_UNTIL        = $_POST["until"];   } else { $PAR_UNTIL = "";        }
    if (isset($_POST["offset"]))  { $PAR_LIMITOFFSET  = $_POST["offset"];  } else { $PAR_LIMITOFFSET = "";  }
    if (isset($_POST["limit"]))   { $PAR_LIMIT        = $_POST["limit"];   } else { $PAR_LIMIT = "";        }
    if (isset($_POST["special"])) { $PAR_SPECIAL      = $_POST["special"]; } else { $PAR_SPECIAL = "";      }
    if (isset($_POST["iv"]))      { $ENC_IV           = $_POST["iv"];      } else { $ENC_IV  = "";          }   
    
    if (isset($_POST["emailto"])) { $PAR_EMAILTO      = $_POST["emailto"]; } else { $PAR_EMAILTO = "";      }
    if (isset($_POST["summary"])) { $PAR_SUMMARY      = $_POST["summary"]; } else { $PAR_SUMMARY = "";      }
    if (isset($_POST["mailtext"])){ $PAR_MAILTEXT     = $_POST["mailtext"];} else { $PAR_MAILTEXT = "";     }
    if (isset($_POST["att01"]))   { $PAR_ATT01        = $_POST["att01"];   } else { $PAR_ATT01 = "";        }
    if (isset($_POST["att02"]))   { $PAR_ATT02        = $_POST["att02"];   } else { $PAR_ATT02 = "";        }
    if (isset($_POST["att03"]))   { $PAR_ATT03        = $_POST["att03"];   } else { $PAR_ATT03 = "";        }
    if (isset($_POST["att04"]))   { $PAR_ATT04        = $_POST["att04"];   } else { $PAR_ATT04 = "";        }
    if (isset($_POST["att05"]))   { $PAR_ATT05        = $_POST["att05"];   } else { $PAR_ATT05 = "";        }
}


if ( $API_KEY != APIKEY ) {
    $JSONResponse["meta"] = array("error" => "999", "rows" => 0, "message" => "you are not allowed");
    echo json_encode($JSONResponse); 
    exit();
    }

if ( $API_ACTION == "" ) { 
    $JSONResponse["meta"] = array("error" => "998", "rows" => 0, "message" => "nothing to do if you dont tell what to do :)");
    echo json_encode($JSONResponse); 
    exit();
    }

            
ob_start("ob_gzhandler");
switch ($API_ACTION) {
    case "checkLogin":
        echo checkLogin($PAR_EMAIL, $PAR_PASSWD, $PAR_UUID);
        break;       
    case "getNavigationElements":
        echo getNavigationElements($PAR_SPECIAL);
        break;
    case "getNavigationToErrorcode":
        echo getNavigationToErrorcode($PAR_SPECIAL);
        break;
    case "getErrorcodeEnc":
        echo getErrorcodeEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;  
    case "getTelefoneEnc":
        echo getTelefoneEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;  
    case "getZugangscodesEnc":
        echo getZugangscodesEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;  
    case "getHerstellerhinweiseEnc":
        echo getHerstellerhinweiseEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;   
    case "getAnleitungenEnc":
        echo getAnleitungenEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;              
    case "getDocumentsEnc":
        echo getDocumentsEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;    
    case "getSparepartsEnc":
        echo getSparepartsEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;         
    case "getCategoryPictures";
        echo getCategoryPictures($PAR_SINCE, $PAR_UNTIL);
        break;
    case "sendEMailToSupport";
        echo sendEMailToSupport($PAR_EMAILTO, $PAR_SUMMARY, $PAR_MAILTEXT, $PAR_ATT01, $PAR_ATT02, $PAR_ATT03, $PAR_ATT04, $PAR_ATT05);
        break;
    case "getPing";
        echo getPing();
        break;
    case "getCompanyLogo";
        echo getCompanyLogo($PAR_EMAIL);
        break; 
    case "getWPEnc":
        echo getWPEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;
    case "getWPStepsEnc":
        echo getWPStepsEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break; 
    case "getWPTasksEnc":
        echo getWPTasksEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;
    case "getWPtoModelEnc":
        echo getWPtoModelEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL);
        break;        
                    
}
ob_end_flush();
exit();


// CHECK LOGIN
function checkLogin($PAR_EMAIL, $PAR_PASSWD, $PAR_UUID) {
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query ="SELECT id, username, password, first_name, last_name, active FROM kbp_user WHERE username like '" . $PAR_EMAIL ."'";        
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_array($result);
    
    $login = true;
    
    $IV = str_pad ( $PAR_EMAIL, 16, $PAR_EMAIL );  // auf 16 stellen auffüllen wenn nötig
    $IV = substr($IV,0,16);                        // auf 16 stellen kürzen wenn nörig
    
    $PAR_PASSWD_DEC = mc_decrypt($PAR_PASSWD, ENC_KEY, $IV);    // entschlüsseln mit key und iv = email
    
    $password_escaped = addslashes(stripslashes($PAR_PASSWD_DEC));
    $login = HashPassword::validate($password_escaped, $row["password"]);  // vergleich von PW wie in knowledgeDB
    
    if ($row["active"] != '1') { $login = false; }
    
    if (strtolower($row["username"]) != strtolower($PAR_EMAIL)) { $login = false; }
    
    $JSONResponse = array("result" => array());
        
    if ($login) {
        
        $user_id        = $row["id"];
        $date_login     = strtotime("now");
        $login_type     = "5";
        //$user_ip        = str_replace(".", "", $_SERVER["REMOTE_ADDR"]);
        $user_ip        = ip2long($_SERVER["REMOTE_ADDR"]);
        $username       = $PAR_EMAIL;
        $output         = $PAR_UUID. ";". htmlentities($_SERVER['HTTP_USER_AGENT']);
        $exitcode       = 1;
        $active         = 1;
        
        $query ="INSERT INTO kbp_log_login (user_id, login_type, user_ip, username, output, exitcode, active) VALUES ('$user_id','$login_type','$user_ip','$username','$output','$exitcode','$active')";
        $result = mysqli_query($connection, $query);
        
        $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "LOGIN OK");
        
    } else {
        $JSONResponse["meta"] = array("error" => "099", "rows" => 0, "message" => "LOGIN FALSE");
    }
    return json_encode($JSONResponse);
    
    
}


/// CATEGORY EXPORT
function getNavigationElements($PAR_SPECIAL) { 
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    //$query = "select * from kbp_kb_category where active = 1 AND name not like '00_Zugangs%'";
    $query = "select kbp_kb_category.*, logo_app from kbp_kb_category ".
                "LEFT JOIN zzz_kb_category_plus ON kbp_kb_category.id = zzz_kb_category_plus.id ".
                "WHERE active = 1 AND name not like '00_Zugangs%'";
    
    $result = mysqli_query($connection, $query);
    
    $JSONResponse = array("result" => array()); 
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"

        while($row = mysqli_fetch_array($result)) {

            $JSONRow = array('id'            => $row["id"], 
                             'label'         => utf8_encode($row["name"]), 
                             'parent_id'     => $row["parent_id"],
                             'icon'          => utf8_encode($row["logo_app"]), 
                             'sort_order'    => $row["sort_order"]);
                                
            array_push($JSONResponse["result"], $JSONRow); 
        }
    }

    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");  
       return json_encode($JSONResponse);         
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse);        
    }  
}


/// ENTRY TO CATEGORY EXPORT
function getNavigationToErrorcode($PAR_SPECIAL) {
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die (); 
    // Nur Einträge für deren Categories auch active=1 gesetzt ist werden exportiert.
    $query = "SELECT a.entry_id, a.category_id, a.sort_order, b.parent_id ".
                "FROM kbp_kb_entry_to_category a, kbp_kb_category b ".
                "WHERE a.category_id = b.id ".
                "AND b.active = 1";
                
    $result = mysqli_query($connection, $query);
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {

            $JSONRow = array('entry_id'       => $row["entry_id"], 
                             'category_id'    => $row["category_id"], 
                             'sort_order'     => $row["sort_order"],
                             'parent_id'      => $row["parent_id"]); 
            array_push($JSONResponse["result"], $JSONRow);
        } 
    }                                                                           
    
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK"); 
       return json_encode($JSONResponse);    
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse);                                               
    }      
        
}



/// ENTRY EXPORT 
function getErrorcodeEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, body, sort_order FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "WHERE private = 0 AND active = 1 AND kbp_kb_custom_data.data = 1 ".
             "AND kbp_kb_custom_data.field_id = 2 ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
                    
    if ($ENC_IV == "appletest@stoerc" ) {
        $query = $query."LIMIT 999 ";
        
    } else {
    
        if ($PAR_LIMIT <> "") {
           $query = $query."LIMIT ".$PAR_LIMIT." "; 
        }
        if ($PAR_LIMITOFFSET <> "") {
           $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
        }      
    
    }
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}


// Telefonnummern exportieren - 8
function getTelefoneEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, body, sort_order FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "WHERE private = 0 AND active = 1 AND kbp_kb_custom_data.data = 8 ".
             "AND kbp_kb_custom_data.field_id = 2 ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
    
    if ($PAR_LIMIT <> "") {
       $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
       $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}

// Zugangscodes exportieren - 7
function getZugangscodesEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, body, sort_order FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "WHERE private = 0 AND active = 1 AND kbp_kb_custom_data.data = 7 ".
             "AND kbp_kb_custom_data.field_id = 2 ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
    
    if ($PAR_LIMIT <> "") {
       $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
       $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}


// Herstellerhinweise exportieren - 13
function getHerstellerhinweiseEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, body, sort_order FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "WHERE private = 0 AND active = 1 AND kbp_kb_custom_data.data = 13 ".
             "AND kbp_kb_custom_data.field_id = 2 ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
    
    if ($PAR_LIMIT <> "") {
       $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
       $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}

// Anleitungen exportieren - 6
function getAnleitungenEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, body, sort_order FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "WHERE private = 0 AND active = 1 AND kbp_kb_custom_data.data = 6 ".
             "AND kbp_kb_custom_data.field_id = 2 ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
    
    if ($PAR_LIMIT <> "") {
       $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
       $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
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
function getSparepartsEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, body, sort_order FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "WHERE private = 0 AND active = 1 AND kbp_kb_custom_data.data = 9 ".
             "AND kbp_kb_custom_data.field_id = 2 ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
    
    if ($PAR_LIMIT <> "") {
       $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
       $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}


// Dokumente exportieren (diverse Typ-IDs nach vorgaben)
function getDocumentsEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT kbp_kb_entry.id, kbp_kb_entry.title, kbp_kb_entry.body, kbp_kb_entry.sort_order,kbp_kb_custom_data.data as typid, kbp_custom_field_range_value.title as typ FROM kbp_kb_entry ".
             "INNER JOIN kbp_kb_custom_data on kbp_kb_entry.id = kbp_kb_custom_data.entry_id ".
             "INNER JOIN kbp_custom_field_range_value on kbp_kb_custom_data.data = kbp_custom_field_range_value.id ".
             "WHERE private = 0 AND active = 1  ".
             "AND kbp_kb_custom_data.field_id = 2 ".
             "AND kbp_custom_field_range_value.range_id = 1 ".
             "AND kbp_kb_custom_data.data IN (10,11,12,14,15,16,17,18,19) ";

    if ($PAR_SINCE <> "") {
       $query = $query."AND date_updated >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_updated <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
    
    if ($PAR_LIMIT <> "") {
       $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
       $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $row['body'] = parseImages($row['body'], "http://www.stoercode.de");
            
            $JSONRow = array('id'             => $row["id"], 
                             'label'          => utf8_encode($row["title"]),
                             'content'        => mc_encrypt(stripslashes(utf8_encode($row["body"])), ENC_KEY, $ENC_IV),
                             'sort_order'     => $row["sort_order"],
                             'typid'          => $row["typid"],
                             'typ'            => $row["typ"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}


/// CATEGORY Icon EXPORT aus description
function getCategoryPictures($PAR_SINCE, $PAR_UNTIL) { 
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();

    $query = 'SELECT logo_app FROM kbp_kb_category '.
                'INNER JOIN zzz_kb_category_plus ON kbp_kb_category.id = zzz_kb_category_plus.id '.
                'WHERE logo_app != "" '.
                'GROUP BY logo_app';
                
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    $count = 0;

    while($row = mysqli_fetch_array($result)) {
        
        $filename = PATH_CATPIC.$row["logo_app"];
        
        if (file_exists($filename)) {
            
            $filetime = date ("Y-m-d", filemtime($filename));
            $filenameI = utf8_encode($row["logo_app"]);
                
            if (strtotime($filetime) >= strtotime($PAR_SINCE)) {
            
                
                    
                                                              
                    $imageBase = imageToBase64 ($filename);
                    //$genid = crc32($filenameI);
                    //$genid = gmp_strval(gmp_init(substr(md5($filenameI), 0, 16), 16), 10);

                    $JSONRow = array('icon'      => $filenameI,
                                     'lastmod'   => $filetime, 
                                     'base64'    => $imageBase); 
                                     
                    array_push($JSONResponse["result"], $JSONRow);
                    $count = $count + 1;
            }
        }
    }

    if ( $count > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => $count, "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
}

/// FEEDBACK EMAIL an Support
function sendEMailToSupport($PAR_EMAILTO, $PAR_SUMMARY, $PAR_MAILTEXT, $PAR_ATT01, $PAR_ATT02, $PAR_ATT03, $PAR_ATT04, $PAR_ATT05) { 

    if ( $PAR_EMAILTO == "" ) {
        $PAR_EMAILTO = SUPPORTMAIL;  //Standartadresse wenn nichts gesetzt
    }
    
    // Mailtext für HTML vorbereiten ohne BR umzuwandeln
    $PAR_MAILTEXT = br2nl($PAR_MAILTEXT);
    $PAR_MAILTEXT = htmlspecialchars($PAR_MAILTEXT);
    $PAR_MAILTEXT = nl2br($PAR_MAILTEXT);
    
    $PAR_SUMMARY = "=?utf-8?b?".base64_encode($PAR_SUMMARY)."?=";
    
    $mime_boundary = "-----=" . md5(uniqid(microtime(), true));
    //$encoding = mb_detect_encoding($message, "utf-8, iso-8859-1, cp-1252");
    $encoding = "utf-8";
            
    preg_match ("/(?<=Benutzer:\s)\S*/m", $PAR_MAILTEXT, $match); //Search-String
    if (isset($match[0])){
        $frommail = $match[0];
        $from = $match[0]; 
    } else {
        $frommail = "info@stoercode.de";
        $from = "API Mail Connector";
    }        

    $headers  = 'From: "'.addslashes($from).'" <'.$frommail.">\r\n";
    $headers .= "Reply-To: ".$frommail."\r\n";  
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"";
    
    $content = "If you can see this MIME than your client doesn't accept MIME types!\r\n\r\n";
    $content.= "--".$mime_boundary."\r\n";

    $content .= "Content-Type: text/html; charset=\"$encoding\"\r\n";
    $content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $content .= $PAR_MAILTEXT."\r\n";
    
    if ($PAR_ATT01 != "") {
        //$img = chunk_split(base64_encode(file_get_contents("test.jpg")));
        $content .= "--".$mime_boundary."\r\n";
        $content .= "Content-disposition: attachment; file=\"picture1.jpg\"\r\n";
        $content .= "Content-Type: image/jpg; name=\"picture1.jpg\"\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= $PAR_ATT01."\r\n";  
    }
    if ($PAR_ATT02 != "") {
        //$img = chunk_split(base64_encode(file_get_contents("test.jpg")));
        $content .= "--".$mime_boundary."\r\n";
        $content .= "Content-disposition: attachment; file=\"picture2.jpg\"\r\n";
        $content .= "Content-Type: image/jpg; name=\"picture2.jpg\"\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= $PAR_ATT02."\r\n";  
    }
    if ($PAR_ATT03 != "") {
        //$img = chunk_split(base64_encode(file_get_contents("test.jpg")));
        $content .= "--".$mime_boundary."\r\n";
        $content .= "Content-disposition: attachment; file=\"picture3.jpg\"\r\n";
        $content .= "Content-Type: image/jpg; name=\"picture3.jpg\"\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= $PAR_ATT03."\r\n";  
    }
    if ($PAR_ATT04 != "") {
        //$img = chunk_split(base64_encode(file_get_contents("test.jpg")));
        $content .= "--".$mime_boundary."\r\n";
        $content .= "Content-disposition: attachment; file=\"picture4.jpg\"\r\n";
        $content .= "Content-Type: image/jpg; name=\"picture4.jpg\"\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= $PAR_ATT04."\r\n";  
    }
    if ($PAR_ATT05 != "") {
        //$img = chunk_split(base64_encode(file_get_contents("test.jpg")));
        $content .= "--".$mime_boundary."\r\n";
        $content .= "Content-disposition: attachment; file=\"picture5.jpg\"\r\n";
        $content .= "Content-Type: image/jpg; name=\"picture5.jpg\"\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= $PAR_ATT05."\r\n";  
    }
    
    $content .= "--".$mime_boundary."--"; 
        
    $JSONResponse = array("result" => array()); 
    $success = mail($PAR_EMAILTO,$PAR_SUMMARY,$content,$headers);
    
    if ($success) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "EMAIL OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "099", "rows" => 0, "message" => "EMAIL FALSE");
       return json_encode($JSONResponse); 
    }   
}


// CHECK PING
function getPing() {
    
    $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "1");
    
    return json_encode($JSONResponse);
    
    
}

// ReturnCompany Logo
function getCompanyLogo($PAR_EMAIL) {
    
    try {
        //include("../Login/custom_company/data.php");
        
        $JSONResponse = array("result" => array());
        
        $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
        $query ="SELECT company_id FROM kbp_user WHERE username like '" . $PAR_EMAIL ."'";        
        $result = mysqli_query($connection, $query); 
        $row = mysqli_fetch_array($result);
        $company_id = $row["company_id"]; 
        
     
        if ($company_id) {
           
            $query ="SELECT logo_app FROM zzz_user_company_plus WHERE id = '" . $company_id ."'";        
            $result = mysqli_query($connection, $query); 
            $row = mysqli_fetch_array($result);
            $company_logo = $row["logo_app"];   
            
            if ($company_logo) {
                $company_logo = PATH_COMPANY_LOGO.$company_logo;
                if (file_exists ($company_logo)) {                   
                    $imageBase = imageToBase64 ($company_logo);
                
                    $JSONRow = array('company_id'       => $company_id,
                                     'logourl'          => str_replace("..","https://www.stoercode.de",$company_logo),
                                     'base64_logo'      => $imageBase); 
                    
                    array_push($JSONResponse["result"], $JSONRow);
                    $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "OK");
                } else {
                    $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
                }
            } else {
                    $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
            }
            
        } else {
                    $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
        }
        
        /* if($company_id && !empty($clients[$company_id])) {
            //$company_logo = PATH_COMPANY_LOGO.$company_id.".png";
            $company_logo = str_replace(".css",".png", PATH_COMPANY_LOGO.$clients[$company_id]);
            
            if (file_exists ($company_logo)) {                   
                $imageBase = imageToBase64 ($company_logo);
            
                $JSONRow = array('company_id'       => $row["company_id"],
                                 'logourl'          => str_replace("..","https://www.stoercode.de",$company_logo),
                                 'base64_logo'      => $imageBase); 
                
                array_push($JSONResponse["result"], $JSONRow);
                $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "OK");
            } else {
                $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
            }    
        } else {
            $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
        }  */          
        
        /*if($company_id && !empty($clients[$company_id])) {
            $company_css = PATH_COMPANY_CSS.$clients[$company_id];
            
            if (file_exists ($company_css)) {
                $cssFileContent = file_get_contents($company_css);
                
                preg_match('/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $cssFileContent, $matches, PREG_OFFSET_CAPTURE);
                $company_logo = PATH_COMPANY_LOGO.pathinfo($matches[3][0], PATHINFO_BASENAME);            
                
                $imageBase = imageToBase64 ($company_logo);
            
                $JSONRow = array('company_id'       => $row["company_id"],
                                 'logourl'          => $matches[3][0],
                                 'base64_logo'      => $imageBase); 
                
                array_push($JSONResponse["result"], $JSONRow);
                $JSONResponse["meta"] = array("error" => "000", "rows" => 1, "message" => "OK");
            } else {
                $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
            }    
        } else {
            $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT");
        } */
                   
    } catch (Exception $e) {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "DEFAULT"); 
    }
    return json_encode($JSONResponse);   
}


function getWPEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, hint, date_version FROM zzz_wp ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."WHERE date_version >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_version <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
                    
    if ($PAR_LIMIT <> "") {
        $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
        $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $JSONRow = array('id'             => $row["id"], 
                             'title'          => mc_encrypt(stripslashes(utf8_encode($row["title"])), ENC_KEY, $ENC_IV),
                             'hint'           => mc_encrypt(stripslashes(utf8_encode($row["hint"])), ENC_KEY, $ENC_IV),
                             'date_version'   => $row["date_version"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
}    
        

function getWPStepsEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT wp, step, task, date_version FROM zzz_wp_steps ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."WHERE date_version >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_version <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY wp ";
                    
    if ($PAR_LIMIT <> "") {
        $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
        $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    } 
    
 
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $JSONRow = array('wp'             => $row["wp"], 
                             'step'           => $row["step"],
                             'task'           => $row["task"],
                             'date_version'   => $row["date_version"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
} 

function getWPTasksEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT id, title, `desc`, hint, control_select, control_count, control_values,  date_version FROM zzz_wp_tasks ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."WHERE date_version >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_version <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY id ";
                    
    if ($PAR_LIMIT <> "") {
        $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
        $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $JSONRow = array('id'             => $row["id"], 
                             'title'          => mc_encrypt(stripslashes(utf8_encode($row["title"])), ENC_KEY, $ENC_IV),
                             'desc'           => mc_encrypt(stripslashes(utf8_encode($row["desc"])), ENC_KEY, $ENC_IV),
                             'hint'           => mc_encrypt(stripslashes(utf8_encode($row["hint"])), ENC_KEY, $ENC_IV),
                             'control_select' => mc_encrypt(stripslashes(utf8_encode($row["control_select"])), ENC_KEY, $ENC_IV),
                             'control_count'  => $row["control_count"],
                             'control_values' => mc_encrypt(stripslashes(utf8_encode($row["control_values"])), ENC_KEY, $ENC_IV),
                             'image'          => "", 
                             'date_version'   => $row["date_version"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
} 

function getWPtoModelEnc($PAR_SINCE, $PAR_UNTIL, $PAR_LIMIT, $PAR_LIMITOFFSET, $ENC_IV, $PAR_SPECIAL) { 
    
    if ($ENC_IV == "") {
            $JSONResponse["meta"] = array("error" => "002", "rows" => 0, "message" => "IV not set!");
            return json_encode($JSONResponse);  
    }
    
    $connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
    $query = "SELECT kb_category_id, wp, `type`, date_version FROM zzz_wp_kbcategory ";
             
    if ($PAR_SINCE <> "") {
       $query = $query."WHERE date_version >= '".$PAR_SINCE."' "; 
    }
    if ($PAR_UNTIL <> "") {
       $query = $query."AND date_version <= '".$PAR_UNTIL."' "; 
    } 
    
    $query = $query."ORDER BY kb_category_id ";
                    
    if ($PAR_LIMIT <> "") {
        $query = $query."LIMIT ".$PAR_LIMIT." "; 
    }
    if ($PAR_LIMITOFFSET <> "") {
        $query = $query."OFFSET ".$PAR_LIMITOFFSET." "; 
    }      
    
    $result = mysqli_query($connection, $query); 
    
    $JSONResponse = array("result" => array());
    
    if ($PAR_SPECIAL != "count") {   // Daten nicht auslesen wenn special="count"
    
        while($row = mysqli_fetch_array($result)) {
            
            $JSONRow = array('kb_category_id'  => $row["kb_category_id"],
                             'wp'              => $row["wp"], 
                             'type'            => $row["type"],
                             'date_version'    => $row["date_version"]);     
            array_push($JSONResponse["result"], $JSONRow);   
        }
    }
  
    if ( mysqli_num_rows($result) > 0 ) {
       $JSONResponse["meta"] = array("error" => "000", "rows" => mysqli_num_rows($result), "message" => "OK");
       return json_encode($JSONResponse); 
    } else {
       $JSONResponse["meta"] = array("error" => "001", "rows" => 0, "message" => "No datarows found!");
       return json_encode($JSONResponse); 
    }   
    
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