<?php

namespace Ulv\Phpch;

/**
 * @package Ulv\Phpch
 */
class ClickhouseQueryMessage
{
    public const POST = 'POST';

    private string $message = '';

    private ConfigurationInterface $configuration;

    private array $httpHeaders = [
        'Accept-Language: en-GB,en-US,q=0.9,en,q=0.8',
        'Connection: keep-alive',
        'Content-Type: application/x-www-form-urlencoded',
    ];

    public function __construct(string $sqlQuery, ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->build($sqlQuery);
    }

    public function __toString(): string
    {
        return $this->message;
    }

    public function length(): int
    {
        return strlen($this->message);
    }

    private function build(string $sqlQuery): void
    {
        $this->message = 'POST /' . $this->configuration->getServerConnectionQuery() . ' HTTP/1.1' . "\r\n";
        $this->message .= 'Host: ' . $this->configuration->get('host', 'localhost') . ':'
            . $this->configuration->get('port', 8123) . "\r\n";
        $this->message .= implode("\r\n", $this->httpHeaders);
        $this->message .= "\r\n";
        $this->message .= 'Content-Length: ' . strlen($sqlQuery) . "\r\n";
        $this->message .= "Connection: Close\r\n\r\n";
        $this->message .= $sqlQuery ?? '';
    }
}
