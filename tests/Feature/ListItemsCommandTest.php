<?php

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver) {
    $driver = new $driver;

    it("can list $driverName items names", function () use ($driverName) {
        fresh_test_vault($driverName);

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

        $command = $this->artisan('item:list', [
            '--password' => 'secret',
        ])->assertExitCode(0);

        // not entirely sure best way to assert table output, this feels good enough?
        $command->expectsOutputToContain('EXAMPLE_TWO');
        $command->expectsOutputToContain('EXAMPLE');
    });
}
