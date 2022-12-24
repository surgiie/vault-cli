<?php

use Surgiie\Console\Command;

beforeAll(function () {
    Command::disableAsyncTask();
});

it('can symlink items', function () {
    fresh_test_vault('local');

    $test_vault_path = base_path('tests/vault');

    $this->artisan('item:new', [
        '--name' => 'example',
        '--password' => 'secret',
        '--content' => 'test',
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    $this->artisan('item:new', [
        '--name' => 'example_two',
        '--password' => 'secret',
        '--content' => 'test_two',
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    $symlink_one = $test_vault_path.'/test-links/example';
    mkdir(dirname($symlink_one));
    $symlink_two = $test_vault_path.'/test-links/example_two';

    $this->artisan('symlink', [
        '--link' => ['example:'.$symlink_one, 'example_two:'.$symlink_two],
        '--password' => 'secret',
        '--force' => true,
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    expect(is_link($symlink_one))->toBeTrue();
    expect(is_link($symlink_two))->toBeTrue();
});

it('can symlink items with permissions', function () {
    fresh_test_vault('local');

    $test_vault_path = base_path('tests/vault');

    $user = get_current_user();

    $this->artisan('item:new', [
        '--name' => 'example',
        '--password' => 'secret',
        '--content' => 'test',
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    $symlink_one = $test_vault_path.'/test-links/example';
    mkdir(dirname($symlink_one));

    $this->artisan('symlink', [
        '--link' => ['example:'.$symlink_one],
        '--password' => 'secret',
        '--user' => $user,
        '--group' => $user,
        '--permissions' => '777',
        '--force' => true,
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    expect(is_link($symlink_one))->toBeTrue();
    expect(substr(decoct(fileperms($symlink_one)), -4))->toBe('0777');
});

it('can symlink items with permissions from item data', function () {
    fresh_test_vault('local');

    $test_vault_path = base_path('tests/vault');

    $user = get_current_user();

    $this->artisan('item:new', [
        '--name' => 'example',
        '--password' => 'secret',
        '--content' => 'test',
        '--vault-symlink-user' => $user,
        '--vault-symlink-group' => $user,
        '--vault-symlink-permissions' => '777',
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    $symlink_one = $test_vault_path.'/test-links/example';
    mkdir(dirname($symlink_one));

    $this->artisan('symlink', [
        '--link' => ['example:'.$symlink_one],
        '--password' => 'secret',
        '--force' => true,
        '--vault-path' => $test_vault_path,
    ])->assertExitCode(0);

    expect(is_link($symlink_one))->toBeTrue();
    expect(substr(decoct(fileperms($symlink_one)), -4))->toBe('0777');
});
