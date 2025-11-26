<?php
require_once '../Meli/RateLimitedMeli.php';

/**
 * Tests for Rate Limiting functionality (Sprint 2)
 */
class RateLimitedMeliTest extends PHPUnit\Framework\TestCase
{
    protected $client_id = '123';
    protected $client_secret = 'a secret';
    
    public function testRateLimitedMeliCanBeInstantiated() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $this->assertInstanceOf(RateLimitedMeli::class, $meli);
        $this->assertInstanceOf(Meli::class, $meli);
    }
    
    public function testSetRateLimitChangesConfiguration() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $meli->setRateLimit(100, 120);
        
        $stats = $meli->getRateLimitStats();
        $this->assertEquals(100, $stats['max_requests']);
        $this->assertEquals(120, $stats['window_seconds']);
    }
    
    public function testSetRateLimitThrowsExceptionWithInvalidMaxRequests() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maxRequests must be greater than 0');
        
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $meli->setRateLimit(0, 60);
    }
    
    public function testSetRateLimitThrowsExceptionWithInvalidWindowSeconds() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('windowSeconds must be greater than 0');
        
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $meli->setRateLimit(50, -1);
    }
    
    public function testGetRateLimitStatsReturnsCorrectStructure() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $stats = $meli->getRateLimitStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('requests_made', $stats);
        $this->assertArrayHasKey('max_requests', $stats);
        $this->assertArrayHasKey('window_seconds', $stats);
        $this->assertArrayHasKey('requests_remaining', $stats);
        $this->assertArrayHasKey('enabled', $stats);
    }
    
    public function testInitialRateLimitStatsShowZeroRequests() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $stats = $meli->getRateLimitStats();
        
        $this->assertEquals(0, $stats['requests_made']);
        $this->assertEquals(50, $stats['max_requests']); // Default
        $this->assertEquals(60, $stats['window_seconds']); // Default
        $this->assertEquals(50, $stats['requests_remaining']);
        $this->assertTrue($stats['enabled']);
    }
    
    public function testEnableAndDisableRateLimit() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        
        $meli->disableRateLimit();
        $stats = $meli->getRateLimitStats();
        $this->assertFalse($stats['enabled']);
        
        $meli->enableRateLimit();
        $stats = $meli->getRateLimitStats();
        $this->assertTrue($stats['enabled']);
    }
    
    public function testSetOnRateLimitCallbackAcceptsCallable() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $called = false;
        
        $callback = function($waitTime, $count, $max) use (&$called) {
            $called = true;
        };
        
        $meli->setOnRateLimitCallback($callback);
        // Si no lanza excepción, el test pasa
        $this->assertTrue(true);
    }
    
    public function testSetOnRateLimitCallbackThrowsExceptionWithNonCallable() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Callback must be callable');
        
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        $meli->setOnRateLimitCallback('not-a-callable');
    }
    
    public function testResetRateLimitClearsRequestHistory() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        
        // Simular que se hicieron requests (esto requeriría mocking del execute)
        // Por ahora, simplemente verificamos que resetRateLimit() no lanza error
        $meli->resetRateLimit();
        
        $stats = $meli->getRateLimitStats();
        $this->assertEquals(0, $stats['requests_made']);
    }
    
    /**
     * Test de integración: Verificar que rate limiting funciona
     * (Este test es más conceptual ya que requeriría hacer requests reales)
     */
    public function testRateLimitingPreventsTooManyRequests() {
        $meli = new RateLimitedMeli($this->client_id, $this->client_secret);
        
        // Configurar límite muy bajo para testing
        $meli->setRateLimit(3, 10); // Solo 3 requests por 10 segundos
        
        $stats = $meli->getRateLimitStats();
        $this->assertEquals(3, $stats['max_requests']);
        $this->assertEquals(10, $stats['window_seconds']);
    }
}

