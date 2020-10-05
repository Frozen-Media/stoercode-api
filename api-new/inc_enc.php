<?php


/*Usage in PHP
$original = "original message";
$key = "abcdefg_abcdefg_abcdefg_abcdefg_";
$iv = "abcdefg_abcdefg_";
$keysize = 128;


$enced = mc_encrypt(stripslashes($original), $key, $iv);
echo "Encrypted: ".$enced;
$unced = mc_decrypt($enced, $key, $iv);
echo "Decrypted: ".$unced; 
*/


//Encryption function
function mc_encrypt($encrypt, $key, $iv)
{
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
    mcrypt_generic_init($td, $key, $iv);
    $encrypted = mcrypt_generic($td, $encrypt);
    $encode = base64_encode($encrypted);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return $encode;
}


//Decryption function
function mc_decrypt($decrypt, $key, $iv)
{
    $decoded = base64_decode($decrypt);
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
    mcrypt_generic_init($td, $key, $iv);
    $decrypted = mdecrypt_generic($td, $decoded);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return trim($decrypted);
}  
?>
