# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/).

## [Sin publicar]

Candidato a `0.2.0`. Refactor de compatibilidad con PHP 8.1-8.4 y Symfony
6.4/7.x/8.x. Se conserva la API pública de las versiones publicadas (`0.1.0` y
`0.1.1`), por lo que se puede publicar como `0.2.0`; la API pública no cambia
de forma incompatible.

### Añadido

- Soporte de Symfony 6.4, 7.x y 8.x. Antes el paquete declaraba `symfony/*: ^7.1`
  junto con `symfony/http-kernel: ^4.0`, una combinación que no se podía instalar.
- `symfony/http-kernel` como dependencia de producción, ya que el bundle extiende
  `Symfony\Component\HttpKernel\Bundle\Bundle`.
- `SieBanxico::setToken()`, `SieBanxico::getToken()` y `SieBanxico::getFixExchangeRate()`
  (alias con la errata corregida de `getFixEchangeRate()`).
- `SieBanxico::client()`, que crea el cliente de forma diferida.
- Opciones de configuración `token` y `http_client`, y los servicios
  `sie_banxico.client` y `Estratos\SieBanxico\SieBanxicoClient` cuando hay token.
- `SieBanxicoClient::__construct()` con la firma del padre, para poder inyectar un
  cliente [PSR-18] propio.
- Pruebas para el bundle, el cliente, la configuración y la extensión (PHPUnit 10/11).
- Matriz de CI de PHP y Symfony, incluyendo Symfony 8.0 sobre PHP 8.4.

### Cambiado

- `Configuration::getConfigTreeBuilder()` usa `new TreeBuilder($name)` y
  `getRootNode()`. `new TreeBuilder()` sin argumentos y `TreeBuilder::root()` fueron
  eliminados en Symfony 7.0, por lo que el bundle no podía arrancar en Symfony 7+.
- `SieBanxico::__construct(?string $token = null)`: el token es opcional para que el
  kernel pueda registrar el bundle con `new SieBanxico()` y configurarlo por YAML.
- `SieBanxico::getContainerExtension()` devuelve la extensión con el alias histórico
  `Estratos_how_to`. La implementación por defecto de Symfony lanza una
  `LogicException` porque el alias esperado para la clase `SieBanxico` sería
  `sie_banxico`.
- `ClientInit()` es idempotente y ya no es obligatorio antes de consultar un tipo de
  cambio.
- `SieBanxicoExtension::load()` procesa y valida la configuración y devuelve `void`.
- Los archivos de `src/` declaran `declare(strict_types=1)` y tipos de retorno.
- `composer.json`: se eliminó `symfony/yaml`, `symfony/dependency-injection` y
  `symfony/config` de `require-dev`; se eliminó el repositorio VCS que apuntaba al
  propio paquete; se actualizaron PHPUnit a `^10.5 || ^11.0` y PHPStan a `^2.0`, y se
  retiró `sebastian/phpcpd` y `squizlabs/php_codesniffer` (sin uso).
- `php-http/guzzle7-adapter` pasa de `require` a `require-dev` + `suggest`: el paquete
  sólo necesita una implementación [PSR-18], que puede aportar la aplicación.
- `extra.branch-alias` pasa de `3.0-dev` a `0.2-dev`, para que coincida con la
  numeración real del paquete (`0.1.x`).
- `tests/phpunit.xml` se migró al esquema moderno (PHPUnit 10/11).

### Eliminado

- `tests/App/AppKernel.php`, `tests/App/config.yml` y `tests/php-cs-fixer.config.php`.
  El kernel de pruebas apuntaba a una clase inexistente
  (`Estratos\SieBanxico\EstratosSieBanxico`) y la configuración de php-cs-fixer usaba
  la API de la versión 2, que ya no existe.

### Compatibilidad

- Se conservan las clases `SieBanxico` y `SieBanxicoClient`, el constructor con token,
  `ClientInit()`, `getExchangeRate()`, `getFixEchangeRate()`, `getEuroExchangeRate()`,
  las constantes públicas de configuración y el nodo `test_node`.

[PSR-18]: https://www.php-fig.org/psr/psr-18/
