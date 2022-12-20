<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use App\Concerns\GathersContentInput;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class EditItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation, GathersContentInput;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'edit:item {--name= : The name of the vault item.}
                                {--password= : The password to use during encryption of this item.}
                                {--content= : The content for the item.}
                                {--content-file= : Read item content from file instead of option.}
                                {--password-file= : Read password from file instead of option. }
                                {--key-data-file=* : Load the content for a extra data key from file using <key>:<file-path> format.}
                                {--editor=vim : When no content for item is given and a tmp file is opened to create content, use this editor. }
                                {--folder=default : Folder to put the vault item in.}
                                ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Edit an existing vault item. Can pass arbitrary options to update/overwrite item with.';


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
        $this->ensureVaultDirExists();
        
        $content = $this->getContent();
        
        $name = $this->normalizeItemName($this->data->get('name'));

        $folder = $this->data->get('folder');

        $itemHash = sha1($name);
        $itemPath = $this->vaultPath("$folder/$itemHash");

        if(! is_file($itemPath)){
            $this->exit("There is no vault item called $name in $folder folder.");
        }

        $password = $this->getPassword();

        
        $encryptionKey = $this->deriveKey($password);
        $encrypter = new Encrypter($encryptionKey,  "AES-256-CBC");
        $currentItemData = json_decode($encrypter->decrypt(file_get_contents($itemPath)), true);

        $otherData = $this->loadOtherDataForItemCrud($this->data->get("key-data-file", []));

        $this->runTask("Edit vault item called $name in $folder folder", function () use ($currentItemData, $content, $encrypter, $itemPath, $otherData) {

            $name = $this->data->get('name');

            $item = array_merge($currentItemData, ['name' => $name, 'content' => $content], $otherData);
            $fileContent = json_encode($item);

            $fileContent = $encrypter->encrypt($fileContent);

            @mkdir(dirname($itemPath), recursive: true);

            return file_put_contents($itemPath, $fileContent) !== false;
        });
    }
}
