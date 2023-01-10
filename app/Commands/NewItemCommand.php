<?php

namespace App\Commands;

use App\Concerns\GathersContentInput;
use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class NewItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption, GathersContentInput;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:new 
                                {name : The name of the vault item.}
                                {--password= : The password to use during encryption of this item.}
                                {--content= : The content for the item.}
                                {--content-file= : Read item content from file instead of option.}
                                {--password-file= : Read password from file instead of option. }
                                {--key-data-file=* : Load the content for an extra data key from file using <key>:<file-path> format.}
                                {--namespace=default : The namespace to put the vault item in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new vault item.';

    /**Allow the command to accept arbritrary options.*/
    protected $arbitraryOptions = true;

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'name' => 'trim',
            'folder' => 'trim',
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
        $this->checkVaultExists();

        $vaultName = get_vault_name();
        
        $name = $this->normalizeToUpperSnakeCase($this->data->get('name'));

        $driver = $this->getDriver();

        $itemHash = sha1($name);

        if ($driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
            $this->exit("The $vaultName vault already has an item called '$name' in the $namespace namespace.");
        }

        $content = $this->gatherInputForItemContent();

        if (! $content) {
            $this->exit('Aborted, no content provided.');
        }

        $password = $this->getEncryptionPassword();

        $encryptionKey = $this->deriveEncryptionKey($password, $itemHash);

        $otherData = $this->gatherOtherItemData($this->data->get('key-data-file', []));

        $this->runTask("Create new vault item called $name", function () use ($name, $content, $itemHash, $driver, $encryptionKey, $otherData) {
            $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

            $item = array_merge(['name' => $name, 'content' => $content], $otherData);

            $fileContent = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            $fileContent = $encrypter->encrypt($fileContent);

            return $driver->store($itemHash, $fileContent, $this->data->get('namespace'));
        });
    }
}
