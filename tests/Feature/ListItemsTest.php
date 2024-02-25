<?php

// use App\Support\Vault;
// use Illuminate\Encryption\Encrypter;

// drivers(function ($driver, $cipher, $algorithm) {
//     $label = $driver['name'].'-'.$cipher.'-'.$algorithm;

//     it("can list $label items", function () use ($driver, $cipher, $algorithm){

//         $this->artisan('use', [
//             'name' => $driver['name'],
//         ])->assertExitCode(0);

//         $password = 'foo';
//         $vaultItems = [
//             [
//                 'name' => $name = 'test',
//                 'namespace' => 'default',
//                 'hash' => $hash = sha1($name),
//                 'content' => (new Encrypter(
//                     key: compute_encryption_key($hash, $password, $algorithm, Vault::DEFAULT_ITERATIONS[$algorithm], Vault::SUPPORTED_CIPHERS[$cipher]['size']),
//                     cipher: $cipher
//                 ))->encrypt(json_encode(['name' => $name, 'content' => 'foo'], JSON_PRETTY_PRINT)),
//             ],
//         ];

//         $this->partialMock($driver['class'], function ($mock) use ($vaultItems) {
//             $mock->shouldReceive('all')->andReturn($vaultItems);
//         });

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
