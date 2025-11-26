# 🔧 Guía de Resolución de Problemas

Esta guía te ayudará a diagnosticar y resolver los problemas más comunes al usar el SDK de PHP para MercadoLibre.

---

## 📋 Tabla de Contenidos

1. [Problemas de Instalación](#problemas-de-instalación)
2. [Errores de Autenticación OAuth](#errores-de-autenticación-oauth)
3. [Errores de API](#errores-de-api)
4. [Problemas con cURL](#problemas-con-curl)
5. [Errores de Publicación](#errores-de-publicación)
6. [Problemas de Performance](#problemas-de-performance)
7. [Debugging Avanzado](#debugging-avanzado)

---

## Problemas de Instalación

### Error: "Call to undefined function curl_init()"

**Síntoma**:
```
Fatal error: Call to undefined function curl_init() in Meli/meli.php on line X
```

**Causa**: La extensión cURL de PHP no está instalada o habilitada.

**Solución**:

**Ubuntu/Debian**:
```bash
sudo apt-get update
sudo apt-get install php-curl
sudo service apache2 restart
# O si usas PHP-FPM
sudo service php7.4-fpm restart
```

**CentOS/RHEL**:
```bash
sudo yum install php-curl
sudo systemctl restart httpd
```

**macOS (con Homebrew)**:
```bash
brew install php
# cURL viene incluido por defecto
```

**Windows (XAMPP/WAMP)**:
1. Abre `php.ini`
2. Busca `;extension=curl`
3. Quita el `;` para descomentar: `extension=curl`
4. Reinicia Apache

**Verificar instalación**:
```bash
php -m | grep curl
# Debe mostrar: curl
```

---

### Error: "Class 'Meli' not found"

**Síntoma**:
```
Fatal error: Class 'Meli' not found in example.php on line X
```

**Causa**: No incluiste el archivo del SDK.

**Solución**:
```php
// Asegúrate de incluir el SDK
require_once 'Meli/meli.php';

// O con ruta absoluta
require_once __DIR__ . '/Meli/meli.php';

// Luego instanciar
$meli = new Meli($appId, $secretKey);
```

---

### Error: "Failed opening required 'Meli/meli.php'"

**Síntoma**:
```
Warning: require(Meli/meli.php): failed to open stream: No such file or directory
```

**Causa**: Ruta incorrecta al archivo.

**Solución**:
```php
// Verificar la estructura de carpetas
// Tu proyecto debe verse así:
// 
// mi-proyecto/
// ├── Meli/
// │   └── meli.php
// └── mi-script.php

// En mi-script.php:
require_once __DIR__ . '/Meli/meli.php';

// O navegar un nivel arriba si estás en subcarpeta:
require_once dirname(__DIR__) . '/Meli/meli.php';
```

**Debugging**:
```php
// Ver el directorio actual
echo "Current dir: " . __DIR__ . "\n";

// Ver si el archivo existe
$sdkPath = __DIR__ . '/Meli/meli.php';
if (file_exists($sdkPath)) {
    echo "SDK encontrado en: $sdkPath\n";
} else {
    echo "SDK NO encontrado. Buscando...\n";
    // Buscar archivo
    $cmd = "find . -name 'meli.php'";
    echo shell_exec($cmd);
}
```

---

## Errores de Autenticación OAuth

### Error: "invalid_grant"

**Síntoma**:
```json
{
  "error": "invalid_grant",
  "error_description": "The provided authorization grant is invalid, expired or revoked",
  "status": 400
}
```

**Causas posibles**:

1. **Código de autorización ya usado**:
```php
// ❌ Código ya usado
$auth = $meli->authorize($_GET['code'], $redirectUri);
// Refresh de página usa el mismo code
$auth = $meli->authorize($_GET['code'], $redirectUri);  // Error!
```

**Solución**: Redirige después de autorizar
```php
if (isset($_GET['code']) && !isset($_SESSION['access_token'])) {
    $auth = $meli->authorize($_GET['code'], $redirectUri);
    
    if ($auth['httpCode'] == 200) {
        $_SESSION['access_token'] = $auth['body']->access_token;
        
        // Redirigir para limpiar el code de la URL
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}
```

2. **Código expirado** (>10 minutos):

**Solución**: Genera uno nuevo
```php
$authUrl = $meli->getAuthUrl($redirectUri, Meli::$AUTH_URL['MLB']);
header("Location: $authUrl");
exit;
```

3. **Credenciales incorrectas**:

**Solución**: Verifica tus credenciales
```php
// Verificar que las credenciales sean correctas
echo "App ID: " . $appId . "\n";
echo "Secret (primeros 5 chars): " . substr($secretKey, 0, 5) . "...\n";

// Comparar con las del portal: https://developers.mercadolibre.com/apps/home
```

---

### Error: "redirect_uri_mismatch"

**Síntoma**:
```json
{
  "message": "redirect_uri_mismatch",
  "error": "invalid_request",
  "status": 400
}
```

**Causa**: El `redirect_uri` usado no coincide con el configurado en tu aplicación.

**Solución**:

1. **Ve a tu aplicación**: https://developers.mercadolibre.com/apps/home
2. **Edita la aplicación**
3. **En "Redirect URI"**, asegúrate que coincida EXACTAMENTE:

```php
// Si usas esto en tu código:
$redirectUri = 'http://localhost:8000/callback.php';

// Debes tener EXACTAMENTE esto en tu app de MercadoLibre:
// http://localhost:8000/callback.php
//
// NO funcionará:
// http://127.0.0.1:8000/callback.php  (localhost vs 127.0.0.1)
// http://localhost:8000/              (sin callback.php)
// https://localhost:8000/callback.php (https vs http)
```

**Debugging**:
```php
// Imprimir ambos redirect_uri para comparar
echo "Redirect URI usado: " . $redirectUri . "\n";

// Obtener el que está en la URL de autorización
$authUrl = $meli->getAuthUrl($redirectUri, Meli::$AUTH_URL['MLB']);
parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
echo "Redirect URI en authUrl: " . $params['redirect_uri'] . "\n";
```

---

### Error: "Malformed access_token"

**Síntoma**:
```json
{
  "message": "Malformed access_token",
  "error": "bad_request",
  "status": 400
}
```

**Causas posibles**:

1. **Token vacío o null**:
```php
// ❌ Token no existe
$result = $meli->get('/users/me', [
    'access_token' => $_SESSION['access_token']  // null
]);
```

**Solución**: Verificar antes de usar
```php
if (empty($_SESSION['access_token'])) {
    die('Debes autenticarte primero');
}

$result = $meli->get('/users/me', [
    'access_token' => $_SESSION['access_token']
]);
```

2. **Token cortado o mal copiado**:

**Solución**: Verificar formato
```php
$token = $_SESSION['access_token'];

// Un access_token válido se ve así:
// APP_USR-1234567890123456-112233-abc123xyz...
if (!preg_match('/^APP_USR-/', $token)) {
    die('Token inválido: ' . substr($token, 0, 20));
}
```

3. **Espacios en blanco**:
```php
// ❌ Token con espacios
$_SESSION['access_token'] = " APP_USR-123... ";

// ✅ Limpiar espacios
$_SESSION['access_token'] = trim($auth['body']->access_token);
```

---

### Token expirado (401)

**Síntoma**:
```json
{
  "message": "Token expired",
  "status": 401
}
```

**Solución**: Renovar con refresh_token
```php
// Detectar token expirado
$result = $meli->get('/users/me', ['access_token' => $_SESSION['access_token']]);

if ($result['httpCode'] == 401) {
    echo "Token expirado, renovando...\n";
    
    $meli = new Meli(
        $appId,
        $secretKey,
        $_SESSION['access_token'],
        $_SESSION['refresh_token']
    );
    
    $refresh = $meli->refreshAccessToken();
    
    if ($refresh['httpCode'] == 200) {
        $_SESSION['access_token'] = $refresh['body']->access_token;
        $_SESSION['refresh_token'] = $refresh['body']->refresh_token ?? $_SESSION['refresh_token'];
        
        // Reintentar petición original
        $result = $meli->get('/users/me', ['access_token' => $_SESSION['access_token']]);
    }
}
```

**Prevención**: Verificar expiración antes de usar
```php
// Al guardar el token, guardar también cuándo expira
$_SESSION['access_token'] = $auth['body']->access_token;
$_SESSION['expires_at'] = time() + $auth['body']->expires_in;

// Antes de cada petición
if (time() >= $_SESSION['expires_at']) {
    $meli->refreshAccessToken();
}
```

---

## Errores de API

### Error 400: Bad Request

**Síntoma**:
```json
{
  "message": "price is required",
  "status": 400,
  "cause": [
    {
      "code": "required",
      "message": "price is required"
    }
  ]
}
```

**Causa**: Parámetros faltantes o inválidos.

**Solución**: Validar datos antes de enviar
```php
function validateItem($item) {
    $required = ['title', 'category_id', 'price', 'currency_id', 'available_quantity'];
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($item[$field]) || empty($item[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('Campos faltantes: ' . implode(', ', $missing));
    }
    
    // Validaciones adicionales
    if ($item['price'] <= 0) {
        throw new Exception('price debe ser mayor a 0');
    }
    
    if (strlen($item['title']) > 60) {
        throw new Exception('title no puede exceder 60 caracteres');
    }
    
    return true;
}

// Usar
try {
    validateItem($item);
    $response = $meli->post('/items', $item, ['access_token' => $token]);
} catch (Exception $e) {
    echo "Error de validación: " . $e->getMessage();
}
```

---

### Error 403: Forbidden

**Síntoma**:
```json
{
  "message": "You don't have permission to access this resource",
  "status": 403
}
```

**Causas posibles**:

1. **Falta scope de permisos**:

**Solución**: Verificar permisos de tu app
- Ve a https://developers.mercadolibre.com/apps/home
- Edita tu aplicación
- En "Scopes", asegúrate de tener los permisos necesarios:
  - `read` - Leer información
  - `write` - Crear/modificar recursos
  - `offline_access` - Renovar tokens

2. **Intentando acceder a recursos de otro usuario**:
```php
// ❌ No puedes modificar items de otros usuarios
$response = $meli->put('/items/MLB999999', $updates, ['access_token' => $myToken]);
// Error 403 si MLB999999 no es tuyo
```

---

### Error 404: Not Found

**Síntoma**:
```json
{
  "message": "Item not found",
  "status": 404
}
```

**Solución**: Verificar que el recurso existe
```php
$itemId = 'MLB123456789';

$result = $meli->get("/items/$itemId");

if ($result['httpCode'] == 404) {
    echo "El item $itemId no existe o fue eliminado\n";
} elseif ($result['httpCode'] == 200) {
    echo "Item encontrado: " . $result['body']->title . "\n";
}
```

---

### Error 429: Too Many Requests

**Síntoma**:
```json
{
  "message": "Too many requests",
  "status": 429
}
```

**Causa**: Excediste el límite de peticiones por minuto.

**Solución inmediata**:
```php
$result = $meli->get('/users/me', $params);

if ($result['httpCode'] == 429) {
    echo "Rate limit excedido. Esperando 60 segundos...\n";
    sleep(60);
    
    // Reintentar
    $result = $meli->get('/users/me', $params);
}
```

**Solución preventiva**: Implementar rate limiting
```php
class RateLimiter {
    private $requests = [];
    private $maxRequests = 50;  // Por minuto
    private $window = 60;        // Segundos
    
    public function allowRequest() {
        $now = time();
        
        // Limpiar requests antiguos
        $this->requests = array_filter($this->requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });
        
        if (count($this->requests) >= $this->maxRequests) {
            $oldestRequest = min($this->requests);
            $waitTime = $this->window - ($now - $oldestRequest);
            
            echo "Rate limit. Esperando {$waitTime}s...\n";
            sleep($waitTime);
            
            $this->requests = [];
        }
        
        $this->requests[] = $now;
        return true;
    }
}

// Uso
$limiter = new RateLimiter();

foreach ($items as $itemId) {
    $limiter->allowRequest();
    $result = $meli->get("/items/$itemId");
    // Procesar...
}
```

---

## Problemas con cURL

### Error: "cURL error 60: SSL certificate problem"

**Síntoma**:
```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

**Causa**: Certificados SSL/TLS desactualizados o faltantes.

**Solución en producción** (actualizar certificados):

**Ubuntu/Debian**:
```bash
sudo apt-get update
sudo apt-get install --reinstall ca-certificates
```

**CentOS/RHEL**:
```bash
sudo yum reinstall ca-certificates
```

**macOS**:
```bash
brew install curl-ca-bundle
```

**Workaround temporal** (⚠️ SOLO PARA DESARROLLO):
```php
// En Meli/meli.php, modificar temporalmente:
public static $CURL_OPTS = array(
    CURLOPT_USERAGENT => "MELI-PHP-SDK-2.0.0",
    CURLOPT_SSL_VERIFYPEER => false,  // ⚠️ NO EN PRODUCCIÓN
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 60
);
```

---

### Error: "cURL error 28: Operation timed out"

**Síntoma**:
```
cURL error 28: Operation timed out after 60000 milliseconds
```

**Causa**: La petición tardó más del timeout configurado.

**Solución**: Aumentar timeout
```php
// Aumentar timeout a 120 segundos
Meli::$CURL_OPTS[CURLOPT_TIMEOUT] = 120;

$meli = new Meli($appId, $secretKey);
```

**Debugging**: Verificar conectividad
```bash
# Verificar que puedes alcanzar la API
curl -I https://api.mercadolibre.com

# Debería retornar:
# HTTP/2 200
```

---

### Error: "cURL error 7: Failed to connect"

**Síntoma**:
```
cURL error 7: Failed to connect to api.mercadolibre.com port 443
```

**Causas posibles**:

1. **Sin conexión a internet**:
```bash
ping api.mercadolibre.com
```

2. **Firewall bloqueando**:
```bash
telnet api.mercadolibre.com 443
# Debe conectar
```

3. **Proxy corporativo**:
```php
// Configurar proxy
Meli::$CURL_OPTS[CURLOPT_PROXY] = 'proxy.empresa.com:8080';
Meli::$CURL_OPTS[CURLOPT_PROXYUSERPWD] = 'usuario:password';

$meli = new Meli($appId, $secretKey);
```

---

## Errores de Publicación

### Error: "Category $id has no attributes"

**Síntoma**:
```json
{
  "message": "Category CBT1744 has no attributes",
  "status": 400
}
```

**Causa**: Categoría incorrecta o descatalogada.

**Solución**: Buscar categoría correcta
```php
// Obtener categoría sugerida automáticamente
$result = $meli->get('/sites/MLB/domain_discovery/search', [
    'q' => 'iPhone 14 Pro'
]);

foreach ($result['body'] as $prediction) {
    echo "Categoría recomendada: {$prediction->category_id} - {$prediction->category_name}\n";
    
    // Usar esta categoría
    $item['category_id'] = $prediction->category_id;
}
```

---

### Error: "Pictures must be an array of source or id"

**Síntoma**:
```json
{
  "message": "Pictures must be an array with source or id",
  "status": 400
}
```

**Causa**: Formato incorrecto de imágenes.

**Solución**:
```php
// ❌ INCORRECTO
$item['pictures'] = [
    'https://example.com/image1.jpg',
    'https://example.com/image2.jpg'
];

// ✅ CORRECTO
$item['pictures'] = [
    ['source' => 'https://example.com/image1.jpg'],
    ['source' => 'https://example.com/image2.jpg']
];

// O con IDs
$item['pictures'] = [
    ['id' => '123456-MLA'],
    ['id' => '789012-MLA']
];
```

---

### Error: "Title: invalid length"

**Síntoma**:
```json
{
  "message": "title: invalid length, must be between 1 and 60",
  "status": 400
}
```

**Solución**: Validar longitud
```php
function sanitizeTitle($title) {
    // Limpiar HTML
    $title = strip_tags($title);
    
    // Truncar a 60 caracteres
    if (strlen($title) > 60) {
        $title = substr($title, 0, 57) . '...';
    }
    
    // Mínimo 1 carácter
    if (empty($title)) {
        throw new Exception('El título no puede estar vacío');
    }
    
    return $title;
}

$item['title'] = sanitizeTitle($userInput);
```

---

## Problemas de Performance

### Las peticiones son muy lentas

**Debugging**: Medir tiempos
```php
$start = microtime(true);

$result = $meli->get('/users/me', ['access_token' => $token]);

$duration = microtime(true) - $start;
echo "Petición tardó: " . round($duration, 2) . " segundos\n";

// Si tarda >5 segundos, hay problema
```

**Soluciones**:

1. **Activar keep-alive**:
```php
Meli::$CURL_OPTS[CURLOPT_TCP_KEEPALIVE] = 1;
```

2. **Usar compresión**:
```php
Meli::$CURL_OPTS[CURLOPT_ENCODING] = 'gzip,deflate';
```

3. **Reducir timeout de conexión**:
```php
Meli::$CURL_OPTS[CURLOPT_CONNECTTIMEOUT] = 5;  // Fallar rápido
```

4. **Implementar caching**:
```php
$cacheKey = "meli_user_$userId";
$user = apcu_fetch($cacheKey);

if ($user === false) {
    $result = $meli->get('/users/me', ['access_token' => $token]);
    $user = $result['body'];
    apcu_store($cacheKey, $user, 3600);  // 1 hora
}
```

---

## Debugging Avanzado

### Habilitar modo verbose de cURL

```php
// Crear archivo para logging
$logFile = fopen('curl_debug.log', 'w');

// Modificar temporalmente en Meli/meli.php
public function execute($path, $opts = array(), $params = array(), $assoc = false) {
    $uri = $this->make_path($path, $params);
    $ch = curl_init($uri);
    curl_setopt_array($ch, self::$CURL_OPTS);
    
    // Agregar debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $logFile);
    
    // resto del código...
}

// Revisar curl_debug.log para ver detalles de la petición
```

### Inspeccionar petición completa

```php
class MeliDebug extends Meli {
    public function execute($path, $opts = array(), $params = array(), $assoc = false) {
        $uri = $this->make_path($path, $params);
        
        echo "═══════════════════════════════════════\n";
        echo "REQUEST\n";
        echo "═══════════════════════════════════════\n";
        echo "URL: $uri\n";
        echo "Method: " . (isset($opts[CURLOPT_CUSTOMREQUEST]) ? $opts[CURLOPT_CUSTOMREQUEST] : 'GET') . "\n";
        
        if (isset($opts[CURLOPT_POSTFIELDS])) {
            echo "Body: " . $opts[CURLOPT_POSTFIELDS] . "\n";
        }
        
        $result = parent::execute($path, $opts, $params, $assoc);
        
        echo "\nRESPONSE\n";
        echo "═══════════════════════════════════════\n";
        echo "HTTP Code: {$result['httpCode']}\n";
        echo "Body: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
        echo "═══════════════════════════════════════\n\n";
        
        return $result;
    }
}

// Usar
$meli = new MeliDebug($appId, $secretKey);
```

### Verificar estado de la API

```php
// Verificar si la API de MercadoLibre está disponible
function checkApiStatus() {
    $ch = curl_init('https://api.mercadolibre.com/sites/MLB');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ API de MercadoLibre disponible\n";
        return true;
    } else {
        echo "❌ API no disponible. HTTP Code: $httpCode\n";
        echo "Verifica: https://status.mercadolibre.com\n";
        return false;
    }
}

checkApiStatus();
```

---

## Obtener Ayuda

Si ninguna de estas soluciones funciona:

1. **Revisa la documentación completa**: [docs/](./OVERVIEW.md)
2. **Busca en issues existentes**: https://github.com/mercadolibre/php-sdk/issues
3. **Pregunta en el foro**: https://developers.mercadolibre.com/community
4. **Abre un nuevo issue**: https://github.com/mercadolibre/php-sdk/issues/new

Al reportar un problema, incluye:
- Versión de PHP (`php -v`)
- Código mínimo para reproducir
- Mensaje de error completo
- Logs de debugging si los tienes

---

**¿Resolviste un problema no listado aquí?** ¡Contribuye! Ver [CONTRIBUTING.md](./CONTRIBUTING.md)

