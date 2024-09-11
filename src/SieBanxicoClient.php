<?php

namespace Estratos\SieBanxico;


use Xint0\BanxicoPHP\SieClient;


class SieBanxicoClient extends SieClient
{

    public const SERIES_EURO_EXCHANGE_RATE_DETERMINATION = 'SF46410';
    
    public function exchangeRateEuroDetermination(?string $start_date = null, ?string $end_date = null)
    {
        return $this->fetchSeries(self::SERIES_EURO_EXCHANGE_RATE_DETERMINATION, $start_date, $end_date);
    }



}