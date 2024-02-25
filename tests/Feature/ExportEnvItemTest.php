<?php

use App\Support\VaultItem;
use Illuminate\Support\Str;
use App\Support\Testing\Fakes\ConfigFake;

it("can export item content to .env file", function ()  {
    drivers(function ($driver, $cipher, $algorithm) {
        $itemName = Str::random(10);

        $this->partialMock($driver["class"], function ($mock) use($itemName, $cipher, $algorithm) {
            $item = new VaultItem($itemName, "default",sha1($itemName), ["name"=>$itemName, "content"=>'foo']);
            $mock->shouldReceive('has')->andReturn(true)
                ->shouldReceive('fetch')->andReturn(encrypt_test_item($item, 'foo', $algorithm, $cipher));
        });

        $env = ConfigFake::basePath('test.env');

        @unlink($env);

        $this->artisan('export:env-file', [
            '--password'=>'foo',
            "--env-file"=> $env,
            "--export"=> [$itemName]
        ])->expectsOutputToContain("Exported vault items to: tests/.vault/test.env")->assertExitCode(0);

        expect(file_exists($env))->toBeTrue();

        $envName = to_upper_snake_case($itemName);
        expect(file_get_contents($env))->toBe("$envName=foo\n");

    }, $this);
});
