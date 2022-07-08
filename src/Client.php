<?php

namespace Ulv\Phpch;

/**
 * Class Client
 * @package Ulv\Phpch
 */
class Client
{
    private array $connection = [
        'scheme' => 'http',
        'host'   => 'localhost',
        'port'   => 8123,
    ];

    private array $connectionOptions = [
        'database'                      => 'default',
        'default_format'                => 'JSONEachRow', // TSV, Native
        'enable_http_compression'       => 1,
        //        'max_result_rows'               => 10000,
        //        'max_result_bytes'              => 10000000,
        'buffer_size'                   => 65535,
        //        'wait_end_of_query'             => 1,
        'send_progress_in_http_headers' => 1,
        //        'result_overflow_mode'          => 'break',
    ];

    private string $user = '';

    private string $password = '';

    private $socket;

    private HttpMessage $httpMessage;

    public function __construct(array $parameters = [])
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

    public function cursor()
    {
        fwrite($this->socket, $this->httpMessage);

        while (($line = fgets($this->socket, 65535)) !== false) {
            yield $line;
        }
    }
}