<?php
define ( 'dbHOST',              'stoercode.frozen-media.de' );
define ( 'dbUSER',              'stoeru_3' );
define ( 'dbPASSWORD',          'F8v7NfUr6A5PCT6n' );
define ( 'dbDATABASE',          'sc_knowledge_dev' );

$JSONinput = '
{
  "user"      : "thomas.ziegler@frozen-media.de",
  "objekt"    : "Meier, Bergstraße 12ä",
  "geraet"    : "Viessmann - Gaskessel - Vitocrossalä",
  "datum"     : "2020-12-13",
  "zeit"      : "12:34",
  "wpid"      : "0815",
  "kommentar" : "",
  "list": [
    {
      "taskid": "123",
      "titel" : "Brenner ausbauenä",
      "soll"  : "",
      "ist"   : "",
      "check" : "1"
    },
    {
      "taskid": "124",
      "titel" : "Heizflächen mit PVC Bürste oder Flüssigreiniger (Wasser)reinigen;Verkratzungsgefahr",
      "soll"  : "",
      "ist"   : "",
      "check" : "0"
    },
    {
      "taskid": "125",
      "titel" : "Ringspaltmessung durchführen O² ca. 20,6 % Abgasleitung raumluftunabhängig; istdicht.",
      "soll"  : "20,0-21,0",
      "ist"   : "20,7%",
      "check" : "1"
    }
  ]
}';

// JSON Importieren
$wplist = json_decode($JSONinput);

$inpUser    = utf8_decode($wplist->user);
$inpWpID    = $wplist->wpid;
$inpObjekt  = utf8_decode($wplist->objekt);
$inpGeraet  = utf8_decode($wplist->geraet);
$inpDatum   = $wplist->datum;
$inpZeit    = $wplist->zeit;
$inpList    = $wplist->list;

// Benutzerdaten aus DB Holen
$connection = mysqli_connect (dbHOST, dbUSER, dbPASSWORD, dbDATABASE) or die ();
$query = "SELECT a.id, a.username, a.first_name, a.last_name, a.company_id, b.title, b.address, b.zip, b.city ".
         "FROM kbp_user a ".
         "LEFT JOIN kbp_user_company b ON a.company_id = b.id ".
         "WHERE a.username = '" . $inpUser ."'";        
$result = mysqli_query($connection, $query);
$userdata = mysqli_fetch_array($result);

// Uservariablen zuweisen
$userid         = $userdata["id"];
$userName       = $userdata["first_name"]. " ". $userdata["last_name"];
$userEmail      = $userdata["username"];
$userCompanyID  = $userdata["company_id"];
$userCompany    = $userdata["title"];
$userAddr       = $userdata["address"];
$userPlz        = $userdata["zip"];
$userCity       = $userdata["city"];

// Log in Datenbank Speichern
$query = "INSERT INTO zzz_log_wartungen (user_id,company_id,wp_id,objekt,geraet,datum,zeit,kommentar) ".
         "VALUES ($userid,$userCompanyID,$inpWpID,'$inpObjekt','$inpGeraet','$inpDatum','$inpZeit','')";
$result = mysqli_query($connection, $query);

$last_id = mysqli_insert_id($connection);

$step = 0;
foreach ($inpList as $line) {
        $step=$step+1;
        $query = "INSERT INTO zzz_log_wartungen_steps (wartung_id,step,taskid,titel,soll,ist,`check`) ".
         "VALUES ($last_id,$step,".$line->taskid.",'".utf8_decode($line->titel)."','".utf8_decode($line->soll)."','".utf8_decode($line->ist)."',".$line->check.")";
        $result = mysqli_query($connection, $query); 
}



//PDF Erzeugen

include "includes/fpdf/fpdf.php";
include "includes/fpdf-easytable/exfpdf.php";
include "includes/fpdf-easytable/easyTable.php";
 
$pdf=new exFPDF("P", "mm", "A4");
$pdf->AddPage(); 
$pdf->SetFont('helvetica','',10);

$table1=new easyTable($pdf, 2);         
$table1->easyCell('Wartungscheckliste', 'font-size:20; font-style:B; font-color:#000000;');
$table1->easyCell('', 'img:images/stoercode-logo.png, w60; align:R;');
$table1->printRow();
$table1->endTable(5);

$table1=new easyTable($pdf, "%{60, 40}");
$table1->rowStyle("font-size:12;");
$table1->easyCell("<b>Erstellt:</b> ".$inpDatum." ".$inpZeit."\n"."<b>Objekt:</b> ".$inpObjekt);
$table1->easyCell($userCompany."\n".$userName."\n".$userAddr."\n".$userPlz." ".$userCity, "align:R;rowspan:2;valign:T");
$table1->printRow(); 
$table1->rowStyle("font-size:12;font-style:B;");
$table1->easyCell($inpGeraet);
$table1->printRow(); 
$table1->endTable(5);


//====================================================================


$table=new easyTable($pdf, '%{70, 10,10,10}','align:L{LCCC};border:1; border-color:#a1a1a1; ');
$table->rowStyle('align:L{LCCC};valign:T;bgcolor:#000000;font-color:#ffffff;font-style:B;');
$table->easyCell('Wartungsschritt');
$table->easyCell('Soll');
$table->easyCell('Ist');
$table->easyCell('Check');
$table->printRow();

foreach ($inpList as $line) {
        $table->rowStyle('valign:T;border:LRTB;paddingY:2;');
        $table->easyCell(utf8_decode($line->titel),'');
        $table->easyCell(utf8_decode($line->soll), '');
        $table->easyCell(utf8_decode($line->ist), '');
        if ($line->check == 1) {
            $table->easyCell('', 'img:images/dialog-ok-apply-6.png, w4;');    
        } else {
            $table->easyCell('', 'img:images/dialog-cancel-7.png, w4;');    
        }
        $table->printRow();
}

$table->endTable();

$pdf->Output(); 
?>
