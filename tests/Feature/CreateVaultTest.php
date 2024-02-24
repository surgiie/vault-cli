<?php

// use App\Support\Config;
// use App\Support\Vault;

// drivers(function ($driver, $cipher, $algorithm) {
//     it('can create items', function () use($driver, $cipher, $algorithm) {
//         $action = 'create';
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//         $this->partialMock($driver['class'], function ($mock) {
//             $mock->shouldReceive('create')->andReturn(true);
//         });

//         $this->artisan('new', [
//             'name' => $vaultName,
//             '--algorithm' => $algorithm,
//             '--driver' => $driverName,
//             '--cipher' => $cipher,
//         ])->expectsOutput("The $driverName vault '$vaultName' has been created.")->assertExitCode(0);

//         $this->artisan('use', [
//             'name' => $vaultName,
//         ])->assertExitCode(0);

//         expect((new Config)->getVaultConfig()->toArray())->toBe([
//             'algorithm' => $algorithm,
//             'cipher' => $cipher,
//             'driver' => $driverName,
//             'iterations' => Vault::DEFAULT_ITERATIONS[$algorithm],
//             'name' => $vaultName,
//         ]);
//     });

//     it('can create vault that already exist', function () use ($driver, $cipher, $algorithm) {
//         $action = 'create-existing';
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//         $this->partialMock($driver['class'], function ($mock) {
//             $mock->shouldReceive('create')->andReturn(true);
//         });

//         $this->artisan('new', [
//             'name' => $vaultName,
//             '--algorithm' => $algorithm,
//             '--driver' => $driverName,
//             '--cipher' => $cipher,
//         ]);

//         $this->artisan('new', [
//             'name' => $vaultName,
//             '--algorithm' => $algorithm,
//             '--driver' => $driverName,
//             '--cipher' => $cipher,
//         ])->expectsOutputToContain("A vault with the name '$vaultName' is already configured in the ~/.vault/config.yaml file.")->assertExitCode(1);
//     });

// });

