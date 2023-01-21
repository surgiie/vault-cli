<?php


it('can set driver', function () {
    fresh_test_vault(name: null);

    $test_driver_path = vault_path('vaults/tests/driver');

    $this->artisan("new", [
        "name"=>"tests",
        "--driver"=>"local"
    ])->assertExitCode(0);

    expect(file_get_contents($test_driver_path))->toBe('local');
});

it('errors when setting invalid driver', function () {
    fresh_test_vault();

    $command = $this->artisan("new", [
        "name"=>"test",
        "--driver"=>"invalid"
    ]);

    $command->assertExitCode(1);

    $command->expectsOutputToContain('Invalid driver: invalid');
});

