<?php

use App\Support\VaultItem;

// it('can edit items', function () {
//     drivers(function ($driver, $cipher, $algorithm) {
//         $action = "edit";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//        $this->partialMock($driver["class"], function ($mock) {
//             $item = new VaultItem("TEST", "default", sha1("TEST"), ["content"=>'foo']);
//             $mock->shouldReceive('create')->andReturn(true)
//                 ->shouldReceive('has')->andReturn(true)
//                 ->shouldReceive('put')->withArgs()->andReturn(true);
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

//         dd("TODO");
//     });
// });
