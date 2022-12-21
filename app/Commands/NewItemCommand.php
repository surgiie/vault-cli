<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use App\Concerns\GathersContentInput;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class NewItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption, GathersContentInput;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new:item {--name= : The name of the vault item.}
                                {--password= : The password to use during encryption of this item.}
                                {--content= : The content for the item.}
                                {--content-file= : Read item content from file instead of option.}
                                {--password-file= : Read password from file instead of option. }
                                {--key-data-file=* : Load the content for a extra data key from file using <key>:<file-path> format.}
                                {--editor=vim : When no content for item is given and a tmp file is opened to create content, use this editor. }
                                {--namespace=default : The namespace to put the vault item in.}
                                {--vault-path= : The path to your .vault directory if not ~/.vault}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new vault item. Can pass arbitrary options to create item with.';


    /**Allow the command to accept arbritrary options.*/
    protected $arbitraryOptions = true;

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'name' => 'trim',
            'folder' => 'trim',
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

        if ($driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
            $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name already exists.");
        }

        $content = $this->gatherInputForItemContent();

        $password = $this->getEncryptionPassword();

        $encryptionKey = $this->deriveEncryptionKey($password);

        $otherData = $this->gatherOtherItemData($this->data->get("key-data-file", []));

        $this->runTask("Create new vault item called $name.", function () use ($content, $itemHash, $driver, $encryptionKey, $otherData) {

            $name = $this->data->get('name');

            $encrypter = new Encrypter($encryptionKey,  "AES-256-CBC");

            $item = array_merge(['name' => $name, 'content' => $content], $otherData);

            $fileContent = json_encode($item);

            $fileContent = $encrypter->encrypt($fileContent);

            return $driver->store($itemHash, $fileContent, $this->data->get('namespace'));
        });
    }
}
