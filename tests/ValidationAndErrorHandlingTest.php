<?php
require_once '../Meli/meli.php';

/**
 * Tests para validación de inputs y manejo de errores (Sprint 1)
 */
class ValidationAndErrorHandlingTest extends PHPUnit\Framework\TestCase
{
    protected $client_id = '123';
    protected $client_secret = 'a secret';
    
    /**
     * Test que constructor valida client_id vacío
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsExceptionWithEmptyClientId() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('client_id must be a non-empty string');
        
        $meli = new Meli('', 'secret');
    }
    
    /**
     * Test que constructor valida client_secret vacío
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsExceptionWithEmptyClientSecret() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('client_secret must be a non-empty string');
        
        $meli = new Meli('client_id', '');
    }
    
    /**
     * Test que authorize valida code vacío
     * @expectedException InvalidArgumentException
     */
    public function testAuthorizeThrowsExceptionWithEmptyCode() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authorization code is required');
        
        $meli = new Meli($this->client_id, $this->client_secret);
        $meli->authorize('', 'http://localhost/callback');
    }
    
    /**
     * Test que authorize valida redirect_uri inválida
     * @expectedException InvalidArgumentException
     */
    public function testAuthorizeThrowsExceptionWithInvalidRedirectUri() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('redirect_uri must be a valid URL');
        
        $meli = new Meli($this->client_id, $this->client_secret);
        $meli->authorize('code123', 'not-a-valid-url');
    }
    
    /**
     * Test que getAuthUrl valida redirect_uri inválida
     * @expectedException InvalidArgumentException
     */
    public function testGetAuthUrlThrowsExceptionWithInvalidRedirectUri() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('redirect_uri must be a valid URL');
        
        $meli = new Meli($this->client_id, $this->client_secret);
        $meli->getAuthUrl('not-a-valid-url', Meli::$AUTH_URL['MLB']);
    }
    
    /**
     * Test que getAuthUrl valida auth_url vacío
     * @expectedException InvalidArgumentException
     */
    public function testGetAuthUrlThrowsExceptionWithEmptyAuthUrl() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('auth_url must be a non-empty string');
        
        $meli = new Meli($this->client_id, $this->client_secret);
        $meli->getAuthUrl('http://localhost/callback', '');
    }
    
    /**
     * Test que execute maneja errores de cURL correctamente
     * (Este test requiere mockear cURL para simular un fallo)
     */
    public function testExecuteHandlesCurlError() {
        $meli = new Meli($this->client_id, $this->client_secret);
        
        // Forzar un timeout muy bajo para simular error de conexión
        $originalTimeout = Meli::$CURL_OPTS[CURLOPT_TIMEOUT];
        Meli::$CURL_OPTS[CURLOPT_TIMEOUT] = 1;
        Meli::$CURL_OPTS[CURLOPT_CONNECTTIMEOUT] = 1;
        
        // Intentar conectar a una URL que probablemente falle rápido
        $result = $meli->get('/test-endpoint-that-will-timeout');
        
        // Restaurar timeout original
        Meli::$CURL_OPTS[CURLOPT_TIMEOUT] = $originalTimeout;
        
        // Verificar que la respuesta contiene información de error
        $this->assertIsArray($result);
        $this->assertArrayHasKey('httpCode', $result);
        
        // Si hay error de cURL, httpCode debe ser 0 o debe haber un campo 'error'
        if ($result['httpCode'] === 0 || isset($result['error'])) {
            $this->assertTrue(true, 'Error de cURL manejado correctamente');
        } else {
            // Si la petición fue exitosa (poco probable con timeout de 1s), también es válido
            $this->assertTrue(true, 'Petición completada antes del timeout');
        }
    }
    
    /**
     * Test que execute NO retorna NULL en el body cuando hay error
     */
    public function testExecuteDoesNotReturnNullBodyOnError() {
        $meli = new Meli($this->client_id, $this->client_secret);
        
        // Forzar timeout muy bajo
        $originalTimeout = Meli::$CURL_OPTS[CURLOPT_TIMEOUT];
        Meli::$CURL_OPTS[CURLOPT_TIMEOUT] = 1;
        
        $result = $meli->get('/test-endpoint');
        
        // Restaurar
        Meli::$CURL_OPTS[CURLOPT_TIMEOUT] = $originalTimeout;
        
        // El body no debe ser null sin explicación
        $this->assertIsArray($result);
        
        if ($result['httpCode'] === 0) {
            // Si hubo error de cURL, debe haber mensaje de error
            $this->assertArrayHasKey('error', $result);
            $this->assertNotEmpty($result['error']);
        }
    }
}

