<?php

use Symfony\Component\Process\Process;

if (! function_exists('vault_path')) {
    /**Create a path relavent to the .vault directory.*/
    function vault_path(string $path = '', ?string $basePath = '')
    {
        if (is_null($basePath)) {
            $basePath = '';
        }

        if ($basePath) {
            $base = rtrim($basePath, '/').'/';
        } else {
            $user = get_current_user();
            $base = rtrim("/home/$user", '/').'/.vault/';
        }

        $path = trim($path, '/');

        return rtrim($base.$path, '/');
    }
}

if (! function_exists('is_sudo')) {
    /**Check if the current user is sudo.*/
    function is_sudo()
    {
        return posix_getuid() === 0;
    }
}

if (! function_exists('exec_command')) {
    /**Exec a command via string.*/
    function exec_command(string $cmd, array $placeholders = [])
    {
        $process = Process::fromShellCommandline($cmd);
        $process->setTty(true);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->mustRun(null, $placeholders);

        return $process;
    }
}

if (! function_exists('copy_to_clipboard')) {
    /**
     * Copy the given value to clipboard.
     */
    function copy_to_clipboard(string $value, ?Closure $onFail = null)
    {
        $value = escapeshellarg($value);

        if (is_file('/proc/version') && str_contains(file_get_contents('/proc/version'), 'microsoft')) {
            // https://askubuntu.com/questions/1035903/how-can-i-get-around-using-xclip-in-the-linux-subsystem-on-win-10
            exec_command("echo $value | clip.exe");

            return;
        }

        try {
            exec_command("echo $value | xclip -sel clip");
        } catch (\Exception $e) {
            if (is_callable($onFail)) {
                return $onFail($e);
            }

            throw $e;
        }
    }
}
