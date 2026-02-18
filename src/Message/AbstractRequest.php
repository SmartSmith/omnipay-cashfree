<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;

abstract class AbstractRequest extends OmnipayAbstractRequest
{
    protected const API_VERSION = '2023-08-01';
    protected const SANDBOX_ENDPOINT = 'https://sandbox.cashfree.com/pg';
    protected const PRODUCTION_ENDPOINT = 'https://api.cashfree.com/pg';

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

    public function getCustomerId(): ?string
    {
        return $this->getParameter('customerId');
    }

    public function setCustomerId(string $value): self
    {
        return $this->setParameter('customerId', $value);
    }

    public function getCustomerPhone(): ?string
    {
        return $this->getParameter('customerPhone');
    }

    public function setCustomerPhone(string $value): self
    {
        return $this->setParameter('customerPhone', $value);
    }

    public function getCustomerName(): ?string
    {
        return $this->getParameter('customerName');
    }

    public function setCustomerName(string $value): self
    {
        return $this->setParameter('customerName', $value);
    }

    public function getCustomerEmail(): ?string
    {
        return $this->getParameter('customerEmail');
    }

    public function setCustomerEmail(string $value): self
    {
        return $this->setParameter('customerEmail', $value);
    }

    public function getPaymentMethods(): ?string
    {
        return $this->getParameter('paymentMethods');
    }

    public function setPaymentMethods(string $value): self
    {
        return $this->setParameter('paymentMethods', $value);
    }

    protected function getBaseEndpoint(): string
    {
        return $this->getTestMode() ? self::SANDBOX_ENDPOINT : self::PRODUCTION_ENDPOINT;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'x-client-id' => $this->getClientId(),
            'x-client-secret' => $this->getClientSecret(),
            'x-api-version' => self::API_VERSION,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
