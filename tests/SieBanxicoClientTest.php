<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests;

use Estratos\SieBanxico\SieBanxicoClient;
use Estratos\SieBanxico\Tests\Compat\StubHttpClient;
use Estratos\SieBanxico\Tests\Compat\StubResponse;
use PHPUnit\Framework\TestCase;
use Xint0\BanxicoPHP\SieClient;

final class SieBanxicoClientTest extends TestCase
{
    private const SERIES_URL_PREFIX = 'https://www.banxico.org.mx/SieAPIRest/service/v1/series/';

    /**
     * @return array{0: StubHttpClient, 1: SieBanxicoClient}
     */
    private function createClient(string $json): array
    {
        $httpClient = new StubHttpClient(new StubResponse(200, $json));
        $client = new SieBanxicoClient('test-token', $httpClient);

        return [$httpClient, $client];
    }

    private static function singleSeriesJson(string $seriesId, string $date, string $value): string
    {
        return json_encode([
            'bmx' => [
                'series' => [
                    [
                        'idSerie' => $seriesId,
                        'titulo' => 'stub series',
                        'datos' => [
                            ['fecha' => $date, 'dato' => $value],
                        ],
                    ],
                ],
            ],
        ], \JSON_THROW_ON_ERROR);
    }

    public function testIsASieClient(): void
    {
        [$httpClient, $client] = $this->createClient(self::singleSeriesJson('SF46410', '01/01/2024', '18.5'));

        self::assertInstanceOf(SieClient::class, $client);
        self::assertSame(0, $httpClient->calls);
    }

    public function testEuroSeriesConstantIsUnchanged(): void
    {
        self::assertSame('SF46410', SieBanxicoClient::SERIES_EURO_EXCHANGE_RATE_DETERMINATION);
        self::assertSame('SF43718', SieClient::SERIES_USD_EXCHANGE_RATE_DETERMINATION);
        self::assertSame('SF60653', SieClient::SERIES_USD_EXCHANGE_RATE_LIQUIDATION);
    }

    public function testExchangeRateEuroDeterminationRequestsTheEuroSeries(): void
    {
        [$httpClient, $client] = $this->createClient(self::singleSeriesJson('SF46410', '01/01/2024', '18.5'));

        self::assertSame('18.5', $client->exchangeRateEuroDetermination());
        self::assertSame(1, $httpClient->calls);
        self::assertSame(
            self::SERIES_URL_PREFIX . 'SF46410/datos/oportuno',
            $httpClient->lastRequestUrl(),
        );
        self::assertSame('test-token', $httpClient->lastRequestToken());
    }

    public function testInheritedSeriesStillWorkThroughTheClient(): void
    {
        [$httpClient, $client] = $this->createClient(self::singleSeriesJson('SF60653', '01/01/2024', '17.1'));

        self::assertSame('17.1', $client->exchangeRateUsdLiquidation());
        self::assertStringStartsWith(self::SERIES_URL_PREFIX . 'SF60653', (string) $httpClient->lastRequestUrl());
    }

    public function testExchangeRateEuroDeterminationAcceptsADateRange(): void
    {
        [$httpClient, $client] = $this->createClient(self::singleSeriesJson('SF46410', '01/01/2024', '18.5'));

        $client->exchangeRateEuroDetermination('2024-01-01', '2024-01-31');

        $url = (string) $httpClient->lastRequestUrl();
        self::assertStringContainsString('SF46410/datos/2024-01-01/2024-01-31', $url);
    }

    public function testNonSuccessfulResponsesRaiseASieClientException(): void
    {
        $httpClient = new StubHttpClient(new StubResponse(401, '{"error": "unauthorized"}'));
        $client = new SieBanxicoClient('bad-token', $httpClient);

        $this->expectException(\Xint0\BanxicoPHP\SieClientException::class);

        $client->exchangeRateEuroDetermination();
    }

    public function testMalformedJsonRaisesASieClientException(): void
    {
        $httpClient = new StubHttpClient(new StubResponse(200, 'not-json'));
        $client = new SieBanxicoClient('test-token', $httpClient);

        $this->expectException(\Xint0\BanxicoPHP\SieClientException::class);

        $client->exchangeRateEuroDetermination();
    }

    public function testMultipleSeriesAreReturnedKeyedBySeriesId(): void
    {
        $json = json_encode([
            'bmx' => [
                'series' => [
                    [
                        'idSerie' => 'SF46410',
                        'datos' => [
                            ['fecha' => '01/01/2024', 'dato' => '18.5'],
                            ['fecha' => '02/01/2024', 'dato' => '18.6'],
                        ],
                    ],
                ],
            ],
        ], \JSON_THROW_ON_ERROR);

        [$httpClient, $client] = $this->createClient($json);

        self::assertSame(
            ['2024-01-01' => '18.5', '2024-01-02' => '18.6'],
            $client->exchangeRateEuroDetermination(),
        );
        self::assertSame(1, $httpClient->calls);
    }
}
