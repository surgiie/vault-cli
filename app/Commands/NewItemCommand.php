<?php

namespace App\Commands;

use App\Concerns\GathersInput;
use App\Concerns\InteractsWithDrivers;
use App\Support\Config;

class NewItemCommand extends BaseCommand
{
    use GathersInput, InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:new
                                {name : The name of the vault item}
                                {--password= : The password to use during encryption of this item}
                                {--content= : The content for the item}
                                {--content-file= : Read item content from file instead of option}
                                {--key-data-file=* : Load the value for a json key from file using <key>:<file-path> format}
                                {--namespace=default : The namespace to put the vault item in}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new vault item.';

    /**
     * Create a new vault item.
     */
    public function handle(): int
    {
        $password = $this->getEncryptionPassword($config = new Config);

        $content = $this->gatherItemContent($config);

        if (! $content && $this->arbitraryOptions->isEmpty()) {
            $this->exit('Aborted, no content provided.');
        }

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver(name: $vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);
        $name = $this->argument('name');

        $success = $this->runTask("Create new vault item '$name'", function () use ($vault, $content) {

            $name = $this->argument('name');

            if ($vault->has(hash: $hash = $this->hashItem($name), namespace: $this->option('namespace'))) {
                $this->exit("Item with name '$name' already exists in the vault.");
            }

            $otherData = $this->gatherOtherItemData($this->option('key-data-file', []));

            return $vault->put(
                hash: $hash,
                content: $vault->encrypt(array_merge(['name' => $name, 'content' => $content], $otherData), $hash),
                namespace: $this->option('namespace')
            );

        }, spinner: ! $this->app->runningUnitTests());

        return $success == false ? 1 : 0;
    }
}
