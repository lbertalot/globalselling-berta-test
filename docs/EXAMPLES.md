# 💡 Ejemplos de Uso

## Índice

1. [Autenticación OAuth](#1-autenticación-oauth)
2. [Gestión de Productos](#2-gestión-de-productos)
3. [Búsqueda y Consultas](#3-búsqueda-y-consultas)
4. [Gestión de Preguntas](#4-gestión-de-preguntas)
5. [Gestión de Pedidos](#5-gestión-de-pedidos)
6. [Notificaciones (Webhooks)](#6-notificaciones-webhooks)
7. [Casos de Uso Avanzados](#7-casos-de-uso-avanzados)

---

## 1. Autenticación OAuth

### Flujo Completo de Autenticación

```php
<?php
session_start();
require 'Meli/meli.php';

$appId = '1234567890';
$secretKey = 'tu_secret_key';
$redirectUri = 'http://localhost:8000/callback.php';
$siteId = 'MLB';  // Brasil

$meli = new Meli($appId, $secretKey);

// PASO 1: Verificar si ya tenemos tokens
if (isset($_SESSION['access_token'])) {
    
    // PASO 2: Verificar si el token expiró
    if (time() >= $_SESSION['expires_at']) {
        echo "Token expirado, renovando...\n";
        
        // PASO 3: Renovar token
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
            $_SESSION['expires_at'] = time() + $refresh['body']->expires_in;
            echo "Token renovado exitosamente\n";
        } else {
            echo "Error al renovar token, reautentique\n";
            unset($_SESSION['access_token']);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    // PASO 4: Usuario autenticado, obtener info
    $user = $meli->get('/users/me', [
        'access_token' => $_SESSION['access_token']
    ]);
    
    if ($user['httpCode'] == 200) {
        echo "Bienvenido, " . $user['body']->nickname . "!\n";
        echo "User ID: " . $user['body']->id . "\n";
    }
    
} elseif (isset($_GET['code'])) {
    
    // PASO 5: Procesar callback con código de autorización
    $auth = $meli->authorize($_GET['code'], $redirectUri);
    
    if ($auth['httpCode'] == 200) {
        $_SESSION['access_token'] = $auth['body']->access_token;
        $_SESSION['refresh_token'] = $auth['body']->refresh_token ?? null;
        $_SESSION['expires_at'] = time() + $auth['body']->expires_in;
        
        echo "Autenticación exitosa. Redirigiendo...\n";
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        echo "Error en autenticación: " . $auth['body']->message . "\n";
    }
    
} else {
    
    // PASO 6: Mostrar link de autorización
    $authUrl = $meli->getAuthUrl($redirectUri, Meli::$AUTH_URL[$siteId]);
    echo '<a href="' . $authUrl . '">Iniciar sesión con MercadoLibre</a>';
}
?>
```

---

## 2. Gestión de Productos

### 2.1 Publicar un Producto Completo

```php
<?php
require 'Meli/meli.php';
session_start();

$meli = new Meli($appId, $secretKey);

$item = [
    // Información básica
    'title' => 'Samsung Galaxy S23 Ultra 256GB - Nuevo Sellado',
    'category_id' => 'MLB9344',  // Buscar en /sites/MLB/categories
    'price' => 6999.99,
    'currency_id' => 'BRL',
    'available_quantity' => 15,
    
    // Tipo de publicación
    'listing_type_id' => 'gold_special',  // gold_special, gold_pro, free
    'buying_mode' => 'buy_it_now',
    'condition' => 'new',  // new, used
    
    // Descripción
    'description' => [
        'plain_text' => 'Samsung Galaxy S23 Ultra con 256GB de almacenamiento. 
                         Incluye cargador y cable USB-C. 
                         Garantía oficial de 12 meses. 
                         Envío inmediato.'
    ],
    
    // Video (opcional)
    'video_id' => 'youtube_video_id',  // ID del video de YouTube
    
    // Imágenes (máximo 12)
    'pictures' => [
        ['source' => 'https://example.com/phone-front.jpg'],
        ['source' => 'https://example.com/phone-back.jpg'],
        ['source' => 'https://example.com/phone-box.jpg']
    ],
    
    // Envío
    'shipping' => [
        'mode' => 'me2',  // me2 = Mercado Envíos
        'free_shipping' => true,
        'local_pick_up' => true
    ],
    
    // Garantía
    'warranty' => '12 meses de garantía oficial',
    
    // Términos de venta
    'sale_terms' => [
        [
            'id' => 'WARRANTY_TYPE',
            'value_name' => 'Garantía del vendedor'
        ],
        [
            'id' => 'WARRANTY_TIME',
            'value_name' => '12 meses'
        ]
    ],
    
    // Atributos técnicos (varían por categoría)
    'attributes' => [
        [
            'id' => 'BRAND',
            'value_name' => 'Samsung'
        ],
        [
            'id' => 'MODEL',
            'value_name' => 'Galaxy S23 Ultra'
        ],
        [
            'id' => 'INTERNAL_MEMORY',
            'value_name' => '256 GB'
        ],
        [
            'id' => 'RAM',
            'value_name' => '12 GB'
        ],
        [
            'id' => 'COLOR',
            'value_name' => 'Phantom Black'
        ]
    ]
];

$response = $meli->post('/items', $item, [
    'access_token' => $_SESSION['access_token']
]);

if ($response['httpCode'] == 201) {
    echo "✅ Producto publicado exitosamente\n";
    echo "ID: " . $response['body']->id . "\n";
    echo "Permalink: " . $response['body']->permalink . "\n";
    echo "Precio: " . $response['body']->price . "\n";
} else {
    echo "❌ Error al publicar:\n";
    print_r($response['body']);
}
?>
```

### 2.2 Actualizar Precio y Stock

```php
<?php
function updateItemStock($meli, $itemId, $newQuantity, $newPrice = null) {
    $updates = ['available_quantity' => $newQuantity];
    
    if ($newPrice !== null) {
        $updates['price'] = $newPrice;
    }
    
    $response = $meli->put("/items/$itemId", $updates, [
        'access_token' => $_SESSION['access_token']
    ]);
    
    if ($response['httpCode'] == 200) {
        echo "✅ Stock actualizado: $newQuantity unidades\n";
        if ($newPrice) echo "💰 Precio actualizado: $$newPrice\n";
        return true;
    } else {
        echo "❌ Error: " . $response['body']->message . "\n";
        return false;
    }
}

// Uso
updateItemStock($meli, 'MLB123456789', 25, 5999.99);
```

### 2.3 Pausar/Reactivar Producto

```php
<?php
function pauseItem($meli, $itemId) {
    $response = $meli->put("/items/$itemId", ['status' => 'paused'], [
        'access_token' => $_SESSION['access_token']
    ]);
    
    return $response['httpCode'] == 200;
}

function activateItem($meli, $itemId) {
    $response = $meli->put("/items/$itemId", ['status' => 'active'], [
        'access_token' => $_SESSION['access_token']
    ]);
    
    return $response['httpCode'] == 200;
}

// Pausar temporalmente
if (pauseItem($meli, 'MLB123456')) {
    echo "Producto pausado (no aparecerá en búsquedas)\n";
}

// Reactivar
if (activateItem($meli, 'MLB123456')) {
    echo "Producto reactivado\n";
}
```

### 2.4 Cerrar Producto (Eliminar)

```php
<?php
function closeItem($meli, $itemId) {
    $response = $meli->put("/items/$itemId", ['status' => 'closed'], [
        'access_token' => $_SESSION['access_token']
    ]);
    
    if ($response['httpCode'] == 200) {
        echo "✅ Producto cerrado (ya no se puede vender)\n";
        return true;
    }
    
    return false;
}
```

### 2.5 Obtener Todos los Productos del Usuario

```php
<?php
function getUserItems($meli, $accessToken, $status = 'active') {
    $offset = 0;
    $limit = 50;
    $allItems = [];
    
    do {
        $result = $meli->get('/users/me/items/search', [
            'access_token' => $accessToken,
            'status' => $status,  // active, paused, closed
            'offset' => $offset,
            'limit' => $limit
        ]);
        
        if ($result['httpCode'] != 200) {
            echo "Error: " . $result['body']->message . "\n";
            break;
        }
        
        $items = $result['body']->results;
        foreach ($items as $itemId) {
            // Obtener detalles de cada item
            $itemDetails = $meli->get("/items/$itemId");
            if ($itemDetails['httpCode'] == 200) {
                $allItems[] = $itemDetails['body'];
            }
        }
        
        $offset += $limit;
        $totalItems = $result['body']->paging->total;
        
        echo "Cargados " . count($allItems) . " de $totalItems items...\n";
        
    } while ($offset < $totalItems);
    
    return $allItems;
}

// Uso
$myItems = getUserItems($meli, $_SESSION['access_token']);
foreach ($myItems as $item) {
    echo "{$item->id} - {$item->title} - Precio: {$item->price}\n";
}
```

---

## 3. Búsqueda y Consultas

### 3.1 Búsqueda de Productos

```php
<?php
function searchProducts($meli, $query, $siteId = 'MLB', $filters = []) {
    $params = array_merge([
        'q' => $query,
        'limit' => 50
    ], $filters);
    
    $result = $meli->get("/sites/$siteId/search", $params);
    
    if ($result['httpCode'] == 200) {
        return [
            'total' => $result['body']->paging->total,
            'results' => $result['body']->results
        ];
    }
    
    return null;
}

// Búsqueda simple
$products = searchProducts($meli, 'laptop gaming');

echo "Total encontrados: {$products['total']}\n\n";

foreach ($products['results'] as $product) {
    echo "Título: {$product->title}\n";
    echo "Precio: {$product->currency_id} {$product->price}\n";
    echo "Vendedor: {$product->seller->nickname}\n";
    echo "Link: {$product->permalink}\n";
    echo "---\n";
}

// Búsqueda con filtros
$filteredSearch = searchProducts($meli, 'smartphone', 'MLB', [
    'price' => '1000-5000',  // Rango de precio
    'condition' => 'new',
    'shipping' => 'free',
    'sort' => 'price_asc'  // price_asc, price_desc, relevance
]);
```

### 3.2 Obtener Categorías

```php
<?php
function getCategories($meli, $siteId = 'MLB') {
    $result = $meli->get("/sites/$siteId/categories");
    
    if ($result['httpCode'] == 200) {
        return $result['body'];
    }
    
    return [];
}

// Obtener todas las categorías
$categories = getCategories($meli);

foreach ($categories as $category) {
    echo "{$category->id} - {$category->name}\n";
}

// Buscar categoría por palabra clave
function predictCategory($meli, $keyword, $siteId = 'MLB') {
    $result = $meli->get("/sites/$siteId/domain_discovery/search", [
        'q' => $keyword,
        'limit' => 5
    ]);
    
    if ($result['httpCode'] == 200) {
        foreach ($result['body'] as $prediction) {
            echo "Categoría sugerida: {$prediction->category_name} ({$prediction->category_id})\n";
        }
    }
}

predictCategory($meli, 'iPhone 14 Pro');
```

### 3.3 Obtener Información de un Producto

```php
<?php
function getItemDetails($meli, $itemId) {
    $item = $meli->get("/items/$itemId");
    
    if ($item['httpCode'] != 200) {
        return null;
    }
    
    $description = $meli->get("/items/$itemId/description");
    
    return [
        'item' => $item['body'],
        'description' => $description['body']->plain_text ?? ''
    ];
}

$details = getItemDetails($meli, 'MLB123456789');

if ($details) {
    $item = $details['item'];
    
    echo "Título: {$item->title}\n";
    echo "Precio: {$item->currency_id} {$item->price}\n";
    echo "Stock: {$item->available_quantity}\n";
    echo "Condición: {$item->condition}\n";
    echo "Vendedor: {$item->seller_id}\n";
    echo "\nDescripción:\n{$details['description']}\n";
}
```

---

## 4. Gestión de Preguntas

### 4.1 Obtener Preguntas de un Producto

```php
<?php
function getItemQuestions($meli, $itemId, $accessToken) {
    $result = $meli->get("/questions/search", [
        'item' => $itemId,
        'access_token' => $accessToken,
        'status' => 'UNANSWERED',  // UNANSWERED, ANSWERED
        'sort' => 'date_desc'
    ]);
    
    if ($result['httpCode'] == 200) {
        return $result['body']->questions;
    }
    
    return [];
}

// Listar preguntas sin responder
$questions = getItemQuestions($meli, 'MLB123456', $_SESSION['access_token']);

foreach ($questions as $q) {
    echo "Pregunta #{$q->id} de {$q->from->nickname}:\n";
    echo "  {$q->text}\n";
    echo "  Fecha: {$q->date_created}\n\n";
}
```

### 4.2 Responder Preguntas

```php
<?php
function answerQuestion($meli, $questionId, $text, $accessToken) {
    $answer = [
        'question_id' => $questionId,
        'text' => $text
    ];
    
    $response = $meli->post('/answers', $answer, [
        'access_token' => $accessToken
    ]);
    
    if ($response['httpCode'] == 200) {
        echo "✅ Pregunta respondida exitosamente\n";
        return true;
    } else {
        echo "❌ Error: " . $response['body']->message . "\n";
        return false;
    }
}

// Responder automáticamente preguntas comunes
$questions = getItemQuestions($meli, 'MLB123456', $_SESSION['access_token']);

foreach ($questions as $q) {
    $text = strtolower($q->text);
    
    if (strpos($text, 'stock') !== false || strpos($text, 'disponible') !== false) {
        answerQuestion($meli, $q->id, 'Sí, tenemos stock disponible. ¡Puedes comprar con confianza!', $_SESSION['access_token']);
    } elseif (strpos($text, 'garantía') !== false) {
        answerQuestion($meli, $q->id, 'Todos nuestros productos tienen garantía oficial de 12 meses.', $_SESSION['access_token']);
    }
}
```

### 4.3 Eliminar una Pregunta

```php
<?php
function deleteQuestion($meli, $questionId, $accessToken) {
    $response = $meli->delete("/questions/$questionId", [
        'access_token' => $accessToken
    ]);
    
    return $response['httpCode'] == 200;
}

// Solo se pueden eliminar preguntas ofensivas o duplicadas
if (deleteQuestion($meli, 123456789, $_SESSION['access_token'])) {
    echo "Pregunta eliminada\n";
}
```

---

## 5. Gestión de Pedidos

### 5.1 Obtener Pedidos del Vendedor

```php
<?php
function getOrders($meli, $accessToken, $filters = []) {
    $params = array_merge([
        'seller' => 'me',
        'access_token' => $accessToken,
        'sort' => 'date_desc',
        'limit' => 50
    ], $filters);
    
    $result = $meli->get('/orders/search', $params);
    
    if ($result['httpCode'] == 200) {
        return $result['body']->results;
    }
    
    return [];
}

// Obtener pedidos pendientes de envío
$pendingOrders = getOrders($meli, $_SESSION['access_token'], [
    'order.status' => 'paid'
]);

foreach ($pendingOrders as $order) {
    echo "Pedido #{$order->id}\n";
    echo "Comprador: {$order->buyer->nickname}\n";
    echo "Total: {$order->currency_id} {$order->total_amount}\n";
    
    foreach ($order->order_items as $item) {
        echo "  - {$item->item->title} x{$item->quantity}\n";
    }
    
    echo "\n";
}
```

### 5.2 Obtener Detalles de un Pedido

```php
<?php
function getOrderDetails($meli, $orderId, $accessToken) {
    $result = $meli->get("/orders/$orderId", [
        'access_token' => $accessToken
    ]);
    
    if ($result['httpCode'] == 200) {
        return $result['body'];
    }
    
    return null;
}

$order = getOrderDetails($meli, 123456789, $_SESSION['access_token']);

if ($order) {
    echo "Estado: {$order->status}\n";
    echo "Método de pago: {$order->payments[0]->payment_method_id}\n";
    
    // Dirección de envío
    if (isset($order->shipping->receiver_address)) {
        $address = $order->shipping->receiver_address;
        echo "\nDirección de envío:\n";
        echo "{$address->street_name} {$address->street_number}\n";
        echo "{$address->city->name}, {$address->state->name}\n";
        echo "CP: {$address->zip_code}\n";
    }
}
```

### 5.3 Generar Etiqueta de Envío

```php
<?php
function getShippingLabel($meli, $shipmentId, $accessToken) {
    $result = $meli->get("/shipment_labels", [
        'shipment_ids' => $shipmentId,
        'access_token' => $accessToken
    ]);
    
    if ($result['httpCode'] == 200) {
        return $result['body']->shipment_labels[0]->pdf_label;
    }
    
    return null;
}

// Descargar etiqueta
$labelUrl = getShippingLabel($meli, 123456789, $_SESSION['access_token']);

if ($labelUrl) {
    echo "Descargar etiqueta: $labelUrl\n";
}
```

---

## 6. Notificaciones (Webhooks)

### 6.1 Configurar Webhook

```php
<?php
// Registrar URL para recibir notificaciones
function registerWebhook($meli, $topic, $callbackUrl, $accessToken) {
    $webhook = [
        'url' => $callbackUrl,
        'topic' => $topic  // items, orders, claims, questions
    ];
    
    $response = $meli->post('/webhooks', $webhook, [
        'access_token' => $accessToken
    ]);
    
    if ($response['httpCode'] == 201) {
        echo "✅ Webhook registrado para el tópico: $topic\n";
        echo "ID: {$response['body']->id}\n";
        return $response['body']->id;
    }
    
    return null;
}

// Registrar webhook para pedidos
registerWebhook(
    $meli,
    'orders',
    'https://miapp.com/webhooks/mercadolibre',
    $_SESSION['access_token']
);
```

### 6.2 Procesar Notificaciones

```php
<?php
// webhook_handler.php

// Recibir notificación
$json = file_get_contents('php://input');
$notification = json_decode($json, true);

// Validar estructura
if (!isset($notification['topic']) || !isset($notification['resource'])) {
    http_response_code(400);
    exit('Invalid notification');
}

$topic = $notification['topic'];
$resource = $notification['resource'];

// Procesar según el tópico
switch ($topic) {
    case 'orders':
        handleOrderNotification($resource);
        break;
        
    case 'items':
        handleItemNotification($resource);
        break;
        
    case 'questions':
        handleQuestionNotification($resource);
        break;
}

http_response_code(200);
echo 'OK';

function handleOrderNotification($resourceUrl) {
    global $meli, $accessToken;
    
    // Obtener ID del pedido desde el resource
    // Formato: /orders/123456789
    $orderId = basename($resourceUrl);
    
    $order = getOrderDetails($meli, $orderId, $accessToken);
    
    if ($order) {
        // Actualizar en tu base de datos
        error_log("Nuevo pedido: {$order->id} - Estado: {$order->status}");
        
        // Enviar email al vendedor
        mail(
            'vendedor@mitienda.com',
            'Nuevo pedido en MercadoLibre',
            "Pedido #{$order->id} por {$order->buyer->nickname}"
        );
    }
}

function handleQuestionNotification($resourceUrl) {
    global $meli, $accessToken;
    
    // Extraer ID de la pregunta
    $questionId = basename($resourceUrl);
    
    $result = $meli->get("/questions/$questionId", [
        'access_token' => $accessToken
    ]);
    
    if ($result['httpCode'] == 200) {
        $question = $result['body'];
        
        // Auto-responder si es posible
        error_log("Nueva pregunta: {$question->text}");
    }
}
```

---

## 7. Casos de Uso Avanzados

### 7.1 Sincronización Masiva de Inventario

```php
<?php
class InventorySync {
    private $meli;
    private $accessToken;
    private $mapping = [];  // producto_local_id => meli_item_id
    
    public function __construct($meli, $accessToken) {
        $this->meli = $meli;
        $this->accessToken = $accessToken;
    }
    
    public function syncFromDatabase($db) {
        $products = $db->query("SELECT * FROM products WHERE sync_meli = 1");
        
        foreach ($products as $product) {
            if (isset($this->mapping[$product['id']])) {
                // Actualizar existente
                $this->updateItem($product);
            } else {
                // Crear nuevo
                $meliId = $this->createItem($product);
                if ($meliId) {
                    $this->mapping[$product['id']] = $meliId;
                    $db->exec("UPDATE products SET meli_id = '$meliId' WHERE id = {$product['id']}");
                }
            }
            
            usleep(500000);  // Rate limiting: 0.5 segundos entre requests
        }
    }
    
    private function updateItem($product) {
        $meliId = $this->mapping[$product['id']];
        
        $updates = [
            'available_quantity' => $product['stock'],
            'price' => $product['price']
        ];
        
        $result = $this->meli->put("/items/$meliId", $updates, [
            'access_token' => $this->accessToken
        ]);
        
        return $result['httpCode'] == 200;
    }
    
    private function createItem($product) {
        $item = [
            'title' => $product['name'],
            'category_id' => $product['category_meli'],
            'price' => $product['price'],
            'currency_id' => 'BRL',
            'available_quantity' => $product['stock'],
            'buying_mode' => 'buy_it_now',
            'listing_type_id' => 'gold_special',
            'condition' => 'new',
            'description' => ['plain_text' => $product['description']],
            'pictures' => array_map(function($img) {
                return ['source' => $img];
            }, explode(',', $product['images']))
        ];
        
        $result = $this->meli->post('/items', $item, [
            'access_token' => $this->accessToken
        ]);
        
        if ($result['httpCode'] == 201) {
            return $result['body']->id;
        }
        
        return null;
    }
}

// Uso
$sync = new InventorySync($meli, $_SESSION['access_token']);
$sync->syncFromDatabase($pdo);
```

### 7.2 Sistema de Auto-Respuesta Inteligente

```php
<?php
class AutoResponder {
    private $meli;
    private $accessToken;
    private $responses = [
        'stock' => 'Sí, tenemos stock disponible. Puedes comprar con confianza.',
        'envio' => 'Enviamos a todo el país con Mercado Envíos. El tiempo depende de tu ubicación.',
        'garantia' => 'Todos nuestros productos tienen garantía oficial de 12 meses.',
        'color' => 'Los colores disponibles están en las variaciones del producto.'
    ];
    
    public function __construct($meli, $accessToken) {
        $this->meli = $meli;
        $this->accessToken = $accessToken;
    }
    
    public function processUnansweredQuestions() {
        // Obtener preguntas sin responder
        $result = $this->meli->get('/questions/search', [
            'seller' => 'me',
            'status' => 'UNANSWERED',
            'access_token' => $this->accessToken
        ]);
        
        if ($result['httpCode'] != 200) return;
        
        foreach ($result['body']->questions as $question) {
            $answer = $this->findAnswer($question->text);
            
            if ($answer) {
                $this->answerQuestion($question->id, $answer);
                echo "Auto-respondida: {$question->text}\n";
            }
        }
    }
    
    private function findAnswer($questionText) {
        $text = strtolower($questionText);
        
        foreach ($this->responses as $keyword => $response) {
            if (strpos($text, $keyword) !== false) {
                return $response;
            }
        }
        
        return null;
    }
    
    private function answerQuestion($questionId, $text) {
        $this->meli->post('/answers', [
            'question_id' => $questionId,
            'text' => $text
        ], [
            'access_token' => $this->accessToken
        ]);
    }
}

// Ejecutar con cron cada 5 minutos
$autoResponder = new AutoResponder($meli, $_SESSION['access_token']);
$autoResponder->processUnansweredQuestions();
```

### 7.3 Reporte de Ventas

```php
<?php
function generateSalesReport($meli, $accessToken, $startDate, $endDate) {
    $orders = getOrders($meli, $accessToken, [
        'order.date_created.from' => $startDate->format('c'),
        'order.date_created.to' => $endDate->format('c')
    ]);
    
    $report = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'by_product' => [],
        'by_payment_method' => []
    ];
    
    foreach ($orders as $order) {
        $report['total_orders']++;
        $report['total_revenue'] += $order->total_amount;
        
        // Por producto
        foreach ($order->order_items as $item) {
            $itemTitle = $item->item->title;
            if (!isset($report['by_product'][$itemTitle])) {
                $report['by_product'][$itemTitle] = ['qty' => 0, 'revenue' => 0];
            }
            $report['by_product'][$itemTitle]['qty'] += $item->quantity;
            $report['by_product'][$itemTitle]['revenue'] += $item->full_unit_price * $item->quantity;
        }
        
        // Por método de pago
        $paymentMethod = $order->payments[0]->payment_method_id;
        if (!isset($report['by_payment_method'][$paymentMethod])) {
            $report['by_payment_method'][$paymentMethod] = 0;
        }
        $report['by_payment_method'][$paymentMethod]++;
    }
    
    return $report;
}

// Reporte del mes actual
$report = generateSalesReport(
    $meli,
    $_SESSION['access_token'],
    new DateTime('first day of this month'),
    new DateTime('now')
);

echo "Total de pedidos: {$report['total_orders']}\n";
echo "Ingresos totales: R$ " . number_format($report['total_revenue'], 2) . "\n\n";

echo "Top 5 productos más vendidos:\n";
arsort($report['by_product']);
foreach (array_slice($report['by_product'], 0, 5, true) as $product => $data) {
    echo "  $product: {$data['qty']} unidades (R$ " . number_format($data['revenue'], 2) . ")\n";
}
```

---

## 📚 Recursos Adicionales

- **Código de ejemplo completo**: Ver carpeta `/examples` en el repositorio
- **API Docs**: https://developers.mercadolibre.com/api-docs
- **Postman Collection**: Importa la colección oficial para probar endpoints

---

**Anterior**: [API_REFERENCE.md](./API_REFERENCE.md) | **Siguiente**: [CONTRIBUTING.md](./CONTRIBUTING.md)

