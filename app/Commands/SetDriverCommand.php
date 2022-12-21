<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class SetDriverCommand extends BaseCommand
{
    use WithTransformers, WithValidation;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'set:driver
                                {--driver= : Set the driver non-interactively. }
                                {--vault-path= : The path to your .vault directory if not ~/.vault}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Set the driver for the cli to use.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'driver' => 'trim'
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $driver = $this->data->get('driver');
        $givenVaultPath = $this->data->get('vault-path', "");
        $driverFilePath = vault_path("driver", $givenVaultPath);

        if (is_file($driverFilePath) && !$driver && !$this->components->confirm("A driver is currently set. Note this does not migrate your existing items to new vault, continue and switch driver?.")) {
            $this->exit("Aborted");
        }

        $vaultPath = vault_path(basePath: $givenVaultPath);
        $drivers = array_keys($this->drivers);

        if (!$driver) { 
            $defaultText = $givenVaultPath ? "": " default";
            $driver = $drivers[$this->menu("Select a vault driver for$defaultText vault: $vaultPath", $drivers)->open()] ?? false;
        }

        if (!$driver) {
            $this->exit("Aborted");
        }

        if ($driver == "sqlite" && !extension_loaded('sqlite3')) {
            $this->exit("The sqlite3 php extension is not loaded");
        }

        file_put_contents($driverFilePath, $driver);


        $this->components->info("Set driver to $driver for: $vaultPath vault.");
    }
}
