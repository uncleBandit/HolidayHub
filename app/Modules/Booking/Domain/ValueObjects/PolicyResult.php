<?php

namespace App\Modules\Booking\Domain\ValueObjects;

use JsonSerializable;

class PolicyResult implements JsonSerializable
{
    /**
     * Create a new policy result.
     *
     * @param  bool  $allowed  Whether the action is permitted
     * @param  string  $message  Human-readable message for display
     * @param  string|null  $code  Machine-friendly policy code (e.g., "CUTOFF_EXCEEDED")
     * @param  array  $context  Extra metadata (cutoff_hours, blackout_range, etc.)
     */
    public function __construct(
        public readonly bool $allowed,
        public readonly string $message,
        public readonly ?string $code = null,
        public readonly array $context = []
    ) {}

    /**
     * Factory for allowed result.
     */
    public static function allow(string $message, ?string $code = null, array $context = []): self
    {
        return new self(true, $message, $code, $context);
    }

    /**
     * Factory for denied result.
     */
    public static function deny(string $message, ?string $code = null, array $context = []): self
    {
        return new self(false, $message, $code, $context);
    }

    /**
     * Convert to array (useful for APIs).
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'message' => $this->message,
            'code' => $this->code,
            'context' => $this->context,
        ];
    }

    /**
     * Support for JSON encoding.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation (useful for logs).
     */
    public function __toString(): string
    {
        return sprintf(
            '[%s] %s',
            $this->allowed ? 'ALLOWED' : 'DENIED',
            $this->message
        );
    }
}
