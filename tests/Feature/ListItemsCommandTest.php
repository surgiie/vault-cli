<?php

use Surgiie\Console\Command;

beforeAll(function () {
    Command::disableAsyncTask();
});

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver){
    $driver = new $driver;

    it("can list $driverName items names", function () use($driverName) {
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

        
        $command = $this->artisan('item:list', [
            '--password' => 'secret',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        // not entirely sure best way to assert table output, this feels good enough?
        $command->expectsOutputToContain('EXAMPLE_TWO');
        $command->expectsOutputToContain('EXAMPLE');
        
    });
}
