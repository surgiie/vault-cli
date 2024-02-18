<?php

namespace App\Concerns;

use App\Support\Config;

use function Laravel\Prompts\password;

trait GathersInput
{
    /**
     * Gather other data for item being saved.
     */
    protected function gatherOtherItemData(array $keyFiles = []): array
    {
        $otherData = $this->arbitraryOptions->all();

        foreach ($keyFiles as $value) {
            [$key, $path] = $this->parseKeyValueOption($value, 'key-data-file');
            if (! is_file($path)) {
                $this->exit("The key data file '$path' does not exist");
            }
            $otherData[$key] = file_get_contents($path);
        }

        return $otherData;
    }

    /**
     * Get the content for item by option, file, terminal editor, or prompt.
     *
     * @param  string  $currentContent.
     */
    protected function gatherItemContent(Config $config, bool $prompt = true, string $currentContent = ''): ?string
    {
        $content = $this->option('content');
        $fromFile = $this->option('content-file');

        // only one option is allowed.
        if ($fromFile && $content) {
            $this->exit('Conflicting options given --content and --content-file. Only one is allowed.');
        }

        // load content from file.
        if ($fromFile) {
            if (! is_file($fromFile)) {
                $this->exit("File from --content-file not found: $fromFile");
            }

            return file_get_contents($fromFile);
        }

        // otherwise if it was passed as an option, return that.
        if ($content) {
            return $content;
        }

        // last resort is ask to open a tmp file.
        if ($prompt && $this->components->confirm('No content passed for item, use a tmp file?')) {
            return $this->getContentFromTmpFile($config, $currentContent);
        }

        return $currentContent;
    }

    /**
     * Gather content from a tmp file using a terminal editor.
     */
    protected function getContentFromTmpFile(Config $config, string $currentContent = ''): string
    {
        $handle = tmpfile();

        $meta = stream_get_meta_data($handle);

        fwrite($handle, $currentContent);

        $uri = $meta['uri'];

        $editor = $config->get('terminal-editor', 'vim');

        $this->exec("$editor $uri");

        $contents = file_get_contents($uri);

        if (str_ends_with($contents, PHP_EOL)) {
            $contents = substr($contents, 0, -1);
        }

        return $contents;
    }

    /**
     * Get the password for encryption.
     */
    public function getEncryptionPassword(Config $config): string
    {
        if ($this->option('password')) {
            return $this->option('password');
        }

        $name = $config->assert('use-vault');

        $name = $this->toUpperSnakeCase($name ?? '');

        $env = getenv("VAULT_CLI_{$name}_PASSWORD");

        if (! $env) {
            $env = getenv('VAULT_CLI_PASSWORD');
        }

        if ($env && is_null($this->option('password'))) {
            return $env;
        }

        return password(
            label: 'Enter your master password:',
            required: true
        );
    }
}
