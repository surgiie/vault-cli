<?php

use Surgiie\Console\Command;

beforeAll(function () {
    Command::disableAsyncTask();
});

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver){
    $driver = new $driver;

    it("can export $driverName items to env files", function () use($driverName) {
        fresh_test_vault($driverName);

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

        $this->artisan('export:env-file', [
            '--export' => ['example', 'example_two'],
            '--env-file' => ($envFile = $test_vault_path.'/'.'.env'),
            '--password' => 'secret',
            '--content' => 'test_two',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $env = file_get_contents($envFile);
        expect($env)->toBe(<<<'EOL'
        EXAMPLE="test"
        EXAMPLE_TWO="test_two"
        EOL);
    });

    it("can export $driverName items to env files using custom env names", function () use($driverName) {
        fresh_test_vault($driverName);

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

        $this->artisan('export:env-file', [
            '--export' => ['example:CUSTOM_NAME_EXAMPLE', 'example_two:CUSTOM_NAME_EXAMPLE_TWO'],
            '--env-file' => ($envFile = $test_vault_path.'/'.'.env'),
            '--password' => 'secret',
            '--content' => 'test_two',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $env = file_get_contents($envFile);
        expect($env)->toBe(<<<'EOL'
        CUSTOM_NAME_EXAMPLE="test"
        CUSTOM_NAME_EXAMPLE_TWO="test_two"
        EOL);
    });

    it("can export $driverName items to env files and include variables passed via --include options", function () use($driverName) {
        fresh_test_vault($driverName);

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

        $this->artisan('export:env-file', [
            '--export' => ['example', 'example_two'],
            '--include' => ['FOO:BAR', 'BAR:FOO'],
            '--env-file' => ($envFile = $test_vault_path.'/'.'.env'),
            '--password' => 'secret',
            '--content' => 'test_two',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $env = file_get_contents($envFile);
        expect($env)->toBe(<<<'EOL'
        EXAMPLE="test"
        EXAMPLE_TWO="test_two"
        FOO="BAR"
        BAR="FOO"
        EOL);
    });
}
