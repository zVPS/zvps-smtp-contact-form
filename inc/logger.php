<?php

/**
 * Logs errors and helps with handling error cases.
 * @author kevin@zvps.co.uk
 */
class logger
{
    
    private static $messages = array('error' => array(), 'success' => array());
    
    public static function addError($message)
    {
        self::$messages['error'][] = $message;
    }
    
    public static function addSuccess($message)
    {
        self::$messages['success'][] = $message;
    }
    
    public static function hasErrors()
    {
        return (count(self::$messages['error']) >= 1) ? true : false;
    }
    
    public static function hasSuccess()
    {
        return (count(self::$messages['success']) >= 1) ? true : false;
    }
    
    public static function hasMessages()
    {
        return (self::hasErrors() || self::hasSuccess()) ? true : false;
    }
    
    public static function getErrors()
    {
        return self::$messages['error'];
    }
    
    public static function getSuccess()
    {
        return self::$messages['success'];
    }
}
