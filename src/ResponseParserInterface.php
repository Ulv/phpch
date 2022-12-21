<?php

namespace Ulv\Phpch;

use Generator;

/**
 * Class ResponseParserInterface
 * @package Ulv\Phpch
 */
interface ResponseParserInterface
{
    public function add(string $block) : self;

    public function row(): Generator;
}