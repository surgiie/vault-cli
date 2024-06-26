<?php

use App\Support\Config;
use App\Support\Vault;
use Symfony\Component\Yaml\Yaml;

it('can create vault', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $driverName = $driver['name'];
        $vaultName = "create-vault-$driverName-$cipher-$algorithm";

        $this->partialMock($driver['class'], function ($mock) {
            $mock->shouldReceive('create')->andReturn(true);
        });

        $this->artisan('new', [
            'name' => $vaultName,
            '--algorithm' => $algorithm,
            '--driver' => $driverName,
            '--cipher' => $cipher,
        ])->expectsOutputToContain("The $driverName vault '$vaultName' has been created.")->assertExitCode(0);

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
        $config = Yaml::parseFile(__DIR__.'/../.vault/config.yaml');

        unset($config['vaults'][$vaultName]);

        file_put_contents(__DIR__.'/../.vault/config.yaml', Yaml::dump($config, 4));
    });
});

it('cannot create existing vault', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $action = 'create-existing';
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
        ]);

        $this->artisan('new', [
            'name' => $vaultName,
            '--algorithm' => $algorithm,
            '--driver' => $driverName,
            '--cipher' => $cipher,
        ])->expectsOutputToContain("A vault with the name '$vaultName' is already configured in the ~/.vault/config.yaml file.")->assertExitCode(1);

        $config = Yaml::parseFile(__DIR__.'/../.vault/config.yaml');

        unset($config['vaults'][$vaultName]);

        file_put_contents(__DIR__.'/../.vault/config.yaml', Yaml::dump($config, 4));
    });

});
