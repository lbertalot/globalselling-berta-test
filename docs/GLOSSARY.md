# 📖 Glosario de Términos

Glosario completo de términos técnicos, siglas y conceptos utilizados en el SDK de PHP para MercadoLibre.

---

## A

### Access Token
Token de acceso JWT que permite a una aplicación realizar operaciones en nombre de un usuario autenticado. Expira típicamente en 6 horas.

**Ejemplo**:
```php
$accessToken = "APP_USR-1234567890-112233-abc123xyz..."
```

**Ver también**: [Refresh Token](#refresh-token), [OAuth 2.0](#oauth-20)

---

### API (Application Programming Interface)
Conjunto de endpoints y métodos que permiten a aplicaciones externas interactuar con los servicios de MercadoLibre.

**Base URL**: `https://api.mercadolibre.com`

**Ver también**: [REST](#rest), [Endpoint](#endpoint)

---

### App ID / Client ID
Identificador único de tu aplicación registrada en MercadoLibre. Se usa para identificar tu app durante el flujo de autenticación.

**Formato**: Número de 13-16 dígitos  
**Ejemplo**: `1234567890123`

**Dónde obtenerlo**: https://developers.mercadolibre.com/apps/home

---

### Authorization Code
Código temporal de un solo uso que se obtiene después de que el usuario autoriza tu aplicación. Se intercambia por un `access_token`.

**Duración**: ~10 minutos  
**Uso**: Un solo uso  

**Flujo**:
```
1. Usuario autoriza → 2. Redirect con code → 3. Exchange code por token
```

---

## B

### Batch Operations
Operación que permite realizar múltiples acciones en una sola petición HTTP, reduciendo el número de requests.

**Ejemplo**:
```php
// En lugar de 3 peticiones individuales
$item1 = $meli->get('/items/MLB123');
$item2 = $meli->get('/items/MLB456');
$item3 = $meli->get('/items/MLB789');

// Una sola petición batch
$items = $meli->get('/items?ids=MLB123,MLB456,MLB789');
```

---

### Buying Mode
Modalidad de compra de un producto en MercadoLibre.

**Valores posibles**:
- `buy_it_now`: Compra inmediata (Comprar Ahora)
- `auction`: Subasta

---

## C

### Category ID
Identificador único de una categoría en MercadoLibre. Cada sitio tiene su propia jerarquía de categorías.

**Formato**: `{SITE_ID}{NUMBER}`  
**Ejemplo**: `MLB1051` (Celulares y Teléfonos en Brasil)

**Cómo obtenerlo**:
```php
$result = $meli->get('/sites/MLB/domain_discovery/search', [
    'q' => 'iPhone 14'
]);
```

---

### CBT (Cross Border Trade)
Programa de MercadoLibre para ventas internacionales que permite a vendedores de un país vender en otros países.

**Site ID**: `CBT`  
**Categoría de prueba**: `CBT11796`

---

### Client Secret
Clave secreta de tu aplicación. **Nunca debe ser expuesta públicamente** ni incluida en código del lado del cliente.

**Formato**: String alfanumérico  
**Ejemplo**: `AbC123XyZ789`

**Dónde obtenerla**: https://developers.mercadolibre.com/apps/home

---

### Condition
Estado del producto.

**Valores posibles**:
- `new`: Nuevo
- `used`: Usado
- `not_specified`: No especificado

---

### CURL
Biblioteca de PHP para realizar peticiones HTTP/HTTPS. El SDK usa cURL internamente.

**Verificar instalación**:
```bash
php -m | grep curl
```

---

### Currency ID
Código de la moneda utilizada para el precio del producto.

**Ejemplos**:
- `BRL`: Real brasileño
- `ARS`: Peso argentino
- `MXN`: Peso mexicano
- `USD`: Dólar estadounidense

---

## D

### Domain Discovery
Servicio de MercadoLibre que sugiere categorías automáticamente basándose en el título o descripción del producto.

**Endpoint**: `/sites/{site_id}/domain_discovery/search`

**Uso**:
```php
$result = $meli->get('/sites/MLB/domain_discovery/search', [
    'q' => 'Samsung Galaxy S23'
]);
```

---

## E

### Endpoint
URL específica de la API que representa un recurso o acción.

**Ejemplos**:
- `/users/me`: Información del usuario autenticado
- `/items`: Crear/listar productos
- `/orders/search`: Buscar pedidos

---

## F

### Free Shipping
Envío gratuito ofrecido por el vendedor.

**En el item**:
```php
'shipping' => [
    'free_shipping' => true
]
```

---

## G

### GET Request
Método HTTP para obtener/leer información sin modificar datos.

**Uso en el SDK**:
```php
$result = $meli->get('/users/me', ['access_token' => $token]);
```

---

### Grant Type
Tipo de autorización OAuth 2.0.

**Tipos usados por el SDK**:
- `authorization_code`: Intercambio inicial de code por token
- `refresh_token`: Renovación de access_token expirado

---

## H

### HTTP Status Code
Código numérico que indica el resultado de una petición HTTP.

**Códigos comunes**:
- `200`: OK (éxito)
- `201`: Created (recurso creado)
- `400`: Bad Request (parámetros inválidos)
- `401`: Unauthorized (token inválido)
- `403`: Forbidden (sin permisos)
- `404`: Not Found (recurso no existe)
- `429`: Too Many Requests (rate limit)

---

## I

### Item
Producto publicado en MercadoLibre.

**Estructura básica**:
```php
[
    'id' => 'MLB123456789',
    'title' => 'iPhone 14 Pro',
    'price' => 6999.99,
    'currency_id' => 'BRL',
    'available_quantity' => 10
]
```

---

### Item ID
Identificador único de un producto en MercadoLibre.

**Formato**: `{SITE_ID}{NUMBER}`  
**Ejemplo**: `MLB1234567890`

---

## J

### JSON (JavaScript Object Notation)
Formato de intercambio de datos usado por la API de MercadoLibre.

**El SDK convierte automáticamente**:
```php
// JSON → Objeto PHP
$result = $meli->get('/users/me');
echo $result['body']->nickname;

// Array PHP → JSON (al hacer POST/PUT)
$meli->post('/items', $itemArray);
```

---

## L

### Listing Type
Tipo de publicación en MercadoLibre, determina la visibilidad y costo.

**Tipos comunes**:
- `free`: Gratuita (baja visibilidad)
- `bronze`: Bronce
- `silver`: Plata  
- `gold`: Oro
- `gold_special`: Oro Premium
- `gold_pro`: Oro Pro (máxima visibilidad)

---

## M

### Mercado Envíos (ME / ME2)
Servicio logístico de MercadoLibre que gestiona el envío de productos.

**Configuración**:
```php
'shipping' => [
    'mode' => 'me2'  // Mercado Envíos
]
```

---

### MLA, MLB, MLM, etc.
Códigos de sitio (Site ID) de MercadoLibre para cada país.

**Principales**:
- `MLA`: Argentina
- `MLB`: Brasil
- `MLM`: México
- `MCO`: Colombia
- `MLC`: Chile
- `MLU`: Uruguay

**Ver todos**: [OVERVIEW.md - Soporte Multi-Región](./OVERVIEW.md#soporte-multi-región)

---

## O

### OAuth 2.0
Protocolo de autorización estándar que permite a aplicaciones acceder a recursos del usuario sin conocer su contraseña.

**Flujo básico**:
```
1. getAuthUrl() → 2. Usuario autoriza → 3. authorize(code) → 4. access_token
```

**Ver también**: [Authorization Code](#authorization-code), [Access Token](#access-token)

---

### Offline Access
Permiso especial que permite renovar tokens sin requerir que el usuario inicie sesión nuevamente.

**Habilitar**: En la configuración de tu app → Scopes → `offline_access`

**Resultado**: Recibes `refresh_token` al autorizar

---

### Order
Pedido/compra realizado por un usuario.

**Estructura**:
```php
[
    'id' => 123456789,
    'status' => 'paid',
    'buyer' => [...],
    'order_items' => [...]
]
```

---

## P

### Permalink
URL permanente de un producto en el sitio de MercadoLibre.

**Ejemplo**: `https://produto.mercadolivre.com.br/MLB-123456789`

---

### Pictures
Imágenes de un producto.

**Formato**:
```php
'pictures' => [
    ['source' => 'https://example.com/image1.jpg'],
    ['source' => 'https://example.com/image2.jpg']
]
```

**Límites**: Máximo 12 imágenes por producto

---

### POST Request
Método HTTP para crear nuevos recursos.

**Uso en el SDK**:
```php
$result = $meli->post('/items', $itemData, ['access_token' => $token]);
```

---

### PUT Request
Método HTTP para actualizar recursos existentes.

**Uso en el SDK**:
```php
$result = $meli->put('/items/MLB123', ['price' => 999], ['access_token' => $token]);
```

---

## Q

### Query String Parameters
Parámetros enviados en la URL después del `?`.

**Ejemplo**:
```
https://api.mercadolibre.com/users/me?access_token=ABC123
```

**En el SDK**:
```php
$meli->get('/users/me', [
    'access_token' => 'ABC123'
]);
```

---

### Questions
Preguntas que los compradores hacen sobre un producto.

**Estados**:
- `UNANSWERED`: Sin responder
- `ANSWERED`: Respondida
- `CLOSED`: Cerrada
- `DISABLED`: Deshabilitada

---

## R

### Rate Limit / Rate Limiting
Límite de peticiones que puedes hacer a la API en un período de tiempo.

**Típicamente**: ~50-300 requests por minuto

**Error**: HTTP 429 "Too Many Requests"

**Solución**: Implementar delays entre peticiones

```php
sleep(1);  // Esperar 1 segundo entre requests
```

---

### Redirect URI
URL a la que MercadoLibre redirige al usuario después de autorizar tu aplicación.

**Debe coincidir exactamente** con la configurada en tu app de MercadoLibre.

**Ejemplo**: `http://localhost:8000/callback.php`

---

### Refresh Token
Token especial que permite renovar un `access_token` expirado sin requerir login del usuario.

**Duración**: No expira (pero puede ser revocado)  
**Requisito**: App debe tener permiso `offline_access`

**Uso**:
```php
$refresh = $meli->refreshAccessToken();
```

---

### REST (Representational State Transfer)
Estilo de arquitectura para APIs web. La API de MercadoLibre es RESTful.

**Características**:
- Usa métodos HTTP estándar (GET, POST, PUT, DELETE)
- Recursos identificados por URLs
- Stateless (sin estado entre peticiones)

---

## S

### Sale Terms
Términos de venta del producto (garantía, tiempo de entrega, etc.).

**Ejemplo**:
```php
'sale_terms' => [
    ['id' => 'WARRANTY_TYPE', 'value_name' => 'Garantía del vendedor'],
    ['id' => 'WARRANTY_TIME', 'value_name' => '12 meses']
]
```

---

### Scope
Permisos que tu aplicación solicita al usuario.

**Scopes comunes**:
- `read`: Leer información
- `write`: Crear/modificar recursos
- `offline_access`: Renovar tokens sin login

---

### SDK (Software Development Kit)
Conjunto de herramientas que facilita el desarrollo de aplicaciones. Este proyecto es el SDK oficial de PHP.

---

### Secret Key
Ver [Client Secret](#client-secret)

---

### Site ID
Código de país/sitio de MercadoLibre.

Ver [MLA, MLB, MLM, etc.](#mla-mlb-mlm-etc)

---

### SSL/TLS
Protocolo de seguridad para conexiones HTTPS cifradas.

**Error común**: "SSL certificate problem"

**Solución**: Actualizar certificados del sistema

---

### Status
Estado de un recurso (item, order, question, etc.).

**Estados de Items**:
- `active`: Activo (visible)
- `paused`: Pausado (no visible)
- `closed`: Cerrado (terminado)
- `under_review`: En revisión
- `inactive`: Inactivo

---

## T

### Token
Ver [Access Token](#access-token)

---

### TTL (Time To Live)
Tiempo de vida de un recurso antes de expirar.

**Access Token TTL**: 21,600 segundos (6 horas)

---

## U

### User Agent
Identificador del cliente que hace las peticiones.

**SDK User Agent**: `MELI-PHP-SDK-2.0.0`

---

## V

### Variations
Variantes de un producto (tallas, colores, etc.).

**Ejemplo**: iPhone 14 Pro en colores Negro, Blanco, Dorado

---

## W

### Webhook
Notificación automática que MercadoLibre envía a tu servidor cuando ocurre un evento (nueva pregunta, nuevo pedido, etc.).

**Tópicos**:
- `items`: Cambios en productos
- `orders`: Nuevos pedidos
- `questions`: Nuevas preguntas
- `claims`: Reclamos

**Configuración**:
```php
$meli->post('/webhooks', [
    'topic' => 'orders',
    'url' => 'https://miapp.com/webhook'
]);
```

---

## Siglas Comunes

| Sigla | Significado |
|-------|-------------|
| **API** | Application Programming Interface |
| **CBT** | Cross Border Trade |
| **CRUD** | Create, Read, Update, Delete |
| **HTTP** | Hypertext Transfer Protocol |
| **HTTPS** | HTTP Secure |
| **JSON** | JavaScript Object Notation |
| **JWT** | JSON Web Token |
| **ME** | Mercado Envíos |
| **OAuth** | Open Authorization |
| **REST** | Representational State Transfer |
| **SDK** | Software Development Kit |
| **SSL** | Secure Sockets Layer |
| **TLS** | Transport Layer Security |
| **TTL** | Time To Live |
| **URI** | Uniform Resource Identifier |
| **URL** | Uniform Resource Locator |

---

## Términos en Español/Portugués

| Español/Portugués | Inglés | Descripción |
|-------------------|--------|-------------|
| Pregunta | Question | Consulta de un comprador |
| Pedido | Order | Compra realizada |
| Publicación | Listing | Producto publicado |
| Vendedor | Seller | Usuario que vende |
| Comprador | Buyer | Usuario que compra |
| Envío | Shipping | Logística de entrega |
| Garantía | Warranty | Garantía del producto |
| Calificación | Rating | Puntuación del vendedor |

---

## Referencias Cruzadas

- **[OVERVIEW.md](./OVERVIEW.md)**: Visión general y arquitectura
- **[API_REFERENCE.md](./API_REFERENCE.md)**: Documentación técnica completa
- **[EXAMPLES.md](./EXAMPLES.md)**: Ejemplos de código
- **[FAQ.md](./FAQ.md)**: Preguntas frecuentes

---

**¿Falta un término?** [Contribuye al glosario](./CONTRIBUTING.md)

