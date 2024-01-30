<?php

namespace App\Commands;

use Closure;
use ErrorException;
use Illuminate\Support\Str;
use Surgiie\Console\Command as ConsoleCommand;
use Symfony\Component\Process\Process;

abstract class BaseCommand extends ConsoleCommand
{
    /**
     * Exec a command via string.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function exec(string $cmd, array $placeholders = [])
    {
        $process = Process::fromShellCommandline($cmd);
        $process->setTty(true);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->mustRun(null, $placeholders);

        return $process;
    }

    /**
     * Copy the given value to clipboard.
     *
     * @return void
     */
    public function copyToClipboard(string $value, ?Closure $onFail = null)
    {
        $value = escapeshellarg($value);

        $envCommand = getenv('VAULT_CLI_COPY_COMMAND');

        if ($envCommand) {
            $this->exec(str_replace(':value:', $value, $envCommand));

            return;
        }

        try {
            if (is_file('/proc/version') && str_contains(file_get_contents('/proc/version'), 'microsoft')) {
                // https://askubuntu.com/questions/1035903/how-can-i-get-around-using-xclip-in-the-linux-subsystem-on-win-10
                $this->exec("echo $value | clip.exe");

                return;
            }
            $this->exec("echo $value | xclip -sel clip");
        } catch (\Exception $e) {
            if (is_callable($onFail)) {
                return $onFail($e);
            }

            throw $e;
        }
    }

    /**
     * Normalize string to snake & uppercase.
     */
    protected function toUpperSnakeCase(string $name): string
    {
        $name = str_replace(['-', '_'], [' ', ' '], mb_strtolower($name));

        return mb_strtoupper(Str::snake($name));
    }

    /**
     * Compute SHA1 hash from a given string.
     *
     * @param  string  $input
     */
    protected function hashItem(string $name): string
    {
        return sha1($name);
    }

    /**
     * Parse key value options strings in <key>:</value> format.
     */
    protected function parseKeyValueOption(string $param, string $optionName, ?Closure $onParseException = null): array
    {
        try {
            [$key, $value] = explode(':', $param, limit: 2);
        } catch (ErrorException $e) {
            if (! is_callable($onParseException)) {
                $this->exit(
                    "Could not parse key value option for $optionName, value given: $param, expected <key>:<value> format."
                );
            }

            return $onParseException($e);
        }

        return [$key, $value];
    }
}
