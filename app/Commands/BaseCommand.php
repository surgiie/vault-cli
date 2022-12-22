<?php

namespace App\Commands;

use ErrorException;
use App\Drivers\LocalVault;
use Illuminate\Support\Str;
use App\Drivers\SqliteVault;
use InvalidArgumentException;
use Surgiie\Console\Command as ConsoleCommand;

abstract class BaseCommand extends ConsoleCommand
{

    /**The available drivers and their implementation classes. */
    protected array $drivers = [
        'local'=> LocalVault::class,
        'sqlite'=>SqliteVault::class
    ];

    /**Run requirements for the cli/command. */
    public function requirements()
    {
        return [
            function(){
                $path = vault_path(basePath: $option = $this->option('vault-path'));
                $option = $option ? " --vault-path=$path" : '';

                $isSetDriverCommandRunning = str_contains($this->signature, "set:driver");
                
                if(! $isSetDriverCommandRunning && !is_dir($vaultDir = vault_path(basePath: $this->option('vault-path', "")))){
                    return "The $vaultDir vault does not exist. Create a new vault directory by running: `vault set:driver$option`";
                }

                $driverFilePath = vault_path("driver", $this->option('vault-path', ""));
                
                if($isSetDriverCommandRunning){
                    return;
                }

                if(!is_file($driverFilePath)){
                    return "Driver is not set for this vault, run `vault set:driver$option`";
                }

                
                if(!in_array(file_get_contents($driverFilePath), array_keys($this->drivers))){
                    return "Invalid driver is set, reset with `vault set:driver`";
                }
            }

        ];
    }

    /**Get the driver class instance.*/
    protected function getDriver(string $vaultPath = "")
    {
        $setDriver = file_get_contents(vault_path('driver', basePath: $vaultPath));

        $class = $this->drivers[$setDriver];

        $driver = new $class;

        $driver->setVaultPath($vaultPath ?:vault_path());

        $driver->boot();

        return $driver;
    }

    /**Parse key value options.*/
    protected function parseKeyValueOption(string $param, string $optionName)
    {
        try {
            list($key, $value) = explode(':', $param);
        } catch (ErrorException) {
            throw new InvalidArgumentException(
                "Could not parse key value option for $optionName, value given: $param, expected <key>:<value> format."
            );
        }
        return [$key, $value];
    }

    /**Normalize item name to snake & uppercase.*/
    protected function normalizeItemName(string $name)
    {
        $name = str_replace(["-", "_"], [" ", ""], mb_strtolower($name));

        return mb_strtoupper(Str::snake($name));
    }

    /**Get a input from file, command option or ask if not derived from other methods.*/
    protected function getFromFileOptionOrAsk(string $name, array $askArgs = [])
    {
        $fromFile = $this->data->get("$name-file");
        $hasFileOption = $this->hasOption("--$name-file");
        $secret = $this->data->get($name);

        if ($fromFile && $secret && $hasFileOption) {
            $this->exit("Conflicted options given --$name and --$name-file. Only one is allowed.");
        }

        if ($fromFile && $hasFileOption) {
            if (!is_file($fromFile)) {
                $this->exit("File from --$name-file not found: $fromFile");
            }
            return trim(file_get_contents($fromFile));
        }

        return $this->getOrAskForInput($name, ...$askArgs);
    }
}
