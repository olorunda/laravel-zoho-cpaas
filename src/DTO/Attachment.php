<?php

namespace Olorunda\ZohoCpaas\DTO;

use InvalidArgumentException;

class Attachment
{
    protected ?string $content = null; // Base64 encoded
    protected string $name;
    protected ?string $mimeType = null;
    protected ?string $fileCacheKey = null;

    public function __construct(string $name, ?string $content = null, ?string $mimeType = null, ?string $fileCacheKey = null)
    {
        $this->name = $name;
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->fileCacheKey = $fileCacheKey;
    }

    public static function fromPath(string $filePath, ?string $name = null, ?string $mimeType = null): self
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found at path: [{$filePath}]");
        }

        $data = file_get_contents($filePath);
        $fileName = $name ?: basename($filePath);
        $detectedMime = $mimeType ?: (mime_content_type($filePath) ?: 'application/octet-stream');

        return new self($fileName, base64_encode($data), $detectedMime);
    }

    public static function fromData(string $data, string $name, ?string $mimeType = null): self
    {
        return new self($name, base64_encode($data), $mimeType ?: 'application/octet-stream');
    }

    public static function fromBase64(string $base64, string $name, ?string $mimeType = null): self
    {
        return new self($name, $base64, $mimeType ?: 'application/octet-stream');
    }

    public static function fromCacheKey(string $fileCacheKey, string $name, ?string $mimeType = null): self
    {
        return new self($name, null, $mimeType, $fileCacheKey);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getFileCacheKey(): ?string
    {
        return $this->fileCacheKey;
    }

    public function toArray(): array
    {
        $data = ['name' => $this->name];

        if ($this->content !== null) {
            $data['content'] = $this->content;
        }

        if ($this->mimeType !== null) {
            $data['mime_type'] = $this->mimeType;
        }

        if ($this->fileCacheKey !== null) {
            $data['file_cache_key'] = $this->fileCacheKey;
        }

        return $data;
    }
}
