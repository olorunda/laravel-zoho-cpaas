<?php

namespace Olorunda\ZohoCpaas\Exceptions;

use Exception;
use Throwable;

class ZohoCpaasException extends Exception
{
    protected ?string $errorCode;
    protected array $errorDetails;
    protected ?int $httpStatusCode;

    public function __construct(
        string $message = '',
        ?string $errorCode = null,
        int $httpStatusCode = 0,
        array $errorDetails = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatusCode, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatusCode = $httpStatusCode;
        $this->errorDetails = $errorDetails;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public static function fromResponse(int $statusCode, array $body): static
    {
        $error = $body['error'] ?? [];
        $message = $error['message'] ?? $body['message'] ?? 'An unknown Zoho CPaaS API error occurred.';
        $code = $error['code'] ?? null;
        $details = $error['details'] ?? $error ?? [];

        if ($statusCode === 401 || $statusCode === 403) {
            return new ZohoCpaasAuthenticationException($message, $code, $statusCode, $details);
        }

        if ($statusCode === 429) {
            return new ZohoCpaasRateLimitException($message, $code, $statusCode, $details);
        }

        if ($statusCode === 400 || $statusCode === 422) {
            return new ZohoCpaasValidationException($message, $code, $statusCode, $details);
        }

        return new static($message, $code, $statusCode, $details);
    }
}
