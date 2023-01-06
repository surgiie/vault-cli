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
    protected $signature = 'new 
                                {--vault-name= : Assign a name to this vault. }
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

    /**The command validation rules. */
    public function rules()
    {
        return [
            'vault-name' => ['required']
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

        $name = $this->data->get("vault-name");

        if ($driver && !in_array($driver, $drivers)) {
            $this->exit("Invalid driver: $driver");
        }

        if (is_file($driverFilePath)) {
            $this->exit('This directory already exists.');
        }

        if (!$driver) {
            $driver = $drivers[$this->menu("Select a vault driver for vault: $vaultPath", $drivers)->open()] ?? false;
        }

        if (!$driver) {
            $this->exit('Aborted');
        }

        if ($driver == 'sqlite' && !extension_loaded('sqlite3')) {
            $this->exit('The sqlite3 php extension is not loaded');
        }

        @mkdir(dirname($driverFilePath));

        file_put_contents($driverFilePath, $driver);
        file_put_contents($vaultPath . "/name", $name);
        file_put_contents($vaultPath . '/.gitignore', 'symlinks');

        $this->components->info("Created new vault directory configured to use the $driver driver at: $vaultPath");

        return 0;
    }
}
