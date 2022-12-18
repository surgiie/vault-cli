<?php

namespace App\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Surgiie\Console\Command as ConsoleCommand;

abstract class BaseCommand extends ConsoleCommand
{
    /**Create a path relavent to the .vault directory.*/
    protected function vaultPath(string $path = "")
    {
        $base = rtrim(getenv("HOME"), "/");

        $path = trim($path, "/");

        return rtrim($base . "/.vault/" . $path, '/');
    }
    /**Derive encryption key.*/
    protected function deriveKey(string $password)
    {
        $salt = $this->generateSaltFromPassword($password);
        
        $encryptionKey = hash_pbkdf2('sha256', $password,  $salt, iterations: 100000, length: 32);

        return $encryptionKey;
    }

     /**
     * Generate a substring sha1 from password to use as a salt.
     */
    protected function generateSaltFromPassword($password)
    {
        $password = strrev($password);

        $num = strlen($password);
        $num = $num / 2;

        $first_half = strrev(substr($password, 0, $num));
        $second_half = strrev(substr($password, $num));

        // limit the sha1 to 32 chars which is a recommended salt length.
        return substr(sha1(strrev($second_half) . strrev($first_half)), 0, 32);
    }

    /**Normalize item name to snake & uppercase.*/
    protected function normalizeItemName(string $name)
    {
        $name = str_replace(["-", "_"], [" ", ""], mb_strtolower($name));

        return mb_strtoupper(Str::snake($name));
    }

    /**Exec a command via string.*/
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
     * Copy the value to clipboard.
     */
    protected function copyToClipboard(string $value): void
    {
        $value = escapeshellarg($value);

        if (str_contains(file_get_contents("/proc/version"), 'microsoft')) {
            // https://askubuntu.com/questions/1035903/how-can-i-get-around-using-xclip-in-the-linux-subsystem-on-win-10
            $this->exec("echo $value | clip.exe");
            return;
        }

        try {
            $this->exec("echo $value | xclip -sel clip");
        } catch (\Exception $e) {
            $this->exit($e->getMessage());
        }
    }
    /**Get the password for encryption.*/
    protected function getPassword()
    {
        $env = getenv("VAULT_CLI_PASSWORD");
        if ($env && !$this->data->get('password') && !$this->data->get('password-file')) {
            return $env;
        }
        return $this->getSecretInputFromFileOrOption('password');
    }


    /**Get a input from file, option or input. Assumes options for file is defined in commands.*/
    protected function getSecretInputFromFileOrOption(string $name)
    {
        $fromFile = $this->data->get("$name-file");
        $secret = $this->data->get($name);

        if ($fromFile && $secret) {
            $this->exit("Conflicted options given --$name and --$name-file. Only one is allowed.");
        }

        if ($fromFile) {
            if (!is_file($fromFile)) {
                $this->exit("File from --$name-file not found: $fromFile");
            }
            return trim(file_get_contents($fromFile));
        }

        return $this->getOrAskForInput($name, confirm: true, secret: true,  rules: ['required']);
    }

    /**Ensure the home vault directory exists. */
    protected function ensureVaultDirExists()
    {
        return @mkdir($this->vaultPath());
    }
}
