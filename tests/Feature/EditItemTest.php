<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;

drivers(function ($driver, $cipher, $algorithm) {
    $label = $driver['name'].'-'.$cipher.'-'.$algorithm;

    it("can edit $label items", function () use($driver) {
        $itemName = Str::random(10);

        $this->artisan('use', [
            'name' => $driver['name'],
        ])->assertExitCode(0);

       $this->partialMock($driver["class"], function ($mock) use($itemName) {
            $item = new VaultItem($itemName, "default", $hash = sha1($itemName), ["content"=>'foo']);
            $mock->shouldReceive('create')->andReturn(true)
                ->shouldReceive('has')->andReturn(true)
                ->shouldReceive('get')->andReturn($item)
                ->shouldReceive('put')->withArgs([$hash, $item->data()])->andReturn(true);
        });

        $this->artisan('item:edit', [
            'name' => $itemName,
            '--password'=>'foo',
            '--content'=>'not-foo'
        ])->expectsOutputToContain("Update vault item '$itemName': Succeeded")->assertExitCode(0);
    });
});


drivers(function ($driver, $cipher, $algorithm) {
    $label = $driver['name'].'-'.$cipher.'-'.$algorithm;

    it("cannot edit non existing $label items", function () use ($driver){

        $this->artisan('use', [
            'name' => $driver['name'],
        ])->assertExitCode(0);

       $this->partialMock($driver["class"], function ($mock) {
            $mock->shouldReceive('create')->andReturn(true)
                ->shouldReceive('has')->andReturn(false);
        });

        $this->artisan('item:edit', [
            'name' => 'test',
            '--password'=>'foo',
            '--content'=>'not-foo'
        ])->expectsOutputToContain("Item with name 'test' does not exist in the vault.")->assertExitCode(1);

    });
});
