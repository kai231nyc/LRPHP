<?php
// @package LR-PHP
// @copyright 2011 Jeffrey Hill
// @license Apache 2.0 License http://www.apache.org/licenses/LICENSE-2.0.html

if(!defined('DS')) define('DS', '/');
define('LREXEC',1);

class LR
{
    protected static $args;
    protected static $ch;
    protected static $debug = false;
    protected static $errors = array();
    protected static $service;
    protected static $request;
    protected static $response;
    
    private function __construct() {}
    private function __clone() {}
    
    public static function debug()
    {
        self::$debug = true;
    }
    
    public static function getErrors()
    {
        return self::$errors;
    }
    
    /**
     * Private function used to send requests to LR Node using cURL
     *
     * @param string $type - GET/POST/PUT/DELETE
     * @return void  
     */
    public static function execute() {
        // Initialize cURL handler
        self::$ch = curl_init();
        $url = LRConfig::URL.DS.self::getServiceName();
        curl_setopt (self::$ch, CURLOPT_POST, true);
        if(self::getAction() == "POST")
        {
            if(self::$service->getVerb())
            {
                $url .= DS.self::$service->getVerb();
            }
            $args = json_encode(self::getArgs());
            curl_setopt (self::$ch, CURLOPT_POSTFIELDS, $args);
            echo $args;
        }
        if(self::getAction() == "GET")
        {
            // TODO: str_replace not necessary in PHP 5.3+ experimental
            $url .= "?".str_replace('+','%20',http_build_query(self::getArgs()));
        }
        echo $url;
        curl_setopt (self::$ch, CURLOPT_URL, $url);
        curl_setopt (self::$ch, CURLOPT_HTTPHEADER, array("Content-type: application/json","Content-length: 0"));
        curl_setopt (self::$ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt (self::$ch, CURLOPT_TIMEOUT, 7200);
        self::$service->data = curl_exec(self::$ch);
        if(self::$service->data === false)
        {
            self::setError('cURL_exec Error<br /><br />'.curl_error(self::$ch).'<br /><br />REQUEST:<textarea>'.$qs.'</textarea>');
        }
        return true;
    }
    
    public static function getAction()
    {
        return self::$service->action;
    }
    
    /**
     * Gets the current array of arguments prepared for the currently loaded LR Service
     * @return array
    */
    public static function getArgs()
    {
        return self::$service->args;
    }
    
    /**
     * Gets the response from LR Node
     * @return string json
    */
    public static function getResponse()
    {
        if(!self::$service->data)
        {
            self::setError('LR response not found');
        }
        return self::$service->data;
    }
    
    /**
     * Gets the LR object name currently loaded
     * @return string
    */
    public static function getServiceName()
    {
        if(!is_a(self::$service,'LRService'))
        {
            self::setError('Service not found');
        }
        return strtolower(substr(get_class(self::$service),2));
    }
    
    /**
     * Initializes a request for LR
     * @return true on success, Exception on failure
    */
    public static function init($service)
    {
        try {
            require_once('config.php');
            self::loadService($service);
        } catch (Exception $e) {
            $self::setError($e->getMessage());
        }
        return true;
    }
    
    /**
     * Loads an LR service representing an object type (i.e. node, document)
     * @return void
    */
    private static function loadService($service)
    {
        // Import LR service
        require_once('svc'.DS.'service.php');
        require_once('svc'.DS.$service.'.php');
        // TODO: Static calls for services in PHP 5.3
        $service = 'LR'.ucfirst($service);
        self::$service = new $service;
    }
    
    /**
     * Set arguments for cURL request, stored in the service instance
     * @param array $args
     * @return void
     */
    public static function setArgs($args)
    {
        self::$service->setArgs($args);
    }
    
    /**
     * Appends an error message
     * @param string $error
     * @return void
     */
    private static function setError($error)
    {
        self::$errors[] = $error;
    }
    
    public static function setVerb($verb)
    {
        if(!self::$service->setVerb($verb))
            self::setError('Verb not valid');
    }
    /**
     * Unset current service arguments.
     * @return void
     */
    public static function unsetArgs()
    {
        unset(self::$service->args);
    }
    
}
