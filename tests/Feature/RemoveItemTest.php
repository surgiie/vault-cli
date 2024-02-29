<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;

it("can remove item", function () {
    drivers(function ($driver, $cipher, $algorithm) {
        $itemName = Str::random(10);

        $this->partialMock($driver["class"], function ($mock) use($itemName, $cipher, $algorithm) {
            $item = new VaultItem($itemName, "default", sha1($itemName), ["name"=>$itemName, "content"=>'foo']);
            $mock
                ->shouldReceive('has')->andReturn(true)
                ->shouldReceive('fetch')->andReturn(encrypt_test_item($item, 'foo', $algorithm, $cipher))
                ->shouldReceive('remove')->andReturn(true);
        });

        $this->artisan('item:remove', [
            '--name' => [$itemName],
            '--password'=>'foo',
        ])->expectsOutputToContain("Remove vault item '$itemName'")->assertExitCode(0);
    }, $this);
});



it("cannot remove items that dont exist", function () {
    drivers(function ($driver) {
        $itemName = Str::random(10);

        $this->partialMock($driver["class"], function ($mock){
            $mock
                ->shouldReceive('has')->andReturn(false);
        });

        $this->artisan('item:remove', [
            '--name' => [$itemName],
            '--password'=>'foo',
        ])->expectsOutputToContain("The vault does not contain an item called '$itemName', skipped.")->assertExitCode(0);
    }, $this);
});



