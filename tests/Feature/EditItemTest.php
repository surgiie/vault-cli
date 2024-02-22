<?php

// use App\Support\VaultItem;

// it('can edit items', function () {

//     drivers(function ($driver, $cipher, $algorithm) {
//         $action = "edit";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//        $this->partialMock($driver["class"], function ($mock) {
//             $item = new VaultItem("test", "default", sha1("test"), ["content"=>'foo']);
//             $mock->shouldReceive('create')->andReturn(true)
//                 ->shouldReceive('has')->andReturn(true)
//                 ->shouldReceive('get')->andReturn($item)
//                 ->shouldReceive('put')->andReturn(true);
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

//         $this->artisan('item:edit', [
//             'name' => 'test',
//             '--password'=>'foo',
//             '--content'=>'not-foo'
//         ])->expectsOutputToContain("Update vault item 'test': Succeeded")->assertExitCode(0);

//     });
// });


// it('cannot edit items that dont exist', function () {

//     drivers(function ($driver, $cipher, $algorithm) {
//         $action = "edit-non-existing";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//        $this->partialMock($driver["class"], function ($mock) {
//             $mock->shouldReceive('create')->andReturn(true)
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

//         $this->artisan('item:edit', [
//             'name' => 'test',
//             '--password'=>'foo',
//             '--content'=>'not-foo'
//         ])->expectsOutputToContain("Item with name 'test' does not exist in the vault.")->assertExitCode(1);

//     });
// });
