<?php

namespace Omnipay\Cashfree;

use Omnipay\Common\AbstractGateway;
use Omnipay\Cashfree\Message\PurchaseRequest;
use Omnipay\Cashfree\Message\CompletePurchaseRequest;
use Omnipay\Cashfree\Message\Payout\CreateBeneficiaryRequest;
use Omnipay\Cashfree\Message\Payout\PayoutRequest;
use Omnipay\Cashfree\Message\Payout\PayoutStatusRequest;
use Omnipay\Cashfree\Message\Payout\ValidateBankAccountRequest;

class Gateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'Cashfree';
    }

    public function getDefaultParameters(): array
    {
        return [
            'clientId' => '',
            'clientSecret' => '',
            'payoutClientId' => '',
            'payoutClientSecret' => '',
            'testMode' => false,
        ];
    }

    // -- PG Credentials --

    public function getClientId(): string
    {
        return $this->getParameter('clientId');
    }

    public function setClientId(string $value): self
    {
        return $this->setParameter('clientId', $value);
    }

    public function getClientSecret(): string
    {
        return $this->getParameter('clientSecret');
    }

    public function setClientSecret(string $value): self
    {
        return $this->setParameter('clientSecret', $value);
    }

    // -- Payout Credentials (separate from PG) --

    public function getPayoutClientId(): string
    {
        return $this->getParameter('payoutClientId');
    }

    public function setPayoutClientId(string $value): self
    {
        return $this->setParameter('payoutClientId', $value);
    }

    public function getPayoutClientSecret(): string
    {
        return $this->getParameter('payoutClientSecret');
    }

    public function setPayoutClientSecret(string $value): self
    {
        return $this->setParameter('payoutClientSecret', $value);
    }

    // -- Payment Gateway Operations --

    public function purchase(array $parameters = []): PurchaseRequest
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = []): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }

    // -- Payout Operations --

    public function createBeneficiary(array $parameters = []): CreateBeneficiaryRequest
    {
        return $this->createRequest(CreateBeneficiaryRequest::class, $parameters);
    }

    public function payout(array $parameters = []): PayoutRequest
    {
        return $this->createRequest(PayoutRequest::class, $parameters);
    }

    public function payoutStatus(array $parameters = []): PayoutStatusRequest
    {
        return $this->createRequest(PayoutStatusRequest::class, $parameters);
    }

    public function validateBankAccount(array $parameters = []): ValidateBankAccountRequest
    {
        return $this->createRequest(ValidateBankAccountRequest::class, $parameters);
    }
}
