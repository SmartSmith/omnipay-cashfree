<?php

namespace Omnipay\Cashfree\Message\Payout;

use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;

/**
 * Base class for Cashfree Payouts API V2 requests.
 *
 * Cashfree Payouts uses a separate base URL and API version from the PG (Payment Gateway) API.
 * Auth is via x-client-id / x-client-secret headers (same approach as PG, but separate credentials).
 *
 * @see https://www.cashfree.com/docs/api-reference/payouts/overview
 */
abstract class AbstractPayoutRequest extends OmnipayAbstractRequest
{
    protected const API_VERSION = '2024-01-01';
    protected const SANDBOX_ENDPOINT = 'https://sandbox.cashfree.com/payout';
    protected const PRODUCTION_ENDPOINT = 'https://api.cashfree.com/payout';

    public function getPayoutClientId(): ?string
    {
        return $this->getParameter('payoutClientId');
    }

    public function setPayoutClientId(?string $value): self
    {
        return $this->setParameter('payoutClientId', $value);
    }

    public function getPayoutClientSecret(): ?string
    {
        return $this->getParameter('payoutClientSecret');
    }

    public function setPayoutClientSecret(?string $value): self
    {
        return $this->setParameter('payoutClientSecret', $value);
    }

    protected function getBaseEndpoint(): string
    {
        return $this->getTestMode() ? self::SANDBOX_ENDPOINT : self::PRODUCTION_ENDPOINT;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'x-client-id' => $this->getPayoutClientId(),
            'x-client-secret' => $this->getPayoutClientSecret(),
            'x-api-version' => self::API_VERSION,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    // -- Beneficiary detail accessors --

    public function getBeneficiaryId(): ?string
    {
        return $this->getParameter('beneficiaryId');
    }

    public function setBeneficiaryId(?string $value): self
    {
        return $this->setParameter('beneficiaryId', $value);
    }

    public function getBeneficiaryName(): ?string
    {
        return $this->getParameter('beneficiaryName');
    }

    public function setBeneficiaryName(?string $value): self
    {
        return $this->setParameter('beneficiaryName', $value);
    }

    public function getBeneficiaryEmail(): ?string
    {
        return $this->getParameter('beneficiaryEmail');
    }

    public function setBeneficiaryEmail(?string $value): self
    {
        return $this->setParameter('beneficiaryEmail', $value);
    }

    public function getBeneficiaryPhone(): ?string
    {
        return $this->getParameter('beneficiaryPhone');
    }

    public function setBeneficiaryPhone(?string $value): self
    {
        return $this->setParameter('beneficiaryPhone', $value);
    }

    public function getBankAccount(): ?string
    {
        return $this->getParameter('bankAccount');
    }

    public function setBankAccount(?string $value): self
    {
        return $this->setParameter('bankAccount', $value);
    }

    public function getBankIfsc(): ?string
    {
        return $this->getParameter('bankIfsc');
    }

    public function setBankIfsc(?string $value): self
    {
        return $this->setParameter('bankIfsc', $value);
    }

    public function getVpa(): ?string
    {
        return $this->getParameter('vpa');
    }

    public function setVpa(?string $value): self
    {
        return $this->setParameter('vpa', $value);
    }

    public function getTransferMode(): ?string
    {
        return $this->getParameter('transferMode');
    }

    public function setTransferMode(?string $value): self
    {
        return $this->setParameter('transferMode', $value);
    }
}
