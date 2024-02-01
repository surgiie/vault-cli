<?php

use App\Support\VaultItem;

it('can list items', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $action = "list";
        $driverName = $driver['name'];
        dd("REWORK To encrypt/decrypt assertions");
        $vaultItems = [
            new VaultItem("TEST", "default", sha1("TEST"), ["content"=>'foo']),
            new VaultItem("TEST_TWO", "default", sha1("TEST_TWO"), ["content"=>'bar']),
        ];

        $vaultName = "$action-vault-$driverName-$cipher-$algorithm";

       $this->partialMock($driver["class"], function ($mock) use ($vaultItems){
            $mock->shouldReceive('create')->andReturn(true)
                ->shouldReceive('all')->andReturn($vaultItems);
        });

        $this->artisan('new', [
            'name' => $vaultName,
            '--algorithm' => $algorithm,
            '--driver' => $driverName,
            '--cipher' => $cipher,
        ])->assertExitCode(0);

        $this->artisan('use', [
            'name' => $vaultName,
        ])->assertExitCode(0);

        $rows = [];

        foreach($vaultItems as $item){
            $rows[] = [
                $item->name(),
                $item->namespace(),
                $item->hash(),
            ];
        }

        $this->artisan('item:list', [
            '--password' => "foo",
        ])->expectsTable([
            'Name',
            'Namespace',
            'Hash',
        ], $rows)
        ->assertExitCode(0);
    });
});
