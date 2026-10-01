<?php

namespace Olorunda\ZohoCpaas\DTO;

use InvalidArgumentException;

class InlineImage
{
    protected string $cid;
    protected string $content; // Base64 encoded
    protected string $name;
    protected ?string $mimeType = null;

    public function __construct(string $cid, string $name, string $content, ?string $mimeType = null)
    {
        $this->cid = $cid;
        $this->name = $name;
        $this->content = $content;
        $this->mimeType = $mimeType;
    }

    public static function fromPath(string $filePath, string $cid, ?string $name = null, ?string $mimeType = null): self
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Inline image not found at path: [{$filePath}]");
        }

        $data = file_get_contents($filePath);
        $fileName = $name ?: basename($filePath);
        $detectedMime = $mimeType ?: (mime_content_type($filePath) ?: 'image/jpeg');

        return new self($cid, $fileName, base64_encode($data), $detectedMime);
    }

    public static function fromData(string $data, string $cid, string $name, ?string $mimeType = null): self
    {
        return new self($cid, $name, base64_encode($data), $mimeType ?: 'image/jpeg');
    }

    public static function fromBase64(string $base64, string $cid, string $name, ?string $mimeType = null): self
    {
        return new self($cid, $name, $base64, $mimeType ?: 'image/jpeg');
    }

    public function getCid(): string
    {
        return $this->cid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function toArray(): array
    {
        $data = [
            'cid' => $this->cid,
            'name' => $this->name,
            'content' => $this->content,
        ];

        if ($this->mimeType !== null) {
            $data['mime_type'] = $this->mimeType;
        }

        return $data;
    }
}
