<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class SymlinkItemsCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'symlink
                            {--link=* : The names of the items to export.}
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
    protected $description = 'Get decrypted items from vault and symlink the content to target files.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'link.*' => 'trim',
            'namespace' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
        ];
    }

    /**Transform inputs.*/
    public function rules()
    {
        return [
            'link' => ['required'],
            'link.*' => ['min:1'],
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

        $links = $this->data->get('link');
        $driver = $this->getDriver();
        $vaultPath = $this->getVaultPath();

        $driver->ensureVaultExists();

        @mkdir(vault_path('symlinks', $vaultPath));

        $password = $this->getEncryptionPassword();
        $encryptionKey = $this->deriveEncryptionKey($password);
        $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

        // validate links
        foreach ($links as $name) {
            [$name, $_] = $this->parseKeyValueOption($name, 'link');

            $name = $this->normalizeItemName($name);

            $itemHash = sha1($name);

            if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name does not exist.");
            }
        }

        // symlink target items
        $this->runTask('Symlink vault items to files', function () use ($vaultPath, $links, $driver, $encrypter) {
            foreach ($links as $name) {
                [$name, $path] = $this->parseKeyValueOption($name, 'link');

                $name = $this->normalizeItemName($name);

                $itemHash = sha1($name);

                $item = json_decode($encrypter->decrypt($driver->get($itemHash, $this->data->get('namespace'))), true);

                $content = $item['content'];
                $fileName = basename($path);
                $fileName = $fileName.'--'.sha1($path);
                $intermediatePath = $vaultPath."/symlinks/$fileName";

                file_put_contents($intermediatePath, $content);

                // unlink existing file if it exists.
                if (is_link($path) || is_file($path)) {
                    unlink($path);
                }

                symlink($intermediatePath, $path);

                $permissions = $this->data->get('permissions', $item['vault-symlink-permissions'] ?? '');
                if ($permissions) {
                    $permissions = octdec($permissions);
                    chmod($path, $permissions);
                }

                $user = $this->data->get('user', $item['vault-symlink-user'] ?? '');
                $group = $this->data->get('group', $item['vault-symlink-group'] ?? '');

                if ($user) {
                    chown($intermediatePath, $user);
                }
                if ($user && is_sudo()) {
                    lchown($path, $user);
                }

                if ($group) {
                    chgrp($intermediatePath, $group);
                }

                if ($group && is_sudo()) {
                    lchgrp($path, $group);
                }
            }
        });
    }
}
