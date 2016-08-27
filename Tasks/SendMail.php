<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 27-8-2016
 * Time: 15:28
 */

global $app_config;
$app_config = parse_ini_file('../config.ini', true);

// Notice that $image_content_id is the optional Content-ID header value of the
// attachment. Must be enclosed by angle brackets (<>)
//$image_content_id = '<image-content-id>';

// Pull in the raw file data of the image file to attach it to the message.
//$image_data = file_get_contents('image.jpg');

//try {
//    $message = new Message();
//    $message->setSender('bert@going-dutch-api.appspotmail.com');
//    $message->addTo('atsantema@yahoo.com');
//    $message->setSubject('Example email');
//    $message->setTextBody('Hello, world!');
//    //$message->addAttachment('image.jpg', $image_data, $image_content_id);
//    $message->send();
//    //echo 'Mail Sent';
//} catch (InvalidArgumentException $e) {
//    error_log($e->getMessage());
//}
use google\appengine\api\mail\Message;

require '../Public/Db/Db-GAE.php';
require '../vendor/sendgrid-google-php-master/SendGrid_loader.php';


//require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
//use PHPMailer\PHPMailer\PHPMailer;


function SendEmail(){
    global $app_config;
    $sendgrid_user = $app_config['secret']['sendgrid_user'];
    $sendgrid_pass = $app_config['secret']['sendgrid_pass'];
    $sendgrid = new SendGrid\SendGrid($sendgrid_user, $sendgrid_pass);


    $sql = "SELECT * FROM email WHERE sent = 0";
    $stmt = Db::getInstance()->prepare($sql);
    $stmt->execute();
    $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    error_log('Email count: ' . count($emails));
    foreach ($emails as $email) {
        //error_log('Processing mail id: ' . $email['email_id']);
//        $mailer = new PHPMailer;  // create a new object
//        $mailer->AddAddress($email['toaddress']);
//        // $mailer->SetFrom($email['toaddress'], 'Going Dutch');
//        $mailer->SetFrom('bert@going-dutch-api.appspotmail.com', 'Going Dutch');
//        $mailer->addReplyTo('bert@going-dutch.eu', 'Going Dutch');
//        $mailer->Subject = $email['subject'];
//        $mailer->Body = $email['message'];
//        $mailer->IsHTML(true);



// Notice that $image_content_id is the optional Content-ID header value of the
// attachment. Must be enclosed by angle brackets (<>)
        // $image_content_id = '<image-content-id>';

// Pull in the raw file data of the image file to attach it to the message.
        //$image_data = file_get_contents('image.jpg');

        try {
/*            $message = new Message();
            $message->setSender('bert@going-dutch-api.appspotmail.com');
            $message->addTo($email['toaddress']);
            $message->setSubject($email['subject']);
            $message->setTextBody('Plain text body');
            $message->setHtmlBody($email['message']);
            //$message->addAttachment('image.jpg', $image_data, $image_content_id);
            $message->send();
            //echo 'Mail Sent';
  */

// Make a message object
            $mail = new SendGrid\Mail();

// Add recipients and other message details
            $mail->addTo($email['toaddress'])->
            //addTo('dude@bar.com')->
            setFrom('going-dutch@mail.going-dutch.eu')->
            setFromName('Going Dutch')->
            setSubject($email['subject'] . ' [TEST]')->
            setText($email['message'])->
            setHtml($email['message']);

// Use the Web API to send your message
            $sendgrid->send($mail);



        } catch (InvalidArgumentException $e) {
            error_log('There was an error sending mail: ' . $e->getMessage());
        }


        $sql = "UPDATE email SET sent=FROM_UNIXTIME(:updated) WHERE email_id=:email_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':email_id' => $email['email_id'],
                ':updated' => time()
            )
        );



//send the message, check for errors
//        if (!$mailer->send()) {
//            error_log("Mailer Error: " . $mail->ErrorInfo);
//        }

    }
}

SendEmail();