<?php

namespace App\Commands;

use App\Support\Config;

use function Laravel\Prompts\select;

class UseVaultCommand extends BaseCommand
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'use {name? : The name of the vault to use}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Set the vault the cli should work with.';

    /**
     * Select a default vault.
     */
    public function handle(): int
    {
        $config = new Config();

        $vaults = array_keys($config->get('vaults', []));

        if (empty($vaults)) {
            $this->exit('No vaults configured in ~/.vault/config.yaml');
        }

        $name = $this->argument('name') ?: select(label: 'Which vault do you want to set as default?', options: $vaults);

        if (! in_array($name, $vaults)) {
            $this->exit("The vault '$name' is not configured in ~/.vault/config.yaml");
        }

        $config->set('use-vault', $name);
        $config->save();

        $this->components->info("Now using vault: $name");

        return 0;
    }
}
