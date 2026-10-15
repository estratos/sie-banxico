<?php

declare(strict_types=1);

namespace Estratos\SieBanxico;

use Estratos\SieBanxico\DependencyInjection\Configuration;
use Estratos\SieBanxico\DependencyInjection\SieBanxicoExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Xint0\BanxicoPHP\SieClient;

/**
 * Symfony bundle for the Banco de Mexico (Banxico) SIE REST API.
 *
 * The class keeps the constructor signature exposed by the published versions
 * (`new SieBanxico($token)`), so existing applications keep working without
 * changes. Because `Bundle` gained typed properties and dropped its constructor
 * in modern Symfony versions, this class does not call `parent::__construct()`.
 *
 * The class name is kept for backwards compatibility. It is not conventional:
 * Symfony derives the expected extension alias from the class short name
 * (`SieBanxico` -> `sie_banxico`) while the configuration root is
 * `estratos_sie_banxico` (see {@see Configuration::ROOT_NAME}), so
 * {@see self::getContainerExtension()} is overridden. Renaming the class would
 * break every application already using `new SieBanxico($token)`.
 */
class SieBanxico extends Bundle
{
    /**
     * SIE token. Optional so the bundle can be registered by the kernel
     * (`new SieBanxico()`) and configured through the bundle configuration.
     */
    private ?string $token;

    /**
     * Lazily created SIE client.
     */
    private ?SieBanxicoClient $client = null;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * Returns the bundle extension using the historical alias.
     *
     * Symfony's default implementation rejects any alias that does not match
     * the underscored bundle name; this override keeps the alias equal to
     * {@see Configuration::ROOT_NAME} so previously written configuration
     * blocks keep being recognised.
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SieBanxicoExtension();
        }

        return false === $this->extension ? null : $this->extension;
    }

    /**
     * Sets the SIE token after construction.
     *
     * Useful when the token is injected from the container instead of being
     * passed to the constructor.
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
        $this->client = null;
    }

    /**
     * Returns the configured SIE token, if any.
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Creates the SIE client.
     *
     * Kept for backwards compatibility; {@see self::client()} calls it
     * automatically on first use, so this method is no longer required before
     * calling the exchange rate helpers. Calling it more than once is safe: the
     * client is created only once.
     *
     * @throws \LogicException when no SIE token has been configured
     */
    public function ClientInit(): SieBanxicoClient
    {
        if (null === $this->client) {
            $this->client = new SieBanxicoClient($this->requireToken());
        }

        return $this->client;
    }

    /**
     * Returns the SIE client, initialising it on first use.
     *
     * @throws \LogicException when no SIE token has been configured
     */
    public function client(): SieBanxicoClient
    {
        return $this->client ?? $this->ClientInit();
    }

    /**
     * USD exchange rate for liquidation (series SF60653).
     *
     * @return SieClient::SERIES_USD_EXCHANGE_RATE_LIQUIDATION series data
     */
    public function getExchangeRate(): mixed
    {
        return $this->client()->exchangeRateUsdLiquidation();
    }

    /**
     * USD exchange rate as determined by Banxico (series SF43718).
     *
     * @return SieClient::SERIES_USD_EXCHANGE_RATE_DETERMINATION series data
     */
    public function getFixEchangeRate(): mixed
    {
        return $this->client()->exchangeRateUsdDetermination();
    }

    /**
     * Correctly spelled alias of {@see self::getFixEchangeRate()}.
     */
    public function getFixExchangeRate(): mixed
    {
        return $this->getFixEchangeRate();
    }

    /**
     * Euro exchange rate as determined by Banxico (series SF46410).
     */
    public function getEuroExchangeRate(): mixed
    {
        return $this->client()->exchangeRateEuroDetermination();
    }

    /**
     * @throws \LogicException when no SIE token has been configured
     */
    private function requireToken(): string
    {
        if (null === $this->token || '' === $this->token) {
            throw new \LogicException(
                'The Banxico SIE token is not configured. Pass it to the SieBanxico constructor, '
                . 'call SieBanxico::setToken(), or set the "token" option of the Estratos_how_to '
                . 'bundle configuration.',
            );
        }

        return $this->token;
    }
}
