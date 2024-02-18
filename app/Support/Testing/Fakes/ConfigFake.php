<?php

namespace App\Support\Testing\Fakes;

use App\Support\Config;

class ConfigFake extends Config
{
    /** Create a new ConfigFake instance. */
    public function __construct()
    {
        // prevent parent constructor from running
    }

    /**
     * Generate a base path to the configuration file directory.
     *
     * @param  string  $path
     */
    public static function basePath($path = ''): string
    {
        return base_path("tests/.vault/$path");
    }

    /**
     * Get path to the configuration file.
     */
    public function path(): string
    {
        return static::basePath('config.yaml');
    }
}
