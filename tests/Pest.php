<?php

use App\Support\Vault;
use App\Support\Config;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Encryption\Encrypter;

uses(Tests\TestCase::class)->beforeEach(function () {
    Config::fake();
})->in(__DIR__);

function drivers(callable $callback, $test = null)
{
    global $argv;

    foreach (Vault::loadDrivers() as $driver) {
        $parts = explode('\\', $driver);
        $driver = [
            'class' => $driver,
            'name' => strtolower(end($parts)),
        ];


        $ciphers = ["aes-256-cbc"];
        $algorithms = ["sha256"];

        // comment out if you want to test all ciphers and algorithms
        // $ciphers = array_keys(Vault::SUPPORTED_CIPHERS);
        // $algorithms = Vault::HASH_ALGORITHMS;

        foreach($ciphers as $cipher) {
            foreach($algorithms as $algorithm) {

                if(!is_null($test)){

                    $test->artisan('use', [
                        'name' => $driver['name'],
                    ])->assertExitCode(0);

                    (new Config)->saveVaultConfig([
                        'algorithm' => $algorithm,
                        'cipher' => $cipher,
                        'driver' => $driver['name'],
                        'iterations' => Vault::DEFAULT_ITERATIONS[$algorithm],
                        'name' => $driver['name'],
                    ]);
                }


                $callback($driver, $cipher, $algorithm);
            }
        }
    }
}

function encrypt_test_item($item, $password, $algorithm, $cipher){
    return (new Encrypter(
        key: compute_encryption_key($item->hash(), $password, $algorithm, Vault::DEFAULT_ITERATIONS[$algorithm], Vault::SUPPORTED_CIPHERS[$cipher]['size']),
        cipher: $cipher
    ))->encrypt(json_encode($item->data()));
}


// Create a vault config file and test vault for each driver/cipher/algorithm combination
@mkdir(__DIR__ . '/.vault');

file_put_contents(__DIR__ . '/.vault/config.yaml', "vaults: {}");

drivers(function ($driver, $cipher, $algorithm) {

    $yaml = Yaml::parseFile(__DIR__ . '/.vault/config.yaml');

    $yaml['vaults'][$driver['name']] = [
        'driver' => $driver['name'],
        'cipher' => $cipher,
        'algorithm' => $algorithm,
        'iterations' => Vault::DEFAULT_ITERATIONS[$algorithm],
    ];

    file_put_contents(__DIR__ . '/.vault/config.yaml', Yaml::dump($yaml, 4));
});
