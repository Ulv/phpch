<?php

namespace Ulv\Phpch;

/**
 * @package Ulv\Phpch
 */
class Client
{
    protected const READ_BYTES = 65535;

    private $socket;

    private ClickhouseQueryMessage $queryMessage;

    private ResponseParserInterface $responseParser;

    private ConfigurationInterface $configuration;

    public function __construct(ConfigurationInterface $configuration, ResponseParserInterface $responseParser = null)
    {
        $this->configuration  = $configuration;
        $this->responseParser = $responseParser ?? new JSONEachRowStreamResponseParser();

        $this->socket = fsockopen(
            $this->configuration->get('host'),
            $this->configuration->get('port'),
            $errorCode,
            $errorMessage
        );

        if ($this->socket === false) {
            throw new ConnectionException($errorMessage, $errorCode);
        }
    }

    public function __destruct()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
    }

    public function query(string $sqlQuery): self
    {
        $this->queryMessage = new ClickhouseQueryMessage($sqlQuery, $this->configuration);

        return $this;
    }

    /**
     * @return \Generator
     * @throws \JsonException
     */
    public function stream(): ?\Generator
    {
        fwrite($this->socket, $this->queryMessage, $this->queryMessage->length());

        $processingBodyStarted = false;
        while (($line = stream_get_line($this->socket, self::READ_BYTES, "\r\n")) !== false) {
            if (empty($line)) {
                // headers delimiter
                $processingBodyStarted = true;
            } elseif (!$processingBodyStarted && strpos($line, 'X-ClickHouse-Exception') !== false) {
                // exception in headers
                throw new ClickhouseServerException($line);
            } elseif ($processingBodyStarted) {
                // parse data
                $block = stream_get_line($this->socket, self::READ_BYTES, "\r\n");
                yield from $this->responseParser->add($block)->row();
            }
        }
    }
}
