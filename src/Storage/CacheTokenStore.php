<?php

namespace Olorunda\ZohoCpaas\Storage;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;

class CacheTokenStore implements TokenStoreInterface
{
    protected CacheRepository $cache;
    protected string $key;

    public function __construct(CacheRepository $cache, string $key = 'zoho_cpaas_access_token')
    {
        $this->cache = $cache;
        $this->key = $key;
    }

    public function get(): ?string
    {
        return $this->cache->get($this->key);
    }

    public function set(string $token, int $ttl = 3500): void
    {
        $this->cache->put($this->key, $token, $ttl);
    }

    public function forget(): void
    {
        $this->cache->forget($this->key);
    }
}
