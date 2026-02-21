<?php

namespace Omnipay\Cashfree\Message\Payout;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Response from Cashfree Payouts V2 Get Transfer Status.
 *
 * Terminal statuses: SUCCESS, FAILED, REVERSED.
 * Pending: PENDING.
 */
class PayoutStatusResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        return $this->getStatus() === 'SUCCESS';
    }

    public function isPending(): bool
    {
        return $this->getStatus() === 'PENDING';
    }

    public function isFailed(): bool
    {
        return $this->getStatus() === 'FAILED';
    }

    public function isReversed(): bool
    {
        return $this->getStatus() === 'REVERSED';
    }

    public function getStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function getStatusCode(): ?string
    {
        return $this->data['status_code'] ?? null;
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['cf_transfer_id'] ?? null;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['transfer_id'] ?? null;
    }

    public function getUtr(): ?string
    {
        return $this->data['utr'] ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->data['status_description']
            ?? $this->data['message']
            ?? $this->data['status']
            ?? null;
    }

    public function getCode(): ?string
    {
        return $this->data['status_code'] ?? $this->data['code'] ?? null;
    }

    public function getTransferAmount(): ?float
    {
        return isset($this->data['transfer_amount']) ? (float) $this->data['transfer_amount'] : null;
    }

    public function getBeneficiaryId(): ?string
    {
        return $this->data['beneficiary_id'] ?? null;
    }
}
