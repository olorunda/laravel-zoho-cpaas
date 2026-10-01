<?php

namespace Olorunda\ZohoCpaas\DTO;

use InvalidArgumentException;

class EmailAddress
{
    protected string $address;
    protected ?string $name;

    public function __construct(string $address, ?string $name = null)
    {
        $filtered = filter_var(trim($address), FILTER_VALIDATE_EMAIL);
        if ($filtered === false) {
            throw new InvalidArgumentException("Invalid email address: [{$address}]");
        }

        $this->address = $filtered;
        $this->name = $name !== null ? trim($name) : null;
    }

    public static function make(string|array|self $address, ?string $name = null): self
    {
        if ($address instanceof self) {
            return $address;
        }

        if (is_array($address)) {
            $addr = $address['address'] ?? $address['email'] ?? $address[0] ?? '';
            $nm = $address['name'] ?? $address[1] ?? null;
            return new self($addr, $nm);
        }

        return new self($address, $name);
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Format for "from" or "reply_to": {"address": "...", "name": "..."}
     */
    public function toArray(): array
    {
        $data = ['address' => $this->address];
        if ($this->name !== null && $this->name !== '') {
            $data['name'] = $this->name;
        }

        return $data;
    }

    /**
     * Format for "to", "cc", "bcc": {"email_address": {"address": "...", "name": "..."}}
     */
    public function toRecipientArray(): array
    {
        return [
            'email_address' => $this->toArray(),
        ];
    }
}
