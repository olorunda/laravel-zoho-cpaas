<?php

namespace Olorunda\ZohoCpaas\Storage;

use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;

class ArrayTokenStore implements TokenStoreInterface
{
    protected ?string $token = null;
    protected ?int $expiresAt = null;

    public function get(): ?string
    {
        if ($this->expiresAt !== null && time() >= $this->expiresAt) {
            $this->token = null;
            $this->expiresAt = null;
        }

        return $this->token;
    }

    public function set(string $token, int $ttl = 3500): void
    {
        $this->token = $token;
        $this->expiresAt = time() + $ttl;
    }

    public function forget(): void
    {
        $this->token = null;
        $this->expiresAt = null;
    }
}
