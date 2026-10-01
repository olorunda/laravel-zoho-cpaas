<?php

namespace Olorunda\ZohoCpaas\Contracts;

interface TokenStoreInterface
{
    /**
     * Get the stored access token.
     */
    public function get(): ?string;

    /**
     * Store the access token with an optional TTL in seconds.
     */
    public function set(string $token, int $ttl = 3500): void;

    /**
     * Clear the stored token.
     */
    public function forget(): void;
}
