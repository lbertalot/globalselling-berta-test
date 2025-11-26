# ⚙️ Configuración e Instalación

## 📋 Requisitos Previos

### Requisitos del Sistema
| Componente | Versión Mínima | Versión Recomendada | Notas |
|------------|----------------|---------------------|-------|
| **PHP** | 5.3.0 | 7.4+ o 8.x | El SDK es compatible con todas las versiones modernas |
| **cURL Extension** | Cualquiera | Latest | `php -m | grep curl` para verificar |
| **JSON Extension** | Cualquiera | Latest | Incluida por defecto en PHP 5.2+ |
| **OpenSSL** | 1.0.1+ | 1.1.1+ | Para conexiones HTTPS seguras |

### Requisitos de MercadoLibre
1. **Cuenta de MercadoLibre**: Crea una en el sitio de tu país
2. **Aplicación registrada**: Obtén tus credenciales en https://developers.mercadolibre.com/apps/home

---

## 🚀 Instalación

### Opción 1: Clone Manual (Recomendado para Desarrollo)

```bash
# 1. Clonar el repositorio
git clone https://github.com/mercadolibre/php-sdk.git
cd php-sdk

# 2. Verificar instalación de PHP y extensiones
php -v
php -m | grep -E 'curl|json'

# 3. Probar el SDK
php examples/example_get.php
```

---

### Opción 2: Descarga Directa

1. Descarga el ZIP desde GitHub: https://github.com/mercadolibre/php-sdk/archive/master.zip
2. Extrae en tu proyecto: `unzip php-sdk-master.zip`
3. Renombra la carpeta: `mv php-sdk-master mercadolibre-sdk`

---

### Opción 3: Composer (Para Proyectos Modernos)

Aunque el `composer.json` actual está vacío, puedes integrarlo manualmente:

```bash
# 1. En tu proyecto, agregar como dependencia local
composer config repositories.meli-sdk path /ruta/a/php-sdk
composer require mercadolibre/php-sdk:@dev

# 2. O usar autoload directo
# En tu composer.json
{
    "autoload": {
        "files": [
            "vendor/mercadolibre-sdk/Meli/meli.php"
        ]
    }
}
```

---

## 🔑 Configuración de Credenciales

### Paso 1: Crear una Aplicación en MercadoLibre

1. Ve a: https://developers.mercadolibre.com/apps/home
2. Haz clic en **"Crear aplicación"** o **"Create application"**
3. Completa el formulario:
   - **Nombre de la aplicación**: Tu nombre de proyecto
   - **Descripción corta**: Breve explicación
   - **Redirect URI**: `http://localhost:8000` (para desarrollo)
   - **Sitio**: Selecciona tu país (MLA, MLB, MLM, etc.)
4. Guarda y obtén:
   - **App ID** (Client ID)
   - **Secret Key** (Client Secret)

### Paso 2: Configurar `configApp.php`

Abre `configApp.php` y configura tus credenciales:

```php
<?php
/* MODO PRODUCCIÓN (Heroku) - Usa variables de entorno */
// $appId = getenv('App_ID');
// $secretKey = getenv('Secret_Key');
// $redirectURI = getenv('Redirect_URI');

/* MODO DESARROLLO LOCAL - Valores directos */
$appId = '1234567890123456';        // ← Tu App ID aquí
$secretKey = 'tu_secret_key_aqui';  // ← Tu Secret Key aquí
$redirectURI = 'http://localhost:8000/callback.php';  // ← Tu callback
$siteId = 'MLA';  // Cambia según tu país (MLA=Argentina, MLB=Brasil, MLM=México)
?>
```

### Paso 3: Actualizar Redirect URI en tu App

1. Vuelve a https://developers.mercadolibre.com/apps/home
2. Edita tu aplicación
3. En **"Redirect URI"** agrega:
   ```
   http://localhost:8000
   http://localhost:8000/examples/example_login.php
   http://localhost:8000/callback.php
   ```
4. Guarda los cambios

---

## 🧪 Verificación de la Instalación

### Test 1: Verificar que PHP funciona
```bash
php -v
# Debe mostrar: PHP 7.x.x o 8.x.x
```

### Test 2: Verificar extensiones
```bash
php -m | grep curl
# Debe mostrar: curl

php -m | grep json
# Debe mostrar: json
```

### Test 3: Probar el SDK sin autenticación
```bash
cd examples
php example_get.php
```

**Salida esperada**:
```
Array
(
    [body] => stdClass Object
        (
            [id] => MLA
            [name] => Argentina
            [country_id] => AR
            [default_currency_id] => ARS
            ...
        )
    [httpCode] => 200
)
```

### Test 4: Probar autenticación OAuth
```bash
# Inicia un servidor PHP local
php -S localhost:8000

# Abre en tu navegador:
# http://localhost:8000/examples/example_login.php
```

**Flujo esperado**:
1. Verás un link "Login using MercadoLibre oAuth 2.0"
2. Al hacer clic, serás redirigido a MercadoLibre
3. Inicia sesión y autoriza la app
4. Serás redirigido de vuelta con tus tokens

---

## 🏗️ Configuración para Diferentes Entornos

### Desarrollo Local

**configApp.php**:
```php
<?php
$appId = '1234567890';
$secretKey = 'dev_secret_key';
$redirectURI = 'http://localhost:8000/callback.php';
$siteId = 'MLB';  // Brasil para testing
```

**Servidor PHP**:
```bash
php -S localhost:8000
```

---

### Staging/Testing

**configApp.php**:
```php
<?php
$appId = getenv('STAGING_APP_ID');
$secretKey = getenv('STAGING_SECRET_KEY');
$redirectURI = 'https://staging.miapp.com/oauth/callback';
$siteId = 'MLA';
```

**Variables de entorno (.env)**:
```bash
export STAGING_APP_ID="1234567890"
export STAGING_SECRET_KEY="staging_secret"
```

---

### Producción

**configApp.php**:
```php
<?php
$appId = getenv('PROD_APP_ID') ?: die('Missing PROD_APP_ID');
$secretKey = getenv('PROD_SECRET_KEY') ?: die('Missing PROD_SECRET_KEY');
$redirectURI = getenv('REDIRECT_URI') ?: 'https://miapp.com/oauth/callback';
$siteId = getenv('SITE_ID') ?: 'MLM';
```

**Seguridad adicional**:
```php
// Deshabilitar errores en producción
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
}
```

---

## 🐳 Deploy en Contenedores (Docker)

### Dockerfile
```dockerfile
FROM php:8.1-apache

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Copiar el SDK
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80

# Variables de entorno
ENV App_ID=""
ENV Secret_Key=""
ENV Redirect_URI="http://localhost/callback.php"

CMD ["apache2-foreground"]
```

### docker-compose.yml
```yaml
version: '3.8'

services:
  meli-sdk:
    build: .
    ports:
      - "8000:80"
    environment:
      - App_ID=1234567890
      - Secret_Key=your_secret_key
      - Redirect_URI=http://localhost:8000/callback.php
    volumes:
      - ./configApp.php:/var/www/html/configApp.php
```

**Ejecutar**:
```bash
docker-compose up -d
# Accede a http://localhost:8000
```

---

## ☁️ Deploy en Heroku

### Opción 1: Deploy con Botón (Más Fácil)

1. Haz clic en el botón "Deploy to Heroku" en el README
2. Completa las variables de entorno:
   - `App_ID`
   - `Secret_Key`
   - `Redirect_URI`
3. Haz clic en "Deploy"

### Opción 2: Deploy Manual con Heroku CLI

```bash
# 1. Instalar Heroku CLI
# https://devcenter.heroku.com/articles/heroku-cli

# 2. Login
heroku login

# 3. Crear app
heroku create mi-app-meli-sdk

# 4. Configurar variables de entorno
heroku config:set App_ID=1234567890
heroku config:set Secret_Key=tu_secret_key
heroku config:set Redirect_URI=https://mi-app-meli-sdk.herokuapp.com

# 5. Deploy
git push heroku master

# 6. Abrir app
heroku open
```

### Actualizar Redirect URI en MercadoLibre
Después del deploy, actualiza tu aplicación en MercadoLibre con la nueva URL:
```
https://tu-app.herokuapp.com
```

---

## 🧩 Integración en Proyectos Existentes

### Framework Laravel

**1. Copiar el SDK**:
```bash
cp -r /ruta/a/php-sdk/Meli app/Libraries/
```

**2. Crear Service Provider**:
```php
// app/Providers/MeliServiceProvider.php
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
require_once app_path('Libraries/Meli/meli.php');

class MeliServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(\Meli::class, function ($app) {
            return new \Meli(
                config('services.mercadolibre.app_id'),
                config('services.mercadolibre.secret_key'),
                session('meli_access_token'),
                session('meli_refresh_token')
            );
        });
    }
}
```

**3. Configurar en `config/services.php`**:
```php
'mercadolibre' => [
    'app_id' => env('MELI_APP_ID'),
    'secret_key' => env('MELI_SECRET_KEY'),
    'redirect_uri' => env('MELI_REDIRECT_URI'),
    'site_id' => env('MELI_SITE_ID', 'MLB'),
],
```

**4. Usar en Controladores**:
```php
use Meli;

class ProductController extends Controller
{
    public function publish(Request $request, Meli $meli)
    {
        $item = [
            'title' => $request->title,
            'price' => $request->price,
            // ...
        ];
        
        $response = $meli->post('/items', $item, [
            'access_token' => session('meli_access_token')
        ]);
        
        return response()->json($response);
    }
}
```

---

### Framework Symfony

**1. Crear Service**:
```yaml
# config/services.yaml
services:
    App\Service\MeliService:
        arguments:
            $appId: '%env(MELI_APP_ID)%'
            $secretKey: '%env(MELI_SECRET_KEY)%'
```

**2. Clase Service**:
```php
// src/Service/MeliService.php
<?php
namespace App\Service;

require_once __DIR__ . '/../../vendor/meli-sdk/Meli/meli.php';

class MeliService
{
    private $meli;
    
    public function __construct(string $appId, string $secretKey)
    {
        $this->meli = new \Meli($appId, $secretKey);
    }
    
    public function getMeli(): \Meli
    {
        return $this->meli;
    }
}
```

---

### WordPress Plugin

**1. Estructura del Plugin**:
```
wp-content/
└── plugins/
    └── meli-integration/
        ├── meli-integration.php
        ├── includes/
        │   └── Meli/
        │       └── meli.php
        └── admin/
            └── settings.php
```

**2. Plugin Principal**:
```php
<?php
/**
 * Plugin Name: MercadoLibre Integration
 * Description: Integración con MercadoLibre API
 */

require_once plugin_dir_path(__FILE__) . 'includes/Meli/meli.php';

function meli_get_instance() {
    return new Meli(
        get_option('meli_app_id'),
        get_option('meli_secret_key'),
        get_option('meli_access_token'),
        get_option('meli_refresh_token')
    );
}

// Usar en cualquier parte:
// $meli = meli_get_instance();
// $result = $meli->get('/users/me', ['access_token' => get_option('meli_access_token')]);
```

---

## 🔒 Configuración de Seguridad

### 1. Proteger credenciales

**Nunca hagas esto**:
```php
// ❌ MAL - Credenciales en código
$appId = '1234567890';
$secretKey = 'mi_secret_super_secreto';
```

**Haz esto**:
```php
// ✅ BIEN - Variables de entorno
$appId = getenv('MELI_APP_ID') ?: die('Missing credentials');
$secretKey = getenv('MELI_SECRET_KEY') ?: die('Missing credentials');
```

### 2. Configurar `.gitignore`
```bash
# .gitignore
configApp.php
.env
*.log
vendor/
```

### 3. Usar HTTPS en Producción
```php
// Forzar HTTPS
if (getenv('APP_ENV') === 'production' && $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### 4. Validar Redirect URI
```php
// Whitelist de URIs permitidas
$allowedRedirects = [
    'https://miapp.com/callback',
    'https://staging.miapp.com/callback'
];

if (!in_array($redirectURI, $allowedRedirects)) {
    die('Invalid redirect URI');
}
```

---

## 🐛 Troubleshooting de Instalación

### Error: "Call to undefined function curl_init()"
**Solución**:
```bash
# Ubuntu/Debian
sudo apt-get install php-curl
sudo service apache2 restart

# CentOS/RHEL
sudo yum install php-curl
sudo systemctl restart httpd

# macOS (Homebrew)
brew install php
# cURL viene incluido por defecto
```

### Error: "SSL certificate problem"
**Solución**:
```php
// Agregar en meli.php (SOLO PARA DESARROLLO)
public static $CURL_OPTS = array(
    CURLOPT_USERAGENT => "MELI-PHP-SDK-2.0.0",
    CURLOPT_SSL_VERIFYPEER => false,  // ← Solo en desarrollo local
    // ...
);
```

**Mejor solución (producción)**:
```bash
# Actualizar certificados CA
sudo apt-get update
sudo apt-get install ca-certificates
```

### Error: "Invalid redirect_uri"
**Causa**: La URI de callback no coincide con la registrada en MercadoLibre.

**Solución**:
1. Ve a https://developers.mercadolibre.com/apps/home
2. Edita tu aplicación
3. Asegúrate que el Redirect URI coincida EXACTAMENTE con tu `$redirectURI`
4. Incluye HTTP/HTTPS, puerto y path completos

### Error: "grant_type not supported"
**Causa**: Cuerpo de la petición OAuth mal formado.

**Solución**: Verifica que `configApp.php` tenga las credenciales correctas.

---

## ✅ Checklist de Instalación

- [ ] PHP >= 5.3 instalado y funcionando
- [ ] Extensión cURL habilitada (`php -m | grep curl`)
- [ ] Extensión JSON habilitada (`php -m | grep json`)
- [ ] SDK clonado o descargado
- [ ] Aplicación creada en MercadoLibre Developers
- [ ] App ID y Secret Key obtenidos
- [ ] `configApp.php` configurado con credenciales
- [ ] Redirect URI actualizado en la aplicación de MercadoLibre
- [ ] Test `example_get.php` ejecutado exitosamente
- [ ] Test `example_login.php` ejecutado y tokens obtenidos
- [ ] (Opcional) Variables de entorno configuradas
- [ ] (Opcional) Deploy en producción realizado

---

## 📚 Próximos Pasos

1. **Leer**: [API_REFERENCE.md](./API_REFERENCE.md) - Documentación completa de métodos
2. **Explorar**: [EXAMPLES.md](./EXAMPLES.md) - Casos de uso detallados
3. **Probar**: Ejecuta `examples/example_list_item.php` para publicar un producto
4. **Integrar**: Incorpora el SDK en tu proyecto siguiendo los ejemplos de frameworks

---

**¿Problemas con la instalación?** Consulta [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) o abre un issue en GitHub.

