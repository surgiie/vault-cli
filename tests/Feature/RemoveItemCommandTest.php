<?php


$drivers = get_drivers();

foreach ($drivers as $driverName => $driver){
    $driver = new $driver;

    it("can remove $driverName item", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);
    
        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
        ])->assertExitCode(0);
    
        $driver->boot();
    
        $this->artisan('item:remove', [
            '--name' => ['example'],
        ])->assertExitCode(0);
    
        expect($driver->exists($hash = sha1('EXAMPLE')))->toBeFalse();
    });
    
    it("can remove $driverName item with namespaces", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);
    
        $this->artisan('item:new', [
            'name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--namespace' => 'other',
        ])->assertExitCode(0);
    
        $driver->boot();
    
        $this->artisan('item:remove', [
            '--name' => ['example'],
            '--namespace' => 'other',
        ])->assertExitCode(0);
    
        expect($driver->exists($hash = sha1('EXAMPLE'), namespace: 'other'))->toBeFalse();
    });  
}
