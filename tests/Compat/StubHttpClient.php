<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\Compat;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal PSR-18 client double.
 *
 * Hand-written on purpose: it keeps the test suite free of an extra HTTP
 * mocking dependency while still capturing the outgoing request so tests can
 * assert which SIE series was requested.
 */
final class StubHttpClient implements ClientInterface
{
    public int $calls = 0;

    public ?RequestInterface $lastRequest = null;

    public function __construct(private readonly ?ResponseInterface $response = null)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        ++$this->calls;
        $this->lastRequest = $request;

        if (null === $this->response) {
            throw new \RuntimeException('StubHttpClient has no response configured.');
        }

        return $this->response;
    }

    /**
     * Full URL of the last requested endpoint, including the query string.
     */
    public function lastRequestUrl(): ?string
    {
        if (null === $this->lastRequest) {
            return null;
        }

        $uri = $this->lastRequest->getUri();

        return (string) $uri;
    }

    /**
     * Value of the `Bmx-Token` header sent with the last request.
     */
    public function lastRequestToken(): ?string
    {
        if (null === $this->lastRequest) {
            return null;
        }

        return $this->lastRequest->getHeaderLine('Bmx-Token');
    }
}
