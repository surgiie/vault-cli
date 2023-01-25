<?php

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver) {
    it("can export $driverName items", function () use ($driverName) {
        fresh_test_vault($driverName);

        $test_vault_path = vault_path('vaults/tests');

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
        ])->assertExitCode(0);

        $this->artisan('item:new', [
            'name' => 'example_two',
            '--password' => 'secret',
            '--content' => 'test_two',
        ])->assertExitCode(0);

        $file_one = $test_vault_path.'/test-links/example';
        mkdir(dirname($file_one));
        $file_two = $test_vault_path.'/test-links/example_two';

        $this->artisan('export:file', [
            '--item' => ['example:'.$file_one, 'example_two:'.$file_two],
            '--password' => 'secret',
            '--force' => true,
        ])->assertExitCode(0);

        expect(is_file($file_one))->toBeTrue();
        expect(is_file($file_two))->toBeTrue();
        expect(file_get_contents($file_one))->toBe('test');
        expect(file_get_contents($file_two))->toBe('test_two');
    });

    it("can export $driverName items with permissions", function () use ($driverName) {
        fresh_test_vault($driverName);

        $test_vault_path = vault_path('vaults/tests');

        $user = posix_getpwuid(posix_geteuid())['name'];

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
        ])->assertExitCode(0);

        $file = $test_vault_path.'/test-links/example';
        mkdir(dirname($file));

        $this->artisan('export:file', [
            '--item' => ['example:'.$file],
            '--password' => 'secret',
            '--user' => $user,
            '--group' => $user,
            '--permissions' => '777',
            '--force' => true,
        ])->assertExitCode(0);

        expect(is_file($file))->toBeTrue();
        expect(file_get_contents($file))->toBe('test');
        expect(substr(decoct(fileperms($file)), -4))->toBe('0777');
    });

    it("can export $driverName items to files with permissions from item data", function () use ($driverName) {
        fresh_test_vault($driverName);

        $test_vault_path = vault_path('vaults/tests');

        $user = posix_getpwuid(posix_geteuid())['name'];

        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--vault-export-user' => $user,
            '--vault-export-group' => $user,
            '--vault-export-permissions' => '777',
        ])->assertExitCode(0);

        $file = $test_vault_path.'/test-links/example';
        mkdir(dirname($file));

        $this->artisan('export:file', [
            '--item' => ['example:'.$file],
            '--password' => 'secret',
            '--force' => true,
        ])->assertExitCode(0);

        expect(is_file($file))->toBeTrue();
        expect(file_get_contents($file))->toBe('test');
        expect(substr(decoct(fileperms($file)), -4))->toBe('0777');
    });
}
