<?php

namespace Omnipay\Cashfree\Message\Payout;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Response from Cashfree Payouts V2 Create Beneficiary.
 *
 * On success (201): returns beneficiary object with beneficiary_status.
 * Statuses: VERIFIED, INITIATED, INVALID, etc.
 */
class CreateBeneficiaryResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        $status = $this->getBeneficiaryStatus();

        return in_array($status, ['VERIFIED', 'INITIATED'], true);
    }

    public function getBeneficiaryId(): ?string
    {
        return $this->data['beneficiary_id'] ?? null;
    }

    public function getBeneficiaryStatus(): ?string
    {
        return $this->data['beneficiary_status'] ?? null;
    }

    public function getAddedOn(): ?string
    {
        return $this->data['added_on'] ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->data['message'] ?? $this->data['beneficiary_status'] ?? null;
    }

    public function getCode(): ?string
    {
        return $this->data['code'] ?? $this->data['type'] ?? null;
    }
}
