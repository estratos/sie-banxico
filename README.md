# sie-banxico

Cliente Symfony 7.* para la API REST del Sistema de Información Económica (SIE) del Banco de México (Banxico).

## Instalación

### Requisitos

- PHP `8.1` o más reciente.
- Cliente HTTP conforme a [PSR-18], cualquiera de la [lista de clientes y adaptadores] de [php-http.org].

### Install Using C0mposer / Utilizar Composer

Instalar con el adaptador para Guzzle 7 (instalado por default) por ejemplo:

```bash
composer require estratos/sie-banxico
```

Si ya se tiene definido un cliente [PSR-18] en el proyecto, se puede instalar solo el cliente:

On your Symfony proyect root run:
```bash
composer require estratos/sie-banxico
```

## Usage / Cómo usar

### Query Token / Token de consulta

Se debe obtener un _token_ de consulta a través de la [página de la API REST] del SIE del Banxico.

### Controller usage in Symfony / Uso en Controller en Symfony

```php
<?php

namespace App\Controller;

use App\Service\RequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Estratos\SieBanxico\SieBanxico;

class BanxicoController extends AbstractController
{


///// helper route to return exchange rate
#[Route('/banxico/tipo', name: 'app_banxico_api_tipo')]
public function getBanxicoRate(): Response
{
    $token = '4271d3f522930eb7ff3d316c4896d0ad5287298ad54363c37107e78cc0ee4c75';
    $res = $this->getExchange($token);


return $this->json($res);
}


public function  getExchange($token) {

  $service = new SieBanxico($token);

  $service->ClientInit();

  $tipo_de_cambio = $service->getExchangeRate();

  return $tipo_de_cambio;
}


}
```

## Licenciamiento

Este paquete implementa el paquete xint0/banxico-php, los derechos de autor de este software pertenecen a su autor Rogelio Jacinto. Copyright 2018-2021 Rogelio Jacinto. Todos
los derechos reservados.  https://github.com/Xint0/banxico-php

El autor de este paquete es software libre, algunos derechos reservados por Estratos Electronics SAS de CV. Este paquete se puede distribuir y/o modificarse bajo los términos de la [Licencia MIT].

[PSR-18]:https://www.php-fig.org/psr/psr-18/
[php-http.org]:https://php-http.org
[lista de clientes y adaptadores]:https://docs.php-http.org/en/latest/clients.html
[página de la API REST]:https://www.banxico.org.mx/SieAPIRest/service/v1/token
[Licencia MIT]:/LICENSE
