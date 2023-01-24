<?php

use Tests\EncryptTestHelper;

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver) {
    $driver = new $driver;
    it("can create $driverName item", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
        ])->assertExitCode(0);

        $driver->boot();

        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test');
    });

    it("cannot create existing $driverName item", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);

        $create_item = function ($content) {
            return $this->artisan('item:new', [
                'name' => 'example',
                '--password' => 'secret',
                '--content' => $content,
            ]);
        };

        $create_item('test')->assertExitCode(0);

        $driver->boot();

        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test');

        $command = $create_item('new value')->assertExitCode(1);
        $command->expectsOutputToContain("The tests vault already has an item called 'EXAMPLE' in the default namespace.");
        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->not->toBe('new value');
    });

    it("can create $driverName items with namespaces", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'foo',
            '--namespace' => 'other',
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->boot();
        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash))->toBeFalse();
        expect($driver->exists($hash, 'other'))->toBeTrue();
        expect($helper->decryptVaultItem($hash, 'other'))->toBe('foo');
    });

    it("can create $driverName items from content files", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);

        $content_file = vault_path('vaults/tests/foo');

        file_put_contents($content_file, 'pizza');

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content-file' => $content_file,
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->boot();
        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('pizza');
    });

    it("can create $driverName items with extra data", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'foo',
            '--extra' => 'bar',
            '--more' => 'data',
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->boot();
        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash))->toBeTrue();

        expect($helper->decryptVaultItem($hash, full: true))->toBe([
            'name' => 'EXAMPLE',
            'content' => 'foo',
            'extra' => 'bar',
            'more' => 'data',
        ]);
    });

    it("can create $driverName item with extra data using key data files", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);

        $data_file = vault_path('vaults/tests/data-file');

        file_put_contents($data_file, 'bar');

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'foo',
            '--key-data-file' => ["extra:$data_file"],
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->boot();
        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash))->toBeTrue();

        expect($helper->decryptVaultItem($hash, full: true))->toBe([
            'name' => 'EXAMPLE',
            'content' => 'foo',
            'extra' => 'bar',
        ]);
    });
}
