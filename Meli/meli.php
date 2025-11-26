<?php

class Meli {

	/**
	 * @version 2.1.0
	 */
    const VERSION  = "2.1.0";

    /**
     * @var $API_ROOT_URL is a main URL to access the Meli API's.
     * @var $AUTH_URL is a url to redirect the user for login.
     */
    protected static $API_ROOT_URL = "https://api.mercadolibre.com";
    protected static $OAUTH_URL    = "/oauth/token";
    public static $AUTH_URL = array(
        "MLA" => "https://auth.mercadolibre.com.ar", // Argentina 
        "MLB" => "https://auth.mercadolivre.com.br", // Brasil
        "MCO" => "https://auth.mercadolibre.com.co", // Colombia
        "MCR" => "https://auth.mercadolibre.com.cr", // Costa Rica
        "MEC" => "https://auth.mercadolibre.com.ec", // Ecuador
        "MLC" => "https://auth.mercadolibre.cl", // Chile
        "MLM" => "https://auth.mercadolibre.com.mx", // Mexico
        "MLU" => "https://auth.mercadolibre.com.uy", // Uruguay
        "MLV" => "https://auth.mercadolibre.com.ve", // Venezuela
        "MPA" => "https://auth.mercadolibre.com.pa", // Panama
        "MPE" => "https://auth.mercadolibre.com.pe", // Peru
        "MPT" => "https://auth.mercadolibre.com.pt", // Prtugal
        "MRD" => "https://auth.mercadolibre.com.do",  // Dominicana
        "CBT" => "https://global-selling.mercadolibre.com"  // CBT
    );

    /**
     * Configuration for CURL
     */
    public static $CURL_OPTS = array(
        CURLOPT_USERAGENT => "MELI-PHP-SDK-2.1.0", 
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 10, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_TIMEOUT => 60
    );

    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $access_token;
    protected $refresh_token;
    
    /**
     * @var resource|null Static cURL handle for connection reuse (Connection Pooling)
     */
    private $curlHandle = null;

    /**
     * Constructor method. Set all variables to connect in Meli
     *
     * @param string $client_id
     * @param string $client_secret
     * @param string $access_token
     * @param string $refresh_token
     * @throws InvalidArgumentException if client_id or client_secret are invalid
     */
    public function __construct($client_id, $client_secret, $access_token = null, $refresh_token = null) {
        // Validate client_id
        if (empty($client_id) || !is_string($client_id)) {
            throw new InvalidArgumentException('client_id must be a non-empty string');
        }
        
        // Validate client_secret
        if (empty($client_secret) || !is_string($client_secret)) {
            throw new InvalidArgumentException('client_secret must be a non-empty string');
        }
        
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
    }

    /**
     * Return an string with a complete Meli login url.
     * NOTE: You can modify the $AUTH_URL to change the language of login
     * 
     * @param string $redirect_uri
     * @param string $auth_url
     * @return string
     * @throws InvalidArgumentException if redirect_uri or auth_url are invalid
     */
    public function getAuthUrl($redirect_uri, $auth_url) {
        // Validate redirect_uri
        if (!filter_var($redirect_uri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('redirect_uri must be a valid URL');
        }
        
        // Validate auth_url
        if (empty($auth_url) || !is_string($auth_url)) {
            throw new InvalidArgumentException('auth_url must be a non-empty string');
        }
        
        $this->redirect_uri = $redirect_uri;
        $params = array("client_id" => $this->client_id, "response_type" => "code", "redirect_uri" => $redirect_uri);
        $auth_uri = $auth_url."/authorization?".http_build_query($params);
        return $auth_uri;
    }

    /**
     * Executes a POST Request to authorize the application and take
     * an AccessToken.
     * 
     * @param string $code
     * @param string $redirect_uri
     * @return array
     * @throws InvalidArgumentException if code or redirect_uri are invalid
     */
    public function authorize($code, $redirect_uri) {
        // Validate authorization code
        if (empty($code) || !is_string($code)) {
            throw new InvalidArgumentException('Authorization code is required and must be a non-empty string');
        }
        
        // Validate redirect_uri if provided
        if ($redirect_uri && !filter_var($redirect_uri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('redirect_uri must be a valid URL');
        }

        if($redirect_uri)
            $this->redirect_uri = $redirect_uri;

        $body = array(
            "grant_type" => "authorization_code", 
            "client_id" => $this->client_id, 
            "client_secret" => $this->client_secret, 
            "code" => $code, 
            "redirect_uri" => $this->redirect_uri
        );

        $opts = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body
        );
    
        $request = $this->execute(self::$OAUTH_URL, $opts);

        if($request["httpCode"] == 200) {             
            $this->access_token = $request["body"]->access_token;

            if($request["body"]->refresh_token)
                $this->refresh_token = $request["body"]->refresh_token;

            return $request;

        } else {
            return $request;
        }
    }

    /**
     * Execute a POST Request to create a new AccessToken from a existent refresh_token
     * 
     * @return string|mixed
     */
    public function refreshAccessToken() {

        if($this->refresh_token) {
             $body = array(
                "grant_type" => "refresh_token", 
                "client_id" => $this->client_id, 
                "client_secret" => $this->client_secret, 
                "refresh_token" => $this->refresh_token
            );

            $opts = array(
                CURLOPT_POST => true, 
                CURLOPT_POSTFIELDS => $body
            );
        
            $request = $this->execute(self::$OAUTH_URL, $opts);

            if($request["httpCode"] == 200) {             
                $this->access_token = $request["body"]->access_token;

                if($request["body"]->refresh_token)
                    $this->refresh_token = $request["body"]->refresh_token;

                return $request;

            } else {
                return $request;
            }   
        } else {
            $result = array(
                'error' => 'Offline-Access is not allowed.',
                'httpCode'  => null
            );
            return $result;
        }        
    }

    /**
     * Execute a GET Request
     * 
     * @param string $path
     * @param array $params
     * @param boolean $assoc
     * @return mixed
     */
    public function get($path, $params = null, $assoc = false) {
        $exec = $this->execute($path, null, $params, $assoc);

        return $exec;
    }

    /**
     * Execute a POST Request
     * 
     * @param string $body
     * @param array $params
     * @return mixed
     */
    public function post($path, $body = null, $params = array()) {
        $body = json_encode($body);
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POST => true, 
            CURLOPT_POSTFIELDS => $body
        );
        
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     * 
     * @param string $path
     * @param string $body
     * @param array $params
     * @return mixed
     */
    public function put($path, $body = null, $params = array()) {
        $body = json_encode($body);
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $body
        );
        
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     * 
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function delete($path, $params) {
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "DELETE"
        );
        
        $exec = $this->execute($path, $opts, $params);
        
        return $exec;
    }

    /**
     * Execute a OPTION Request
     * 
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function options($path, $params = null) {
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        );
        
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Get or create a reusable cURL handle (Connection Pooling - Sprint 2)
     * This improves performance by reusing SSL/TCP connections
     * 
     * @return resource cURL handle
     */
    private function getCurlHandle() {
        if ($this->curlHandle === null) {
            $this->curlHandle = curl_init();
            if ($this->curlHandle === false) {
                return false;
            }
        }
        return $this->curlHandle;
    }
    
    /**
     * Execute all requests and returns the json body and headers
     * Now with Connection Pooling for better performance (Sprint 2)
     * 
     * @param string $path
     * @param array $opts
     * @param array $params
     * @param boolean $assoc
     * @return array Response array with 'body', 'httpCode', and optionally 'error'
     */
    public function execute($path, $opts = array(), $params = array(), $assoc = false) {
        $uri = $this->make_path($path, $params);

        // Get reusable cURL handle (Connection Pooling)
        $ch = $this->getCurlHandle();
        
        // Check if cURL initialization failed
        if ($ch === false) {
            return array(
                'error' => 'Failed to initialize cURL session',
                'httpCode' => 0,
                'body' => null
            );
        }
        
        // Set URL for this specific request
        curl_setopt($ch, CURLOPT_URL, $uri);
        
        // Apply default options
        curl_setopt_array($ch, self::$CURL_OPTS);

        // Apply request-specific options
        if(!empty($opts))
            curl_setopt_array($ch, $opts);

        // Execute cURL request
        $response = curl_exec($ch);
        
        // Check for cURL errors
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        if ($curlErrno !== 0) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // Don't close handle here - will be reused
            
            return array(
                'error' => "cURL Error ($curlErrno): $curlError",
                'httpCode' => $httpCode ? $httpCode : 0,
                'body' => null
            );
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Don't close cURL handle - it will be reused (Connection Pooling)
        // It will be closed in __destruct()
        
        // Decode JSON response
        $decodedBody = json_decode($response, $assoc);
        
        // Check for JSON decode errors
        $jsonError = json_last_error();
        if ($jsonError !== JSON_ERROR_NONE && !empty($response)) {
            // Log JSON error but still return the response
            $jsonErrorMsg = function_exists('json_last_error_msg') ? json_last_error_msg() : "JSON Error code: $jsonError";
            error_log("Meli SDK - JSON decode error: $jsonErrorMsg. Response preview: " . substr($response, 0, 200));
            
            // Return raw response if JSON is invalid
            return array(
                'body' => $response,
                'httpCode' => $httpCode,
                'error' => "JSON decode error: $jsonErrorMsg"
            );
        }
        
        // Successful response
        $return = array(
            'body' => $decodedBody,
            'httpCode' => $httpCode
        );
        
        return $return;
    }
    
    /**
     * Destructor - Clean up cURL handle (Connection Pooling cleanup)
     */
    public function __destruct() {
        if ($this->curlHandle !== null) {
            curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }

    /**
     * Check and construct an real URL to make request
     * 
     * @param string $path
     * @param array $params
     * @return string
     */
    public function make_path($path, $params = array()) {
        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $uri = self::$API_ROOT_URL . $path;
        
        if(!empty($params)) {
            $paramsJoined = array();

            foreach($params as $param => $value) {
               $paramsJoined[] = "$param=$value";
            }
            $params = '?'.implode('&', $paramsJoined);
            $uri = $uri.$params;
        }

        return $uri;
    }
}
