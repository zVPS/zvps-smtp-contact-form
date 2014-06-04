<?php

require_once 'inc/mailer.php';

/** setup mailer and captcha services */
$mailer = new mailer();
$recaptchService = new Zend_Service_ReCaptcha($mailer->recaptchPublicKey, $mailer->recaptchPrivateKey);
$recaptchService->setParam('ssl', $mailer->recaptchSiteSsl);
$postSubmit = filter_input(INPUT_POST, 'submit', FILTER_DEFAULT);
$postRecaptchChallenge = filter_input(INPUT_POST, 'recaptcha_challenge_field', FILTER_DEFAULT);
$postRecaptchResponse = filter_input(INPUT_POST, 'recaptcha_response_field', FILTER_DEFAULT);

/** contact form was submitted, try to send the email */
if (isset($postSubmit)) {
    
    if($recaptchService->verify($postRecaptchChallenge, $postRecaptchResponse)) {
        
        $mailer->setFromName(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
        $mailer->setReplyTo(filter_input(INPUT_POST, 'email', FILTER_DEFAULT));
        $mailer->setSubject(filter_input(INPUT_POST, 'submit', FILTER_DEFAULT));
        $mailer->setBody(filter_input(INPUT_POST, 'message', FILTER_DEFAULT));
        $sendStatus = $mailer->send();  
        
    } else {
        logger::addError('Please try re-entering the Recaptch.');
    } 
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Contact Form Example</title>
    </head>
    <body>
        <?php if (logger::hasMessages()) { ?>
            <div class="<?php (logger::hasErrors()) ? 'error' : 'success'; ?>">
                <div><?php echo (logger::hasErrors()) ? implode('<br>', logger::getErrors()) : implode('<br>', logger::getSuccess()); ?></div>
            </div>
        <?php } ?>
        <form target="" method="post" action="" name="contact">
            <input type="text" placeholder="Your Name" name="name" id="email"><br>
            <input type="text" placeholder="Your Email Address" name="email" id="email"><br>
            <input type="text" placeholder="Summary of your question" name="subject" id="subject"><br>
            <textarea rows="8" name="message" id="message"></textarea><br>
            <?php echo $recaptchService->getHTML();  ?><br>
            <button type="submit" name="submit" value="submit">Send</button><br>
        </form>
    </body>
</html>
