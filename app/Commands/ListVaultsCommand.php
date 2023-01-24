<?php

namespace App\Commands;

use Symfony\Component\Finder\Finder;

class ListVaultsCommand extends BaseCommand
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list-available';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List the current available vaults in ~/.vaults/vaults.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = (new Finder())->directories()->in(vault_path('vaults'))->depth(0);

        $rows = [];

        $this->line('Available vaults:');

        foreach ($files as $file) {
            $name = $file->getBaseName();
            $rows[] = [$name];
        }

        $this->table(['Vault Name'], $rows);
    }
}
