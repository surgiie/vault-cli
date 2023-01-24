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
                                {--namespace=default : Folder to put the vault item in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Remove an item from the vault.';

    /**
     * The transformers for input arguments and options.
     *
     * @return array
     */
    public function transformers()
    {
        return [
            'name.*' => 'trim',
            'namespace' => 'trim',
            'password' => 'trim',
        ];
    }

    /**
     * The validation rules for input arguments and options.
     *
     * @return array
     */
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
        $this->checkVaultExists();
        $vaultName = get_selected_vault_name();

        $names = [];
        foreach ($this->data->get('name') as $name) {
            $names[] = $this->normalizeToUpperSnakeCase($name);
        }

        $driver = $this->getDriver();

        foreach ($names as $name) {
            $itemHash = sha1($name);

            if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->exit("The $vaultName vault does not contain an item called '$name' in the $namespace namespace.");
            }

            $this->runTask("Remove vault item called $name", function () use ($itemHash, $driver) {
                $driver->remove($itemHash, $this->data->get('namespace'));
            });
        }
    }
}
