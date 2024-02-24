<?php

use App\Support\Vault;
use App\Support\Config;
use Illuminate\Filesystem\Filesystem;


function drivers(callable $callback)
{
    foreach (load_drivers() as $driver) {
        $parts = explode('\\', $driver);
        $driver = [
            'class' => $driver,
            'name' => strtolower(end($parts)),
        ];
        // to test only single:
        // // Comment out/replace cipher if wanting to test a specific cipher
        // // $cipher = 'aes-128-cbc';
        // // $cipher = 'aes-128-gcm';
        // // $cipher = 'aes-256-gcm';
        // $cipher = 'aes-256-cbc';
        // // Comment out/replace algorithm if wanting to test a specific algorithm
        // // $algorithm = 'sha512';
        // $algorithm = 'sha256';

        // $callback($driver, $cipher, $algorithm);

        foreach(array_keys(Vault::SUPPORTED_CIPHERS) as $cipher) {
            foreach(Vault::HASH_ALGORITHMS as $algorithm) {
                $callback($driver, $cipher, $algorithm);
            }
        }
    }
}

uses(Tests\TestCase::class)->beforeEach(function () {
    Config::fake();
})->in(__DIR__);


// Create a vault config file and test vault for each driver/cipher/algorithm combination
@mkdir(__DIR__ . '/.vault');

file_put_contents(__DIR__ . '/.vault/config.yaml', "vaults: []");

drivers(function ($driver, $cipher, $algorithm) {

    dd("TODO");
});
