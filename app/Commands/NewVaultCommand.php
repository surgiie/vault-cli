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
    protected $signature = 'new {name : The name for this vault. }
                                {--driver= : Set the driver non-interactively. }';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new vault directory.';

    /**
     * The transformers for input arguments and options.
     *
     * @return array
     */
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
        $name = $this->data->get('name');

        $driver = $this->data->get('driver');

        $vaultPath = vault_path("vaults/$name");

        if (is_dir($vaultPath)) {
            $this->exit("The vault '$name' already exists in ~/.vault/vaults/$name");
        }

        $drivers = array_keys(static::$drivers);

        if ($driver && ! in_array($driver, $drivers)) {
            $this->exit("Invalid driver: $driver");
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

        @mkdir($vaultPath, recursive: true);

        file_put_contents(vault_path("vaults/$name/driver"), $driver);

        if($driver == 'sqlite'){
            $database = vault_path("vaults/$name/database");

            touch($database);

            config([
                'database.connections.vault' => array_merge(config('database.connections.vault'), [
                    'database' => vault_path("vaults/$name/database"),
                ]),
            ]);
            
            $this->call("migrate");
        }

        $this->components->info("Created new vault $driver vault: $name");

        return 0;
    }
}
