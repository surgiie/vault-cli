<?php

namespace App\Commands;

use App\Drivers\LocalVault;
use App\Drivers\SqliteVault;
use Closure;
use ErrorException;
use Illuminate\Support\Str;
use Surgiie\Console\Command as ConsoleCommand;

abstract class BaseCommand extends ConsoleCommand
{
    /**
     * The available drivers and their implementation classes.
     *
     * @var array
     */
    protected static array $drivers = [
        'local' => LocalVault::class,
        'sqlite' => SqliteVault::class,
    ];

    /**
     * Return drivers supported by the cli.
     *
     * @return array
     */
    public static function getDrivers()
    {
        return static::$drivers;
    }

    /**
     * Get the driver class instance.
     *
     * @return \App\Drivers\LocalVault|\App\Drivers\SqliteVault
     */
    protected function getDriver()
    {
        $name = get_selected_vault_name();

        if ($name === false) {
            $this->exit('A vault is not selected, set one with: vault select <name>');
        }

        $setDriver = file_get_contents(vault_path("vaults/$name/driver"));
        $class = static::$drivers[$setDriver];

        $driver = new $class;

        $driver->boot();

        return $driver;
    }

    /**
     * Parse key value options.
     *
     * @param  string  $param
     * @param  string  $optionName
     * @param  Closure|null  $onParseException
     * @return array
     */
    protected function parseKeyValueOption(string $param, string $optionName, ?Closure $onParseException = null): array
    {
        try {
            [$key, $value] = explode(':', $param, limit: 2);
        } catch (ErrorException) {
            if (! is_callable($onParseException)) {
                $this->exit(
                    "Could not parse key value option for $optionName, value given: $param, expected <key>:<value> format."
                );
            }

            return $onParseException();
        }

        return [$key, $value];
    }

    /**
     * Check if the vault exists.
     *
     * @return void
     */
    protected function checkVaultExists()
    {
        $name = get_selected_vault_name();
        if (! is_dir(vault_path("vaults/$name"))) {
            $this->exit("The vault '$name' doesnt exist.");
        }
    }

    /**
     * Normalize item name to snake & uppercase.
     *
     * @param  string  $name
     * @return string
     */
    protected function normalizeToUpperSnakeCase(string $name)
    {
        $name = str_replace(['-', '_'], [' ', ' '], mb_strtolower($name));

        return mb_strtoupper(Str::snake($name));
    }

    /**
     * Get a input from file, command option or ask if not derived from other methods.
     *
     * @param  string  $name
     * @param  array  $askOptions
     * @return string
     */
    protected function getFromFileOptionOrAsk(string $name, array $askOptions = [])
    {
        $fromFile = $this->data->get("$name-file");
        $hasFileOption = $this->hasOption("--$name-file");
        $secret = $this->data->get($name);

        if ($fromFile && $secret && $hasFileOption) {
            $this->exit("Conflicted options given --$name and --$name-file. Only one is allowed.");
        }

        if ($fromFile && $hasFileOption) {
            if (! is_file($fromFile)) {
                $this->exit("File from --$name-file not found: $fromFile");
            }

            return trim(file_get_contents($fromFile));
        }

        return $this->getOrAskForInput($name, $askOptions);
    }
}
