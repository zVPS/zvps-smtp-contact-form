<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage ReCaptcha
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** @see Zend_Service_Abstract */
require_once 'Zend/Service/Abstract.php';

/** @see Zend_Json */
require_once 'Zend/Json.php';

/** @see Zend_Service_ReCaptcha_Response */
require_once 'Zend/Service/ReCaptcha/Response.php';

/**
 * Zend_Service_ReCaptcha
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage ReCaptcha
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class Zend_Service_ReCaptcha extends Zend_Service_Abstract
{
    /**
     * URI to the secure API
     *
     * @var string
     */
    const API_SERVER = 'https://www.google.com/recaptcha/api';

    /**
     * URI to the verify server
     *
     * @var string
     */
    const VERIFY_SERVER = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Public key used when displaying the captcha
     *
     * @var string
     */
    protected $_publicKey = null;

    /**
     * Private key used when verifying user input
     *
     * @var string
     */
    protected $_privateKey = null;

    /**
     * Ip address used when verifying user input
     *
     * @var string
     */
    protected $_ip = null;

    /**
     * Parameters for the object
     *
     * @var array
     */
    protected $_params = array(
        //Optional. The name of your callback function to be executed once all the dependencies have loaded.
        'onload' => null, //your-function-here
        //Optional. Whether to render the widget explicitly. Defaults to onload, which will render the widget in the first g-recaptcha tag it finds.
        'render' => null, //onload|explicit
        //See language codes	Optional. Forces the widget to render in a specific language. Auto-detects the user's language if unspecified.
        'hl' => null,
    );

    /**
     * Options for tailoring reCaptcha
     *
     * See the different options on https://developers.google.com/recaptcha/docs/display
     *
     * @var array
     */
    protected $_options = array(
        //Optional. The color theme of the widget.
        'data-theme'            => 'light', //dark|light
        //Optional. The size of the widget.
        'data-size'             => 'normal', //compact|normal 
        //Optional. The tabindex of the widget and challenge. If other elements in your page use tabindex, it should be set to make user navigation easier.
        'data-tabindex'         => 0,
        //Optional. The name of your callback function, executed when the user submits a successful response. The g-recaptcha-response token is passed to your callback.
        'data-callback'         => array(),
        //Optional. The name of your callback function, executed when the reCAPTCHA response expires and the user needs to re-verify.
        'data-expired-callback' => array(),
        //Optional. The name of your callback function, executed when reCAPTCHA encounters an error (usually network connectivity) and cannot continue until connectivity is restored. If you specify a function here, you are responsible for informing the user that they should retry.
        'data-error-callback'   => array(),
    );

    /**
     * Response from the verify server
     *
     * @var Zend_Service_ReCaptcha_Response
     */
    protected $_response = null;

    /**
     * Class constructor
     *
     * @param string $publicKey
     * @param string $privateKey
     * @param array $params
     * @param array $options
     * @param string $ip
     * @param array|Zend_Config $params
     */
    public function __construct($publicKey = null, $privateKey = null, $params = null, $options = null, $ip = null)
    {
        if ($publicKey !== null) {
            $this->setPublicKey($publicKey);
        }

        if ($privateKey !== null) {
            $this->setPrivateKey($privateKey);
        }

        if ($ip !== null) {
            $this->setIp($ip);
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->setIp($_SERVER['REMOTE_ADDR']);
        }

        if ($params !== null) {
            $this->setParams($params);
        }

        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    /**
     * Serialize as string
     *
     * When the instance is used as a string it will display the recaptcha.
     * Since we can't throw exceptions within this method we will trigger
     * a user warning instead.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $return = $this->getHtml();
        } catch (Exception $e) {
            $return = '';
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        return $return;
    }

    /**
     * Set the ip property
     *
     * @param string $ip
     * @return Zend_Service_ReCaptcha
     */
    public function setIp($ip)
    {
        $this->_ip = $ip;

        return $this;
    }

    /**
     * Get the ip property
     *
     * @return string
     */
    public function getIp()
    {
        return $this->_ip;
    }

    /**
     * Set a single parameter
     *
     * @param string $key
     * @param string $value
     * @return Zend_Service_ReCaptcha
     */
    public function setParam($key, $value)
    {
        $this->_params[$key] = $value;

        return $this;
    }

    /**
     * Set parameters
     *
     * @param array|Zend_Config $params
     * @return Zend_Service_ReCaptcha
     * @throws Zend_Service_ReCaptcha_Exception
     */
    public function setParams($params)
    {
        if ($params instanceof Zend_Config) {
            $params = $params->toArray();
        }

        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->setParam($k, $v);
            }
        } else {
            /** @see Zend_Service_ReCaptcha_Exception */
            require_once 'Zend/Service/ReCaptcha/Exception.php';

            throw new Zend_Service_ReCaptcha_Exception(
                'Expected array or Zend_Config object'
            );
        }

        return $this;
    }

    /**
     * Get the parameter array
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Get a single parameter
     *
     * @param string $key
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->_params[$key];
    }

    /**
     * Set a single option
     *
     * @param string $key
     * @param string $value
     * @return Zend_Service_ReCaptcha
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;

        return $this;
    }

    /**
     * Set options
     *
     * @param array|Zend_Config $options
     * @return Zend_Service_ReCaptcha
     * @throws Zend_Service_ReCaptcha_Exception
     */
    public function setOptions($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if (is_array($options)) {
            foreach ($options as $k => $v) {
                $this->setOption($k, $v);
            }
        } else {
            /** @see Zend_Service_ReCaptcha_Exception */
            require_once 'Zend/Service/ReCaptcha/Exception.php';

            throw new Zend_Service_ReCaptcha_Exception(
                'Expected array or Zend_Config object'
            );
        }

        return $this;
    }

    /**
     * Get the options array
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get a single option
     *
     * @param string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->_options[$key];
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->_publicKey;
    }

    /**
     * Set the public key
     *
     * @param string $publicKey
     * @return Zend_Service_ReCaptcha
     */
    public function setPublicKey($publicKey)
    {
        $this->_publicKey = $publicKey;

        return $this;
    }

    /**
     * Get the private key
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->_privateKey;
    }

    /**
     * Set the private key
     *
     * @param string $privateKey
     * @return Zend_Service_ReCaptcha
     */
    public function setPrivateKey($privateKey)
    {
        $this->_privateKey = $privateKey;

        return $this;
    }

    /**
     * Get the HTML code for the captcha
     *
     * This method uses the public key to fetch a recaptcha form.
     *
     * @param  null|string $name Base name for recaptcha form elements
     * @return string
     * @throws Zend_Service_ReCaptcha_Exception
     */
    public function getHtml($name = null)
    {
        if ($this->_publicKey === null) {
            /** @see Zend_Service_ReCaptcha_Exception */
            require_once 'Zend/Service/ReCaptcha/Exception.php';

            throw new Zend_Service_ReCaptcha_Exception('Missing public key');
        }

        $host = self::API_SERVER;

        $paramsEncoded = '';

        if (!empty($this->_options)) {
            $paramsEncoded = http_build_query($this->_params);
        }

        /** @todo finish adding options to the new html div via the options array */
        $optionsEncoded = '';

        if (!empty($this->_options)) {
            $optionsEncoded = Zend_Json::encode($this->_options);
        }
        
        $script = <<<SCRIPT
<script src="{$host}.js?{$paramsEncoded}" async defer></script>
SCRIPT;
                

        $html = <<<HTML
<div class="g-recaptcha" data-sitekey="{$this->_publicKey}"></div>
HTML;
            
        return $script . $html;
    }

    /**
     * Post a solution to the verify server
     *
     * @param string $challengeField
     * @param string $responseField
     * @return Zend_Http_Response
     * @throws Zend_Service_ReCaptcha_Exception
     */
    protected function _post($responseField)
    {
        if ($this->_privateKey === null) {
            /** @see Zend_Service_ReCaptcha_Exception */
            require_once 'Zend/Service/ReCaptcha/Exception.php';

            throw new Zend_Service_ReCaptcha_Exception('Missing private key');
        }

        if ($this->_ip === null) {
            /** @see Zend_Service_ReCaptcha_Exception */
            require_once 'Zend/Service/ReCaptcha/Exception.php';

            throw new Zend_Service_ReCaptcha_Exception('Missing ip address');
        }

        /* Fetch an instance of the http client */
        $httpClient = self::getHttpClient();
        $httpClient->resetParameters(true);

        $postParams = array(
            'secret' => $this->_privateKey,
            'remoteip'   => $this->_ip,
            'response'   => $responseField
        );

        /* Make the POST and return the response */
        return $httpClient->setUri(self::VERIFY_SERVER)
                          ->setParameterPost($postParams)
                          ->request(Zend_Http_Client::POST);
    }

    /**
     * Verify the user input
     *
     * This method calls up the post method and returns a
     * Zend_Service_ReCaptcha_Response object.
     *
     * @param string $challengeField
     * @param string $responseField
     * @return Zend_Service_ReCaptcha_Response
     */
    public function verify($responseField)
    {
        $response = $this->_post($responseField);

        return new Zend_Service_ReCaptcha_Response(null, null, $response);
    }
}
