<?php

declare(strict_types=1);

namespace Estratos\SieBanxico;

use Psr\Http\Client\ClientInterface;
use Xint0\BanxicoPHP\SieClient;

/**
 * SIE client with the extra Banxico series this bundle exposes.
 *
 * The class name, the inherited constructor signature and the
 * `exchangeRateEuroDetermination()` method are part of the public API released in
 * 0.1.0 and 0.1.1 and must keep working as-is.
 */
class SieBanxicoClient extends SieClient
{
    /**
     * Euro exchange rate as determined by Banxico (pesos per euro).
     */
    public const SERIES_EURO_EXCHANGE_RATE_DETERMINATION = 'SF46410';

    /**
     * @param string               $token      Banxico SIE API token
     * @param ClientInterface|null $httpClient PSR-18 client, autodiscovered when omitted
     * @param array<string, mixed> $params     Extra options; `base_uri` is merged with the default
     */
    public function __construct(string $token, ?ClientInterface $httpClient = null, array $params = [])
    {
        parent::__construct($token, $httpClient, $params);
    }

    /**
     * Euro exchange rate as determined by Banxico.
     *
     * @param string|null $start_date Start date (any format accepted by `strtotime`), or null for the latest value
     * @param string|null $end_date   End date, or null to use the start date
     *
     * @return mixed A single value or a map of `YYYY-MM-DD` => value. See {@see SieClient::fetchSeries()}.
     */
    public function exchangeRateEuroDetermination(?string $start_date = null, ?string $end_date = null): mixed
    {
        return $this->fetchSeries(self::SERIES_EURO_EXCHANGE_RATE_DETERMINATION, $start_date, $end_date);
    }
}
