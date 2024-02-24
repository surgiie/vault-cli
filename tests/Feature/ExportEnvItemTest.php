<?php

// use App\Support\Testing\Fakes\ConfigFake;
// use App\Support\VaultItem;

// drivers(function ($driver, $cipher, $algorithm) {
//     it('can export item content to .env file', function () use ($driver, $cipher, $algorithm) {

//         $action = "export-env";
//         $driverName = $driver['name'];

//         $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

//         $this->partialMock($driver["class"], function ($mock) {
//             $item = new VaultItem("test", "default", $hash = sha1("test"), ["content"=>'foo']);
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
//         $env = ConfigFake::basePath('test.env');

//         $this->artisan('export:env-file', [
//             '--password'=>'foo',
//             "--env-file"=> $env,
//             "--export"=> ['test']
//         ])->expectsOutputToContain("Exported vault items to: tests/.vault/test.env")->assertExitCode(0);

//         expect(file_exists($env))->toBeTrue();
//         expect(file_get_contents($env))->toBe("TEST=foo\n");
//     });
// });
