<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use App\Concerns\GathersContentInput;
use App\Concerns\HandlesEncryption;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class RemoveItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation, GathersContentInput, HandlesEncryption;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:remove {--name= : The name of the vault item.}
                                {--vault-path= : The path to your .vault directory if not ~/.vault}
                                {--namespace=default : Folder to put the vault item in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Remove an item from the vault.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'name' => 'trim',
            'namespace' => 'trim',
            'password' => 'trim'
        ];
    }
    /**Transform inputs.*/
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->normalizeItemName($this->data->get('name'));

        $driver = $this->getDriver($vault = $this->data->get('vault-path', ''));

        $itemHash = sha1($name);
        $vaultPath = $vault ?: vault_path();

        $driver->ensureVaultExists();

        if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
            $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name does not exist.");
        }

        $this->runTask("Remove vault item called $name", function () use ($itemHash, $driver) {
            return $driver->remove($itemHash, $this->data->get('namespace'));
        });
    }
}
