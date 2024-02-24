<?php

use App\Support\VaultItem;

// drivers(function ($driver, $cipher, $algorithm) {

//     it('can get item content', function () use ($driver, $cipher, $algorithm) {
//         $action = "get";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//        $this->partialMock($driver["class"], function ($mock) {
//             $item = new VaultItem("test", "default", sha1("test"), ["content"=>'foo']);
//             $mock
//                 ->shouldReceive('has')->andReturn(true)
//                 ->shouldReceive('get')->andReturn($item);
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

//         $this->artisan('item:get', [
//             'name' => 'test',
//             '--password'=>'foo',
//         ])->expectsOutputToContain("foo")->assertExitCode(0);
//     });

//     it('can get item json', function () use ($driver, $cipher, $algorithm) {
//         $action = "get";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//         $item = new VaultItem("test", "default", sha1("test"), ["content"=>'foo']);

//         $this->partialMock($driver["class"], function ($mock) use($item) {
//             $mock
//                 ->shouldReceive('has')->andReturn(true)
//                 ->shouldReceive('get')->andReturn($item);
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

//         $this->artisan('item:get', [
//             'name' => 'test',
//             '--json'=> true,
//             '--password'=>'foo',
//         ])->expectsOutputToContain(json_encode($item->data(), JSON_PRETTY_PRINT))->assertExitCode(0);
//     });

//     it('cannot get items that dont exist', function () use ($driver, $cipher, $algorithm) {

//         $action = "get-non-existing";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//         $this->partialMock($driver["class"], function ($mock) {
//             $mock
//                 ->shouldReceive('has')->andReturn(false);
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

//         $this->artisan('item:get', [
//             'name' => 'test',
//             '--password'=>'foo',
//         ])->expectsOutputToContain("Item with name 'test' does not exist in the vault.")->assertExitCode(1);

//     });

// });

