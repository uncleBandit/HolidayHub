<?php

namespace App\Modules\Payments\Domain\ValueObjects;

use JsonSerializable;

class PaymentResult implements JsonSerializable
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status, // success, pending, failed, refunded, error
        public readonly ?string $transactionId = null,
        public readonly ?string $message = null,
        public readonly ?string $errorCode = null,
        public readonly array $raw = []
    ) {}

    public static function success(string $transactionId, string $message = 'Payment successful', array $raw = []): self
    {
        return new self(true, 'success', $transactionId, $message, null, $raw);
    }

    public static function pending(string $transactionId, string $message = 'Payment pending', array $raw = []): self
    {
        return new self(false, 'pending', $transactionId, $message, null, $raw);
    }

    public static function failure(string $message, ?string $errorCode = null, array $raw = []): self
    {
        return new self(false, 'failed', null, $message, $errorCode, $raw);
    }

    public static function refunded(string $transactionId, string $message = 'Payment refunded', array $raw = []): self
    {
        return new self(true, 'refunded', $transactionId, $message, null, $raw);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'raw' => $this->raw,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
