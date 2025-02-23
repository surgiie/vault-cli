<?php

namespace App\Support;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class CommandOptionsParser
{
    /**
     * The options being parsed.
     */
    protected array $options = [];

    /**
     * Construct new CommandOptionsParser instance.
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * Set the options to parse.
     */
    public function setOptions(array $options): static
    {
        $this->options = array_filter($options);

        return $this;
    }

    /**
     * Tests use ArrayInput objects, so we need to handle the tokens differently.
     *
     * @return void
     */
    protected function parseTokensForTests()
    {
        $options = [];
        foreach ($this->options as $token => $v) {
            if (str_starts_with($token, '--')) {
                if ($v == false) {
                    continue;
                }
                if (is_array($v)) {
                    foreach ($v as $item) {
                        $options[] = "$token=$item";

                    }
                } else {
                    $token = $token.($v ? "=$v" : '');
                    $options[] = $token;
                }
            }
        }

        return $options;
    }

    /**
     * Parse the set options.
     */
    public function parse(): array
    {
        $options = [];
        $iterable = $this->options;

        if (app()->runningUnitTests()) {
            $iterable = $this->parseTokensForTests();
        }

        foreach ($iterable as $token) {

            preg_match('/--([^=]+)(=)?(.*)/', $token, $match);

            if (! $match) {
                continue;
            }

            $name = $match[1];
            $equals = $match[2] ?? false;
            $value = $match[3] ?? false;

            $optionExists = array_key_exists($name, $options);

            if ($optionExists && ($value || $equals)) {
                $options[$name] = [
                    'mode' => InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'value' => $options[$name]['value'] ?? [],
                ];
                $options[$name]['value'] = Arr::wrap($options[$name]['value']);
                $options[$name]['value'][] = $value;
            } elseif ($value) {
                $options[$name] = [
                    'mode' => InputOption::VALUE_REQUIRED,
                    'value' => $value,
                ];
            } elseif (! $optionExists) {
                $options[$name] = [
                    'mode' => ($value == '' && $equals) ? InputOption::VALUE_OPTIONAL : InputOption::VALUE_NONE,
                    'value' => ($value == '' && $equals) ? '' : true,
                ];
            } else {
                throw new InvalidArgumentException("The '$name' option has already been provided.");
            }
        }

        return $options;
    }
}
