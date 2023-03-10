<?php

namespace App\Commands;

use App\Concerns\GathersContentInput;
use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithTransformers;
use Symfony\Component\Process\Process;

class EditItemCommand extends BaseCommand
{
    use WithTransformers, GathersContentInput, HandlesEncryption;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:edit 
                                {name : The name of the vault item to edit.}
                                {--password= : The password to use during encryption of this item.}
                                {--content= : The content for the item.}
                                {--content-file= : Read item content from file instead of option.}
                                {--password-file= : Read password from file instead of option. }
                                {--key-data-file=* : Load the content for a extra data key from file using <key>:<file-path> format.}
                                {--edit-json : When passed, a tmp file will be opened in vim, where you can edit the full json instead of just content.  }
                                {--namespace=default : Folder to put the vault item in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Edit an existing vault item.';

    /**
     * Allow the command to accept arbritrary options.
     *
     * @var bool
     */
    protected $arbitraryOptions = true;

    /**
     * The transformers for input arguments and options.
     *
     * @return array
     */
    public function transformers()
    {
        return [
            'name' => 'trim',
            'namespace' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
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

        $name = $this->normalizeToUpperSnakeCase($this->data->get('name'));

        $driver = $this->getDriver();

        $itemHash = sha1($name);

        if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
            $this->exit("The $vaultName vault does not contain an item called '$name' in the $namespace namespace.");
        }

        $password = $this->getEncryptionPassword();

        $encryptionKey = $this->deriveEncryptionKey($password, $itemHash);

        $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

        $currentItemData = json_decode($encrypter->decrypt($driver->get($itemHash, $this->data->get('namespace'))), true);

        $content = false;
        $otherData = [];
        if ($this->data->get('edit-json')) {
            $handle = tmpfile();

            $meta = stream_get_meta_data($handle);
            // ensure that item naem cannot be updated.
            unset($currentItemData['name']);
            fwrite($handle, json_encode($currentItemData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $process = new Process(['vim', $meta['uri']]);

            $process->setTty(true);
            $process->setIdleTimeout(null);
            $process->setTimeout(null);
            $process->mustRun();

            $currentItemData = json_decode(file_get_contents($meta['uri']), true);

            if (is_null($currentItemData)) {
                $this->exit('Could not update json item, bad json. Try again');
            }
            // ensure we remove, if someone got funny ideas.
            unset($currentItemData['name']);
        } else {
            $otherData = $this->gatherOtherItemData($this->data->get('key-data-file', []));

            $content = $this->gatherInputForItemContent(prompt: $this->arbitraryData->isEmpty(), existingContent: $currentItemData['content']);

            if ($this->arbitraryData->isEmpty() && ! $content) {
                $this->exit('No update data given, nothing to do.', code: 1, level: 'warn');
            }
        }

        $this->runTask("Update vault item called $name", function () use ($name, $content, $itemHash, $driver, $currentItemData, $encrypter, $otherData) {
            $baseData = ['name' => $name];

            if ($content) {
                $baseData['content'] = $content;
            }

            $item = array_merge($currentItemData, $baseData, $otherData);

            $fileContent = json_encode($item);

            $fileContent = $encrypter->encrypt($fileContent);

            return $driver->store($itemHash, $fileContent, $this->data->get('namespace'));
        });
    }
}
