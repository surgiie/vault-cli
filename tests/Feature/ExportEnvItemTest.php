<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;
use App\Support\Testing\Fakes\ConfigFake;

drivers(function ($driver, $cipher, $algorithm) {
    $label = $driver['name'].'-'.$cipher.'-'.$algorithm;

    it("can export $label item content to .env file", function () use ($driver, $cipher, $algorithm) {

        $this->artisan('use', [
            'name' => $driver['name'],
        ])->assertExitCode(0);

        $itemName = Str::random(10);

        $this->partialMock($driver["class"], function ($mock) use($itemName) {
            $item = new VaultItem($itemName, "default", sha1($itemName), ["content"=>'foo']);
            $mock->shouldReceive('has')->andReturn(true)->shouldReceive('get')->andReturn($item);
        });

        $env = ConfigFake::basePath('test.env');

        $this->artisan('export:env-file', [
            '--password'=>'foo',
            "--env-file"=> $env,
            "--export"=> ['test']
        ])->expectsOutputToContain("Exported vault items to: tests/.vault/test.env")->assertExitCode(0);

        expect(file_exists($env))->toBeTrue();
        expect(file_get_contents($env))->toBe("TEST=foo\n");

    });
});
