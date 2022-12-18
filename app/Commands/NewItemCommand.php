<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\Process\Process;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class NewItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation;
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
                                {--editor=vim : When no content for item is given and a tmp file is opened to create content, use this editor. }
                                {--folder=default : Folder to put the vault item in.}
                                ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new vault item. Can pass arbitrary options to create with ';


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
    /**Get the content for the vault item.*/
    protected function getContent()
    {
        $content = $this->data->get('content');
        $fromFile = $this->data->get("content-file");

        if ($fromFile && $content) {
            $this->exit("Conflicted options given --content and --content-file. Only one is allowed.");
        }

        if ($fromFile) {
            if (!is_file($fromFile)) {
                $this->exit("File from --content-file not found: $fromFile");
            }
            return trim(file_get_contents($fromFile));
        }

        if ($content) {
            return $content;
        }

        $editor = $this->data->get("editor");

        if ($this->components->confirm("No content passed for item, open a tmp file to add content? Will use $editor as set by --editor option.")) {
            $handle = tmpfile();

            $meta = stream_get_meta_data($handle);

            fwrite($handle, "");

            $process = new Process([$editor, $meta['uri']]);

            $process->setTty(true);
            $process->mustRun();

            return file_get_contents($meta['uri']);
        }

        $this->exit("No content given for item.");
    }

    /**Derive encryption key.*/
    protected function deriveKey(string $password)
    {
        $salt = $this->generateSaltFromPassword($password);

        $encryptionKey = hash_pbkdf2('sha256', $password,  $salt, iterations: 100000, length: 32);

        return $encryptionKey;
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

        $itemFileName = sha1($name);

        $itemPath = $this->vaultPath("$folder/$itemFileName");

        if(is_file($itemPath)){
            $this->exit("There is already a vault item called $name");
        }

        $password = $this->getPassword();

        $encryptionKey = $this->deriveKey($password);

        $this->runTask("Create new vault item called $name", function () use ($content, $encryptionKey, $itemPath) {

            $name = $this->data->get('name');

            $encrypter = new Encrypter($encryptionKey,  "AES-256-CBC");

            $item = array_merge(['name' => $name, 'content' => $content], $this->arbitraryData->all());

            $fileContent = json_encode($item);

            $fileContent = $encrypter->encrypt($fileContent);

            @mkdir(dirname($itemPath), recursive: true);

            return file_put_contents($itemPath, $fileContent) !== false;
        });
    }
}
