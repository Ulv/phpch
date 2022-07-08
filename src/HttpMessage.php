<?php

namespace Ulv\Phpch;

/**
 * Class HttpMessage
 * @package Ulv\Phpch
 */
class HttpMessage
{
    public const POST = 'POST';

    private string $message;

    public function __construct(string $host, int $port, string $method = self::POST, array $headers = null, string $query = null, string $body = null)
    {
        $this->message = $method . ' /' . ($query ? '?' . $query : '') . ' HTTP/1.1' . "\r\n";
        $this->message .= 'Host: ' . $host . ':' . $port . "\r\n";

        if ($headers !== null) {
            $this->message .= implode("\r\n", $headers);
        }

        $this->message .= "\r\n";
        $this->message .= 'Content-Length: ' . strlen($body) . "\r\n";
        $this->message .= "Connection: Close\r\n\r\n";
        $this->message .= $body ?? '';
    }

    public function __toString(): string
    {
        return $this->message;
    }
}