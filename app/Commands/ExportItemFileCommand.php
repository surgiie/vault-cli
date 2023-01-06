<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
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
                            {--file=* : The names of the items to export.}
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
            'file.*' => 'trim',
            'namespace' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
        ];
    }

    /**Validation rules.*/
    public function rules()
    {
        return [
            'file' => ['required'],
            'file.*' => ['min:1'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->data->get('force') === false && ! $this->confirm('This command will overwrite existing files/previous symlinks, continue?')) {
            $this->exit('Aborted');
        }

        $files = $this->data->get('file');
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

            if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name does not exist.");
            }
        }

        // symlink target items
        $this->runTask('Symlink vault items to files', function () use ($vaultPath, $files, $driver, $encrypter) {
            foreach ($files as $name) {
                [$name, $path] = $this->parseKeyValueOption($name, 'link');

                $name = $this->normalizeItemName($name);

                $itemHash = sha1($name);

                $item = json_decode($encrypter->decrypt($driver->get($itemHash, $this->data->get('namespace'))), true);

                $content = $item['content'];

                file_put_contents($path, $content);

                // unlink existing file if it exists.
                if (is_file($path)) {
                    unlink($path);
                }

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
            }
        });
    }
}
