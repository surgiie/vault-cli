<?php

use Tests\EncryptTestHelper;

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver) {
    $driver = new $drivers[$driverName];

    it("can create $driverName item", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $driver->setVaultPath($test_vault_path);
        $driver->boot();

        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test');
    });

    it("cannot create existing $driverName item", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        $create_item = function ($vault_path, $content) {
            return $this->artisan('item:new', [
                '--name' => 'example',
                '--password' => 'secret',
                '--content' => $content,
                '--vault-path' => $vault_path,
            ]);
        };

        $create_item($test_vault_path, 'test')->assertExitCode(0);

        $driver->setVaultPath($test_vault_path);
        $driver->boot();

        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test');

        $command = $create_item($test_vault_path, 'new value')->assertExitCode(1);
        $command->expectsOutputToContain('The vault item EXAMPLE already exists');
        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->not->toBe('new value');
    });

    it("can create $driverName items with namespaces", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'foo',
            '--namespace' => 'other',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->setVaultPath($test_vault_path);
        $driver->boot();
        $helper = new EncryptTestHelper('secret', $driver);

        //default namespace should not have the item
        expect($driver->exists($hash))->toBeFalse();
        // other namespace will.
        expect($driver->exists($hash, 'other'))->toBeTrue();

        expect($helper->decryptVaultItem($hash, 'other'))->toBe('foo');
    });

    it("can create $driverName items from content files", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');
        $content_file = base_path('tests/vault/foo');

        file_put_contents($content_file, 'pizza');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content-file' => $content_file,
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->setVaultPath($test_vault_path);
        $driver->boot();
        $helper = new EncryptTestHelper('secret', $driver);

        expect($driver->exists($hash))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('pizza');
    });

    it("can create $driverName items with extra data", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'foo',
            '--vault-path' => $test_vault_path,
            '--extra' => 'bar',
            '--more' => 'data',
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->setVaultPath($test_vault_path);
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

    it("can create $driverName item with extra data using key data files", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');
        $data_file = base_path('tests/vault/data-file');

        file_put_contents($data_file, 'bar');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'foo',
            '--vault-path' => $test_vault_path,
            '--key-data-file' => ["extra:$data_file"],
        ])->assertExitCode(0);

        $hash = sha1('EXAMPLE');
        $driver->setVaultPath($test_vault_path);
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
