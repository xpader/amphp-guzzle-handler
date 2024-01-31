<?php

namespace AmpGuzzle;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\ReadableStreamIteratorAggregate;
use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Psr\Http\Message\StreamInterface;

/**
 * Convert guzzle request stream into amp ReadableStream
 */
class AmpReadableStream implements ReadableStream, \IteratorAggregate {

    use ReadableStreamIteratorAggregate;
    use ForbidCloning;
    use ForbidSerialization;

    private $closed = false;
    private \Closure $onCloseCallback;

    public function __construct(private StreamInterface $stream)
    {
    }

    public function read(?Cancellation $cancellation = null): ?string
    {
        $content = $this->stream->read(1024);
        return $content !== '' ? $content : null;
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function close(): void
    {
        $this->stream->close();
        $this->closed = true;
        if ($this->onCloseCallback !== null) {
            call_user_func($this->onCloseCallback);
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function onClose(\Closure $onClose): void
    {
        $this->onCloseCallback = $onClose;
    }

}
