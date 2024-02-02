<?php

use App\Support\Vault;
use App\Support\Config;
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

uses(Tests\TestCase::class)->beforeEach(function () {
    Config::fake();

    (new Filesystem)->deleteDirectory(Config::basePath());

    @mkdir(Config::basePath());

})->afterAll(function(){

    (new Filesystem)->deleteDirectory(Config::basePath());

})->in(__DIR__);


function drivers(callable $callback)
{
    foreach(load_drivers() as $driver) {
        $parts = explode('\\', $driver);
        $driver = [
            'class'=>$driver,
            'name'=> strtolower(end($parts)),
        ];
        // Comment out/replace cipher if wanting to test a specific cipher
        // $cipher = 'aes-128-cbc';
        // $cipher = 'aes-128-gcm';
        // $cipher = 'aes-256-gcm';
        $cipher = 'aes-256-cbc';
        // Comment out/replace algorithm if wanting to test a specific algorithm
        // $algorithm = 'sha512';
        $algorithm = 'sha256';

        $callback($driver, $cipher, $algorithm);
    }
}

