<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * Cashfree's answer to a refund. A 200 carries the refund entity with its refund_status (SUCCESS,
 * PENDING, ONHOLD, CANCELLED). Anything else is an error answer {message, code, type}: a 409 means a
 * refund with this refund_id already exists, a 400, 404 or 422 means Cashfree refused the refund and
 * nothing was refunded, and every other status (401, 403, 429, 5xx) leaves the outcome to the caller.
 */
class RefundResponse extends AbstractResponse
{
    /** @param array<array-key, mixed> $data */
    public function __construct(RequestInterface $request, array $data, private readonly int $httpStatus)
    {
        parent::__construct($request, $data);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** Cashfree holds the refund: its entity came back. */
    public function isAccepted(): bool
    {
        return $this->httpStatus >= 200 && $this->httpStatus < 300 && is_array($this->data) && isset($this->data['refund_status']);
    }

    public function isSuccessful(): bool
    {
        return $this->isAccepted() && $this->data['refund_status'] === 'SUCCESS';
    }

    public function isPending(): bool
    {
        return $this->isAccepted() && in_array($this->data['refund_status'], ['PENDING', 'ONHOLD'], true);
    }

    /** Cashfree refused the refund (amount over the paid sum, order not paid, no such order): nothing was refunded. */
    public function isRefused(): bool
    {
        return in_array($this->httpStatus, [400, 404, 422], true);
    }

    /** A refund with this refund_id already exists at Cashfree: an earlier attempt reached it. */
    public function isDuplicateRefundId(): bool
    {
        return $this->httpStatus === 409;
    }

    /** @return array<array-key, mixed>|null the refund entity, when Cashfree holds the refund */
    public function getRefund(): ?array
    {
        if (! $this->isAccepted()) {
            return null;
        }

        return $this->data;
    }

    public function getTransactionReference(): ?string
    {
        if (! $this->isAccepted() || ! isset($this->data['cf_refund_id'])) {
            return null;
        }

        return (string) $this->data['cf_refund_id'];
    }

    public function getRefundStatus(): ?string
    {
        if (! $this->isAccepted()) {
            return null;
        }

        return (string) $this->data['refund_status'];
    }

    public function getMessage(): ?string
    {
        if (is_array($this->data) && isset($this->data['message'])) {
            return (string) $this->data['message'];
        }

        if ($this->isAccepted() && isset($this->data['status_description'])) {
            return (string) $this->data['status_description'];
        }

        return null;
    }

    public function getCode(): ?string
    {
        if (is_array($this->data) && isset($this->data['code'])) {
            return (string) $this->data['code'];
        }

        return null;
    }

    public function getErrorType(): ?string
    {
        if (is_array($this->data) && isset($this->data['type'])) {
            return (string) $this->data['type'];
        }

        return null;
    }
}
