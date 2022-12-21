<?php

namespace Ulv\Phpch;

use Generator;

/**
 * @package Ulv\Phpch
 */
class JSONEachRowStreamResponseParser implements ResponseParserInterface
{
    private array $validRows = [];

    private string $incompleteRow = '';

    /**
     * @throws \JsonException
     */
    public function add(string $block): ResponseParserInterface
    {
        $rows         = explode("\n", $block);
        $lastRowIndex = array_key_last($rows);

        foreach ($rows as $i => $row) {
            if ($this->isValidJson($row)) {
                $this->validRows[] = json_decode($row, true, 512, JSON_THROW_ON_ERROR);
            } elseif ($i === 0 || $i === $lastRowIndex) {
                $this->incompleteRow .= $row;
                if ($this->isValidJson($this->incompleteRow)) {
                    $this->validRows[]   = json_decode($this->incompleteRow, true, 512, JSON_THROW_ON_ERROR);
                    $this->incompleteRow = '';
                }
            }
        }

        return $this;
    }

    public function row(): Generator
    {
        foreach ($this->validRows as $row) {
            yield $row;
        }
        $this->validRows = [];
    }

    private function isValidJson(string $string): bool
    {
        return json_decode($string) !== null && json_last_error() === JSON_ERROR_NONE;
    }
}
