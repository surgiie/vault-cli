<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;

it('can edit items', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $itemName = Str::random(10);

        $this->partialMock($driver['class'], function ($mock) use ($itemName, $cipher, $algorithm) {
            $item = new VaultItem($itemName, 'default', $hash = sha1($itemName), ['name' => $itemName, 'content' => 'foo']);

            $mock
                ->shouldReceive('has')->andReturn(true)
                ->shouldReceive('fetch')->andReturn(encrypt_test_item($item, 'foo', $algorithm, $cipher))
                ->shouldReceive('put')->withArgs([$hash, $item->data()])->andReturn(true);
        });

        $this->artisan('item:edit', [
            'name' => $itemName,
            '--password' => 'foo',
            '--content' => 'not-foo',
        ])->expectsOutputToContain("Update vault item '$itemName': Succeeded")->assertExitCode(0);

    }, $this);
});

it('cannot edit non existing items', function () {
    drivers(function ($driver) {

        $this->partialMock($driver['class'], function ($mock) {
            $mock
                ->shouldReceive('has')->andReturn(false);
        });

        $this->artisan('item:edit', [
            'name' => 'test',
            '--password' => 'foo',
            '--content' => 'not-foo',
        ])->expectsOutputToContain("Item with name 'test' does not exist in the vault.")->assertExitCode(1);

    }, $this);
});
