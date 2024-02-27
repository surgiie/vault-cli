<?php

namespace App\Commands;

use App\Concerns\GathersInput;
use App\Concerns\InteractsWithDrivers;
use App\Support\Config;
use App\Support\Vault;

class RemoveItemsCommand extends BaseCommand
{
    use GathersInput, InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:remove
                                {--name=* : The names of the vault items to remove.}
                                {--password= : The password for the decryption}
                                {--namespace=default : Folder to put the vault item in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Remove item(s) from the vault.';

    /**
     * Remove item(s) from the vault.
     */
    public function handle(): int
    {
        if (empty($this->option('name'))) {
            $this->error('Please provide the name(s) of the item to remove, using the --name option.');

            return 1;
        }

        $failures = false;

        $password = $this->getEncryptionPassword($config = new Config);

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        foreach ($this->option('name') as $name) {
            if (! $vault->has($hash = $this->hashItem($name), $namespace = $this->option('namespace'))) {
                $this->components->warn("The vault does not contain an item called '$name' in the $namespace namespace, skipped.");

                continue;
            }

            // first retrieve the item from the vault, this will check that the user attempting
            // to remove the item has the correct password, otherwise prevent the removal.
            $vault->get($hash, $this->arbitraryOptions, $this->option('namespace'));

            $success = $this->runTask("Remove vault item '$name'", function () use ($hash, $vault) {
                return $vault->remove($hash, $this->arbitraryOptions, $this->option('namespace'));
            }, spinner: ! $this->app->runningUnitTests());

            if ($success === false) {
                $failures = true;
            }
        }

        return $failures ? 1 : 0;
    }
}
