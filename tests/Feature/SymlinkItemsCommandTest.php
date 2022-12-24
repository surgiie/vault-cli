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
