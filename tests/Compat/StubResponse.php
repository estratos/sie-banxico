<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\Compat;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Minimal PSR-7 response double backed by an in-memory stream.
 */
final class StubResponse implements ResponseInterface
{
    /** @var array<string, array<string>> */
    private array $headers;

    private ?StreamInterface $stream = null;

    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        private readonly int $statusCode = 200,
        private readonly string $body = '',
        array $headers = [],
        private readonly string $reasonPhrase = '',
        private readonly string $version = '1.1',
    ) {
        $this->headers = [];
        foreach ($headers as $name => $value) {
            $this->headers[$name] = \is_array($value) ? array_values($value) : [$value];
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return new self($this->statusCode, $this->body, $this->headers, $this->reasonPhrase, $version);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $clone = clone $this;
        $clone->headers[$name] = \is_array($value) ? array_values($value) : [(string) $value];

        return $clone;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $clone = clone $this;
        $clone->headers[$name] = array_merge(
            $this->headers[$name] ?? [],
            \is_array($value) ? array_values($value) : [(string) $value],
        );

        return $clone;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        $clone = clone $this;
        unset($clone->headers[$name]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        if (null === $this->stream) {
            $this->stream = new StubStream($this->body);
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $clone = clone $this;
        $clone->stream = $body;

        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return new self($code, $this->body, $this->headers, $reasonPhrase, $this->version);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}

/**
 * In-memory PSR-7 stream double.
 */
final class StubStream implements StreamInterface
{
    private int $position = 0;

    private bool $detached = false;

    public function __construct(private readonly string $contents = '')
    {
    }

    public function __toString(): string
    {
        return $this->contents;
    }

    public function close(): void
    {
        $this->position = 0;
    }

    public function detach()
    {
        $this->detached = true;

        return null;
    }

    /**
     * Size of the in-memory body, or null once the stream has been detached, as
     * allowed by PSR-7.
     */
    public function getSize(): ?int
    {
        return $this->detached ? null : \strlen($this->contents);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= \strlen($this->contents);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = \SEEK_SET): void
    {
        $this->position = match ($whence) {
            \SEEK_SET => $offset,
            \SEEK_CUR => $this->position + $offset,
            \SEEK_END => \strlen($this->contents) + $offset,
            default => throw new \RuntimeException('Unsupported seek whence.'),
        };
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('StubStream is read-only.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $chunk = substr($this->contents, $this->position, $length);
        $this->position += \strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        // Responses are parsed once per call, so the body must stay re-readable
        // instead of behaving like a consumed stream.
        $this->position = 0;

        return $this->contents;
    }

    public function getMetadata(?string $key = null)
    {
        $metadata = ['seekable' => true, 'eof' => $this->eof()];

        return null === $key ? $metadata : ($metadata[$key] ?? null);
    }
}

