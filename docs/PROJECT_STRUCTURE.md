## Estructura del proyecto

El repositorio está organizado para separar claramente:

- La **librería núcleo** (el SDK PHP).
- La **aplicación de ejemplo** y scripts de demostración.
- La **configuración de despliegue** (Heroku) y pruebas automatizadas.

Esta sección explica “dónde vive cada cosa” para que un desarrollador nuevo pueda ubicarse rápidamente.

---

## Raíz del repositorio

- **`index.php`**
  - Página principal / **sample app**.
  - Muestra un flujo completo:
    - Lectura de credenciales desde `configApp.php`.
    - Instanciación de `Meli`.
    - Login OAuth, refresco de tokens y almacenamiento en `$_SESSION`.
    - Ejemplos básicos (GET de sitio, publicación de ítem de prueba) incrustados en la propia página.
  - Es ideal como **punto de entrada** para entender cómo se usa el SDK end‑to‑end.

- **`configApp.php`**
  - Archivo de **configuración de credenciales** de la app:
    - Lee `App_ID`, `Secret_Key`, `Redirect_URI` desde variables de entorno (modo Heroku).
    - Define el `siteId` por defecto (ej. `MLA`, `MLB`, `CBT`, etc.).
  - Incluye una alternativa comentada para proyectos que **no usan Heroku** (credenciales hard‑codeadas).

- **`Meli/`**
  - Carpeta de la **librería SDK**.
  - Contiene la clase principal:
    - `Meli/meli.php`: implementación completa del cliente.

- **`examples/`**
  - Scripts PHP autocontenidos, cada uno mostrando **un caso de uso específico** de la API:
    - `example_get.php`: consulta de información de un sitio (`GET /sites/{site_id}`).
    - `example_login.php`: flujo de login OAuth + manejo de sesión.
    - `example_list_item.php`: publicación de un ítem de prueba (`POST /items`).
    - `example_delete_question.php`: borrado de una pregunta (`DELETE /questions/{id}`).
    - `example_put_description.php`: actualización de la descripción de un ítem (`PUT /items/{id}/descriptions`).
  - Suelen requerir:
    - `../Meli/meli.php`
    - `../configApp.php`

- **`getting-started/`**
  - Recursos estáticos utilizados por `index.php`:
    - `style.css`: estilos de la landing.
    - `logo-developers.png`: logotipo de desarrolladores de Mercado Libre.
  - No contiene lógica de negocio, solo **presentación**.

- **`tests/`**
  - Infraestructura de **tests automatizados con PHPUnit**:
    - `phpunit.xml`: configuración de PHPUnit.
    - `bootstrap.php`: bootstrap de pruebas (autoload, configuración inicial).
    - `meli.php`: pruebas unitarias de la clase `Meli` (métodos OAuth, GET/POST/PUT/DELETE, `make_path`, etc.).
    - `_reports/`: reportes de cobertura HTML y otros formatos (`xunit`, `tap`, etc.).

- **`app.json`**
  - Manifiesto para despliegue en **Heroku**:
    - Nombre, descripción y metadata del proyecto.
    - Buildpack PHP (`heroku/php`).
    - Definición de variables de entorno (`App_ID`, `Secret_Key`, `Redirect_URI`).
  - Permite **one‑click deploy** desde Heroku.

- **`composer.json`**
  - Actualmente vacío (`{}`).
  - Espacio reservado para declarar dependencias y configuración de Composer si el proyecto evoluciona hacia un empaquetado estándar.

- **`README.md`**
  - Documentación original (en inglés) con:
    - Instanciación básica de `Meli`.
    - Ejemplos de llamadas GET/POST/PUT/DELETE.
    - Enlaces a documentación oficial y ejemplos.

- **`LICENSE`**
  - Licencia del proyecto (open source).

---

## Núcleo del SDK: `Meli/meli.php`

La clase `Meli` es el **corazón técnico** del proyecto. Desde el punto de vista de estructura y patrones:

- **Responsabilidades principales**
  - Gestionar la **configuración de endpoints**:
    - `self::$API_ROOT_URL`: URL base de la API (`https://api.mercadolibre.com`).
    - `self::$OAUTH_URL`: endpoint de token (`/oauth/token`).
    - `self::$AUTH_URL`: mapa de URLs OAuth por país / site id.
  - Encapsular el flujo **OAuth 2.0 Authorization Code**:
    - `getAuthUrl($redirect_uri, $auth_url)`
    - `authorize($code, $redirect_uri)`
    - `refreshAccessToken()`
  - Proveer métodos de alto nivel para **operaciones REST**:
    - `get($path, $params, $assoc = false)`
    - `post($path, $body, $params)`
    - `put($path, $body, $params)`
    - `delete($path, $params)`
    - `options($path, $params)`
  - Gestionar la **comunicación HTTP real** vía cURL:
    - `execute($path, $opts = [], $params = [], $assoc = false)`.
    - `make_path($path, $params = [])` construye las URLs finales.

- **Patrones de diseño aplicados**
  - **Fachada / Gateway**:
    - `Meli` presenta una interfaz simple mientras delega en cURL y en la API externa.
  - **Encapsulación de cliente HTTP**:
    - Los detalles de cURL (timeouts, headers, opciones) se esconden detrás de un método `execute`.
  - **Value Object ligero para configuración estática**:
    - `Meli::$AUTH_URL` funciona como un mapa inmutable de endpoints por país.
  - En las pruebas (`tests/meli.php`), se usa el patrón **Mock Object** (vía `getMock`) para:
    - Simular respuestas de `execute`.
    - Controlar el comportamiento de OAuth sin necesidad de llamar a la API real.

No se utiliza un framework MVC, ni un contenedor de inversión de control; es un diseño **minimalista y directo**, muy adecuado para una librería SDK.

---

## Aplicación de ejemplo y scripts de uso

### `index.php` (Landing + Demo)

Rol principal:

- Mostrar una **página HTML amigable** con:
  - Explicación textual (en inglés) de cómo funciona el SDK.
  - Enlaces a Getting Started, API Docs y comunidad.
  - Secciones guiadas para:
    - Autenticar al usuario con OAuth.
    - Ejecutar un GET a `/sites/{site_id}`.
    - Publicar un ítem de prueba (incluyendo un snippet de ejemplo).
  - Visualizar las **credenciales cargadas** (`App_Id`, `Secret_Key`, `Redirect_URI`, `Site_Id`).

Patrones / buenas prácticas:

- Separa presentación (HTML/CSS) de lógica de negocio básica (PHP con `Meli`).
- Reutiliza la clase `Meli` tanto para el flujo de login como para la publicación de ítems.
- Usa `$_SESSION` para mantener el estado de autenticación entre requests.

### Carpeta `examples/`

Cada archivo en `examples/` es un **workflow de negocio mínimo**, diseñado para ser copiado y adaptado:

- **`example_get.php`**
  - Demuestra un `GET` básico:
    - Construye la URL `/sites/{siteId}`.
    - Imprime el resultado de `Meli::get` formateado.

- **`example_login.php`**
  - Implementa el flujo completo de autenticación:
    - Redirección a OAuth.
    - Intercambio de `code` por tokens.
    - Refresco de tokens si expiraron.
    - Impresión del contenido de `$_SESSION`.

- **`example_list_item.php`**
  - Muestra cómo:
    - Autenticarse.
    - Construir el `body` de un ítem con múltiples atributos, fotos, términos de venta, etc.
    - Invocar `post('/items', $item, ['access_token' => ...])`.

- **`example_delete_question.php`**
  - Borrado de una pregunta existente usando `delete('/questions/{id}', ...)`.

- **`example_put_description.php`**
  - Actualización de descripción de ítem vía `put('/items/{id}/descriptions', $body, $params)`.

Estos archivos son la **mejor referencia práctica** para un desarrollador junior: contienen el mínimo de código extra y se centran en la llamada concreta a la API.

---

## Tests automatizados (`tests/`)

La carpeta `tests/` se centra en validar el comportamiento de la clase `Meli`:

- **`tests/meli.php`**
  - Clase de prueba `InitSDKTest` basada en `PHPUnit_Framework_TestCase`.
  - Cubre:
    - Generación de URLs de autorización (`testGetAuthUrl`).
    - Flujo de `authorize` y `refreshAccessToken` con respuestas controladas.
    - Métodos `get`, `post`, `put`, `delete`, `options` contra mocks de `execute`.
    - Construcción de URLs con parámetros en `make_path`.
  - Utiliza funciones globales mock (`getAuthorizeMock`, `getRefreshTokenMock`, `getSimpleCurl`) para simular distintas respuestas HTTP.

- **`tests/_reports/`**
  - Reportes de cobertura HTML (`index.html`, `meli.php.html`) y otros formatos (`xunit`, `tap`, `testdox`).
  - Útiles para:
    - Visualizar qué partes de `Meli` están mejor cubiertas.
    - Guiar nuevas contribuciones (añadir tests antes de ampliar funcionalidad).

Para un contribuidor open source, la ruta recomendada es:

1. Ejecutar los tests existentes.
2. Agregar tests nuevos en `tests/meli.php` que cubran la funcionalidad a modificar.
3. Asegurarse de que la cobertura se mantiene o mejora.

---

## Patrones de diseño detectados

- **Fachada / API Gateway**
  - La clase `Meli` presenta una interfaz única y sencilla para múltiples operaciones distintas de la API externa.

- **Encapsulación de cliente HTTP**
  - La lógica de cURL y construcción de URLs está centralizada en `execute` y `make_path`.
  - Los métodos de alto nivel (`get`, `post`, `put`, `delete`, `options`) actúan como “atajos semánticos”.

- **Mock Object (en tests)**
  - Se usa `getMock` de PHPUnit para reemplazar `execute` con implementaciones simuladas.
  - Permite:
    - Probar lógica del SDK sin depender de la red.
    - Simular distintos códigos de respuesta HTTP y cuerpos.

- **Configuración mediante entorno**
  - Uso de variables de entorno (`getenv`) combinado con `configApp.php`.
  - Facilita despliegues en PaaS como Heroku.

No hay uso de patrones más complejos (Repository, Service Layer, MVC completo), por diseño: el objetivo es mantener el SDK **ligero y fácil de integrar** en cualquier tipo de aplicación PHP (framework propio, Laravel, Symfony, scripts planos, etc.).

---

## Cómo orientarse como desarrollador nuevo

Si eres un desarrollador que recién se suma al proyecto, se recomienda el siguiente recorrido:

1. **Leer `README.md`** para entender el propósito general y ejemplos básicos de uso.
2. **Leer `docs/OVERVIEW.md`** (este archivo se asume que ya lo has visto) para comprender arquitectura y flujos.
3. **Explorar `Meli/meli.php`**:
   - Identificar métodos públicos (`get`, `post`, `put`, `delete`, `options`, `authorize`, `refreshAccessToken`, `getAuthUrl`).
4. **Ejecutar `index.php` y los archivos en `examples/`** para ver la integración real con la API.
5. **Revisar `tests/meli.php`** para entender casos borde y comportamiento esperado del SDK.

Con este mapa mental, deberías estar en condiciones de:

- Integrar el SDK en una aplicación PHP existente.
- Extenderlo con nuevos helpers o utilidades.
- Proponer mejoras a nivel de errores, validación o experiencia de desarrollador.


