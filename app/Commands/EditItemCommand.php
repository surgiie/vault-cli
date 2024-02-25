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
     * Update vault item content or json.
     */
    public function handle(): int
    {

        $otherData = [];
        $content = false;
        $config = new Config;

        $name = $this->argument('name') ?: text('Enter the name of the vault item to get', required: true);
        $hash = $this->hashItem($name);

        $vaultConfig = $config->getVaultConfig();

        $password = $this->getEncryptionPassword($config);

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        if (! $vault->has($hash, $this->option('namespace'))) {
            $this->exit("Item with name '$name' does not exist in the vault.");
        }

        $currentData = $vault->get($hash, $this->arbitraryOptions, $this->option('namespace'))->data();

        if ($this->option('json')) {
            // ensure that item name cannot be updated.
            unset($currentData['name']);

            $currentData = json_decode(
                json: $this->getContentFromTmpFile($config, currentContent: json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                associative: true
            );

            if (is_null($currentData)) {
                $this->exit('Could not update json item, bad json data. Try again');
            }
            // ensure we remove name, if someone got funny ideas to change a name.
            unset($currentData['name']);
        } else {
            $content = $this->gatherItemContent($config, prompt: $this->arbitraryOptions->isEmpty(), currentContent: $currentData['content']);

            if ($this->arbitraryOptions->isEmpty() && ! $content) {
                $this->exit('No update data given, nothing to update.', code: 1, level: 'warn');
            }

            $currentData['content'] = $content;
        }

        $otherData = $this->gatherOtherItemData($this->option('key-data-file', []));

        $success = $this->runTask("Update vault item '$name'", function () use ($name, $hash, $vault, $currentData, $otherData) {
            return $vault->put(
                hash: $hash,
                data: array_merge($currentData, $otherData, ['name' => $name]),
                namespace: $this->option('namespace'),
            );
        },  spinner: ! $this->app->runningUnitTests());

        return $success === false ? 1 : 0;
    }
}
