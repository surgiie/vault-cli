<?php

namespace App\Commands;

use App\Concerns\InteractsWithDrivers;
use App\Support\Config;
use App\Support\Vault;

use function Laravel\Prompts\password;

class RencryptItemsCommand extends BaseCommand
{
    use InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'reencrypt
                                {--old-password= : The old password previously used for encryption.}
                                {--force : Force run the command without prompt.}
                                {--password= : The new password to use for encryption.}
                                {--decrypt-iterations= : Use this iteration value to decrypt the items, overwriting what is in your vault config.}
                                {--decrypt-cipher= : Use this cipher value to decrypt the items, overwriting what is in your vault config.}
                                {--decrypt-algorithm= : Use this cipher value to decrypt the items, overwriting what is in your vault config.}
                                {--iterations= : Use this iteration value to encrypt the items, overwriting what is in your vault config.}
                                {--cipher= : Use this cipher value to encrypt the items, overwriting what is in your vault config.}
                                {--algorithm= : Use this algorithm value to encrypt the items, overwriting what is in your vault config.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Reencrypt all items with a new master password.';

    /**
     * Reencrypt all vault items with a new master password.
     */
    public function handle(): int
    {

        $failures = false;

        if (! $this->option('force') && ! $this->components->confirm('It is recommended you create a backup of your vault first before running this command, continue?')) {
            $this->exit('Aborted');
        }

        $oldConfig = (new Config)->getVaultConfig();

        foreach (['iterations', 'cipher', 'algorithm'] as $key) {
            if ($this->option("decrypt-{$key}")) {
                $oldConfig->put($key, $this->option("decrypt-{$key}"));
            }
        }

        $oldPassword = $this->option('old-password') ?: password('Enter the old password previously used for encryption', required: true);
        $vault = $this->getDriver($oldConfig->assert('driver'), password: $oldPassword)->setConfig($oldConfig);

        $decryptedItems = [];

        foreach ($vault->all() as $item) {
            $decryptedItems[] = $vault->decrypt($item['content'], $item['hash'], $item['namespace']);
        }

        $newConfig = (new Config)->getVaultConfig();

        foreach (['iterations', 'cipher', 'algorithm'] as $key) {
            if ($this->option($key)) {
                $newConfig->put($key, $this->option($key));
            }
        }

        if ($oldConfig->get('algorithm') !== $newConfig->get('algorithm') && ! $this->option('iterations')) {
            $newConfig->put('iterations', Vault::DEFAULT_ITERATIONS[$newConfig->get('algorithm')]);
        }

        $newPassword = $this->option('password') ?: password('Enter the new password for encryption', required: true);
        $confirmPassword = $this->option('password') ?: password('Confirm new password', required: true);

        if ($newPassword !== $confirmPassword) {
            $this->exit('Confirmation and password do not match.');
        }

        $vault->setPassword($newPassword)->setConfig($newConfig);

        foreach ($decryptedItems as $item) {

            $name = $item->data()['name'];

            $success = $this->runTask("Rencrypt vault item '$name'", function () use ($item, $vault) {
                return $vault->put(hash: $item->hash(), content: $vault->encrypt($item->data(), $item->hash()), namespace: $item->namespace());

            }, spinner: ! $this->app->runningUnitTests());

            if ($success === false) {
                $failures = true;
            }
        }

        $this->components->info('Reencryption complete. '.($failures ? 'Some items failed to reencrypt, restore vault.' : 'All items reencrypted successfully.'));

        // if no failures, update the config with new options if they were set.
        if (! $failures) {
            (new Config)->saveVaultConfig($vault);
        }

        return $failures ? 1 : 0;
    }
}
