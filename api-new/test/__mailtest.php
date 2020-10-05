<?php

echo emailImage("HIER KOMMT DER EMAILCONTENT HIN");


    
    function emailImage($message) {
        
        $mime_boundary = "-----=" . md5(uniqid(microtime(), true));
        $encoding = mb_detect_encoding($message, "utf-8, iso-8859-1, cp-1252");
        //$encoding = "iso-8859-1";
                
        $to = "thomas.ziegler@frozen-media.de";
        $subject = "API Test";
        $frommail = "noreply@frozen-media.de";
        $from = "TEST API";

        $headers  = 'From: "'.addslashes($from).'" <'.$frommail.">\r\n";
        $headers .= "Reply-To: ".$frommail."\r\n";  
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"";
        
        $content = "If you can see this MIME than your client doesn't accept MIME types!\r\n\r\n";
        $content.= "--".$mime_boundary."\r\n";

        $content .= "Content-Type: text/html; charset=\"$encoding\"\r\n";
        $content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $content .= $message."\r\n";


        for ($i = 1; $i <= 5; $i++) {
          $img = chunk_split(base64_encode(file_get_contents("test.jpg")));
          $content .= "--".$mime_boundary."\r\n";
          $content .= "Content-disposition: attachment; file=\"picture".$i.".jpg\"\r\n";
          $content .= "Content-Type: image/jpg; name=\"picture".$i.".jpg\"\r\n";
          $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
          $content .= $img."\r\n";
    
        }         

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
