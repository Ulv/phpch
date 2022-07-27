<?php

namespace Ulv\Phpch;

/**
 * Class Client
 * @package Ulv\Phpch
 */
class Client
{
    protected const READ_BYTES = 65535;

    private array $connection = [
        'scheme' => 'http',
        'host'   => 'localhost',
        'port'   => 8123,
    ];

    private array $connectionOptions = [
        'database'                      => 'default',
        //        'compress' => 1,
        //        'network_compression_method'    => 'ZSTD',
        'default_format'                => 'JSONEachRow',
        //                'enable_http_compression'       => 1,
        //        'max_result_rows'               => 10000,
        //        'max_result_bytes'              => 10000000,
        'buffer_size'                   => 4096,
        'wait_end_of_query'             => 1,
        'send_progress_in_http_headers' => 1,
        //        'output_format_enable_streaming' => 1,
        //        'result_overflow_mode'          => 'break',
    ];

    private string $user = '';

    private string $password = '';

    private $socket;

    private HttpMessage $httpMessage;

    private ResponseParserInterface $responseParser;

    private array $summary = [];

    private array $progress = [];

    private array $responseHeaders = [];

    public function __construct(array $parameters = [], ResponseParserInterface $responseParser = null)
    {
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                if (isset($this->connection[$key])) {
                    $this->connection[$key] = $value;
                } elseif (isset($this->connectionOptions[$key])) {
                    $this->connectionOptions[$key] = $value;
                }
            }

            $this->user     = $parameters['user'] ?? '';
            $this->password = $parameters['password'] ?? '';
        }

        $this->socket = fsockopen($this->connection['host'], $this->connection['port'], $errorCode, $errorMessage);

        if (!$this->socket) {
            throw new ClientException($errorMessage, $errorCode);
        }

        $this->responseParser = $responseParser ?? new JSONEachRowStreamResponseParser();
    }

    public function __destruct()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
    }

    public function query(string $query): self
    {
        $this->httpMessage = new HttpMessage(
            $this->connection['host'],
            $this->connection['port'],
            HttpMessage::POST,
            [
//                'Accept-Encoding: gzip, deflate, br',
'Accept-Language: en-GB,en-US,q=0.9,en,q=0.8',
'Connection: keep-alive',
'Content-Type: application/x-www-form-urlencoded',
'X-Clickhouse-User: ' . $this->user,
'X-Clickhouse-Key: ' . $this->password,
            ],
            http_build_query($this->connectionOptions),
            $query
        );

        return $this;
    }

    public function stream()
    {
        fwrite($this->socket, $this->httpMessage, $this->httpMessage->length());

        $processingBodyStarted = false;
        while (($line = stream_get_line($this->socket, self::READ_BYTES, "\r\n")) !== false) {
            if (empty($line)) {
                // split headers from data
                $processingBodyStarted = true;
                continue;
            } elseif (!$processingBodyStarted) {
                $this->responseHeaders[] = $line;

                if (strpos($line, 'X-ClickHouse-Exception') !== false) {
                    // todo: parse clickhouse exception
                    [$_, $exceptionCode] = explode(': ', $line, 2);
                    throw new ServerException($line, $exceptionCode);
                } elseif (strpos($line, 'X-ClickHouse-Summary') !== false) {
                    [$_, $summary] = explode(': ', $line, 2);
                    $this->summary = json_decode($summary, true, 2);
                } elseif (strpos($line, 'X-ClickHouse-Progress') !== false) {
                    [$_, $progress] = explode(': ', $line, 2);
                    $this->progress = json_decode($progress, true, 2);
                }
            }

            if ($processingBodyStarted) {
                // skip line with chunk size & get data
                $dataLine = stream_get_line($this->socket, self::READ_BYTES, "\r\n");
                $this->responseParser->add($dataLine);

                yield from $this->responseParser->row();
            }
        }
    }

    public function progress(): array
    {
        return $this->progress;
    }

    public function summary(): array
    {
        return $this->summary;
    }
}