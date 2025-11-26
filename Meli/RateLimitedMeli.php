<?php

require_once 'meli.php';

/**
 * RateLimitedMeli - Extended Meli class with Rate Limiting support
 * 
 * This class extends the base Meli SDK to add automatic rate limiting,
 * preventing HTTP 429 (Too Many Requests) errors from MercadoLibre API.
 * 
 * Usage:
 * ```php
 * $meli = new RateLimitedMeli('app_id', 'secret');
 * $meli->setRateLimit(50, 60); // 50 requests per 60 seconds
 * 
 * // Now all requests will be automatically rate-limited
 * for ($i = 0; $i < 100; $i++) {
 *     $result = $meli->get('/items/MLB123');
 *     // Automatically throttles after 50 requests
 * }
 * ```
 * 
 * @version 2.1.0
 * @author MercadoLibre Developers Team
 */
class RateLimitedMeli extends Meli {
    
    /**
     * @var array Timestamps of recent requests
     */
    private $requests = array();
    
    /**
     * @var int Maximum number of requests allowed in the time window
     */
    private $maxRequests = 50;
    
    /**
     * @var int Time window in seconds
     */
    private $windowSeconds = 60;
    
    /**
     * @var bool Enable/disable rate limiting
     */
    private $enabled = true;
    
    /**
     * @var callable|null Optional callback called when rate limit is hit
     */
    private $onRateLimitCallback = null;
    
    /**
     * Configure rate limiting parameters
     * 
     * @param int $maxRequests Maximum requests allowed per window
     * @param int $windowSeconds Time window in seconds
     * @return void
     * 
     * @example
     * $meli->setRateLimit(100, 60); // 100 requests per minute
     * $meli->setRateLimit(300, 60); // 300 requests per minute (production)
     */
    public function setRateLimit($maxRequests, $windowSeconds) {
        if ($maxRequests <= 0) {
            throw new InvalidArgumentException('maxRequests must be greater than 0');
        }
        
        if ($windowSeconds <= 0) {
            throw new InvalidArgumentException('windowSeconds must be greater than 0');
        }
        
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }
    
    /**
     * Enable rate limiting
     * 
     * @return void
     */
    public function enableRateLimit() {
        $this->enabled = true;
    }
    
    /**
     * Disable rate limiting (use with caution in production)
     * 
     * @return void
     */
    public function disableRateLimit() {
        $this->enabled = false;
    }
    
    /**
     * Set callback to be called when rate limit is hit
     * Useful for logging or custom throttling strategies
     * 
     * @param callable $callback Function called with ($waitTime, $requestCount, $maxRequests)
     * @return void
     * 
     * @example
     * $meli->setOnRateLimitCallback(function($waitTime, $count, $max) {
     *     error_log("Rate limit hit: $count/$max requests. Waiting {$waitTime}s");
     * });
     */
    public function setOnRateLimitCallback($callback) {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback must be callable');
        }
        $this->onRateLimitCallback = $callback;
    }
    
    /**
     * Get current rate limit statistics
     * 
     * @return array Statistics with 'requests_made', 'max_requests', 'window_seconds', 'requests_remaining'
     */
    public function getRateLimitStats() {
        $now = time();
        
        // Clean old requests
        $this->cleanOldRequests($now);
        
        $requestsMade = count($this->requests);
        $requestsRemaining = max(0, $this->maxRequests - $requestsMade);
        
        return array(
            'requests_made' => $requestsMade,
            'max_requests' => $this->maxRequests,
            'window_seconds' => $this->windowSeconds,
            'requests_remaining' => $requestsRemaining,
            'enabled' => $this->enabled
        );
    }
    
    /**
     * Override execute() to add rate limiting
     * 
     * @param string $path
     * @param array $opts
     * @param array $params
     * @param boolean $assoc
     * @return array
     */
    public function execute($path, $opts = array(), $params = array(), $assoc = false) {
        // Enforce rate limit before making request
        if ($this->enabled) {
            $this->enforceRateLimit();
        }
        
        // Call parent execute (with all Sprint 1 & 2 improvements)
        return parent::execute($path, $opts, $params, $assoc);
    }
    
    /**
     * Enforce rate limiting - wait if necessary
     * 
     * @return void
     */
    private function enforceRateLimit() {
        $now = time();
        
        // Remove requests outside the time window
        $this->cleanOldRequests($now);
        
        // Check if we've hit the limit
        if (count($this->requests) >= $this->maxRequests) {
            $oldestRequest = min($this->requests);
            $waitTime = $this->windowSeconds - ($now - $oldestRequest);
            
            if ($waitTime > 0) {
                // Call user callback if set
                if ($this->onRateLimitCallback !== null) {
                    call_user_func(
                        $this->onRateLimitCallback,
                        $waitTime,
                        count($this->requests),
                        $this->maxRequests
                    );
                }
                
                // Wait until the oldest request falls outside the window
                sleep($waitTime + 1);
                
                // Clear old requests after waiting
                $this->cleanOldRequests(time());
            }
        }
        
        // Register this request
        $this->requests[] = time();
    }
    
    /**
     * Remove requests older than the time window
     * 
     * @param int $now Current timestamp
     * @return void
     */
    private function cleanOldRequests($now) {
        $windowStart = $now - $this->windowSeconds;
        
        $this->requests = array_filter($this->requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Re-index array after filtering
        $this->requests = array_values($this->requests);
    }
    
    /**
     * Reset rate limit counters
     * Useful for testing or manual control
     * 
     * @return void
     */
    public function resetRateLimit() {
        $this->requests = array();
    }
}

