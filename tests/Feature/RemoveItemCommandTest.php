<?php

use App\Drivers\LocalVault;
use Surgiie\Console\Command;

beforeAll(function () {
    Command::disableAsyncTask();
});

$driver = new LocalVault;

it('can remove item', function () use ($driver) {
    fresh_test_vault('local');

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

it('can remove item with namespaces', function () use ($driver) {
    fresh_test_vault('local');

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
