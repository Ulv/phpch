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
        'default_format'                => 'JSONCompact', // TSV, Native
        'enable_http_compression'       => 1,
        'max_result_rows'               => 10000,
        'max_result_bytes'              => 10000000,
        'buffer_size'                   => 10,
        'wait_end_of_query'             => 1,
        'send_progress_in_http_headers' => 1,
        'result_overflow_mode'          => 'break',
    ];

    private string $user = '';

    private string $password = '';

    private string $query;

    private $socket;

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
        $this->query = $query;

        $request = 'POST /?' . http_build_query($this->connectionOptions) . ' HTTP/1.1' . "\r\n";
        $request .= 'Host: ' . $this->connection['host'] . ':' . $this->connection['port'] . "\r\n";
//        $request .= 'Accept-Encoding: gzip, deflate, br' . "\r\n";
        $request .= 'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8' . "\r\n";
        $request .= 'Connection: keep-alive' . "\r\n";
        $request .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
        $request .= 'X-Clickhouse-User: ' . $this->user . "\r\n";
        $request .= 'X-Clickhouse-Key: ' . $this->password . "\r\n";
        $request .= 'Content-Length: ' . strlen($this->query) . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $request .= $this->query;

        fwrite($this->socket, $request);

        return $this;
    }

    public function cursor()
    {
        while (($line = stream_get_line($this->socket, 4096)) !== false) {
            yield $line;
        }
    }
}