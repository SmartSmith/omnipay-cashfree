<?php

namespace Omnipay\Cashfree\Message\Payout;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Response from Cashfree bank account validation.
 */
class ValidateBankAccountResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        return ($this->data['status'] ?? '') === 'SUCCESS'
            && ($this->data['data']['account_status'] ?? '') === 'VALID';
    }

    public function getAccountStatus(): ?string
    {
        return $this->data['data']['account_status'] ?? null;
    }

    public function getAccountHolderName(): ?string
    {
        return $this->data['data']['name_at_bank'] ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->data['message'] ?? $this->data['data']['account_status'] ?? null;
    }

    public function getCode(): ?string
    {
        return $this->data['subCode'] ?? $this->data['code'] ?? null;
    }
}
