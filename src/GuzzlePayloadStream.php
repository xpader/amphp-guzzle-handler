<?php

namespace AmpGuzzle;

use Amp\ByteStream\Payload;
use Psr\Http\Message\StreamInterface;

/**
 * Convert amp http client response payload to guzzle stream
 */
class GuzzlePayloadStream implements StreamInterface
{

    private ?int $size = null;
    private string $buffer = '';
    private bool $isEof = false;

    public function __construct(private Payload $payload, $length=null)
    {
        if ($length) {
            $this->size = (int)$length;
        }
    }

    public function __toString(): string
    {
        return $this->payload->buffer();
    }

    public function close(): void
    {
        $this->payload->close();
    }

    public function detach() { }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return $this->isEof;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void { }

    public function rewind(): void { }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        return 0;
    }

    public function isReadable(): bool
    {
        return $this->payload->isReadable();
    }

    public function read(int $length): string
    {
        if ($this->buffer !== '' && strlen($this->buffer) >= $length) {
            $buffer = substr($this->buffer, 0, $length);
            $this->buffer = substr($this->buffer, $length);
            return $buffer;
        }

        if ($this->eof()) {
            return '';
        }

        $buffer = $this->payload->read();

        if ($buffer === null) {
            $this->isEof = true;
            return $this->buffer;
        }

        $content = $this->buffer.$buffer;
        $len = strlen($content);

        if ($len > $length) {
            $this->buffer = substr($buffer, $length);
            return substr($buffer, 0, $length);
        } elseif ($len < $length) {
            $this->buffer = '';
            return $content.$this->read($length - $len);
        } else {
            $this->buffer = '';
            return $content;
        }
    }

    public function getContents(): string
    {
        return $this->payload->buffer();
    }

    public function getMetadata(?string $key = null)
    {}

}