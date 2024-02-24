<?php

namespace App\Commands;

use App\Exceptions\ExitException;
use App\Support\CommandOptionsParser;
use Closure;
use ErrorException;
use Illuminate\Console\View\Components\Factory as ConsoleViewFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Prompts\Spinner;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class BaseCommand extends Command
{
    /**
     * The options that are not defined on the command.
     */
    protected Collection $arbitraryOptions;

    /**
     * The command argv tokens.
     */
    protected array $commandTokens = [];

    /**
     * Constuct a new Command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->arbitraryOptions = collect();

        // Ignore validation errors for arbitrary options support.
        $this->ignoreValidationErrors();
    }

    /**
     * Check if an option was passed in a command call.
     */
    protected function optionWasPassed(string $name): bool
    {
        $name = ltrim($name, '--');

        $matches  = preg_grep ("/--$name(=)?(.*)/i", $this->commandTokens);

        return ! empty($matches);
    }

    /**
     * Throw an exception to exit the command.
     */
    protected function exit(string $error = '', int $code = 1, string $level = 'error'): void
    {
        throw new ExitException($error, $code, $level);
    }

    /**
     * Run a long running task with a spinner.
     *
     * @return bool|null
     */
    public function runTask(string $title = '', ?Closure $task = null, string $finishedText = '', bool $spinner = false)
    {
        $finishedText = $finishedText ?: $title;

        if ($spinner) {
            $result = (new Spinner($title))->spin(
                $task,
                $title,
            );
        } else {
            $result = invade((new Spinner($title)))->renderStatically($task);
        }

        $this->output->writeln(
            "  $finishedText: ".($result !== false ? '<info>Succeeded</info>' : '<error>Failed</error>')
        );

        return $result;
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->components = $this->laravel->make(ConsoleViewFactory::class, ['output' => $this->output]);

        try {
            $status = 0;
            $method = method_exists($this, 'handle') ? 'handle' : '__invoke';
            $status = (int) $this->laravel->call([$this, $method]);
        } catch (ExitException $e) {
            $level = $e->getLevel();

            $message = $e->getMessage();

            if ($message) {
                $this->components->$level($message);
            }

            $status = $e->getStatus();
        }

        return $status;
    }

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
     * Initialize the command input/ouput objects.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // parse arbitrary options for variable data.
        $this->commandTokens = $input instanceof ArrayInput ? invade($input)->parameters : invade($input)->tokens;

        $parser = new CommandOptionsParser($this->commandTokens);

        $definition = $this->getDefinition();

        foreach ($parser->parse() as $name => $data) {
            if (! $definition->hasOption($name)) {
                $this->arbitraryOptions->put($name, $data['value']);
                $this->addOption($name, mode: $data['mode']);
            }
        }
        //rebind input definition
        $input->bind($definition);
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
