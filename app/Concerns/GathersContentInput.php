<?php

namespace App\Concerns;

use Symfony\Component\Process\Process;

trait GathersContentInput
{
    /**
     * Gather other data from arbitrary option and files for vault item create/update.
     *
     * @param array $keyFiles
     * @return array
     */
    protected function gatherOtherItemData(array $keyFiles = []): array
    {
        $otherData = $this->arbitraryData->all();

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
     * Get the content for storing/updating vault item from one of many methods.
     *
     * @param boolean $prompt
     * @param string $existingContent
     * @return string|null
     */
    protected function gatherInputForItemContent(bool $prompt = true, string $existingContent = ''): string|null
    {
        $content = $this->data->get('content');
        $fromFile = $this->data->get('content-file');

        // only one option is allowed.
        if ($fromFile && $content) {
            $this->exit('Conflicted options given --content and --content-file. Only one is allowed.');
        }

        // load from file.
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
        $confirmText = "No content passed for item, open a tmp file to add content?";

        if($existingContent){
            $confirmText = "No content passed for item, edit item content in a tmp file?";
        }

        if ($prompt && $this->components->confirm($confirmText)) {
            $handle = tmpfile();

            $meta = stream_get_meta_data($handle);

            fwrite($handle, $existingContent);

            $process = new Process(["vim", $meta['uri']]);

            $process->setTty(true);
            $process->setIdleTimeout(null);
            $process->setTimeout(null);
            $process->mustRun();

            return file_get_contents($meta['uri']);
        }

        return null;
    }
}
