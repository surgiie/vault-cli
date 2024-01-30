<?php

use Mockery as m;
use App\Support\Config;
use App\Support\VaultItem;
use Illuminate\Filesystem\Filesystem;

uses(Tests\TestCase::class)->beforeEach(function () {
    Config::fake();
    (new Filesystem)->deleteDirectory(Config::basePath());

    @mkdir(Config::basePath());
})->in(__DIR__);

it('can list items', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $driverName = $driver['name'];

        # create a new vault for this driver and cipher
        $vaultName = "list-vault-$driverName-$cipher-$algorithm";

        $mock = $this->partialMock($driver["class"], function ($mock){
            $mock->shouldReceive('create')->andReturn(true);
        });


        $this->artisan('new', [
            'name' => $vaultName,
            '--algorithm' => $algorithm,
            '--driver' => $driverName,
            '--cipher' => $cipher,
        ])->assertExitCode(0);

        // set as default vault
        $this->artisan('select', [
            'name' => $vaultName,
        ])->assertExitCode(0);

        // the items in the vault.
        $items = [
            new VaultItem("test", "default", sha1("test"), ["content"=>'foo']),
        ];


        $this->artisan('item:list', [
            '--password' => "foo",
        ])->assertExitCode(0);
    });
});
