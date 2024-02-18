<?php

use App\Support\Config;
use App\Support\Vault;

it('can create items', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $action = 'create';
        $driverName = $driver['name'];

        $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

        $this->partialMock($driver['class'], function ($mock) {
            $mock->shouldReceive('create')->andReturn(true);
        });

        $this->artisan('new', [
            'name' => $vaultName,
            '--algorithm' => $algorithm,
            '--driver' => $driverName,
            '--cipher' => $cipher,
        ])->expectsOutput("The $driverName vault '$vaultName' has been created.")->assertExitCode(0);

        $this->artisan('use', [
            'name' => $vaultName,
        ])->assertExitCode(0);

        expect((new Config)->getVaultConfig()->toArray())->toBe([
            'algorithm' => $algorithm,
            'cipher' => $cipher,
            'driver' => $driverName,
            'iterations' => Vault::DEFAULT_ITERATIONS[$algorithm],
            'name' => $vaultName,
        ]);

    });
});
