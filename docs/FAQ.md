# ❓ Preguntas Frecuentes (FAQ)

## Índice

1. [Preguntas Generales](#preguntas-generales)
2. [Autenticación y OAuth](#autenticación-y-oauth)
3. [API y Endpoints](#api-y-endpoints)
4. [Publicación de Productos](#publicación-de-productos)
5. [Errores Comunes](#errores-comunes)
6. [Performance y Límites](#performance-y-límites)
7. [Seguridad](#seguridad)

---

## Preguntas Generales

### ¿Qué es el SDK de PHP para MercadoLibre?

Es una biblioteca oficial que simplifica la integración con la API de MercadoLibre. Maneja automáticamente:
- Autenticación OAuth 2.0
- Peticiones HTTP (GET, POST, PUT, DELETE)
- Renovación de tokens
- Construcción de URLs

### ¿Es gratuito?

**Sí**, el SDK es 100% gratuito y open source. Sin embargo:
- Necesitas una cuenta de MercadoLibre (gratuita)
- Crear una aplicación en el portal de desarrolladores (gratuito)
- Las comisiones de venta aplican según las políticas de MercadoLibre

### ¿Qué versión de PHP necesito?

- **Mínima**: PHP 5.3
- **Recomendada**: PHP 7.4 o superior
- **Compatible**: PHP 8.x

```bash
# Verificar versión
php -v
```

### ¿Funciona en todos los países donde opera MercadoLibre?

**Sí**, el SDK soporta los 14 sitios de MercadoLibre:
- 🇦🇷 Argentina (MLA)
- 🇧🇷 Brasil (MLB)
- 🇲🇽 México (MLM)
- 🇨🇴 Colombia (MCO)
- 🇨🇱 Chile (MLC)
- 🇺🇾 Uruguay (MLU)
- 🇵🇪 Perú (MPE)
- Y más...

### ¿Puedo usar este SDK en producción?

**Sí**, está diseñado para producción. Asegúrate de:
- Usar variables de entorno para credenciales
- Implementar manejo de errores robusto
- Configurar logging apropiado
- Respetar los límites de rate de la API

---

## Autenticación y OAuth

### ¿Qué es OAuth 2.0 y por qué lo necesito?

OAuth 2.0 es un protocolo de autorización que permite a tu aplicación acceder a la cuenta de un usuario de MercadoLibre **sin pedirle su contraseña**.

**Necesitas OAuth para**:
- Publicar productos en nombre del usuario
- Ver pedidos y ventas
- Responder preguntas
- Cualquier acción que requiera permisos del usuario

**No necesitas OAuth para**:
- Búsquedas públicas de productos
- Consultar información de categorías
- Ver detalles públicos de items

### ¿Cuánto dura el access_token?

Por defecto, **6 horas** (21,600 segundos).

```php
// Al autorizar
$auth = $meli->authorize($code, $redirectUri);
$expiresIn = $auth['body']->expires_in;  // 21600

// Calcular cuándo expira
$expiresAt = time() + $expiresIn;
$_SESSION['expires_at'] = $expiresAt;

// Verificar si expiró
if (time() >= $_SESSION['expires_at']) {
    // Renovar token
    $meli->refreshAccessToken();
}
```

### ¿Qué es el refresh_token?

Es un token especial que permite renovar el `access_token` sin que el usuario tenga que volver a iniciar sesión.

**Importante**:
- Solo lo recibes si tu app tiene permiso de `offline_access`
- No expira (pero puede ser revocado por el usuario)
- Guárdalo de forma segura

### ¿Cómo obtengo offline_access?

En la configuración de tu aplicación en el portal de desarrolladores:

1. Ve a https://developers.mercadolibre.com/apps/home
2. Edita tu aplicación
3. En "Scopes" o "Permisos", selecciona **"offline_access"**
4. Guarda los cambios

### ¿Puedo tener múltiples usuarios autenticados?

**Sí**, pero debes almacenar los tokens de cada usuario por separado:

```php
// Al autorizar usuario 1
$auth1 = $meli->authorize($code, $redirectUri);
$db->saveTokens(
    $userId = 123,
    $accessToken = $auth1['body']->access_token,
    $refreshToken = $auth1['body']->refresh_token
);

// Más tarde, usar tokens del usuario específico
$tokens = $db->getTokens($userId = 123);
$meli = new Meli($appId, $secretKey, $tokens['access'], $tokens['refresh']);
```

### ¿Qué hago si el usuario revoca el acceso?

Si el usuario revoca tu app desde su configuración de MercadoLibre, tus tokens dejarán de funcionar.

**Detectar revocación**:
```php
$result = $meli->get('/users/me', ['access_token' => $token]);

if ($result['httpCode'] == 403) {
    // Token revocado
    echo "Por favor, autoriza la aplicación nuevamente";
    // Eliminar tokens de la DB
    $db->deleteTokens($userId);
}
```

---

## API y Endpoints

### ¿Dónde encuentro la lista completa de endpoints?

Documentación oficial: https://developers.mercadolibre.com/api-docs

**Endpoints más usados**:
- `/users/me` - Información del usuario autenticado
- `/items` - Crear/actualizar productos
- `/orders/search` - Buscar pedidos
- `/questions/search` - Buscar preguntas
- `/sites/{site_id}/search` - Buscar productos

### ¿Cómo sé qué parámetros enviar?

Usa el método `OPTIONS` para obtener metadata:

```php
$info = $meli->options('/items');
print_r($info['body']);
// Muestra campos requeridos, tipos de datos, valores permitidos, etc.
```

### ¿La API retorna JSON o XML?

**Solo JSON**. El SDK automáticamente convierte la respuesta JSON a objetos/arrays de PHP.

```php
$result = $meli->get('/users/me', $params);

// Acceso como objeto (por defecto)
echo $result['body']->nickname;

// O como array asociativo
$result = $meli->get('/users/me', $params, true);
echo $result['body']['nickname'];
```

### ¿Puedo hacer peticiones a dominios personalizados?

No directamente. El SDK está diseñado para `https://api.mercadolibre.com`.

Si necesitas otro dominio, modifica `Meli::$API_ROOT_URL`:

```php
Meli::$API_ROOT_URL = "https://api-staging.mercadolibre.com";
$meli = new Meli($appId, $secretKey);
```

---

## Publicación de Productos

### ¿Cómo sé qué category_id usar?

**Método 1: Predicción automática**
```php
$result = $meli->get('/sites/MLB/domain_discovery/search', [
    'q' => 'iPhone 14 Pro'
]);

foreach ($result['body'] as $prediction) {
    echo $prediction->category_id . " - " . $prediction->category_name . "\n";
}
```

**Método 2: Navegar categorías**
```php
$categories = $meli->get('/sites/MLB/categories');
// Explorar la jerarquía manualmente
```

### ¿Cómo subo imágenes?

MercadoLibre soporta dos formas:

**Opción 1: URL externa (recomendado)**
```php
'pictures' => [
    ['source' => 'https://mi-cdn.com/producto.jpg']
]
```

**Opción 2: Upload directo (requiere endpoint adicional)**
```php
// Primero subir imagen
$imageData = base64_encode(file_get_contents('producto.jpg'));
$response = $meli->post('/pictures', [
    'picture' => $imageData
]);

$pictureId = $response['body']->id;

// Luego usar en el item
'pictures' => [
    ['id' => $pictureId]
]
```

### ¿Cuántos productos puedo publicar?

Depende del tipo de cuenta:

- **Cuenta gratuita**: Límite inicial bajo (~10-50 items)
- **Mercado Shops**: Límites más altos
- **Grandes vendedores**: Sin límites prácticos

Consulta tu límite:
```php
$user = $meli->get('/users/me', ['access_token' => $token]);
echo $user['body']->seller_reputation->metrics->sales->completed;
```

### ¿Puedo publicar productos en múltiples países?

Sí, pero necesitas:
1. Cuentas separadas en cada país (o cuenta CBT - Cross Border Trade)
2. Aplicaciones configuradas para cada sitio
3. Tokens de autenticación por país

```php
// Usuario de Brasil
$meliML = new Meli($appIdBrasil, $secretBrasil);
$meliML->post('/items', $item, ['access_token' => $tokenBrasil]);

// Usuario de Argentina
$meliAR = new Meli($appIdArgentina, $secretArgentina);
$meliAR->post('/items', $item, ['access_token' => $tokenArgentina]);
```

### ¿Cómo actualizo solo el precio sin tocar el resto?

```php
$response = $meli->put("/items/MLB123456", [
    'price' => 999.99
], [
    'access_token' => $_SESSION['access_token']
]);
```

Solo envía los campos que quieres cambiar.

---

## Errores Comunes

### Error: "invalid_grant"

**Causa**: Código de autorización inválido o expirado.

**Solución**:
- El código OAuth es de un solo uso
- Expira en ~10 minutos
- Genera uno nuevo iniciando el flujo OAuth desde cero

```php
$authUrl = $meli->getAuthUrl($redirectUri, Meli::$AUTH_URL['MLB']);
header("Location: $authUrl");
```

### Error: "Malformed access_token"

**Causa**: Token mal formado o vacío.

**Solución**:
```php
// Verificar que el token existe
if (empty($_SESSION['access_token'])) {
    echo "Debes autenticarte primero";
    exit;
}

// Verificar formato
if (strpos($_SESSION['access_token'], 'APP_USR-') !== 0) {
    echo "Token inválido";
    exit;
}
```

### Error: "Too many requests" (429)

**Causa**: Excediste el límite de peticiones por minuto.

**Solución**:
```php
$result = $meli->get('/users/me', $params);

if ($result['httpCode'] == 429) {
    echo "Límite de rate excedido. Espera 60 segundos.\n";
    sleep(60);
    // Reintentar
    $result = $meli->get('/users/me', $params);
}
```

**Prevención**:
- Implementa caching
- Agrega delays entre requests
- Usa webhooks en lugar de polling

### Error: "cURL error 60: SSL certificate"

**Causa**: Certificados SSL desactualizados.

**Solución**:
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install ca-certificates

# macOS
brew install openssl
```

**Workaround temporal (solo desarrollo)**:
```php
Meli::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;  // ⚠️ NO EN PRODUCCIÓN
```

---

## Performance y Límites

### ¿Cuántas peticiones por minuto puedo hacer?

MercadoLibre no publica límites específicos públicamente, pero en general:
- **Desarrollo**: ~60 requests/minuto
- **Producción**: ~300-500 requests/minuto (varía)

**Buena práctica**: Agrega 1 segundo de delay entre peticiones intensivas.

### ¿Cómo mejoro el rendimiento?

**1. Cachear respuestas que no cambian**:
```php
$cache_key = "meli_categories_MLB";
if (!$categories = $cache->get($cache_key)) {
    $result = $meli->get('/sites/MLB/categories');
    $categories = $result['body'];
    $cache->set($cache_key, $categories, 3600);  // 1 hora
}
```

**2. Peticiones en lote** (cuando la API lo soporte):
```php
// En lugar de 100 peticiones individuales, usa batch
$items = ['MLB123', 'MLB456', 'MLB789'];
$result = $meli->get('/items?ids=' . implode(',', $items));
```

**3. Usar webhooks** en lugar de polling:
```php
// ❌ MAL - Polling cada 5 minutos
while (true) {
    $orders = $meli->get('/orders/search', ['seller' => 'me', 'access_token' => $token]);
    // Procesar
    sleep(300);
}

// ✅ BIEN - Webhook
// MercadoLibre te notifica cuando hay nuevos pedidos
// Ver docs/EXAMPLES.md sección "Notificaciones"
```

### ¿Puedo hacer peticiones en paralelo?

**Sí**, con cURL multi-handle:

```php
function parallelRequests($meli, $endpoints) {
    $mh = curl_multi_init();
    $handles = [];
    
    foreach ($endpoints as $endpoint) {
        $ch = curl_init("https://api.mercadolibre.com$endpoint");
        curl_setopt_array($ch, Meli::$CURL_OPTS);
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }
    
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running);
    
    $results = [];
    foreach ($handles as $ch) {
        $results[] = json_decode(curl_multi_getcontent($ch));
        curl_multi_remove_handle($mh, $ch);
    }
    
    curl_multi_close($mh);
    return $results;
}

// Obtener 3 items al mismo tiempo
$results = parallelRequests($meli, [
    '/items/MLB123',
    '/items/MLB456',
    '/items/MLB789'
]);
```

---

## Seguridad

### ¿Dónde guardo las credenciales?

**Nunca en el código**. Usa variables de entorno:

```php
// ❌ MAL
$appId = '1234567890';
$secretKey = 'abc123xyz';

// ✅ BIEN
$appId = getenv('MELI_APP_ID') ?: die('Missing credentials');
$secretKey = getenv('MELI_SECRET_KEY') ?: die('Missing credentials');
```

**En servidor**:
```bash
# .env
export MELI_APP_ID="1234567890"
export MELI_SECRET_KEY="abc123xyz"
```

### ¿Cómo almaceno los tokens de forma segura?

**Base de datos**:
```php
// Encriptar antes de guardar
$encryptedToken = openssl_encrypt(
    $accessToken,
    'AES-256-CBC',
    $encryptionKey,
    0,
    $iv
);

$db->exec("INSERT INTO user_tokens (user_id, token) VALUES (?, ?)", 
    [$userId, $encryptedToken]
);

// Desencriptar al leer
$encryptedToken = $db->query("SELECT token FROM user_tokens WHERE user_id = ?", [$userId]);
$accessToken = openssl_decrypt(
    $encryptedToken,
    'AES-256-CBC',
    $encryptionKey,
    0,
    $iv
);
```

### ¿El SDK valida la entrada del usuario?

**No automáticamente**. Tú debes validar:

```php
// ✅ Validar entrada del usuario
function createItemFromUserInput($userInput, $meli, $token) {
    // Validar precio
    if (!is_numeric($userInput['price']) || $userInput['price'] <= 0) {
        throw new Exception('Precio inválido');
    }
    
    // Sanitizar título
    $title = htmlspecialchars($userInput['title'], ENT_QUOTES);
    $title = substr($title, 0, 60);  // MercadoLibre límite de 60 chars
    
    $item = [
        'title' => $title,
        'price' => floatval($userInput['price']),
        // ...
    ];
    
    return $meli->post('/items', $item, ['access_token' => $token]);
}
```

### ¿Es seguro usar en shared hosting?

**Con precauciones**:
- ✅ Usa HTTPS siempre
- ✅ Variables de entorno o archivos de config fuera del document root
- ✅ Permisos restrictivos en archivos (chmod 600)
- ❌ Evita guardar tokens en archivos planos
- ✅ Usa bases de datos con credenciales separadas

---

## Preguntas Específicas de Uso

### ¿Cómo sé si un usuario ya autorizó mi app?

Intenta obtener su información:

```php
$result = $meli->get('/users/me', ['access_token' => $storedToken]);

if ($result['httpCode'] == 200) {
    echo "Usuario autenticado: " . $result['body']->nickname;
} else {
    echo "Usuario no autenticado o token expirado";
    // Iniciar flujo OAuth nuevamente
}
```

### ¿Puedo testear sin publicar productos reales?

**Sí**, usa el entorno de testing:

1. Crea una aplicación de prueba en https://developers.mercadolibre.com/apps/home
2. Usa la categoría de test: `CBT11796` (Cross Border Trade test)
3. Agrega `--kc:off` al título para evitar que se publique

```php
$item = [
    'title' => 'Producto de Prueba --kc:off',
    'category_id' => 'CBT11796',
    // ...
];
```

### ¿Cómo migro de otra biblioteca/SDK?

1. Instala este SDK
2. Reemplaza llamadas de autenticación
3. Actualiza métodos HTTP (GET/POST/PUT/DELETE)
4. Ajusta manejo de respuestas

**Antes (curl manual)**:
```php
$ch = curl_init('https://api.mercadolibre.com/users/me');
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
$result = curl_exec($ch);
```

**Después (con SDK)**:
```php
$result = $meli->get('/users/me', ['access_token' => $token]);
```

---

## Soporte y Recursos

### ¿Dónde obtengo ayuda?

1. **Esta documentación**: `/docs` folder
2. **Foro oficial**: https://developers.mercadolibre.com/community
3. **GitHub Issues**: https://github.com/mercadolibre/php-sdk/issues
4. **Stack Overflow**: Tag `mercadolibre-api`

### ¿Puedo contactar directamente al equipo de MercadoLibre?

Para soporte técnico, usa los canales oficiales:
- Foro de desarrolladores (recomendado)
- Soporte en el portal de apps

**No** contactes a desarrolladores individuales por redes sociales.

### ¿Cuándo se actualiza el SDK?

Revisa el [changelog](../changelog.md) y las [releases en GitHub](https://github.com/mercadolibre/php-sdk/releases).

---

**¿Tu pregunta no está aquí?** Abre un issue en GitHub o consulta la [documentación completa](./OVERVIEW.md).

