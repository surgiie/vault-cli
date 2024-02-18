<?php

namespace App\Commands;

use App\Concerns\InteractsWithDrivers;
use App\Support\Config;

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
                                {--new-password= : The new password to use for encryption.}
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
        if (! $this->option('force') && ! $this->components->confirm('It is recommended you create a backup of your vault first before running this command, continue?')) {
            $this->exit('Aborted');
        }

        $oldConfig = ($config = new Config)->getVaultConfig();

        foreach (['iterations', 'cipher', 'algorithm'] as $key) {
            if ($this->option("decrypt-{$key}")) {
                $oldConfig->put($key, $this->option("decrypt-{$key}"));
            }
        }

        $oldPassword = $this->option('old-password') ?: password('Enter the old password previously used for encryption', required: true);
        $oldVault = $this->getDriver($oldConfig->assert('driver'), password: $oldPassword)->setConfig($oldConfig);

        $newConfig = $config->getVaultConfig();

        foreach (['iterations', 'cipher', 'algorithm'] as $key) {
            if ($this->option($key)) {
                $newConfig->put($key, $this->option($key));
            }
        }

        $newPassword = $this->option('new-password') ?: password('Enter the new password for encryption', required: true);
        $confirmPassword = $this->option('new-password') ?: password('Confirm new password', required: true);

        if ($newPassword !== $confirmPassword) {
            $this->exit('Confirmation and password do not match.');
        }

        $newVault = $this->getDriver($newConfig->assert('driver'), password: $newPassword)->setConfig($newConfig);

        $failures = false;

        foreach ($oldVault->all() as $item) {

            $name = $item->data()['name'];

            $success = $this->runTask("Rencrypt vault item '$name'", function () use ($item, $newVault) {
                return $newVault->put(hash: $item->hash(), data: $item->data(), namespace: $item->namespace());
            }, spinner: ! $this->app->runningUnitTests());

            if ($success === false) {
                $failures = true;
            }
        }

        $this->components->info('Reencryption complete. '.($failures ? 'Some items failed to reencrypt, restore vault.' : 'All items reencrypted successfully.'));

        // if no failures, update the config with new options if they were set.
        if (! $failures) {
            $config->saveVaultConfig($newVault);
        }

        return $failures ? 1 : 0;
    }
}
