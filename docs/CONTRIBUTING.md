# 🤝 Guía de Contribución

¡Gracias por tu interés en contribuir al SDK oficial de PHP para MercadoLibre! Este documento te guiará a través del proceso de contribución.

---

## 📋 Tabla de Contenidos

1. [Código de Conducta](#código-de-conducta)
2. [¿Cómo puedo contribuir?](#cómo-puedo-contribuir)
3. [Configuración del Entorno de Desarrollo](#configuración-del-entorno-de-desarrollo)
4. [Flujo de Trabajo con Git](#flujo-de-trabajo-con-git)
5. [Estándares de Código](#estándares-de-código)
6. [Guía de Testing](#guía-de-testing)
7. [Proceso de Revisión](#proceso-de-revisión)
8. [Reportar Bugs](#reportar-bugs)
9. [Sugerir Mejoras](#sugerir-mejoras)

---

## Código de Conducta

Este proyecto sigue el [Código de Conducta de Contributor Covenant](https://www.contributor-covenant.org/). Al participar, te comprometes a mantener un ambiente respetuoso y acogedor para todos.

### Comportamientos Esperados

✅ Ser respetuoso con diferentes puntos de vista  
✅ Aceptar críticas constructivas con gracia  
✅ Enfocarse en lo que es mejor para la comunidad  
✅ Mostrar empatía hacia otros miembros

### Comportamientos Inaceptables

❌ Lenguaje o imágenes sexualizadas  
❌ Comentarios insultantes o despectivos  
❌ Acoso público o privado  
❌ Publicar información privada de otros sin permiso

---

## ¿Cómo puedo contribuir?

### 🐛 Reportar Bugs

Si encuentras un bug, por favor:

1. **Busca primero** en los [issues existentes](https://github.com/mercadolibre/php-sdk/issues) para evitar duplicados
2. Si no existe, [crea un nuevo issue](https://github.com/mercadolibre/php-sdk/issues/new) con:
   - Título descriptivo
   - Pasos para reproducir el error
   - Comportamiento esperado vs. comportamiento actual
   - Versión de PHP y del SDK
   - Código de ejemplo que reproduce el bug

**Plantilla de Bug Report**:
```markdown
## Descripción del Bug
[Descripción clara y concisa del problema]

## Pasos para Reproducir
1. Instanciar Meli con credenciales...
2. Llamar método get()...
3. Ver error...

## Comportamiento Esperado
[Lo que debería suceder]

## Comportamiento Actual
[Lo que realmente sucede]

## Entorno
- PHP Version: 7.4.28
- SDK Version: 2.0.0
- OS: macOS 12.3

## Código de Ejemplo
```php
$meli = new Meli('...', '...');
$result = $meli->get('/users/me');
// Error aquí
```
```

---

### 💡 Sugerir Nuevas Funcionalidades

¿Tienes una idea para mejorar el SDK? 

1. Abre un issue con la etiqueta `enhancement`
2. Describe:
   - ¿Qué problema resuelve tu propuesta?
   - ¿Cómo lo implementarías?
   - ¿Existen alternativas?

**Plantilla de Feature Request**:
```markdown
## Descripción de la Funcionalidad
[Descripción clara de lo que propones]

## Problema que Resuelve
[Por qué es útil esta funcionalidad]

## Propuesta de Implementación
[Cómo lo implementarías técnicamente]

## Alternativas Consideradas
[Otras formas de resolver el mismo problema]

## Ejemplo de Uso
```php
// Código mostrando cómo se usaría la nueva funcionalidad
$meli->newFeature();
```
```

---

### 🔧 Contribuir Código

#### Tipos de Contribuciones Bienvenidas

- **Corrección de bugs**: Fixes a problemas reportados
- **Nuevas funcionalidades**: Métodos o características adicionales
- **Mejoras de performance**: Optimizaciones
- **Documentación**: Mejoras en comentarios, README, o docs
- **Tests**: Aumentar cobertura de testing
- **Ejemplos**: Nuevos casos de uso en `/examples`

---

## Configuración del Entorno de Desarrollo

### Requisitos

- PHP >= 5.3 (recomendado 7.4+ o 8.x)
- Git
- Composer (opcional, pero recomendado)
- PHPUnit para testing

### Setup Inicial

```bash
# 1. Fork del repositorio en GitHub
# Haz clic en "Fork" en https://github.com/mercadolibre/php-sdk

# 2. Clonar tu fork
git clone https://github.com/TU_USUARIO/php-sdk.git
cd php-sdk

# 3. Agregar el repositorio original como upstream
git remote add upstream https://github.com/mercadolibre/php-sdk.git

# 4. Instalar dependencias de desarrollo (si usas Composer)
composer install --dev

# 5. Configurar credenciales de prueba
cp configApp.php.example configApp.php
# Editar configApp.php con tus credenciales de testing

# 6. Verificar que los tests pasen
cd tests
phpunit
```

---

## Flujo de Trabajo con Git

### 1. Crear una Rama de Trabajo

```bash
# Actualizar tu fork con los últimos cambios
git checkout master
git pull upstream master

# Crear rama descriptiva
git checkout -b feature/add-batch-operations
# o
git checkout -b fix/auth-token-refresh
```

### Convención de Nombres de Ramas

- `feature/nombre-corto`: Para nuevas funcionalidades
- `fix/nombre-corto`: Para corrección de bugs
- `docs/nombre-corto`: Para mejoras en documentación
- `refactor/nombre-corto`: Para refactorización de código
- `test/nombre-corto`: Para agregar o mejorar tests

---

### 2. Hacer Cambios

```bash
# Editar archivos
vim Meli/meli.php

# Ver cambios
git status
git diff

# Agregar cambios al stage
git add Meli/meli.php

# Commit con mensaje descriptivo
git commit -m "feat: Add batch update method for multiple items

- Implement updateBatch() method
- Add rate limiting to prevent API throttling
- Include unit tests for new functionality
- Update documentation with examples"
```

---

### 3. Convención de Commits

Usamos [Conventional Commits](https://www.conventionalcommits.org/):

**Formato**:
```
<tipo>(<scope>): <descripción corta>

<descripción detallada>

<footer>
```

**Tipos permitidos**:
- `feat`: Nueva funcionalidad
- `fix`: Corrección de bug
- `docs`: Solo cambios en documentación
- `style`: Cambios de formato (espacios, punto y coma, etc.)
- `refactor`: Refactorización sin cambiar funcionalidad
- `test`: Agregar o modificar tests
- `chore`: Cambios en build, herramientas, etc.

**Ejemplos**:

```bash
# Nueva funcionalidad
git commit -m "feat: Add support for OPTIONS HTTP method"

# Corrección de bug
git commit -m "fix: Correct token expiration validation logic

The previous implementation was comparing timestamps incorrectly,
causing premature token refresh requests.

Fixes #123"

# Documentación
git commit -m "docs: Add examples for bulk product updates"

# Tests
git commit -m "test: Add unit tests for refreshAccessToken method"
```

---

### 4. Ejecutar Tests

Antes de hacer push, asegúrate que todos los tests pasen:

```bash
cd tests
phpunit --testdox

# Verificar cobertura de código
phpunit --coverage-html _reports/coverage
open _reports/coverage/index.html
```

---

### 5. Push y Pull Request

```bash
# Push a tu fork
git push origin feature/add-batch-operations

# En GitHub, abre un Pull Request desde tu rama hacia master del repo original
```

---

## Estándares de Código

### Estilo de Código PHP

Seguimos **PSR-2** con algunas adaptaciones para mantener consistencia con el código existente.

#### Indentación y Espaciado

```php
// ✅ CORRECTO
public function get($path, $params = null, $assoc = false) {
    $uri = $this->make_path($path, $params);
    
    if (!empty($params)) {
        // código...
    }
    
    return $result;
}

// ❌ INCORRECTO
public function get($path,$params=null,$assoc=false){
  $uri=$this->make_path($path,$params);
  if(!empty($params)){
    // código...
  }
  return $result;
}
```

#### Nomenclatura

```php
// Clases: PascalCase
class Meli { }

// Métodos públicos: camelCase
public function getAuthUrl() { }
public function refreshAccessToken() { }

// Variables: snake_case
$access_token = '...';
$client_id = '...';

// Constantes: UPPER_SNAKE_CASE
const VERSION = "2.0.0";
protected static $API_ROOT_URL = "...";
```

#### Documentación con PHPDoc

```php
/**
 * Execute a GET Request to MercadoLibre API
 * 
 * @param string $path Endpoint path (e.g., '/users/me')
 * @param array|null $params Query string parameters
 * @param bool $assoc Return associative array instead of object
 * @return array Response with 'body' and 'httpCode'
 * 
 * @example
 * $result = $meli->get('/sites/MLB');
 * echo $result['body']->name;  // "Brasil"
 */
public function get($path, $params = null, $assoc = false) {
    // implementación...
}
```

---

### Mejores Prácticas

#### 1. Validación de Parámetros

```php
// ✅ CORRECTO - Validar entradas
public function authorize($code, $redirect_uri) {
    if (empty($code)) {
        throw new InvalidArgumentException('Authorization code is required');
    }
    
    if (!filter_var($redirect_uri, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('Invalid redirect URI');
    }
    
    // continuar...
}
```

#### 2. Manejo de Errores Consistente

```php
// ✅ CORRECTO - Retornar estructura consistente
public function get($path, $params = null, $assoc = false) {
    try {
        $exec = $this->execute($path, null, $params, $assoc);
        return $exec;
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'httpCode' => 0
        ];
    }
}
```

#### 3. DRY (Don't Repeat Yourself)

```php
// ❌ INCORRECTO - Código duplicado
public function post($path, $body = null, $params = array()) {
    $body = json_encode($body);
    $opts = array(/* ... */);
    
    $uri = $this->make_path($path, $params);
    $ch = curl_init($uri);
    curl_setopt_array($ch, self::$CURL_OPTS);
    // ... mucho código repetido ...
}

// ✅ CORRECTO - Reutilizar método base
public function post($path, $body = null, $params = array()) {
    $body = json_encode($body);
    $opts = array(
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_POST => true, 
        CURLOPT_POSTFIELDS => $body
    );
    
    return $this->execute($path, $opts, $params);
}
```

---

## Guía de Testing

### Estructura de Tests

```php
// tests/meli.php
<?php
require_once 'bootstrap.php';

class MeliTest extends PHPUnit\Framework\TestCase
{
    protected $meli;
    
    protected function setUp(): void
    {
        $this->meli = new Meli('test_client_id', 'test_secret');
    }
    
    public function testConstructorSetsCredentials()
    {
        $this->assertNotNull($this->meli);
        // Más aserciones...
    }
    
    public function testGetAuthUrlReturnsValidUrl()
    {
        $authUrl = $this->meli->getAuthUrl(
            'http://localhost/callback',
            Meli::$AUTH_URL['MLB']
        );
        
        $this->assertStringContainsString('auth.mercadolivre.com.br', $authUrl);
        $this->assertStringContainsString('client_id=test_client_id', $authUrl);
        $this->assertStringContainsString('response_type=code', $authUrl);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testAuthorizeThrowsExceptionWithEmptyCode()
    {
        $this->meli->authorize('', 'http://localhost/callback');
    }
}
```

### Ejecutar Tests

```bash
# Todos los tests
phpunit

# Test específico
phpunit --filter testGetAuthUrlReturnsValidUrl

# Con cobertura
phpunit --coverage-text

# Formato testdox (legible)
phpunit --testdox
```

### Cobertura Mínima

- **Métodos públicos**: 100% de cobertura
- **Casos edge**: Incluir tests para valores null, vacíos, inválidos
- **Errores**: Testear manejo de errores y excepciones

---

## Proceso de Revisión

### Checklist Antes de Enviar PR

- [ ] Código sigue los estándares de estilo
- [ ] Todos los tests pasan (`phpunit`)
- [ ] Agregaste tests para tu nueva funcionalidad/fix
- [ ] Actualizaste la documentación relevante
- [ ] Los commits siguen Conventional Commits
- [ ] La rama está actualizada con `upstream/master`
- [ ] PHPDoc actualizado en métodos modificados/nuevos

### Descripción del Pull Request

```markdown
## Tipo de Cambio
- [ ] Bug fix (cambio no-breaking que soluciona un issue)
- [ ] Nueva funcionalidad (cambio no-breaking que agrega funcionalidad)
- [ ] Breaking change (fix o feature que causa que funcionalidad existente no funcione como antes)
- [ ] Documentación

## Descripción
[Describe tus cambios en detalle]

## Motivación y Contexto
[Por qué es necesario este cambio? ¿Qué problema resuelve?]
[Si cierra un issue, mencionar: Fixes #123]

## ¿Cómo se ha testeado?
- [ ] Tests unitarios
- [ ] Tests de integración
- [ ] Tests manuales

Describe los tests que ejecutaste para verificar tus cambios.

## Capturas de pantalla (si aplica)

## Checklist
- [ ] Mi código sigue el estilo del proyecto
- [ ] He realizado auto-revisión de mi código
- [ ] He comentado mi código, especialmente en áreas difíciles
- [ ] He actualizado la documentación
- [ ] Mis cambios no generan nuevos warnings
- [ ] He agregado tests que prueban mi fix/funcionalidad
- [ ] Todos los tests (nuevos y existentes) pasan
```

---

### Revisión de Código

Los maintainers revisarán tu PR y pueden:

1. **Aprobar**: Tu código será merged
2. **Solicitar cambios**: Deberás hacer modificaciones
3. **Comentar**: Sugerencias sin bloquear el merge

**Tiempo de respuesta esperado**: 3-5 días hábiles

---

## Reportar Bugs

### Información Requerida

Al reportar un bug, incluye:

1. **Versión de PHP**: `php -v`
2. **Versión del SDK**: Ver en `Meli/meli.php` línea 8
3. **Sistema Operativo**: Windows, macOS, Linux
4. **Descripción detallada** del problema
5. **Código mínimo** para reproducir
6. **Logs de error** si los hay

### Ejemplo de Buen Reporte

```markdown
**Descripción**
El método refreshAccessToken() lanza un error cuando el refresh_token es null.

**Pasos para Reproducir**
1. Crear instancia sin refresh_token
2. Llamar a refreshAccessToken()
3. Ver error

**Código**
```php
$meli = new Meli('app_id', 'secret');
$result = $meli->refreshAccessToken();
// Error: Trying to get property of non-object
```

**Error Completo**
```
Notice: Trying to get property of non-object in Meli/meli.php on line 147
```

**Entorno**
- PHP: 7.4.28
- SDK: 2.0.0
- OS: Ubuntu 20.04
```

---

## Sugerir Mejoras

### Áreas de Mejora Bienvenidas

1. **Performance**: Optimizaciones en peticiones cURL
2. **Seguridad**: Mejoras en validación de inputs
3. **Developer Experience**: APIs más intuitivas
4. **Documentación**: Más ejemplos, mejores explicaciones
5. **Testing**: Mayor cobertura de tests

### Proceso de Discusión

1. Abre un issue con etiqueta `enhancement`
2. Espera feedback de maintainers y comunidad
3. Si es aprobado, implementa y envía PR
4. Si no es aprobado, considera alternativas o forks

---

## Recursos Adicionales

- **Documentación de la API**: https://developers.mercadolibre.com/api-docs
- **Foro de Desarrolladores**: https://developers.mercadolibre.com/community
- **Guía de Estilo PHP**: https://www.php-fig.org/psr/psr-2/
- **PHPUnit Docs**: https://phpunit.de/documentation.html

---

## Reconocimientos

Todos los contribuidores serán listados en el README y agradecidos públicamente.

¡Gracias por hacer que el SDK de MercadoLibre sea mejor! 🎉

---

**Preguntas?** Abre un issue con la etiqueta `question` o únete a la [comunidad de desarrolladores](https://developers.mercadolibre.com/community).

