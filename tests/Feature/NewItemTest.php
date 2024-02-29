<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;

it("can create new items", function () {
    drivers(function ($driver) {
        $itemName = Str::random(10);

       $this->partialMock($driver["class"], function ($mock) use($itemName) {
            $item = new VaultItem($itemName, "default", $hash = sha1($itemName), ["name"=>$itemName, "content"=>'foo']);

            $mock
                ->shouldReceive('has')->andReturn(false)
                ->shouldReceive('put')->withArgs([$hash, $item->data()])->andReturn(true);
        });

        $this->artisan('item:new', [
            'name' => $itemName,
            '--password'=>'foo',
            '--content'=>'not-foo'
        ])->expectsOutputToContain("Create new vault item '$itemName': Succeeded")->assertExitCode(0);

    }, $this);
});



it("cannot create existing items", function (){
    drivers(function ($driver) {
        $itemName = Str::random(10);

       $this->partialMock($driver["class"], function ($mock) use ($itemName) {
            $item = new VaultItem($itemName, "default", $hash = sha1($itemName), ["name"=>$itemName, "content"=>'foo']);
            $mock
                ->shouldReceive('has')->andReturn(true)
                ->shouldReceive('put')->withArgs([$hash, $item->data()])->andReturn(true);

        });

        $this->artisan('item:new', [
            'name' => $itemName,
            '--password'=>'foo',
            '--content'=>'not-foo'
        ])->expectsOutputToContain("Item with name '$itemName' already exists in the vault.")->assertExitCode(1);

    }, $this);
});
