<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 27-8-2016
 * Time: 15:28
 */
require '../Public/Db/Db-GAE.php';
require '../vendor/sendgrid-google-php-master/SendGrid_loader.php';
require '../vendor/soundasleep/html2text/src/Html2Text.php';

global $app_config;
$app_config = parse_ini_file('../config.ini', true);

function SendEmail(){
    global $app_config;
    $sendgrid_user = $app_config['secret']['sendgrid_user'];
    $sendgrid_pass = $app_config['secret']['sendgrid_pass'];
    $sendgrid = new SendGrid\SendGrid($sendgrid_user, $sendgrid_pass);


    $sql = "SELECT * FROM email WHERE sent = 0";
    $stmt = Db::getInstance()->prepare($sql);
    $stmt->execute();
    $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // error_log('Email count: ' . count($emails));
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


        try {
            $mail = new SendGrid\Mail();

            $text = Html2Text\Html2Text::convert($email['message']);

            $mail->addTo($email['toaddress'])->
            //addTo('dude@bar.com')->
            setFrom('going-dutch@mail.going-dutch.eu')->
            setFromName('Going Dutch')->
            setSubject($email['subject'] . ' [TEST]')->
            setText($text)->
            setHtml($email['message']);
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