<?php

namespace App\Commands;

use Symfony\Component\Finder\Finder;

class SelectVaultCommand extends BaseCommand
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'select {name? : The name of the vault to select.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Select the default vault the cli should work with.';

    /**
     * Ask user to select vault using interactive menu.
     *
     * @return void
     */
    protected function askForVaultSelection()
    {
        $files = (new Finder())->directories()->in(vault_path('vaults'))->depth(0);

        $vaults = [];

        foreach ($files as $file) {
            $name = $file->getBaseName();

            $vaults[$name] = $name;
        }

        if (empty($vaults)) {
            $this->exit('No vaults available for selection.');
        }

        $name = $this->menu('Select a vault:', $vaults)->open();

        if (! $name) {
            $this->exit('Aborted');
        }

        return $name;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->data->get('name');

        if (! $name) {
            $name = $this->askForVaultSelection();
        }

        $defaultFile = vault_path('default-vault');

        if (! is_dir(vault_path("vaults/$name"))) {
            $this->exit("The vault '$name' does not exist");
        }

        file_put_contents($defaultFile, $name);

        $this->components->info("Set the default vault to: $name");
    }
}
