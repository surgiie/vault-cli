<?php

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver) {
    $driver = new $drivers[$driverName];

    it("can remove $driverName item", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        // create item.
        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $driver->setVaultPath($test_vault_path);
        $driver->boot();

        $this->artisan('item:remove', [
            '--name' => 'example',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeFalse();
    });

    it("can remove $driverName item with namespaces", function () use ($driverName, $driver) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        // create item.
        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--namespace' => 'other',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $driver->setVaultPath($test_vault_path);
        $driver->boot();

        $this->artisan('item:remove', [
            '--name' => 'example',
            '--namespace' => 'other',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        expect($driver->exists($hash = sha1('EXAMPLE'), namespace: 'other'))->toBeFalse();
    });
}
