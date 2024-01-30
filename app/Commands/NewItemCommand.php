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
     * Specifies the command can accept arbritrary options.
     */
    protected bool $arbitraryOptions = true;

    /**
     * The transformers for input arguments and options.
     */
    public function transformers(): array
    {
        return [
            'name' => ['trim', fn ($v) => $this->toUpperSnakeCase($v)],
            'folder' => 'trim',
            'password' => 'trim',
        ];
    }

    /**
     * The validation rules for input arguments and options.
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }

    /**
     * Create a new vault item.
     */
    public function handle(): int
    {
        $password = $this->getEncryptionPassword($config = new Config);

        $content = $this->gatherItemContent($config);

        if (! $content) {
            $this->exit('Aborted, no content provided.');
        }

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver(name: $vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        $success = $this->runTask('Store new vault item', function () use ($vault, $content) {

            $name = $this->data->get('name');

            if ($vault->has(hash: $hash = $this->hashItem($name), namespace: $this->data->get('namespace'))) {
                $this->exit("An item with the name '$name' already exists in the vault.");
            }

            $otherData = $this->gatherOtherItemData($this->data->get('key-data-file', []));

            return $vault->put(
                hash: $hash,
                data: array_merge(['name' => $name, 'content' => $content], $otherData),
                namespace: $this->data->get('namespace')
            );

        }, spinner: ! $this->app->runningUnitTests());

        return $success == false ? 1 : 0;
    }
}
