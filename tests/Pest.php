<?php

use App\Commands\BaseCommand;
use App\Drivers\LocalVault;
use App\Drivers\SqliteVault;
use Surgiie\Console\Command;
use Illuminate\Filesystem\Filesystem;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature');

uses()->beforeAll(function () {
    Command::disableAsyncTask();
})->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function fresh_test_vault(?string $driver = null, ?string $name = "tests")
{
    $fs = new Filesystem;

    $basePath = __DIR__.'/.vault';
    
    putenv("VAULT_CLI_BASE_PATH=$basePath");

    $fs->deleteDirectory($basePath);
    
    @mkdir($basePath);
    
    if (! is_null($name)) {
        @mkdir($basePath."/vaults/$name", recursive: true);
    }

    if (! is_null($driver)) {
        file_put_contents($basePath."/vaults/$name/driver", $driver);
    }
    
    file_put_contents($basePath."/default-vault", $name);
}

/**Return available drivers.*/
function get_drivers()
{
    return BaseCommand::getDrivers();
}
