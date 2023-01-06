<?php

use Surgiie\Console\Command;

beforeAll(function ()  {
    Command::disableAsyncTask();
});

$drivers = get_drivers();

foreach ($drivers as $driverName => $driver){
    it("can symlink $driverName items", function () use($driverName) {
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
    
        $file_one = $test_vault_path.'/test-links/example';
        mkdir(dirname($file_one));
        $file_two = $test_vault_path.'/test-links/example_two';
    
        $this->artisan('export:file', [
            '--file' => ['example:'.$file_one, 'example_two:'.$file_two],
            '--password' => 'secret',
            '--force' => true,
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);
    
        expect(is_file($file_one))->toBeTrue();
        expect(is_file($file_two))->toBeTrue();
    });
    
    it("can symlink $driverName items with permissions", function () use($driverName) {
        fresh_test_vault($driverName);
    
        $test_vault_path = base_path('tests/vault');
    
        $user = get_current_user();
    
        $this->artisan('item:new', [
            '--name' => 'example',
            '--password' => 'secret',
            '--content' => 'test',
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);
    
        $file_one = $test_vault_path.'/test-links/example';
        mkdir(dirname($file_one));
    
        $this->artisan('export:file', [
            '--file' => ['example:'.$file_one],
            '--password' => 'secret',
            '--user' => $user,
            '--group' => $user,
            '--permissions' => '777',
            '--force' => true,
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);
    
        expect(is_file($file_one))->toBeTrue();
        expect(substr(decoct(fileperms($file_one)), -4))->toBe('0777');
    });
    
    it("can symlink $driverName items with permissions from item data", function () use($driverName) {
        fresh_test_vault($driverName);
    
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
    
        $file_one = $test_vault_path.'/test-links/example';
        mkdir(dirname($file_one));
    
        $this->artisan('symlink', [
            '--file' => ['example:'.$file_one],
            '--password' => 'secret',
            '--force' => true,
            '--vault-path' => $test_vault_path,
        ])->assertExitCode(0);
    
        expect(is_file($file_one))->toBeTrue();
        expect(substr(decoct(fileperms($file_one)), -4))->toBe('0777');
    });
    
}