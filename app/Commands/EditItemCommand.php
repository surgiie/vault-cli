<?php

namespace App\Commands;

use App\Concerns\GathersInput;
use App\Concerns\InteractsWithDrivers;
use App\Support\Config;

use function Laravel\Prompts\text;

class EditItemCommand extends BaseCommand
{
    use GathersInput, InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:edit
                                {name? : The name of the vault item to edit}
                                {--password= : The password to use during encryption of this item}
                                {--content= : The content for the item}
                                {--content-file= : Read item content from file instead of option}
                                {--key-data-file=* : Load the value for a json key from file using <key>:<file-path> format}
                                {--json : When passed, a tmp file will be opened in terminal editor, where you can edit the full json instead of just content}
                                {--namespace=default : Folder to put the vault item in}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Edit an existing vault item.';

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
            'name' => 'trim',
            'namespace' => 'trim',
            'password' => 'trim',
        ];
    }

    /**
     * Update vault item content or json.
     */
    public function handle(): int
    {
        $name = $this->data->get('name') ?: text('Enter the name of the vault item to get', required: true);
        $password = $this->getEncryptionPassword($config = new Config);

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        $currentItemData = $vault->get($hash = $this->hashItem($this->toUpperSnakeCase($name)), $this->arbitraryData, $this->data->get('namespace'))->data();

        $otherData = [];
        $content = false;

        if ($this->data->get('json')) {
            // ensure that item name cannot be updated.
            unset($currentItemData['name']);

            $currentItemData = json_decode(
                json: $this->getContentFromTmpFile($config, currentContent: json_encode($currentItemData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                associative: true
            );

            if (is_null($currentItemData)) {
                $this->exit('Could not update json item, bad json data. Try again');
            }
            // ensure we remove name, if someone got funny ideas to change a name.
            unset($currentItemData['name']);
        } else {
            $content = $this->gatherItemContent($config, prompt: $this->arbitraryData->isEmpty(), currentContent: $currentItemData['content']);

            if ($this->arbitraryData->isEmpty() && ! $content) {
                $this->exit('No update data given, nothing to update.', code: 1, level: 'warn');
            }

            $currentItemData['content'] = $content;
        }

        $otherData = $this->gatherOtherItemData($this->data->get('key-data-file', []));

        $success = $this->runTask("Update vault item '$name'", function () use ($name, $hash, $vault, $currentItemData, $otherData) {
            return $vault->put(
                hash: $hash,
                data: array_merge($currentItemData, $otherData, ['name' => $this->toUpperSnakeCase($name)]),
                namespace: $this->data->get('namespace'),
            );
        });

        return $success === false ? 1 : 0;
    }
}
