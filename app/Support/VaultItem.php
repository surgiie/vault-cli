<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class VaultItem implements Arrayable, JsonSerializable
{
    /**
     * The vault item's name.
     */
    protected string $name;

    /**
     * The vault item's namespace.
     */
    protected string $namespace;

    /**
     * The vault item's hash.
     */
    protected string $hash;

    /**
     * The vault item's json data.
     */
    protected array $data;

    public function __construct(string $name, string $namespace, string $hash, array $data)
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->hash = $hash;
        $this->data = $data;
    }

    /**
     * Get the vault item's namespace.
     */
    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get the vault item's name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the vault item's hash.
     */
    public function hash(): string
    {
        return $this->hash;
    }

    /**
     * Get the vault item's json serializable format.
     */
    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    /**
     * Get the main content field of the vault item.
     */
    public function content(): string
    {
        return $this->data('content');
    }

    /**
     * Get the vault item's json data.
     */
    public function data($key = null)
    {
        if (! is_null($key)) {
            return data_get($this->data, $key);
        }

        return $this->data;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'namespace' => $this->namespace,
            'hash' => $this->hash,
            'data' => $this->data,
        ];
    }
}
