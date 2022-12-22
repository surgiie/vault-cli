<?php

it('can set driver', function () {
    fresh_test_vault();
    
    $test_vault_path = base_path('tests/vault');

    $test_driver_path = base_path('tests/vault/driver');
    
    $this->artisan("set:driver --driver=local --vault-path=$test_vault_path")->assertExitCode(0);

    expect(file_get_contents($test_driver_path))->toBe('local');
});


it('errors when setting invalid driver', function () {
    fresh_test_vault();

    $test_vault_path = base_path('tests/vault');

    $command = $this->artisan("set:driver --driver=invalid --vault-path=$test_vault_path");
    
    $command->assertExitCode(1);

    $command->expectsOutputToContain('Invalid driver: invalid');
});
