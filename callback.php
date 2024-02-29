<?php
header('Content-Type: application/json');
// Read the callback response
$stkCallbackResponse = file_get_contents('php://input');

// Log the response to a file
$logFile = 'mpesastkresponse.json';
// $data = '{}';
// file_put_contents($logFile, $data);
$log = fopen($logFile, 'a');
fwrite($log, $stkCallbackResponse);
fclose($log);
?>
