<?php

namespace App\Commands;

use App\Concerns\GathersContentInput;
use App\Concerns\HandlesEncryption;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class RemoveItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation, GathersContentInput, HandlesEncryption;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:remove 
                                {--name=* : The names of the vault items to remove.}
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
            'name.*' => 'trim',
            'namespace' => 'trim',
            'password' => 'trim',
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
        $names = [];
        foreach($this->data->get('name') as $name){
            $names[] = $this->normalizeItemName($name);
        }

        $driver = $this->getDriver();

        $vaultPath = $this->getVaultPath();

        $driver->ensureVaultExists();

        $hashes = [];
        foreach($names as $name){
            $itemHash = sha1($name);

            if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name does not exist.");
            }
            $hashes[] = $itemHash;
        }

        $this->runTask("Remove vault item called $name", function () use ($hashes, $driver) {
            foreach($hashes as $hash){
                $driver->remove($hash, $this->data->get('namespace'));
            }
        });

        if(is_dir($vaultPath."/.git")){
            $this->components->warn("It appears like your vault is version controlled, be sure to commit/push your removed item.");
        }
    }
}
