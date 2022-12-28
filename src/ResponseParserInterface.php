<?php

namespace Ulv\Phpch;

use Generator;

/**
 * @package Ulv\Phpch
 */
interface ResponseParserInterface
{
    public function add(string $block) : self;

    public function row(): Generator;
}
