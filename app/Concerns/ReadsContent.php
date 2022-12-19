<?php

namespace App\Concerns;

trait ReadsContent
{
    /**Load other data from arbitrary options/files.*/
    protected function loadOtherDataForItemCrud(array $keyFiles = []): array
    {
        $otherData = $this->arbitraryData->all();

        foreach ($keyFiles as $value) {
            list($key, $path) = $this->parseKeyValueOption($value, 'key-data-file');
            if (!is_file($path)) {
                $this->exit("The key data file '$path' does not exist");
            }
            $otherData[$key] = file_get_contents($path);
        }

        return $otherData;
    }
    
    /**Get the content for the vault item.*/
    protected function getContent(): string
    {
        $content = $this->data->get('content');
        $fromFile = $this->data->get("content-file");

        if ($fromFile && $content) {
            $this->exit("Conflicted options given --content and --content-file. Only one is allowed.");
        }

        if ($fromFile) {
            if (!is_file($fromFile)) {
                $this->exit("File from --content-file not found: $fromFile");
            }
            return trim(file_get_contents($fromFile));
        }

        if ($content) {
            return $content;
        }

        $editor = $this->data->get("editor");

        if ($this->components->confirm("No content passed for item, open a tmp file to add content? Will use $editor as set by --editor option.")) {
            $handle = tmpfile();

            $meta = stream_get_meta_data($handle);

            fwrite($handle, "");

            $process = new Process([$editor, $meta['uri']]);

            $process->setTty(true);
            $process->mustRun();

            return file_get_contents($meta['uri']);
        }

        $this->exit("No content given for item.");
    }
}
