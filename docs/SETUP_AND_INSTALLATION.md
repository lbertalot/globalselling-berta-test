## Objetivo de esta guía

Este documento explica **cómo instalar, configurar y ejecutar** el SDK PHP de Mercado Libre, tanto en entorno local como en Heroku.  
Está pensado para:

- Desarrolladores **junior** que necesitan instrucciones paso a paso.
- Desarrolladores **senior** que quieren un **resumen rápido** de requisitos y flujos.

---

## Requisitos previos

- **Cuenta de desarrollador en Mercado Libre**
  - Necesitas poder crear una aplicación en el panel **My Apps**.
  - URL de referencia: `https://developers.mercadolibre.com.ar/apps/home` (varía según el país).

- **Entorno PHP**
  - PHP 5.6+ (idealmente 7.x/8.x).
  - Extensiones de PHP:
    - `curl`
    - `json`
    - `session` (para ejemplos que usan `$_SESSION`).

- **Herramientas recomendadas**
  - Git (`git clone`).
  - Un servidor web con soporte para PHP:
    - Opciones:
      - Servidor embebido de PHP (`php -S`).
      - Apache/Nginx + PHP-FPM.
      - Heroku (PaaS) con buildpack PHP.

---

## Clonar el repositorio

1. Abre una terminal.
2. Ejecuta:

```bash
git clone https://github.com/mercadolibre/php-sdk.git
cd php-sdk
```

3. Verifica que tengas la estructura básica:
   - `index.php`
   - `configApp.php`
   - `Meli/meli.php`
   - `examples/`
   - `tests/`
   - `getting-started/`

---

## Crear una aplicación en Mercado Libre

1. Accede al panel **My Apps**:
   - Por ejemplo: `https://developers.mercadolibre.com.ar/apps/home`.
2. Crea una nueva aplicación.
3. Obtén:
   - **Application Id** (`App_ID`).
   - **Secret Key** (`Secret_Key`).
   - **Redirect URI** (`Redirect_URI`):
     - Debe apuntar a la URL pública donde se ejecuta tu aplicación.
     - Ejemplo para Heroku: `https://{tu-app}.herokuapp.com`.
4. Configura el **alcance (scopes)** y permisos según las operaciones que quieras realizar (lectura, escritura, etc.).

Guarda estos valores: los usarás en `configApp.php` o en variables de entorno.

---

## Configuración de credenciales

El proyecto ofrece dos formas principales de configurarse:

### 1. Usando variables de entorno (modo recomendado / Heroku)

`configApp.php` está preparado para leer:

- `App_ID`
- `Secret_Key`
- `Redirect_URI`

Desde el entorno (`getenv`).  
En Heroku puedes configurarlas con:

```bash
heroku config:set App_ID=TU_APP_ID
heroku config:set Secret_Key=TU_SECRET_KEY
heroku config:set Redirect_URI=https://tu-app.herokuapp.com
```

Para entornos locales, puedes exportarlas en tu shell:

```bash
export App_ID=TU_APP_ID
export Secret_Key=TU_SECRET_KEY
export Redirect_URI=http://localhost:8000
```

Asegúrate de que **`Redirect_URI` coincide exactamente** con la URL registrada en My Apps.

### 2. Configuración directa en `configApp.php` (sin Heroku)

Si no deseas usar variables de entorno:

1. Abre `configApp.php`.
2. Edita la sección comentada al final del archivo:

```php
// $appId = 'App_ID';
// $secretKey = 'Secret_Key';
// $redirectURI = 'Redirect_URI';
// $siteId = 'MLB';
```

3. Reemplaza con tus propios datos y descomenta las líneas:
   - `App_ID` → tu Application Id.
   - `Secret_Key` → tu Secret Key.
   - `Redirect_URI` → URL donde corre tu app (ej. `http://localhost:8000`).
   - `siteId` → país donde operará la app (`MLA`, `MLB`, `MLM`, `CBT`, etc.).

> **Nota:** para pruebas, puedes usar `CBT` (Cross Border Trade) o el site correspondiente a tu país.

---

## Ejecutar el proyecto en local

### 1. Usando el servidor embebido de PHP

1. En la raíz del proyecto, ejecuta:

```bash
php -S localhost:8000
```

2. Abre tu navegador en:

```text
http://localhost:8000
```

3. Verás la landing de **“Getting Started with Mercado Libre's PHP SDK”**.
4. Asegúrate de que:
   - Las credenciales mostradas en la sección “Your Credentials” son correctas.
   - El botón de autenticación OAuth aparece y te redirige al login de Mercado Libre.

### 2. Probar los ejemplos individuales

Con el servidor embebido activo:

- **GET de sitio**

  Visita:

  ```text
  http://localhost:8000/examples/example_get.php
  ```

- **Login OAuth**

  ```text
  http://localhost:8000/examples/example_login.php
  ```

- **Publicar ítem de prueba**

  1. Ajusta primero los campos de ítem en `examples/example_list_item.php` (categoría, currency, etc.) según tu `siteId`.
  2. Visita:

  ```text
  http://localhost:8000/examples/example_list_item.php
  ```

- **Borrar pregunta**

  Edita `examples/example_delete_question.php` para usar un `question_id` válido, luego visita la URL correspondiente.

- **Actualizar descripción**

  Edita `examples/example_put_description.php` con un `item_id` válido y una nueva descripción.

> Para un **junior**, la recomendación es comenzar con `example_get.php` y `example_login.php`, ya que tienen menos variables de negocio.

---

## Despliegue en Heroku

El proyecto está preparado para funcionar “out of the box” en Heroku gracias a `app.json` y al buildpack PHP.

### Opción 1: Botón de deploy (desde el README)

1. En GitHub, haz clic en el botón **“Deploy to Heroku”**.
2. Heroku te pedirá:
   - Nombre de la app.
   - Valores para:
     - `App_ID`
     - `Secret_Key`
     - `Redirect_URI`
3. Completa los datos (usando tu `Redirect_URI` con formato `https://{tu-app}.herokuapp.com`).
4. Heroku:
   - Clonará el repositorio.
   - Instalará PHP y extensiones.
   - Desplegará el slug.

Al finalizar, podrás acceder a:

```text
https://{tu-app}.herokuapp.com
```

### Opción 2: Despliegue manual con Git

1. Crea una app en Heroku:

```bash
heroku create tu-app
```

2. Configura las variables de entorno:

```bash
heroku config:set App_ID=TU_APP_ID
heroku config:set Secret_Key=TU_SECRET_KEY
heroku config:set Redirect_URI=https://tu-app.herokuapp.com
```

3. Haz push al repositorio:

```bash
git push heroku master
```

4. Abre la app:

```bash
heroku open
```

Deberías ver la misma landing que en local, pero ya desplegada en un dyno de Heroku.

---

## Ejecutar tests automatizados

Para contribuciones o validación de cambios en el SDK:

1. Instala PHPUnit (global o via Composer, según tu entorno).
2. Desde la raíz del proyecto, ejecuta:

```bash
phpunit -c tests/phpunit.xml
```

3. Revisa:
   - Que todos los tests de `tests/meli.php` pasen.
   - Los reportes en `tests/_reports/` para ver cobertura.

Para un desarrollador senior, esto es clave antes de enviar un Pull Request.

---

## Primeros pasos recomendados (onboarding rápido)

Para un **nuevo integrante del equipo**, se sugiere el siguiente camino:

1. **Levantar el proyecto en local**
   - Seguir la sección de “Ejecutar el proyecto en local”.
2. **Probar OAuth y GET de sitio**
   - Asegurarse de que `example_login.php` y `example_get.php` funcionan con tus credenciales.
3. **Leer código de la clase `Meli`**
   - Revisar `Meli/meli.php` para entender:
     - Cómo se construyen las rutas.
     - Cómo se ejecutan las llamadas HTTP.
4. **Modificar un ejemplo**
   - Cambiar `example_list_item.php` para publicar un ítem simple en tu sitio de pruebas (usando categorías y currency válidas).
5. **Ejecutar tests**
   - Correr PHPUnit y revisar la cobertura para entender qué partes del SDK están testeadas.

Después de estos pasos, el desarrollador tendrá:

- Entendimiento del flujo OAuth.
- Conocimiento práctico de cómo se invocan los endpoints más comunes.
- Confianza para integrar el SDK en una aplicación propia o extenderlo.

---

## Integración en una app propia (resumen)

Para usar el SDK fuera de este repositorio:

1. Copia la carpeta `Meli/` a tu proyecto (o añádela como dependencia si se publica como paquete).
2. Incluye la clase:

```php
require 'Meli/meli.php';
```

3. Instancia el cliente:

```php
$meli = new Meli($appId, $secretKey);
```

4. Implementa el flujo OAuth igual que en `examples/example_login.php`.
5. Usa los métodos:
   - `get`, `post`, `put`, `delete`, `options`
   - Reutilizando los ejemplos de esta carpeta como plantilla.

Con esto tendrás una integración limpia y reutilizable con la API de Mercado Libre.


