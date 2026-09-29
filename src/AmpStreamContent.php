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

    public function __construct(private StreamInterface $stream)
    {
    }

    public function getContent(): ReadableStream
    {
        // Rewind the stream so retries (amphp RetryRequests reuses the same
        // HttpContent instance) can replay the full body, otherwise the retry
        // would send fewer bytes than declared in Content-Length.
        if ($this->stream->isSeekable()) {
            $this->stream->seek(0);
        }

        return new AmpReadableStream($this->stream);
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
