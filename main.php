<?php

echo 'Hello, world!';

$subject = 'Test email notification system';
$email = 'This is a test message';
$to = 'atsantema@yahoo.com';

$headers   = array();
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/plain; charset=iso-8859-1";
$headers[] = "From: Going Dutch <admin@going-dutch-eu.appspotmail.com>";
//$headers[] = "Bcc: JJ Chong <bcc@domain2.com>";
$headers[] = "Reply-To: Going Dutch <admin@going-dutch.eu>";
//$headers[] = "Subject: {$subject}";
$headers[] = "X-Mailer: PHP/".phpversion();

//mail($to, $subject, $email, implode("\r\n", $headers));


//echo 'mail sent ' . time();

/*
$to      = 'nobody@example.com';
$subject = 'the subject';
$message = 'hello';
$headers = 'From: webmaster@example.com' . "\r\n" .
    'Reply-To: webmaster@example.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);
*/