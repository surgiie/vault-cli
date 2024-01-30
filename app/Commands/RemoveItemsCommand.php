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
     * The transformers for input arguments and options.
     */
    public function transformers(): array
    {
        return [
            'name.*' => ['trim', fn ($v) => $this->toUpperSnakeCase($v)],
            'namespace' => 'trim',
            'password' => 'trim',
        ];
    }

    /**
     * The validation rules for input arguments and options.
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'name.*' => 'required',
        ];
    }

    /**
     * Remove item(s) from the vault.
     */
    public function handle(): int
    {
        $failures = false;

        $password = $this->getEncryptionPassword($config = new Config);

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        foreach ($this->data->get('name') as $name) {
            if (! $vault->has($hash = $this->hashItem($name), $namespace = $this->data->get('namespace'))) {
                $this->components->warn("The vault does not contain an item called '$name' in the $namespace namespace, skipped.");

                continue;
            }

            // first retrieve the item from the vault, this will check that the user attempting
            // to remove the item has the correct password, otherwise prevent the removal.
            $vault->get($hash, $this->arbitraryData, $this->data->get('namespace'));

            $success = $this->runTask("Remove vault item called $name", function () use ($hash, $vault) {
                return $vault->remove($hash, $this->arbitraryData, $this->data->get('namespace'));
            }, spinner: ! $this->app->runningUnitTests());

            if ($success === false) {
                $failures = true;
            }
        }

        return $failures ? 1 : 0;
    }
}
