<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\Compat;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client double that can be registered as a container service, used to
 * verify that the bundle wires the configured HTTP client into the SIE client.
 */
final class StubContainerHttpClient implements ClientInterface
{
    private ?ResponseInterface $response = null;

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (null === $this->response) {
            throw new \RuntimeException('No response configured.');
        }

        return $this->response;
    }
}
