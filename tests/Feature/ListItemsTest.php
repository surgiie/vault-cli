<?php

use App\Support\Vault;
use Illuminate\Encryption\Encrypter;

// drivers(function ($driver, $cipher, $algorithm) {

//     it('can list items', function () use ($driver, $cipher, $algorithm){
//         $action = 'list';
//         $driverName = $driver['name'];
//         $password = 'foo';

//         $vaultItems = [
//             [
//                 'name' => $name = 'TEST_ONE',
//                 'namespace' => 'default',
//                 'hash' => $hash = sha1($name),
//                 'content' => (new Encrypter(
//                     key: compute_encryption_key($hash, $password, $algorithm, Vault::DEFAULT_ITERATIONS[$algorithm], Vault::SUPPORTED_CIPHERS[$cipher]['size']),
//                     cipher: $cipher
//                 ))->encrypt(json_encode(['name' => $name, 'content' => 'foo'])),
//             ],
//         ];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//         $this->partialMock($driver['class'], function ($mock) use ($vaultItems) {
//             $mock->shouldReceive('create')->andReturn(true)
//                 ->shouldReceive('all')->andReturn($vaultItems);
//         });

//         $this->artisan('new', [
//             'name' => $vaultName,
//             '--algorithm' => $algorithm,
//             '--driver' => $driverName,
//             '--cipher' => $cipher,
//         ])->assertExitCode(0);

//         $this->artisan('use', [
//             'name' => $vaultName,
//         ])->assertExitCode(0);

//         $rows = [];

//         foreach ($vaultItems as $item) {
//             $rows[] = [
//                 $item['name'],
//                 $item['namespace'],
//                 $item['hash'],
//             ];
//         }

//         $this->artisan('item:list', [
//             '--password' => $password,
//         ])->expectsTable([
//             'Name',
//             'Namespace',
//             'Hash',
//         ], $rows)
//             ->assertExitCode(0);
//     });
// });
