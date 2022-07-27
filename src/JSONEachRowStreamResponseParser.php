<?php

namespace Ulv\Phpch;

use ArrayIterator;
use Generator;

/**
 * @package Ulv\Phpch
 */
class JSONEachRowStreamResponseParser implements ResponseParserInterface
{
    protected array $rows = [];

    protected array $partialRows = [];

    public function add(string $dataLine): ResponseParserInterface
    {
        if ($dataLine) {
            if (strpos($dataLine, "\n") !== false) {
                $rows = explode("\n", $dataLine);

                foreach ($rows as $row) {
                    if ($decoded = json_decode($row, true, JSON_THROW_ON_ERROR)) {
                        $this->rows[] = $decoded;
                    } else {
                        $this->partialRows[] = $row;
                    }
                }
            } else {
                $this->partialRows[] = $dataLine;
            }
        }

        return $this;
    }

    public function row(): Generator
    {
        yield from $this->rows;
    }
}