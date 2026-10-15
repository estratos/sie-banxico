# sie-banxico

Cliente Symfony para la API REST del Sistema de Información Económica (SIE) del Banco de México (Banxico).

## Requisitos

| Componente | Versiones soportadas |
| --- | --- |
| PHP | `8.1`, `8.2`, `8.3`, `8.4` |
| Symfony (`config`, `dependency-injection`, `http-kernel`) | `6.4`, `7.x`, `8.x` |
| Cliente HTTP | Cualquiera conforme a [PSR-18] (y una fábrica [PSR-17]) |

Symfony `7.4` requiere PHP `>= 8.2` y Symfony `8.x` requiere PHP `>= 8.4`; el paquete sólo declara `php: >=8.1` y deja que Composer elija la combinación válida para tu plataforma.

El paquete no impone un cliente HTTP: declara `php-http/guzzle7-adapter` en `require-dev` para poder ejecutar sus pruebas y en `suggest` como recomendación. Si tu aplicación todavía no tiene un cliente [PSR-18], instálalo:

```bash
composer require php-http/guzzle7-adapter
```

## Instalación

```bash
composer require estratos/sie-banxico
```

## Configuración

Registra el bundle en `config/bundles.php`:

```php
<?php

return [
    // ...
    Estratos\SieBanxico\SieBanxico::class => ['all' => true],
];
```

Y define tu token:

```yaml
# config/packages/estratos_how_to.yaml
Estratos_how_to:
    token: '%env(BANXICO_SIE_TOKEN)%'
```

> El nodo raíz de configuración se llama `Estratos_how_to` por compatibilidad con las versiones 1.x y 2.x. Es un nombre no convencional a propósito: cambiarlo rompería la configuración de las aplicaciones existentes.

Con el token configurado, el bundle registra dos servicios:

| Servicio | Descripción |
| --- | --- |
| `sie_banxico.client` | `Estratos\SieBanxico\SieBanxicoClient` con el token inyectado. |
| `Estratos\SieBanxico\SieBanxicoClient` | Alias del anterior, para inyección por tipo. |

Si tu proyecto tiene más de un cliente PSR-18, indica cuál debe usarse:

```yaml
Estratos_how_to:
    token: '%env(BANXICO_SIE_TOKEN)%'
    http_client: 'app.http_client'
```

Si no configuras `token`, el bundle no registra servicios y puedes seguir usando las clases como librería (ver más abajo).

## Uso

### Inyección de dependencias (recomendado)

```php
<?php

namespace App\Controller;

use Estratos\SieBanxico\SieBanxicoClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BanxicoController
{
    public function __construct(private readonly SieBanxicoClient $sie)
    {
    }

    #[Route('/banxico/tipo', name: 'app_banxico_api_tipo')]
    public function getBanxicoRate(): Response
    {
        return $this->json([
            'liquidacion' => $this->sie->exchangeRateUsdLiquidation(),
            'determinacion' => $this->sie->exchangeRateUsdDetermination(),
            'euro' => $this->sie->exchangeRateEuroDetermination(),
        ]);
    }
}
```

### Uso directo (compatible con versiones anteriores)

El flujo de las versiones 1.x y 2.x sigue funcionando sin cambios:

```php
use Estratos\SieBanxico\SieBanxico;

$service = new SieBanxico($token);
$service->ClientInit();

$tipoDeCambio = $service->getExchangeRate();
```

`ClientInit()` es idempotente y opcional: si se omite, el cliente se crea la primera vez que se consulta un tipo de cambio.

### Métodos disponibles

| Método | Serie Banxico | Descripción |
| --- | --- | --- |
| `getExchangeRate()` | `SF60653` | Tipo de cambio USD (liquidación). |
| `getFixEchangeRate()` | `SF43718` | Tipo de cambio USD (determinación). Nombre con errata conservado por compatibilidad. |
| `getFixExchangeRate()` | `SF43718` | Alias con la errata corregida. |
| `getEuroExchangeRate()` | `SF46410` | Tipo de cambio EUR (determinación). |

En `SieBanxicoClient` están disponibles los métodos heredados de `xint0/banxico-php`:

| Método | Descripción |
| --- | --- |
| `exchangeRateUsdLiquidation(?string $startDate = null, ?string $endDate = null)` | Serie `SF60653`. |
| `exchangeRateUsdDetermination(?string $startDate = null, ?string $endDate = null)` | Serie `SF43718`. |
| `exchangeRateEuroDetermination(?string $startDate = null, ?string $endDate = null)` | Serie `SF46410`. |
| `fetchSeries(string $series, ?string $startDate = null, ?string $endDate = null)` | Cualquier serie del SIE. |

Sin fechas se consulta el dato más reciente (`oportuno`). Con fechas se consulta el rango indicado. El resultado es un `string` cuando hay un solo dato y un arreglo `['YYYY-MM-DD' => valor]` cuando hay varios.

## Desarrollo

```bash
composer install
vendor/bin/phpunit -c tests/phpunit.xml
vendor/bin/phpstan analyse -c tests/phpstan.neon
```

### Probar contra varias versiones de Symfony

El directorio `.compat/` (ignorado por git) contiene ambientes aislados con una versión concreta de Symfony. `tests/bootstrap.php` respeta la variable de entorno `SIE_BANXICO_COMPAT_VENDOR` para cargar las dependencias de esos ambientes:

```bash
mkdir -p .compat/6.4 && cp .compat/composer.json .compat/6.4/
(cd .compat/6.4 && composer require "symfony/config:6.4.*" "symfony/dependency-injection:6.4.*" "symfony/http-kernel:6.4.*" "symfony/yaml:6.4.*")

SIE_BANXICO_COMPAT_VENDOR="$PWD/.compat/6.4/vendor" \
  vendor/bin/phpunit --bootstrap tests/bootstrap.php tests
```

En Windows (PowerShell):

```powershell
$env:SIE_BANXICO_COMPAT_VENDOR = "$PWD\.compat\6.4\vendor"
php vendor/bin/phpunit --bootstrap tests/bootstrap.php tests
```

La matriz completa de PHP y Symfony se ejecuta en GitHub Actions (`.github/workflows/symfony.yml`), incluyendo Symfony `8.0` sobre PHP `8.4`.

## Compatibilidad hacia atrás

Se conserva la API pública de las versiones 1.x y 2.x:

- Clases `Estratos\SieBanxico\SieBanxico` y `Estratos\SieBanxico\SieBanxicoClient` con sus nombres y métodos.
- Constructor `new SieBanxico($token)` (el argumento ahora es opcional) y `ClientInit()`.
- Métodos `getExchangeRate()`, `getFixEchangeRate()` (con la errata original) y `getEuroExchangeRate()`.
- Constantes `Configuration::ROOT_NAME` (`Estratos_how_to`), `Configuration::NODE_TEST` y `SieBanxicoClient::SERIES_EURO_EXCHANGE_RATE_DETERMINATION`.
- Nodo de configuración `test_node`, sin efecto, conservado para que las configuraciones existentes sigan siendo válidas.

Cambios que pueden requerir atención:

- El alias de la extensión sigue siendo `Estratos_how_to`. Si prefieres el alias convencional `sie_banxico`, sobrescribe `SieBanxico::getContainerExtension()` en tu proyecto.
- Las clases ahora declaran `declare(strict_types=1)` y tipos de retorno (`mixed`) en los métodos de consulta.
- `tests/App/AppKernel.php` se eliminó: su única prueba se cubre con pruebas de la extensión y del contenedor.

## Licenciamiento

Este paquete implementa el paquete xint0/banxico-php, los derechos de autor de este software pertenecen a su autor Rogelio Jacinto. Copyright 2018-2021 Rogelio Jacinto. Todos
los derechos reservados.  https://github.com/Xint0/banxico-php

El autor de este paquete es software libre, algunos derechos reservados por Estratos Electronics SAS de CV. Este paquete se puede distribuir y/o modificarse bajo los términos de la [Licencia MIT].

[PSR-18]:https://www.php-fig.org/psr/psr-18/
[PSR-17]:https://www.php-fig.org/psr/psr-17/
[php-http.org]:https://php-http.org
[lista de clientes y adaptadores]:https://docs.php-http.org/en/latest/clients.html
[página de la API REST]:https://www.banxico.org.mx/SieAPIRest/service/v1/token
[Licencia MIT]:/LICENSE
