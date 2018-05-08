<?php

/** puts the Zend library on the current include path */
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

require_once 'Zend/Mail.php';
require_once 'Zend/Mail/Exception.php';
require_once 'Zend/Exception.php';
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Service/ReCaptcha.php';
require_once 'Zend/Filter/Alpha.php';
require_once 'Zend/Filter/StripTags.php';
require_once 'Zend/Validate/EmailAddress.php';

require_once 'logger.php';

/**
 * Mailer service wrapper for Zend SMTP
 * @requires logger
 * @author kevin@zvps.uk
 */
class mailer
{
    /** Editable - Setup recaptch keys */
    public $recaptchPublicKey = 'public-key_here';
    public $recaptchPrivateKey = 'private-key-here';

    /** Editable - Setup email account credentials and connection type */
    private $emailConfigAuth = 'plain';
    private $emailConfigUser = 'from@domain.com';
    private $emailConfigPass = 'email-account-password';
    private $emailConfigHost = 'localhost';
    private $emailConfigPort = 25;
    private $emailConfigSsl  = false;

    /** Editable - setup your email to receive contact form notifications */
    private $emailTo = 'to@domain.com';
    private $emailToName = 'our support team';
    private $emailFromName = 'customer name';
    private $emailSubject = 'Website Contact - ';

    /** End Editable */

    private $emailBody = null;
    private $emailReplyTo = null;
    private $smtpMail;
    private $smtpTransport;

    const CONFIG_AUTH = 'auth';
    const CONFIG_USER = 'username';
    const CONFIG_PASS = 'password';
    const CONFIG_PORT = 'port';
    const CONFIG_SSL  = 'ssl';
    const CONFIG_NAME = 'name';

    public function __construct()
    {
        $config = array(
            self::CONFIG_AUTH => $this->emailConfigAuth,
            self::CONFIG_USER => $this->emailConfigUser,
            self::CONFIG_PASS => $this->emailConfigPass,
            self::CONFIG_PORT => $this->emailConfigPort,
            self::CONFIG_SSL  => $this->emailConfigSsl,
            self::CONFIG_NAME => $this->emailConfigHost,
        );

        if($this->emailConfigSsl) {
            $config[self::CONFIG_SSL] = $this->emailConfigSsl;
        }

        $this->smtpTransport = new Zend_Mail_Transport_Smtp($this->emailConfigHost, $config);
        $this->smtpMail = new Zend_Mail();
    }

    /**
     * Pass a name or company from the contact form
     * @param string $displayName
     */
    public function setFromName($displayName)
    {
        $filterAlpha = new Zend_Filter_Alpha(true);
        $this->emailFromName = $filterAlpha->filter($displayName);
    }

    /**
     * Appends to the email subject line
     * @param type $subject
     */
    public function setSubject($subject)
    {
        $filterTags = new Zend_Filter_StripTags();
        $this->emailSubject .= $filterTags->filter($subject);
    }

    public function setBody($body)
    {
        $filterTags = new Zend_Filter_StripTags();
        $this->emailBody = $filterTags->filter($body);
    }

    public function appendToBody($string)
    {
        $filterTags = new Zend_Filter_StripTags();
        $this->emailBody .= $filterTags->filter($string) . '<br />';
    }

    public function setReplyTo($replyTo)
    {
        $emailValidator = new Zend_Validate_EmailAddress(array('mx' => true, 'deep' => true));
        $validator = new Zend_Validate();

        if($validator->addValidator($emailValidator)->isValid($replyTo)) {
            $this->emailReplyTo = $replyTo;
        } else {
            logger::addError(implode(' - ', $validator->getMessages()));
        }
    }

    public function validate()
    {
        if(is_null($this->emailBody)) {
            logger::addError('Please enter a message body.');
        }

        if(is_null($this->emailReplyTo)) {
            logger::addError('Please enter a valid email address we can use to contact you on.');
        }

        if(is_null($this->emailSubject)) {
            logger::addError('Please enter a subject.');
        }

        return (logger::hasErrors()) ? false : true;
    }

    public function send()
    {
        if($this->validate()) {

            $this->smtpMail->setFrom($this->emailConfigUser, $this->emailFromName);
            $this->smtpMail->addTo($this->emailTo, $this->emailToName);
            $this->smtpMail->setSubject($this->emailSubject);
            $this->smtpMail->setBodyText($this->emailBody);
            $this->smtpMail->send($this->smtpTransport);

            logger::addSuccess('Your message has been received, one of our team will contact you as soon as possible.');

            return true;
        } else {
            return false;
        }
    }
}
