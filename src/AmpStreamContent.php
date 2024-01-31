<?php

namespace AmpGuzzle;

use Amp\ByteStream\ReadableStream;
use Amp\Http\Client\HttpContent;
use Psr\Http\Message\StreamInterface;

/**
 * Convert guzzle request stream into amp http client stream HttpContent
 */
class AmpStreamContent implements HttpContent
{

    private ReadableStream $readableStream;

    public function __construct(private StreamInterface $stream)
    {
        $this->readableStream = new AmpReadableStream($stream);
    }

    public function getContent(): ReadableStream
    {
        return $this->readableStream;
    }

    public function getContentLength(): ?int
    {
        return $this->stream->getSize();
    }

    public function getContentType(): ?string
    {
        return null;
    }

}
