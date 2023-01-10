<?php


use Tests\EncryptTestHelper;



$drivers = get_drivers();

foreach ($drivers as $driverName => $driver){
    $driver = new $driver;
    it("can reencrypt $driverName items", function () use ($driver, $driverName) {
        fresh_test_vault($driverName);
    
        $this->artisan('item:new', [
            'name' => 'example_one',
            '--password' => 'secret',
            '--content' => 'test',
        ])->assertExitCode(0);
    
        $this->artisan('item:new', [
            'name' => 'example_two',
            '--password' => 'secret',
            '--content' => 'test_two',
        ])->assertExitCode(0);
    
        $driver->boot();
    
        $helper = new EncryptTestHelper('secret', $driver);
    
        expect($driver->exists($hash = sha1('EXAMPLE_ONE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test');
            
        expect($driver->exists($hash = sha1('EXAMPLE_TWO')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test_two');

        $this->artisan('items:rencrypt', [
            '--old-password' => 'secret',
            '--new-password' => 'new-secret',
        ])->assertExitCode(0);
            
        $helper = new EncryptTestHelper('new-secret', $driver);

        expect($driver->exists($hash = sha1('EXAMPLE_ONE')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test');
            
        expect($driver->exists($hash = sha1('EXAMPLE_TWO')))->toBeTrue();
        expect($helper->decryptVaultItem($hash))->toBe('test_two');
    });
}