<?php

stream_context_set_default( [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);

validateRemoteUrl("https://support.stoercode.de");

   // **********
function contains($str, $needle) {
  return (strpos($str, $needle) !== false);
}
// **********
function validateRemoteUrl($url) {
  $headers = get_headers($url);
  return (isset($headers) && count($headers) > 0 && contains($headers[0], "200"));
}
?>
