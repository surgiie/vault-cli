<?php

namespace Tests;

use PHPUnit\Runner\AfterTestHook;

class LongRunningTestAlert implements AfterTestHook
{
    protected const MAX_SECONDS_ALLOWED = 3;

    public function executeAfterTest(string $test, float $time): void
    {
        if ($time > self::MAX_SECONDS_ALLOWED) {
            fwrite(STDERR, sprintf("\nThe %s test took %s seconds!\n", $test, $time));
        }
    }
}
