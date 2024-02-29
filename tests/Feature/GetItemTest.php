<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;

it('can get item content', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $itemName = Str::random(10);

        $this->partialMock($driver['class'], function ($mock) use ($itemName, $cipher, $algorithm) {
            $item = new VaultItem($itemName, 'default', sha1($itemName), ['name' => $itemName, 'content' => 'foo']);
            $mock
                ->shouldReceive('has')->andReturn(true)
                ->shouldReceive('fetch')->andReturn(encrypt_test_item($item, 'foo', $algorithm, $cipher));
        });

        $this->artisan('item:get', [
            'name' => $itemName,
            '--password' => 'foo',
        ])->expectsOutputToContain('foo')->assertExitCode(0);
    }, $this);
});

it('can get item json', function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $this->artisan('use', [
            'name' => $driver['name'],
        ])->assertExitCode(0);

        $itemName = Str::random(10);
        $item = new VaultItem($itemName, 'default', sha1($itemName), ['name' => $itemName, 'content' => 'foo']);

        $this->partialMock($driver['class'], function ($mock) use ($cipher, $algorithm, $item) {
            $mock
                ->shouldReceive('has')->andReturn(true)
                ->shouldReceive('fetch')->andReturn(encrypt_test_item($item, 'foo', $algorithm, $cipher));
        });

        $this->artisan('item:get', [
            'name' => $itemName,
            '--json' => true,
            '--password' => 'foo',
        ])->expectsOutputToContain(json_encode($item->data(), JSON_PRETTY_PRINT))->assertExitCode(0);
    }, $this);
});

it('cannot get items that dont exist', function () {
    drivers(function ($driver) {
        $itemName = Str::random(10);
        $this->partialMock($driver['class'], function ($mock) {
            $mock
                ->shouldReceive('has')->andReturn(false);
        });

        $this->artisan('item:get', [
            'name' => $itemName,
            '--password' => 'foo',
        ])->expectsOutputToContain("Item with name '$itemName' does not exist in the vault.")->assertExitCode(1);
    });
});
