<?php

namespace Estratos\SieBanxico;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SieBanxico extends Bundle
{
    private $token;
    private $client;

    public function __construct(string $token) {

     $this->token = $token;

    }


    public function ClientInit()
    {

        $this->client = new SieBanxicoClient($this->token);
    }

    public function getExchangeRate()
    {

        $exchange_rate = $this->client->exchangeRateUsdLiquidation();

        return $exchange_rate;
    }

    public function getFixEchangeRate()
    {

        $exchange_rate = $this->client->exchangeRateUsdDetermination();

        return $exchange_rate;
    }

    public function getEuroExchangeRate()
    {

        $exchange_rate = $this->client->exchangeRateEuroDetermination();

        return $exchange_rate;
    }
}
