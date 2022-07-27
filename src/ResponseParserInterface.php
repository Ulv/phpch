<?php

namespace Ulv\Phpch;

use Generator;

/**
 * Class ResponseParserInterface
 * @package Ulv\Phpch
 */
interface ResponseParserInterface
{
    public function add(string $dataLine) : self;

    public function row(): Generator;
}