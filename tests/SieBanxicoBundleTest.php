<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests;

use Estratos\SieBanxico\SieBanxico;
use Estratos\SieBanxico\SieBanxicoClient;
use Estratos\SieBanxico\Tests\Compat\StubHttpClient;
use Estratos\SieBanxico\Tests\Compat\StubResponse;
use PHPUnit\Framework\TestCase;

/**
 * Guards the public API of the published releases (0.1.0 and 0.1.1) of the
 * bundle class: users instantiate it with
 * a token, optionally call ClientInit() and then call the exchange rate
 * helpers.
 */
final class SieBanxicoBundleTest extends TestCase
{
    private const SERIES_URL_PREFIX = 'https://www.banxico.org.mx/SieAPIRest/service/v1/series/';

    private static function singleSeriesJson(string $seriesId, string $value): string
    {
        return json_encode([
            'bmx' => [
                'series' => [
                    [
                        'idSerie' => $seriesId,
                        'datos' => [
                            ['fecha' => '01/01/2024', 'dato' => $value],
                        ],
                    ],
                ],
            ],
        ], \JSON_THROW_ON_ERROR);
    }

    private static function injectClient(SieBanxico $bundle, SieBanxicoClient $client): void
    {
        $property = new \ReflectionProperty(SieBanxico::class, 'client');
        $property->setValue($bundle, $client);
    }

    public function testLegacyBootstrapFlowStillWorks(): void
    {
        $bundle = new SieBanxico('test-token');
        $httpClient = new StubHttpClient(new StubResponse(200, self::singleSeriesJson('SF60653', '17.1')));
        $client = new SieBanxicoClient('test-token', $httpClient);
        self::injectClient($bundle, $client);

        self::assertSame($client, $bundle->ClientInit());
        self::assertSame('17.1', $bundle->getExchangeRate());
        self::assertSame(1, $httpClient->calls);
    }

    public function testClientInitIsIdempotent(): void
    {
        $bundle = new SieBanxico('test-token');

        self::assertSame($bundle->ClientInit(), $bundle->ClientInit());
    }

    public function testExchangeRateHelpersUseTheExpectedSeries(): void
    {
        $bundle = new SieBanxico('test-token');
        $httpClient = new StubHttpClient(new StubResponse(200, self::singleSeriesJson('SF60653', '17.1')));
        self::injectClient($bundle, new SieBanxicoClient('test-token', $httpClient));

        self::assertSame('17.1', $bundle->getExchangeRate());
        self::assertSame(self::SERIES_URL_PREFIX . 'SF60653/datos/oportuno', $httpClient->lastRequestUrl());
    }

    public function testFixExchangeRateKeepsTheHistoricallyMisspelledName(): void
    {
        $bundle = new SieBanxico('test-token');
        $httpClient = new StubHttpClient(new StubResponse(200, self::singleSeriesJson('SF43718', '18.0')));
        self::injectClient($bundle, new SieBanxicoClient('test-token', $httpClient));

        self::assertSame('18.0', $bundle->getFixEchangeRate());
        self::assertSame(self::SERIES_URL_PREFIX . 'SF43718/datos/oportuno', $httpClient->lastRequestUrl());
    }

    public function testCorrectlySpelledAliasReturnsTheSameSeries(): void
    {
        $bundle = new SieBanxico('test-token');
        $httpClient = new StubHttpClient(new StubResponse(200, self::singleSeriesJson('SF43718', '18.0')));
        self::injectClient($bundle, new SieBanxicoClient('test-token', $httpClient));

        self::assertSame($bundle->getFixEchangeRate(), $bundle->getFixExchangeRate());
    }

    public function testEuroExchangeRateUsesTheEuroSeries(): void
    {
        $bundle = new SieBanxico('test-token');
        $httpClient = new StubHttpClient(new StubResponse(200, self::singleSeriesJson('SF46410', '18.5')));
        self::injectClient($bundle, new SieBanxicoClient('test-token', $httpClient));

        self::assertSame('18.5', $bundle->getEuroExchangeRate());
        self::assertSame(self::SERIES_URL_PREFIX . 'SF46410/datos/oportuno', $httpClient->lastRequestUrl());
    }

    public function testTokenCanBeSetAfterConstruction(): void
    {
        $bundle = new SieBanxico();
        $bundle->setToken('late-token');

        self::assertSame('late-token', $bundle->getToken());
        self::assertInstanceOf(SieBanxicoClient::class, $bundle->ClientInit());
    }

    public function testSetTokenResetsAPreviouslyInitialisedClient(): void
    {
        $bundle = new SieBanxico('first-token');
        $httpClient = new StubHttpClient(new StubResponse(200, self::singleSeriesJson('SF46410', '18.5')));
        self::injectClient($bundle, new SieBanxicoClient('first-token', $httpClient));

        $bundle->setToken('second-token');

        $client = $bundle->ClientInit();
        self::assertNotSame($httpClient, $client);
    }

    public function testCallingAnExchangeRateHelperWithoutATokenFailsLoudly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('token');

        (new SieBanxico())->getExchangeRate();
    }

    public function testAnEmptyTokenIsRejectedAsWell(): void
    {
        $this->expectException(\LogicException::class);

        (new SieBanxico(''))->ClientInit();
    }
}
