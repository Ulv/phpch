<?php

namespace Ulv\Phpch;

/**
 * @package Ulv\Phpch
 */
interface ConfigurationInterface
{
    public function get(string $key, string $default = null);

    public function getServerConnectionQuery(): string;
}
