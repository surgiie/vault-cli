<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class ExportItemFileCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'export:file
                            {--item=* : The names of the items to export.}
                            {--password= : The password for the decryption.}
                            {--vault-path= : The path to your .vault directory if not ~/.vault}
                            {--user= : The user who owns the intermediate file if not current.}
                            {--group= : The group who owns the intermediate file if not current.}
                            {--permissions= : The permissions to set on the intermediate file.}
                            {--force : Force symlink the file if a file already exists.}
                            {--namespace=default : The namespace to put the vault item in.}
                            {--password-file= : Read password from file instead of option.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Export content of vault items to files.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'item.*' => 'trim',
            'namespace' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
        ];
    }

    /**Validation rules.*/
    public function rules()
    {
        return [
            'item' => ['required'],
            'item.*' => ['min:1'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       
        $files = $this->data->get('item');
        $driver = $this->getDriver();
        $vaultPath = $this->getVaultPath();

        $driver->ensureVaultExists();

        $password = $this->getEncryptionPassword();
        $encryptionKey = $this->deriveEncryptionKey($password);
        $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

        // validate links
        foreach ($files as $name) {
            [$name, $_] = $this->parseKeyValueOption($name, 'file');

            $name = $this->normalizeItemName($name);

            $itemHash = sha1($name);

            if (!$driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->vaultItemDoesNotExist($name, $vaultPath, $namespace);
            }
        }

        // export target items
        foreach ($files as $name) {

            [$name, $path] = $this->parseKeyValueOption($name, 'item');

            if (is_file($path) && $this->data->get('force') !== false && !$this->confirm("File '$path' already exists, overwrite?")) {
                continue;
            }
    

            $this->runTask("Export vault item '$name' to $path", function () use ($name, $path, $driver, $encrypter) {

                $name = $this->normalizeItemName($name);

                $itemHash = sha1($name);

                try {
                    $item = json_decode($encrypter->decrypt($driver->get($itemHash, $this->data->get('namespace'))), true);
                }catch (DecryptException){
                    $this->exit("Could not decrypt item with set/given password: $name");
                }

                $content = $item['content'];

                file_put_contents($path, $content);

                $permissions = $this->data->get('permissions', $item['vault-export-permissions'] ?? '');

                if ($permissions) {
                    $permissions = octdec($permissions);
                    chmod($path, $permissions);
                }

                $user = $this->data->get('user', $item['vault-export-user'] ?? '');
                $group = $this->data->get('group', $item['vault-export-group'] ?? '');

                if ($user) {
                    chown($path, $user);
                }

                if ($group) {
                    chgrp($path, $group);
                }
            });
        }
    }
}
