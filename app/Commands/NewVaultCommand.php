<?php

namespace App\Commands;

use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class NewVaultCommand extends BaseCommand
{
    use WithTransformers, WithValidation;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'vault:new
                                {--driver= : Set the driver non-interactively. }
                                {--vault-path= : The path to your .vault directory if not ~/.vault}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new vault directory.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'driver' => 'trim',
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
        $vaultPath = $this->getVaultPath();

        $driverFilePath = vault_path('driver', $vaultPath);
        $drivers = array_keys($this->drivers);

        if ($driver && ! in_array($driver, $drivers)) {
            $this->exit("Invalid driver: $driver");
        }

        if (is_file($driverFilePath)) {
            $this->exit('This vault exists & configured to use a driver.');
        }

        if (! $driver) {
            $driver = $drivers[$this->menu("Select a vault driver for vault: $vaultPath", $drivers)->open()] ?? false;
        }

        if (! $driver) {
            $this->exit('Aborted');
        }

        if ($driver == 'sqlite' && ! extension_loaded('sqlite3')) {
            $this->exit('The sqlite3 php extension is not loaded');
        }

        @mkdir(dirname($driverFilePath));

        file_put_contents($driverFilePath, $driver);
        file_put_contents($vaultPath.'/.gitignore', 'symlinks');

        $this->components->info("Set driver to $driver for: $vaultPath vault.");

        return 0;
    }
}
