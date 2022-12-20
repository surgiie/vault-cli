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
        $vaultPath = $this->data->get('vault-path', "");
        $driverFilePath = vault_path("driver", $vaultPath);

        if (is_file($driverFilePath) && !$driver && !$this->components->confirm("A driver is currently set. Note this does not migrate your existing items to new vault, continue and switch driver?.")) {
            $this->exit("Aborted", code: 0);
        }

        $drivers = array_keys($this->drivers);

        if (!$driver) {
            $driver = $drivers[$this->menu('Select a vault driver.', $drivers)->open()];
        }

        if (!$driver) {
            return 1;
        }

        file_put_contents($driverFilePath, $driver);

        $vault = vault_path(basePath: $vaultPath);

        $this->components->info("Set driver to $driver for $vault vault.");
    }
}
