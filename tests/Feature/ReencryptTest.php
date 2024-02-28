<?php

use App\Support\Vault;
use App\Support\Config;
use App\Support\VaultItem;
use Illuminate\Support\Str;

it("can reencrypt items", function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $this->partialMock($driver["class"], function ($mock) use($cipher, $algorithm) {
            $items = [];
            foreach (range(1, 3) as $i) {
                $itemName = Str::random(10);
                $item = new VaultItem($itemName, "default", $hash = sha1($itemName), ["name"=>$itemName, "content"=>'foo']);
                $items[] = ["namespace"=>'default', "hash"=>$hash, "content"=>encrypt_test_item($item, $oldPassword = 'foo', $algorithm, $cipher)];
            }

            $mock
                ->shouldReceive('all')->andReturn($items);
        });

        $this->artisan('reencrypt', [
            '--old-password'=>'foo',
            '--password'=>'bar',
            '--force'=>true,
        ])->expectsOutputToContain("Reencryption complete. All items reencrypted successfully.")->assertExitCode(0);
    }, $this);
});

it("can reencrypt items with new options", function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $this->partialMock($driver["class"], function ($mock) use($cipher, $algorithm) {
            $items = [];
            foreach (range(0, 3) as $i) {
                $itemName = Str::random(10);
                $item = new VaultItem($itemName, "default", $hash = sha1($itemName), ["name"=>$itemName, "content"=>'foo']);
                $items[] = ["namespace"=>'default', "hash"=>$hash, "content"=>encrypt_test_item($item, $oldPassword = 'foo', $algorithm, $cipher)];
            }

            $mock
                ->shouldReceive('all')->andReturn($items);
        });


        $newCipher = str_contains($cipher, '256') ? str_replace("256", "128", $cipher) :  str_replace("128", "256", $cipher);
        $newAlgorithm = str_contains($algorithm, '256') ? str_replace("256", "512", $algorithm) :  str_replace("512", "256", $algorithm);

        // asser the current config matches current driver, cipher and algorithm
        expect((new Config)->getVaultConfig()->toArray())->toBe([
            "algorithm" => $algorithm,
            "cipher" => $cipher,
            "driver" => $driver['name'],
            "iterations" => Vault::DEFAULT_ITERATIONS[$algorithm],
            "name" => $driver['name']
        ]);

        $this->artisan('reencrypt', [
            '--old-password'=>'foo',
            '--password'=>'bar',
            '--cipher'=>$newCipher,
            '--algorithm'=>$newAlgorithm,
            '--force'=>true,
        ])->expectsOutputToContain("Reencryption complete. All items reencrypted successfully.")->assertExitCode(0);

        // assert new config matches updated options.
        expect((new Config)->getVaultConfig()->toArray())->toBe([
            "algorithm" => $newAlgorithm,
            "cipher" => $newCipher,
            "driver" => $driver['name'],
            "iterations" => Vault::DEFAULT_ITERATIONS[$newAlgorithm],
            "name" => $driver['name']
        ]);

    }, $this);
});




