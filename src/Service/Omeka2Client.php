<?php
/**
 * Adapted from https://github.com/jimsafley/ZendService_Omeka/blob/master/library/ZendService/Omeka/Omeka.php
 */

namespace Omeka2Importer\Service;

use Zend\Http\Client;
use Zend\Http\Response;
use Zend\Http\Request;

class Omeka2Client
{
    /**
     * @var Zend\Http\Client
     */
    protected $httpClient;
    
    /**
     * @var array
     */
    protected $methods = array('get', 'post', 'put', 'delete');
    
    /**
     * @var array
     */
    protected $callbacks = array();
    
    /**
     * @var string
     */
    protected $key;
    
    /**
     * @var string
     */
    protected $apiBaseUrl;
    
    /**
     * @var string
     */
    protected $resource;
    
    /**
     * @var int
     */
    protected $id;
    
    /**
     * Proxy resources
     * 
     * @param string $resource
     * @return Omeka
     */
    public function __get($resource)
    {
        $this->resource = $resource;
        return $this;
    }
    
    /**
     * Method overloading
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!in_array($method, $this->methods)) {
            throw new \Exception('Invalid method.');
        }
        // Check for a callback.
        if (array_key_exists($this->resource, $this->callbacks) 
            && array_key_exists($method, $this->callbacks[$this->resource])
        ) {
            $callback = $this->callbacks[$this->resource][$method];
            // Prepend this Omeka client to the argument list.
            array_unshift($args, $this);
            return call_user_func_array($callback, $args);
        }
        return call_user_func_array(array($this, $method), $args);
    }
    
    /**
     * Set custom behavior for a resource/method.
     * 
     * @param string $resource
     * @param string $method
     * @param \Closure $callback
     */
    public function setCallback($resource, $method, \Closure $callback)
    {
        $this->callbacks[$resource][$method] = $callback;
    }
    
    /**
     * Get the HTTP client.
     *
     * @return Http\Client
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Client;
        }
        return $this->httpClient;
    }
    
    /**
     * Get the API base URL.
     * 
     * @return string
     */
    public function getApiBaseUrl()
    {
        return $this->apiBaseUrl;
    }
    
    public function setApiBaseUrl($endpoint)
    {
        $this->apiBaseUrl = $endpoint;
    }
    
    /**
     * Set the authentication key.
     * 
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    /**
     * Get the authentication key.
     * 
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Make a GET request.
     * 
     * Setting the first argument as an integer will make a request for one 
     * resource, while not setting the first argument (or setting it as 
     * an array of parameters) will make a request for multiple resources.
     * 
     * @param integer|array $id
     * @param array $params
     * @return Zend\Http\Response
     */
    protected function get($id = null, array $params = array())
    {
        if (is_array($id)) {
            $params = $id;
        } else {
            $this->id = $id;
        }
        $client = $this->prepare(Request::METHOD_GET, $params);
        return $client->send();
    }
    
    /**
     * Make a POST request.
     * 
     * @param string $data
     * @param array $params
     * @return Zend\Http\Response
     */
    protected function post($data, array $params = array())
    {
        $client = $this->prepare(Http\Request::METHOD_POST, $params)
            ->setRawBody($data);
        return $client->send();
    }
    
    /**
     * Make a PUT request.
     * 
     * @param integer $id
     * @param string $data
     * @param array $params
     * @return Zend\Http\Response
     */
    protected function put($id, $data, array $params = array())
    {
        $this->id = $id;
        $client = $this->prepare(Http\Request::METHOD_PUT, $params)
            ->setRawBody($data);
        return $client->send();
    }
    
    /**
     * Make a DELETE request.
     * 
     * @param integer $id
     * @param string $data
     * @param array $params
     * @return Zend\Http\Response
     */
    protected function delete($id, array $params = array())
    {
        $this->id = $id;
        $client = $this->prepare(Http\Request::METHOD_DELETE, $params);
       return $client->send();
    }
    
    /**
     * Prepare and return the API client.
     * 
     * @param string $method
     * @param array $params
     * @return Zend\Http\Client
     */
    protected function prepare($method, array $params = array())
    {
        if (!$this->resource) {
            throw new \Exception('A resource must be set before making a request.');
        }
        $path = '/' . $this->resource;
        if ($this->id) {
            $path = $path . '/' . $this->id;
        }
        $client = $this->getHttpClient()
            ->resetParameters()
            ->setUri($this->apiBaseUrl . $path)
            ->setMethod($method);
        echo $client->getUri();
        if ($this->key) {
            $params = array_merge($params, array('key' => $this->key));
        }
        $client->setParameterGet($params);
        return $client;
    }
}
