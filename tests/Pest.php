<?php

use App\Support\Vault;
use App\Support\Config;
use App\Support\VaultItem;
use Illuminate\Filesystem\Filesystem;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/



function drivers(callable $callback)
{
    foreach(load_drivers() as $driver) {
        $parts = explode('\\', $driver);
        $driver = [
            'class'=>$driver,
            'name'=> strtolower(end($parts)),
        ];
        foreach(array_keys(Vault::SUPPORTED_CIPHERS) as $cipher) {
            foreach(Vault::HASH_ALGORITHMS as $algorithm) {
                $callback($driver, $cipher, $algorithm);
            }
        }
    }
}

