<?php

use Surgiie\Console\Command;

beforeAll(function () {
    Command::disableAsyncTask();
});

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver) {
    $driver = new $drivers[$driverName];

    it("can retrieve $driverName item", function () use ($driverName) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $command = $this->artisan('item:get', [
            '--name' => 'example',
            '--password' => 'secret',
            '--vault-path' => $test_vault_path,
        ]);

        $command->assertExitCode(0);
        $command->expectsOutputToContain('test');
    });

    it("can retrieve full json $driverName item", function () use ($driverName) {
        fresh_test_vault($driverName);

        $test_vault_path = base_path('tests/vault');

        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);

        $command = $this->artisan('item:get', [
            '--name' => 'example',
            '--password' => 'secret',
            '--json' => true,
            '--vault-path' => $test_vault_path,
        ]);

        $command->assertExitCode(0);
        $command->expectsOutputToContain(<<<'EOL'
        {
            "name": "EXAMPLE",
            "content": "test"
        }
        EOL);
    });
}
